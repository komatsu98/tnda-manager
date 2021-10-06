<?php

namespace App\Http\Controllers;
use App\User;
use Carbon\Carbon;
use Exception;
use Storage;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function VBIReturn(Request $request)
    {
        $respStatus = $respMsg = '';
        $auth_str = 'tndaauthexample';
        $auth_header = $request->header('Authorization');
        if($auth_header != $auth_str) {
            $respStatus = 'error';
            $respMsg = 'Unauthenticated';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        if (!request()->has('agent_code')) {
            $respStatus = 'error';
            $respMsg = 'Missing agent_code';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $input = $request->input();
        try {
            Storage::append('vbi_return.log', time() . "---" . json_encode($input)) . "\r\n";
        } catch (Exception $e) {
            $respStatus = 'error';
            $respMsg = 'Something went wrong';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $respStatus = 'success';
        return ['status' => $respStatus, 'message' => $respMsg];
    }
}
