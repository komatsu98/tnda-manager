<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comission extends Model
{
    protected $table = 'comissions';
    protected $fillable = ['agent_code', 'amount', 'contract_id', 'transaction_id', 'received_date', 'is_raw'];
    protected $casts = [
    ];

    /**
     * The user that history belonged to.
     */
    public function agent()
    {
        return $this->belongsTo('App\User', 'agent_code', 'agent_code');
    }

    public function contract()
    {
        return $this->belongsTo('App\Contract');
    }

    public function transaction()
    {
        return $this->belongsTo('App\Transaction', 'transaction_id', 'id');
    }
}
