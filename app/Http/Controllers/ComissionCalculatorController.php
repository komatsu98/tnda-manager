<?php

namespace App\Http\Controllers;
use App\User;
use Carbon\Carbon;
use Exception;
use Storage;
use Illuminate\Http\Request;
use App\Util;
use App\MonthlyMetric;


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

    public function cal($agent_code) {
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
        $data['RYPp'] = $this->getRYP($agent, "p", 0, 3);
        $data['RYPr'] = $this->getRYP($agent, "r", 0, 3);
        return $data;
    }
    
    private function getTwork($agent, $month_back = 0) {
        $to = Carbon::now()->subMonths($month_back);
        $from = Carbon::createFromFormat('Y-m-d', $agent->join_date);
        $twork = $to->diffInMonths($from);
        return $twork;
    }

    private function getTp($agent, $month_back = 0) {
        $to = Carbon::now()->subMonths($month_back);
        $from = Carbon::createFromFormat('Y-m-d', $agent->promote_date);
        $tp = $to->diffInMonths($from);
        return $tp;
    }

    private function getPos($agent) {
        return $agent->designation_code;
    }

    private function getHpos($agent) {
        return $agent->highest_designation_code;
    }

    private function getNpos($agent) {
        $pos_list = array_keys($this->designation_code);
        $cur_pos_index = array_search($agent->designation_code, $pos_list);
        return $pos_list[$cur_pos_index + 1];
    }

    private function getCC($agent, $month_back = 0, $month_range = 1) {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back);
        $from = $from->subMonths($month_range-1)->startOfMonth()->format('Y-m-d');
        $CCs = $agent->monthlyMetrics()->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])
        ->selectRaw('sum(CC) as count')
        ->get();
        $countCC = 0;
        if(count($CCs)) {
            $countCC = intval($CCs[0]->count);
        }
        return $countCC;
    }

    private function getFYP($agent, $month_back = 0, $month_range = 1) {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back);
        $from = $from->subMonths($month_range-1)->startOfMonth()->format('Y-m-d');
        $FYPs = $agent->monthlyMetrics()->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])
        ->selectRaw('sum(FYP) as count')
        ->get();
        $countFYP = 0;
        if(count($FYPs)) {
            $countFYP = intval($FYPs[0]->count);
        }
        return $countFYP;
    }

    private function getFYC($agent, $month_back = 0, $month_range = 1) {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back);
        $from = $from->subMonths($month_range-1)->startOfMonth()->format('Y-m-d');
        $FYCs = $agent->monthlyMetrics()->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])
        ->selectRaw('sum(FYC) as count')
        ->get();
        $countFYC = 0;
        if(count($FYCs)) {
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

    private function getRYP($agent, $status = "p", $month_back = 0, $month_range = 1) {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back);
        $from = $from->subMonths($month_range-1)->startOfMonth()->format('Y-m-d');
        $countRYP = 0;
        if($status == "p") {
            $RYPs = $agent->monthlyMetrics()->where([
                ['month', '>=', $from],
                ['month', '<=', $to]
            ])
            ->selectRaw('sum(RYP) as count')
            ->get();
            if(count($RYPs)) {
                $countRYP = intval($RYPs[0]->count);
            }
        } else if ($status == "r") {
            $countRYP = 1;
        }
        
        return $countRYP;
    }
}
