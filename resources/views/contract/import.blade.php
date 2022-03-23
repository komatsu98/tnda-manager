@extends('layouts.app')

@section('content')

<div class="container">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<h2>Thêm hợp đồng/giao dịch mới</h2>
		</div>
		<div class="col-md-6">
			<div class="float-right">
				<a href="{{ route('admin.contracts') }}" class="btn btn-primary">Back</a>
			</div>
		</div>
	</div>

	<div class="card bg-light mt-3">
		<div class="card-header">
			Choose file to upload

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
		</div>
		<div class="card-body">
			<form action="{{ route('admin.contract.import') }}" method="POST" enctype="multipart/form-data">
				@csrf
				<div>
					<label for="partner_code">Đối tác</label>
					<select name="partner_code" id="partner_code" class="form-control" required>
						<option value="">Chọn đối tác</option>
						@foreach ($list_partners as $partner)
						<option value="{{$partner['code']}}">{{$partner['name']}}</option>
						@endforeach
					</select>
				</div>
				<div>
					<input type="file" name="file" class="form-control">
				</div>
				<br>
				<button class="btn btn-success">Nhập</button>
			</form>
			<hr>
			<div class="row">
				<div class="col-12">
					<h3>Danh sách file mẫu</h3>
					<ul>
						<li><a href="/upload_templates/BML.xlsx">BML</a></li>
						<li><a href="/upload_templates/FWD.xlsx">FWD</a></li>
						<li><a href="/upload_templates/VBI.xlsx">VBI</a></li>
						<li><a href="/upload_templates/BV.xlsx">BV</a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection