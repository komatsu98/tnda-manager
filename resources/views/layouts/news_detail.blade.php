<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Tiêu đề</th>
            <th>Thể loại</th>
            <th>Link</th>
            <th>Tóm tắt</th>
            <th>Nội dung</th>
            <th>Link ảnh</th>
            <th>Trạng thái</th>
            <th>Ngày xuất bản</th>
            <th>Cập nhật</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($newss as $news)
        <tr>
            <td>{{ $news->title }}</td>
            <td>{{ $news->type_text }}</td>
            <td><a href="{{ $news->url }}">Link</a></td>
            <td>{{ $news->lead }}</td>
            <td>{{ $news->content }}</td>
            <td><a href="{{ $news->image }}">Link</a></td>
            <td>{{ $news->status_text }}</td>
            <td>{{ $news->public_at }}</td>
            <td><a href="{{route('admin.news.edit', $news->id)}}"><i class="fa fa-edit"></i></a></td>
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