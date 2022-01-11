<?php

namespace App;

use App\User;
use Illuminate\Support\Carbon;

class Util
{
    /**
     * The user that history belonged to.
     */

    public static function get_designation_code()
    {
        $designation_code = [
            'AG' => 'Đại lý',
            'DM' => 'Trưởng phòng kinh doanh',
            'SDM' => 'Trưởng phòng kinh doanh cấp cao',
            'AM' => 'Trưởng khu vực kinh doanh',
            'RD' => 'Giám đốc phát triển kinh doanh vùng ',
            'SRD' => 'Giám đốc phát triển kinh doanh vùng cấp cao',
            'TD' => 'Giám đốc phát triển kinh doanh miền',
            'PGD' => 'Phó Tổng Giám đốc',
            'GD' => 'Tổng Giám đốc',
            'ADMIN' => 'Admin'
        ];
        return $designation_code;
    }

    public static function get_contract_search_type_code()
    {
        $contract_search_type_code = [
            "S1C" => "HSYCBH nộp trong tháng",
            "S2C" => "Hợp đồng phát hành trong tháng",
            "S3C" => "Hồ sơ nộp trong tháng bị từ chối"
        ];
        return $contract_search_type_code;
    }

    public static function get_contract_term_code()
    {
        $contract_term_code = [
            "y" => "Hàng năm",
            "m" => "Hàng tháng",
            "q" => "Hàng quý",
            "m6" => "Nửa năm",
        ];
        return $contract_term_code;
    }

    public static function get_contract_bg_color()
    {
        // pending: '#F26A11',
        // success: '#22D69F',
        // fail: '#C7171B',
        $contract_bg_color = [
            "SM" => "#F26A11",
            "21" => "#F26A11",
            "RL" => "#22D69F",
            "MA" => "#F26A11",
            "LA" => "#C7171B"
        ];
        return $contract_bg_color;
    }

    public static function get_contract_renewal_status_code()
    {
    }
    public static function get_contract_status_code()
    {
        $contract_status_code = [
            // "AP" => "Hiệu lực",
            // "CF" => "Vô hiệu hợp đồng",
            // "CP" => "CP",
            // "DC" => "Từ chối",
            // "DH" => "Giải quyết quyền lợi bảo hiểm tử vong",
            // "EX" => "Đáo hạn",
            // "FL" => "Hủy hợp đồng trong thời gian cân nhắc",
            // "HP" => "PH RestPnd",
            // "IF" => "Hiệu lực",
            "LA" => "Mất hiệu lực",
            // "LS" => "Mất hiệu lực/Hủy hợp đồng",
            "MA" => "Đáo hạn",
            // "MP" => "Hiệu lực",
            // "NT" => "Hủy do quá hạn hoàn tất yêu cầu",
            // "P" => "Đang xư lý",
            // "PO" => "Tạm hoãn",
            // "PS" => "Hồ sơ yêu cầu bảo hiểm",
            // "PU" => "Duy trì hợp đồng vs số tiền BH giảm",
            // "RD" => "Đăng ký giải quyết quyền lợi bảo hiểm tử vong",
            // "SU" => "Hủy hợp đồng nhận GTHL",
            // "TR" => "Chấm dứt hợp đồng",
            // "UW" => "Thẩm định",
            // "VR" => "Reg Vested",
            // "WD" => "Hủy hồ sơ theo yêu cầu của khách hàng",
            // "DR" => "Từ chối bồi thường tử vong",
            // "NP" => "Đang thẩm định",
            // "VO" => "Yêu cầu mất hiệu lực",
            // "UA" => "PENDING",
            // "NR" => "NB Revert",
            "SM" => "Nộp vào",
            "21" => "21 ngày",
            "RL" => "Phát hành"
        ];
        return $contract_status_code;
    }

    public static function get_contract_info_await_code()
    {
        $contract_info_await_code = [
            "I1A" => "Thiếu giấy khám sức khỏe",
            "I2A" => "Thiếu xác nhận ABC"
        ];
        return $contract_info_await_code;
    }

