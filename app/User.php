<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'users';

    protected $fillable = [
        'username', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'api_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the sessions for the user.
     */
    public function sessions()
    {
        return $this->hasMany('App\SessionLog', 'agent_id');
    }

    public function contracts()
    {
        return $this->hasMany('App\Contract', 'agent_code', 'agent_code');
    }

    public function comissions()
    {
        return $this->hasMany('App\Comission', 'agent_code', 'agent_code');
    }

    public function transactions()
    {
        return $this->hasMany('App\Transaction', 'agent_code', 'agent_code');
    }

    public function supervisor() {
        return $this->belongsTo('App\User', 'supervisor_code', 'agent_code');
    }

    public function directAgents()
    {
        return $this->hasMany('App\User', 'supervisor_code', 'agent_code');
    }

    public function monthlyIncomes()
    {
        return $this->hasMany('App\MonthlyIncome', 'agent_code', 'agent_code');
    }

    public function monthlyMetrics()
    {
        return $this->hasMany('App\MonthlyMetric', 'agent_code', 'agent_code');
    }

}
