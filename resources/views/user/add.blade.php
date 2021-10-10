@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2>Add User</h2>
        </div>
        <div class="col-md-6">
            <div class="float-right">
                <a href="{{ route('admin.users') }}" class="btn btn-primary">Back</a>
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
            <form action="{{ route('admin.user.store') }}" method="POST">
                <div class="row">
                    @csrf
                    <div class="form-group col-8">
                        <label for="fullname">Họ và tên</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required>
                    </div>
                    <div class="form-group col-4">
                        <label for="identity_num">Số CMND</label>
                        <input type="text" class="form-control" id="identity_num" name="identity_num" required>
                    </div>
                    <!-- <div class="form-group col-4">
                        <label for="email">Email</label>
                        <input type="text" class="form-control" id="email" name="email" required>
                    </div> -->

                    <div class="form-group col-6">
                        <label for="designation_code">Chức vụ</label>
                        <select class="form-control" id="designation_code" name="designation_code" required>
                            @forelse ($list_designation_code as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                            @empty

                            @endforelse
                        </select>
                    </div>

                    <div class="form-group col-4">
                        <label for="gender">Giới tính</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="0">Nam</option>
                            <option value="1">Nữ</option>
                        </select>
                    </div>

                    <!-- <div class="form-group col-4">
                        <label for="gender">Ngày sinh</label>
                        <div id="datepicker" class="input-group date" data-provide="datepicker">
                            <input type="text" class="form-control">
                            <div class="input-group-addon">
                                <span class="glyphicon glyphicon-th"></span>
                            </div>
                        </div>
                    </div> -->

                    <!-- <div class="form-group">
					<label for="description">Description:</label>
					<textarea name="description" class="form-control" id="description" rows="5"></textarea>
                    </div>
                    <div class="form-group">
                    <label for="status">Select todo status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                    </select>
                    </div> -->
                </div>

                <button type="submit" class="btn btn-default">Submit</button>
            </form>
        </div>
    </div>
</div>

@endsection
