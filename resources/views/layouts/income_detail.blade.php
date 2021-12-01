<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Trường</th>
            @forelse ($incomes as $income)
            <th><a href="{{route('admin.user.detail', $income->agent_code)}}">{{ $income->agent_text }}</a></th>
            @empty
            <th>
                <center>Không có dữ liệu phù hợp</center>
            </th>
            @endforelse
        </tr>
    </thead>
    <tbody>
        @forelse ($list_income_code as $key => $name)
        <tr>
            <td>{{ $name }} </td>
            @forelse ($incomes as $income)
            <td>{{ object_get($income, "{$key}" ) }}</td>
            @empty
            <td>
                <center>Không có dữ liệu phù hợp</center>
            </td>
            @endforelse
        </tr>
        @empty
        <tr>
            <td colspan="4">
                <center>Không có dữ liệu phù hợp</center>
            </td>
        </tr>
        @endforelse
        <tr>
            <td>Tổng</td>
            @forelse ($incomes as $income)
            <td>{{$income->total}}</td>
            @empty
            <td>
                <center>Không có dữ liệu phù hợp</center>
            </td>
            @endforelse
        </tr>
    </tbody>
</table>