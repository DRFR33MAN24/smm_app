<?php

namespace App\Models;

use App\CPU\Helpers;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Card extends Model
{



    public function telecomProvider()
    {
        return $this->belongsTo(TelecomProvider::class);
    }


}