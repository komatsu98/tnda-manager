<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $table = 'contracts';
    protected $fillable = ['customer_type', 'agent_code', 'customer_id', 'contract_code', 'partner_contract_code', 'partner_code', 'submit_date', 'ack_date', 'status_code', 'info_awaiting', 'contract_year', 'term_code', 'release_date', 'expire_date', 'maturity_date', 'active_require_update_time', 'calc_status'];
    // 'product_code', 'sub_product_code', 'premium', 'premium_term', 'premium_received', 'renewal_premium_required'
    
    protected $casts = [
    ];

    /**
     * The user that belonged to.
     */
    public function agent()
    {
        return $this->belongsTo('App\User', 'agent_code', 'agent_code');
    }

    public function customer()
    {
        return $this->belongsTo('App\Customer', 'customer_id', 'id');
    }

    public function products()
    {
        return $this->hasMany('App\ContractProduct', 'contract_id', 'id');
    }

}
