<?php

namespace App\Http\Controllers;
use App\User;
use App\Contract;
use App\Customer;
use Carbon\Carbon;
use Exception;
use Storage;
use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleClient;
use App\Util;

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
        $agent_code = str_replace(['tnda'], '', strtolower($input['agent_code']));

        try {
            Storage::append('vbi_return.log', date('Y-m-d H:i:s') . "---" . json_encode($input)) . "\r\n";
            $agent = User::where(['agent_code' => $agent_code])->first();
            if(!$agent) {
                $respStatus = 'error';
                $respMsg = 'Invalid agent_code';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $short_data = $input['data'];
            $check_exists = Contract::where(['partner_contract_code' => $short_data['so_id']])->first();
            if($check_exists) {
                $respStatus = 'error';
                $respMsg = 'Duplicate so_id';
                return ['status' => $respStatus, 'message' => $respMsg];
            }
            $detail = $this->VBIFetchData($short_data['so_id']);
            
            if($detail) {
                $r = $this->VBICreateContract($agent_code, $detail->response_data);
                if(!$r) {
                    $respStatus = 'error';
                    $respMsg = 'Unable to save contract detail';
                    return ['status' => $respStatus, 'message' => $respMsg];
                }
            } else {
                $respMsg = 'Unable to get VBI contract data';
            }
        } catch (Exception $e) {
            $respStatus = 'error';
            $respMsg = 'Something went wrong ' . $e->getMessage();
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        if($respStatus == '') $respStatus = 'success';
        return ['status' => $respStatus, 'message' => $respMsg];
    }

    public function VBIFetchData($id) {
        $headers = [
            'Authority' => config('partner.VBI')['data_auth'],
        ];
        
        $client = new GuzzleClient([
            'headers' => $headers
        ]);
        $url = config('partner.VBI')['data_url'] . '?noi_dung=' . $id;
        $request = $client->get($url);
        $response = $request->getBody()->getContents();
        // $response = '{"response_code":"00","response_message":"SUCSESS","so_id_vbi":null,"response_data":{"HOP_DONG":[{"MA_DVI":"ABANK_DD","SO_ID_VBI":"20211019036382","SO_ID_DTAC":"TNDA000001","SO_HD":"020.KD09.HD.CN.21.009576","TEN":"Aaa","DCHI":"Long Biên","NGAY_HL":"19/10/2021","NGAY_KT":"19/10/2022","TONG_PHI":1432000.0,"NGAY_HT":"19/10/2021","TRANG_THAI":"D","KEY_VNP":null,"KEY_PAYOO":null}],"DANH_SACH":[{"TEN":"Aaa","DCHI":"Long Biên","CMT":"020088000069","NGAY_SINH":"08/10/1992","D_THOAI":"12321321123","EMAIL":"khanhvn.vbi@vietinbank.vn","SO_ID_DT":"20211019036383","GOI_BH":"BAN_LE_TITAN","TEN_GOI_BH":"VBIcare gói Titan","TEN_NV":"Bảo hiểm sức khỏe - NEW","LH_NV":"CN.6","LINK_GCN":"http://14.160.90.226:8084/sales/Preview/?code=&type=GCN"}]}}';
        try {
            $response = json_decode($response);
        } catch(Exception $e) {
            return false;
        }
        if($response->response_code == '00') return $response;
        return false;
    }

    public function VBICreateContract($agent_code, $data) {
        $hd = $data->HOP_DONG[0];
        $customer = Customer::create([
            'fullname' => $hd->TEN,
            'address' => $hd->DCHI,
        ]);
        $product_code_list = [];
        foreach($data->DANH_SACH as $c) {
            Customer::create([
                'fullname' => $c->TEN,
                'day_of_birth' => $c->NGAY_SINH,
                'identity_num' => $c->CMT,
                'address' => $c->DCHI,
                'email' => $c->EMAIL,
                'mobile_phone' => $c->D_THOAI,
                'beneficiary_from_id' => $customer->id
            ]);
            $product_code_list[] = $c->GOI_BH;
        }

        $hc = Util::get_highest_contract_code();
        $contract = null;
        $contract_data = [
            'agent_code' => $agent_code,
            'customer_id' => $customer->id,
            'contract_code' => $hc+1,
            'partner_contract_code' => $hd->SO_ID_VBI,
            'partner_code' => 'VBI',
            'submit_date' => Carbon::createFromFormat('d/m/Y', $hd->NGAY_HT)->format('Y-m-d'),
            // Nếu chưa 'D' nghĩa là chưa được kí -> nộp vào
            // 'D' => phát hành
            'status_code' => $hd->TRANG_THAI == 'D' ? 'RL' : 'SM',
            'contract_year' => 1,
            'product_code' => implode( ",", $product_code_list),
            'premium' => $hd->TONG_PHI,
            'premium_term' => $hd->TONG_PHI,
            // Đã kí => nộp đủ
            // Chưa kí => 0
            'premium_received' => $hd->TRANG_THAI == 'D' ? $hd->TONG_PHI : 0,
            'term_code' => 'y',
            'release_date' => Carbon::createFromFormat('d/m/Y', $hd->NGAY_HL)->format('Y-m-d'),
            'expire_date' => Carbon::createFromFormat('d/m/Y', $hd->NGAY_KT)->format('Y-m-d'),
            'maturity_date' => Carbon::createFromFormat('d/m/Y', $hd->NGAY_KT)->format('Y-m-d'),
        ];
        $contract = Contract::create($contract_data);
        while($contract == null) {
            try {
                $contract_data['contract_code'] += 1;
                $contract = Contract::create($contract_data);
            } catch(Exception $e) {
                $contract = null;
            }
        }
        return $contract;
    }

    public function VBICheckUpdateContract($id = null) {
        $respStatus = $respMsg = '';
        $contract  = Contract::where(['partner_contract_code' => $id])->first();
        if(!$contract) {
            $respStatus = 'error';
            $respMsg = 'Contract not found';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $detail = $this->VBIFetchData($id);
        if(!$detail || !$detail->response_data->HOP_DONG[0]) {
            $respStatus = 'error';
            $respMsg = 'Fetch data from VBI failed';
            return ['status' => $respStatus, 'message' => $respMsg];
        }
        $hd = $detail->response_data->HOP_DONG[0];
        $c_status = $hd->TRANG_THAI == 'D' ? 'RL' : 'SM';
        if($c_status != $contract->status_code) {
            $contract->status_code = $c_status;
            $contract->save();
            $respMsg = 'Status updated';
        }
        if($respStatus == '') $respStatus = 'success';
        return ['status' => $respStatus, 'message' => $respMsg];
    }
}
