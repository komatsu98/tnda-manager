@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2>Thêm Thay đổi chức vụ</h2>
        </div>
        <div class="col-md-6">
            <div class="float-right">
                <a href="{{ route('admin.promotion_ups') }}" class="btn btn-primary">Back</a>
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
            <form action="{{ route('admin.promotion_up.store') }}" method="POST">
                <div class="row">
                    @csrf
                    <div class="col-md-4 form-group">
                        <label for="agent_code">Mã số nhân viên</label>
                        <input type="text" class="form-control" id="agent_code" name="agent_code">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="agent_name">Tên vấn viên</label>
                        <input type="text" class="form-control" id="agent_name" disabled>
                    </div>
                    <div class="form-group col-6">
                        <label for="old_designation_code">Chức vụ cũ</label>
                        <select class="form-control" id="old_designation_code" name="old_designation_code">
                            @forelse ($list_designation_code as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label for="new_designation_code">Chức vụ mới</label>
                        <select class="form-control" id="new_designation_code" name="new_designation_code" required>
                            @forelse ($list_designation_code as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="valid_month">Có hiệu lực từ</label>
                        <input type="date" class="form-control" id="valid_month" name="valid_month">
                    </div>
                </div>

                <button type="submit" class="btn btn-default">Submit</button>
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
            $.get(`/admin/user/${agent_code}/raw`, function (data) {
                if(data) {
                    $('#agent_name').val(data.fullname)
                    $('#old_designation_code').val(data.designation_code)
                }
            });
        });
    })
</script>
@endpush