@extends('layouts.app')

@section('content')
<div class="container">
	<br>
	<div class="row justify-content-center">
		<div class="col-md-3">
			<h2>Users List</h2>
		</div>
		<div class="col-md-9">
			<div class="float-right">
				<form action="{{ route('admin.users')}}" method="get" id="search">
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
						<th width="5%">ID</th>
						<th>Nickname</th>
						<th>Email</th>
						<th>Created at</th>
						<th width="5%">
							<center>Status</center>
						</th>
						<th>
							<center>Action</center>
						</th>
					</tr>
				</thead>
				<tbody>
					@forelse ($fUsers as $user)
					<tr>
						<th>{{ $user->id }}</th>
						<td>{{ $user->name }}</td>
						<td>{{ $user->email }}</td>
						<td>{{ $user->created_at }}</td>
						<td>
							<center>
								<form action="{{ route('admin.user.update', $user->id) }}" method="post" id="update_status">
									@csrf
									@method('PUT')
									@if ($user->status == 1)
									<input type="checkbox" checked class="change_status" name="status">
									@else
									<input type="checkbox" class="change_status" name="status">
									@endif
									<input type="hidden" name="type" value="active">
								</form>

							</center>

						</td>
						<td class="d-flex justify-content-around">
							<a href="/admin/user/{{ $user->id }}/group"><button class="btn btn-primary">Group</button></a>
							<a href="/admin/user/{{ $user->id }}/history"><button class="btn btn-primary">History</button></a>
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
		<br>
		<div class="col-md-12 d-flex justify-content-center">
			{{ $fUsers->links() }}
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('input.change_status').click(function() {
			$(this).parent('form').submit();
		})
		$('#search_icon').click(function() {
			$('form#search').submit();
		})
	});
</script>

@endsection