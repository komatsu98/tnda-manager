<?php

namespace App\Http\Controllers;
use App\User;
use Carbon\Carbon;
use Exception;
use Storage;
use Illuminate\Http\Request;

class ComissionCalculatorController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function getTwork($agent_code) {
        $agent = User::where(['agent_code' => $agent_code])->first();
        $to = Carbon::now();
        $from = Carbon::createFromFormat('Y-m-d', $agent->join_date);
        $twork = $to->diffInMonths($from);
        return $twork;
    }

    
}
