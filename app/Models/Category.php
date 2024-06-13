<?php

namespace App\Models;

use App\CPU\Helpers;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Category extends Model
{



    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}