<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Admin;
use Auth;

use function PHPSTORM_META\exitPoint;

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
        $agents = User::orderBy('created_at', 'desc');
        if (request()->has('id')) {
            $id = request('id');
            $agents = $agents->where('id', '=', $id);
        }
        if (request()->has('search')) {
            $str = trim(strtolower(request('search')), ' ');
            $agents = $agents->where('username', 'LIKE', '%' . $str . '%')
                ->orwhere('fullname', 'LIKE', '%' . $str . '%')
                ->orWhere('email', 'LIKE', '%' . $str . '%')
                ->orWhere('agent_code', 'LIKE', '%' . $str . '%')
                ->orWhere('id', 'LIKE', '%' . $str . '%');
        }
        $agents = $agents->paginate(15);
        return view('agent.list', ['agents' => $agents]);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'fullname' => 'required',
            'username' => 'required',
        ]);
        $input = $request->input();
        $input['is_master'] = $request->has('is_master');

        $user = FUser::find($id);
        if (!$user) {
            return redirect('admin/users')->with('error', 'User not found.');
        }
        $gid = $input['group_id'];
        if ($user->groups()->find($gid)) {
            return redirect('admin/user/' . $id . '/group')->with('error', 'Group already joined');
        }
        $group = FGroup::find($gid);
        if (!$group) {
            return back()->with('error', 'Group not found.');
        }
        $user->groups()->attach($gid, ['group_id' => $input['group_id'], 'is_master' => $input['is_master']]);
        return redirect('admin/user/' . $id . '/group')->with('success', 'Group successfully joined.');
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
