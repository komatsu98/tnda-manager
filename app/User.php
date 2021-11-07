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
        "id","is_admin","username","password","password2","agent_code","IFA","IFA_branch","IFA_ref_code","IFA_ref_name","IFA_supervisor_code","IFA_supervisor_name","IFA_supervisor_designation_code","IFA_TD_code","IFA_TD_name","IFA_start_date","fullname","image","join_date","alloc_code_date","terminate_date","resident_address","resident_province","business_address","business_province","gender","marital_status_code","native_place","mobile_phone","day_of_birth","identity_num","identity_alloc_date","identity_alloc_place","email","designation_code","highest_designation_code","promote_date","supervisor_code","reference_code","branch_id","email_verified_at","active","created_at","updated_at"
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'api_token', 'password2'
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

    public function reference() {
        return $this->belongsTo('App\User', 'reference_code', 'agent_code');
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

    public function promotions() {
        return $this->hasMany('App\Promotion', 'agent_code', 'agent_code');
    }

}
