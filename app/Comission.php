<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comission extends Model
{
    protected $table = 'comissions';
    protected $fillable = ['agent_code', 'amount'];
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
