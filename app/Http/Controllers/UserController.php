<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Middleware;
use App\User;
use App\SessionLog;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

use function PHPSTORM_META\exitPoint;

class UserController extends Controller
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

    public function profile(Request $request)
    {
        $respStatus = $respMsg = '';
        if (!request()->has('access_token')) {
            $respStatus = 'error';
            $respMsg = 'Invalid token';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $access_token = request('access_token');
        $session = SessionLog::where([
            ['access_token', '=', $access_token],
            ['expired_at', '>', Carbon::now()]
        ])->first();
        if(!$session) {
            $respStatus = 'error';
            $respMsg = 'Expired token';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $respStatus = 'success';
        $user = $session->user;
        $data = [];
        $data['user'] = $user;
        // $data['session'] = $session;
        // ()->only(['id', 'name', 'email', 'email']);
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
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
        if (Auth::attempt(['name' => request('username'), 'password' => request('password')])) {
            $id = Auth::id();
            $session = SessionLog::where([
                ['user_id', '=', $id],
                ['expired_at', '>', Carbon::now()]
            ])->first();
            $input = $request->input();
            if($session && $session->device == $input['device']) {
                $respStatus = 'success';
                $respMsg = 'Already logged in';
                $data['access_token'] = $session->access_token;
                return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
            }
            $token = Str::random(60);
            $hashed_token = hash('sha256', $token);
            $input['user_id'] = $id; 
            $input['access_token'] = $hashed_token; 
            $now = Carbon::now();
            $now->addSecond(900);
            $input['expired_at'] = $now;
            $new_session = SessionLog::create($input);
            $respStatus = "success";
            $data['access_token'] = $hashed_token;
            return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
        }
        
        return ['status' => $respStatus, 'message' => $respMsg, 'data' => $data];
    }
}

?>