<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Tên</th>
            <th>Tháng</th>
            <th>Mục tiêu</th>
            <th>Yêu cầu</th>
            <th>Đạt được</th>
            <th>Trạng thái</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($promotions as $promotion)
        <tr>
            <td><a href="{{route('admin.user.detail', $promotion->agent_code)}}">{{ $promotion->agent_text }}</a></td>
            <td>{{ substr($promotion->month, 0, 7) }}</td>
            <td>{{ $promotion->pro_text }}</td>
            <td>{{ $promotion->req_text }}</td>
            <td>{{ $promotion->progress_text }}</td>
            <td>{{ $promotion->is_done_text }}</td>
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