@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-6 mb-3">
            <h2 class="mb-3">Cập nhật thông tin Thay đổi chức vụ</h2>
            <h4>{{ $promotion->agent_text }}</h4>
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
            <form action="{{ route('admin.promotion_up.update', $promotion->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="form-group col-6">
                        <label for="old_designation_code">Chức vụ cũ</label>
                        <select class="form-control" id="old_designation_code" name="old_designation_code" value="{{ $promotion->old_designation_code }}">
                            @forelse ($list_designation_code as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="form-group col-6">
                        <label for="new_designation_code">Chức vụ mới</label>
                        <select class="form-control" id="new_designation_code" name="new_designation_code" value="{{ $promotion->new_designation_code }}" required>
                            @forelse ($list_designation_code as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="valid_month">Có hiệu lực từ</label>
                        <input type="date" class="form-control" id="valid_month" name="valid_month" value="{{ $promotion->valid_month }}">
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
        $('#old_designation_code').val("{{$promotion->old_designation_code}}");
        $('#new_designation_code').val("{{$promotion->new_designation_code}}");
        $('#valid_month').val("{{$promotion->valid_month}}");
    })
</script>
@endpush
@endsection