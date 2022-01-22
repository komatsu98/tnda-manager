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
use App\ContractProduct;
use App\Promotion;
use App\PromotionProgress;

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

    public function calc(Request $request, $agent_code)
    {
        $agent = User::where(['agent_code' => $agent_code])->first();
        $month = isset($request->month) ? $request->month : null;
        return $this->updateThisMonthAllStructure($agent, $month);
        $data = [];
        $data['month'] = $month;
        $data['twork'] = $this->getTwork($agent, $month);
        $data['tp'] = $this->getTp($agent, $month);
        $data['pos'] = $this->getPos($agent, $month);
        $data['hpos'] = $this->getHpos($agent, $month);
        $data['npos'] = $this->getNpos($agent, $month);
        $data['thisMonthMetric'] = $this->updateThisMonthMetric($agent, $month);
        $data['dr'] = $this->getDr($agent);
        $data['drCodes'] = $this->getDr($agent)->pluck('agent_code')->toArray();
        $data['depDr'] = $this->getDepDr($agent);
        $data['depDrCodes'] = $data['depDr']->pluck('agent_code')->toArray();
        $data['teamAGCodes'] = $this->getWholeTeamCodes($agent, true);
        $data['teamCodes'] = $this->getWholeTeamCodes($agent);
        $data['isDrAreaManager'] = $this->getIsDrAreaManager($agent);
        $data['thisMonthReward'] = $this->updateThisMonthReward($agent, $data, $month);
        $data['thisMonthPromotionReq'] = $this->updateThisMonthPromotionReq($agent, $data, $month);
        return $data;
    }

    public function calcAll() {
        // $des = ['AG'];
        // foreach($des as $d) {
        //     $AGs = User::whereIn('designation_code',[$d])->get();
            // foreach($AGs as $agent) {
            //     $this->updateThisMonthAllStructure($agent, $month = '2021-10-01');
            //     $this->updateThisMonthAllStructure($agent, $month = '2021-11-01');
            //     $this->updateThisMonthAllStructure($agent, $month = '2021-12-01');
            // }
        // }
        $codes = [2,4,22,29,30,31,32,38,40,42,43,44,48,49,50,51,52,55,59,61,64,69,77,85,88,91,99,104,106,108,109,110,113,114,115,116,118,129,134,141,142,144,147,150,159,164,184,185,187,188,190,198,202,224,242,244,258];
        $agents = User::whereIn('agent_code', $codes)->get();
        foreach($agents as $agent) {
            $this->updateThisMonthAllStructure($agent, $month = '2021-10-01');
            $this->updateThisMonthAllStructure($agent, $month = '2021-11-01');
            $this->updateThisMonthAllStructure($agent, $month = '2021-12-01');
        }
        echo "done";
        
    }

    public function updateThisMonthAllStructure($agent, $month = null)
    {
        $this->updateThisMonthAgent($agent, $month);
        $this->updateThisMonthAgent($agent->reference, $month);
        while ($supervisor = $agent->supervisor) {
            $this->updateThisMonthAgent($supervisor, $month);
            $this->updateThisMonthAgent($supervisor->reference, $month);
            $agent = $supervisor;
        }
    }

    public function updateThisMonthAgent($agent, $month = null)
    {
        if (!$agent) return;
        $data = [];
        $data['month'] = $month;
        $data['twork'] = $this->getTwork($agent, $month);
        $data['tp'] = $this->getTp($agent, $month);
        $data['pos'] = $this->getPos($agent, $month);
        $data['hpos'] = $this->getHpos($agent, $month);
        $data['npos'] = $this->getNpos($agent, $month);
        $data['thisMonthMetric'] = $this->updateThisMonthMetric($agent, $month);
        $data['dr'] = $this->getDr($agent);
        $data['drCodes'] = $this->getDr($agent)->pluck('agent_code')->toArray();
        $data['depDr'] = $this->getDepDr($agent);
        $data['depDrCodes'] = $data['depDr']->pluck('agent_code')->toArray();
        $data['teamAGCodes'] = $this->getWholeTeamCodes($agent, true);
        $data['teamCodes'] = $this->getWholeTeamCodes($agent);
        $data['isDrAreaManager'] = $this->getIsDrAreaManager($agent);
        $data['thisMonthReward'] = $this->updateThisMonthReward($agent, $data, $month);
        $data['thisMonthPromotionReq'] = $this->updateThisMonthPromotionReq($agent, $data, $month);
    }

    public function updateThisMonthPromotionReq($agent, $data, $month = null)
    {
        $progress = $this->calcThisMonthPromotionReqType($agent, $data, $month);
        if (!$month) {
            $month = Carbon::now()->startOfMonth()->format('Y-m-d');
        } else {
            $month = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth()->format('Y-m-d');
        }
        foreach ($progress as $p) {
            $old_metrics = $agent->promotionProgress()
                ->where([
                    'agent_code' => $agent->agent_code,
                    'month' => $month,
                    'pro_code' => $p['code']
                ])->get();
            foreach ($p['requirements'] as $r) {
                if ($r['progress_text'] == null) continue; // manually
                $metric = null;
                foreach ($old_metrics as $om) {
                    if ($om->req_id == $r['id']) {
                        $metric = $om;
                        break;
                    }
                }
                if (!$metric) {
                    $metric = PromotionProgress::create([
                        'pro_code' => $p['code'],
                        'agent_code' => $agent->agent_code,
                        'month' => $month,
                        'req_id' => $r['id'],
                        'progress_text' => $r['progress_text'],
                        'is_done' => $r['is_done']
                    ]);
                } else $metric->update($r);
            }
        }
        return $progress;
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

    private function getTwork($agent, $month = null)
    {
        if (!$month) {
            $to = Carbon::now();
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth();
        }

        $from = Carbon::createFromFormat('Y-m-d', $agent->alloc_code_date);
        $twork = $to->diffInMonths($from);
        return $twork;
    }

    private function getTp($agent, $month = null)
    {
        if (!$month) {
            $to = Carbon::now();
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth();
        }
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

    private function getNpos($agent, $month = null)
    {
        if (!$month) {
            $valid_month = Carbon::now()->addMonths(1)->startOfMonth()->format('Y-m-d');
        } else {
            $valid_month = Carbon::createFromFormat('Y-m-d', $month)->addMonths(1)->startOfMonth()->format('Y-m-d');
        }

        $promotion = $agent->promotions()->where(['valid_month' => $valid_month])->first();
        if ($promotion) return $promotion->new_designation_code;
        return null;
    }

    public function updateThisMonthMetric($agent, $month = null)
    {
        $metric = $this->calcThisMonthMetric($agent, $month);
        if (!$month) {
            $month = Carbon::now()->startOfMonth()->format('Y-m-d');
        } else {
            $month = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth()->format('Y-m-d');
        }
        $metric['month'] = $month;
        $old_metric = $agent->monthlyMetrics()->where(['month' => $month])->first();
        if ($old_metric) $old_metric->update($metric);
        else MonthlyMetric::create($metric);
        return $metric;
    }

    private function calcThisMonthMetric($agent, $month = null)
    {
        $data = [];
        $data['agent_code'] = $agent->agent_code;
        $data['FYC'] = $this->calcThisMonthFYC($agent, $month);
        $data['FYP'] = $this->calcThisMonthFYP($agent, $month);
        $data['APE'] = $this->calcThisMonthAPE($agent, $month);
        $data['RYP'] = $this->calcThisMonthRYPp($agent, $month);
        $data['RYPr'] = $this->calcThisMonthRYPr($agent, $month);
        $data['K2'] = $this->calcK2($data['RYP'], $data['RYPr']);
        $data['CC'] = $this->calcThisMonthCC($agent, $month);
        $data['AA'] = $this->calcIsAA($agent, 0, $data['FYP'], $data['CC']);
        $data['AAU'] = $this->calcThisMonthAAU($agent, $month); // Active Agent Under
        $data['U'] = count($this->getWholeTeamCodes($agent)); // under
        $data['AU'] = count($this->getWholeTeamCodes($agent, true)); // agent under
        $data['HC'] = count($this->getReferenceeCodes($agent)); // headcount
        $data['AHC'] = count($this->getReferenceeCodes($agent, true)); // agent headcount

        return $data;
    }

    private function calcThisMonthFYC($agent, $month = null)
    {
        if (!$month) {
            $from = Carbon::now()->startOfMonth()->format('Y-m-d');
            $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        } else {
            $from = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth()->format('Y-m-d');
            $to = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth()->format('Y-m-d');
        }
        $last_month_valid_ack = Carbon::createFromFormat('Y-m-d', $to)->subMonth(1)->subDay(21);
        $valid_ack_date = Carbon::createFromFormat('Y-m-d', $to)->subDay(21);
        $FYCs = $agent->comissions()
        ->where(function ($q) use ($from, $to, $valid_ack_date, $last_month_valid_ack) {
            $q->where(function($q1) use ($from, $to) {
                $q1->where([
                    ['received_date', '>=', $from],
                    ['received_date', '<=', $to]
                ])->whereHas('contract', function ($q) {
                    $q->whereIn('partner_code', ['BV', 'VBI']);
                });
            })->orWhereHas('contract', function ($q1) use ($valid_ack_date, $last_month_valid_ack) {
                $q1->whereIn('partner_code', ['FWD', 'BML'])->whereNotNull('ack_date')->where([['ack_date', '<', $valid_ack_date], ['ack_date', '>', $last_month_valid_ack]]);
            });
        })
        ->selectRaw('sum(amount) as count')
        ->get();
        // $query = str_replace(array('?'), array('\'%s\''), $FYCs->toSql());
        // $query = vsprintf($query, $FYCs->getBindings());
        // print_r($query);exit;
        $countFYC = 0;
        if (count($FYCs)) {
            $countFYC = intval($FYCs[0]->count);
        }
        return $countFYC;
    }

    private function calcThisMonthCC($agent, $month = null)
    {
        if (!$month) {
            $from = Carbon::now()->startOfMonth()->format('Y-m-d');
            $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        } else {
            $from = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth()->format('Y-m-d');
            $to = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth()->format('Y-m-d');
        }
        $CCs = $agent->contracts()->where([
            ['release_date', '>=', $from],
            ['release_date', '<=', $to]
        ])
            ->selectRaw('sum(1) as count')
            ->get();
        $countCC = 0;
        if (count($CCs)) {
            $countCC = intval($CCs[0]->count);
        }
        return $countCC;
    }

    private function calcThisMonthAAU($agent, $month = null) {
        if (!$month) {
            $month = Carbon::now()->startOfMonth()->format('Y-m-d');
        }
        $teamAGCodes = $this->getWholeTeamCodes($agent, true);
        $AAU = $this->getTotalRewardTypeByCodes($teamAGCodes, 'ag_rwd_hldlth', 0, 1, $month);
        return $AAU;
    }

    private function calcThisMonthFYP($agent, $month = null, $list_product = null, $list_partner_code = null)
    {
        if (!$month) {
            $from = Carbon::now()->startOfMonth()->format('Y-m-d');
            $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        } else {
            $from = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth()->format('Y-m-d');
            $to = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth()->format('Y-m-d');
        }
        $last_month_valid_ack = Carbon::createFromFormat('Y-m-d', $to)->subMonth(1)->subDay(21);
        $valid_ack_date = Carbon::createFromFormat('Y-m-d', $to)->subDay(21);

        $FYPs = $agent->transactions()
        ->where(function ($q) use ($from, $to, $valid_ack_date, $last_month_valid_ack) {
            $q->where(function($q1) use ($from, $to) {
                $q1->where([
                    ['trans_date', '>=', $from],
                    ['trans_date', '<=', $to]
                ])->whereHas('contract', function ($q) {
                    $q->whereIn('partner_code', ['BV', 'VBI']);
                });
            })->orWhereHas('contract', function ($q1) use ($valid_ack_date, $last_month_valid_ack) {
                $q1->whereIn('partner_code', ['FWD', 'BML'])
                ->whereNotNull('ack_date')
                ->where([['ack_date', '<', $valid_ack_date], ['ack_date', '>', $last_month_valid_ack]]);
            });
        });
        if (!is_null($list_product)) {
            $FYPs = $FYPs->whereHas('contract_product', function ($query) use ($list_product) {
                $query->whereIn('product_code', $list_product);
            });
        }
        if (!is_null($list_partner_code)) {
            $FYPs = $FYPs->whereHas('contract', function ($query) use ($list_partner_code) {
                $query->whereIn('partner_code', $list_partner_code);
            });
        }
        // $query = str_replace(array('?'), array('\'%s\''), $FYPs->toSql());
        // $query = vsprintf($query, $FYPs->getBindings());
        // print_r($query);exit;
        $FYPs = $FYPs->selectRaw('sum(premium_received) as count')->get();
        // print_r();exit;
        $countFYP = 0;
        if (count($FYPs)) {
            $countFYP = intval($FYPs[0]->count);
        }
        return $countFYP;
    }

    private function calcThisMonthAPE($agent, $month = null, $list_product = null, $list_partner_code = null)
    {
        if (!$month) {
            $from = Carbon::now()->startOfMonth()->format('Y-m-d');
            $to = Carbon::now()->endOfMonth()->format('Y-m-d');
        } else {
            $from = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth()->format('Y-m-d');
            $to = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth()->format('Y-m-d');
        }
        $last_month_valid_ack = Carbon::createFromFormat('Y-m-d', $to)->subMonth(1)->subDay(21);
        $valid_ack_date = Carbon::createFromFormat('Y-m-d', $to)->subDay(21);

        $APEs = ContractProduct::whereHas('contract', function($q) use ($agent, $from, $to, $valid_ack_date, $last_month_valid_ack) {
            $q->where([
                ['agent_code', '=', $agent->agent_code], 
                ['release_date', '>=', $from],
                ['release_date', '<=', $to]
            ])->where(function($q1) use ($valid_ack_date, $last_month_valid_ack) {
                $q1->whereIn('partner_code', ['BV', 'VBI'])
                ->orWhere(function($q2) use ($valid_ack_date, $last_month_valid_ack) {
                    $q2->whereIn('partner_code', ['FWD', 'BML'])
                    ->whereNotNull('ack_date')
                    ->where([['ack_date', '<', $valid_ack_date], ['ack_date', '>', $last_month_valid_ack]]);
                });
            });
        });
       
        // $query = str_replace(array('?'), array('\'%s\''), $APEs->toSql());
        // $query = vsprintf($query, $APEs->getBindings());
        // print_r($query);exit;
        $APEs = $APEs->selectRaw('sum(premium) as count')->get();
        // print_r();exit;
        $countAPE = 0;
        if (count($APEs)) {
            $countAPE = intval($APEs[0]->count);
        }
        return $countAPE;
    }

    private function calcIsAA($agent, $month_back = 0, $FYP_month, $CC_month, $month = null)
    {
        if (!$month) {
            $month_end = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        } else {
            $month_end = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
        }

        $working_month = !$agent->terminate_date || $agent->terminate_date > $month_end;
        $isAA = $working_month && $FYP_month >= 6000000 && $CC_month >= 1 ? 1 : 0;
        return $isAA;
    }

    // thực thu
    private function calcThisMonthRYPp($agent, $month = null)
    {
        if (!$month) {
            $from = Carbon::now()->startOfMonth()->format('Y-m-d');
            $to = Carbon::now()->endOfMonth()->format('Y-m-d');
            $ack_from = Carbon::now()->subMonths(14)->format('Y-m-d');
            $to_back_two = Carbon::now()->subMonths(2);
        } else {
            $from = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth()->format('Y-m-d');
            $to = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth()->format('Y-m-d');
            $ack_from = Carbon::createFromFormat('Y-m-d', $month)->subMonths(14)->format('Y-m-d');
            $to_back_two = Carbon::createFromFormat('Y-m-d', $month)->subMonths(2);
        }

        $RYPs = $agent->transactions()->where([
            ['trans_date', '>=', $from],
            ['trans_date', '<=', $to],
            ['agent_code', '=', $agent->agent_code],
        ])
            ->whereHas('contract', function ($query) use ($ack_from, $to_back_two) {
                $query->whereIn('status_code', ['MA'])
                    ->where([
                        ['maturity_date', '>=', $to_back_two]
                    ])
                    ->where(function ($q) use ($ack_from) {
                        $q->whereNull('ack_date')
                            ->orWhere('ack_date', '>=', $ack_from);
                    });
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
    private function calcThisMonthRYPr($agent, $month = null)
    {
        if (!$month) {
            $ack_from = Carbon::now()->subMonths(14)->format('Y-m-d');
            $to_back_two = Carbon::now()->subMonths(2);
        } else {
            $ack_from = Carbon::createFromFormat('Y-m-d', $month)->subMonths(14)->format('Y-m-d');
            $to_back_two = Carbon::createFromFormat('Y-m-d', $month)->subMonths(2);
        }
        $mcontracts = ContractProduct::whereHas('contract', function ($query) use ($agent, $ack_from, $to_back_two) {
            $query->where([
                ['agent_code', '=', $agent->agent_code],
                ['maturity_date', '>=', $to_back_two],
            ])->where(function ($q) use ($ack_from) {
                $q->whereNull('ack_date')
                    ->orWhere('ack_date', '>=', $ack_from);
            })
                ->whereIn('status_code', ['MA']);
        })
            ->selectRaw('sum(premium_term) as RYP')
            ->get();
        if (count($mcontracts)) {
            $countRYP = intval($mcontracts[0]->RYP);
        }
        return $countRYP;
    }

    private function calcK2($RYPp_month, $RYPr_month)
    {
        if ($RYPr_month == 0) {
            return 1;
        }
        return round($RYPp_month / $RYPr_month, 4);
    }

    public function getCC($agent, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

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

    public function getU($agent, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

        $CCs = $agent->monthlyMetrics()->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])
            ->selectRaw('sum(U) as count')
            ->get();
        $countCC = 0;
        if (count($CCs)) {
            $countCC = intval($CCs[0]->count);
        }
        return $countCC;
    }

    public function getAHC($agent, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

        $CCs = $agent->monthlyMetrics()->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])
            ->selectRaw('sum(AHC) as count')
            ->get();
        $countCC = 0;
        if (count($CCs)) {
            $countCC = intval($CCs[0]->count);
        }
        return $countCC;
    }


    public function getHC($agent, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

        $CCs = $agent->monthlyMetrics()->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])
            ->selectRaw('sum(HC) as count')
            ->get();
        $countCC = 0;
        if (count($CCs)) {
            $countCC = intval($CCs[0]->count);
        }
        return $countCC;
    }


    public function getFYP($agent, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }
        // echo "from " . $from;
        // echo "\nto " . $to;
        // exit;
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

    public function getAPE($agent, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

        $FYPs = $agent->monthlyMetrics()->where([
            ['month', '>=', $from],
            ['month', '<=', $to]
        ])
            ->selectRaw('sum(APE) as count')
            ->get();
        $countFYP = 0;
        if (count($FYPs)) {
            $countFYP = intval($FYPs[0]->count);
        }
        return $countFYP;
    }

    private function getTotalFYPByCodes($codes, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

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

    private function calcTotalFYPByCodes($codes, $month_back = 0, $month_range = 1, $month = null, $list_product = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

        $FYPs = Transaction::whereHas('contract_product.contract.agent', function ($query) use ($codes) {
            $query->whereIn('agent_code', $codes);
        })->where([
            ['trans_date', '>=', $from],
            ['trans_date', '<=', $to]
        ]);
        if (!is_null($list_product)) {
            $FYPs = $FYPs->whereHas('contract_product', function ($query) use ($list_product) {
                $query->whereIn('product_code', $list_product);
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

    public function getFYC($agent, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

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

    private function getTotalFYCByCodes($codes, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

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

    private function getTotalAAByCodes($codes, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

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

    public function getK2($agent, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

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

    private function getTotalK2ByCodes($codes, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

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
        if(is_string($supervisor)) $supervisor = User::where(['agent_code' => $supervisor])->first();
        $direct_unders = $supervisor->directUnders;
        if (!count($direct_unders)) {
            return [];
        } else {
            foreach ($direct_unders as $dr_under) {
                if (!$isAGOnly || $dr_under->designation_code == 'AG') array_push($codes, $dr_under->agent_code);
                $codes = array_merge($codes, $this->getWholeTeamCodes($dr_under, $isAGOnly));
            }
            return $codes;
        }
    }

    private function getReferenceeCodes($agent, $isAGOnly = false)
    {
        $refee =  $agent->referencee()->select('agent_code');
        if($isAGOnly) $refee = $refee->where(['designation_code' => 'AG']);
        return $refee->get();
    }

    public function getAU($agent, $month_back = 0, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->startOfMonth()->format('Y-m-d');
        }

        $AU = $agent->monthlyMetrics()
            ->where([
                ['month', '=', $to]
            ])->select('AU')->first();
  
        $countAU = 0;
        if ($AU) {
            $countAU = $AU->AU;
        }
        return $countAU;
    }

    public function getAAU($agent, $month_back = 0, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->startOfMonth()->format('Y-m-d');
        }

        $AU = $agent->monthlyMetrics()
            ->where([
                ['month', '=', $to]
            ])->select('AAU')->first();
  
        $countAU = 0;
        if ($AU) {
            $countAU = $AU->AAU;
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

    public function updateThisMonthReward($agent, $data, $month = null)
    {
        if (!$month) {
            $month = Carbon::now()->startOfMonth()->format('Y-m-d');
        } else {
            $month = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth()->format('Y-m-d');
        }

        // clear
        $agent->monthlyIncomes()->where(['month' => $month])->delete();

        // insert
        $list_reward_type = Util::get_income_code();
        $rewards = [];
        foreach ($list_reward_type as $type => $desc) {
            $rewards[$type] = $this->calcThisMonthRewardType($agent, $data, $type, $month);
        }
        // print_r($rewards);
        $list_reward_to_insert = [];
        foreach ($rewards as $key => $list_result) {
            foreach ($list_result as $result) {
                $amount = $result[0];
                if (!$amount) continue;
                $valid_month = $result[1];
                $ref_agent_code = isset($result[2]) ? $result[2] : null;
                if (!isset($list_reward_to_insert[$valid_month])) $list_reward_to_insert[$valid_month] = [];
                $list_reward_to_insert[$valid_month][$key] = $amount;
                if ($ref_agent_code) $list_reward_to_insert[$valid_month]['ref_agent_code'] = $ref_agent_code;
            }
        }
        foreach ($list_reward_to_insert as $valid_month => $reward) {
            $reward['month'] = $month;
            $reward['valid_month'] = $valid_month;
            $reward['agent_code'] = $agent->agent_code;
            MonthlyIncome::create($reward);
        }

        // merge
        return isset($list_reward_to_insert[$month]) ? $list_reward_to_insert[$month] : [];
    }

    private function getRewardType($agent, $type, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

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

    private function getTotalRewardTypeByCodes($codes, $type, $month_back = 0, $month_range = 1, $month = null)
    {
        if (!$month) {
            $to = Carbon::now()->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::now()->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->endOfMonth()->format('Y-m-d');
            $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths($month_back)->subMonths($month_range - 1)->startOfMonth()->format('Y-m-d');
        }

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

    private function calcThisMonthPromotionReqType($agent, $data, $month = null)
    {
        if (!$month) {
            $eval_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        } else {
            $eval_date = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth()->format('Y-m-d');
        }
        $pro_reqs = Util::get_promotions($agent->designation_code);
        foreach ($pro_reqs as $i => $pro_req) {
            switch ($pro_req['code']) {
                case 'PRO_DM':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $referencee_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG']
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->count();
                                $referencee_count += 1; // bản thân đại lý
                                $r['progress_text'] = str_pad($referencee_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($referencee_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                if (!$month) {
                                    $backto = Carbon::now()->subMonths(6)->endOfMonth()->format('Y-m-d');
                                    $startMonth = Carbon::now()->startOfMonth();
                                } else {
                                    $backto = Carbon::createFromFormat('Y-m-d', $month)->subMonths(6)->endOfMonth()->format('Y-m-d');
                                    $startMonth = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth();
                                }
                                $referencee_AA_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG'],
                                    ['alloc_code_date', '>=', $backto],
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->whereHas('monthlyMetrics', function ($query) use ($startMonth) {
                                    $query->where([
                                        ['month', '=', $startMonth],
                                        ['AA', '=', 1]
                                    ]);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_AA_count, 2, "0", STR_PAD_LEFT) . " đại lý";
                                if ($referencee_AA_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $refencee_AG_codes = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG']
                                ])->pluck('agent_code')->toArray();
                                $refencee_AG_codes[] = $agent->agent_code;
                                $FYC_check = $this->getTotalFYCByCodes($refencee_AG_codes, 0, 6, $month);
                                $r['progress_text'] = round($FYC_check / 1000000, 2) . " triệu đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 6, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 6, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 6:
                                $k2_check = $data['thisMonthMetric']['K2'];
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 7:
                                // huấn luyện
                                break;
                            case 8:
                                // quy chế
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'PRO_SDM':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->where(['designation_code' => 'DM'])->count();
                                $r['progress_text'] = $direct_dm_count . " DM";
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $referencee_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG']
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($referencee_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                if (!$month) {
                                    $backto = Carbon::now()->subMonths(6)->endOfMonth()->format('Y-m-d');
                                    $startMonth = Carbon::now()->startOfMonth();
                                } else {
                                    $backto = Carbon::createFromFormat('Y-m-d', $month)->subMonths(6)->endOfMonth()->format('Y-m-d');
                                    $startMonth = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth();
                                }
                                $referencee_AA_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG'],
                                    ['alloc_code_date', '>=', $backto],
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->whereHas('monthlyMetrics', function ($query) use ($startMonth) {
                                    $query->where([
                                        ['month', '=', $startMonth],
                                        ['AA', '=', 1]
                                    ]);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_AA_count, 2, "0", STR_PAD_LEFT) . " đại lý";
                                if ($referencee_AA_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 6, $month);
                                $r['progress_text'] = round($FYC_check / 1000000, 2) . " triệu đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 6:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 6, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 6, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 7:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 8:
                                // huấn luyện
                                break;
                            case 9:
                                // huấn luyện
                                break;
                            case 10:
                                // quy chê
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'PRO_AM':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->where(['designation_code' => 'SDM'])->count();
                                $r['progress_text'] = $direct_dm_count . " SDM";
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $teamCodes = $data['teamCodes'];
                                $dm_plus_count = User::whereIn('agent_code', $teamCodes)->whereIn('designation_code', ['DM', 'SDM'])->count();
                                $r['progress_text'] = $dm_plus_count;
                                if ($dm_plus_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $referencee_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG']
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($referencee_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                if (!$month) {
                                    $backto = Carbon::now()->subMonths(6)->endOfMonth()->format('Y-m-d');
                                    $startMonth = Carbon::now()->startOfMonth();
                                } else {
                                    $backto = Carbon::createFromFormat('Y-m-d', $month)->subMonths(6)->endOfMonth()->format('Y-m-d');
                                    $startMonth = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth();
                                }
                                $referencee_AA_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG'],
                                    ['alloc_code_date', '>=', $backto],
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->whereHas('monthlyMetrics', function ($query) use ($startMonth) {
                                    $query->where([
                                        ['month', '=', $startMonth],
                                        ['AA', '=', 1]
                                    ]);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_AA_count, 2, "0", STR_PAD_LEFT) . " đại lý";
                                if ($referencee_AA_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 6:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 6, $month);
                                $r['progress_text'] = round($FYC_check / 1000000, 2) . " triệu đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 7:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 6, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 6, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 8:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 9:
                                // huấn luyện
                                break;
                            case 10:
                                // huấn luyện
                                break;
                            case 11:
                                // huấn luyện
                                break;
                            case 12:
                                // quy chê
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'PRO_RD':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['SDM', 'AM'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $teamCodes = $data['teamCodes'];
                                $dm_plus_count = User::whereIn('agent_code', $teamCodes)->whereIn('designation_code', ['DM', 'SDM', 'AM'])->count();
                                $r['progress_text'] = $dm_plus_count;
                                if ($dm_plus_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $referencee_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG']
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($referencee_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = round($FYC_check / 1000000000, 4) . " tỷ đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 6:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 12, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 7:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 8:
                                // huấn luyện
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'PRO_SRD':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['RD'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $teamCodes = $data['teamCodes'];
                                $dm_plus_count = User::whereIn('agent_code', $teamCodes)->whereIn('designation_code', ['DM', 'SDM'])->count();
                                $r['progress_text'] = $dm_plus_count;
                                if ($dm_plus_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $referencee_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG']
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($referencee_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = round($FYC_check / 1000000000, 4) . " tỷ đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 6:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 12, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 7: // tỉ lệ hoạt động đại lý hay cả đội ngũ?
                                $teamAGCodes = $data['teamAGCodes'];
                                $aa_check = $this->getTotalAAByCodes($teamAGCodes, 0, 3, $month);
                                $perc_aa_check = $teamAGCodes ? $aa_check / count($teamAGCodes) : 0;
                                $r['progress_text'] = round($perc_aa_check, 2) . "%";
                                if ($perc_aa_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 8:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 9:
                                // phỏng vấn
                                break;
                            case 10:
                                // quy chế
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'PRO_TD':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['RD', 'SRD'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $referencee_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG']
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($referencee_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = round($FYC_check / 1000000000, 4) . " tỷ đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 12, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 6: // tỉ lệ hoạt động đại lý hay cả đội ngũ?
                                $teamAGCodes = $data['teamAGCodes'];
                                $aa_check = $this->getTotalAAByCodes($teamAGCodes, 0, 3, $month);
                                $perc_aa_check = $teamAGCodes ? $aa_check / count($teamAGCodes) : 0;
                                $r['progress_text'] = round($perc_aa_check, 2) . "%";
                                if ($perc_aa_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 7:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 8:
                                // phỏng vấn
                                break;
                            case 9:
                                // quy chế
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'STAY_AG':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        $twork = $data['twork'];
                        switch ($r['id']) {
                            case 1:
                                $r['requirement_value'] = $twork <= 12 ? 1 : 1;
                                $cc_check = $this->getCC($agent, 0, 5, $month);
                                $r['progress_text'] = str_pad($cc_check, 2, "0", STR_PAD_LEFT) . " CC";
                                if ($cc_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $r['requirement_value'] = $twork <= 12 ? 2000000 : 1000000;
                                $fyc_check = $this->getFYC($agent, 0, 5, $month);
                                $r['progress_text'] = round($fyc_check / 1000000, 2) . " triệu đồng";
                                if ($fyc_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $r['requirement_value'] = $twork <= 12 ? 0 : 0.75;
                                $k2_check = $data['thisMonthMetric']['K2'];
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                        }
                    }
                    break;
                case 'STAY_DM':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['DM'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $referencee_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG']
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($referencee_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = round($FYC_check / 1000000, 2) . " triệu đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 12, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 6:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'STAY_SDM':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['DM', 'SDM'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $referencee_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG']
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($referencee_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = round($FYC_check / 1000000, 2) . " triệu đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 12, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 6:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'STAY_AM':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['DM', 'SDM', 'AM'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $referencee_count = $agent->referencee()->where([
                                    ['designation_code', '=', 'AG']
                                ])->where(function ($q) use ($eval_date) {
                                    $q->whereNull('terminate_date')
                                        ->orWhere('terminate_date', '>=', $eval_date);
                                })->count();
                                $r['progress_text'] = str_pad($referencee_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($referencee_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = round($FYC_check / 1000000, 2) . " triệu đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 12, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 6:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'STAY_RD':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['DM', 'SDM', 'AM'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['RD'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $teamCodes = $data['teamCodes'];
                                $under_count = User::whereIn('agent_code', $teamCodes)
                                    ->where(function ($q) use ($eval_date) {
                                        $q->whereNull('terminate_date')
                                            ->orWhere('terminate_date', '>=', $eval_date);
                                    })->count();
                                $r['progress_text'] = str_pad($under_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($under_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = round($FYC_check / 1000000, 2) . " triệu đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 6:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 12, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 7:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'STAY_SRD':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['DM', 'SDM', 'AM'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['RD', 'SRD'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $teamCodes = $data['teamCodes'];
                                $under_count = User::whereIn('agent_code', $teamCodes)
                                    ->where(function ($q) use ($eval_date) {
                                        $q->whereNull('terminate_date')
                                            ->orWhere('terminate_date', '>=', $eval_date);
                                    })->count();
                                $r['progress_text'] = str_pad($under_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($under_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = round($FYC_check / 1000000000, 4) . " tỷ đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 6:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 12, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 7:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
                case 'STAY_TD':
                    foreach ($pro_req['requirements'] as $j => $r) {
                        $r['progress_text'] = null;
                        switch ($r['id']) {
                            case 1:
                                $r['progress_text'] = $data['twork'] . " tháng";
                                if ($data['twork'] >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 2:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['DM', 'SDM', 'AM'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 3:
                                $direct_dm_count = $data['dr']->whereIn('designation_code', ['RD', 'SRD', 'TD'])->count();
                                $r['progress_text'] = $direct_dm_count;
                                if ($direct_dm_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 4:
                                $teamCodes = $data['teamCodes'];
                                $under_count = User::whereIn('agent_code', $teamCodes)
                                    ->where(function ($q) use ($eval_date) {
                                        $q->whereNull('terminate_date')
                                            ->orWhere('terminate_date', '>=', $eval_date);
                                    })->count();
                                $r['progress_text'] = str_pad($under_count, 2, "0", STR_PAD_LEFT) . " nhân sự";
                                if ($under_count >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 5:
                                $teamCodes = $data['teamCodes'];
                                $FYC_check = $this->getTotalFYCByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = round($FYC_check / 1000000000, 4) . " tỷ đồng";
                                if ($FYC_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                            case 6:
                                $list_sub_product_code = Util::get_sub_product_code();
                                $teamCodes = $data['teamCodes'];
                                $FYP_sub = $this->calcTotalFYPByCodes($teamCodes, 0, 12, $month, $list_sub_product_code);
                                $FYP_total = $this->getTotalFYPByCodes($teamCodes, 0, 12, $month);
                                $r['progress_text'] = ($FYP_total ? round($FYP_sub * 100 / $FYP_total, 2) : 0) . "%";
                                if ($FYP_total ? $FYP_sub / $FYP_total >= $r['requirement_value'] : 0) $r['is_done'] = 1;
                                break;
                            case 7:
                                $teamCodes = $data['teamCodes'];
                                $k2_check = count($teamCodes) ? $this->getTotalK2ByCodes($teamCodes, 0, 3, $month) / (3 * count($teamCodes)) : 0;
                                $r['progress_text'] = round($k2_check * 100, 2) . "%";
                                if ($k2_check >= $r['requirement_value']) $r['is_done'] = 1;
                                break;
                        }
                        $pro_req['requirements'][$j] = $r;
                    }
                    break;
            }
            $pro_req['evaluation_date'] = $eval_date;
            $pro_reqs[$i] = $pro_req;
        }

        return $pro_reqs;
    }

    private function calcThisMonthRewardType($agent, $data, $type, $month = null)
    {
        $list_result = [];
        $result = 0;
        if (!$month) {
            $valid_month = Carbon::now()->startOfMonth()->format('Y-m-d');
        } else {
            $valid_month = Carbon::createFromFormat('Y-m-d', $month)->startOfMonth()->format('Y-m-d');
        }

        switch ($type) {
            case 'ag_rwd_hldlth':
                if (!in_array($agent->designation_code, ['AG', 'DM', 'SDM', 'AM']))  break;
                if ($data['twork'] > 9) break;
                $fyc_check = $this->getFYC($agent, 0, 3, $month);
                $cc_check = $this->getCC($agent, 0, 3, $month);
                $result = $fyc_check >= 30000000 && $cc_check >= 3 ? 1 : 0;
                $list_result[] = [$result, $valid_month];
                break;
            case 'ag_hh_bhcn':
                if (!in_array($agent->designation_code, ['AG', 'DM', 'SDM', 'AM', 'RD', 'SRD', 'TD'])) break;
                // if (!in_array($agent->designation_code, ['AG'])) break;    // updated Jan 12
                $result = $this->getFYC($agent, 0, 1, $month);
                $list_result[] = [$result, $valid_month];
                break;
            case 'ag_rwd_dscnhq':
                if (!$this->checkValidTpay('q', $month)) break;
                if (!in_array($agent->designation_code, ['AG'])) break;
                $fyc_q_check = $this->getFYC($agent, 0, 3, $month);
                $twork_check = $data['twork'];
                $k2_check = $data['thisMonthMetric']['K2'];
                if ($fyc_q_check < 20000000) {
                    if ($twork_check < 6)
                        $result = 0.4 * $fyc_q_check;
                    else if ($twork_check >= 6 && $twork_check < 14)
                        $result = 0.3 * $fyc_q_check;
                    else if ($twork_check >= 14) {
                        // if ($k2_check < 0.75) break;
                        // else 
                        // tạm bỏ điều kiện K2
                        if ($k2_check < 0.85)
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
                        // if ($k2_check < 0.75) break;
                        // else 
                        // tạm bỏ điều kiện K2
                        if ($k2_check < 0.85)
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
                if (!$this->checkValidTpay('y', $month)) break;
                if (!in_array($agent->designation_code, ['AG'])) break;
                $fyc_y = $this->getFYC($agent, 0, 12, $month);
                $result = 0.1 * $fyc_y;
                if (!$month) {
                    $valid_month = Carbon::now()->addYears(1)->startOfMonth()->format('Y-06-d');
                } else {
                    $valid_month = Carbon::createFromFormat('Y-m-d', $month)->addYears(1)->startOfMonth()->format('Y-06-d');
                }
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
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM'])) break;
                $depdrCodes = $data['depDrCodes'];
                $count_depdr_check = count($depdrCodes);
                if ($count_depdr_check) break;
                $depdr_hldlth_check = $this->getTotalRewardTypeByCodes($depdrCodes, 'ag_rwd_hldlth', 0, 1, $month);
                $result = $depdr_hldlth_check >= 3 ? 1 : 0;
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_dscnht':
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM'])) break;
                $depdrCodes = $data['depDrCodes'];
                $count_depdr_check = count($depdrCodes);
                $count_depdr_aa_check = $this->getTotalAAByCodes($depdrCodes);
                $perc_depdr_aa_check = $count_depdr_check ? $count_depdr_aa_check / $count_depdr_check : 0;
                // list products restrict for this reward
                $fyp = $this->calcThisMonthFYP($agent, $month, null, ['BML', 'FWD']);
                // $fyp = $this->calcThisMonthFYP($agent, $month);   // updated Jan 12
                if ($count_depdr_aa_check < 3 || $perc_depdr_aa_check < 0.5) $result = 0.5 * $fyp;
                else if ($count_depdr_aa_check == 3) $result = 0.55 * $fyp;
                else if ($count_depdr_aa_check > 3) $result = 0.65 * $fyp;
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_qlhtthhptt':
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM'])) break;
                $depdrCodes = $data['depDrCodes'];
                // $count_depdr_check = $data['depDr']->count();
                $count_depdr_check = count($depdrCodes);
                if (!count($depdrCodes)) break;
                $count_depdr_fyc_check = $this->getTotalFYCByCodes($depdrCodes, 0, 1, $month);
                $count_depdr_k2_check = $this->getTotalK2ByCodes($depdrCodes, 0, 1, $month) / $count_depdr_check;
                $count_depdr_aa_check = $this->getTotalAAByCodes($depdrCodes, 0, 1, $month);
                // echo "\ncount_depdr_check: " . implode(",", $depdrCodes);
                // echo "\ncount_depdr_fyc_check: " . $count_depdr_fyc_check;
                // echo "\ncount_depdr_k2_check: " . $count_depdr_k2_check;
                // echo "\ncount_depdr_aa_check: " . $count_depdr_aa_check;

                // if ($count_depdr_k2_check < 0.75) break;
                if ($count_depdr_fyc_check < 25000000) {
                    if ($count_depdr_aa_check < 3) $result = 0.15 * $count_depdr_fyc_check;
                    if ($count_depdr_aa_check >= 3) $result = 0.2 * $count_depdr_fyc_check;
                } else if ($count_depdr_fyc_check >= 25000000) {
                    if ($count_depdr_aa_check < 3) $result = 0.15 * $count_depdr_fyc_check;
                    if ($count_depdr_aa_check == 3) $result = 0.2 * $count_depdr_fyc_check;
                    if ($count_depdr_aa_check > 3) $result = 0.25 * $count_depdr_fyc_check;
                }
                // print_r($result);exit;

                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_qlhqthhptt':
                if (!$this->checkValidTpay('q', $month)) break;
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM'])) break;
                $depdrCodes = $data['depDrCodes'];
                $count_depdr_check = count($depdrCodes);
                if (!$count_depdr_check) break;
                $count_depdr_fyc_check = $this->getTotalFYCByCodes($depdrCodes, 0, 3, $month);
                $count_depdr_k2_check = $this->getTotalK2ByCodes($depdrCodes, 0, 3, $month) / (3 * $count_depdr_check);
                $count_depdr_aa_check = $this->getTotalAAByCodes($depdrCodes, 0, 3, $month);

                // echo "\ncount_depdr_check: " . implode(",", $depdrCodes);
                // echo "\ncount_depdr_fyc_check: " . $count_depdr_fyc_check;
                // echo "\ncount_depdr_k2_check: " . $count_depdr_k2_check;
                // echo "\ncount_depdr_aa_check: " . $count_depdr_aa_check;

                // if ($count_depdr_k2_check < 0.75) break;
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
                if (!$this->checkValidTpay('y', $month)) break;
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM'])) break;
                $depdrCodes = $data['depDrCodes'];
                $count_depdr_check = count($depdrCodes);
                if (!count($depdrCodes)) break;
                $depdr_fyc = $this->getTotalFYCByCodes($depdrCodes, 0, 12, $month);
                $result = 0.06 * $depdr_fyc;
                if (!$month) {
                    $valid_month = Carbon::now()->addYears(1)->startOfMonth()->format('Y-06-d');
                } else {
                    $valid_month = Carbon::createFromFormat('Y-m-d', $month)->addYears(1)->startOfMonth()->format('Y-06-d');
                }
                $list_result[] = [$result, $valid_month];
                break;
            case 'dm_rwd_ptptt':
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM'])) break;
                $drs = $data['dr'];
                if (!$month) {
                    $to = Carbon::now()->endOfMonth()->format('Y-m-d');
                    $from = Carbon::now()->subMonths(12)->startOfMonth()->format('Y-m-d');
                } else {
                    $to = Carbon::createFromFormat('Y-m-d', $month)->endOfMonth()->format('Y-m-d');
                    $from = Carbon::createFromFormat('Y-m-d', $month)->subMonths(12)->startOfMonth()->format('Y-m-d');
                }

                $list_designation = Util::get_designation_code();
                $valid_designation_codes = [];
                foreach ($list_designation as $dc => $name) {
                    $valid_designation_codes[] = $dc;
                    if ($dc == $agent->designation_code) break;
                }
                $validDMs = $drs->whereIn('designation_code', $valid_designation_codes)
                    ->whereHas('promotions', function ($query) use ($from, $to) {
                        $query->where([
                            ['new_designation_code', '=', 'DM'],
                            ['valid_month', '>=', $from],
                            ['valid_month', '<=', $to]
                        ]);
                    })->get();
                if (!count($validDMs)) break;
                foreach ($validDMs as $dm) {
                    $dm_rwd_qlhtthhptt = $this->getRewardType($dm, 'dm_rwd_qlhtthhptt');
                    if (!$dm_rwd_qlhtthhptt) continue;
                    $list_result[] = [0.5 * $dm_rwd_qlhtthhptt, $valid_month];
                    if (!$month) {
                        $list_result[] = [0.25 * $dm_rwd_qlhtthhptt, Carbon::now()->addMonths(6)->startOfMonth()->format('Y-m-d'), $dm->agent_code];
                        $list_result[] = [0.25 * $dm_rwd_qlhtthhptt, Carbon::now()->addMonths(14)->startOfMonth()->format('Y-m-d'), $dm->agent_code];
                    } else {
                        $list_result[] = [0.25 * $dm_rwd_qlhtthhptt, Carbon::createFromFormat('Y-m-d', $month)->addMonths(6)->startOfMonth()->format('Y-m-d'), $dm->agent_code];
                        $list_result[] = [0.25 * $dm_rwd_qlhtthhptt, Carbon::createFromFormat('Y-m-d', $month)->addMonths(14)->startOfMonth()->format('Y-m-d'), $dm->agent_code];
                    }
                }
                break;
            case 'dm_rwd_gt':
                if (!in_array($agent->designation_code, ['DM', 'SDM', 'AM'])) break;
                $drCodes = $data['drCodes'];
                $count_dr_check = count($drCodes);
                if (!$count_dr_check) break;
                $dr_qlhtthhptt_check = $this->getTotalRewardTypeByCodes($drCodes, 'dm_rwd_qlhtthhptt', 0, 1, $month);
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
                $fyp = $this->calcThisMonthFYP($agent, $month, null, ['BML', 'FWD']);
                // echo "fyp " . $fyp; 
                $result = 0.65 * $fyp;
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_hh_nsht':
                if (!in_array($agent->designation_code, ['RD', 'SRD', 'TD'])) break;
                // if ($data['isDrAreaManager']) {
                $teamCodes = $data['teamCodes'];
                $list_dr_RD_plus = [];
                foreach($data['drCodes'] as $drc) {
                    $drcc = User::where(['agent_code' => $drc])->select('designation_code')->first();
                    if($drcc && in_array($drcc->designation_code,['RD','SRD','TD'])) {
                        $list_dr_RD_plus[] = $drc;
                    }
                }
                $except_codes = [];
                foreach($list_dr_RD_plus as $rdp) {
                    $except_codes = array_merge($except_codes, $this->getWholeTeamCodes($rdp));
                }
                $teamCodes = array_filter($teamCodes, function($q) use($except_codes) {return !in_array($q, $except_codes);});
                $count_team_fyc_check = $this->getTotalFYCByCodes($teamCodes, 0, 1, $month);
                if ($count_team_fyc_check < 100000000) $result = 0.15 * $count_team_fyc_check;
                else if ($count_team_fyc_check >= 100000000) $result = 0.2 * $count_team_fyc_check;
                // }
                
                if (in_array($agent->designation_code, ['SRD', 'TD'])) {
                    $drCodes = $data['drCodes'];
                    $count_dr_check = count($drCodes);
                    if ($count_dr_check) {
                        $rd_hh_nsht_check = $this->getTotalRewardTypeByCodes($drCodes, 'rd_hh_nsht', 0, 1, $month);
                        $result += 0.5 * $rd_hh_nsht_check;
                    }
                }
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_rwd_dctkdq':
                if (!$this->checkValidTpay('q', $month)) break;
                if (!in_array($agent->designation_code, ['RD', 'SRD', 'TD'])) break;
                if (!$month) {
                    $target_date = date('Y-m-d');
                } else {
                    $target_date = Carbon::createFromFormat('Y-m-d', $month)->format('Y-m-d');
                }
                $qTargetFYC = $this->getTarget($agent, 'q', 'FYC', $target_date);
                if (!$qTargetFYC) $percFYC_check = 1;
                else {
                    $fyc_check = $this->getFYC($agent, 0, 3, $month);  // FYC check là check theo FYC cá nhân hay toàn vùng?
                    $percFYC_check = $fyc_check / $qTargetFYC;
                }
                if ($percFYC_check < 1) break;
                $k2_check = $this->getK2($agent, 0, 3, $month) / 3;
                // if ($k2_check < 0.75) break; // tạm bỏ điều kiện K2
                $last_quater_HC = $this->getAU($agent, 3, $month);
                $then_quater_HC = $this->getAU($agent, 0, $month);
                $incHC_check = $last_quater_HC ? $then_quater_HC / $last_quater_HC - 1 : 1;
                $teamAGCodes = $data['teamAGCodes'];
                $count_teamAG_fyc_check = $this->getTotalFYCByCodes($teamAGCodes, 0, 1, $month);
                if ($data['isDrAreaManager']) {
                    if ($incHC_check < 0.2) $result = 0.05 * $count_teamAG_fyc_check;
                    else if ($incHC_check >= 0.2) $result = 0.1 * $count_teamAG_fyc_check;
                }
                if (in_array($agent->designation_code, ['SRD', 'TD'])) {
                    $drCodes = $data['drCodes'];
                    $count_dr_check = count($drCodes);
                    if ($count_dr_check) {
                        $rd_hh_nsht_check = $this->getTotalRewardTypeByCodes($drCodes, 'rd_rwd_dctkdq', 0, 1, $month);
                        $result += 0.5 * $rd_hh_nsht_check;
                    }
                }
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_rwd_tndhkd':
                if (!$this->checkValidTpay('y', $month)) break;
                if (!in_array($agent->designation_code, ['RD', 'SRD', 'TD'])) break;
                // $k2_check = $this->getK2($agent, 0, 12) / 12; // K2 này lấy cả năm hay tháng đó
                // if($k2_check < 0.75) break; // tạm bỏ điều kiện K2
                $month_in_position = $data['tp'];
                if (!$month) {
                    $target_date = date('Y-m-d');
                } else {
                    $target_date = Carbon::createFromFormat('Y-m-d', $month)->format('Y-m-d');
                }
                $yTargetFYC = $this->getTarget($agent, 'y', 'FYC', $target_date);
                if (!$yTargetFYC) $percFYC_check = 1;
                else {
                    $fyc_check = $this->getFYC($agent, 0, 12, $month);  // FYC check là check theo FYC cá nhân hay toàn vùng?
                    $percFYC_check = $fyc_check / $yTargetFYC;
                }
                if ($percFYC_check < 1) break;
                if ($data['isDrAreaManager']) {
                    $teamAGCodes = $data['teamAGCodes'];
                    $count_teamAG_fyc_check = $this->getTotalFYCByCodes($teamAGCodes, 0, 1, $month);
                    if ($month_in_position <= 12) $result = 0.035 * $count_teamAG_fyc_check;
                    if ($month_in_position <= 24) $result = 0.05 * $count_teamAG_fyc_check;
                    if ($month_in_position <= 36) $result = 0.07 * $count_teamAG_fyc_check;
                }
                if (in_array($agent->designation_code, ['SRD', 'TD'])) {
                    $drCodes = $data['drCodes'];
                    $count_dr_check = count($drCodes);
                    if ($count_dr_check) {
                        $rd_hh_nsht_check = $this->getTotalRewardTypeByCodes($drCodes, 'rd_rwd_tndhkd', 0, 1, $month);
                        $result += 0.5 * $rd_hh_nsht_check;
                    }
                }
                if (!$month) {
                    $valid_month = Carbon::now()->addYears(1)->startOfMonth()->format('Y-03-d');
                } else {
                    $valid_month = Carbon::createFromFormat('Y-m-d', $month)->addYears(1)->startOfMonth()->format('Y-03-d');
                }
                $list_result[] = [$result, $valid_month];
                break;
            case 'rd_rwd_dbgdmht':
                if (!in_array($agent->designation_code, ['TD'])) break;
                $teamCodes = $data['teamCodes'];
                $count_teamAG_fyc_check = $this->getTotalFYCByCodes($teamCodes, 0, 1, $month);
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
                if (!$this->checkValidTpay('q', $month)) break;
                if (!in_array($agent->designation_code, ['RD', 'SRD', 'TD'])) break;
                if (!$data['isDrAreaManager']) break;
                $teamAGCodes = $data['teamAGCodes'];
                $count_teamAG_check = count($teamAGCodes);
                $count_teamAG_k2_check = $count_teamAG_check ? $this->getTotalK2ByCodes($teamAGCodes, 0, 3, $month) / (3 * $count_teamAG_check) : 0;  // K2 toàn vùng theo quý hay theo tháng hiện tại
                $count_teamAG_fyp_check = $this->getTotalFYPByCodes($teamAGCodes, 0, 1, $month);
                // if ($count_teamAG_k2_check < 0.75) break; // tạm bỏ điều kiện K2
                if ($data['isDrAreaManager']) {
                    if ($count_teamAG_k2_check < 0.8) $result = 0.015 * $count_teamAG_fyp_check;
                    else if ($count_teamAG_k2_check < 0.9) $result = 0.02 * $count_teamAG_fyp_check;
                    else if ($count_teamAG_k2_check >= 0.9) $result = 0.03 * $count_teamAG_fyp_check;
                }

                if (in_array($agent->designation_code, ['SRD', 'TD'])) {
                    $drCodes = $data['drCodes'];
                    $count_dr_check = count($drCodes);
                    if (!$count_dr_check) break;
                    $rd_rwd_dthdtvtt_check = $this->getTotalRewardTypeByCodes($drCodes, 'rd_rwd_dthdtvtt', 0, 1, $month);
                    $result += 0.5 * $rd_rwd_dthdtvtt_check;
                }
                $list_result[] = [$result, $valid_month];
                break;
        }
        return $list_result;
    }

    private function getThisMonthFinalIncome($agent)
    {
    }

    private function checkValidTpay($tpay, $month = null)
    {
        // return true;
        if (!$month) {
            $current_month = Carbon::now()->format('m');
        } else {
            $current_month = Carbon::createFromFormat('Y-m-d', $month)->format('m');
        }

        if ($tpay == 'm') return true;
        if ($tpay == 'q') return in_array($current_month, [3, 6, 9, 12]);
        if ($tpay == 'y') return in_array($current_month, [12]);
    }
}
