@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2>{{ $user->name }}'s Groups</h2>
        </div>
        <div class="col-md-6">
            <div class="float-right">
                <a href="{{ route('admin.user.group.create', $user->id) }}" class="btn btn-primary"><i class="fa fa-plus"></i> Join new Group</a>
            </div>
        </div>
        <br>
        <div class="col-md-12">
            @if (session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
            @endif
            @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
            @endif
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th width="5%">ID</th>
                        <th>Name</th>
                        <th>Master</th>
                        <th width="5%">Members</th>
                        <th>Joined at</th>
                        <th width="20%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                    <tr>
                        <th>{{ $group->id }}</th>
                        <td><b><a href="/admin/groups?id={{$group->id}}">{{ $group->name }}</a></b></td>
                        <td>{!! $group->masters !!}</td>
                        <td>{{ $group->users()->count() }}</td>
                        <td>{{ $group->pivot->created_at }}</td>
                        <td class="d-flex justify-content-around">
                            <div class="action_btn">
                                <div class="action_btn">
                                    <form action="{{ route('admin.user.group.destroy', ['user' => $user->id, 'group' => $group->id])}}" method="post">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger" type="submit">Kick</button>
                                    </form>
                                </div>
                                <div class="action_btn margin-left-10">
                                    <form action="{{ route('admin.user.group.update', ['user' => $user->id, 'group' => $group->id])}}" method="post">
                                        @csrf
                                        @method('PUT')
                                        @if (!$group->pivot->is_master)
                                        <input type="hidden" value="make_master" name="type">
                                        <button class="btn btn-success" type="submit">Make master</button>
                                        @else
                                        <input type="hidden" value="undo_master" name="type">
                                        <button class="btn btn-secondary" type="submit">Undo master</button>
                                        @endif
                                    </form>
                                </div>
                            </div>

                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <center>No data found</center>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <br>
    </div>
</div>

@endsection
