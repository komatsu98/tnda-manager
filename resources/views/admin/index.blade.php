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
                <a href="{{ url('/admin/customers') }}">Khách hàng</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/app-news') }}">Bảng tin</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/transactions') }}">Giao dịch</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/metrics') }}">Chỉ số</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/incomes') }}">Thu nhập</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/promotion-ups') }}">Thay đổi Chức vụ</a>
            </div>
            <div class="links">
                <a href="{{ url('/admin/promotion-steps') }}">Tiến trình thăng tiến</a>
            </div>
        </div>
    </div>
</div>
@endsection