<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    protected $table = 'targets';
    protected $fillable = ['agent_code', 'term_code', 'name', 'amount', 'start_date', 'end_date'];
    // protected $guarded = ['id'];
    protected $casts = [
    ];

    /**
     * The user that history belonged to.
     */
    public function agent()
    {
        return $this->belongsTo('App\User', 'agent_code', 'agent_code');
    }
}
