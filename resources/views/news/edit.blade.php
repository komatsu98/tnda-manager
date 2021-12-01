@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-6 mb-3">
            <h2 class="mb-3">Chỉnh sửa tin tức</h2>
        </div>
        <div class="col-md-6">
            <div class="float-right">
                <a href="{{ route('admin.newss') }}" class="btn btn-primary">Back</a>
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
            <form action="{{ route('admin.news.update', $news->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label for="title">Tiêu đề</label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ $news->title }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="type">Thể loại</label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="">Chọn loại tin</option>
                            <option value="0">Tin ngắn</option>
                            <option value="1">Bài viết gắn link</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="status">Trạng thái</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="">Chọn trạng thái</option>
                            <option value="0">Ẩn</option>
                            <option value="1">Hiện</option>
                        </select>
                    </div>
                    <div class="col-md-12 form-group">
                        <label for="url">Link</label>
                        <input type="text" class="form-control" id="url" name="url" value="{{ $news->url }}">
                    </div>
                    <div class="col-md-12 form-group">
                        <label for="lead">Tóm tắt</label>
                        <input type="text" class="form-control" id="lead" name="lead" value="{{ $news->lead }}">
                    </div>
                    <div class="col-md-12 form-group">
                        <label for="content">Nội dung</label>
                        <input type="text" class="form-control" id="content" name="content" value="{{ $news->content }}">
                    </div>
                    <div class="col-md-12 form-group">
                        <label for="image">Link ảnh</label>
                        <input type="text" class="form-control" id="image" name="image" value="{{ $news->image }}">
                    </div>

                    <div class="col-md-6 form-group">
                        <label for="public_at">Ngày xuất bản</label>
                        <input type="date" id="public_at" name="public_at" value="{{ $news->public_at }}">
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
        $('#type').val("{{$news->type}}");
        $('#status').val("{{$news->status}}");
    })
</script>
@endpush