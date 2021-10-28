@extends('layouts.app')

@section('content')

<div class="container">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<h2>Thêm thành viên mới</h2>
		</div>
		<div class="col-md-6">
            <div class="float-right">
                <a href="{{ route('admin.users') }}" class="btn btn-primary">Back</a>
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
			<form action="{{ route('admin.user.import') }}" method="POST" enctype="multipart/form-data">
				@csrf
				<input type="file" name="file" class="form-control">
				<br>
				<button class="btn btn-success">Nhập</button>
			</form>
		</div>
	</div>
</div>
@endsection