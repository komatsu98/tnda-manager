@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-6 mb-3">
            <h2 class="mb-3">Cập nhật thông tin thành viên</h2>
            <h4>{{ $user->fullname }} - TNDA{{ $user->agent_code }}</h4>
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
            <form action="{{ route('admin.user.update', ['id' => $user->id]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="name">Họ và tên</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" value="{{ $user->fullname }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="day_of_birth">Ngày sinh</label>
                        <input type="date" class="form-control" id="day_of_birth" name="day_of_birth" value="{{ $user->day_of_birth }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="gender">Giới tính</label>
                        <select name="gender" id="gender" class="form-control" required>
                            <option value="">Chọn giới tính</option>
                            <option value="0">Nam</option>
                            <option value="1">Nữ</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="identity_num">Số CMTND</label>
                        <input type="text" class="form-control" id="identity_num" name="identity_num" value="{{ $user->identity_num }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="identity_alloc_place">Nơi cấp CMTND</label>
                        <input type="text" class="form-control" id="identity_alloc_place" name="identity_alloc_place" value="{{ $user->identity_alloc_place }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="identity_alloc_date">Ngày cấp CMTND</label>
                        <input type="date" class="form-control" id="identity_alloc_date" name="identity_alloc_date" value="{{ $user->identity_alloc_date }}" required>
                    </div>
                    <div class="col-md-5 form-group">
                        <label for="resident_address">Địa chỉ thường trú</label>
                        <input type="text" class="form-control" id="resident_address" name="resident_address" value="{{ $user->resident_address }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="email">Email cá nhân</label>
                        <input type="text" class="form-control" id="email" name="email" value="{{ $user->email }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="company_email">Email công ty</label>
                        <input type="text" class="form-control" id="company_email" name="company_email" value="{{ $user->company_email }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="mobile_phone">Điện thoại</label>
                        <input type="text" class="form-control" id="mobile_phone" name="mobile_phone" value="{{ $user->mobile_phone }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="marital_status_code">Tình trạng hôn nhân</label>
                        <select name="marital_status_code" id="marital_status_code" class="form-control">
                            <option value="">Chọn tình trạng hôn nhân</option>
                            @foreach ($list_marital_status_code as $code => $name)
                            <option value="{{$code}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="alloc_code_date">Ngày cấp code</label>
                        <input type="date" class="form-control" id="alloc_code_date" name="alloc_code_date" value="{{ $user->alloc_code_date }}" disabled>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="designation_code">Chức vụ</label>
                        <select name="designation_code" id="designation_code" class="form-control" required>
                            <option value="">Chọn chức vụ</option>
                            @foreach ($list_designation_code as $code => $name)
                            <option value="{{$code}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="reference_code">Mã số người giới thiệu</label>
                        <input type="text" class="form-control" id="reference_code" name="reference_code" value="{{ $user->reference_code }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="reference_name">Tên người giới thiệu</label>
                        <input type="text" class="form-control" id="reference_name" name="reference_name" value="{{ $user->reference_name }}" disabled>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="supervisor_code">Mã số quản lý trực tiếp</label>
                        <input type="text" class="form-control" id="supervisor_code" name="supervisor_code" value="{{ $user->supervisor_code }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="supervisor_name">Tên quản lý trực tiếp</label>
                        <input type="text" class="form-control" id="supervisor_name" name="supervisor_name" value="{{ $user->supervisor_name }}" disabled>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="supervisor_designation_text">Chức vụ người quản lý trực tiếp</label>
                        <input type="text" class="form-control" id="supervisor_designation_text" name="supervisor_designation_text" value="{{ $user->supervisor_designation_text }}" disabled>
                    </div>           
                    <div class="col-md-1 form-group">
                        <label for="active">Active</label>
                        <input type="checkbox" class="form-control" id="active" name="active" @if ($user->active == 1) checked @endif value="{{$user->active}}">
                    </div>         
                </div>

                <button type="submit" class="btn btn-default">Submit</button>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#gender').val("{{$user->gender}}");
        $('#marital_status_code').val("{{$user->marital_status_code ? $user->marital_status_code : ''}}");
        $('#designation_code').val("{{$user->designation_code ? $user->designation_code : ''}}");
        $('#active').change(function(){
            $(this).val($(this).prop('checked') ? 1 : 0);
        })
        $('#reference_code').change(function() {
            $('#reference_name').val('');
            var ref_code = $(this).val().toLowerCase().replace('tnda', '');
            console.log("ref_code", ref_code)
            $.get(`/admin/user/${ref_code}/raw`, function (data) {
                if(data) $('#reference_name').val(data.fullname)
            });
        });
        $('#supervisor_code').change(function() {
            $('#supervisor_name').val('');
            $('#supervisor_designation_text').val('');
            var sup_code = $(this).val().toLowerCase().replace('tnda', '');
            console.log("sup_code", sup_code)
            $.get(`/admin/user/${sup_code}/raw`, function (data) {
                if(data) {
                    $('#supervisor_name').val(data.fullname)
                    $('#supervisor_designation_text').val(data.designation_text)
                }
            });
        })
        // $('#submit').click(function(){
        //     $('#active').val($('#active').prop('checked') ? 1 : 0);
        // })
    })
</script>
@endsection