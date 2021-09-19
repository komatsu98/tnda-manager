<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MonthlyIncome extends Model
{
    protected $table = 'monthly_income';
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
}
