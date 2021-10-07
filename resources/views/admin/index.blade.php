@extends('layouts.app')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="links">
                <a href="{{ url('/admin/users') }}">Danh sách thành viên</a>
            </div>
        </div>
    </div>
</div>
@endsection