<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    //
    protected $table = 'histories';
    protected $fillable = ['user_id', 'master_id', 'bet_secs', 'amount', 'type', 'result', 'gain', 'is_demo'];

    /**
     * The user that history belonged to.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

}
