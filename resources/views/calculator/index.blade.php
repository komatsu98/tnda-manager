@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-6 mb-3">
            <h2 class="mb-3">Tính toán thu nhập và thăng tiến</h2>
        </div>
        <div class="col-md-6">
            <div class="float-right">
                <a href="{{ route('admin.index') }}" class="btn btn-primary">Back</a>
            </div>
        </div>
        <br>
        <div class="col-12">
            <hr>
        </div>
        <div class="col-12">
            <a class="btn btn-info" href="/api/calc/all?month={{ $month_calc }}">Tính toán tất cả</a>
            <hr>
        </div>
        
        
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

            <form action="{{ route('admin.calculator.calc') }}" method="POST">
                @csrf
                @method('POST')
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="agent_code">Mã số nhân viên</label>
                        <input type="text" class="form-control" id="agent_code" name="agent_code">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="agent_name">Tên vấn viên</label>
                        <input type="text" class="form-control" id="agent_name" disabled>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="month">Tháng</label>
                        <input type="date" class="form-control" id="month" name="month" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-info">Tính toán trường hợp trên</button>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#agent_code').change(function() {
            $('#agent_name').val('');
            var agent_code = $(this).val().toLowerCase().replace('tnda', '');
            $.get(`/admin/user/${agent_code}/raw`, function(data) {
                if (data) {
                    $('#agent_name').val(data.fullname)
                }
            });
        });
    })
</script>
@endpush