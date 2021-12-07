<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Nhân viên</th>
            <th>Chức vụ mới</th>
            <th>Chức vụ cũ</th>
            <th>Có hiệu lực từ</th>
            <th>Cập nhật</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($promotions as $promotion)
        <tr>
            <td><a href="{{route('admin.promotion_up.detail', $promotion->id)}}">{{ $promotion->agent_text }}</a></td>
            <td>{{ $promotion->new_designation_text }}</td>
            <td>{{ $promotion->old_designation_text }}</td>
            <td>{{ $promotion->valid_month }}</td>
            <td><a href="{{route('admin.promotion_up.edit', $promotion->id)}}"><i class="fa fa-edit"></i></a></td>
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