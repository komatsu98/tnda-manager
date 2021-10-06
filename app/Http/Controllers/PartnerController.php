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
        $data = [];
        // if (!request()->has('agent_code')) {
        //     $respStatus = 'error';
        //     $respMsg = 'Invalid input';
        //     return ['status' => $respStatus, 'message' => $respMsg];
        // }
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
