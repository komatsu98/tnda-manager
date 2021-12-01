@extends('layouts.app')
@section('content')
<div class="container">
	<br>
	<div class="row justify-content-center">
		<div class="col-md-6">
			<h2>
			Danh sách sản phẩm trong hợp đồng
			<!-- <a href="{{ route('admin.contract.create')}}"><span style="font-size: 24px;"><i class="fas fa-plus text-grey" aria-hidden="true"></i></span></a> -->
			<!-- <a href="{{ route('admin.contract.bulk_create')}}"><span style="font-size: 24px;"><i class="fas fa-plus text-grey" aria-hidden="true"></i></span></a> -->
			</h2>
		</div>
        <div class="col-md-6">
            <div class="float-right">
                <a href="{{ route('admin.contract.detail', $contract->id) }}" class="btn btn-primary">Back</a>
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
			@include('layouts.contract_product_detail', ['contract_products' => $contract_products])
		</div>
		<br>
		<div class="col-md-12 d-flex justify-content-center">
			{{ $contract_products->links() }}
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		$('#search_icon').click(function() {
			$('form#search').submit();
		})
	});
</script>
@endpush