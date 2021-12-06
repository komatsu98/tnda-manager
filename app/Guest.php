<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $table = 'guests';
    protected $fillable = ['username', 'password'];
    // protected $guarded = ['id'];
    protected $casts = [
    ];
}
