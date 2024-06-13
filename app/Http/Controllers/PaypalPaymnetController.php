<?php

namespace App\Http\Controllers;


use App\CPU\Helpers;

use App\Models\BusinessSetting;

use App\Models\Order;

use App\Models\User;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PayPal\Api\Amount;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Common\PayPalModel;
use PayPal\Rest\ApiContext;

class PaypalPaymentController extends Controller
{
    public function __construct()
    {
        $paypal_conf = Config::get('paypal');
        $this->_api_context = new ApiContext(new OAuthTokenCredential(
                $paypal_conf['client_id'],
                $paypal_conf['secret'])
        );
        $this->_api_context->setConfig($paypal_conf['settings']);
    }

    public function payWithpaypal(Request $request)
    {
       


        $currency_code="USD";
       
        $value = Session('value');

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $items_array = [];

        $item = new Item();
        $item->setName('Products')
            ->setCurrency($currency_code)
            ->setQuantity(1)
            ->setPrice($value);
        array_push($items_array, $item);

        $item_list = new ItemList();
        $item_list->setItems($items_array);

        $amount = new Amount();
        $amount->setCurrency($currency_code)
            ->setTotal($value);

        // $tran = OrderManager::gen_unique_id();
        // session()->put('transaction_ref', $tran);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($item_list)
            ->setDescription($tran);

        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(URL::route('paypal-status'))
            ->setCancelUrl(URL::route('home'));

        $payment = new Payment();
        $payment->setIntent('Sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirect_urls)
            ->setTransactions(array($transaction));
        try {
            $payment->create($this->_api_context);
            foreach ($payment->getLinks() as $link) {
                if ($link->getRel() == 'approval_url') {
                    $redirect_url = $link->getHref();
                    break;
                }
            }

            /** add payment ID to session **/
            Session::put('paypal_payment_id', $payment->getId());
            if (isset($redirect_url)) {
                return Redirect::away($redirect_url);
            }

        } catch (\Exception $ex) {
            Toastr::error('Payment process is failed.');
            return back();
        }

        Session::put('error', 'Unknown error occurred');
        return back();

    }

    public function getPaymentStatus(Request $request)
    {
        $payment_id = $request['paymentId'];
        if (empty($request['PayerID']) || empty($request['token'])) {
            Session::put('error', 'Payment failed');
            return back();
        }

        $payment = Payment::get($payment_id, $this->_api_context);
        $execution = new PaymentExecution();
        $execution->setPayerId($request['PayerID']);

        /**Execute the payment **/
        $result = $payment->execute($execution, $this->_api_context);
        if ($result->getState() == 'approved') {
            $unique_id = OrderManager::gen_unique_id();
            $order_ids = [];
            foreach (CartManager::get_cart_group_ids() as $group_id) {
                $data = [
                    'payment_method' => 'paypal',
                    'order_status' => 'confirmed',
                    'payment_status' => 'paid',
                    'transaction_ref' => \session('transaction_ref'),
                    'order_group_id' => $unique_id,
                    'cart_group_id' => $group_id
                ];
                $order_id = OrderManager::generate_order($data);
                array_push($order_ids, $order_id);
            }
           

            if (session()->has('payment_mode') && \session('payment_mode') == 'app') {
                return redirect()->route('payment-success');
            }

            if (auth('customer')->check()) {
                Toastr::success('Payment success.');
                return view(VIEW_FILE_NAMES['order_complete']);
            }
        }

        if (session()->has('payment_mode') && \session('payment_mode') == 'app') {
            return redirect()->route('payment-fail');
        }

        if (auth('customer')->check()) {
            Toastr::error('Payment failed.');
            return back();
        }
    }
}