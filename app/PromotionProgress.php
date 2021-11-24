<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PromotionProgress extends Model
{
    protected $table = 'promotion_orogress';
    protected $fillable = ['agent_code', 'pro_code', 'req_id', 'month', 'is_done'];
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
