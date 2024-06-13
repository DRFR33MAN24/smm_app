<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;


class Review extends Model
{
    protected $casts = [
        'style_id'  => 'integer',
        'user_id' => 'integer',
        'rating'      => 'integer',
        'status'      => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    protected $fillable = [
        'style_id',
        'user_id',

        'order_id',
        'comment',

        'rating',
        'status',
    ];

    public function scopeActive($query)
    {
        $query->where('status', 1);
    }

    public function style()
    {
        return $this->belongsTo(Style::class, 'style_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }



    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
