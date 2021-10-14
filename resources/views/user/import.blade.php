@extends('layouts.app')

@section('content')

<div class="container">
	<div class="">
		<h2>Add User</h2>
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
				<button class="btn btn-success">Import</button>
			</form>
		</div>
	</div>
</div>
@endsection