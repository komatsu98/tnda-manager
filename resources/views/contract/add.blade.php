@extends('layouts.app')

@section('content')
<div class="container">
    <br>
    <div class="row justify-content-center">
        <div class="col-md-6 mb-3">
            <h2 class="mb-3">Thêm hợp đồng</h2>
        </div>
        <div class="col-md-6">
            <div class="float-right">
                <a href="{{ route('admin.contracts') }}" class="btn btn-primary">Back</a>
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
            <form action="{{ route('admin.contract.store') }}" method="POST">
                @csrf
                @method('POST')
                <div class="row">
                    <!-- <div class="col-md-4 form-group">
                        <label for="name">Số hợp đồng TNDA</label>
                        <input type="text" class="form-control" id="contract_code" name="contract_code" required>
                    </div> -->
                    <div class="col-md-3 form-group">
                        <label for="customer_id">ID khách hàng</label>
                        <input type="text" class="form-control" id="customer_id" name="customer_id" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="customer_name">Tên khách hàng</label>
                        <input type="text" class="form-control" id="customer_name" disabled>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="partner_code">Đối tác</label>
                        <select name="partner_code" id="partner_code" class="form-control" required>
                            <option value="">Chọn đối tác</option>
                            @foreach ($list_partners as $partner)
                            <option value="{{$partner['code']}}">{{$partner['name']}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="partner_contract_code">Số hợp đồng đối tác</label>
                        <input type="text" class="form-control" id="partner_contract_code" name="partner_contract_code" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="agent_code">Mã số tư vấn viên</label>
                        <input type="text" class="form-control" id="agent_code" name="agent_code" >
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="agent_name">Tư vấn viên</label>
                        <input type="text" class="form-control" id="agent_name" disabled>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="product_code">Sản phẩm chính</label>
                        <select name="product_code[]" id="product_code" class="form-control selectpicker" multiple data-live-search="true" required>
                            <option value="">Chọn sản phẩm</option>
                            @foreach ($list_product_code as $code => $name)
                            <option value="{{$code}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="sub_product_code">Sản phẩm phụ</label>
                        <select name="sub_product_code[]" id="sub_product_code" class="form-control selectpicker" multiple data-live-search="true">
                            <option value="">Chọn sản phẩm phụ</option>
                            @foreach ($list_product_code as $code => $name)
                            <option value="{{$code}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="submit_date">Ngày nộp hợp đồng</label>
                        <input type="date" class="form-control" id="submit_date" name="submit_date" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="release_date">Ngày hiệu lực</label>
                        <input type="date" class="form-control" id="release_date" name="release_date" >
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="ack_date">Ngày ACK</label>
                        <input type="date" class="form-control" id="ack_date" name="ack_date" >
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="maturity_date">Ngày đáo hạn</label>
                        <input type="date" class="form-control" id="maturity_date" name="maturity_date" >
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="premium">Tổng số phí phải đóng</label>
                        <input type="number" class="form-control" id="premium" name="premium" >
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="term_code">Định kỳ đóng phí</label>
                        <select name="term_code" id="term_code" class="form-control" required>
                            <option value="">Chọn kỳ đóng phí</option>
                            @foreach ($list_contract_term_code as $code => $name)
                            <option value="{{$code}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="premium_term">Phí định kỳ</label>
                        <input type="number" class="form-control" id="term_premium" name="premium_term" >
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="premium_received">Tổng số phí đã đóng</label>
                        <input type="number" class="form-control" id="premium_received" name="premium_received" >
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="info_awaiting">Hồ sơ còn thiếu</label>
                        <select name="info_awaiting[]" id="info_awaiting" class="form-control selectpicker" multiple data-live-search="true">
                            <option value="">Chọn hồ sơ</option>
                            @foreach ($list_contract_info_await_code as $code => $name)
                            <option value="{{$code}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="status_code">Tình trạng</label>
                        <select name="status_code" id="status_code" class="form-control">
                            <option value="">Chọn tình trạng</option>
                            @foreach ($list_contract_status_code as $code => $name)
                            <option value="{{$code}}">{{$name}}</option>
                            @endforeach
                        </select>
                    </div>
                    

                </div>

                <button type="submit" class="btn btn-default">Submit</button>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

<script>
    $(document).ready(function() {
        $('#agent_code').change(function() {
            $('#agent_name').val('');
            var agent_code = $(this).val().toLowerCase().replace('tnda', '');
            $.get(`/admin/user/${agent_code}/raw`, function (data) {
                if(data) $('#agent_name').val(data.fullname)
            });
        });
        $('#customer_id').change(function() {
            $('#customer_name').val('');
            var cus_id = $(this).val().toLowerCase().replace('tnda', '');
            if(!cus_id) return;
            $.get(`/admin/customer/${cus_id}/raw`, function(data) {
                if (data) $('#customer_name').val(data.fullname)
            });
        });
        $('.selectpicker').selectpicker();
    })
</script>
@endpush