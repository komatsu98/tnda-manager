<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Số hợp đồng TNDA</th>
            <th>Tên khách hàng</th>
            <th>Đối tác</th>
            <th>Số hợp đồng đối tác</th>
            <th>Sản phẩm chính</th>
            <th>Sản phẩm phụ</th>
            <th>Tư vấn viên</th>
            <th>Ngày hiệu lực</th>
            <th>Ngày ACK</th>
            <th>Tình trạng</th>
            <th>Phí bảo hiểm</th>
            <th>Phí định kỳ</th>
            <th>Định kỳ đóng phí</th>
            <th>Tổng số phí đã thu</th>
            <th>Ngày đáo hạn</th>
            <th>Cập nhật</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($contracts as $contract)
        <tr>
            <td>{{ $contract->contract_code }}</td>
            <td><a href="{{route('admin.customer.detail', $contract->customer_id)}}">{{ $contract->customer_name }}</a></td>
            <td>{{ $contract->partner_text }}</td>
            <td>{{ $contract->partner_contract_code }}</td>
            <td>{{ $contract->product_text }}</td>
            <td>{{ $contract->sub_product_text }}</td>
            <td><a href="{{route('admin.user.detail', $contract->agent_code)}}">{{ $contract->agent_name }}</a></td>
            <td>{{ $contract->release_date }}</td>
            <td>{{ $contract->ack_date }}</td>
            <td>{{ $contract->status_text }}</td>
            <td>{{ $contract->permium }}</td>
            <td>{{ $contract->premium_term }}</td>
            <td>{{ $contract->term_text }}</td>
            <td>{{ $contract->premium_received }}</td>
            <td>{{ $contract->maturity_date }}</td>
            <td><a href="{{route('admin.contract.edit', $contract->contract_code)}}"><i class="fa fa-edit"></i></a></td>
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