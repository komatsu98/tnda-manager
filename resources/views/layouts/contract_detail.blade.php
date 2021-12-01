<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Tên khách hàng</th>
            <th>Đối tác</th>
            <th>Số hợp đồng đối tác</th>
            <th>Sản phẩm</th>
            <th>Tư vấn viên</th>
            <th>Ngày hiệu lực</th>
            <th>Ngày ACK</th>
            <th>Tình trạng</th>
            <th>Phí phải thu</th>
            <th>Phí định kỳ</th>
            <th>Định kỳ đóng phí</th>
            <th>Phí đã thu</th>
            <th>Phí tái tục đã thu</th>
            <th>Hoa hồng</th>
            <th>Ngày đáo hạn</th>
            <th>Hồ sơ còn thiếu</th>
            <th>Cập nhật</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($contracts as $contract)
        <tr>
            <td><a href="{{route('admin.customer.detail', $contract->customer_id)}}">{{ $contract->customer_name }}</a></td>
            <td>{{ $contract->partner_text }}</td>
            <td>{{ $contract->partner_contract_code }}</td>
            <td><a href="{{route('admin.contract.products', $contract->id)}}">{{ $contract->product_text }}</a></td>
            <td><a href="{{route('admin.user.detail', $contract->agent_code)}}">{{ $contract->agent_name }}</a></td>
            <td>{{ $contract->release_date }}</td>
            <td>{{ $contract->ack_date }}</td>
            <td style="background-color:{{$contract->bg_color}}"><span class="" >{{ $contract->status_text }}</span></td>
            <td>{{ $contract->premium }}</td>
            <td>{{ $contract->premium_term }}</td>
            <td>{{ $contract->term_text }}</td>
            <td>{{ $contract->premium_received }}</td>
            <td>{{ $contract->renewal_premium_received }}</td>
            <td>{{ $contract->comission }}</td>
            <td>{{ $contract->maturity_date }}</td>
            <td>{{ $contract->info_awaiting_text }}</td>
            <td><a href="{{route('admin.contract.edit', $contract->id)}}"><i class="fa fa-edit"></i></a></td>
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