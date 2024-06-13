<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['tag', 'visit_count'];

    public function items()
    {
        return $this->belongsToMany(Style::class)->using(StyleTag::class);
    }
}
