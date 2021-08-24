@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2>{{ $user->name }} - Join new group</h2>
        </div>
        <div class="col-md-6">
            <div class="float-right">
                <a href="{{ route('admin.user.group.list', $user->id) }}" class="btn btn-primary">Back</a>
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
            <div class="alert alert-error" role="alert">
                {{ session('error') }}
            </div>
            @endif
            <form action="{{ route('admin.user.group.store', $user->id) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="group">Select Group</label>
                    <select class="form-control" id="group" name="group_id">
                        <option value="">Choose group</option>
                        @foreach ($groups as $group)
                        <option value="{{$group->id}}">{{$group->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="is_master">Make master</label>
                    <input type="checkbox" id="is_master" name="is_master">
                </div>
                <button type="submit" class="btn btn-default">Submit</button>
            </form>
        </div>
    </div>
</div>
@endsection