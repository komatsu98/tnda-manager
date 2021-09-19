<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MonthlyMetric extends Model
{
    protected $table = 'monthly_metrics';
    protected $fillable = ['agent_code'];
    protected $casts = [
    ];

    /**
     * The user that history belonged to.
     */
    public function agent()
    {
        return $this->belongsTo('App\User');
    }
}
