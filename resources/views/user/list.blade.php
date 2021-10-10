@extends('layouts.app')
@section('content')
<div class="container">
	<br>
	<div class="row justify-content-center">
		<div class="col-md-3">
			<h2>
			Users List 
			<a href="{{ route('admin.user.create')}}"><span style="font-size: 24px;"><i class="fas fa-plus text-grey" aria-hidden="true"></i></span></a>
			<a href="{{ route('admin.user.bulk_create')}}"><span style="font-size: 24px;"><i class="fas fa-plus text-grey" aria-hidden="true"></i></span></a>
			</h2>

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
						<th>Mã nhân viên</th>
						<th>Họ và tên</th>
						<th>Ngày sinh</th>
						<th>Giới tính</th>
						<th>Số CMND</th>
						<th>Ngày cấp CMND</th>
						<th>Nơi cấp CMND</th>
						<th>Địa chỉ thường trú</th>
						<th>Email</th>
						<th>Điện thoại</th>
						<th>Tình trạng hôn nhân</th>
						<th>Ngày bắt đầu</th>
						<th>Ngày cấp code</th>
						<th>Đơn vị</th>
						<th>IFA</th>
						<th>Chức vụ</th>
						<th>Người giới thiệu</th>
						<th>Mã số người giới thiệu</th>
						<th>Quản lý trực tiếp</th>
						<th>Mã số Quản lý trực tiếp</th>
						<th>Chức vụ Quản lý trực tiếp</th>
						<th>Giám đốc kinh doanh miền</th>
						<th>Mã số Giám đốc kinh doanh miền</th>
					</tr>
				</thead>
				<tbody>
					@forelse ($users as $user)
					<tr>
						<td>{{ $user->agent_code }}</td>
						<td>{{ $user->fullname }}</td>
						<td>{{ $user->day_of_birth }}</td>
						<td>{{ $user->gender_text }}</td>
						<td>{{ $user->identity_num }}</td>
						<td>{{ $user->identity_alloc_date }}</td>
						<td>{{ $user->identity_alloc_place }}</td>
						<td>{{ $user->resident_address }}</td>
						<td>{{ $user->email }}</td>
						<td>{{ $user->mobile_phone }}</td>
						<td>{{ $user->marital_status_text }}</td>
						<td>{{ $user->IFA_start_date }}</td>
						<td>{{ $user->alloc_code_date }}</td>
						<td>{{ $user->IFA_branch }}</td>
						<td>{{ $user->IFA }}</td>
						<td>{{ $user->designation_code }}</td>
						<td>{{ $user->ref_name }}</td>
						<td>{{ $user->ref_code }}</td>
						<td>{{ $user->supervisor_name }}</td>
						<td>{{ $user->supervisor_code }}</td>
						<td>{{ $user->supervisor_designation_code }}</td>
						<td>{{ $user->TD_name }}</td>
						<td>{{ $user->TD_code }}</td>
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
			{{ $users->links() }}
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