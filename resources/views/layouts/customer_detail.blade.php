<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Nhóm</th>
            <th>Tên</th>
            <th>Số CMND/MST</th>
            <th>Ngày sinh</th>
            <th>Email</th>
            <th>Điện thoại</th>
            <th>Địa chỉ</th>
            <th>Hợp đồng</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($customers as $customer)
        <tr>
            <td>{{ $customer->type_text }}</td>
            <td>{{ $customer->fullname }}</td>
            <td>{{ $customer->identity_num }}</td>
            <td>{{ $customer->day_of_birth }}</td>
            <td>{{ $customer->email }}</td>
            <td>{{ $customer->mobile_phone }}</td>
            <td>{{ $customer->address }}</td>
            <td><a href="{{ route('admin.customer.contracts', $customer->id) }}">{{ $customer->contract_count }} hợp đồng</a></td>
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