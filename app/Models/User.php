<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'f_name', 'l_name', 'name', 'email', 'password', 'phone', 'image', 'login_medium', 'is_active', 'social_id', 'is_phone_verified', 'temporary_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'integer',
        'is_phone_verified' => 'integer',
        'is_email_verified' => 'integer',
        'wallet_balance' => 'float'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }
}
