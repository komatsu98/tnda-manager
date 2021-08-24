@extends('layouts.app')

@section('content')
<div class="container">
	<br>
    <div class="row justify-content-center">
    	<div class="col-md-6">
    		<h2>Edit Todo</h2>
    	</div>
    	<div class="col-md-6">
    		<div class="float-right">
    			<a href="{{ route('admin.groups') }}" class="btn btn-primary">Back</a>
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
			<form action="{{ route('admin.group.update', ['id' => $group->id]) }}" method="POST">
				@csrf
                @method('PUT')
				<div class="form-group">
					<label for="name">Name</label>
					<input type="text" class="form-control" id="name" name="name" value="{{ $group->name }}">
				</div>
				<!-- <div class="form-group">
					<label for="description">Description:</label>
					<textarea name="description" class="form-control" id="description" rows="5">{{ $group->description }}</textarea>
				</div>
				<div class="form-group">
				<label for="status">Select todo status</label>
				<select class="form-control" id="status" name="status">
					<option value="pending" @if ($group->status == 'pending') selected @endif>Pending</option>
					<option value="completed" @if ($group->status == 'completed') selected @endif>Completed</option>
				</select>
				</div> -->
				<button type="submit" class="btn btn-default">Submit</button>
			</form>
        </div>
    </div>
</div>
@endsection
