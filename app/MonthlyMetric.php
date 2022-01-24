<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MonthlyMetric extends Model
{
    protected $table = 'monthly_metrics';
    protected $fillable = ['agent_code', 'FYC', 'FYC_all', 'FYP', 'FYP_all', 'APE', 'APE_all', 'RYP', 'IP', 'CC', 'K2', 'AA', 'month', 'AAU', 'AU', 'U', 'AHC', 'HC'];
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
