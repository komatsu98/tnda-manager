<?php

namespace App\Http\Controllers;

use App\Contract;
use App\User;
use Carbon\Carbon;
use Exception;
use Storage;
use Illuminate\Http\Request;
use App\Util;
use App\MonthlyMetric;
use App\Transaction;
use App\Comission;

class ComissionCalculatorController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->designation_code = Util::get_designation_code();
    }

    public function cal($agent_code)
    {
        $agent = User::where(['agent_code' => $agent_code])->first();
        $data = [];
        $data['twork'] = $this->getTwork($agent);
        $data['tp'] = $this->getTp($agent);
        $data['pos'] = $this->getPos($agent);
        $data['hpos'] = $this->getHpos($agent);
        $data['npos'] = $this->getNpos($agent);
        $data['CC'] = $this->getCC($agent, 0, 3);
        $data['FYP'] = $this->getFYP($agent, 0, 3);
        $data['FYC'] = $this->getFYC($agent, 0, 3);
        $data['RYPp'] = $this->getRYP($agent, $status = "p", 0, 14);
        $data['RYPr'] = $this->getRYP($agent, $status = "r", 0, 14);
        $data['isAA'] = $this->getIsAA($agent, 2);
        $data['K2'] = $this->getK2($agent);
        $data['RYPp'] = $this->calcThisMonthRYPp($agent);
        return $data;
    }

    private function getTwork($agent, $month_back = 0)
    {
        $to = Carbon::now()->subMonths($month_back);
        $from = Carbon::createFromFormat('Y-m-d', $agent->alloc_code_date);
        $twork = $to->diffInMonths($from);
        return $twork;
    }

    private function getTp($agent, $month_back = 0)
    {
        $to = Carbon::now()->subMonths($month_back);
        $from = Carbon::createFromFormat('Y-m-d', $agent->promote_date);
        $tp = $to->diffInMonths($from);
        return $tp;
    }

    private function getPos($agent)
    {
        return $agent->designation_code;
    }

    private function getHpos($agent)
    {
        return $agent->highest_designation_code;
    }

    private function getNpos($agent)
    {
        $pos_list = array_keys($this->designation_code);
        $cur_pos_index = array_search($agent->designation_code, $pos_list);
        return $pos_list[$cur_pos_index];
    }

    private function getCC($agent, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        $CCs = $agent->monthlyMetrics()->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])
            ->selectRaw('sum(CC) as count')
            ->get();
        $countCC = 0;
        if (count($CCs)) {
            $countCC = intval($CCs[0]->count);
        }
        return $countCC;
    }

    private function calcThisMonthCC($agent)
    {
        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        $CCs = Contract::where([
            ['release_date', '>=', $from],
            ['agent_code', '>=', $agent->agent_code],
        ])
            ->selectRaw('sum(CC) as count')
            ->get();
        $countCC = 0;
        if (count($CCs)) {
            $countCC = intval($CCs[0]->count);
        }
        return $countCC;
    }

    private function getFYP($agent, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        $FYPs = $agent->monthlyMetrics()->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])
            ->selectRaw('sum(FYP) as count')
            ->get();
        $countFYP = 0;
        if (count($FYPs)) {
            $countFYP = intval($FYPs[0]->count);
        }
        return $countFYP;
    }

    private function calcThisMonthFYP($agent)
    {
        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        $FYPs = Transaction::where([
            ['trans_date', '>=', $from],
            ['agent_code', '>=', $agent->agent_code],
        ])
            ->selectRaw('sum(premium_received) as count')
            ->get();
        $countFYP = 0;
        if (count($FYPs)) {
            $countFYP = intval($FYPs[0]->count);
        }
        return $countFYP;
    }

    private function getFYC($agent, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        $FYCs = $agent->monthlyMetrics()->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])
            ->selectRaw('sum(FYC) as count')
            ->get();
        $countFYC = 0;
        if (count($FYCs)) {
            $countFYC = intval($FYCs[0]->count);
        }
        return $countFYC;
    }

    private function calcThisMonthFYC($agent)
    {
        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        $FYCs = Comission::where([
            ['com_date', '>=', $from],
            ['agent_code', '>=', $agent->agent_code],
        ])
            ->selectRaw('sum(amount) as count')
            ->get();
        $countFYC = 0;
        if (count($FYCs)) {
            $countFYC = intval($FYCs[0]->count);
        }
        return $countFYC;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param status (r: phải thu, p: thực thu)
     * @return 
     */

    private function getRYP($agent, $status = "p", $month_back = 0, $month_range = 0)
    {
        $to = Carbon::now()->subMonths($month_back)->format('Y-m-d');
        $to_back_two = Carbon::now()->subMonths($month_back + 2);
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range)->format('Y-m-d');
        $countRYP = 0;
        if ($status == "p") {
            $RYPs = $agent->monthlyMetrics()->where([
                ['month', '>=', $from],
                ['month', '<=', $to]
            ])
                ->selectRaw('sum(RYP) as count')
                ->get();
            if (count($RYPs)) {
                $countRYP = intval($RYPs[0]->count);
            }
        } else if ($status == "r") {
            $mcontracts = $agent->contracts()->where([
                ['maturity_date', '<=', $to],
                ['maturity_date', '>=', $to_back_two],
                ['ack_date', '>=', $from]
            ])
                ->whereIn('status_code', ['RL', 'IF'])
                ->selectRaw('sum(renewal_premium_required) as RYP')
                ->get();
            if (count($mcontracts)) {
                $countRYP = intval($mcontracts[0]->RYP);
            }
        }

        return $countRYP;
    }

    private function calcThisMonthRYPp($agent)
    {
        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        $RYPs = Transaction::where([
            ['trans_date', '>=', $from],
            ['agent_code', '>=', $agent->agent_code],
        ])
            ->whereHas('contract', function ($query) {
                $query->whereIn('status_code', ['MA']);
            })
            ->selectRaw('sum(premium_received) as count')
            ->get();
        $countRYP = 0;
        if (count($RYPs)) {
            $countRYP = intval($RYPs[0]->count);
        }
        return $countRYP;
    }

    private function getIsAA($agent, $month_back = 0, $FYP_month = null, $CC_month = null)
    {
        $month_start = Carbon::now()->subMonths($month_back)->startOfMonth()->format('Y-m-d');
        $month_end = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $FYP_month = $FYP_month === null ? $this->getFYP($agent, $month_back) : $FYP_month;
        $CC_month = $CC_month === null ? $this->getCC($agent, $month_back) : $CC_month;
        $working_month = !$agent->terminate_date || $agent->terminate_date > $month_end;
        $isAA = $working_month && $FYP_month >= 6000000 && $CC_month >= 1;
        return $isAA;
    }

    private function getK2($agent, $month_back = 0, $RYPp_month = null, $RYPr_month = null)
    {
        $RYPp_month = $RYPp_month === null ? $this->getRYP($agent, $status = "p", $month_back, 14) : $RYPp_month;
        $RYPr_month = $RYPr_month === null ? $this->getRYP($agent, $status = "r", $month_back, 14) : $RYPr_month;
        if ($RYPr_month == 0) {
            return 1;
        }
        return round($RYPp_month / $RYPr_month, 4);
    }
}
