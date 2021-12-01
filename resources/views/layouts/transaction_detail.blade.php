<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Sản phẩm hợp đồng</th>
            <th>Tư vấn viên</th>
            <th>Số tiền</th>
            <th>Loại phí</th>
            <th>Ngày giao dịch</th>
            <th>Hoa hồng</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transactions as $transaction)
        <tr>
            <td><a href="{{route('admin.contract.product.detail', $transaction->contract_product_id)}}">{{ $transaction->product_text }}</a></td>
            <td><a href="{{route('admin.user.detail', $transaction->agent_code)}}">{{ $transaction->agent_text }}</a></td>
            <td>{{ $transaction->premium_received }}</td>
            <td>{{ $transaction->renewal_text }}</td>
            <td>{{ $transaction->trans_date }}</td>
            <td>{{ $transaction->comission_amount }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4">
                <center>Không có dữ liệu phù hợp</center>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>