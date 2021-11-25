<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';
    protected $fillable = ['agent_code', 'contract_id', 'premium_received', 'trans_date', 'product_code', 'contract_product_id'];
    protected $casts = [
    ];

    /**
     * The user that history belonged to.
     */
    public function agent()
    {
        return $this->belongsTo('App\User');
    }

    public function contract()
    {
        return $this->belongsTo('App\Contract', 'contract_id', 'id');
    }

    public function contract_product()
    {
        return $this->belongsTo('App\ContractProduct', 'contract_product_id', 'id');
    }
}
