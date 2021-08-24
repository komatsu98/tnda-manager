@extends('layouts.app')

@section('content')
<div class="container">
	<br>
	<div class="row justify-content-center">
		<div class="col-md-6">
			<h2>Groups List</h2>
		</div>
		<div class="col-md-6">
			<div class="float-right">
				<a href="{{ route('admin.group.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add new group</a>
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
						<th width="5%">ID</th>
						<th>Name</th>
						<th>Masters</th>
						<th width="5%">Members</th>
						<th>Created at</th>
						<th width="14%">
							<center>Action</center>
						</th>
					</tr>
				</thead>
				<tbody>
					@forelse ($groups as $group)
					<tr>
						<td>{{ $group->id }}</td>
						<td><b>{{ $group->name }}</b></td>
						<td>{!! $group->masters !!}</td>
						<td>
							<center>{{ $group->members }}</center>
						</td>
						<td>{{ $group->created_at }}</td>
						<td class="d-flex justify-content-around">
							<div class="action_btn">
								<div class="action_btn">
									<a href="{{ route('admin.group.edit', $group->id)}}" class="btn btn-secondary">Edit</a>
								</div>
								<div class="action_btn ml-2">
									<form action="{{ route('admin.group.destroy', $group->id) }}" method="post">
										@csrf
										@method('DELETE')
										<button class="btn btn-danger" type="submit">Delete</button>
									</form>
								</div>
							</div>
						</td>
					</tr>
					@empty
					<tr>
						<td colspan="4">
							<center>No data found</center>
						</td>
					</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>
</div>
@endsection