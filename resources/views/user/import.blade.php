@extends('layouts.app')

@section('content')
<div class="container">
	<br>
	<div class="row justify-content-center">
		<div class="col-md-6">
			<h2>Add User</h2>
		</div>
		<div class="col-md-6">
			<div class="float-right">
				<a href="{{ route('admin.users') }}" class="btn btn-primary">Back</a>
			</div>
		</div>
		<br>
		<div class="col-md-12">
			@if (session('success'))
			<div class="alert alert-success" role="alert">
				{{ session('success') }}
			</div>
			<img src="uploads/{{ Session::get('file') }}">
			@endif
			@if (session('error'))
			<div class="alert alert-error" role="alert">
				{{ session('error') }}
			</div>
			@endif
			<form action="{{ route('admin.user.import') }}" enctype="multipart/form-data" method="POST">
				@csrf
				<div>
					<div>
						<input type="file" name="file">
					</div>
					<div>
						<button class="btn" type="submit">Upload</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection