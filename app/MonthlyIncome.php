<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MonthlyIncome extends Model
{
    protected $table = 'monthly_income';
    protected $fillable = ['agent_code', 'month', 'month_valid', 'is_qualified', 'ag_rwd_hldlth', 'ag_hh_bhcn', 'ag_rwd_dscnhq', 'ag_rwd_tndl', 'ag_rwd_tcldt_dm', 'ag_rwd_tthd', 'dm_rwd_hldlm', 'dm_rwd_dscnht', 'dm_rwd_qlhtthhptt', 'dm_rwd_qlhqthhptt', 'dm_rwd_tnql', 'dm_rwd_ptptt', 'dm_rwd_gt', 'dm_rwd_tcldt_sdm', 'dm_rwd_tcldt_am', 'dm_rwd_tcldt_rd', 'dm_rwd_dthdtptt', 'rd_rwd_dscnht', 'rd_hh_nsht', 'rd_rwd_dctkdq', 'rd_rwd_tndhkd', 'rd_rwd_dbgdmht', 'rd_rwd_tcldt_srd', 'rd_rwd_tcldt_td', 'rd_rwd_dthdtvtt'];
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
