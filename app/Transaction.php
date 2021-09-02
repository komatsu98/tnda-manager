<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';
    protected $fillable = ['agent_code', 'contract_code', 'premium_received', 'trans_date'];
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
        return $this->belongsTo('App\Contract');
    }
}
