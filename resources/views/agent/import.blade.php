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
            @endif
            @if (session('error'))
                <div class="alert alert-error" role="alert">
                    {{ session('error') }}
                </div>
            @endif
			<form action="{{ route('admin.user.store') }}" method="POST">
				@csrf
				<div class="form-group">
					<label for="fullname">Full name</label>
					<input type="text" class="form-control" id="fullname" name="fullname">
				</div>
                <div class="form-group">
					<label for="agent_code">Code</label>
					<input type="text" class="form-control" id="agent_code" name="agent_code">
				</div>
				<button type="submit" class="btn btn-default">Submit</button>
			</form>
            <form action="{{ route('admin.user.import') }}" method="POST">
				@csrf
                <div class="form-group">
					<label for="agent_code">Code</label>
					<input type="text" class="form-control" id="agent_code" name="agent_code">
				</div>
				<button type="submit" class="btn btn-default">Submit</button>
			</form>
        </div>
    </div>
</div>
@endsection