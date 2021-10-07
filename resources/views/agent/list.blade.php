@extends('layouts.app')
@section('content')
<div class="container">
	<br>
	<div class="row justify-content-center">
		<div class="col-md-3">
			<h2>Users List <a href="{{ route('admin.user.create')}}"><span style="font-size: 24px;"><i class="fas fa-plus text-grey" aria-hidden="true"></i></span></a></h2>
            
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
						<th>Code</th>
						<th>Email</th>
						<th>Username</th>
                        <th>Full name</th>
					</tr>
				</thead>
				<tbody>
					@forelse ($agents as $agent)
					<tr>
						<th>{{ $agent->id }}</th>
						<td>{{ $agent->agent_code }}</td>
						<td>{{ $agent->email }}</td>
						<td>{{ $agent->username }}</td>
                        <td>{{ $agent->fullname }}</td>
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
			{{ $agents->links() }}
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('#search_icon').click(function() {
			$('form#search').submit();
		})
	});
</script>
@endsection