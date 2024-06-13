<?php

namespace App\Jobs;

use App\Models\Order;
use App\Providers\OrderCompleted;
use App\Providers\OrderProccessing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpParser\JsonDecoder;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
   private $baseUrl = 'http://185.208.206.238:8188';
    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order,)
    {
        
        $this->order=$order;
    }
    
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        OrderProccessing::dispatch('initializing job');
        
        $prompt_id='';

        
         $photo =storage_path('app/public/orders/'.$this->order->uploaded_img);

        $new_client = new \GuzzleHttp\Client();
        $response = $new_client->post( $this->baseUrl.'/upload/image', [
            'headers' => [
                'Accept' => 'application/json',
                
            ],
            'multipart' => [[
                'name'     => 'image',
                'filename' => $this->order->uploaded_img,
              
                'contents' => fopen( $photo, 'r' ),
            ]]
        ]);
          
            $result_code = $response->getStatusCode();

        if ($result_code ==200) {
            OrderProccessing::dispatch('image uploaded');
            //load the predefined style json
            $json = Storage::disk('public')->get('/styles_json/'.$this->order->style_id.'.json');
            $json = str_replace("input_image.png",$this->order->uploaded_img,$json);
            $data = json_decode($json);

            // $response= Http::withBody(json_encode($data), 'application/json')->post($this->baseUrl.'/prompt');
            $response = $new_client->post($this->baseUrl.'/prompt', ['body' => json_encode($data)]);
            $result_code = $response->getStatusCode();
            if ($result_code ==200) {
                OrderProccessing::dispatch('style uploaded');
                $prompt_id = json_decode($response->getBody(),true)['prompt_id'];
                
               
            while(true){
                $img =$this->get_history($prompt_id);
               if ($img) {
               // OrderCompleted::dispatch(asset('storage/' . $img));
               // download image and update user order field
               $response= Http::timeout(600)->get($this->baseUrl.'/view?filename='.$img);
               Storage::disk('public')->put($img, $response->body());
               OrderCompleted::dispatch($img);
                break;
               } else {
               // Log::info("sleeping");
               $progress = $this->get_progress();
                 OrderProccessing::dispatch($progress);
                sleep(15);
               }
                
            }
           }
        
            
        }
    }

    private function get_history(String $prompt_id){
        $response =Http::get($this->baseUrl.'/history/'.$prompt_id);
        $data = json_decode($response,true);
        if (!empty($data)) {
            $img = $response[$prompt_id]['outputs'][9]['images'][0]["filename"];
          // $img=$this->findImage($data);
         
           
            return $img;
        }
        else {
            return null;
        }
        
        
    }

    private function get_progress(){
        $response=Http::get($this->baseUrl.'/progress');
      
        if ($response->successful()) {
            $result = json_decode($response,true);
            if (!empty($result)) {
                # code...
                $progress = $result['value']/$result['total'] *100;
                return strval($progress).'%';
            }
            return 'proccessing...';
        } else {
            return 'proccessing...';
        }
        
    }

    function findImage($data){
        $imageFilename = null;
        foreach ($data as $key => $value) {
            if (isset($value['outputs'])) {
                $imageFilename = $this->findImageFilename($value['outputs']);
                if ($imageFilename !== null) {
                    break;
                }
            }
}
    }
    function findImageFilename($data) {
        foreach ($data as $key => $value) {
            if ($key === 'filename' && is_string($value)) {
                return $value;
            }
    
            if (is_array($value) || is_object($value)) {
                $result = $this->findImageFilename((array)$value);
                if ($result !== null) {
                    return $result;
                }
            }
        }
    
        return null;
    }
}
