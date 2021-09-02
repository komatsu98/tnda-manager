<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Middleware;
use App\User;
use App\AppNews;
use App\Customer;
use App\Customers;
use App\SessionLog;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        // $this->middleware('auth');

        // $this->middleware('log')->only('index');

        // $this->middleware('subscribed')->except('store');
    }


    public function login(Request $request)
    {
        $respStatus = $respMsg = '';
        $data = [];
        if (!request()->has('username') || !request()->has('password') || !request()->has('device') || !request()->has('location') || !request()->has('app_version')) {
            $respStatus = 'error';
            $respMsg = 'Invalid input';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        if (Auth::attempt(['username' => request('username'), 'password' => request('password')])) {
            $id = Auth::id();
            $session = SessionLog::where([
                ['agent_id', '=', $id],
                ['expired_at', '>', Carbon::now()]
            ])->first();
            $input = $request->input();
            if ($session && $session->device == $input['device']) {
                $respStatus = 'success';
                $respMsg = 'Already logged in';
                $data['access_token'] = $session->access_token;
                return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
            }
            $token = Str::random(60);
            $hashed_token = hash('sha256', $token);
            $input['agent_id'] = $id;
            $input['access_token'] = $hashed_token;
            $input['ip_addr'] = $request->ip();

            $now = Carbon::now();
            $now->addSecond(900);
            $input['expired_at'] = $now;
            $new_session = SessionLog::create($input);
            $respStatus = "success";
            $data['access_token'] = $hashed_token;
            return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
        }
        $respStatus = 'error';
        $respMsg = 'Invalid username or password';
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function profile(Request $request)
    {
        $respStatus = $respMsg = '';
        if (!request()->has('access_token')) {
            $respStatus = 'error';
            $respMsg = 'Invalid token';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $check = $this->checkSession(request('access_token'));
        if ($check['status'] == 'error') {
            $respStatus = 'error';
            $respMsg = $check['message'];
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $session = $check['session'];
        $respStatus = 'success';
        $agent = $session->agent;
        $data = [];
        $data['agent'] = $agent;
        // $data['session'] = $session;
        // ()->only(['id', 'name', 'email', 'email']);
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getAgentContracts(Request $request)
    {
        $respStatus = $respMsg = '';
        if (!request()->has('access_token')) {
            $respStatus = 'error';
            $respMsg = 'Invalid token';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $check = $this->checkSession(request('access_token'));
        if ($check['status'] == 'error') {
            $respStatus = 'error';
            $respMsg = $check['message'];
            return ['status' => $respStatus, 'message' => $respMsg];
        }

        $page = 1;
        $limit = 25;
        if (request()->has('page')) {
            $page = intval(request('page'));
            if (!is_int($page)) {
                $respStatus = 'error';
                $respMsg = 'Invalid page';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }
        if (request()->has('limit')) {
            $limit = intval(request('limit'));
            if (!is_int($limit)) {
                $respStatus = 'error';
                $respMsg = 'Invalid limit';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }
        $offset = ($page - 1) * $limit;

        $submit_from = '';
        $submit_to = '';
        if (request()->has('submit_date')) {
            $date_range = explode("_", request('submit_date'));
            if (count($date_range) == 2) {
                try {
                    $submit_from = strlen($date_range[0]) ? Carbon::createFromFormat('Y-m-d', $date_range[0])->format('Y-m-d') : '';
                    $submit_to = strlen($date_range[1]) ? Carbon::createFromFormat('Y-m-d', $date_range[1])->format('Y-m-d') : '';
                } catch (Exception $e) {
                    $respStatus = 'error';
                    $respMsg = 'Invalid date range';
                    return ['status' => $respStatus, 'message' => $respMsg];
                }
            }
        }

        $release_from = '';
        $release_to = '';
        if (request()->has('release_date')) {
            $date_range = explode("_", request('release_date'));
            if (count($date_range) == 2) {
                try {
                    $release_from = strlen($date_range[0]) ? Carbon::createFromFormat('Y-m-d', $date_range[0])->format('Y-m-d') : '';
                    $release_to = strlen($date_range[1]) ? Carbon::createFromFormat('Y-m-d', $date_range[1])->format('Y-m-d') : '';
                } catch (Exception $e) {
                    $respStatus = 'error';
                    $respMsg = 'Invalid date range';
                    return ['status' => $respStatus, 'message' => $respMsg];
                }
            }
        }
        
        $ack_from = '';
        $ack_to = '';
        if (request()->has('ack_date')) {
            $date_range = explode("_", request('ack_date'));
            if (count($date_range) == 2) {
                try {
                    $ack_from = strlen($date_range[0]) ? Carbon::createFromFormat('Y-m-d', $date_range[0])->format('Y-m-d') : '';
                    $ack_to = strlen($date_range[1]) ? Carbon::createFromFormat('Y-m-d', $date_range[1])->format('Y-m-d') : '';
                } catch (Exception $e) {
                    $respStatus = 'error';
                    $respMsg = 'Invalid date range';
                    return ['status' => $respStatus, 'message' => $respMsg];
                }
            }
        }

        $maturity_from = '';
        $maturity_to = '';
        if (request()->has('maturity_date')) {
            $date_range = explode("_", request('maturity_date'));
            if (count($date_range) == 2) {
                try {
                    $maturity_from = strlen($date_range[0]) ? Carbon::createFromFormat('Y-m-d', $date_range[0])->format('Y-m-d') : '';
                    $maturity_to = strlen($date_range[1]) ? Carbon::createFromFormat('Y-m-d', $date_range[1])->format('Y-m-d') : '';
                } catch (Exception $e) {
                    $respStatus = 'error';
                    $respMsg = 'Invalid date range';
                    return ['status' => $respStatus, 'message' => $respMsg];
                }
            }
        }
        $contract_code = '';
        if (request()->has('contract_code')) {
            $contract_code = request('contract_code');
        }
        $status_code = '';
        if (request()->has('status_code')) {
            $status_code = request('status_code');
        }
        $customer_id = '';
        if (request()->has('customer_id')) {
            $customer_id = request('customer_id');
        }
        $customer_name = '';
        if (request()->has('customer_name')) {
            $customer_name = request('customer_name');
        }
        $customer_birthday_from = '';
        $customer_birthday_to = '';
        if (request()->has('customer_birthday')) {
            $date_range = explode("_", request('customer_birthday'));
            if (count($date_range) == 2) {
                try {
                    $customer_birthday_from = strlen($date_range[0]) ? Carbon::createFromFormat('Y-m-d', '2000-' . $date_range[0])->format('Y-m-d') : '';
                    $customer_birthday_to = strlen($date_range[1]) ? Carbon::createFromFormat('Y-m-d', '2000-' . $date_range[1])->format('Y-m-d') : '';
                } catch (Exception $e) {
                    $respStatus = 'error';
                    $respMsg = 'Invalid date range';
                    return ['status' => $respStatus, 'message' => $respMsg];
                }
            }
        }
        
        $agent = $check['session']->agent;
        $contracts = $agent->contracts();

        if($submit_from !== '') {
            $contracts = $contracts->where('submit_date', '>=', $submit_from);
        }
        if($submit_to !== '') {
            $contracts = $contracts->where('submit_date', '<=', $submit_to);
        }
        if($release_from !== '') {
            $contracts = $contracts->where('release_date', '>=', $release_from);
        }
        if($release_to !== '') {
            $contracts = $contracts->where('release_date', '<=', $release_to);
        }
        if($ack_from !== '') {
            $contracts = $contracts->where('ack_date', '>=', $ack_from);
        }
        if($ack_to !== '') {
            $contracts = $contracts->where('ack_date', '<=', $ack_to);
        }
        if($maturity_from !== '') {
            $contracts = $contracts->where('maturity_date', '>=', $maturity_from);
        }
        if($maturity_to !== '') {
            $contracts = $contracts->where('maturity_date', '<=', $maturity_to);
        }
        if($contract_code !== '') {
            $contracts = $contracts->where('contract_code', '=', $contract_code);
        }
        if($status_code !== '') {
            $contracts = $contracts->where('status_code', '=', $status_code);
        }
        if($customer_id !== '') {
            $contracts = $contracts->where('customer_id', '=', $customer_id);
        }
        if($customer_name !== '') {
            $contracts = $contracts->whereHas('customer', function ($query) use ($customer_name) {
                $query->where('fullname','like','%'.$customer_name.'%');
            });
        }
        if($customer_birthday_from !== '') {
            $contracts = $contracts->whereHas('customer', function ($query) use ($customer_birthday_from) {
                $query->whereRaw("DATE_FORMAT(day_of_birth, '%m-%d') >= DATE_FORMAT('" . $customer_birthday_from . "', '%m-%d')");
            });
        }
        if($customer_birthday_to !== '') {
            $contracts = $contracts->whereHas('customer', function ($query) use ($customer_birthday_to) {
                $query->whereRaw("DATE_FORMAT(day_of_birth, '%m-%d') <= DATE_FORMAT('" . $customer_birthday_to . "', '%m-%d')");
            });
        }

        $contracts = $contracts->orderBy('created_at', 'desc')->offset($offset)->take($limit)->get();
        $data = [];
        $respStatus = 'success';
        $data['contracts'] = $contracts;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getAgentComissions(Request $request)
    {
        $respStatus = $respMsg = '';
        if (!request()->has('access_token')) {
            $respStatus = 'error';
            $respMsg = 'Invalid token';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $check = $this->checkSession(request('access_token'));
        if ($check['status'] == 'error') {
            $respStatus = 'error';
            $respMsg = $check['message'];
            return ['status' => $respStatus, 'message' => $respMsg];
        }

        $page = 1;
        $limit = 25;
        if (request()->has('page')) {
            $page = intval(request('page'));
            if (!is_int($page)) {
                $respStatus = 'error';
                $respMsg = 'Invalid page';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }
        if (request()->has('limit')) {
            $limit = intval(request('limit'));
            if (!is_int($limit)) {
                $respStatus = 'error';
                $respMsg = 'Invalid limit';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }
        $offset = ($page - 1) * $limit;

        $received_from = '';
        $received_to = '';
        if (request()->has('received_date')) {
            $date_range = explode("_", request('received_date'));
            if (count($date_range) == 2) {
                try {
                    $received_from = strlen($date_range[0]) ? Carbon::createFromFormat('Y-m-d', $date_range[0])->format('Y-m-d') : '';
                    $received_to = strlen($date_range[1]) ? Carbon::createFromFormat('Y-m-d', $date_range[1])->format('Y-m-d') : '';
                } catch (Exception $e) {
                    $respStatus = 'error';
                    $respMsg = 'Invalid date range';
                    return ['status' => $respStatus, 'message' => $respMsg];
                }
            }
        }

        $agent = $check['session']->agent;

        $comissions = $agent->comissions();
        if($received_from !== '') {
            $comissions = $comissions->where('received_date', '>=', $received_from);
        }
        if($received_to !== '') {
            $comissions = $comissions->where('received_date', '<=', $received_to);
        }

        $comissions = $comissions->orderBy('created_at', 'desc')->offset($offset)->take($limit)->get();
        $data = [];
        $respStatus = 'success';
        $data['comissions'] = $comissions;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getAgentTransactions(Request $request)
    {
        $respStatus = $respMsg = '';
        if (!request()->has('access_token')) {
            $respStatus = 'error';
            $respMsg = 'Invalid token';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $check = $this->checkSession(request('access_token'));
        if ($check['status'] == 'error') {
            $respStatus = 'error';
            $respMsg = $check['message'];
            return ['status' => $respStatus, 'message' => $respMsg];
        }

        $page = 1;
        $limit = 25;
        if (request()->has('page')) {
            $page = intval(request('page'));
            if (!is_int($page)) {
                $respStatus = 'error';
                $respMsg = 'Invalid page';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }
        if (request()->has('limit')) {
            $limit = intval(request('limit'));
            if (!is_int($limit)) {
                $respStatus = 'error';
                $respMsg = 'Invalid limit';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }
        $offset = ($page - 1) * $limit;

        $trans_from = '';
        $trans_to = '';
        if (request()->has('trans_date')) {
            $date_range = explode("_", request('trans_date'));
            if (count($date_range) == 2) {
                try {
                    $trans_from = strlen($date_range[0]) ? Carbon::createFromFormat('Y-m-d', $date_range[0])->format('Y-m-d') : '';
                    $trans_to = strlen($date_range[1]) ? Carbon::createFromFormat('Y-m-d', $date_range[1])->format('Y-m-d') : '';
                } catch (Exception $e) {
                    $respStatus = 'error';
                    $respMsg = 'Invalid date range';
                    return ['status' => $respStatus, 'message' => $respMsg];
                }
            }
        }
        
        $contract_code = '';
        if (request()->has('contract_code')) {
            $contract_code = request('contract_code');
        }

        $agent = $check['session']->agent;
        $transactions = $agent->transactions();
        if($trans_from !== '') {
            $transactions = $transactions->where('trans_date', '>=', $trans_from);
        }
        if($trans_to !== '') {
            $transactions = $transactions->where('trans_date', '<=', $trans_to);
        }
        if($contract_code !== '') {
            $transactions = $transactions->where('contract_code', '=', $contract_code);
        }

        $transactions = $transactions->orderBy('created_at', 'desc')->offset($offset)->take($limit)->get();
        $data = [];
        $respStatus = 'success';
        $data['transactions'] = $transactions;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getAppNews(Request $request)
    {
        $respStatus = $respMsg = '';
        if (!request()->has('access_token')) {
            $respStatus = 'error';
            $respMsg = 'Invalid token';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $check = $this->checkSession(request('access_token'));
        if ($check['status'] == 'error') {
            $respStatus = 'error';
            $respMsg = $check['message'];
            return ['status' => $respStatus, 'message' => $respMsg];
        }

        $page = 1;
        $limit = 25;
        if (request()->has('page')) {
            $page = intval(request('page'));
            if (!is_int($page)) {
                $respStatus = 'error';
                $respMsg = 'Invalid page';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }
        if (request()->has('limit')) {
            $limit = intval(request('limit'));
            if (!is_int($limit)) {
                $respStatus = 'error';
                $respMsg = 'Invalid limit';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }
        $offset = ($page - 1) * $limit;

        $news = AppNews::where(['status' => 1])->orderBy('public_at', 'desc')->offset($offset)->take($limit)->get();
        $data = [];
        $respStatus = 'success';
        $data['news'] = $news;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getPotentialCustomers(Request $request)
    {
        $respStatus = $respMsg = '';
        if (!request()->has('access_token')) {
            $respStatus = 'error';
            $respMsg = 'Invalid token';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $check = $this->checkSession(request('access_token'));
        if ($check['status'] == 'error') {
            $respStatus = 'error';
            $respMsg = $check['message'];
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        
        $page = 1;
        $limit = 25;
        if (request()->has('page')) {
            $page = intval(request('page'));
            if (!is_int($page)) {
                $respStatus = 'error';
                $respMsg = 'Invalid page';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }
        if (request()->has('limit')) {
            $limit = intval(request('limit'));
            if (!is_int($limit)) {
                $respStatus = 'error';
                $respMsg = 'Invalid limit';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }
        $offset = ($page - 1) * $limit;

        $customers = Customer::offset($offset)->take($limit)->get();
        $data = [];
        $respStatus = 'success';
        $data['customers'] = $customers;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    private function checkSession($access_token)
    {
        $checkStatus = $checkMsg = '';
        $session = SessionLog::where([
            ['access_token', '=', $access_token],
            ['expired_at', '>', Carbon::now()]
        ])->first();
        if (!$session) {
            $checkStatus = 'error';
            $checkMsg = 'Expired token';
        } else {
            $checkStatus = 'success';
        }
        return ['status' => $checkStatus, 'message' => $checkMsg, 'session' => $session];
    }
}