    public static function get_product_code()
    {
        $product_code = [
            // "WP02" => "Bảo Hiểm Miễn Thu Phí Bệnh Hiểm Nghèo",
            // "UX01" => "Phí đóng thêm",
            // "AC01" => "Bảo Hiểm Tai Nạn Cá Nhân Toàn Diện",
            // "HS04" => "FWD CARE Bảo hiểm trợ cấp nằm viện",
            // "WP05" => "Bảo Hiểm Miễn Thu Phí Bệnh Hiểm Nghèo",
            // "MR01" => "FWD CARE Bảo hiểm sức khỏe",
            // "WP06" => "Bảo Hiểm Miễn Thu Phí Mở Rộng",
            // "WP08" => "FWD CARE Bảo hiểm miễn đóng nâng cao",
            // "QEF1" => "Family MCCI - Embedded Benefit for Child",
            // "JC01" => "Bảo Hiểm Bệnh Hiểm Nghèo Dành Cho Trẻ Em - Phí Thông Thường",
            // "UX02" => "Khoản Đầu Tư Thêm Dự Kiến",
            // "QWP1" => "Embedded Waiver",
            // "UL04" => "FWD Đón đầu thay đổi 2.0",
            // "AC03" => "FWD CARE Bảo hiểm tai nạn",
            // "HS03" => "Bảo Hiểm Hỗ Trợ Viện Phí do tai nạn",
            // "UL01" => "Linh Hoạt 3 Trong 1 - Quyền lợi cơ bản",
            // "AC02" => "Bảo Hiểm Tai Nạn Cá Nhân Toàn Diện",
            // "CI04" => "FWD CARE Bảo hiểm bệnh hiểm nghèo 2.0",
            // "HS01" => "Bảo hiểm trợ cấp viện phí và chi phí phẫu thuật",
            // "CI02" => "Bảo Hiểm Bổ Trợ Trợ Cấp Thu Nhập Khi Mắc Bệnh Hiểm Nghèo",
            // "HS02" => "Bảo Hiểm Trợ Cấp Viện Phí Và Phẫu Thuật",
            // "WP07" => "FWD CARE Bảo hiểm miễn đóng phí bệnh hiểm nghèo",
            // "CI01" => "Bảo Hiểm Bổ Trợ Trợ Cấp Thu Nhập Khi Mắc Bệnh Hiểm Nghèo",
            // "CI03" => "FWD CARE Bảo hiểm bệnh hiểm nghèo",
            // "MR02" => "FWD CARE Bảo hiểm sức khỏe 2.0",
            // "MC01" => "FWD Bảo hiểm hỗ trợ viện phí",
            // "TR01" => "Bảo Hiểm Tử Kỳ",
            // "WP10" => "FWD CARE Bảo hiểm miễn đóng phí nâng cao 2.0",
            // "UX03" => "Khoản Đầu Tư Thêm Dự Kiến",
            // "CC01" => "FWD Sống khỏe - Bảo hiểm bệnh ung thư",
            // "UL03" => "FWD Đón Đầu Thay Đổi",
            // "WP09" => "FWD CARE Bảo hiểm miễn đóng phí bệnh hiểm nghèo 2.0",
            // "TR02" => "FWD CARE Bảo hiểm tử vong và thương tật",
            // "BP01" => "FWD Bộ 3 bảo vệ",
            // "IX01" => "Khoản Đầu Tư Thêm",
            // "IL01" => "FWD Bộ đôi tài sản",
            // "EF02" => "FWD Cả nhà vui khỏe - Kế hoạch B",
            "CN.6" => "Bảo hiểm sức khỏe",
            "XC.1.1" => "Bảo hiểm xe máy",
            "XE" => "Bảo hiểm ô tô",
            "CORONA" => "Vi Cong Dong",
            "VBI.8" => "Bảo hiểm tai nạn",
            "VBI.9" => "Bảo hiểm sức khỏe",
            "VBI.10" => "Bảo hiểm ung thư",
            "VBI.11" => "Bảo hiểm ung thư vú",
            "VBI.12" => "Bảo hiểm bệnh hiểm nghèo",
            "VBI.13" => "Bảo hiểm du lịch nội địa",
            "VBI.14" => "Bảo hiểm du lịch quốc tế",
            "VBI.15" => "Bảo hiểm vật chất xe ô tô",
            "VBI.16" => "Bảo hiểm TNDS xe ô tô",
            "VBI.17" => "Bảo hiểm TNDS xe máy",
            "VBI.18" => "Bảo hiểm nhà tư nhân"
        ];
        return $product_code;
    }

