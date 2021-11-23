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
use App\MonthlyIncome;
use App\Transaction;
use App\Comission;
use App\Promotion;

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

    public function calc($agent_code)
    {
        $agent = User::where(['agent_code' => $agent_code])->first();
        $data = [];
        $data['twork'] = $this->getTwork($agent);
        $data['tp'] = $this->getTp($agent);
        $data['pos'] = $this->getPos($agent);
        $data['hpos'] = $this->getHpos($agent);
        $data['npos'] = $this->getNpos($agent);
        $data['thisMonthMetric'] = $this->updateThisMonthMetric($agent);
        $data['dr'] = $this->getDr($agent);
        $data['drCodes'] = $this->getDr($agent)->pluck('agent_code')->toArray();
        $data['depDr'] = $this->getDepDr($agent);
        $data['depDrCodes'] = $data['depDr']->pluck('agent_code')->toArray();
        $data['teamAGCodes'] = $this->getWholeTeamCodes($agent, true);
        $data['isDrAreaManager'] = $this->getIsDrAreaManager($agent);
        $data['thisMonthReward'] = $this->updateThisMonthReward($agent, $data);
        return $data;
    }    

    public function updateThisMonthAllStructure($agent) {
        $this->updateThisMonthAgent($agent);
        while($supervisor = $agent->supervisor) {
            $this->updateThisMonthAgent($supervisor);
            $agent = $supervisor;
        }
    }

    public function updateThisMonthAgent($agent) {
        $data = [];
        $data['twork'] = $this->getTwork($agent);
        $data['tp'] = $this->getTp($agent);
        $data['pos'] = $this->getPos($agent);
        $data['hpos'] = $this->getHpos($agent);
        $data['npos'] = $this->getNpos($agent);
        $data['thisMonthMetric'] = $this->updateThisMonthMetric($agent);
        $data['dr'] = $this->getDr($agent);
        $data['drCodes'] = $this->getDr($agent)->pluck('agent_code')->toArray();
        $data['depDr'] = $this->getDepDr($agent);
        $data['depDrCodes'] = $data['depDr']->pluck('agent_code')->toArray();
        $data['teamAGCodes'] = $this->getWholeTeamCodes($agent, true);
        $data['isDrAreaManager'] = $this->getIsDrAreaManager($agent);
        $data['thisMonthReward'] = $this->updateThisMonthReward($agent, $data);
        $data['thisMonthPromotionReq'] = $this->updateThisMonthPromotion($agent, $data);
    }

    public function updateThisMonthPromotionReq($agent, $data) {

    }

    public function updateThisMonthAllMetrics()
    {
        $designations = Util::get_designation_code();
        foreach ($designations as $dc => $name) {
            $agents = User::where(['designation_code' => $dc])->get();
            foreach ($agents as $agent) {
                $this->updateThisMonthMetric($agent);
            }
        }
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
        $valid_month = Carbon::now()->addMonths(1)->startOfMonth()->format('Y-m-d');
        $promotion = $agent->promotions()->where(['valid_month' => $valid_month])->first();
        if ($promotion) return $promotion->new_designation_code;
        return null;
        // $pos_list = array_keys($this->designation_code);
        // $cur_pos_index = array_search($agent->designation_code, $pos_list);
        // return $pos_list[$cur_pos_index];
    }

    public function updateThisMonthMetric($agent)
    {
        $metric = $this->calcThisMonthMetric($agent);
        $month = Carbon::now()->startOfMonth()->format('Y-m-d');
        $metric['month'] = $month;
        $old_metric = $agent->monthlyMetrics()->where(['month' => $month])->first();
        if ($old_metric) $old_metric->update($metric);
        else MonthlyMetric::create($metric);
        return $metric;
    }

    private function calcThisMonthMetric($agent)
    {
        $data = [];
        $data['agent_code'] = $agent->agent_code;
        $data['FYC'] = $this->calcThisMonthFYC($agent);
        $data['FYP'] = $this->calcThisMonthFYP($agent);
        $data['RYP'] = $this->calcThisMonthRYPp($agent);
        $data['RYPr'] = $this->calcThisMonthRYPr($agent);
        $data['K2'] = $this->calcK2($agent, 0, $data['RYP'], $data['RYPr']);
        $data['CC'] = $this->calcThisMonthCC($agent);
        $data['AA'] = $this->calcIsAA($agent, 0, $data['FYP'], $data['CC']);
        $data['AU'] = count($this->getWholeTeamCodes($agent, true));
        return $data;
    }

    private function calcThisMonthFYC($agent)
    {
        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        $FYCs = $agent->comissions()->where([
            ['received_date', '>=', $from]
        ])
            ->selectRaw('sum(amount) as count')
            ->get();
        $countFYC = 0;
        if (count($FYCs)) {
            $countFYC = intval($FYCs[0]->count);
        }
        return $countFYC;
    }

    private function calcThisMonthCC($agent)
    {
        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        $CCs = $agent->contracts()->where([
            ['release_date', '>=', $from]
        ])
            ->selectRaw('sum(1) as count')
            ->get();
        $countCC = 0;
        if (count($CCs)) {
            $countCC = intval($CCs[0]->count);
        }
        return $countCC;
    }

    private function calcThisMonthFYP($agent, $list_product = [], $list_sub_product = [])
    {
        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        $FYPs = $agent->transactions()->where([
            ['trans_date', '>=', $from]
        ]);
        if (count($list_product) || count($list_sub_product)) {
            $FYPs = $FYPs->whereHas('contract', function ($query) use ($list_product, $list_sub_product) {
                $query->where(function ($q) use ($list_product, $list_sub_product) {
                    foreach ($list_product as $product_code) {
                        $q->orWhere('product_code', 'like', '%' . $product_code . '%');
                    }
                    foreach ($list_sub_product as $sub_product_code) {
                        $q->orWhere('sub_product_code', 'like', '%' . $sub_product_code . '%');
                    }
                });
            });
        }
        // $query = str_replace(array('?'), array('\'%s\''), $FYPs->toSql());
        // $query = vsprintf($query, $FYPs->getBindings());
        // print_r($query);
        $FYPs = $FYPs->selectRaw('sum(premium_received) as count')->get();
        $countFYP = 0;
        if (count($FYPs)) {
            $countFYP = intval($FYPs[0]->count);
        }
        return $countFYP;
    }

    private function calcIsAA($agent, $month_back = 0, $FYP_month = null, $CC_month = null)
    {
        $month_start = Carbon::now()->subMonths($month_back)->startOfMonth()->format('Y-m-d');
        $month_end = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $FYP_month = $FYP_month === null ? $this->getFYP($agent, $month_back) : $FYP_month;
        $CC_month = $CC_month === null ? $this->getCC($agent, $month_back) : $CC_month;
        $working_month = !$agent->terminate_date || $agent->terminate_date > $month_end;
        $isAA = $working_month && $FYP_month >= 6000000 && $CC_month >= 1 ? 1 : 0;
        return $isAA;
    }

    // thực thu
    private function calcThisMonthRYPp($agent)
    {
        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $ack_from = Carbon::now()->subMonths(0)->subMonths(14)->format('Y-m-d');
        $to_back_two = Carbon::now()->subMonths(2);
        $RYPs = $agent->transactions()->where([
            ['trans_date', '>=', $from],
            ['agent_code', '>=', $agent->agent_code],
        ])
            ->whereHas('contract', function ($query) use ($ack_from, $to_back_two) {
                $query->where([
                    ['maturity_date', '>=', $to_back_two],
                    ['ack_date', '>=', $ack_from]
                ])
                    ->whereIn('status_code', ['MA']);
            })
            ->selectRaw('sum(premium_received) as count')
            ->get();
        $countRYP = 0;
        if (count($RYPs)) {
            $countRYP = intval($RYPs[0]->count);
        }
        return $countRYP;
    }

    // phải thu
    private function calcThisMonthRYPr($agent)
    {
        $from = Carbon::now()->subMonths(0)->subMonths(14)->format('Y-m-d');
        // $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        $to_back_two = Carbon::now()->subMonths(2);
        $mcontracts = $agent->contracts()->where([
            ['maturity_date', '>=', $to_back_two],
            ['ack_date', '>=', $from]
        ])
            ->whereIn('status_code', ['MA'])
            ->selectRaw('sum(premium_term) as RYP')
            ->get();
        if (count($mcontracts)) {
            $countRYP = intval($mcontracts[0]->RYP);
        }
        return $countRYP;
    }

    private function calcK2($agent, $month_back = 0, $RYPp_month = null, $RYPr_month = null)
    {
        $RYPp_month = $RYPp_month === null ? $this->getRYP($agent, $status = "p", $month_back, 14) : $RYPp_month;
        $RYPr_month = $RYPr_month === null ? $this->getRYP($agent, $status = "r", $month_back, 14) : $RYPr_month;
        if ($RYPr_month == 0) {
            return 1;
        }
        return round($RYPp_month / $RYPr_month, 4);
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

    private function getTotalFYPByCodes($codes, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');

        $FYPs = MonthlyMetric::whereHas('agent', function ($query) use ($codes) {
            $query->whereIn('agent_code', $codes);
        })->where([
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

    private function getTotalFYCByCodes($codes, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');

        $FYCs = MonthlyMetric::whereHas('agent', function ($query) use ($codes) {
            $query->whereIn('agent_code', $codes);
        })->where([
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
                ->selectRaw('sum(premium_term) as RYP')
                ->get();
            if (count($mcontracts)) {
                $countRYP = intval($mcontracts[0]->RYP);
            }
        }

        return $countRYP;
    }

    private function getIsAA($agent, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        $AAs = $agent->monthlyMetrics()
            ->where([
                ['month', '>=', $from],
                ['month', '<=', $to]
            ])->selectRaw('sum(AA) as AA')
            ->get();
        $countAA = 0;
        if (count($AAs)) {
            $countAA = intval($AAs[0]->AA);
        }
        return $countAA;
    }

    private function getTotalAAByCodes($codes, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');

        $AAs = MonthlyMetric::whereHas('agent', function ($query) use ($codes) {
            $query->whereIn('agent_code', $codes);
        })->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])->selectRaw('sum(AA) as count')
            ->get();
        $countAA = 0;
        if (count($AAs)) {
            $countAA = $AAs[0]->count;
        }
        return $countAA;
    }

    private function getK2($agent, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        $K2s = $agent->monthlyMetrics()
            ->where([
                ['month', '>=', $from],
                ['month', '<=', $to]
            ])->selectRaw('sum(K2) as K2')
            ->get();
        $countK2 = 0;
        if (count($K2s)) {
            $countK2 = $K2s[0]->K2;
        }
        return $countK2;
    }

    private function getTotalK2ByCodes($codes, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');

        $K2s = MonthlyMetric::whereHas('agent', function ($query) use ($codes) {
            $query->whereIn('agent_code', $codes);
        })->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])->selectRaw('sum(K2) as count')
            ->get();
        $countK2 = 0;
        if (count($K2s)) {
            $countK2 = $K2s[0]->count;
        }
        return $countK2;
    }

    private function getDr($agent)
    {
        $dr = $agent->directUnders();
        return $dr;
    }

    private function getDepDr($agent)
    {
        $depdr = $agent->directUnders()->where(['designation_code' => 'AG']);
        return $depdr;
    }

    private function getWholeTeamCodes($supervisor, $isAGOnly = false)
    {
        $codes = [];
        $direct_unders = $supervisor->directUnders;
        if (!count($direct_unders)) {
            return [];
        } else {
            foreach ($direct_unders as $dr_under) {
                if (!$isAGOnly || $dr_under->designation_code == 'AG') array_push($codes, $dr_under->agent_code);
                $codes = array_merge($codes, $this->getWholeTeamCodes($dr_under, true));
            }
            return $codes;
        }
    }

    private function getAU($agent, $month_back = 0)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $AU = $agent->monthlyMetrics()
            ->where([
                ['month', '=', $to]
            ])->pluck('AU')->first();
        $countAU = 0;
        if ($AU) {
            $countAU = $AU->AU;
        }
        return $countAU;
    }

    private function getIsDrAreaManager($agent, $drs_builder = null)
    {
        if (!in_array($agent->designation_code, ['RD', 'TD', 'SRD'])) return false;
        if (is_null($drs_builder)) $drs = $agent->directUnders();
        else $drs = $drs_builder;
        $exist_RD_plus = $drs->whereIn('designation_code', ['RD', 'TD', 'SRD'])->first();
        return !$exist_RD_plus;
    }

    private function getTarget($agent, $term, $name, $date)
    {
        $target = $agent->targets()->where([
            ['start_date', '<=', $date],
            ['end_date', '>=', $date],
            ['name', '=', $name],
            ['term_code', '=', $term]
        ])->first();
        $amount = 0;
        if ($target) $amount = $target->amount;
        return $amount;
    }

    public function updateThisMonthReward($agent, $data)
    {
        $month = Carbon::now()->startOfMonth()->format('Y-m-d');
        // clear
        $agent->monthlyIncomes()->where(['month' => $month])->delete();
        
        // insert
        $list_reward_type = Util::get_income_code();
        $rewards = [];
        foreach ($list_reward_type as $type => $desc) {
            $rewards[$type] = $this->calcThisMonthRewardType($agent, $data, $type);
        }
        $list_reward_to_insert = [];
        foreach($rewards as $key => $list_result) {
            foreach($list_result as $result){
                $amount = $result[0];
                if(!$amount) continue;
                $valid_month = $result[1];
                $ref_agent_code = isset($result[2]) ? $result[2] : null;
                if(!isset($list_reward_to_insert[$valid_month])) $list_reward_to_insert[$valid_month] = [];
                $list_reward_to_insert[$valid_month][$key] = $amount;
                if($ref_agent_code) $list_reward_to_insert[$valid_month]['ref_agent_code'] = $ref_agent_code; 
            }
        }
        foreach($list_reward_to_insert as $valid_month => $reward) {
            $reward['month'] = $month;
            $reward['valid_month'] = $valid_month;
            $reward['agent_code'] = $agent->agent_code;
            MonthlyIncome::create($reward);
        }

        // merge
        return isset($list_reward_to_insert[$month]) ? $list_reward_to_insert[$month] : [];
    }

    private function getRewardType($agent, $type, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        $rewards = $agent->monthlyIncomes()
            ->where([
                ['valid_month', '>=', $from],
                ['valid_month', '<=', $to]
            ])->selectRaw('sum(' . $type . ') as ' . $type)
            ->get();
        $countReward = 0;
        if (count($rewards)) {
            $countReward = intval($rewards[0]->{$type});
        }
        return $countReward;
    }

    private function getTotalRewardTypeByCodes($codes, $type, $month_back = 0, $month_range = 1)
    {
        $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');

        $rewards = MonthlyIncome::whereHas('agent', function ($query) use ($codes) {
            $query->whereIn('agent_code', $codes);
        })->where([
            ['valid_month', '>=', $from],
            ['valid_month', '<=', $to]
        ]);
        // $query = str_replace(array('?'), array('\'%s\''), $rewards->toSql());
        // $query = vsprintf($query, $rewards->getBindings());
        // print_r($query);
        $rewards = $rewards->selectRaw('sum(' . $type . ') as ' . $type)
            ->get();

        $countReward = 0;
        if (count($rewards)) {
            $countReward = intval($rewards[0]->{$type});
        }
        return $countReward;
    }

    private function calcThisMonthPromotionReqType($agent, $data, $type) {
        $list_result = [];
        switch($type) {
            case 'PRO_AM_DM':
                
                break;
        }
    }

    private function calcThisMonthRewardType($agent, $data, $type)
    {
        $list_result = [];
        $result = 0;
        $valid_month = Carbon::now()->startOfMonth()->format('Y-m-d');

        switch ($type) {
            case 'ag_rwd_hldlth':
                if (!in_array($agent->designation_code, ['AG', 'DM', 'SDM', 'AM']))  break;
                if ($data['twork'] > 9) break;
                $fyc_check = $this->getFYC($agent, 0, 3);
                $cc_check = $this->getCC($agent, 0, 3);
                $result = $fyc_check >= 30000000 && $cc_check >= 3 ? 1 : 0;
                $list_result[] = [$result, $valid_month];
                break;
            case 'ag_hh_bhcn':
                if (!in_array($agent->designation_code, ['AG', 'DM', 'SDM', 'AM', 'RD', 'SRD', 'TD'])) break;
                $result = $this->getFYC($agent, 0, 1);
                $list_result[] = [$result, $valid_month];
                break;
            case 'ag_rwd_dscnhq':
                if (!$this->checkValidTpay('q')) break;
                if (!in_array($agent->designation_code, ['AG'])) break;
                $fyc_q_check = $this->getFYC($agent, 0, 3);
                $twork_check = $data['twork'];
                $k2_check = $data['thisMonthMetric']['K2'];
                if ($fyc_q_check < 20000000) {
                    if ($twork_check < 6)
                        $result = 0.4 * $fyc_q_check;
                    else if ($twork_check >= 6 && $twork_check < 14)
                        $result = 0.3 * $fyc_q_check;
                    else if ($twork_check >= 14) {
                        if ($k2_check < 0.75) break;
                        else if ($k2_check < 0.85)
                            $result = 0.3 * $fyc_q_check;
                        else if ($k2_check <= 0.9)
                            $result = 0.35 * $fyc_q_check;
                        else if ($k2_check > 0.9)
                            $result = 0.4 * $fyc_q_check;
                    }
                } else if ($fyc_q_check >= 20000000) {
                    if ($twork_check < 6)
                        $result = 0.5 * $fyc_q_check;
                    else if ($twork_check >= 6 && $twork_check < 14)
                        $result = 0.4 * $fyc_q_check;
                    else if ($twork_check >= 14) {
                        if ($k2_check < 0.75) break;
                        else if ($k2_check < 0.85)
                            $result = 0.35 * $fyc_q_check;
                        else if ($k2_check <= 0.9)
                            $result = 0.40 * $fyc_q_check;
                        else if ($k2_check > 0.9)
                            $result = 0.5 * $fyc_q_check;
                    }
                }
                $list_result[] = [$result, $valid_month];
                break;
            case 'ag_rwd_tndl':
                if (!$this->checkValidTpay('y')) break;
                if (!in_array($agent->designation_code, ['AG'])) break;
                $fyc_y = $this->getFYC($agent, 0, 12);
                $result = 0.1 * $fyc_y;
                $valid_month = Carbon::now()->addYears(1)->startOfMonth()->format('Y-06-d');
                $list_result[] = [$result, $valid_month];
                break;
            case 'ag_rwd_tcldt_dm':
                if (!in_array($agent->designation_code, ['AG'])) break;
                if ($data['npos'] != 'DM') break;
                if (Util::get_designation_rank($data['hpos']) >= Util::get_designation_rank('DM')) break;
                $result = 1;
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_hldlm':
                $depdrCodes = $data['depDrCodes'];
                $count_depdr_check = count($depdrCodes);
                if ($count_depdr_check) break;
                $depdr_hldlth_check = $this->getTotalRewardTypeByCodes($depdrCodes, 'ag_rwd_hldlth');
                $result = $depdr_hldlth_check >= 3 ? 1 : 0;
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_dscnht':
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM'])) break;
                $depdrCodes = $data['depDrCodes'];
                $count_depdr_check = count($depdrCodes);
                $count_depdr_aa_check = $this->getTotalAAByCodes($depdrCodes);
                $perc_depdr_aa_check = $count_depdr_aa_check / $count_depdr_check;
                // list products restrict for this reward
                $fyp = $this->getFYP($agent);
                if ($count_depdr_aa_check < 3 || $perc_depdr_aa_check < 0.5) $result = 0.5 * $fyp;
                else if ($count_depdr_aa_check == 3) $result = 0.55 * $fyp;
                else if ($count_depdr_aa_check > 3) $result = 0.65 * $fyp;
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_qlhtthhptt':
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM', 'RD', 'SRD', 'TD'])) break;
                $depdrCodes = $data['depDrCodes'];
                // $count_depdr_check = $data['depDr']->count();
                $count_depdr_check = count($depdrCodes);
                if (!count($depdrCodes)) break;
                $count_depdr_fyc_check = $this->getTotalFYCByCodes($depdrCodes);
                $count_depdr_k2_check = $this->getTotalK2ByCodes($depdrCodes) / $count_depdr_check;
                $count_depdr_aa_check = $this->getTotalAAByCodes($depdrCodes);
                // echo "\ncount_depdr_check: " . implode(",", $depdrCodes);
                // echo "\ncount_depdr_fyc_check: " . $count_depdr_fyc_check;
                // echo "\ncount_depdr_k2_check: " . $count_depdr_k2_check;
                // echo "\ncount_depdr_aa_check: " . $count_depdr_aa_check;

                if ($count_depdr_k2_check < 0.75) break;
                if ($count_depdr_fyc_check < 25000000) {
                    if ($count_depdr_aa_check < 3) $result = 0.15 * $count_depdr_fyc_check;
                    if ($count_depdr_aa_check >= 3) $result = 0.2 * $count_depdr_fyc_check;
                } else if ($count_depdr_fyc_check >= 25000000) {
                    if ($count_depdr_aa_check < 3) $result = 0.15 * $count_depdr_fyc_check;
                    if ($count_depdr_aa_check == 3) $result = 0.2 * $count_depdr_fyc_check;
                    if ($count_depdr_aa_check > 3) $result = 0.25 * $count_depdr_fyc_check;
                }
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_qlhqthhptt':
                if (!$this->checkValidTpay('q')) break;
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM', 'RD', 'SRD', 'TD'])) break;
                $depdrCodes = $data['depDrCodes'];
                $count_depdr_check = count($depdrCodes);
                if (!count($depdrCodes)) break;
                $count_depdr_fyc_check = $this->getTotalFYCByCodes($depdrCodes, 0, 3);
                $count_depdr_k2_check = $this->getTotalK2ByCodes($depdrCodes, 0, 3) / (3 * $count_depdr_check);
                $count_depdr_aa_check = $this->getTotalAAByCodes($depdrCodes, 0, 3);

                // echo "\ncount_depdr_check: " . implode(",", $depdrCodes);
                // echo "\ncount_depdr_fyc_check: " . $count_depdr_fyc_check;
                // echo "\ncount_depdr_k2_check: " . $count_depdr_k2_check;
                // echo "\ncount_depdr_aa_check: " . $count_depdr_aa_check;

                if ($count_depdr_k2_check < 0.75) break;
                if ($count_depdr_fyc_check < 60000000) {
                    if ($count_depdr_aa_check < 3) $result = 0.05 * $count_depdr_fyc_check;
                    if ($count_depdr_aa_check >= 3) $result = 0.1 * $count_depdr_fyc_check;
                } else if ($count_depdr_fyc_check >= 25000000) {
                    if ($count_depdr_aa_check < 3) $result = 0.05 * $count_depdr_fyc_check;
                    if ($count_depdr_aa_check == 3) $result = 0.1 * $count_depdr_fyc_check;
                    if ($count_depdr_aa_check > 3) $result = 0.15 * $count_depdr_fyc_check;
                }
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_tnql':
                if (!$this->checkValidTpay('y')) break;
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM', 'RD', 'SRD', 'TD'])) break;
                $depdrCodes = $data['depDrCodes'];
                $count_depdr_check = count($depdrCodes);
                if (!count($depdrCodes)) break;
                $depdr_fyc = $this->getTotalFYCByCodes($depdrCodes, 0, 12);
                $result = 0.06 * $depdr_fyc;
                $valid_month = Carbon::now()->addYears(1)->startOfMonth()->format('Y-06-d');
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_ptptt':
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM', 'RD', 'SRD', 'TD'])) break;
                $drs = $data['dr'];
                $from = Carbon::now()->subMonths(12)->startOfMonth()->format('Y-m-d');
                $list_designation = Util::get_designation_code();
                $valid_designation_codes = [];
                foreach($list_designation as $dc => $name) {
                    $valid_designation_codes[] = $dc;
                    if($dc == $agent->designation_code) break;
                }
                $validDMs = $drs->whereIn('designation_code', $valid_designation_codes)
                ->whereHas('promotions', function ($query) use ($from) {
                    $query->where([
                        ['new_designation_code', '=', 'DM'],
                        ['valid_month', '>=', $from]
                    ]);
                })->get();
                if(!count($validDMs)) break;
                foreach($validDMs as $dm) {
                    $dm_rwd_qlhtthhptt = $this->getRewardType($dm, 'dm_rwd_qlhtthhptt');
                    if(!$dm_rwd_qlhtthhptt) continue;
                    $list_result[] = [0.5 * $dm_rwd_qlhtthhptt, $valid_month];
                    $list_result[] = [0.25 * $dm_rwd_qlhtthhptt, Carbon::now()->addMonths(6)->startOfMonth()->format('Y-m-d'), $dm->agent_code];
                    $list_result[] = [0.25 * $dm_rwd_qlhtthhptt, Carbon::now()->addMonths(14)->startOfMonth()->format('Y-m-d'), $dm->agent_code];
                }
                break;
            case 'dm_rwd_gt':
                $drCodes = $data['drCodes'];
                $count_dr_check = count($drCodes);
                if (!$count_dr_check) break;
                $dr_qlhtthhptt_check = $this->getTotalRewardTypeByCodes($drCodes, 'dm_rwd_qlhtthhptt');
                $result = 0.5 * $dr_qlhtthhptt_check;
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_tcldt_sdm':
                if (!in_array($agent->designation_code, ['DM'])) break;
                if ($data['npos'] != 'SDM') break;
                if (Util::get_designation_rank($data['hpos']) >= Util::get_designation_rank('SDM')) break;
                $result = 1;
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_tcldt_am':
                if (!in_array($agent->designation_code, ['SDM'])) break;
                if ($data['npos'] != 'AM') break;
                if (Util::get_designation_rank($data['hpos']) >= Util::get_designation_rank('AM')) break;
                $result = 1;
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_tcldt_rd':
                if (!in_array($agent->designation_code, ['AM'])) break;
                if ($data['npos'] != 'RD') break;
                if (Util::get_designation_rank($data['hpos']) >= Util::get_designation_rank('RD')) break;
                $result = 1;
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_dthdtptt':
                // duy trì hợp đồng trên phòng trực tiếp
                break;
            case 'rd_rwd_dscnht':
                if (!in_array($agent->designation_code, ['RD', 'SRD', 'TD'])) break;
                $fyp = $this->getFYP($agent);
                $result = 0.65 * $fyp;
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_hh_nsht':
                if (!in_array($agent->designation_code, ['RD', 'SRD', 'TD'])) break;
                if ($data['isDrAreaManager']) {
                    $teamAGCodes = $data['teamAGCodes'];
                    $count_teamAG_fyc_check = $this->getTotalFYCByCodes($teamAGCodes);
                    if ($count_teamAG_fyc_check < 100000000) $result = 0.15 * $count_teamAG_fyc_check;
                    else if ($count_teamAG_fyc_check >= 100000000) $result = 0.2 * $count_teamAG_fyc_check;
                }
                if (in_array($agent->designation_code, ['SRD', 'TD'])) {
                    $drCodes = $data['drCodes'];
                    $count_dr_check = count($drCodes);
                    if ($count_dr_check) {
                        $rd_hh_nsht_check = $this->getTotalRewardTypeByCodes($drCodes, 'rd_hh_nsht');
                        $result += 0.5 * $rd_hh_nsht_check;
                    }
                }
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_rwd_dctkdq':
                if (!$this->checkValidTpay('q')) break;
                if (!in_array($agent->designation_code, ['RD', 'SRD', 'TD'])) break;
                $qTargetFYC = $this->getTarget($agent, 'q', 'FYC', date('Y-m-d'));
                if (!$qTargetFYC) $percFYC_check = 1;
                else {
                    $fyc_check = $this->getFYC($agent, 0, 3);  // FYC check là check theo FYC cá nhân hay toàn vùng?
                    $percFYC_check = $fyc_check / $qTargetFYC;
                }
                if ($percFYC_check < 1) break;
                $k2_check = $this->getK2($agent, 0, 3) / 3;
                if ($k2_check < 0.75) break;
                $last_quater_HC = $this->getAU($agent, 3);
                $then_quater_HC = $this->getAU($agent);
                $incHC_check = $then_quater_HC / $last_quater_HC - 1;
                $teamAGCodes = $data['teamAGCodes'];
                $count_teamAG_fyc_check = $this->getTotalFYCByCodes($teamAGCodes);
                if ($data['isDrAreaManager']) {
                    if ($incHC_check < 0.2) $result = 0.05 * $count_teamAG_fyc_check;
                    else if ($incHC_check >= 0.2) $result = 0.1 * $count_teamAG_fyc_check;
                }
                if (in_array($agent->designation_code, ['SRD', 'TD'])) {
                    $drCodes = $data['drCodes'];
                    $count_dr_check = count($drCodes);
                    if ($count_dr_check) {
                        $rd_hh_nsht_check = $this->getTotalRewardTypeByCodes($drCodes, 'rd_rwd_dctkdq');
                        $result += 0.5 * $rd_hh_nsht_check;
                    }
                }
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_rwd_tndhkd':
                if (!$this->checkValidTpay('y')) break;
                if (!in_array($agent->designation_code, ['RD', 'SRD', 'TD'])) break;
                // $k2_check = $this->getK2($agent, 0, 12) / 12; // K2 này lấy cả năm hay tháng đó
                // if($k2_check < 0.75) break;
                $month_in_position = $data['tp'];
                $yTargetFYC = $this->getTarget($agent, 'y', 'FYC', date('Y-m-d'));
                if (!$yTargetFYC) $percFYC_check = 1;
                else {
                    $fyc_check = $this->getFYC($agent, 0, 12);  // FYC check là check theo FYC cá nhân hay toàn vùng?
                    $percFYC_check = $fyc_check / $yTargetFYC;
                }
                if ($percFYC_check < 1) break;
                if ($data['isDrAreaManager']) {
                    $teamAGCodes = $data['teamAGCodes'];
                    $count_teamAG_fyc_check = $this->getTotalFYCByCodes($teamAGCodes);
                    if ($month_in_position <= 12) $result = 0.035 * $count_teamAG_fyc_check;
                    if ($month_in_position <= 24) $result = 0.05 * $count_teamAG_fyc_check;
                    if ($month_in_position <= 36) $result = 0.07 * $count_teamAG_fyc_check;
                }
                if (in_array($agent->designation_code, ['SRD', 'TD'])) {
                    $drCodes = $data['drCodes'];
                    $count_dr_check = count($drCodes);
                    if ($count_dr_check) {
                        $rd_hh_nsht_check = $this->getTotalRewardTypeByCodes($drCodes, 'rd_rwd_tndhkd');
                        $result += 0.5 * $rd_hh_nsht_check;
                    }
                }
                $valid_month = Carbon::now()->addYears(1)->startOfMonth()->format('Y-03-d');
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_rwd_dbgdmht':
                if (!in_array($agent->designation_code, ['TD'])) break;
                $teamAGCodes = $data['teamAGCodes'];
                $count_teamAG_fyc_check = $this->getTotalFYCByCodes($teamAGCodes);
                $result = 0.05 * $count_teamAG_fyc_check;
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_rwd_tcldt_srd':
                if (!in_array($agent->designation_code, ['RD'])) break;
                if ($data['npos'] != 'SRD') break;
                if (Util::get_designation_rank($data['hpos']) >= Util::get_designation_rank('SRD')) break;
                $result = 1;
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_rwd_tcldt_td':
                if (!in_array($agent->designation_code, ['SRD'])) break;
                if ($data['npos'] != 'TD') break;
                if (Util::get_designation_rank($data['hpos']) >= Util::get_designation_rank('TD')) break;
                $result = 1;
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_rwd_dthdtvtt':
                if (!$this->checkValidTpay('q')) break;
                if (!in_array($agent->designation_code, ['RD', 'SRD', 'TD'])) break;
                if (!$data['isDrAreaManager']) break;
                $teamAGCodes = $data['teamAGCodes'];
                $count_teamAG_check = count($teamAGCodes);
                $count_teamAG_k2_check = $this->getTotalK2ByCodes($teamAGCodes, 0, 3) / (3 * $count_teamAG_check);  // K2 toàn vùng theo quý hay theo tháng hiện tại
                $count_teamAG_fyp_check = $this->getTotalFYPByCodes($teamAGCodes);
                if ($count_teamAG_k2_check < 0.75) break;
                if ($data['isDrAreaManager']) {
                    if ($count_teamAG_k2_check < 0.8) $result = 0.015 * $count_teamAG_fyp_check;
                    else if ($count_teamAG_k2_check < 0.9) $result = 0.02 * $count_teamAG_fyp_check;
                    else if ($count_teamAG_k2_check >= 0.9) $result = 0.03 * $count_teamAG_fyp_check;
                }

                if (in_array($agent->designation_code, ['SRD', 'TD'])) {
                    $drCodes = $data['drCodes'];
                    $count_dr_check = count($drCodes);
                    if (!$count_dr_check) break;
                    $rd_rwd_dthdtvtt_check = $this->getTotalRewardTypeByCodes($drCodes, 'rd_rwd_dthdtvtt');
                    $result += 0.5 * $rd_rwd_dthdtvtt_check;
                }
                $list_result[] = [$result, $valid_month];
                break;
        }
        return $list_result;
    }

    private function getThisMonthFinalIncome($agent) {
        
    }

    private function checkValidTpay($tpay)
    {
        return true;
        $current_month = Carbon::now()->format('m');
        if ($tpay == 'm') return true;
        if ($tpay == 'q') return in_array($current_month, [3, 6, 9, 12]);
        if ($tpay == 'y') return in_array($current_month, [12]);
    }
}
