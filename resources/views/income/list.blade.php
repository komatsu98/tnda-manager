@extends('layouts.app')
@section('content')
<div class="container">
	<br>
	<div class="row justify-content-center">
		<div class="col-md-6">
			<h2>
				Danh sách Thu nhập
				<h4>
					<h6>Tải Báo cáo Thu nhập tháng</h6>
					<form action="{{ route('admin.income.export')}}" method="get" id="export">
						<div class="input-group md-form form-sm form-2 pl-0">
							<input class="form-control my-0 py-1 lime-border" type="text" placeholder="Tháng, ví dụ: 2021-12" aria-label="month" name="month">
							<div class="input-group-append">
								<span class="input-group-text lime lighten-2" id="download_icon"><i class="fas fa-download text-grey" aria-hidden="true"></i></span>
							</div>
						</div>
					</form>
				</h4>

			</h2>
		</div>
		<div class="col-md-6">
			<div class="float-right">
				<form action="{{ route('admin.incomes')}}" method="get" id="search">
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
			@include('layouts.income_detail', ['incomes' => $incomes])
		</div>
		<br>
		<div class="col-md-12 d-flex justify-content-center">
			{{ $incomes->links() }}
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		$('#download_icon').click(function() {
			$('form#export').submit();
		})
	});
</script>
@endpush