    public static function get_sub_product_code()
    {
        $sub_product_code = [];
        return $sub_product_code;
    }

    public static function get_comission_perc($product_code)
    {
        $com = 0;
        switch ($product_code) {
            case 'CN.6':
            case 'XC.1.1':
            case 'XE':
            case 'CORONA': // ???????????
            case 'VBI.8':
            case 'VBI.9':
            case 'VBI.10':
            case 'VBI.11':
            case 'VBI.12':
            case 'VBI.13':
            case 'VBI.14':
            case 'VBI.15':
            case 'VBI.16':
            case 'VBI.17':
                $com = 0.07;
                break;
            case 'VBI.18':
                $com = 0.03;
                break;
        }
        return $com;
    }

    public static function get_income_code()
    {
        $income_code = [
            "ag_rwd_hldlth" => "Thưởng huấn luyện đại lý Tinh Hoa",
            "ag_hh_bhcn" => "Hoa hồng bán hàng cá nhân",
            "ag_rwd_dscnhq" => "Thưởng doanh số cá nhân hàng quý",
            "ag_rwd_tndl" => "Thưởng năm (gắn bó dài lâu) dành cho đại lý",
            "ag_rwd_tcldt_dm" => "Thưởng thăng cấp lần đầu tiên lên DM",
            "ag_rwd_tthd" => "Thưởng tái tục hợp đồng",
            "dm_rwd_hldlm" => "Thưởng huấn luyện đại lí mới",
            "dm_rwd_dscnht" => "Thưởng doanh số CÁ NHÂN hàng tháng",
            "dm_rwd_qlhtthhptt" => "Thưởng quản lý hàng THÁNG trên hoa hồng phòng trực tiếp",
            "dm_rwd_qlhqthhptt" => "Thưởng quản lý hàng QUÝ trên hoa hồng phòng trực tiếp",
            "dm_rwd_tnql" => "Thưởng năm (gắn bó lâu dài) dành cho quản lý",
            "dm_rwd_ptptt" => "Thưởng phát triển phòng (DM) trực tiếp",
            "dm_rwd_gt" => "Thưởng gián tiếp",
            "dm_rwd_tcldt_sdm" => "Thưởng thăng cấp lần đầu tiên lên SDM",
            "dm_rwd_tcldt_am" => "Thưởng thăng cấp lần đầu tiên lên AM",
            "dm_rwd_tcldt_rd" => "Thưởng thăng cấp lần đầu tiên lên RD",
            "dm_rwd_dthdtptt" => "Thưởng Duy Trì hợp đồng trên Phòng trực tiếp",
            "rd_rwd_dscnht" => "Thưởng doanh số CÁ NHÂN hàng tháng",
            "rd_hh_nsht" => "Hoa hồng năng suất hàng tháng ",
            "rd_rwd_dctkdq" => "Thưởng ĐẠT chỉ tiêu kinh doanh hàng QUÝ ",
            "rd_rwd_tndhkd" => "Thưởng năm (gắn bó lâu dài) dành cho cấp điều hành kinh doanh",
            "rd_rwd_dbgdmht" => "Thưởng đặc biệt hàng tháng dành cho giám đốc miền (TD) ",
            "rd_rwd_tcldt_srd" => "Thưởng thăng cấp lần đầu tiên lên SRD",
            "rd_rwd_tcldt_td" => "Thưởng thăng cấp lần đầu tiên lên TD",
            "rd_rwd_dthdtvtt" => "Thưởng Duy Trì Hợp Đồng trên Vùng trực tiếp"
        ];
        return $income_code;
    }

