<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Số hợp đồng đối tác</th>
            <th>Sản phẩm</th>
            <th>Phí phải thu</th>
            <th>Phí định kỳ</th>
            <th>Định kỳ đóng phí</th>
            <th>Phí đã thu</th>
            <th>Phí tái tục đã thu</th>
            <th>Hoa hồng</th>
            <th>Giao dịch</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($contract_products as $contract_product)
        <tr>
            <td>{{ $contract_product->partner_contract_code }}</td>
            <td>{{ $contract_product->product_text }}</td>
            <td>{{ $contract_product->premium }}</td>
            <td>{{ $contract_product->premium_term }}</td>
            <td>{{ $contract_product->term_text }}</td>
            <td>{{ $contract_product->premium_received }}</td>
            <td>{{ $contract_product->renewal_premium_received }}</td>
            <td>{{ $contract_product->comission }}</td>
            <td><a href="{{ route('admin.contract.product.transactions', $contract_product->id) }}">{{ $contract_product->transaction_count }} giao dịch</a></td>
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