@extends('layouts.app')
@section('content')
<div class="container">
	<br>
	<div class="row justify-content-center">
		<div class="col-md-6">
			<h2>
				Danh sách bài viết
				<a href="{{ route('admin.news.create')}}"><span style="font-size: 24px;"><i class="fas fa-plus text-grey" aria-hidden="true"></i></span></a>
			</h2>
		</div>
		<div class="col-md-6">
			<div class="float-right">
				<form action="{{ route('admin.newss')}}" method="get" id="search">
					<div class="input-group md-form form-sm form-2 pl-0">
						<input class="form-control my-0 py-1 lime-border" type="text" placeholder="Search" aria-label="Search" name="search">
						<div class="input-group-append">
							<span class="input-group-text lime lighten-2" id="search_icon"><i class="fas fa-search text-grey" aria-hidden="true"></i></span>
						</div>
					</div>
				</form>
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
			<div class="alert alert-danger" role="alert">
				{{ session('error') }}
			</div>
			@endif
			<table class="table table-bordered">
				<thead class="thead-light">
					<tr>
						<th>Tiêu đề</th>
						<th>Thể loại</th>
						<th>Link</th>
						<th>Ảnh</th>
						<th>Trạng thái</th>
						<th>Ngày xuất bản</th>
					</tr>
				</thead>
				<tbody>
					@forelse ($newss as $news)
					<tr>
						<td><a href="{{ route('admin.news.detail', $news->id) }}">{{ $news->title }}</a></td>
						<td>{{ $news->type_text }}</td>
						<td><a href="{{ $news->url }}">Link</a></td>
						<td><a href="{{ $news->image }}">Link</a></td>
						<td>{{ $news->status_text }}</td>
						<td>{{ $news->public_at }}</td>
					</tr>
					@empty
					<tr>
						<td colspan="4">
							<center>Không có dữ liệu phù hợp</center>
						</td>
					</tr>
					@endforelse
				</tbody>
			</table>
		</div>
		<br>
		<div class="col-md-12 d-flex justify-content-center">
			{{ $newss->links() }}
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		$('#search_icon').click(function() {
			$('form#search').submit();
		})
	});
</script>
@endpush