    public static function get_promotions($designation_code = '', $code = '')
    {
        $promotions = [
            [
                'code' => 'PRO_DM',
                'designation_code' => 'AG',
                'title' => 'Thăng cấp Trưởng phòng kinh doanh',
                'requiment_count' => 8,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (AG)',
                        'requirement_value' => 6,
                        'requirement_text' => '6 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 6,
                        'requirement_text' => '06 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số đại lý hoạt động (AA) trực tiếp GIỚI THIỆU trong 06 tháng vừa qua và còn làm việc tại thời điểm xét (mỗi AA chỉ được tính 1 lần)',
                        'requirement_value' => 4,
                        'requirement_text' => '04 đại lý',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng FYC trong 06 tháng vừa qua (bao gồm kết quả của cá nhân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 50000000,
                        'requirement_text' => '50 triệu đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.3,
                        'requirement_text' => '30%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của cá nhân đại lý tại thời điểm xét',
                        'requirement_value' => 0.75,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 7,
                        'title' => 'Hoàn thành khóa huấn luyện “Nền tảng quản lý và trả bài bằng Video”',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 8,
                        'title' => 'Không vi phạm quy chế Công ty',
                        'requirement_value' => 1,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'PRO_SDM',
                'designation_code' => 'DM',
                'title' => 'Thăng cấp Trưởng phòng kinh doanh cấp cao',
                'requiment_count' => 10,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (DM)',
                        'requirement_value' => 6,
                        'requirement_text' => '06 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số DM báo cáo TRỰC TIẾP cho quản lý này (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 3,
                        'requirement_text' => '03 DM',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 20,
                        'requirement_text' => '20 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng số đại lý hoạt động (AA) trực tiếp tuyển trong 06 tháng vừa qua và còn làm việc tại thời điểm xét (mỗi AA chỉ được tính 1 lần)',
                        'requirement_value' => 4,
                        'requirement_text' => '04 AA',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 100000000,
                        'requirement_text' => '100 triệu đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.3,
                        'requirement_text' => '30%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 7,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.8,
                        'requirement_text' => '80%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 8,
                        'title' => 'Hoàn thành khóa huấn luyện “Nền tảng quản lý”',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 9,
                        'title' => 'Hoàn thành khóa huấn luyện',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 10,
                        'title' => 'Không vi phạm quy chế Công ty',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'PRO_AM',
                'designation_code' => 'SDM',
                'title' => 'Thăng cấp Trưởng khu vực kinh doanh',
                'requiment_count' => 12,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (SDM)',
                        'requirement_value' => 6,
                        'requirement_text' => '06 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số SDM báo cáo TRỰC TIẾP cho quản lý này (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 3,
                        'requirement_text' => '03 SDM',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số DM, SDM trong toàn hệ thống (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 3,
                        'requirement_text' => '06 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 60,
                        'requirement_text' => '60 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tổng số đại lý hoạt động (AA) trực tiếp tuyển trong 06 tháng vừa qua và còn làm việc tại thời điểm xét (mỗi AA chỉ được tính 1 lần)',
                        'requirement_value' => 4,
                        'requirement_text' => '04 AA',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 300000000,
                        'requirement_text' => '300 triệu đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 7,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.3,
                        'requirement_text' => '30%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 8,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.75,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 9,
                        'title' => 'Hoàn thành khóa huấn luyện “Nền tảng quản lý”',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 10,
                        'title' => 'Hoàn thành khóa huấn luyện',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 11,
                        'title' => 'Hoàn thành khóa huấn luyện',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 12,
                        'title' => 'Không vi phạm quy chế Công ty',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'PRO_RD',
                'designation_code' => 'AM',
                'title' => 'Thăng cấp Trưởng khu vực kinh doanh',
                'requiment_count' => 9,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (AM)',
                        'requirement_value' => 12,
                        'requirement_text' => '12 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số SDM, AM báo cáo TRỰC TIẾP cho quản lý này (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 3,
                        'requirement_text' => '03 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số DM, SDM, AM trong toàn hệ thống (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 18,
                        'requirement_text' => '18 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 120,
                        'requirement_text' => '120 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 12 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 900000000,
                        'requirement_text' => '900 triệu đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.3,
                        'requirement_text' => '30%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 7,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.75,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 8,
                        'title' => 'Đạt kỳ phỏng vấn thăng cấp',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 9,
                        'title' => 'Không vi phạm quy chế Công ty',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'PRO_SRD',
                'designation_code' => 'RD',
                'title' => 'Thăng cấp Giám đốc phát triển kinh doanh vùng cấp cao',
                'requiment_count' => 10,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (RD)',
                        'requirement_value' => 12,
                        'requirement_text' => '12 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số RD báo cáo TRỰC TIẾP cho quản lý này (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 3,
                        'requirement_text' => '03 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số DM, SDM trong toàn hệ thống (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 36,
                        'requirement_text' => '36 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 300,
                        'requirement_text' => '300 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 12 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 2700000000,
                        'requirement_text' => '2,7 tỷ đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.3,
                        'requirement_text' => '30%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 7,
                        'title' => 'Tỷ lệ hoạt động trung bình 03 tháng gần nhất tính đến thời điểm xét',
                        'requirement_value' => 0.3,
                        'requirement_text' => '30%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 8,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.75,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 9,
                        'title' => 'Đạt kỳ phỏng vấn thăng cấp',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 10,
                        'title' => 'Không vi phạm quy chế Công ty',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'PRO_TD',
                'designation_code' => 'SRD',
                'title' => 'Thăng cấp Trưởng miền kinh doanh',
                'requiment_count' => 9,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (SRD)',
                        'requirement_value' => 12,
                        'requirement_text' => '12 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số RD, SRD báo cáo TRỰC TIẾP cho quản lý này (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 3,
                        'requirement_text' => '03 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 750,
                        'requirement_text' => '750 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 12 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 8100000000,
                        'requirement_text' => '8,1 tỷ đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.3,
                        'requirement_text' => '30%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỷ lệ hoạt động trung bình 03 tháng gần nhất tính đến thời điểm xét',
                        'requirement_value' => 0.25,
                        'requirement_text' => '25%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 7,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.70,
                        'requirement_text' => '70%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 8,
                        'title' => 'Đạt kỳ phỏng vấn thăng cấp',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 9,
                        'title' => 'Không vi phạm quy chế Công ty',
                        'requirement_value' => 1,
                        'requirement_text' => 'Bắt buộc',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'STAY_AG',
                'designation_code' => 'AG',
                'title' => 'Duy trì cấp Đại lý',
                'requiment_count' => 3,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Số lượng hợp đồng (CC) thực cấp trong 5 tháng vừa qua',
                        'requirement_value' => 1,
                        'requirement_text' => '01 CC',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng FYC thực cấp trong 5 tháng vừa qua',
                        'requirement_value' => 0,
                        'requirement_text' => 'triệu đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 tại thời điểm xét',
                        'requirement_value' => 0,
                        'requirement_text' => '%',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'STAY_DM',
                'designation_code' => 'DM',
                'title' => 'Duy trì chức danh Quản lý (DM)',
                'requiment_count' => 6,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (DM)',
                        'requirement_value' => 6,
                        'requirement_text' => '06 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số DM+ báo cáo TRỰC TIẾP cho quản lý này (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 0,
                        'requirement_text' => '0 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 5,
                        'requirement_text' => '05 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 12 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 45000000,
                        'requirement_text' => '45 triệu đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.2,
                        'requirement_text' => '20%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.75,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'STAY_SDM',
                'designation_code' => 'SDM',
                'title' => 'Duy trì chức danh Quản lý (SDM)',
                'requiment_count' => 6,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (SDM)',
                        'requirement_value' => 6,
                        'requirement_text' => '06 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số DM+ báo cáo TRỰC TIẾP cho quản lý này (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 2,
                        'requirement_text' => '02 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 15,
                        'requirement_text' => '15 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 12 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 90000000,
                        'requirement_text' => '90 triệu đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.2,
                        'requirement_text' => '20%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.75,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'STAY_AM',
                'designation_code' => 'AM',
                'title' => 'Duy trì chức danh Quản lý (AM)',
                'requiment_count' => 6,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (AM)',
                        'requirement_value' => 6,
                        'requirement_text' => '06 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số DM+ báo cáo TRỰC TIẾP cho quản lý này (không bao gồm bản thân quản lý được xét thăng cấp)',
                        'requirement_value' => 4,
                        'requirement_text' => '04 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 40,
                        'requirement_text' => '40 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 12 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 180000000,
                        'requirement_text' => '180 triệu đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.2,
                        'requirement_text' => '20%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.75,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'STAY_RD',
                'designation_code' => 'RD',
                'title' => 'Duy trì chức danh cấp điều hành kinh doanh (RD)',
                'requiment_count' => 7,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (RD)',
                        'requirement_value' => 24,
                        'requirement_text' => '24 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số DM+ báo cáo TRỰC TIẾP cho RD+ được đánh giá',
                        'requirement_value' => 3,
                        'requirement_text' => '03 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số RD+ báo cáo TRỰC TIẾP cho RD+ được đánh giá',
                        'requirement_value' => 0,
                        'requirement_text' => '0 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 75,
                        'requirement_text' => '75 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 12 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 900000000,
                        'requirement_text' => '900 triệu đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.2,
                        'requirement_text' => '20%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 7,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.75,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'STAY_SRD',
                'designation_code' => 'SRD',
                'title' => 'Duy trì chức danh cấp điều hành kinh doanh (SRD)',
                'requiment_count' => 7,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (SRD)',
                        'requirement_value' => 24,
                        'requirement_text' => '24 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số DM+ báo cáo TRỰC TIẾP cho RD+ được đánh giá',
                        'requirement_value' => 0,
                        'requirement_text' => '00 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số RD+ báo cáo TRỰC TIẾP cho RD+ được đánh giá',
                        'requirement_value' => 2,
                        'requirement_text' => '02 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 150,
                        'requirement_text' => '150 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 12 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 1800000000,
                        'requirement_text' => '1,8 tỷ đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.2,
                        'requirement_text' => '20%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 7,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.75,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ],
            [
                'code' => 'STAY_TD',
                'designation_code' => 'TD',
                'title' => 'Duy trì chức danh cấp điều hành kinh doanh (TD)',
                'requiment_count' => 7,
                'gained_count' => 0,
                'evaluation_date' => '',
                'requirements' => [
                    [
                        'id' => 1,
                        'title' => 'Thời gian tối thiểu ở vị trí hiện tại (SRD)',
                        'requirement_value' => 24,
                        'requirement_text' => '24 tháng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 2,
                        'title' => 'Tổng số DM+ báo cáo TRỰC TIẾP cho RD+ được đánh giá',
                        'requirement_value' => 0,
                        'requirement_text' => '00 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 3,
                        'title' => 'Tổng số RD+ báo cáo TRỰC TIẾP cho RD+ được đánh giá',
                        'requirement_value' => 4,
                        'requirement_text' => '04 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 4,
                        'title' => 'Tổng số nhân sự (HC) còn làm việc tại thời điểm xét (bao gồm bản thân đại lý được xét thăng cấp và các đại lý được giới thiệu)',
                        'requirement_value' => 300,
                        'requirement_text' => '300 nhân sự',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 5,
                        'title' => 'Tổng FYC của toàn bộ đội ngũ trong 12 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 3600000000,
                        'requirement_text' => '3,6 tỷ đồng',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 6,
                        'title' => 'Tỉ lệ FYP sản phẩm bổ sung bổ trợ/ Tổng FYP của toàn bộ đội ngũ trong 06 tháng vừa qua (bao gồm kết quả nhóm trực tiếp và gián tiếp)',
                        'requirement_value' => 0.2,
                        'requirement_text' => '20%',
                        'progress_text' => '',
                        'is_done' => 0
                    ],
                    [
                        'id' => 7,
                        'title' => 'Tỷ lệ duy trì hợp đồng K2 của toàn bộ đội ngũ (trực tiếp và gián tiếp) tại thời điểm xét',
                        'requirement_value' => 0.75,
                        'requirement_text' => '75%',
                        'progress_text' => '',
                        'is_done' => 0
                    ]
                ]
            ]
        ];
        if ($designation_code != '') {
            $promotions = array_filter($promotions, function ($var) use ($designation_code) {
                return ($var['designation_code'] == $designation_code);
            });
        }
        if ($code != '') {
            $promotions = array_filter($promotions, function ($var) use ($code) {
                return ($var['code'] == $code);
            });
        }
        return $promotions;
    }

    public static function get_rwd_things()
    {
        $rwd_things = [
            "ag_rwd_hldlth" => "Tham dự chương trình huấn luyện kỹ năng và du lịch dã ngoại",
            "ag_rwd_tcldt_dm" => "Tham dự tiệc vinh danh thăng cấp tại chương trình huấn luyện, du lịch.",
            "dm_rwd_hldlm" => "Nhận thư mời ĐẶC BIỆT tham dự chương trình huấn luyện kỹ năng và du lịch dã cùng đại lí Tinh Hoa.",
            "ag_rwd_tcldt_sdm" => "1 điện thoại di động (trị giá 10 triệu đồng).",
            "ag_rwd_tcldt_am" => "1 laptop (trị giá 15 triệu đồng).",
            "ag_rwd_tcldt_rd" => "1 xe máy (trị giá 30 triệu đồng).",
            "ag_rwd_tcldt_srd" => "1 xe máy tay ga (trị giá 45 triệu đồng).",
            "ag_rwd_tcldt_td" => "1 xe máy SH (trị giá 100 triệu đồng).",
        ];
        return $rwd_things;
    }


    public static function get_metric_code()
    {
        $metric_code = [
            'FYC' => 'FYC',
            'FYP' => 'FYP',
            'IP' => 'IP',
            'APE' => 'APE',
            'RYP' => 'RYP',
            'CC' => 'Số hợp đồng thực cấp',
            'K2' => 'Tỷ lệ duy trì hợp đồng',
            'AA' => 'Đại lý hoạt động',
        ];
        return $metric_code;
    }

    public static function get_partners()
    {
        $partners = [
            [
                'code' => 'VBI',
                'name' => 'Bảo hiểm VietinBank',
                'icon' => 'http://103.226.249.106/images/logo_vbi.png',
                'url' => 'https://apitest1.evbi.vn/MyVBI/webview_tnd/bos-suc-khoe-tnd.html'
            ],
            [
                'code' => 'FWD',
                'name' => 'Bảo hiểm FWD',
                'icon' => 'http://103.226.249.106/images/logo_fwd.png',
                'url' => ''
            ],
            [
                'code' => 'BML',
                'name' => 'Bảo hiểm BIDV Metlife',
                'icon' => 'http://103.226.249.106/images/logo_fwd.png',
                'url' => ''
            ],
            [
                'code' => 'BV',
                'name' => 'Bảo hiểm Bảo Việt',
                'icon' => 'http://103.226.249.106/images/logo_fwd.png',
                'url' => ''
            ]
        ];
        return $partners;
    }

    public static function get_marital_status_code()
    {
        $marital_status_code = [
            'M' => 'Kết hôn',
            'S' => 'Độc thân',
            'D' => 'Ly hôn'
        ];
        return $marital_status_code;
    }

    public static function get_default_avatar()
    {
        $default_avatar = 'http://103.226.249.106/images/avatar_1.png';
        return $default_avatar;
    }

    public static function get_instructions()
    {
        $instructions = [
            [
                'title' => 'Phần mềm này là gì?',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'TND Assurance App '
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Đang cập nhật...'
                    ]
                ]
            ],
            [
                'title' => 'Cấp lại mật khẩu',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'TND Assurance App '
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Đang cập nhật...'
                    ]
                ]
            ],
            [
                'title' => 'Số tiền lương tháng này xem ở đâu?',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'TND Assurance App '
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Đang cập nhật...'
                    ]
                ]
            ],
            [
                'title' => 'Hướng dẫn tải xuống tài liệu',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'TND Assurance App '
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Đang cập nhật...'
                    ]
                ]
            ],
            [
                'title' => 'Xem thông tin chi tiết hợp đồng ở đâu?',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'TND Assurance App '
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Đang cập nhật...'
                    ]
                ]
            ],
            [
                'title' => 'Cách sử dụng tra cứu khách hàng tiềm năng',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'TND Assurance App '
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Đang cập nhật...'
                    ]
                ]
            ],

        ];
        return $instructions;
    }

    public static function get_documents()
    {
        $documents = [
            [
                'name' => 'Tài liệu tham khảo (đang cập nhật)',
                'url' => '',
                'image' => 'http://103.226.249.106/images/logo.jpg'
            ]
        ];
        return $documents;
    }

    public static function get_customer_type()
    {
        $customer_types = [
            [
                '1' => 'Cá nhân',
                '2' => 'Doanh nghiệp'
            ]
        ];
        return $customer_types;
    }

    public static function get_highest_agent_code($is_special = false)
    {
        $ha = User::select('agent_code');
        if ($is_special) {
            $ha = $ha->where('agent_code', '<', '000021');
        } else {
            $ha = $ha->where('agent_code', '>', '000020');
        }
        $ha = $ha->orderBy('agent_code', 'desc')
            ->limit(1)
            ->first();
        if (!$ha) {
            return $is_special ? 0 : 20;
        }
        return $ha['agent_code'];
    }

    public static function get_TD($agent)
    {
        $supervisor = $agent->supervisor;

        while ($supervisor && $supervisor->designation_code != 'TD') {
            $supervisor = $supervisor->supervisor;
        }
        return $supervisor;
    }

    public static function sortByDesDesc($users)
    {
        usort($users, function ($a, $b) {
            $score_a = $this->get_designation_rank($a['designation_code']);
            $score_b = $this->get_designation_rank($b['designation_code']);
            if ($score_a == $score_b) return 0;
            return ($score_a < $score_b) ? 1 : -1;
        });
        return $users;
    }

    public static function get_saved_numbers()
    {
        return [68, 86, 100, 111, 123, 168, 186, 200, 222, 234, 246, 268, 286, 300, 303, 345];
    }

    public static function get_designation_rank($d)
    {
        $ranks = ['AG' => 1, 'DM' => 2, 'SDM' => 3, 'AM' => 4, 'RD' => 5, 'SRD' => 6, 'TD' => 7, 'PGD' => 8, 'GD' => 9, 'ADMIN' => 99];
        return $ranks[$d];
    }

    public static function parseDateExcel($d = '', $format = 'd/m/Y', $target_format = '')
    {
        $d = trim($d);
        if (!$d || $d == '') return null;
        $date = Carbon::createFromFormat($format, is_numeric($d) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($d)->format($format) : $d);
        if ($target_format != '') $date = $date->format('Y-m-d');
        return $date;
    }

    public static function calc_BML_comission($product_code, $contract_year, $premium, $sub_product_premium)
    {
        $comission_perc = 0;
        $sub_comission_perc = 0;
        $is_bonus = false;
        if ($sub_product_premium > $premium * 0.15) $is_bonus = true;
        switch ($product_code) {
            case 'WUL':
                $comission_perc = $is_bonus ? 0.4 : 0.35;
                $sub_comission_perc = 0;
                break;
            case 'ULI':
                $comission_perc = $contract_year >= 11 ? 0.3 : 0.2;
                $sub_comission_perc = 0.02;
                break;
            case 'TR02':
                $comission_perc = 0.15;
                $sub_comission_perc = 0.1;
                break;
            case 'HSR':
                $comission_perc = 0.1;
                $sub_comission_perc = 0;
                break;
            case 'WOP1':
            case 'CI03':
                if($contract_year <= 5) $comission_perc = 0.1;
                if($contract_year == 6) $comission_perc = 0.1;
                if($contract_year == 7) $comission_perc = 0.15;
                if($contract_year >= 8 && $contract_year <= 10) $comission_perc = 0.15;
                if($contract_year >= 10) $comission_perc = 0.2;
                break;
        }
        return $comission_perc * $premium + $sub_comission_perc * $sub_product_premium;
    }
}
