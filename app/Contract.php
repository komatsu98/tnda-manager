<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $table = 'contracts';
    protected $fillable = ['agent_code', 'customerlient_id', 'contract_code', 'submit_date', 'ack_date', 'status_code', 'contract_year', 'product_code', 'sub_product_code', 'premium', 'term_code', 'release_date', 'expire_date', 'maturity_code'];
    protected $casts = [
    ];

    /**
     * The user that belonged to.
     */
    public function agent()
    {
        return $this->belongsTo('App\User');
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer');
    }

}
