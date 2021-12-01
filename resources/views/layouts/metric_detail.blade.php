<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Tên</th>
            <th>Tháng</th>
            <th>FYC</th>
            <th>FYP</th>
            <th>CC</th>
            <th>K2</th>
            <th>AA</th>
            <th>Số đại lý trong đội ngũ</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($metrics as $metric)
        <tr>
            <td><a href="{{route('admin.user.detail', $metric->agent_code)}}">{{ $metric->agent_text }}</a></td>
            <td>{{ substr($metric->month, 0, 7) }}</td>
            <td>{{ $metric->FYC }}</td>
            <td>{{ $metric->FYP }}</td>
            <td>{{ $metric->CC }}</td>
            <td>{{ $metric->K2 }}</td>
            <td>{{ $metric->AA }}</td>
            <td>{{ $metric->AU }}</td>
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