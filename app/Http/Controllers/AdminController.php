<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        $contract->term_text = $list_contract_term_code[$contract->term_code];
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
            dd($import->data);
            exit;
            $agent_list = [];
            $month_list = [];
            // echo "<pre>";

            foreach ($import->data as $partner_contract_code => $dt) {
                try {
                    $customer_data = $dt['customer'];
                    // print_r($customer_data);continue;
                    $contract_data = $dt['contract'];
                    $products = $dt['products'];
                    $agent_code = $contract_data['agent_code'];
                    $agent = User::where(['agent_code' => $agent_code])->first();
                    if (!$agent) {
                        throw new Exception('Agent not found');
                    }
                    if (!isset($agent_list[$agent_code])) $agent_list[$agent_code] = $agent;
                    $customer = Customer::where([
                        'identity_num' => $customer_data['identity_num'],
                        'fullname' => $customer_data['fullname'],
                        'day_of_birth' => $customer_data['day_of_birth'],
                        'mobile_phone' => $customer_data['mobile_phone'],
                        'email' => $customer_data['email'],
                    ])->first();
                    $contract = Contract::where(['partner_contract_code' => $partner_contract_code])->first();
                    if (!$customer) {
                        $customer = Customer::create($customer_data);
                    }
                    if (!$contract) {
                        $contract_data['customer_id'] = $customer->id;
                        $contract = Contract::create($contract_data);
                    }
                    foreach ($products as $product_code => $product_data) {
                        $contract_product = ContractProduct::where([
                            'contract_id' => $contract->id,
                            'product_code' => $product_code
                        ])->first();
                        if (!$contract_product) {
                            $contract_product = ContractProduct::create([
                                'contract_id' => $contract->id,
                                'product_code' => $product_code,
                                'confirmation' => $product_data['confirmation'],
                                'premium' => $product_data['premium'],
                                'premium_term' => $product_data['premium_term'],
                                'term_code' => $contract_data['term_code'],
                            ]);
                        }
                        foreach ($product_data['transactions'] as $transaction_data) {
                            $transaction_data['contract_product_id'] = $contract_product->id;
                            $transaction_data['contract_id'] = $contract->id;
                            $transaction_data['agent_code'] = $agent_code;
                            $transaction_data['product_code'] = $product_code;
                            $final[] = $transaction_data;
                            $transaction = Transaction::create($transaction_data);
                            $comission_data = [
                                'transaction_id' => $transaction->id,
                                'contract_id' => $contract->id,
                                'agent_code' => $agent_code,
                                'amount' => $transaction_data['comission'],
                                'received_date' => $transaction_data['trans_date']
                            ];
                            $comission = Comission::create($comission_data);
                            $month = Carbon::createFromFormat('Y-m-d', $transaction_data['trans_date'])->startOfMonth()->format('Y-m-d');
                            if (!isset($month_list[$month])) $month_list[$month] = [];
                            if (!in_array($agent_code, $month_list[$month])) $month_list[$month][] = $agent_code;
                        }
                        $contract_product->premium_received = $contract_product->transactions()->selectRaw("sum(premium_received) as premium_received")->first()->premium_received;
                        $contract_product->renewal_premium_received = $contract_product->transactions()->where(['is_renewal' => true])->selectRaw("sum(premium_received) as premium_received")->first()->premium_received;
                        if (!$contract_product->renewal_premium_received) $contract_product->renewal_premium_received = 0;
                        $contract_product->comission = 0;
                        foreach ($contract_product->transactions as $transaction) {
                            $com = $transaction->comission->amount;
                            if ($com) $contract_product->comission += $com;
                        }
                        if ($contract->partner_code == 'VBI') {
                            $contract_product->premium = $contract_product->premium_received;
                            $contract_product->premium_term = $contract_product->premium_received;
                        }
                        $contract_product->save();
                    }
                    $success[] =  $partner_contract_code . "\r\n";
                } catch (Exception $e) {
                    $errors[] = $partner_contract_code . " FAILED:" . $e->getMessage() . "\r\n";
                }
            }
            foreach ($agent_list as $agent_code => $agent) {
                try {
                    foreach ($month_list as $month => $agents) {
                        if (in_array($agent_code, $agents)) {
                            $calc = new ComissionCalculatorController();
                            $calc->updateThisMonthAllStructure($agent, $month);
                        }
                    }

                    $success[] =  "updated agent" . $agent_code . "\r\n";
                } catch (Exception $e) {
                    $errors[] = "failed updating agent" . $agent_code . " FAILED:" . $e->getMessage() . "\r\n";
                }
            }


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
        return view('calculator.index');
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
