<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comission extends Model
{
    protected $table = 'comissions';
    protected $fillable = ['agent_code', 'amount', 'contract_id'];
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
}
