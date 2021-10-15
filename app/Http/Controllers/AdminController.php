<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Admin;
use Auth;
use App\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Validator;
use Excel;
use App\UsersImport;
use Illuminate\Support\Facades\DB;

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
                    $agent_code = str_pad($highest_agent_code + 1, 6, "0", STR_PAD_LEFT);
                    $user['agent_code'] = $agent_code;
                    $user['username'] = 'TNDA' . $agent_code;
                    User::create($user);
                    $success[] = $user['fullname'] . " " . $user['agent_code']. " " . $user['identity_num'] . "\r\n";
                }
                catch(\Illuminate\Database\QueryException $e){
                    $errors[] = $user['fullname'] . " " . $user['agent_code']. " " . $user['identity_num'] . " FAILED:" .$e->getMessage() . "\r\n";
                }
                // try {
                //     DB::table('users')->insertOrIgnore($import->data);
                // } catch (Exception $e) {
                //     return back()->with('error', $e->getMessages());
                // }
                // return back()->with('success', 'Thêm mới danh sách thành viên thành công');
            }
            if(count($errors)) {
                return back()->with('error', "SUCCESS ". json_encode($success) . "\r\n===============\r\nERROR " . json_encode($errors));
            } else {
                return back()->with('success', 'Thêm mới danh sách thành viên thành công' . json_encode($success));
            }
            // try {
            //     $import = new UsersImport;
            //     Excel::import($import, $request->file('file'));
            //     dd($import->data);
            //     return back()->with('success', 'Thêm mới danh sách thành viên thành công');
            // } catch (\Exception $e) {
            //     return back()->with('error', $e->getMessage());
            // }
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
            // $identity_alloc_date = Carbon::createFromFormat('d/m/Y', $row[8])->format('Y-m-d');
            // $day_of_birth = Carbon::createFromFormat('d-m-Y', $row[3].'-'.$row[4].'-'.$row[5])->format('Y-m-d');
            // $marital_status_code = strtolower($row[13]) == 'kết hôn' ? 'M' : (strtolower($row[13]) == 'độc thân' ? 'S' : (strtolower($row[13]) == 'ly hôn' ? 'D' : ''));
            // $IFA_start_date = $row[18] == '' ? $row[18] : Carbon::createFromFormat('d/m/Y', $row[18])->format('Y-m-d');
            // $designation_code = str_replace(['"', 'TNDA'], '', $row[28]);
            // $data = [
            //     'fullname' => $row[2],
            //     'day_of_birth' => $day_of_birth,
            //     'gender' => strtolower($row[6]) == 'nam' ? 0 : 1,
            //     'identity_num' => $row[7],
            //     'identity_alloc_date' => $identity_alloc_date,
            //     'identity_alloc_place' => $row[9],
            //     'resident_place' => $row[10],
            //     'email' => $row[11],
            //     'mobile_phone' => $row[12],
            //     'marital_status_code' => $marital_status_code,
            //     'IFA_start_date' => $IFA_start_date,
            //     'IFA_branch' => $row[20],
            //     'IFA' => $row[21],
            //     'designation_code' => $designation_code,
            //     'IFA_ref_code' => $row[29],
            //     'IFA_ref_name' => $row[30],
            //     'IFA_supervisor_code' => $row[31],
            //     'IFA_supervisor_name' => $row[32],
            //     'IFA_supervisor_designation_code' => $row[33],
            //     'IFA_TD_code' => $row[34],
            //     'IFA_TD_name' => $row[35],
            // ];
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
        // echo "<pre>";print_r(implode('","', array_keys($user->toArray())));exit;
        $this->parseUserDetail($user);

        return view('user.detail', compact('user'));
    }

    private function parseUserDetail($user)
    {
        $user->gender_text = $user->gender == 0 ? 'Nam' : 'Nữ';
        $user->marital_status_text = (!is_null($user->marital_status_code) && $user->marital_status_code != '') ? (Util::get_marital_status_code())[$user->marital_status_code] : '';
        $ref = $user->reference;
        if ($ref) {
            $user->ref_code = $user->reference_code;
            $user->ref_name = $ref->fullname;
        } else {
            $user->ref_code = $user->IFA_ref_code;
            $user->ref_name = $user->IFA_ref_name;
        }
        $supervisor = $user->supervisor;
        if ($ref) {
            $user->supervisor_code = $user->supervisor_code;
            $user->supervisor_name = $supervisor->fullname;
            $user->supervisor_designation_code = $supervisor->designation_code;
        } else {
            $user->supervisor_code = $user->IFA_supervisor_code;
            $user->supervisor_name = $user->IFA_supervisor_name;
            $user->supervisor_designation_code = $user->IFA_supervisor_designation_code;
        }
        $TD = Util::get_TD($user);
        if ($TD) {
            $user->TD_code = $TD->agent_code;
            $user->TD_name = $TD->fullname;
        } else {
            $user->TD_code = $user->IFA_TD_code;
            $user->TD_name = $user->IFA_TD_name;
        }
    }

    // /**
    //  * Update the specified resource in storage.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function updateFUser(Request $request, $id)
    // {
    //     // $userId = Auth::user()->id;
    // $request->validate([
    //     'type' => 'required',
    // ]);
    //     $user = FUser::find($id);
    //     if (!$user) {
    //         return redirect('admin/users')->with('error', 'User not found.');
    //     }
    //     // echo "<pre>";
    //     $input = $request->input();
    //     $input['status'] = $request->has('status');
    //     // $input['user_id'] = $userId;
    //     if($input['type'] == "active") {
    //         $userStatus = $user->update(['status' => $input['status']]);
    //     }
    //     if ($userStatus) {
    //         return back()->with('success', 'User successfully updated.');
    //     } else {
    //         return back()->with('error', 'Oops something went wrong. User not updated');
    //     }
    // }

    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function listUserGroups(FUser $user)
    // {
    //     $groups = $user->groups;
    //     foreach ($groups as $group) {
    //         $masters = $group->users()->wherePivot('is_master', 1)->get();
    //         $group->masters = "";
    //         foreach ($masters as $master) {
    //             $group->masters .= "<a href='/admin/users?id=" . $master->id . "'><span class='btn btn-" . ($master->id == $user->id ? "success" : "primary") . " ml-2'>" . $master->name . "</span></a>";
    //         }
    //     }
    //     return view('admin.user.group', compact('user', 'groups'));
    // }

    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function createUserGroup(FUser $user)
    // {
    //     $groups = FGroup::orderBy('name')->get();
    //     return view('admin.user.join', compact('user', 'groups'));
    // }

    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function storeUserGroup(Request $request, $id)
    // {
    //     $request->validate([
    //         'group_id' => 'required',
    //     ]);
    //     $input = $request->input();
    //     $input['is_master'] = $request->has('is_master');

    //     $user = FUser::find($id);
    //     if (!$user) {
    //         return redirect('admin/users')->with('error', 'User not found.');
    //     }
    //     $gid = $input['group_id'];
    //     if ($user->groups()->find($gid)) {
    //         return redirect('admin/user/' . $id . '/group')->with('error', 'Group already joined');
    //     }
    //     $group = FGroup::find($gid);
    //     if (!$group) {
    //         return back()->with('error', 'Group not found.');
    //     }
    //     $user->groups()->attach($gid, ['group_id' => $input['group_id'], 'is_master' => $input['is_master']]);
    //     return redirect('admin/user/' . $id . '/group')->with('success', 'Group successfully joined.');
    // }


    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function editUserGroup(FUser $user)
    // {
    // }

    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function updateUserGroup(Request $request, $userId, $groupId)
    // {
    //     $request->validate([
    //         'type' => 'required'
    //     ]);
    //     $input = $request->input();
    //     $type = $input['type'];

    //     $user = FUser::find($userId);
    //     if (!$user) {
    //         return redirect('admin/users')->with('error', 'User not found.');
    //     }
    //     // $gid = $input['group_id'];

    //     $group =  FGroup::find($groupId);
    //     if (!$group) {
    //         return back()->with('error', 'Group not found.');
    //     }

    //     $userGroup = $user->groups()->find($groupId);
    //     if (!$userGroup) {
    //         return back()->with('error', 'User not in group');
    //     }

    //     if ($type == "make_master") {
    //         $user->groups()->updateExistingPivot($groupId, ['is_master' => 1]);
    //         return back()->with('success', 'User successfully made master.');
    //     }

    //     if ($type == "undo_master") {
    //         $user->groups()->updateExistingPivot($groupId, ['is_master' => 0]);
    //         return back()->with('success', 'User successfully removed master.');
    //     }
    //     return back()->with('error', 'Action not found.');
    // }

    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function destroyUserGroup($userId, $groupId)
    // {
    //     $user = FUser::find($userId);
    //     if (!$user) {
    //         return redirect('admin/users')->with('error', 'User not found.');
    //     }
    //     // $gid = $input['group_id'];

    //     $group =  FGroup::find($groupId);
    //     if (!$group) {
    //         return back()->with('error', 'Group not found.');
    //     }

    //     $userGroup = $user->groups()->find($groupId);
    //     if (!$userGroup) {
    //         return back()->with('error', 'User not in group');
    //     }

    //     $respStatus = $respMsg = '';
    //     $groupDelStatus = $user->groups()->detach($userGroup->id);
    //     if ($groupDelStatus) {
    //         $respStatus = 'success';
    //         $respMsg = 'Group kicked user successfully';
    //     } else {
    //         $respStatus = 'error';
    //         $respMsg = 'Oops something went wrong. User not kicked from group successfully';
    //     }
    //     return back()->with($respStatus, $respMsg);
    // }

    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function listUserHistories(FUser $user)
    // {
    //     $histories = $user->histories()->orderBy('created_at', 'desc')->paginate(25);
    //     // echo "<pre>";
    //     foreach ($histories as $history) {
    //         $is_master = $history->master_id == $user->id;
    //         if (!$is_master) {
    //             $master = FUser::find($history->master_id);
    //             if($master) {
    //                 $history->master = $master->only('name')['name'];
    //             }
    //         } else {
    //             $history->master = $user->name;
    //         }
    //         $history->is_master = $is_master;
    //     }
    //     return view('admin.user.history', compact('user', 'histories'));
    // }

    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function listFGroups()
    // {
    //     // $userId = Auth::user()->id;
    //     $groups = FGroup::orderBy('created_at', 'desc');
    //     if (request()->has('id')) {
    //         $id = request('id');
    //         $groups = $groups->where('id', '=', $id);
    //     }
    //     if (request()->has('search')) {
    //         $str = trim(strtolower(request('search')), ' ');
    //         $groups = $groups->where('name', 'LIKE', '%' . $str . '%')
    //             ->orWhere('id', 'LIKE', '%' . $str . '%');
    //     }
    //     $groups = $groups->paginate(15);

    //     foreach ($groups as $group) {
    //         $masters = $group->users()->wherePivot('is_master', 1)->get();
    //         $group->masters = "";
    //         foreach ($masters as $master) {
    //             $group->masters .= "<a href='/admin/users?id=" . $master->id . "'><span class='btn btn-primary ml-2'>" . $master->name . "</span></a>";
    //         }
    //         $group->members = $group->users()->count();
    //     }
    //     return view('admin.group.list', compact('groups'));
    // }

    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function listGroupUsers(FGroup $group)
    // {
    //     $users = $group->users;
    //     // echo "<pre>";
    //     $masters = $group->users()->wherePivot('is_master', 1)->get();

    //     foreach ($users as $user) {
    //         // echo "GROUP ", $group->only('name')['name'], "\r\n";
    //         $is_master = false;
    //         // 
    //         foreach ($masters as $master) {
    //             if ($master->id == $user->id) {
    //                 $is_master = true;
    //                 break;
    //             }
    //         }
    //         $user->is_master = $is_master;
    //     }
    //     return view('admin.group.user', compact('group', 'users'));
    // }

    // /**
    //  * Show the form for creating a new resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function createFGroup()
    // {
    //     return view('admin.group.add');
    // }

    // /**
    //  * Store a newly created resource in storage.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @return \Illuminate\Http\Response
    //  */
    // public function storeFGroup(Request $request)
    // {
    //     // $userId = Auth::user()->id;
    //     $request->validate([
    //         'name' => 'required|unique:fgroups|max:255'
    //     ]);
    //     $input = $request->input();
    //     $group = FGroup::create($input);
    //     if ($group) {
    //         $request->session()->flash('success', 'Group successfully added');
    //     } else {
    //         $request->session()->flash('error', 'Oops something went wrong, Group not saved');
    //     }
    //     return redirect('/admin/groups');
    // }

    // /**
    //  * Display the specified resource.
    //  *
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function showFGroup($id)
    // {
    //     // $userId = Auth::user()->id;
    //     // $fUser = FUser::where(['id' => $id])->first();
    //     // if (!$todo) {
    //     //     return redirect('fuser')->with('error', 'User not found');
    //     // }
    //     // return view('admin.user.view', ['fUser' => $fUser]);
    // }

    // /**
    //  * Show the form for editing the specified resource.
    //  *
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function editFGroup($id)
    // {
    //     // $userId = Auth::user()->id;
    //     $group = FGroup::where(['id' => $id])->first();
    //     if ($group) {
    //         return view('admin.group.edit', ['group' => $group]);
    //     } else {
    //         return redirect('admin/groups')->with('error', 'Group not found');
    //     }
    // }

    // /**
    //  * Update the specified resource in storage.
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function updateFGroup(Request $request, $id)
    // {
    //     // $userId = Auth::user()->id;
    //     $request->validate([
    //         'name' => 'required|unique:fgroups|max:255'
    //     ]);
    //     $group = FGroup::find($id);
    //     if (!$group) {
    //         return redirect('admin/groups')->with('error', 'Group not found.');
    //     }
    //     $input = $request->input();
    //     // $input['user_id'] = $userId;
    //     $groupStatus = $group->update($input);
    //     if ($groupStatus) {
    //         return redirect('admin/groups')->with('success', 'Group successfully updated.');
    //     } else {
    //         return redirect('admin/groups')->with('error', 'Oops something went wrong. Group not updated');
    //     }
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  *
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function destroyFGroup($id)
    // {
    //     // $userId = Auth::user()->id;
    //     $group = FGroup::where(['id' => $id])->first();
    //     $respStatus = $respMsg = '';
    //     if (!$group) {
    //         $respStatus = 'error';
    //         $respMsg = 'Group not found';
    //     }
    //     $groupDelStatus = $group->delete();
    //     if ($groupDelStatus) {
    //         $respStatus = 'success';
    //         $respMsg = 'Group deleted successfully';
    //     } else {
    //         $respStatus = 'error';
    //         $respMsg = 'Oops something went wrong. Todo not deleted successfully';
    //     }
    //     return redirect('admin/groups')->with($respStatus, $respMsg);
    // }
}
