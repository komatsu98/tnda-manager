@extends('layouts.app')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="links">
                <a href="{{ url('/admin/users') }}">Thành viên</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/contracts') }}">Hợp đồng</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/users') }}">Khách hàng</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/users') }}">Bảng tin</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/users') }}">Báo cáo kinh doanh</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/users') }}">Thu nhập</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/users') }}">Hoa hồng trong tháng</a>
            </div>
        </div>
    </div>
</div>
@endsection