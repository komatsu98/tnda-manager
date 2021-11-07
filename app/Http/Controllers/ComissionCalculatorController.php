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
        // $data['CC'] = $this->getCC($agent, 0, 3);
        // $data['FYP'] = $this->getFYP($agent, 0, 3);
        // $data['FYC'] = $this->getFYC($agent, 0, 3);
        // $data['RYPp'] = $this->getRYP($agent, $status = "p", 0, 14);
        // $data['RYPr'] = $this->getRYP($agent, $status = "r", 0, 14);
        // $data['isAA'] = $this->getIsAA($agent, 2);
        $data['K2'] = $this->getK2($agent);
        // $data['cal_RYPp'] = $this->calcThisMonthRYPp($agent);
        $data['depDr'] = $this->getDepDr($agent);
        $data['depDrCodes'] = $this->getDepDrCodes($agent);
        // return $data;
        $rewards = $this->calcRewards($agent, $data);
        return $rewards;
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

    private function calcThisMonthFYP($agent, $list_product = [], $list_sub_product = [])
    {
        $from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        $FYPs = Transaction::where([
            ['trans_date', '>=', $from],
            ['agent_code', '=', $agent->agent_code],
        ]);
        if(count($list_product) || count($list_sub_product)) {
            $FYPs = $FYPs->whereHas('contract', function ($query) use ($list_product, $list_sub_product) {
                $query->where(function ($q) use ($list_product, $list_sub_product) {
                    foreach($list_product as $product_code) {
                        $q->orWhere('product_code', 'like', '%' . $product_code . '%');
                    }
                    foreach($list_sub_product as $sub_product_code) {
                        $q->orWhere('sub_product_code', 'like', '%' . $sub_product_code . '%');
                    }
                });
            });
        }
        $query = str_replace(array('?'), array('\'%s\''), $FYPs->toSql());
        $query = vsprintf($query, $FYPs->getBindings());
        print_r($query);
        $FYPs = $FYPs->selectRaw('sum(premium_received) as count')->get();
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
                ->selectRaw('sum(premium_term) as RYP')
                ->get();
            if (count($mcontracts)) {
                $countRYP = intval($mcontracts[0]->RYP);
            }
        }

        return $countRYP;
    }

    // thực thu
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
            ->selectRaw('sum(renewal_premium_received) as count')
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
        $isAA = $working_month && $FYP_month >= 6000000 && $CC_month >= 1 ? 1 : 0;
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

    private function getDepDr($agent)
    {
        $depdr = $agent->directAgents();
        return $depdr;
    }

    private function getDepDrCodes($agent)
    {
        $depdrCodes = $agent->directAgents()->where(['designation_code' => 'AG'])->pluck('agent_code')->toArray();
        return $depdrCodes;
    }

    private function calcRewards($agent, $data)
    {
        $list_reward_type = Util::get_income_code();
        $rewards = [];
        foreach ($list_reward_type as $type => $desc) {
            $rewards[$type] = $this->calcRewardType($agent, $data, $type);
        }
        return $rewards;
    }

    private function calcRewardType($agent, $data, $type)
    {
        $result = 0;
        $month = '';
        if(!isset($data['twork'])) $data['twork'] = $this->getTwork($agent);
        if(!isset($data['K2'])) $data['K2'] = $this->getK2($agent);
        if(!isset($data['npos'])) $data['npos'] = $this->getNpos($agent);
        if(!isset($data['hpos'])) $data['hpos'] = $this->getHpos($agent);

        switch ($type) {
            case 'ag_rwd_hldlth':
                if (!in_array($agent->designation_code, ['AG', 'DM', 'SDM', 'AM'])) {
                    break;
                }
                if ($data['twork'] > 9) break;
                $month = Carbon::now()->startOfMonth()->format('Y-m-d');
                $fyc_check = $this->getFYC($agent, 0, 3);
                $cc_check = $this->getCC($agent, 0, 3);
                // echo $agent->agent_code . " fyc_check " . $fyc_check . "\n";
                // echo $agent->agent_code . " cc_check " . $cc_check . "\n";
                $result = $fyc_check >= 30000000 && $cc_check >= 3 ? 1 : 0;
                break;
            case 'ag_hh_bhcn':
                if (!in_array($agent->designation_code, ['AG', 'DM', 'SDM', 'AM', 'RD', 'SRD', 'TD'])) {
                    break;
                }
                $month = Carbon::now()->startOfMonth()->format('Y-m-d');
                $result = $this->getFYC($agent, 0, 1);
                break;
            case 'ag_rwd_dscnhq':
                if (!$this->checkValidTpay('q')) break;
                if (!in_array($agent->designation_code, ['AG'])) {
                    break;
                }
                $month = Carbon::now()->startOfMonth()->format('Y-m-d');
                $fyc_q_check = $this->getFYC($agent, 0, 3);
                $twork_check = $data['twork'];
                $k2_check = $data['K2'];
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
                break;
            case 'ag_rwd_tndl':
                if (!$this->checkValidTpay('y')) break;
                if (!in_array($agent->designation_code, ['AG'])) {
                    break;
                }
                $month = Carbon::now()->addMonths(6)->startOfMonth()->format('Y-m-d');
                $fyc_y = $this->getFYC($agent, 0, 12);
                $result = 0.1 * $fyc_y;
                break;
            case 'ag_rwd_tcldt_dm':
                if (!in_array($agent->designation_code, ['AG'])) {
                    break;
                }
                if ($data['npos'] != 'DM') break;
                if (Util::get_designation_rank($data['hpos']) >= Util::get_designation_rank('DM')) break;
                $result = 1;
                break;
            case 'dm_rwd_hldlm':
                $count_depdr_check = $data['depDr']->count();
                if(!$count_depdr_check) break;
                $depdr_hldlth_check = 0;
                foreach($data['depDr']->get() as $dr_agent) {
                    $depdr_hldlth_check += $this->calcRewardType($dr_agent, $data, 'ag_rwd_hldlth')[0];
                }
                $result = $depdr_hldlth_check >= 3 ? 1 : 0;
                break;
            case 'dm_rwd_dscnht':
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM'])) {
                    break;
                }
                $count_depdr_check = $data['depDr']->count();
                $count_depdr_aa_check = 0;
                if($count_depdr_check) {
                    foreach($data['depDr']->get() as $dr_agent) {
                        $count_depdr_aa_check += $this->getIsAA($dr_agent);
                    }
                }
                $perc_depdr_aa_check = $count_depdr_aa_check/$count_depdr_check;
                // list products restrict for this reward
                $fyp = $this->calcThisMonthFYP($agent, [], []);

                if($count_depdr_aa_check < 3 || $perc_depdr_aa_check < 0.5) $result = 0.5 * $fyp;
                else if($count_depdr_aa_check == 3) $result = 0.55 * $fyp;
                else if($count_depdr_aa_check > 3) $result = 0.65 * $fyp;                
                break;
            case 'dm_rwd_qlhtthhptt':
                break;
            case 'dm_rwd_qlhqthhptt':
                break;
            case 'dm_rwd_tnql':
                break;
            case 'dm_rwd_ptptt':
                break;
            case 'dm_rwd_gt':
                break;
            case 'dm_rwd_tcldt_sdm':
                break;
            case 'dm_rwd_tcldt_am':
                break;
            case 'dm_rwd_tcldt_rd':
                break;
            case 'dm_rwd_dthdtptt':
                break;
            case 'rd_rwd_dscnht':
                break;
            case 'rd_hh_nsht':
                break;
            case 'rd_rwd_dctkdq':
                break;
            case 'rd_rwd_tndhkd':
                break;
            case 'rd_rwd_dbgdmht':
                break;
            case 'rd_rwd_tcldt_srd':
                break;
            case 'rd_rwd_tcldt_td':
                break;
            case 'rd_rwd_dthdtvtt':
                break;
        }
        return [$result, $month];
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
