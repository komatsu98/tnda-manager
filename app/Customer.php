<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';
    protected $fillable = ['fullname', 'email', 'mobile_phone', 'fb_id'];
    protected $casts = [
    ];
}
