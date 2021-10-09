<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Middleware;
use App\User;
use App\AppNews;
use App\Contract;
use App\Customer;
use App\Customers;
use App\MonthlyMetric;
use App\SessionLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use PhpParser\Node\Expr\Cast\Object_;
use App\Util;

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
        $this->designation_code = Util::get_designation_code();
        $this->contract_search_type_code = Util::get_contract_search_type_code();
        $this->contract_status_code = Util::get_contract_status_code();
        $this->contract_info_await_code = Util::get_contract_info_await_code();
        $this->product_code = Util::get_product_code();
        $this->income_code = Util::get_income_code();
        $this->rwd_things = Util::get_rwd_things();
        $this->metric_code = Util::get_metric_code();
        $this->partners = Util::get_partners();
        $this->marital_status_code = Util::get_marital_status_code();
        $this->instructions = Util::get_instructions();
    }

    public function login(Request $request)
    {
        // print_r(Hash::make('Tnda123456'));
        // print_r(Hash::check('12345678', '$2y$10$u/g3raLlg5JxgVTOoMZtF.X5rgsOCkQLql9TmCTnw0TGZPgj8eAUe'));
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
            $data['latest_version'] = env('APP_VERSION', '0.0.0');
            $data['user'] = User::find($id);
            $data['user']->designation_text = $this->designation_code[$data['user']->designation_code];
            $data['user']->marital_status_text = $this->marital_status_code[$data['user']->marital_status_code];
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

    public function requireUpdateContract(Request $request)
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
        if (!request()->has('contract_code')) {
            $respStatus = 'error';
            $respMsg = 'Missing contract code';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $contract_code = request('contract_code');
        $agent = $check['session']->agent;
        $contract = $agent->contracts()->where(['contract_code' => $contract_code])->first();
        $data = [];
        if (!$contract) {
            $respStatus = 'error';
            $respMsg = 'Contract not found';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        if ($contract->active_require_update_time !== null) {
            $respStatus = 'success';
            $respMsg = 'Already in queue';
            $data['active_require_update_time'] = $contract->active_require_update_time;
            return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
        }
        $contract->active_require_update_time = Carbon::now();
        $contract->save();
        $respStatus = 'success';
        $data['active_require_update_time'] = $contract->active_require_update_time->format('Y-m-d H:i:s');
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

        if (request()->has('view_as')) {
            $view_as_code = request('view_as');
            if (!$this->checkSupervisor($agent, $view_as_code)) {
                $respStatus = 'error';
                $respMsg = 'View as not allowed!';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $agent = User::where(['agent_code' => $view_as_code])->first();
        }

        $data = [];
        $data['agent'] = $agent;
        $data['agent']->designation_text = $this->designation_code[$data['agent']->designation_code];
        $data['agent']->marital_status_text = $this->marital_status_code[$data['agent']->marital_status_code];
        // $data['session'] = $session;
        // ()->only(['id', 'name', 'email', 'email']);
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getContractStatusCodes(Request $request)
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
        $contract_status_codes = $this->contract_status_code;
        $respStatus = 'success';
        $data = [];
        $data['contract_status_codes'] = $contract_status_codes;

        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getContractSearchTypeCodes(Request $request)
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
        $contract_search_type_codes = $this->contract_search_type_code;
        $respStatus = 'success';
        $data = [];
        $data['contract_search_type_codes'] = $contract_search_type_codes;

        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getDesignationCodes(Request $request)
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
        $designation_codes = $this->designation_code;
        $respStatus = 'success';
        $data = [];
        $data['designation_codes'] = $designation_codes;

        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getProductCodes(Request $request)
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
        $product_codes = $this->product_code;
        $respStatus = 'success';
        $data = [];
        $data['product_codes'] = $product_codes;

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
        $search_type = '';
        if (request()->has('search_type')) {
            $search_type = request('search_type');
        }
        $customer_id = '';
        if (request()->has('customer_id')) {
            $customer_id = request('customer_id');
        }
        $customer_name = '';
        if (request()->has('customer_name')) {
            $customer_name = request('customer_name');
        }
        $search = '';
        if (request()->has('search')) {
            $search = request('search');
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
        if (request()->has('view_as')) {
            $view_as_code = request('view_as');
            if (!$this->checkSupervisor($agent, $view_as_code)) {
                $respStatus = 'error';
                $respMsg = 'View as not allowed!';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $agent = User::where(['agent_code' => $view_as_code])->first();
        }

        $view_by = null;
        if (request()->has('view_by')) {
            $view_by = request('view_by');
        }
        if ($view_by == "direct") {
            $teamCodes = $agent->directAgents()->where(['designation_code' => 'AG'])->pluck('agent_code')->toArray();
            $contracts = Contract::whereIn('agent_code', $teamCodes);
        } else if ($view_by == "team") {
            $teamCodes = $this->getWholeTeamCodes($agent);
            $teamCodes[] = $agent->agent_code;
            $contracts = Contract::whereIn('agent_code', $teamCodes);
        } else {
            $contracts = $agent->contracts();
        }

        if ($submit_from !== '') {
            $contracts = $contracts->where('submit_date', '>=', $submit_from);
        }
        if ($submit_to !== '') {
            $contracts = $contracts->where('submit_date', '<=', $submit_to);
        }
        if ($release_from !== '') {
            $contracts = $contracts->where('release_date', '>=', $release_from);
        }
        if ($release_to !== '') {
            $contracts = $contracts->where('release_date', '<=', $release_to);
        }
        if ($ack_from !== '') {
            $contracts = $contracts->where('ack_date', '>=', $ack_from);
        }
        if ($ack_to !== '') {
            $contracts = $contracts->where('ack_date', '<=', $ack_to);
        }
        if ($maturity_from !== '') {
            $contracts = $contracts->where('maturity_date', '>=', $maturity_from);
        }
        if ($maturity_to !== '') {
            $contracts = $contracts->where('maturity_date', '<=', $maturity_to);
        }
        if ($contract_code !== '') {
            $contracts = $contracts->where('contract_code', '=', $contract_code);
        }
        if ($status_code !== '') {
            $contracts = $contracts->where('status_code', '=', $status_code);
        }
        ////////////////////////////////////
        if ($search_type !== '') {
            $search_type_codes = explode(",", $search_type);
            foreach ($search_type_codes as $stc) {
                switch ($stc) {
                    case "1":
                        break;
                }
            }
        }
        if ($customer_id !== '') {
            $contracts = $contracts->where('customer_id', '=', $customer_id);
        }
        if ($customer_name !== '') {
            $contracts = $contracts->whereHas('customer', function ($query) use ($customer_name) {
                $query->where('fullname', 'like', '%' . $customer_name . '%');
            });
        }
        if ($search != '') {
            $contracts = $contracts->where(function($q) use ($search) {
                $q->where('contract_code', 'like', '%' . $search . '%')
                ->orWhereHas('customer', function ($query) use ($search) {
                    $query->where('fullname', 'like', '%' . $search . '%');
                });
            });
        }
        if ($customer_birthday_from !== '') {
            $contracts = $contracts->whereHas('customer', function ($query) use ($customer_birthday_from) {
                $query->whereRaw("DATE_FORMAT(day_of_birth, '%m-%d') >= DATE_FORMAT('" . $customer_birthday_from . "', '%m-%d')");
            });
        }
        if ($customer_birthday_to !== '') {
            $contracts = $contracts->whereHas('customer', function ($query) use ($customer_birthday_to) {
                $query->whereRaw("DATE_FORMAT(day_of_birth, '%m-%d') <= DATE_FORMAT('" . $customer_birthday_to . "', '%m-%d')");
            });
        }

        $contracts = $contracts->orderBy('created_at', 'desc')->offset($offset)->take($limit)->with('customer')->get();
        foreach ($contracts as $contract) {
            $contract->status_text = $this->contract_status_code[$contract->status_code];
            $contract->product_text = $this->product_code[$contract->product_code];
            $contract->sub_product_text = $this->product_code[$contract->sub_product_code];
            $info_awaiting_text = [];
            if ($contract->info_awaiting && strlen($contract->info_awaiting)) {
                $await_codes = explode(",", $contract->info_awaiting);
                if (count($await_codes)) {
                    foreach ($await_codes as $ac) {
                        $info_awaiting_text[] = $this->contract_info_await_code[trim($ac)];
                    }
                }
            }
            $contract->info_awaiting_text = $info_awaiting_text;
        }
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
        if (request()->has('view_as')) {
            $view_as_code = request('view_as');
            if (!$this->checkSupervisor($agent, $view_as_code)) {
                $respStatus = 'error';
                $respMsg = 'View as not allowed!';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $agent = User::where(['agent_code' => $view_as_code])->first();
        }

        $comissions = $agent->comissions();
        if ($received_from !== '') {
            $comissions = $comissions->where('received_date', '>=', $received_from);
        }
        if ($received_to !== '') {
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
        if (request()->has('view_as')) {
            $view_as_code = request('view_as');
            if (!$this->checkSupervisor($agent, $view_as_code)) {
                $respStatus = 'error';
                $respMsg = 'View as not allowed!';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $agent = User::where(['agent_code' => $view_as_code])->first();
        }

        $transactions = $agent->transactions();
        if ($trans_from !== '') {
            $transactions = $transactions->where('trans_date', '>=', $trans_from);
        }
        if ($trans_to !== '') {
            $transactions = $transactions->where('trans_date', '<=', $trans_to);
        }
        if ($contract_code !== '') {
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

        $customers = Customer::where(['status' => 1])->offset($offset)->take($limit)->get();
        $data = [];
        $respStatus = 'success';
        $data['customers'] = $customers;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getCustomers(Request $request)
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

        $agent = $check['session']->agent;
        if (request()->has('view_as')) {
            $view_as_code = request('view_as');
            if (!$this->checkSupervisor($agent, $view_as_code)) {
                $respStatus = 'error';
                $respMsg = 'View as not allowed!';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $agent = User::where(['agent_code' => $view_as_code])->first();
        }

        $view_by = null;
        if (request()->has('view_by')) {
            $view_by = request('view_by');
        }
        if ($view_by == "direct") {
            $teamCodes = $agent->directAgents()->where(['designation_code' => 'AG'])->pluck('agent_code')->toArray();
            $customers = Customer::whereHas('contracts.agent', function ($query) use ($teamCodes) {
                $query->whereIn('agent_code', $teamCodes);
            })->offset($offset)->take($limit)->get();
        } else if ($view_by == "team") {
            $teamCodes = $this->getWholeTeamCodes($agent);
            $teamCodes[] = $agent->agent_code;
            $customers = Customer::whereHas('contracts.agent', function ($query) use ($teamCodes) {
                $query->whereIn('agent_code', $teamCodes);
            })->offset($offset)->take($limit)->get();
        } else {
            $customers = Customer::whereHas('contracts.agent', function ($query) use ($agent) {
                $query->where('agent_code', '=', $agent->agent_code);
            })->offset($offset)->take($limit)->get();
        }

        $data = [];
        $respStatus = 'success';
        $data['customers'] = $customers;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getPromotionProgress(Request $request)
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

        $agent = $check['session']->agent;
        if (request()->has('view_as')) {
            $view_as_code = request('view_as');
            if (!$this->checkSupervisor($agent, $view_as_code)) {
                $respStatus = 'error';
                $respMsg = 'View as not allowed!';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $agent = User::where(['agent_code' => $view_as_code])->first();
        }

        $promotions = [
            [
                'code' => 'PRO_AM_DM',
                'title' => 'Thăng cấp Trưởng phòng kinh doanh',
                'requiment_count' => 8,
                'gained_count' => 8,
                'evaluation_date' => '2021-09-30',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (AG)',
                        'requirement_text' => '6 tháng',
                        'progress_text' => '10 tháng',
                        'is_done' => 1
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_text' => '06 nhân sự',
                        'progress_text' => '06 nhân sự',
                        'is_done' => 1
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số đại lý hoạt động (AA) trực tiếp GIỚI THIỆU trong 06 tháng vừa qua và còn làm việc tại thời điểm xét (mỗi AA chỉ được tính 1 lần)',
                        'requirement_text' => '04 đại lý',
                        'progress_text' => '05 đại lý',
                        'is_done' => 1
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng FYC trong 06 tháng vừa qua (bao gồm kết quả của cá nhân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_text' => '50 triệu đồng',
                        'progress_text' => '60 triệu đồng',
                        'is_done' => 1
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_text' => '30%',
                        'progress_text' => '32.5%',
                        'is_done' => 1
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của cá nhân đại lý tại thời điểm xét',
                        'requirement_text' => '75%',
                        'progress_text' => '80.5%',
                        'is_done' => 1
                    ],
                    [
                        'id' => 7,
                        'title' => 'Hoàn thành khóa huấn luyện “Nền tảng quản lý và trả bài bằng Video”',
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => 'Hoàn thành',
                        'is_done' => 1
                    ],
                    [
                        'id' => 8,
                        'title' => 'Không vi phạm quy chế Công ty',
                        'requirement_text' => '75%',
                        'progress_text' => 'Chưa hoàn thành',
                        'is_done' => 1
                    ]
                ]
            ],
            [
                'code' => 'PRO_DM_SDM',
                'requiment_count' => 8,
                'title' => 'Thăng cấp Trưởng phòng kinh doanh cấp cao',
                'gained_count' => 7,
                'evaluation_date' => '2021-09-30',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (DM)',
                        'requirement_text' => '6 tháng',
                        'progress_text' => '10 tháng',
                        'is_done' => 1
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số DM báo cáo TRỰC TIẾP cho quản lý này (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_text' => '03 DM',
                        'progress_text' => '04 DM',
                        'is_done' => 1
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_text' => '20 nhân sự',
                        'progress_text' => '21 nhân sự',
                        'is_done' => 1
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng số đại lý hoạt động (AA) trực tiếp tuyển trong 06 tháng vừa qua và còn làm việc tại thời điểm xét (mỗi AA chỉ được tính 1 lần)',
                        'requirement_text' => '04 AA',
                        'progress_text' => '06 AA',
                        'is_done' => 1
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_text' => '100 triệu đồng',
                        'progress_text' => '90 triệu đồng',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_text' => '30%',
                        'progress_text' => '32.5%',
                        'is_done' => 1
                    ],
                    [
                        'id' => 7,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_text' => '80%',
                        'progress_text' => '80%',
                        'is_done' => 1
                    ],
                    [
                        'id' => 8,
                        'title' => 'Hoàn thành khóa huấn luyện “Nền tảng quản lý”',
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => 'Hoàn thành',
                        'is_done' => 1
                    ]
                ]
            ]
        ];
        $data = [];
        $respStatus = 'success';
        $data['promotions'] = $promotions;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];

        // if (!request()->has('access_token')) {
        //     $respStatus = 'error';
        //     $respMsg = 'Invalid token';
        //     return ['status' => $respStatus, 'message' => $respMsg];
        // }
        // $check = $this->checkSession(request('access_token'));
        // if ($check['status'] == 'error') {
        //     $respStatus = 'error';
        //     $respMsg = $check['message'];
        //     return ['status' => $respStatus, 'message' => $respMsg];
        // }

        // $page = 1;
        // $limit = 25;
        // if (request()->has('page')) {
        //     $page = intval(request('page'));
        //     if (!is_int($page)) {
        //         $respStatus = 'error';
        //         $respMsg = 'Invalid page';
        //         return ['status' => $respStatus, 'message' => $respMsg];
        //     }
        // }
        // if (request()->has('limit')) {
        //     $limit = intval(request('limit'));
        //     if (!is_int($limit)) {
        //         $respStatus = 'error';
        //         $respMsg = 'Invalid limit';
        //         return ['status' => $respStatus, 'message' => $respMsg];
        //     }
        // }
        // $offset = ($page - 1) * $limit;

        // $customers = Customer::offset($offset)->take($limit)->get();
        // $data = [];
        // $respStatus = 'success';
        // $data['customers'] = $customers;
        // return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getTeam(Request $request)
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

        $agent = $check['session']->agent;
        if (request()->has('view_as')) {
            $view_as_code = request('view_as');
            if (!$this->checkSupervisor($agent, $view_as_code)) {
                $respStatus = 'error';
                $respMsg = 'View as not allowed!';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $agent = User::where(['agent_code' => $view_as_code])->first();
        }

        $data = [];
        $respStatus = 'success';
        $team = $agent->directAgents;
        foreach ($team as $dr_agent) {
            $dr_agent['team'] = $dr_agent->directAgents;
            $dr_agent->designation_text = $this->designation_code[$dr_agent->designation_code];
            $dr_agent->marital_status_text = $this->marital_status_code[$dr_agent->marital_status_code];
            foreach ($dr_agent['team'] as $dr_2_agent) {
                $dr_2_agent->designation_text = $this->designation_code[$dr_2_agent->designation_code];
                $dr_2_agent->marital_status_text = $this->marital_status_code[$dr_2_agent->marital_status_code];
            }
        }
        $data['team'] = $team;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getIncome(Request $request)
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

        // $month_from = Carbon::now()->format('Y-m');
        // $month_to = Carbon::now()->format('Y-m');
        // if (request()->has('month')) {
        //     $date_range = explode("_", request('month'));
        //     if (count($date_range) == 2) {
        //         try {
        //             $month_from = strlen($date_range[0]) ? Carbon::createFromFormat('Y-m', $date_range[0])->format('Y-m') : '';
        //             $month_to = strlen($date_range[1]) ? Carbon::createFromFormat('Y-m', $date_range[1])->format('Y-m') : '';
        //         } catch (Exception $e) {
        //             $respStatus = 'error';
        //             $respMsg = 'Invalid month range';
        //             return ['status' => $respStatus, 'message' => $respMsg];
        //         }
        //     }
        // }
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

        $agent = $check['session']->agent;
        if (request()->has('view_as')) {
            $view_as_code = request('view_as');
            if (!$this->checkSupervisor($agent, $view_as_code)) {
                $respStatus = 'error';
                $respMsg = 'View as not allowed!';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $agent = User::where(['agent_code' => $view_as_code])->first();
        }

        $income = $agent->monthlyIncomes();

        // if ($month_from !== '') {
        //     $month_from = $month_from . '-01';
        //     $income = $income->where('month', '>=', $month_from);
        // }
        // if ($month_to !== '') {
        //     $month_to = $month_to . '-01';
        //     $income = $income->where('month', '<=', $month_to);
        // }
        $selectStr = 'month, ';
        foreach($this->income_code as $field => $name) {
            $selectStr .= 'sum('.$field.') as '.$field.',';
        }
        $selectStr = substr($selectStr,0,-1);
        $income = $income
        ->groupBy('month')
        ->selectRaw($selectStr)
        ->orderBy('month', 'desc')->offset($offset)->take($limit)->get();
        $explained_income = array();
        foreach ($income as $in) {
            $income_tmp = [
                'month' => substr($in->month, 0, 7),
                'file' => '',
                'detail' => [],
                'total' => 0
            ];
            foreach ($in->toArray() as $key => $value) {
                if (!isset($this->income_code[$key]) || $value == 0)
                    continue;
                $rwd_thing = isset($this->rwd_things[$key]) ? $this->rwd_things[$key] : null;
                $unit = 'vnd';
                if ($rwd_thing !== null) {
                    $unit = 'other';
                } else {
                    $income_tmp['total'] += $value;
                }

                $tmp = [
                    'name' => $this->income_code[$key],
                    'amount' => $value,
                    'unit' => $unit,
                    'reward_other' => $rwd_thing
                ];
                $income_tmp['detail'][] = $tmp;
            }
            $explained_income[] = $income_tmp;
        }

        $data = [];
        $respStatus = 'success';
        $data['income'] = $explained_income;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getMetrics(Request $request)
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

        // $month_from = Carbon::now()->format('Y-m');
        // $month_to = Carbon::now()->format('Y-m');
        // if (request()->has('month')) {
        //     $date_range = explode("_", request('month'));
        //     if (count($date_range) == 2) {
        //         try {
        //             $month_from = strlen($date_range[0]) ? Carbon::createFromFormat('Y-m', $date_range[0])->format('Y-m') : '';
        //             $month_to = strlen($date_range[1]) ? Carbon::createFromFormat('Y-m', $date_range[1])->format('Y-m') : '';
        //         } catch (Exception $e) {
        //             $respStatus = 'error';
        //             $respMsg = 'Invalid month range';
        //             return ['status' => $respStatus, 'message' => $respMsg];
        //         }
        //     }
        // }

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

        $agent = $check['session']->agent;
        if (request()->has('view_as')) {
            $view_as_code = request('view_as');
            if (!$this->checkSupervisor($agent, $view_as_code)) {
                $respStatus = 'error';
                $respMsg = 'View as not allowed!';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $agent = User::where(['agent_code' => $view_as_code])->first();
        }

        $view_by = null;
        if (request()->has('view_by')) {
            $view_by = request('view_by');
        }
        if ($view_by == "direct") {
            $teamCodes = $agent->directAgents()->where(['designation_code' => 'AG'])->pluck('agent_code')->toArray();
            $metrics = MonthlyMetric::whereIn('agent_code', $teamCodes);
        } else if ($view_by == "team") {
            $teamCodes = $this->getWholeTeamCodes($agent);
            $teamCodes[] = $agent->agent_code;
            $metrics = MonthlyMetric::whereIn('agent_code', $teamCodes);
        } else {
            $metrics = $agent->monthlyMetrics();
        }

        // if ($month_from !== '') {
        //     $month_from = $month_from . '-01';
        //     $metrics = $metrics->where('month', '>=', $month_from);
        // }
        // if ($month_to !== '') {
        //     $month_to = $month_to . '-01';
        //     $metrics = $metrics->where('month', '<=', $month_to);
        // }
        $metrics = $metrics->groupBy('month')
        ->selectRaw('month, sum(FYC) as FYC, sum(FYP) as FYP, sum(IP) as IP, sum(APE) as APE, sum(CC) as CC, sum(K2) as K2, sum(AA) as AA')
        ->orderBy('month', 'desc')->offset($offset)->take($limit)->get();
        
        $explained_metrics = array();
        foreach ($metrics as $m) {
            $m_tmp = [
                'month' => substr($m->month, 0, 7),
                'detail' => [],
            ];
            $other_unit = [
                'CC' => 'Hợp đồng',
                'AA' => 'Hoạt động',
                'K2' => '%'
            ];
            foreach ($m->toArray() as $key => $value) {
                if (!isset($this->metric_code[$key]))
                    continue;

                $tmp = [
                    'name' => $this->metric_code[$key],
                    'amount' => $value,
                    'unit' => isset($other_unit[$key]) ? $other_unit[$key] : 'vnd'
                ];
                $m_tmp['detail'][] = $tmp;
            }
            $explained_metrics[] = $m_tmp;
        }
        $data = [];


        $respStatus = 'success';
        $data['metric'] = $explained_metrics;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getDocuments(Request $request)
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

        $data = [];
        $respStatus = 'success';
        $documents = [
            [
                'name' => 'Tài liệu bán hàng 01',
                'url' => 'http://103.226.249.106/files/Huong_dan_thi_Online-1 (1).pdf',
                'image' => 'http://103.226.249.106/images/11-Tài liệu.png'
            ],
            [
                'name' => 'Giá trị của niềm tin',
                'url' => 'https://www.youtube.com/watch?v=VGTNUVlFK8k',
                'image' => 'http://103.226.249.106/images/logo.jpg'
            ],
            [
                'name' => 'Hướng dẫn bán hàng 02',
                'url' => 'https://fb.watch/82V53xTyRC/',
                'image' => 'http://103.226.249.106/images/logo.jpg'
            ]
        ];

        $data['documents'] = $documents;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];

        // $page = 1;
        // $limit = 25;
        // if (request()->has('page')) {
        //     $page = intval(request('page'));
        //     if (!is_int($page)) {
        //         $respStatus = 'error';
        //         $respMsg = 'Invalid page';
        //         return ['status' => $respStatus, 'message' => $respMsg];
        //     }
        // }
        // if (request()->has('limit')) {
        //     $limit = intval(request('limit'));
        //     if (!is_int($limit)) {
        //         $respStatus = 'error';
        //         $respMsg = 'Invalid limit';
        //         return ['status' => $respStatus, 'message' => $respMsg];
        //     }
        // }
        // $offset = ($page - 1) * $limit;

        // $customers = Customer::offset($offset)->take($limit)->get();
        // $data = [];
        // $respStatus = 'success';
        // $data['customers'] = $customers;
        // return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getInstructions(Request $request)
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

        $data = [];
        $respStatus = 'success';
        $instructions = $this->instructions;

        // '<!DOCTYPE html><html><head><title></title><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body><div style="width: 100%;margin: auto;padding: 10px"><div style="padding: 10px"><p style="font-weight: bold;font-size: 18px">Hướng dẫn đăng nhập</p></div><div style="padding: 10px"><p>Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập HướngHướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập</p></div><div style="text-align: center;padding: 10px"><img src="http://103.226.249.106/images/i_login.png" style="width: 75%;"></div><div style="padding: 10px"><p>Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập HướngHướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập</p></div></div><div style="width: 100%;margin: auto;padding: 10px"><div style="padding: 10px"><p style="font-weight: bold;font-size: 18px">Hướng dẫn đăng nhập</p></div><div style="padding: 10px"><p>Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập HướngHướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập</p></div><div style="text-align: center;padding: 10px"><img src="http://103.226.249.106/images/i_login.png" style="width: 75%;"></div><div style="padding: 10px"><p>Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập HướngHướng dẫn đăng nhập Hướng dẫn đăng nhập Hướng dẫn đăng nhập</p></div></div></body></html>';

        $data['instructions'] = $instructions;
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];

        // $page = 1;
        // $limit = 25;
        // if (request()->has('page')) {
        //     $page = intval(request('page'));
        //     if (!is_int($page)) {
        //         $respStatus = 'error';
        //         $respMsg = 'Invalid page';
        //         return ['status' => $respStatus, 'message' => $respMsg];
        //     }
        // }
        // if (request()->has('limit')) {
        //     $limit = intval(request('limit'));
        //     if (!is_int($limit)) {
        //         $respStatus = 'error';
        //         $respMsg = 'Invalid limit';
        //         return ['status' => $respStatus, 'message' => $respMsg];
        //     }
        // }
        // $offset = ($page - 1) * $limit;

        // $customers = Customer::offset($offset)->take($limit)->get();
        // $data = [];
        // $respStatus = 'success';
        // $data['customers'] = $customers;
        // return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    public function getPartners(Request $request)
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
        $partners = $this->partners;
        $respStatus = 'success';
        $data = [];
        $data['partners'] = $partners;

        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }

    private function checkSupervisor($supervisor, $agent_code)
    {
        $agent = User::where(['agent_code' => $agent_code])->first();
        if (!$agent) return false;
        $result = false;
        $direct_supervisor = $agent->supervisor;
        while ($direct_supervisor && !$result) {
            if ($direct_supervisor->agent_code == $supervisor->agent_code) {
                $result = true;
                break;
            }
            $direct_supervisor = $direct_supervisor->supervisor;
        }
        return $result;
    }

    private function getWholeTeamCodes($supervisor) {
        $codes = [];
        $direct_agents = $supervisor->directAgents;
        if(!count($direct_agents)) {
            return [];
        } else {
            foreach($direct_agents as $dr_agent) {
                array_push($codes, $dr_agent->agent_code);
                $codes = array_merge($codes, $this->getWholeTeamCodes($dr_agent));
            }
            return $codes;
        }
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
