<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Mã nhân viên</th>
            <th>Họ và tên</th>
            <th>Ngày sinh</th>
            <th>Giới tính</th>
            <th>Số CMND</th>
            <th>Ngày cấp CMND</th>
            <th>Nơi cấp CMND</th>
            <th>Địa chỉ thường trú</th>
            <th>Email</th>
            <th>Điện thoại</th>
            <th>Tình trạng hôn nhân</th>
            <th>Ngày cấp code</th>
            <!-- <th>Đơn vị</th>
            <th>IFA</th> -->
            <th>Chức vụ</th>
            <th>Người giới thiệu</th>
            <th>Mã số người giới thiệu</th>
            <th>Quản lý trực tiếp</th>
            <th>Mã số Quản lý trực tiếp</th>
            <th>Chức vụ Quản lý trực tiếp</th>
            <th>Giám đốc kinh doanh miền</th>
            <th>Mã số Giám đốc kinh doanh miền</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($users as $user)
        <tr>
            <td>{{ "TNDA".$user->agent_code }}</td>
            <td>{{ $user->fullname }}</td>
            <td>{{ $user->day_of_birth }}</td>
            <td>{{ $user->gender_text }}</td>
            <td>{{ $user->identity_num }}</td>
            <td>{{ $user->identity_alloc_date }}</td>
            <td>{{ $user->identity_alloc_place }}</td>
            <td>{{ $user->resident_address }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->mobile_phone }}</td>
            <td>{{ $user->marital_status_text }}</td>
            <td>{{ $user->alloc_code_date }}</td>
            <!-- <td>{{ $user->IFA_branch }}</td>
            <td>{{ $user->IFA }}</td> -->
            <td>{{ $user->designation_code }}</td>
            <td>{{ $user->ref_name }}</td>
            <td>{{ $user->ref_code }}</td>
            <td>{{ $user->supervisor_name }}</td>
            <td>{{ $user->supervisor_code }}</td>
            <td>{{ $user->supervisor_designation_code }}</td>
            <td>{{ $user->TD_name }}</td>
            <td>{{ $user->TD_code }}</td>
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