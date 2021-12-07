@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-6 mb-3">
            <h2 class="mb-3">Cập nhật thông tin Tiến độ thăng tiến</h2>
            <h4>{{ $promotion->agent_text }} - {{ $promotion->pro_text }}</h4>
        </div>
        <div class="col-md-6">
            <div class="float-right">
                <a href="{{ route('admin.promotions') }}" class="btn btn-primary">Back</a>
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
            <form action="{{ route('admin.promotion.update', $promotion->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label for="req_text">Yêu cầu</label>
                        <input type="text" class="form-control" id="req_text" value="{{ $promotion->req_text }}" disabled>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="progress_text">Đạt được</label>
                        <input type="text" class="form-control" name="progress_text" id="progress_text" value="{{ $promotion->progress_text }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="requirement_text">Điều kiện</label>
                        <input type="text" class="form-control" id="requirement_text" value="{{ $promotion->requirement_text }}" disabled>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="is_done">Trạng thái</label>
                        <select name="is_done" id="is_done" class="form-control" required>
                            <option value="0">Đang tiến hành</option>
                            <option value="1">Đã hoàn thành</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-default">Submit</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#is_done').val("{{$promotion->is_done}}");
    })
</script>
@endpush
@endsection