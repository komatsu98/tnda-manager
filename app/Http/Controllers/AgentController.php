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
use PhpParser\Node\Expr\Cast\Object_;

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
            $data['latest_version'] = env('APP_VERSION', '0.0.0');
            $data['user'] = User::find($id);
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
        if ($customer_id !== '') {
            $contracts = $contracts->where('customer_id', '=', $customer_id);
        }
        if ($customer_name !== '') {
            $contracts = $contracts->whereHas('customer', function ($query) use ($customer_name) {
                $query->where('fullname', 'like', '%' . $customer_name . '%');
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

        $customers = Customer::offset($offset)->take($limit)->get();
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

        $promotions = [
            [
                'code' => 'PRO_AM_DM',
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
                'requiment_count' => 7,
                'gained_count' => 8,
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

        $data = [];
        $respStatus = 'success';
        $team = [
            '60006123' => [
                'name' => 'Nguyễn Văn A',
                'designation_code' => 'SDM',
                'team' => [
                    '60006124' => [
                        'name' => 'Nguyễn Văn C',
                        'designation_code' => 'DM',
                        'team' => [
                            '60006125' => [
                                'name' => 'Nguyễn Văn D',
                                'designation_code' => 'AG',
                                'team' => []
                            ],
                            '60006126' => [
                                'name' => 'Nguyễn Văn E',
                                'designation_code' => 'AG',
                                'team' => []
                            ]
                        ]
                    ],
                    '60006127' => [
                        'name' => 'Nguyễn Văn C',
                        'designation_code' => 'AG',
                        'team' => []
                    ]
                ]
            ],
            '60006128' => [
                'name' => 'Nguyễn Văn B',
                'designation_code' => 'AG',
                'team' => []
            ]
        ];
        $data['team'] = $team;
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

        $income_month = '';
        if (request()->has('month')) {
            $month = intval(request('month'));
            try {
                $income_month = strlen($month) ? Carbon::createFromFormat('m-d', $month)->format('m-d') : '';
            } catch (Exception $e) {
                $respStatus = 'error';
                $respMsg = 'Invalid date range';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
        }

        $data = [];

        // specific month
        if ($income_month !== '') {
            
        } else {
            // predict this month
        }

        $income = [
            'total' => 25600400,
            'detail' => [
                [
                    'title' => 'Hoa hồng bán hàng cá nhân',
                    'amount' => 18000000
                ],
                [
                    'title' => 'Thưởng doanh số cá nhân hàng quý',
                    'amount' => 4000000
                ],
                [
                    'title' => 'Thưởng năm (gắn bó dài lâu)',
                    'amount' => 2600400
                ]
            ],
        ];

        $respStatus = 'success';
        $data['income'] = $income;
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
