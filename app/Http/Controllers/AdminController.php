<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

use Illuminate\Support\Facades\Validator;
use Excel;
use App\Imports\UsersImport;
use App\Imports\ContractsImportVBI;
use App\Imports\ContractsImportBML;
use App\Imports\ContractsImportBV;
use App\Imports\ContractsImportFWD;

use Illuminate\Support\Facades\DB;

use App\User;
use App\Admin;
use App\Contract;
use App\Customer;
use App\AppNews;
use App\MonthlyIncome;
use App\MonthlyMetric;
use App\Transaction;
use App\Comission;
use App\ContractProduct;
use App\Http\Controllers\ComissionCalculatorController;
use App\PromotionProgress;
use App\Promotion;
use Exception;


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
        $users = User::orderBy('agent_code', 'desc');
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
            foreach ($import->data as $user) {
                try {
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
                    while (in_array($highest_agent_code + 1, $saved_numbers)) {
                        $highest_agent_code++;
                    }
                    $agent_code = str_pad($highest_agent_code + 1, 6, "0", STR_PAD_LEFT);
                    $user['agent_code'] = $agent_code;
                    $user['username'] = 'TNDA' . $agent_code;
                    $user['reference_r'] = [];
                    $user['supervisor_r'] = [];


                    User::create($user);
                    $reference_r = User::where(['IFA_ref_code' => $user['identity_num']])->orWhere(['IFA_ref_code' => $user['username']])->get();
                    foreach ($reference_r as $rf) {
                        $rf->reference_code = $user['agent_code'];
                        $user['reference_r'][] = $rf->agent_code;
                        $rf->save();
                    }
                    $supervisor_r = User::where(['IFA_supervisor_code' => $user['identity_num']])->orWhere(['IFA_supervisor_code' => $user['username']])->get();
                    foreach ($supervisor_r as $sf) {
                        $sf->supervisor_code = $user['agent_code'];
                        $sf->save();
                        $user['supervisor_r'][] = $sf->agent_code;
                    }
                    // $final[] = $user;
                    $success[] = $user['fullname'] . " " . $user['agent_code'] . " " . $user['identity_num'] . "\r\n";
                } catch (\Illuminate\Database\QueryException $e) {
                    $errors[] = $user['fullname'] . " " . $user['agent_code'] . " " . $user['identity_num'] . " FAILED:" . $e->getMessage() . "\r\n";
                }
            }
            // dd($final); exit;

            if (count($errors)) {
                return back()->with('error', "SUCCESS " . json_encode($success) . "\r\n===============\r\nERROR " . json_encode($errors));
            } else {
                return back()->with('success', 'Thêm mới danh sách thành viên thành công' . json_encode($success));
            }
        }

        return back();
    }

    public function getUser(Request $request, $agent_code)
    {
        $user = User::where(['agent_code' => $agent_code])->first();
        if (!$user) {
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
        if ($user) {
            $list_designation_code = Util::get_designation_code();
            if ($user->designation_code) $user->designation_text = $list_designation_code[$user->designation_code];
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
        $TD = Util::get_super_by_des($user);
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
            if ($user->reference_code) {
                $reference = $user->reference;
                if ($reference) {
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
                if ($supervisor->designation_code) $user->supervisor_designation_text = $list_designation_code[$supervisor->designation_code];
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
        if (!$contract) {
            return back()->with('error', 'Không tìm thấy hợp đồng!');
        }
        $this->parseContractDetail($contract);
        $list_product_code = Util::get_product_code();

        return view('contract.detail', compact('contract', 'list_product_code'));
    }

    public function listContractProducts(Request $request, $contract_id)
    {
        $contract = Contract::find($contract_id);
        if (!$contract) return back()->with('error', 'Không tìm thấy hợp đồng!');
        $contract_products = $contract->products()->orderBy('created_at', 'desc');
        $contract_products = $contract_products->paginate(25);

        foreach ($contract_products as $contract_product) {
            $this->parseContractProductDetail($contract_product, $contract);
        }
        return view('contract.product.list', compact('contract_products', 'contract'));
    }

    public function getContractProduct(Request $request, $contract_product_id)
    {
        $contract_product = ContractProduct::find($contract_product_id);
        if (!$contract_product) {
            return back()->with('error', 'Không tìm thấy sản phẩm ứng với hợp đồng!');
        }
        $this->parseContractProductDetail($contract_product);
        return view('contract.product.detail', compact('contract_product'));
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

    private function parseContractProductDetail($contract_product, $contract = null)
    {
        $list_product_code = Util::get_product_code();
        $list_contract_term_code = Util::get_contract_term_code();
        if (!$contract) $contract = $contract_product->contract;
        $contract_product->partner_contract_code = $contract->partner_contract_code;
        $contract_product->product_text = $list_product_code[$contract_product->product_code];
        $contract_product->term_text = $list_contract_term_code[$contract->term_code];
        $contract_product->transaction_count = $contract_product->transactions()->count();
    }

    private function parseContractDetail($contract)
    {
        $list_contract_status_code = Util::get_contract_status_code();
        $list_product_code = Util::get_product_code();
        $list_contract_info_await_code = Util::get_contract_info_await_code();
        $list_partners = Util::get_partners();
        $list_contract_bg_color = Util::get_contract_bg_color();
        $list_contract_term_code = Util::get_contract_term_code();
        $contract->status_text = isset($list_contract_status_code[$contract->status_code]) ? $list_contract_status_code[$contract->status_code] : '';
        $product_texts = [];
        $sub_product_texts = [];
        $comission = 0;
        $premium = 0;
        $premium_term = 0;
        $premium_received = 0;
        $renewal_premium_received = 0;
        $contract_products = $contract->products;
        foreach ($contract_products as $pc) {
            $product_texts[] = $list_product_code[trim($pc->product_code)];
            $premium += $pc->premium;
            $comission += $pc->comission;
            $premium_term += $pc->premium_term;
            $premium_received += $pc->premium_received;
            $renewal_premium_received += $pc->renewal_premium_received;
            // list sub => sub_product_texts[] =...
        }
        $contract->product_text = implode(", ", $product_texts);
        $contract->sub_product_text = implode(", ", $sub_product_texts);
        $contract->comission = $comission;
        $contract->premium = $premium;
        $contract->premium_term = $premium_term;
        $contract->premium_received = $premium_received;
        $contract->renewal_premium_received = $renewal_premium_received;

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

        $contract->term_text = $contract->term_code ? $list_contract_term_code[$contract->term_code] : "Không xác định";
        $contract->info_awaiting_text = implode(", ", $info_awaiting_text);
        $agent_name = $contract->agent()->pluck('fullname');
        if (count($agent_name)) $contract->agent_name = $agent_name[0];
        else $contract->agent_name = 'Người này chưa được cấp code';
        $customer_name = $contract->customer()->pluck('fullname');
        if (count($customer_name)) $contract->customer_name = $customer_name[0];
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
        if (isset($input['product_code']) && is_array($input['product_code'])) {
            $input['product_code'] = implode(",", $input['product_code']);
        }
        if (isset($input['sub_product_code']) && is_array($input['sub_product_code'])) {
            $input['sub_product_code'] = implode(",", $input['sub_product_code']);
        }
        if (isset($input['info_awaiting']) && is_array($input['info_awaiting'])) {
            $input['info_awaiting'] = implode(",", $input['info_awaiting']);
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
            if ($contract->product_code) $contract->product_code = explode(',', $contract->product_code);
            else $contract->product_code = [];
            if ($contract->sub_product_code) $contract->sub_product_code = explode(',', $contract->sub_product_code);
            else $contract->sub_product_code = [];
            if ($contract->info_awaiting) $contract->info_awaiting = explode(',', $contract->info_awaiting);
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
        if (isset($input['product_code']) && is_array($input['product_code'])) {
            $input['product_code'] = implode(",", $input['product_code']);
        }
        if (isset($input['sub_product_code']) && is_array($input['sub_product_code'])) {
            $input['sub_product_code'] = implode(",", $input['sub_product_code']);
        }
        if (isset($input['info_awaiting']) && is_array($input['info_awaiting'])) {
            $input['info_awaiting'] = implode(",", $input['info_awaiting']);
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
            switch ($request->partner_code) {
                case 'VBI':
                    $import = new ContractsImportVBI;
                    break;
                case 'BML':
                    $import = new ContractsImportBML;
                    break;
                case 'FWD':
                    $import = new ContractsImportFWD;
                    break;
                case 'BV':
                    $import = new ContractsImportBV;
                    break;
                default:
                    $import = null;
            }
            if (!$import) return back()->with('error', 'Có lỗi xảy ra. Vui lòng thử lại.');

            Excel::import($import, $request->file('file'));
            $errors = [];
            $success = [];
            $final = [];
            // dd($import->data);
            // exit;
            $agent_list = [];
            $month_list = [];
            // echo "<pre>";

            foreach ($import->data as $partner_contract_code => $dt) {
                try {
                    $customer_data = $dt['customer'];
                    $contract_data = $dt['contract'];
                    $is_nt = in_array($contract_data['partner_code'], ['FWD', 'BML']); //  có phải là hđ nhân thọ hay không 
                    $products = $dt['products'];
                    $has_bonus = $dt['perc']['sub'] / max(1, ($dt['perc']['main'] + $dt['perc']['sub'])) >= 0.15;
                    $agent_code = $contract_data['agent_code'];
                    $agent = User::where(['agent_code' => $agent_code])->first();
                    if (!$agent) {
                        $errors[] = 'Agent not found ' . $agent_code . " - " . $partner_contract_code;
                        continue;
                    }
                    if (!isset($agent_list[$agent_code])) {                        
                        $agent_list[$agent_code] = $agent;
                    }
                    // $is_valid_21_days = !is_null($contract_data['ack_date']) && $contract_data['ack_date'] && Carbon::createFromFormat('Y-m-d', $contract_data['ack_date'])->addDay(21) < Carbon::now();

                    $customer = Customer::where([
                        'identity_num' => $customer_data['identity_num'],
                        'fullname' => $customer_data['fullname'],
                        'day_of_birth' => $customer_data['day_of_birth'],
                        'mobile_phone' => $customer_data['mobile_phone'],
                        'email' => $customer_data['email'],
                    ])->first();
                    if (!$customer) {
                        $customer = Customer::create($customer_data);
                    }
                    $contract = Contract::where(['partner_contract_code' => $partner_contract_code])->first();
                    $contract_data['customer_id'] = $customer->id;
                    if (!$contract) {
                        $contract = Contract::create($contract_data);
                    } else {
                        $contract->update($contract_data);
                    }
                    $product_list = array_keys($products);
                    foreach ($products as $product_code => $product_data) {
                        $contract_product = ContractProduct::where([
                            'contract_id' => $contract->id,
                            'product_code' => $product_code,
                            // 'confirmation' => $product_data['confirmation']
                        ])->first();
                        if (!$contract_product) {
                            $contract_product = ContractProduct::create([
                                'contract_id' => $contract->id,
                                'product_code' => $product_code,
                                // 'confirmation' => $product_data['confirmation'],
                                'premium' => $product_data['premium'],
                                'premium_term' => $product_data['premium_term'],
                                'term_code' => $contract_data['term_code'],
                            ]);
                        } else {
                            $contract_product->update([
                                // 'confirmation' => $product_data['confirmation'],
                                'premium' => $product_data['premium'],
                                'premium_term' => $product_data['premium_term'],
                                'term_code' => $contract_data['term_code'],
                            ]);
                        }

                        $sum_exists_transactions = $contract_product->transactions()->selectRaw('sum(premium_received) as premium_received')->first();
                        if($sum_exists_transactions) $sum_exists_transactions = $sum_exists_transactions->premium_received;
                        $new_sum_transactions = array_reduce(array_map(function ($a) {
                            return $a['premium_received'];
                        }, $product_data['transactions']), function ($a, $b) {
                            return $a + $b;
                        });
                        if ($new_sum_transactions != $sum_exists_transactions) {
                            $product_data['transactions'][0]['premium_received'] = $new_sum_transactions - $sum_exists_transactions;
                            foreach ($product_data['transactions'] as $transaction_data) {
                                // dd($transaction_data);exit;
                                $transaction_data['contract_product_id'] = $contract_product->id;
                                $transaction_data['contract_id'] = $contract->id;
                                $transaction_data['agent_code'] = $agent_code;
                                $transaction_data['product_code'] = $product_code;
                                $final[] = $transaction_data;
                                $transaction = Transaction::create($transaction_data);
                                $success[] =  "New transaction id " . $transaction->id . "\r\n";
                                // print_r($transaction);continue;
                                $month = Carbon::createFromFormat('Y-m-d', $transaction_data['trans_date'])->startOfMonth()->format('Y-m-d');
                                // $month_release = Carbon::createFromFormat('Y-m-d', $contract_data['release_date'])->startOfMonth()->format('Y-m-d');
                                if (!isset($month_list[$month])) $month_list[$month] = [];
                                if (!in_array($agent_code, $month_list[$month])) $month_list[$month][] = $agent_code;
                                // if($is_valid_21_days) {
                                //     if (!isset($month_list[$month_release])) $month_list[$month_release] = [];
                                //     if (!in_array($agent_code, $month_list[$month_release])) $month_list[$month_release][] = $agent_code;
                                // }

                                // Tạo comission để tính vào FYC trong trường hợp k phải là hợp đồng nhân thọ bán bởi cấp quản lý và điều hành
                                // echo $agent->designation_code; exit;
                                
                                $data_calc = [
                                    'product_code' => $product_code,
                                    'contract_year' => $contract->contract_year,
                                    'premium' => $transaction_data['premium_received'],
                                    'is_bonus' => $has_bonus,
                                    'factor_rank' => $product_data['premium_factor_rank'],
                                    'APE' => $product_data['premium'],
                                    'customer_type' => $customer_data['type'],
                                    'product_list' => $product_list,
                                    'main_code' => $dt['perc']['main_code']
                                ];
                                $comission_data = [
                                    'transaction_id' => $transaction->id,
                                    'contract_id' => $contract->id,
                                    'agent_code' => $agent_code,
                                    'amount' => Util::calc_comission($request->partner_code, $data_calc),
                                    'received_date' => $transaction_data['trans_date']
                                ];

                                if ($is_nt && $agent->designation_code != 'AG') {
                                    $comission_data['is_raw'] = true;
                                } else $comission_data['is_raw'] = false;
                                $comission = Comission::create($comission_data);
                            }
                            $contract_product->premium_received = $contract_product->transactions()
                                ->selectRaw("sum(premium_received) as premium_received")
                                ->first()
                                ->premium_received;

                            $contract_product->renewal_premium_received = $contract_product->transactions()
                                ->where(['is_renewal' => true])
                                ->selectRaw("sum(premium_received) as premium_received")
                                ->first()
                                ->premium_received;

                            if (!$contract_product->renewal_premium_received) $contract_product->renewal_premium_received = 0;
                            $contract_product->comission = 0;
                            foreach ($contract_product->transactions as $transaction) {
                                $comission = $transaction->comission;
                                if (!$comission) continue;
                                $com = $comission->amount;
                                if ($com) $contract_product->comission += $com;
                            }
                            if ($contract->partner_code == 'VBI') {
                                $contract_product->premium = $contract_product->premium_received;
                                $contract_product->premium_term = $contract_product->premium_received;
                            }
                            $contract_product->save();
                        } else if ($product_data['premium'] != $contract_product->premium || $product_data['premium_term'] != $contract_product->premium_term) {
                            $contract_product->update([
                                'premium' => $product_data['premium'],
                                'premium_term' => $product_data['premium_term']
                            ]);
                            $success[] =  "Contract product updated " . $contract_product->id . "\r\n";;
                        }
                    }
                    $success[] =  $partner_contract_code . "\r\n";
                } catch (Exception $e) {
                    $errors[] = $partner_contract_code . " FAILED:" . $e->getMessage() . "\r\n";
                }
            }
            // foreach ($agent_list as $agent_code => $agent) {
            //     try {
            //         foreach ($month_list as $month => $agents) {
            //             if (in_array($agent_code, $agents)) {
            //                 $calc = new ComissionCalculatorController();
            //                 $calc->updateThisMonthAllStructure($agent, $month);
            //             }
            //         }

            //         $success[] =  "updated agent" . $agent_code . "\r\n";
            //     } catch (Exception $e) {
            //         $errors[] = "failed updating agent" . $agent_code . " FAILED:" . $e->getMessage() . "\r\n";
            //     }
            // }


            // dd($final); exit;

            if (count($errors)) {
                // return back()->with('error',json_encode($final));
                return back()->with('error', "SUCCESS " . json_encode($success) . "\r\n===============\r\nERROR " . json_encode($errors));
            } else {
                return back()->with('success', 'Thêm mới hợp đồng thành công' . json_encode($success));
            }
        }
        // return back();
    }

    public function listCustomers()
    {
        $customers = Customer::orderBy('created_at', 'desc');
        if (request()->has('search')) {
            $str = trim(strtolower(request('search')), ' ');
            $customers = $customers->where('fullname', 'LIKE', '%' . $str . '%')
                ->orwhere('identity_num', 'LIKE', '%' . $str . '%')
                ->orWhere('email', 'LIKE', '%' . $str . '%')
                ->orWhere('id', 'LIKE', '%' . $str . '%');
        }
        $customers = $customers->paginate(25);
        foreach ($customers as $customer) {
            $this->parseCustomerDetail($customer);
        }
        return view('customer.list', ['customers' => $customers]);
    }

    private function parseCustomerDetail($customer)
    {
        $customer->type_text = $customer->type == 1 ? 'Cá nhân' : 'Doanh nghiệp';
        $customer->contract_count = $customer->contracts()->count();
    }

    public function listCustomerContracts(Request $request, $customer_id)
    {
        $customer = Customer::find($customer_id);
        if (!$customer) return back()->with('error', 'Không tìm thấy khách hàng');
        $contracts = $customer->contracts;
        if ($contracts && count($contracts)) {
            foreach ($contracts as $contract) {
                $this->parseContractDetail($contract);
            }
        }
        return view('customer.list_contract', ['contracts' => $contracts, 'customer' => $customer]);
    }

    public function getCustomer(Request $request, $customer_id)
    {
        $customer = Customer::find($customer_id)->first();
        if (!$customer) {
            return back()->with('error', 'Không tìm thấy khách hàng!');
        }
        $this->parseCustomerDetail($customer);

        return view('customer.detail', compact('customer'));
    }

    public function getCustomerRaw(Request $request, $id)
    {
        $customer = Customer::where(['id' => $id])->first();
        // ->with('beneficiaries')
        if ($customer) {
        }
        return $customer;
    }

    public function exportUsersStructure()
    {
        $start_agent_code = '000003';
        $agent = User::where(['agent_code' => $start_agent_code])->first();
        $structure = [];
        $structure[$agent->designation_code . " - " . $agent->fullname . " - TNDA" . $agent->agent_code] = getStructure($structure, $agent);
        // echo '<pre>';
        // print_r();
        $users_with_no_super = User::where(['supervisor_code' => null])->get();
        foreach ($users_with_no_super as $user) {
            $structure_x = [];
            $structure[$user->designation_code . " - " . $user->fullname . " - TNDA" . $user->agent_code] = getStructure($structure_x, $user);
        }

        return view('user.structure', ['data' => json_encode($structure)]);
    }

    public function listNewss()
    {
        $newss = AppNews::orderBy('created_at', 'desc');
        if (request()->has('search')) {
            $str = trim(strtolower(request('search')), ' ');
            $newss = $newss->where('title', 'LIKE', '%' . $str . '%')
                ->orwhere('lead', 'LIKE', '%' . $str . '%')
                ->orWhere('content', 'LIKE', '%' . $str . '%');
        }
        $newss = $newss->paginate(25);
        foreach ($newss as $news) {
            $this->parseNewsDetail($news);
        }
        return view('news.list', ['newss' => $newss]);
    }

    private function parseNewsDetail($news)
    {
        $news->type_text = $news->type == 1 ? 'Bài viết kèm link' : 'Tin ngắn';
        $news->status_text = $news->type == 1 ? 'Hiện' : 'Ẩn';
    }

    public function getNews(Request $request, $news_id)
    {
        $news = AppNews::find($news_id);
        if (!$news) {
            return back()->with('error', 'Không tìm thấy tin tức!');
        }
        $this->parseNewsDetail($news);

        return view('news.detail', compact('news'));
    }

    public function createNews(Request $requset)
    {
        return view('news.add');
    }

    public function storeNews(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'type' => 'required',
            'status' => 'required',
            'public_at' => 'required'
        ]);
        $input = $request->input();

        try {
            $new_post = AppNews::create($input);
        } catch (Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại!');
        }

        return redirect('admin/news/' . $new_post->id)->with('success', 'Thêm bài viết thành công');
    }

    public function editNews($news_id)
    {
        $news = AppNews::find($news_id);
        if ($news) {
            return view('news.edit', compact('news'));
        } else {
            return redirect('admin/app-news')->with('error', 'Không tìm thấy bài viết.');
        }
    }

    public function updateNews(Request $request, $news_id)
    {
        $news = AppNews::find($news_id);
        if (!$news) {
            return redirect('admin/app-news')->with('error', 'Không tìm thấy bài viết.');
        }
        $input = $request->input();
        $newsUpdate = $news->update($input);
        if ($newsUpdate) {
            return back()->with('success', 'Cập nhật thông tin bài viết thành công.');
        } else {
            return back()->with('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
        }
    }

    public function listTransactions($contract_product_id = null)
    {
        $transactions = Transaction::orderBy('created_at', 'desc');
        if (!is_null($contract_product_id)) {
            $transactions = $transactions->where(['contract_product_id' => $contract_product_id]);
        }
        if (request()->has('search')) {
            $str = trim(strtolower(request('search')), ' ');
            $transactions = $transactions->where('agent_code', 'LIKE', '%' . $str . '%')
                ->orwhere('contract_id', 'LIKE', '%' . $str . '%');
        }
        $transactions = $transactions->paginate(25);
        foreach ($transactions as $transaction) {
            $this->parseTransactionsDetail($transaction);
        }
        return view('transaction.list', ['transactions' => $transactions]);
    }

    private function parseTransactionsDetail($transaction)
    {
        $list_product_code = Util::get_product_code();
        $product_code = $transaction->contract_product->product_code;
        $transaction->product_text = isset($list_product_code[$product_code]) ? $list_product_code[$product_code] : '';
        $agent = $transaction->agent;
        $transaction->agent_text = $agent ? $agent->fullname : '';
        $transaction->renewal_text = $transaction->is_renewal ? 'Tái tục' : 'Năm nhất';
        $transaction->comission_amount = $transaction->comission->amount;
    }

    public function getTransaction(Request $request, $news_id)
    {
        $transaction = Transaction::find($news_id);
        if (!$transaction) {
            return back()->with('error', 'Không tìm thấy giao dịch!');
        }
        $this->parseTransactionsDetail($transaction);

        return view('transaction.detail', compact('transaction'));
    }

    public function listMetrics($agent_code = null)
    {
        $metrics = MonthlyMetric::orderBy('created_at', 'desc');
        if (!is_null($agent_code)) {
            $metrics = $metrics->where(['agent_code' => $agent_code]);
        }
        if (request()->has('search')) {
            $str = trim(strtolower(request('search')), ' ');
            $metrics = $metrics->where('agent_code', 'LIKE', '%' . $str . '%')
                ->orwhere('month', 'LIKE', '%' . $str . '%');
        }
        $metrics = $metrics->paginate(25);
        foreach ($metrics as $metric) {
            $this->parseMetricsDetail($metric);
        }
        return view('metric.list', ['metrics' => $metrics]);
    }

    private function parseMetricsDetail($metric)
    {
        $metric->agent_text = $metric->agent->fullname;
    }

    public function getMetric(Request $request, $metric_id)
    {
        $metric = MonthlyMetric::find($metric_id);
        if (!$metric) {
            return back()->with('error', 'Không tìm thấy chỉ số!');
        }
        $this->parseMetricsDetail($metric);

        return view('metric.detail', compact('metric'));
    }

    public function listIncomes($agent_code = null)
    {
        $incomes = MonthlyIncome::orderBy('created_at', 'desc');
        if (!is_null($agent_code)) {
            $incomes = $incomes->where(['agent_code' => $agent_code]);
        }
        if (request()->has('search')) {
            $str = trim(strtolower(request('search')), ' ');
            $incomes = $incomes->where('agent_code', 'LIKE', '%' . $str . '%')
                ->orwhere('month', 'LIKE', '%' . $str . '%');
        }
        $incomes = $incomes->paginate(25);
        foreach ($incomes as $income) {
            $this->parseIncomesDetail($income);
        }
        $list_income_code = Util::get_income_code();
        return view('income.list', compact('incomes', 'list_income_code'));
    }

    private function parseIncomesDetail($income)
    {
        $list_income_code = Util::get_income_code();
        $income->agent_text = $income->agent->fullname;
        $total = 0;
        foreach ($list_income_code as $code => $name) {
            $total += $income->{$code};
        }
        $income->total = $total;
    }

    public function getIncome(Request $request, $income_id)
    {
        $income = MonthlyIncome::find($income_id);
        if (!$income) {
            return back()->with('error', 'Không tìm thấy thu nhập!');
        }
        $this->parseIncomesDetail($income);

        return view('income.detail', compact('income'));
    }

    public function listPromotions($agent_code = null)
    {
        $promotions = PromotionProgress::orderBy('created_at', 'desc');
        if (!is_null($agent_code)) {
            $promotions = $promotions->where(['agent_code' => $agent_code]);
        }
        if (request()->has('search')) {
            $str = trim(strtolower(request('search')), ' ');
            $promotions = $promotions->where('agent_code', 'LIKE', '%' . $str . '%')
                ->orwhere('month', 'LIKE', '%' . $str . '%');
        }
        $promotions = $promotions->paginate(25);
        $list_promotion_code = Util::get_promotions();
        foreach ($promotions as $promotion) {
            $this->parsePromotionsDetail($promotion, $list_promotion_code);
        }
        return view('promotion.list', compact('promotions'));
    }

    private function parsePromotionsDetail($promotion, $list_promotion_code)
    {
        $promotion->agent_text = $promotion->agent->fullname;
        $pro_index = array_search($promotion->pro_code, array_column($list_promotion_code, 'code'));
        if ($pro_index === false) return;
        $promotion_code = $list_promotion_code[$pro_index];
        $promotion->pro_text = $promotion_code["title"];
        $req_index = array_search($promotion->req_id, array_column($promotion_code["requirements"], 'id'));
        if ($req_index !== false) {
            $req = $promotion_code["requirements"][$req_index];
            $promotion->req_text = $req["title"];
            $promotion->requirement_text = $req["requirement_text"];
            $promotion->is_done_text = $promotion->is_done == 1 ? "Hoàn thành" : "Đang tiến hành";
        }
    }

    public function getPromotion(Request $request, $promotion_id)
    {
        $promotion = PromotionProgress::find($promotion_id);
        if (!$promotion) {
            return back()->with('error', 'Không tìm thấy thăng tiến!');
        }
        $list_promotion_code = Util::get_promotions();
        $this->parsePromotionsDetail($promotion, $list_promotion_code);

        return view('promotion.detail', compact('promotion'));
    }

    public function editPromotion($promotion_id)
    {
        $promotion = PromotionProgress::find($promotion_id);
        $list_promotion_code = Util::get_promotions();
        if ($promotion) {
            $this->parsePromotionsDetail($promotion, $list_promotion_code);
            return view('promotion.edit', [
                'promotion' => $promotion
            ]);
        } else {
            return redirect('admin/promotions')->with('error', 'Không tìm thấy thăng tiến.');
        }
    }

    public function updatePromotion(Request $request, $promotion_id)
    {
        $promotion = PromotionProgress::find($promotion_id);
        if (!$promotion) {
            return redirect('admin/promotions')->with('error', 'Không tìm thấy thăng tiến.');
        }
        $input = $request->input();
        $promotionUpdate = $promotion->update($input);
        if ($promotionUpdate) {
            return back()->with('success', 'Cập nhật thông tin thăng tiến thành công.');
        } else {
            return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại.');
        }
    }

    public function listPromotionUps($agent_code = null)
    {
        $promotions = Promotion::orderBy('created_at', 'desc');
        if (!is_null($agent_code)) {
            $promotions = $promotions->where(['agent_code' => $agent_code]);
        }
        if (request()->has('search')) {
            $str = trim(strtolower(request('search')), ' ');
            $promotions = $promotions->where('agent_code', 'LIKE', '%' . $str . '%')
                ->orwhere('valid_month', 'LIKE', '%' . $str . '%');
        }
        $promotions = $promotions->paginate(25);
        $list_designation_code = Util::get_designation_code();
        foreach ($promotions as $promotion) {
            $this->parsePromotionUpsDetail($promotion, $list_designation_code);
        }
        return view('promotion_up.list', compact('promotions'));
    }

    private function parsePromotionUpsDetail($promotion, $list_designation_code)
    {
        $promotion->agent_text = $promotion->agent->fullname;
        $promotion->old_designation_text = $list_designation_code[$promotion->old_designation_code];
        $promotion->new_designation_text = $list_designation_code[$promotion->new_designation_code];
    }

    public function getPromotionUp(Request $request, $promotion_id)
    {
        $promotion = Promotion::find($promotion_id);
        if (!$promotion) {
            return back()->with('error', 'Không tìm thấy thay đổi chức vụ!');
        }
        $list_designation_code = Util::get_designation_code();
        $this->parsePromotionUpsDetail($promotion, $list_designation_code);
        return view('promotion_up.detail', [
            'promotion' => $promotion,
            'list_designation_code' => $list_designation_code
        ]);
    }

    public function editPromotionUp($promotion_id)
    {
        $promotion = Promotion::find($promotion_id);
        $list_designation_code = Util::get_designation_code();
        if ($promotion) {
            $this->parsePromotionUpsDetail($promotion, $list_designation_code);
            return view('promotion_up.edit', [
                'promotion' => $promotion,
                'list_designation_code' => $list_designation_code
            ]);
        } else {
            return redirect('admin/promotion-ups')->with('error', 'Không tìm thấy thay đổi chức vụ.');
        }
    }

    public function updatePromotionUp(Request $request, $promotion_id)
    {
        $promotion = Promotion::find($promotion_id);
        if (!$promotion) {
            return redirect('admin/promotion-ups')->with('error', 'Không tìm thấy thay đổi chức vụ.');
        }
        $input = $request->input();
        $promotionUpdate = $promotion->update($input);
        if ($promotionUpdate) {
            return back()->with('success', 'Cập nhật thông tin thay đổi chức vụ thành công.');
        } else {
            return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại.');
        }
    }

    public function createPromotionUp(Request $request)
    {
        $list_designation_code = Util::get_designation_code();
        return view('promotion_up.add', compact('list_designation_code'));
    }

    public function storePromotionUp(Request $request)
    {
        $request->validate([
            'old_designation_code' => 'required',
            'new_designation_code' => 'required',
            'valid_month' => 'required',
        ]);
        $input = $request->input();
        $input["valid_month"] = Carbon::createFromFormat('Y-m-d', $input["valid_month"])->startOfMonth()->format('Y-m-d');

        try {
            $new_promotion = Promotion::create($input);
        } catch (Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại!');
        }

        return redirect('admin/promotion-up/' . $new_promotion->id)->with('success', 'Thêm thay đổi chức vụ thành công');
    }

    public function calculator()
    {

        return view('calculator.index', ['month_calc' => Carbon::now()->subMonthsNoOverflow(1)->format('Y-m')]);
    }

    public function calc(Request $request)
    {
        $request->validate([
            'agent_code' => 'required',
            'month' => 'required'
        ]);
        $input = $request->input();
        $agent = User::where(['agent_code' => $input['agent_code']])->first();
        $month = Carbon::createFromFormat('Y-m-d', $input["month"])->startOfMonth()->format('Y-m-d');
        if (!$agent) return back()->with('error', 'Không tìm thấy thành viên!');
        $calc = new ComissionCalculatorController();
        $calc->updateThisMonthAllStructure($agent, $month);
        return back()->with('success', 'Cập nhật thành công!');
    }

    public function test()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Hello World !');

        $writer = new Xlsx($spreadsheet);
        $writer->save('hello world.xlsx');
    }

    public function exportIncome(Request $request)
    {
        set_time_limit(600);
        ini_set('memory_limit', '4095M'); // 4 GBs minus 1 MB
        $month = trim($request->month);
        $input_file = "report_template/income.xlsx";
        $spreadsheet = IOFactory::load($input_file);
        // $designation_code = Util::get_designation_code();
        // calc_date
        // tính toán lại cho các hợp đồng có calc_status = 0 (chưa chốt để thanh toán)
        // $calc_date = Carbon::createFromFormat('Y-m-d', $month  . "-01")->endOfMonth()->format('Y-m-d');
        // $valid_ack_date = Carbon::createFromFormat('Y-m-d', $calc_date)->subDay(21)->format('Y-m-d');
        // $contracts = Contract::where(['ack_date' > $valid_ack_date, 'calc_status' => 0])->get();
        // AG-DM+
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue("B3", "Tháng " . $month);

        $incomes = MonthlyIncome::where(['valid_month' => $month . "-01"]);
        // ->whereHas('agent', function ($q) {
        //     $q->whereIn('designation_code', ['AG', 'DM', 'SDM', 'AM']);
        // });
        $selectStr = 'agent_code, ';
        $income_code = Util::get_income_code();
        foreach ($income_code as $field => $name) {
            $selectStr .= 'sum(' . $field . ') as ' . $field . ',';
        }
        $selectStr = substr($selectStr, 0, -1);
        $incomes = $incomes
            ->groupBy('agent_code')
            ->selectRaw($selectStr)
            ->orderBy('agent_code')
            ->get();

        $styleArray = array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        );

        $styleBold = array(
            'font' => [
                'bold' => true,
            ]
        );

        $i = 7;
        foreach ($incomes as $income) {
            $agent = $income->agent;
            $metric = $agent->monthlyMetrics()->select('FYC')->where(['month' => $month . "-01"])->first();
            $sheet->setCellValue("B" . $i, $agent->IFA_TD_name);
            // $sheet->setCellValue("C".$i, $income->TD_code);
            $sheet->setCellValue("E" . $i, $agent->fullname);
            $sheet->setCellValue("F" . $i, $agent->agent_code);
            $sheet->setCellValue("G" . $i, $agent->designation_code);
            $sheet->setCellValue("J" . $i, isset($metric->FYC) ? $metric->FYC : "?");
            $sheet->setCellValue("K" . $i, $income->ag_rwd_hldlth);
            $sheet->setCellValue("L" . $i, $income->ag_hh_bhcn);
            $sheet->setCellValue("M" . $i, $income->ag_rwd_dscnhq);
            $sheet->setCellValue("N" . $i, $income->ag_rwd_tndl);
            $sheet->setCellValue("O" . $i, $income->ag_rwd_tcldt_dm);
            $sheet->setCellValue("P" . $i, $income->dm_rwd_hldlm);
            $sheet->setCellValue("Q" . $i, $income->dm_rwd_dscnht);
            $sheet->setCellValue("R" . $i, $income->dm_rwd_qlhtthhptt);
            $sheet->setCellValue("S" . $i, $income->dm_rwd_qlhqthhptt);
            $sheet->setCellValue("T" . $i, $income->dm_rwd_tnql);
            $sheet->setCellValue("U" . $i, $income->dm_rwd_ptptt);
            $sheet->setCellValue("V" . $i, $income->dm_rwd_gt);
            $sheet->setCellValue("W" . $i, $income->dm_rwd_tcldt_sdm);
            $sheet->setCellValue("X" . $i, $income->dm_rwd_tcldt_am);
            $sheet->setCellValue("Y" . $i, $income->dm_rwd_tcldt_rd);
            $sheet->setCellValue("Z" . $i, $income->dm_rwd_dthdtptt);
            $sheet->setCellValue("AA" . $i, $income->rd_rwd_dscnht);
            $sheet->setCellValue("AB" . $i, $income->rd_hh_nsht);
            $sheet->setCellValue("AC" . $i, $income->rd_rwd_dctkdq);
            $sheet->setCellValue("AD" . $i, $income->rd_rwd_tndhkd);
            $sheet->setCellValue("AE" . $i, $income->rd_rwd_dbgdmht);
            $sheet->setCellValue("AF" . $i, $income->rd_rwd_tcldt_srd);
            $sheet->setCellValue("AG" . $i, $income->rd_rwd_tcldt_td);
            $sheet->setCellValue("AH" . $i, $income->rd_rwd_dthdtvtt);
            $i++;
        }

        $sheet->getStyle('A7:AH' . ($i))->applyFromArray($styleArray);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); //Tell the browser to output 07Excel file
        //header(‘Content-Type:application/vnd.ms-excel’);//Tell the browser to output the Excel03 version file
        header('Content-Disposition: attachment;filename="income_export_' . $month . '.xlsx"'); //Tell the browser to output the browser name
        header('Cache-Control: max-age=0'); //Disable caching
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    }

    public function exportMetric(Request $request)
    {
        set_time_limit(600);
        ini_set('memory_limit', '4095M'); // 4 GBs minus 1 MB

        $input_file = "report_template/metric.xlsx";

        $spreadsheet = IOFactory::load($input_file);
        $year = trim($request->year);
        $styleArray = array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        );
        $styleBold = array(
            'font' => [
                'bold' => true,
            ]
        );

        $from = Carbon::createFromFormat('Y-m-d H:s:i', $year . '-01-01 00:00:00');
        $to = Carbon::now();
        $month_back = $to->diffInMonths($from);

        // AGENT
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();
        $date_report = date('d-m-Y');
        $sheet->setCellValue("B2", $date_report);

        $agents = User::select('agent_code', 'fullname', 'supervisor_code', 'IFA_supervisor_name', 'alloc_code_date', 'reference_code', 'IFA_ref_name', 'designation_code')
            // ->limit(10)
            ->get();
        $com = new ComissionCalculatorController();
        foreach ($agents as $agent) {
            $super_list = Util::get_all_super_info($agent);
            $agent->super_info = $super_list;
            $metrics = [
                'total' => [
                    'FYP' => 0,
                    'FYC' => 0,
                    'APE' => 0,
                    'CC' => 0,
                ]

            ];
            for ($m = 0; $m < 12; $m++) {
                // $month = Carbon::now()->subMonthsNoOverflow($month_back - $m)->startOfMonth()->format('Y-m-d');
                $metrics[$m] = [
                    'FYP' => $com->getFYP_all($agent, $month_back - $m, 1),
                    'FYC' => $com->getFYC_all($agent, $month_back - $m, 1),
                    'APE' => $com->getAPE_all($agent, $month_back - $m, 1),
                    'CC' => $com->getCC($agent, $month_back - $m, 1)
                ];
                $metrics['total']['FYP'] += $metrics[$m]['FYP'];
                $metrics['total']['FYC'] += $metrics[$m]['FYC'];
                $metrics['total']['APE'] += $metrics[$m]['APE'];
                $metrics['total']['CC'] += $metrics[$m]['CC'];
            }
            $agent->metric = $metrics;
        }

        $i = 5;
        $max_col = 0;
        foreach ($agents as $agent) {
            $super_info = $agent->super_info;
            $sheet->setCellValue("C" . $i, isset($super_info['TD']) ? "TNDA" . $super_info['TD']['agent_code'] : '');
            $sheet->setCellValue("D" . $i, isset($super_info['TD']) ? $super_info['TD']['fullname'] : $agent->IFA_TD_name);
            $sheet->setCellValue("E" . $i, isset($super_info['SRD']) ? $super_info['SRD']['fullname'] : '');
            $sheet->setCellValue("F" . $i, isset($super_info['RD']) ? $super_info['RD']['fullname'] : '');
            $sheet->setCellValue("H" . $i, $agent->IFA_supervisor_name);
            $sheet->setCellValue("J" . $i, isset($super_info['DM']) ? $super_info['DM']['fullname'] : '');
            $sheet->setCellValue("K" . $i, isset($super_info['SDM']) ? $super_info['SDM']['fullname'] : '');
            $sheet->setCellValue("L" . $i, "TNDA" . $agent->reference_code);
            $sheet->setCellValue("M" . $i, $agent->IFA_ref_name);
            $sheet->setCellValue("N" . $i, "TNDA" . $agent->agent_code);
            $sheet->setCellValue("O" . $i, $agent->fullname);
            $sheet->setCellValue("P" . $i, $agent->alloc_code_date);
            $sheet->setCellValue("Q" . $i, $agent->designation_code);

            $col = 18;
            for ($m = 0; $m <= 12; $m++) {
                if ($m < 12) $metric = $agent->metric[$m];
                else $metric = $agent->metric['total'];

                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $i, $metric['FYP']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $i, $metric['APE']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 2) . $i, $metric['FYC']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 3) . $i, $metric['CC']);
                $col += 4;
                if ($col > $max_col) $max_col = $col;
            }
            $i++;
        }

        $sheet->getStyle('A5:' . Coordinate::stringFromColumnIndex($max_col - 1) . ($i - 1))->applyFromArray($styleArray);

        // LEADER
        $spreadsheet->setActiveSheetIndex(1);
        $sheet = $spreadsheet->getActiveSheet();
        $date_report = date('d-m-Y');
        $sheet->setCellValue("B2", $date_report);

        $agents = User::where([['designation_code', '<>', 'AG'], ['agent_code', '<>', '000000']])
            ->select('agent_code', 'fullname', 'supervisor_code', 'IFA_supervisor_name', 'alloc_code_date', 'reference_code', 'IFA_ref_name', 'designation_code')
            // ->limit(10)
            ->get();
        $com = new ComissionCalculatorController();
        // echo "<pre>";
        foreach ($agents as $agent) {
            // echo "\n" . $agent->agent_code;
            $super_list = Util::get_all_super_info($agent);
            $agent->super_info = $super_list;
            $metrics = [
                'total' => [
                    'FYP_dr' => 0,
                    'FYP_tm' => 0,
                    'FYC_dr' => 0,
                    'FYC_tm' => 0,
                    'APE_dr' => 0,
                    'APE_tm' => 0,
                    'AAU' => 0,
                    'HC' => 0,
                    'AHC' => 0
                ]
            ];

            for ($m = 0; $m < 12; $m++) {
                $teamAGCodes = $com->getWholeTeamCodes($agent, true);
                $teamCodes = $com->getWholeTeamCodes($agent);
               
                $metrics[$m] = [
                    'FYP_dr' => $com->getTotalFYPAllByCodes($teamAGCodes, $month_back - $m, 1),
                    'FYP_tm' => $com->getTotalFYPAllByCodes($teamCodes, $month_back - $m, 1),
                    'FYC_dr' => $com->getTotalFYCAllByCodes($teamAGCodes, $month_back - $m, 1),
                    'FYC_tm' => $com->getTotalFYCAllByCodes($teamCodes, $month_back - $m, 1),
                    'APE_dr' => $com->getTotalAPEAllByCodes($teamAGCodes, $month_back - $m, 1),
                    'APE_tm' => $com->getTotalAPEAllByCodes($teamCodes, $month_back - $m, 1),
                    'AAU' => $com->getAAU($agent, $month_back - $m),
                    'AU' => $com->getAU($agent, $month_back - $m),
                    'U' => $com->getU($agent, $month_back - $m),
                ];
                $metrics[$m]['U2'] = $metrics[$m]['U'] - $metrics[$m]['AU'];
                $metrics['total']['FYP_dr'] += $metrics[$m]['FYP_dr'];
                $metrics['total']['FYP_tm'] += $metrics[$m]['FYP_tm'];
                $metrics['total']['FYC_dr'] += $metrics[$m]['FYC_dr'];
                $metrics['total']['FYC_tm'] += $metrics[$m]['FYC_tm'];
                $metrics['total']['APE_dr'] += $metrics[$m]['APE_dr'];
                $metrics['total']['APE_dr'] += $metrics[$m]['APE_dr'];
                $metrics['total']['AAU'] = $metrics[$m]['AAU'];
                $metrics['total']['AU'] = $metrics[$m]['AU'];
                $metrics['total']['U2'] = $metrics[$m]['U2'];
            }
            $agent->metric = $metrics;
        }
        // dd($agents[8]);
        // exit;
        $i = 6;
        $max_col = 0;
        foreach ($agents as $agent) {
            $super_info = $agent->super_info;
            $sheet->setCellValue("C" . $i, isset($super_info['TD']) ? "TNDA" . $super_info['TD']['agent_code'] : '');
            $sheet->setCellValue("D" . $i, isset($super_info['TD']) ? $super_info['TD']['fullname'] : $agent->IFA_TD_name);
            $sheet->setCellValue("E" . $i, isset($super_info['SRD']) ? $super_info['SRD']['fullname'] : '');
            $sheet->setCellValue("F" . $i, isset($super_info['RD']) ? $super_info['RD']['fullname'] : '');
            // $sheet->setCellValue("H" . $i, $agent->IFA_supervisor_name);
            $sheet->setCellValue("I" . $i, isset($super_info['DM']) ? $super_info['DM']['fullname'] : '');
            // $sheet->setCellValue("K" . $i, isset($super_info['SDM']) ? $super_info['SDM']['fullname'] : '');
            // $sheet->setCellValue("K" . $i, "TNDA" . $agent->reference_code);
            // $sheet->setCellValue("L" . $i, $agent->IFA_ref_name);
            $sheet->setCellValue("K" . $i, "TNDA" . $agent->agent_code);
            $sheet->setCellValue("L" . $i, $agent->fullname);
            $sheet->setCellValue("M" . $i, $agent->alloc_code_date);
            $sheet->setCellValue("N" . $i, $agent->designation_code);

            $col = 14;
            for ($m = 0; $m <= 12; $m++) {
                if ($m < 12) $metric = $agent->metric[$m];
                else $metric = $agent->metric['total'];

                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $i, $metric['FYP_dr']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $i, $metric['FYP_tm']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 2) . $i, $metric['APE_dr']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 3) . $i, $metric['APE_tm']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 4) . $i, $metric['FYC_dr']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 5) . $i, $metric['FYC_tm']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 6) . $i, $metric['AAU']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 7) . $i, $metric['AU']);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 8) . $i, $metric['U2']);
                $col += 9;
                if ($col > $max_col) $max_col = $col;
            }
            $i++;
        }

        $sheet->getStyle('A6:' . Coordinate::stringFromColumnIndex($max_col - 1) . ($i - 1))->applyFromArray($styleArray);

        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); //Tell the browser to output 07Excel file
        //header(‘Content-Type:application/vnd.ms-excel’);//Tell the browser to output the Excel03 version file
        header('Content-Disposition: attachment;filename="metric_export_' . $year . '.xlsx"'); //Tell the browser to output the browser name
        header('Cache-Control: max-age=0'); //Disable caching
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        // $writer->save('report_template/metric_export.xlsx');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    }

    public function export21DayContract(Request $request)
    {
        if(!isset($request->month)) {
            return back()->with('error', 'Thiếu tháng!');
        }
        $month = trim($request->month);
        $from = $month . '-01';
        $to = Carbon::createFromFormat('Y-m-d', $from)->endOfMonth()->format('Y-m-d');
        $last_month_valid_ack = Carbon::createFromFormat('Y-m-d', $to)->subMonthsNoOverflow(1)->subDay(21);
        if($to == '2022-01-31') $to = '2022-01-25';
        $valid_ack_date = Carbon::createFromFormat('Y-m-d', $to)->subDay(21);        
        if($from == '2022-02-01') $from = '2022-01-26';

        // echo "last_month_valid_ack " . $last_month_valid_ack;
        // echo " valid_ack_date " . $valid_ack_date;
        // exit;
        $input_file = "report_template/contract.xlsx";
        $spreadsheet = IOFactory::load($input_file);
        
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue("A2", $from . " - " . $to);

        $contracts = Contract::where(function($q1) use ($from, $to) {
            $q1->whereIn('partner_code', ['BV', 'VBI'])
                ->where([
                    ['release_date', '>=', $from],
                    ['release_date', '<=', $to]
                ]);
        })->orWhere(function ($q1) use ($valid_ack_date, $last_month_valid_ack) {
            $q1->whereIn('partner_code', ['FWD', 'BML'])
                ->whereNotNull('ack_date')
                ->where([['ack_date', '<', $valid_ack_date], ['ack_date', '>', $last_month_valid_ack]]);
        })->get();
        foreach($contracts as $contract) {
            $this->parseContractDetail($contract);
        }
        // dd($contracts);exit;
        
        $styleArray = array(
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        );

        $styleBold = array(
            'font' => [
                'bold' => true,
            ]
        );

        $i = 5;
        foreach ($contracts as $k => $contract) {
            $sheet->setCellValue("A" . $i, ($k+1));
            $sheet->setCellValue("B" . $i, $contract->agent_code);
            $sheet->setCellValue("C" . $i, $contract->agent_name);
            $sheet->setCellValue("D" . $i, $contract->partner_code);
            $sheet->setCellValue("E" . $i, $contract->partner_contract_code);
            $sheet->setCellValue("F" . $i, $contract->product_text);
            $sheet->setCellValue("G" . $i, $contract->customer_name);
            $sheet->setCellValue("H" . $i, $contract->premium_term);
            $sheet->setCellValue("I" . $i, $contract->premium_received);
            $sheet->setCellValue("J" . $i, $contract->premium);
            $sheet->setCellValue("K" . $i, $contract->submit_date);
            $sheet->setCellValue("L" . $i, $contract->release_date);
            $sheet->setCellValue("M" . $i, $contract->ack_date);
            $i++;
        }

        $sheet->getStyle('A5:M' . ($i))->applyFromArray($styleArray);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); //Tell the browser to output 07Excel file
        //header(‘Content-Type:application/vnd.ms-excel’);//Tell the browser to output the Excel03 version file
        header('Content-Disposition: attachment;filename="contract_export_' . $month . '.xlsx"'); //Tell the browser to output the browser name
        header('Cache-Control: max-age=0'); //Disable caching
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    }
}
function getStructure($structure, $agent)
{
    $unders = $agent->directUnders;
    $structure[$agent->designation_code . " - " . $agent->fullname . " - TNDA" . $agent->agent_code] = '.';
    if (count($unders)) {
        $structure[$agent->designation_code . " - " . $agent->fullname . " - TNDA" . $agent->agent_code] = [];
        foreach ($unders as $under) {
            $structure[$agent->designation_code . " - " . $agent->fullname . " - TNDA" . $agent->agent_code][$under->designation_code . " - " . $under->fullname . " - TNDA" . $under->agent_code] = getStructure($structure, $under);
        }
    }
    return $structure[$agent->designation_code . " - " . $agent->fullname . " - TNDA" . $agent->agent_code];
};
