<?php

namespace App;


class Util
{
    /**
     * The user that history belonged to.
     */
    
    public static function get_designation_code() {
        $designation_code = [
            'AG' => 'Đại lý',
            'DM' => 'Trưởng phòng kinh doanh',
            'SDM' => 'Trưởng phòng kinh doanh cấp cao',
            'AM' => 'Trưởng khu vực kinh doanh',
            'RD' => 'Giám đốc phát triển kinh doanh vùng ',
            'SRD' => 'Giám đốc phát triển kinh doanh vùng cấp cao',
            'TD' => 'Giám đốc phát triển kinh doanh miền',
        ];
        return $designation_code;
    }
    
    public static function get_contract_search_type_code() {
        $contract_search_type_code = [
            "S1C" => "HSYCBH nộp trong tháng",
            "S2C" => "Hợp đồng phát hành trong tháng",
            "S3C" => "Hồ sơ nộp trong tháng bị từ chối"
        ];
        return $contract_search_type_code;
    }

    public static function get_contract_renewal_status_code() {
        
    }
    public static function get_contract_status_code() {
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
            // "LA" => "Mất hiệu lực",
            // "LS" => "Mất hiệu lực/Hủy hợp đồng",
            // "MA" => "Đáo hạn",
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

    public static function get_contract_info_await_code() {
        $contract_info_await_code = [
            "I1A" => "Thiếu giấy khám sức khỏe",
            "I2A" => "Thiếu xác nhận ABC"
        ];
        return $contract_info_await_code;
    }

    public static function get_product_code() {
        $product_code = [
            "WP02" => "Bảo Hiểm Miễn Thu Phí Bệnh Hiểm Nghèo",
            "UX01" => "Phí đóng thêm",
            "AC01" => "Bảo Hiểm Tai Nạn Cá Nhân Toàn Diện",
            "HS04" => "FWD CARE Bảo hiểm trợ cấp nằm viện",
            "WP05" => "Bảo Hiểm Miễn Thu Phí Bệnh Hiểm Nghèo",
            "MR01" => "FWD CARE Bảo hiểm sức khỏe",
            "WP06" => "Bảo Hiểm Miễn Thu Phí Mở Rộng",
            "WP08" => "FWD CARE Bảo hiểm miễn đóng nâng cao",
            "QEF1" => "Family MCCI - Embedded Benefit for Child",
            "JC01" => "Bảo Hiểm Bệnh Hiểm Nghèo Dành Cho Trẻ Em - Phí Thông Thường",
            "UX02" => "Khoản Đầu Tư Thêm Dự Kiến",
            "QWP1" => "Embedded Waiver",
            "UL04" => "FWD Đón đầu thay đổi 2.0",
            "AC03" => "FWD CARE Bảo hiểm tai nạn",
            "HS03" => "Bảo Hiểm Hỗ Trợ Viện Phí do tai nạn",
            "UL01" => "Linh Hoạt 3 Trong 1 - Quyền lợi cơ bản",
            "AC02" => "Bảo Hiểm Tai Nạn Cá Nhân Toàn Diện",
            "CI04" => "FWD CARE Bảo hiểm bệnh hiểm nghèo 2.0",
            "HS01" => "Bảo hiểm trợ cấp viện phí và chi phí phẫu thuật",
            "CI02" => "Bảo Hiểm Bổ Trợ Trợ Cấp Thu Nhập Khi Mắc Bệnh Hiểm Nghèo",
            "HS02" => "Bảo Hiểm Trợ Cấp Viện Phí Và Phẫu Thuật",
            "WP07" => "FWD CARE Bảo hiểm miễn đóng phí bệnh hiểm nghèo",
            "CI01" => "Bảo Hiểm Bổ Trợ Trợ Cấp Thu Nhập Khi Mắc Bệnh Hiểm Nghèo",
            "CI03" => "FWD CARE Bảo hiểm bệnh hiểm nghèo",
            "MR02" => "FWD CARE Bảo hiểm sức khỏe 2.0",
            "MC01" => "FWD Bảo hiểm hỗ trợ viện phí",
            "TR01" => "Bảo Hiểm Tử Kỳ",
            "WP10" => "FWD CARE Bảo hiểm miễn đóng phí nâng cao 2.0",
            "UX03" => "Khoản Đầu Tư Thêm Dự Kiến",
            "CC01" => "FWD Sống khỏe - Bảo hiểm bệnh ung thư",
            "UL03" => "FWD Đón Đầu Thay Đổi",
            "WP09" => "FWD CARE Bảo hiểm miễn đóng phí bệnh hiểm nghèo 2.0",
            "TR02" => "FWD CARE Bảo hiểm tử vong và thương tật",
            "BP01" => "FWD Bộ 3 bảo vệ",
            "IX01" => "Khoản Đầu Tư Thêm",
            "IL01" => "FWD Bộ đôi tài sản",
            "EF02" => "FWD Cả nhà vui khỏe - Kế hoạch B"
        ];
        return $product_code;
    }

    public static function get_income_code() {
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
    
    public static function get_rwd_things() {
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
    

    public static function get_metric_code() {
        $metric_code = [
            'FYC' => 'FYC',
            'FYP' => 'FYP',
            'IP' => 'IP',
            'APE' => 'APE',
            'RYP' => 'RYP',
            'CC' => 'Số hợp đồng thực cấp',
            'K2' => 'Tỷ lệ duy trì hợp đồng',
            'AA' => 'Trạng thái lý hoạt động',
        ];
        return $metric_code;
    }

    public static function get_partners() {
        $partners = [
            [
                'code' => 'VBI',
                'name' => 'Bảo hiểm VietinBank',
                'icon' => 'http://103.226.249.106/images/logo_vbi.png',
                'url' => 'http://14.160.90.226:86/MyVBI/webview_tnd/bos-suc-khoe-tnd.html'
            ],
            [
                'code' => 'BIDV_METLIFE',
                'name' => 'Bảo hiểm phi nhân thọ - BIDV',
                'icon' => 'http://103.226.249.106/images/logo_bidv_metlife.png',
                'url' => 'http://14.160.90.226:86/MyVBI/webview_tnd/bos-suc-khoe-tnd.html'
            ],
            [
                'code' => 'FWD',
                'name' => 'Bảo hiểm Nhân thọ FWD',
                'icon' => 'http://103.226.249.106/images/logo_fwd.png',
                'url' => 'http://14.160.90.226:86/MyVBI/webview_tnd/bos-suc-khoe-tnd.html'
            ]
        ];
        return $partners;
    }

    public static function get_marital_status_code() {
        $marital_status_code = [
            'M' => 'Đã kết hôn',
            'S' => 'Độc thân',
            'D' => 'Đã ly hôn'
        ];
        return $marital_status_code;
    }

    public static function get_instructions() {
        $instructions = [
            [
                'title' => 'Phần mềm này là gì?',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'Phần mềm này là gì?
                        Phần mềm này là gì?'
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Phần mềm này là gì?
                        Phần mềm này là gì?'
                    ]
                ]
            ],
            [
                'title' => 'Cấp lại mật khẩu',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'Cấp lại mật khẩu
                        Cấp lại mật khẩu'
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Cấp lại mật khẩu
                        Cấp lại mật khẩu'
                    ]
                ]
            ],
            [
                'title' => 'Số tiền lương tháng này xem ở đâu?',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'Số tiền lương tháng này xem ở đâu?'
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Số tiền lương tháng này xem ở đâu?'
                    ]
                ]
            ],
            [
                'title' => 'Hướng dẫn tải xuống tài liệu',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'Hướng dẫn tải xuống tài liệu
                        Hướng dẫn tải xuống tài liệu'
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Hướng dẫn tải xuống tài liệu'
                    ]
                ]
            ],
            [
                'title' => 'Xem thông tin chi tiết hợp đồng ở đâu?',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'Xem thông tin chi tiết hợp đồng ở đâu?'
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Xem thông tin chi tiết hợp đồng ở đâu?'
                    ]
                ]
            ],
            [
                'title' => 'Cách sử dụng tra cứu khách hàng tiềm năng',
                'content' => [
                    [
                        'type' => 'text',
                        'value' => 'Cách sử dụng tra cứu khách hàng tiềm năng'
                    ],
                    [
                        'type' => 'image',
                        'value' => 'http://103.226.249.106/images/i_login.png'
                    ],
                    [
                        'type' => 'text',
                        'value' => 'Cách sử dụng tra cứu khách hàng tiềm năng'
                    ]
                ]
            ],
    
        ];
        return $instructions;
    }

}
