<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Tên</th>
            <th>Tháng</th>
            <th>Mục tiêu</th>
            <th>Yêu cầu</th>
            <th>Đạt được</th>
            <td>Điều kiện</td>
            <th>Trạng thái</th>
            <th>Cập nhật</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($promotions as $promotion)
        <tr>
            <td><a href="{{route('admin.promotion.detail', $promotion->id)}}">{{ $promotion->agent_text }}</a></td>
            <td>{{ substr($promotion->month, 0, 7) }}</td>
            <td>{{ $promotion->pro_text }}</td>
            <td>{{ $promotion->req_text }}</td>
            <td>{{ $promotion->progress_text }}</td>
            <td>{{ $promotion->requirement_text }}</td>
            <td>{{ $promotion->is_done_text }}</td>
            <td><a href="{{route('admin.promotion.edit', $promotion->id)}}"><i class="fa fa-edit"></i></a></td>
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