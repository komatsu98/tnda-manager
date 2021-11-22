<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Validator;
use Excel;
use App\UsersImport;
use App\ContractsImportVBI;
use Illuminate\Support\Facades\DB;

use App\User;
use App\Admin;
use App\Contract;
use App\Customer;
use App\AppNews;
use App\MonthlyIncome;
use App\MonthlyMetric;


class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.index');
    }

    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    public function listUsers()
    {
        $users = User::orderBy('agent_code', 'asc');
        if (request()->has('id')) {
            $id = request('id');
            $users = $users->where('id', '=', $id);
        }
        if (request()->has('search')) {
            $str = trim(strtolower(request('search')), ' ');
            $users = $users->where('username', 'LIKE', '%' . $str . '%')
                ->orwhere('fullname', 'LIKE', '%' . $str . '%')
                ->orWhere('email', 'LIKE', '%' . $str . '%')
                ->orWhere('id', 'LIKE', '%' . $str . '%');
        }
        $users = $users->paginate(25);
        foreach ($users as $user) {
            $this->parseUserDetail($user);
        }
        return view('user.list', ['users' => $users]);
    }

    public function createUser()
    {
        return view('user.add', ['list_designation_code' => Util::get_designation_code()]);
    }

    public function createBulkUsers()
    {
        return view('user.import');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'fullname' => 'required',
            'identity_num' => 'required',
            'designation_code' => 'required',
            'gender' => 'required',
        ]);
        $input = $request->input();
        $check_exists = User::where(['identity_num' => $input['identity_num']])->first();
        if ($check_exists) {
            return redirect('admin/users')->with('error', 'Số CMND đã tồn tại!');
        }
        $highest_agent_code = $input['designation_code'] == "TD" ? intval(Util::get_highest_agent_code(21)) : intval(Util::get_highest_agent_code());
        $agent_code = $highest_agent_code + 1;
        $input['agent_code'] = $agent_code;
        $input['password'] = Hash::make($input['identity_num']);
        $input['highest_designation_code'] = $input['designation_code'];
        $input['username'] = 'TNDA' . $agent_code;
        try {
            $new_agent = User::create($input);
        } catch (Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại!');
        }

        return redirect('admin/user/' . $agent_code)->with('success', 'Thêm thành viên thành công');
    }

    public function importUsers(Request $request)
    {       
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx'
        ]);
        // process the form
        if ($validator->fails()) {
            return redirect()->to(route('admin.user.bulk_create'))->withErrors($validator);
        } else {
            $import = new UsersImport;
            Excel::import($import, $request->file('file'));
            $errors = [];
            $success = [];
            $final = [];
            // dd($import->data); exit;
            foreach($import->data as $user) {
                try 
                {
                    $reference = User::where(['username' => $user['IFA_ref_code']])->orWhere(['identity_num' => $user['IFA_ref_code']])->first();
                    if ($reference) {
                        $user['reference_code'] = $reference->agent_code;
                    }
                    $supervisor = User::where(['username' => $user['IFA_supervisor_code']])->orWhere(['identity_num' => $user['IFA_supervisor_code']])->first();
                    if ($supervisor) {
                        $user['supervisor_code'] = $supervisor->agent_code;
                    }
                    $highest_agent_code = $user['designation_code'] == "TD" ? intval(Util::get_highest_agent_code(true)) : intval(Util::get_highest_agent_code());
                    $saved_numbers = Util::get_saved_numbers();
                    while(in_array($highest_agent_code + 1, $saved_numbers)) {
                        $highest_agent_code++;
                    }
                    $agent_code = str_pad($highest_agent_code + 1, 6, "0", STR_PAD_LEFT);
                    $user['agent_code'] = $agent_code;
                    $user['username'] = 'TNDA' . $agent_code;
                    $user['reference_r'] = [];
                    $user['supervisor_r'] = [];


                    User::create($user);
                    $reference_r = User::where(['IFA_ref_code' => $user['identity_num']])->orWhere(['IFA_ref_code' => $user['username']])->get();
                    foreach($reference_r as $rf) {
                        $rf->reference_code = $user['agent_code'];
                        $user['reference_r'][] = $rf->agent_code;
                        $rf->save();
                    }
                    $supervisor_r = User::where(['IFA_supervisor_code' => $user['identity_num']])->orWhere(['IFA_supervisor_code' => $user['username']])->get();
                    foreach($supervisor_r as $sf) {
                        $sf->supervisor_code = $user['agent_code'];
                        $sf->save();
                        $user['supervisor_r'][] = $sf->agent_code;
                    }
                    // $final[] = $user;
                    $success[] = $user['fullname'] . " " . $user['agent_code']. " " . $user['identity_num'] . "\r\n";
                }
                catch(\Illuminate\Database\QueryException $e){
                    $errors[] = $user['fullname'] . " " . $user['agent_code']. " " . $user['identity_num'] . " FAILED:" .$e->getMessage() . "\r\n";
                }
            }
            // dd($final); exit;

            if(count($errors)) {
                return back()->with('error', "SUCCESS ". json_encode($success) . "\r\n===============\r\nERROR " . json_encode($errors));
            } else {
                return back()->with('success', 'Thêm mới danh sách thành viên thành công' . json_encode($success));
            }
        }

        return back();
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);
        $path = $request->file('file')->getRealPath();
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $rows = $sheet->rangeToArray('A2:AJ' . $highestRow);

        foreach ($rows as $k => $row) {
            $identity_alloc_date = Carbon::createFromFormat('d/m/Y', $row[7])->format('Y-m-d');
            $day_of_birth = Carbon::createFromFormat('d-m-Y', $row[2] . '-' . $row[3] . '-' . $row[4])->format('Y-m-d');
            $marital_status_code = strtolower($row[12]) == 'kết hôn' ? 'M' : (strtolower($row[12]) == 'độc thân' ? 'S' : (strtolower($row[12]) == 'ly hôn' ? 'D' : ''));
            $IFA_start_date = $row[18] == '' ? $row[18] : Carbon::createFromFormat('d/m/Y', $row[18])->format('Y-m-d');
            $designation_code = str_replace(['"', 'TNDA'], '', $row[13]);
            $IFA_supervisor_designation_code = str_replace(['"', 'TNDA'], '', $row[19]);
            $data = [
                'fullname' => $row[1],
                'day_of_birth' => $day_of_birth,
                'gender' => strtolower($row[5]) == 'nam' ? 0 : 1,
                'identity_num' => $row[6],
                'identity_alloc_date' => $identity_alloc_date,
                'identity_alloc_place' => $row[8],
                'resident_place' => $row[9],
                'email' => $row[10],
                'mobile_phone' => $row[11],
                'marital_status_code' => $marital_status_code,
                'IFA_start_date' => '',
                'IFA_branch' => '',
                'IFA' => '',
                'designation_code' => $designation_code,
                'IFA_ref_code' => trim($row[14]),
                'IFA_ref_name' => $row[15],
                'IFA_supervisor_code' => trim($row[17]),
                'IFA_supervisor_name' => $row[18],
                'IFA_supervisor_designation_code' => $IFA_supervisor_designation_code,
                'IFA_TD_code' => '',
                'IFA_TD_name' => $row[25],
            ];
            $reference = User::where(['username' => $data['IFA_ref_code']])->orWhere(['identity_num' => $data['IFA_ref_code']])->first();
            if ($reference) {
                $data['reference_code'] = $reference->agent_code;
            }
            $supervisor = User::where(['username' => $data['IFA_supervisor_code']])->orWhere(['identity_num' => $data['IFA_supervisor_code']])->first();
            if ($supervisor) {
                $data['supervisor_code'] = $supervisor->agent_code;
            }
            $highest_agent_code = $data['designation_code'] == "TD" ? intval(Util::get_highest_agent_code(true)) : intval(Util::get_highest_agent_code());
            $agent_code = str_pad($highest_agent_code + 1, 6, "0", STR_PAD_LEFT);
            $data['agent_code'] = $agent_code;
            $data['alloc_code_date'] = Carbon::now()->format('Y-m-d');
            $data['promote_date'] = Carbon::now()->format('Y-m-d');
            $data['password'] = Hash::make($data['identity_num']);
            $data['highest_designation_code'] = $data['designation_code'];
            $data['username'] = 'TNDA' . $agent_code;
            try {
                $new_agent = User::create($data);
            } catch (Exception $e) {
                return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại!');
            }
        }
        return back()->with('success', 'Thêm mới danh sách thành viên thành công');
    }

    public function getUser(Request $request, $agent_code)
    {
        $user = User::where(['agent_code' => $agent_code])->first();
        if(!$user) {
            return back()->with('error', 'Không tìm thấy thành viên!');
        }
        $this->parseUserDetail($user);

        return view('user.detail', compact('user'));
    }

    public function getUserRaw(Request $request, $agent_code)
    {
        $user = User::where(['agent_code' => $agent_code])->first();
        // echo "<pre>";print_r(implode('","', array_keys($user->toArray())));exit;
        // $this->parseUserDetail($user);
        if($user) {
            $list_designation_code = Util::get_designation_code();
            if($user->designation_code) $user->designation_text = $list_designation_code[$user->designation_code];
        }
        return $user;
    }

    private function parseUserDetail($user)
    {
        $user->gender_text = $user->gender == 0 ? 'Nam' : 'Nữ';
        $user->marital_status_text = (!is_null($user->marital_status_code) && $user->marital_status_code != '') ? (Util::get_marital_status_code())[$user->marital_status_code] : '';
        $ref = $user->reference;
        if ($ref) {
            $user->ref_code = "TNDA" . $user->reference_code;
            $user->ref_name = $ref->fullname;
        } else {
            $user->ref_code = $user->IFA_ref_code;
            $user->ref_name = $user->IFA_ref_name;
        }
        $supervisor = $user->supervisor;
        if ($supervisor) {
            $user->supervisor_code = "TNDA" . $user->supervisor_code;
            $user->supervisor_name = $supervisor->fullname;
            $user->supervisor_designation_code = $supervisor->designation_code;
        } else {
            $user->supervisor_code = $user->IFA_supervisor_code;
            $user->supervisor_name = $user->IFA_supervisor_name;
            // $user->supervisor_designation_code = $user->IFA_supervisor_designation_code;
        }
        $TD = Util::get_TD($user);
        if ($TD) {
            $user->TD_code = "TNDA" . $TD->agent_code;
            $user->TD_name = $TD->fullname;
        } else {
            $user->TD_code = $user->IFA_TD_code;
            $user->TD_name = $user->IFA_TD_name;
        }
    }

    public function editUser($agent_code)
    {
        $user = User::where(['agent_code' => $agent_code])->first();
        $list_designation_code = Util::get_designation_code();
        $list_marital_status_code = Util::get_marital_status_code();
        if ($user) {
            if($user->reference_code) {
                $reference = $user->reference;
                if($reference) {
                    $user->reference_name = $reference->fullname;
                } else {
                    $user->reference_name = 'Người này chưa được cấp code';
                }
                $user->reference_code = 'TNDA' . $user->reference_code;
            }
            $supervisor = $user->supervisor;
            if ($supervisor) {
                $user->supervisor_code = "TNDA" . $user->supervisor_code;
                $user->supervisor_name = $supervisor->fullname;
                if($supervisor->designation_code) $user->supervisor_designation_text = $list_designation_code[$supervisor->designation_code];
            } else {
                $user->supervisor_code = $user->IFA_supervisor_code;
                $user->supervisor_name = 'Người này chưa được cấp code';
                $user->supervisor_designation_code = $user->IFA_supervisor_designation_code;
            }
            return view('user.edit', [
                'user' => $user, 
                'list_designation_code' => $list_designation_code,
                'list_marital_status_code' => $list_marital_status_code,
            ]);
        } else {
            return redirect('admin/users')->with('error', 'Không tìm thấy thành viên.');
        }
    }

    public function updateUser(Request $request, $agent_code)
    {
        // $userId = Auth::user()->id;
        // $request->validate();
        $user = User::where(['agent_code' => $agent_code])->first();
        if (!$user) {
            return redirect('admin/users')->with('error', 'Không tìm thấy thành viên.');
        }
        // echo "<pre>";
        $input = $request->input();
        $userUpdate = $user->update($input);
        if ($userUpdate) {
            return back()->with('success', 'Cập nhật thông tin thành viên thành công.');
        } else {
            return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại.');
        }
    }

    public function listContracts()
    {
        $contracts = Contract::orderBy('created_at', 'desc');
        if (request()->has('id')) {
            $id = request('id');
            $contracts = $contracts->where('id', '=', $id);
        }
        if (request()->has('search')) {
            $str = trim(strtolower(request('search')), ' ');
            $contracts = $contracts->where('partner_contract_code', 'LIKE', '%' . $str . '%')
                ->orwhere('agent_code', 'LIKE', '%' . $str . '%');
        }
        $contracts = $contracts->paginate(25);
        foreach ($contracts as $contract) {
            $this->parseContractDetail($contract);
        }
        return view('contract.list', ['contracts' => $contracts]);
    }

    public function getContract(Request $request, $contract_id)
    {
        $contract = Contract::find($contract_id);
        if(!$contract) {
            return back()->with('error', 'Không tìm thấy hợp đồng!');
        }
        $this->parseContractDetail($contract);

        return view('contract.detail', compact('contract'));
    }

    public function getContractRaw(Request $request, $contract_id)
    {
        $contract = Contract::find($contract_id);
        // $this->parseContractDetail($contract);
        // if($contract) {
        //     $list_designation_code = Util::get_designation_code();
        //     if($contract->designation_code) $contract->designation_text = $list_designation_code[$user->designation_code];
        // }
        return $contract;
    }

    private function parseContractDetail($contract)
    {
        $list_contract_status_code = Util::get_contract_status_code();
        $list_product_code = Util::get_product_code();
        $list_contract_info_await_code = Util::get_contract_info_await_code();
        $list_partners = Util::get_partners();
        $list_contract_bg_color = Util::get_contract_bg_color();
        $list_contract_term_code = Util::get_contract_term_code();
        $contract->status_text = $list_contract_status_code[$contract->status_code];
        $product_texts = [];
        foreach(explode(",", $contract->product_code) as $pc) {
            $product_texts[] = $list_product_code[trim($pc)];
        }

        $contract->product_text = implode(", ", $product_texts);

        $sub_product_texts = [];
        foreach(explode(",", $contract->sub_product_code) as $spc) {
            if(trim($spc) == '') continue;
            $sub_product_texts[] = $list_product_code[trim($spc)];
        }
        
        $contract->sub_product_text = implode(", ", $sub_product_texts);

        $info_awaiting_text = [];
        if ($contract->info_awaiting && strlen($contract->info_awaiting)) {
            $await_codes = explode(",", $contract->info_awaiting);
            if (count($await_codes)) {
                foreach ($await_codes as $ac) {
                    $info_awaiting_text[] = $list_contract_info_await_code[trim($ac)];
                }
            }
        }

        $contract->bg_color = $list_contract_bg_color[$contract->status_code];
        $partner_index = array_search($contract->partner_code, array_column($list_partners, 'code'));
        if ($partner_index !== false) {
            $contract->partner_text = $list_partners[$partner_index]['name'];
        } else $contract->partner_text = null;

        $contract->term_text = $list_contract_term_code[$contract->term_code];
        $contract->info_awaiting_text = implode(", ", $info_awaiting_text);
        $agent_name = $contract->agent()->pluck('fullname');
        if(count($agent_name)) $contract->agent_name = $agent_name[0];
        else $contract->agent_name = 'Người này chưa được cấp code';
        $customer_name = $contract->customer()->pluck('fullname');
        if(count($customer_name)) $contract->customer_name = $customer_name[0];
        else $contract->customer_name = 'Khách hàng chưa được tạo';
        $contract->agent_code = $contract->agent_code;
    }

    public function createContract(Request $requset)
    {
        $list_partners = Util::get_partners();
        $list_product_code = Util::get_product_code();
        $list_contract_status_code = Util::get_contract_status_code();
        $list_contract_bg_color = Util::get_contract_bg_color();
        $list_contract_term_code = Util::get_contract_term_code();
        $list_contract_info_await_code = Util::get_contract_info_await_code();
        
        return view('contract.add', [
            'list_partners' => $list_partners,
            'list_product_code' => $list_product_code,
            'list_contract_status_code' => $list_contract_status_code,
            'list_contract_info_await_code' => $list_contract_info_await_code,
            'list_contract_term_code' => $list_contract_term_code,
        ]);
    }

    public function storeContract(Request $request)
    {
        $request->validate([
            'customer_id' => 'required',
            'partner_code' => 'required',
            'partner_contract_code' => 'required',
            'agent_code' => 'required',
            'product_code' => 'required',
            'term_code' => 'required',
            'premium_term' => 'required',
            'status_code' => 'required',
        ]);
        $input = $request->input();
        // $check_exists = Contract::where(['identity_num' => $input['identity_num']])->first();
        // if ($check_exists) {
        //     return redirect('admin/users')->with('error', 'Số CMND đã tồn tại!');
        // }
        
        $input['expire_date'] = $input['maturity_date'];
        if(isset($input['product_code']) && is_array($input['product_code'])) {
            $input['product_code'] = implode(",",$input['product_code']);
        }
        if(isset($input['sub_product_code']) && is_array($input['sub_product_code'])) {
            $input['sub_product_code'] = implode(",",$input['sub_product_code']);
        }
        if(isset($input['info_awaiting']) && is_array($input['info_awaiting'])) {
            $input['info_awaiting'] = implode(",",$input['info_awaiting']);
        }
        $input['agent_code'] = str_replace("tnda", "", strtolower($input['agent_code']));
        
        try {
            $new_contract = Contract::create($input);
        } catch (Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại!');
        }

        return redirect('admin/contract/' . $new_contract->id)->with('success', 'Thêm hợp đồng thành công');
    }

    public function editContract($contract_id)
    {
        $contract = Contract::find($contract_id);
        if ($contract) {
            $list_partners = Util::get_partners();
            $list_product_code = Util::get_product_code();
            $list_contract_status_code = Util::get_contract_status_code();
            $list_contract_bg_color = Util::get_contract_bg_color();
            $list_contract_term_code = Util::get_contract_term_code();
            $list_contract_info_await_code = Util::get_contract_info_await_code();
            $this->parseContractDetail($contract);
            if($contract->product_code) $contract->product_code = explode(',', $contract->product_code);
            else $contract->product_code = [];
            if($contract->sub_product_code) $contract->sub_product_code = explode(',', $contract->sub_product_code);
            else $contract->sub_product_code = [];
            if($contract->info_awaiting) $contract->info_awaiting = explode(',', $contract->info_awaiting);
            else $contract->info_awaiting = [];
            
            return view('contract.edit', [
                'contract' => $contract,
                'list_partners' => $list_partners,
                'list_product_code' => $list_product_code,
                'list_contract_status_code' => $list_contract_status_code,
                'list_contract_info_await_code' => $list_contract_info_await_code,
                'list_contract_term_code' => $list_contract_term_code,
            ]);
        } else {
            return redirect('admin/contracts')->with('error', 'Không tìm thấy hợp đồng.');
        }
    }

    public function updateContract(Request $request, $contract_id)
    {
        $contract = Contract::find($contract_id);
        if (!$contract) {
            return redirect('admin/contracts')->with('error', 'Không tìm thấy hợp đồng.');
        }
        $input = $request->input();
        if(isset($input['product_code']) && is_array($input['product_code'])) {
            $input['product_code'] = implode(",",$input['product_code']);
        }
        if(isset($input['sub_product_code']) && is_array($input['sub_product_code'])) {
            $input['sub_product_code'] = implode(",",$input['sub_product_code']);
        }
        if(isset($input['info_awaiting']) && is_array($input['info_awaiting'])) {
            $input['info_awaiting'] = implode(",",$input['info_awaiting']);
        }
        $input['agent_code'] = str_replace("tnda", "", strtolower($input['agent_code']));
        $contractUpdate = $contract->update($input);
        if ($contractUpdate) {
            return back()->with('success', 'Cập nhật thông tin hợp đồng thành công.');
        } else {
            return back()->with('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
        }
    }

    public function createBulkContracts()
    {
        $list_partners = Util::get_partners();
        return view('contract.import', ['list_partners' => $list_partners]);
    }

    public function importContracts(Request $request)
    {       
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx',
            'partner_code' => 'required'
        ]);
        // process the form
        if ($validator->fails()) {
            return redirect()->to(route('admin.contract.bulk_create'))->withErrors($validator);
        } else {
            switch($request->partner_code) {
                case 'VBI':
                    $import = new ContractsImportVBI;
                    break;
                default:
                    $import = null;
            }
            if(!$import) return back()->with('error', 'Có lỗi xảy ra. Vui lòng thử lại.');

            Excel::import($import, $request->file('file'));
            $errors = [];
            $success = [];
            $final = [];
            dd($import->data); exit;
            foreach($import->data as $user) {
                try 
                {
                    $reference = User::where(['username' => $user['IFA_ref_code']])->orWhere(['identity_num' => $user['IFA_ref_code']])->first();
                    if ($reference) {
                        $user['reference_code'] = $reference->agent_code;
                    }
                    $supervisor = User::where(['username' => $user['IFA_supervisor_code']])->orWhere(['identity_num' => $user['IFA_supervisor_code']])->first();
                    if ($supervisor) {
                        $user['supervisor_code'] = $supervisor->agent_code;
                    }
                    $highest_agent_code = $user['designation_code'] == "TD" ? intval(Util::get_highest_agent_code(true)) : intval(Util::get_highest_agent_code());
                    $saved_numbers = Util::get_saved_numbers();
                    while(in_array($highest_agent_code + 1, $saved_numbers)) {
                        $highest_agent_code++;
                    }
                    $agent_code = str_pad($highest_agent_code + 1, 6, "0", STR_PAD_LEFT);
                    $user['agent_code'] = $agent_code;
                    $user['username'] = 'TNDA' . $agent_code;
                    $user['reference_r'] = [];
                    $user['supervisor_r'] = [];


                    User::create($user);
                    $reference_r = User::where(['IFA_ref_code' => $user['identity_num']])->orWhere(['IFA_ref_code' => $user['username']])->get();
                    foreach($reference_r as $rf) {
                        $rf->reference_code = $user['agent_code'];
                        $user['reference_r'][] = $rf->agent_code;
                        $rf->save();
                    }
                    $supervisor_r = User::where(['IFA_supervisor_code' => $user['identity_num']])->orWhere(['IFA_supervisor_code' => $user['username']])->get();
                    foreach($supervisor_r as $sf) {
                        $sf->supervisor_code = $user['agent_code'];
                        $sf->save();
                        $user['supervisor_r'][] = $sf->agent_code;
                    }
                    // $final[] = $user;
                    $success[] = $user['fullname'] . " " . $user['agent_code']. " " . $user['identity_num'] . "\r\n";
                }
                catch(\Illuminate\Database\QueryException $e){
                    $errors[] = $user['fullname'] . " " . $user['agent_code']. " " . $user['identity_num'] . " FAILED:" .$e->getMessage() . "\r\n";
                }
            }
            // dd($final); exit;

            if(count($errors)) {
                return back()->with('error', "SUCCESS ". json_encode($success) . "\r\n===============\r\nERROR " . json_encode($errors));
            } else {
                return back()->with('success', 'Thêm mới hợp đồng thành công' . json_encode($success));
            }
        }
        return back();
    }
    public function getCustomerRaw(Request $request, $id)
    {
        $customer = Customer::where(['id' => $id])->first();
        // ->with('beneficiaries')
        if($customer) {
        }
        return $customer;
    }

    public function exportUsersStructure() {
        $start_agent_code = '000003';
        $agent = User::where(['agent_code' => $start_agent_code])->first();
        $structure = [];
        $structure[$agent->designation_code . " - " . $agent->fullname . " - TNDA" . $agent->agent_code] = getStructure($structure, $agent);
        // echo '<pre>';
        // print_r();
        $users_with_no_super = User::where(['supervisor_code' => null])->get();
        foreach($users_with_no_super as $user) {
            $structure_x = [];
            $structure[$user->designation_code . " - " . $user->fullname . " - TNDA" . $user->agent_code] = getStructure($structure_x, $user);
        }

        return view('user.structure', ['data' => json_encode($structure)]);
    }
}
function getStructure($structure, $agent) {
    $unders = $agent->directUnders;
    $structure[$agent->designation_code . " - " .$agent->fullname . " - TNDA" . $agent->agent_code] = '.';
    if(count($unders)) {
        $structure[$agent->designation_code . " - " .$agent->fullname . " - TNDA" . $agent->agent_code] = [];
        foreach($unders as $under) {
            $structure[$agent->designation_code . " - " .$agent->fullname . " - TNDA" . $agent->agent_code][$under->designation_code . " - " .$under->fullname . " - TNDA" . $under->agent_code] = getStructure($structure, $under);
        }
    }
    return $structure[$agent->designation_code . " - " .$agent->fullname . " - TNDA" . $agent->agent_code];
};