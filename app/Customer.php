<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';
    protected $fillable = ['fullname', 'email', 'mobile_phone', 'fb_id', 'address', 'beneficiary_from_id', 'identity_num'];
    protected $casts = [
    ];

    public function contracts()
    {
        return $this->hasMany('App\Contract', 'customer_id', 'id');
    }
}
