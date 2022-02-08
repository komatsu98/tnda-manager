<?php
namespace App\Imports;

use Carbon\Carbon;

use App\Util;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ContractsImportBV implements ToCollection
{
    public $data;
    public function collection(Collection $rows)
    {
        $data = [];
        // dd($rows); exit;
        foreach ($rows as $i => $row) {
            if ($i < 1) {
                continue;
            }
            foreach ($row as $key => $field) {
                $row[$key] = trim($field);
            }

            $agent_code = str_replace(['TNDA'], '', $row[1]);
            $partner_contract_code = $row[3];
            $customer_name = $row[4];
            $customer_type = $row[5] == 'Cá nhân' ? 1 : 2;
            $customer_identity_num = $row[6];
            $customer_phone = $row[7];
            $customer_email = $row[8];
            $customer_address = $row[9];
            $product_code = $row[10];
            $term_code = $this->getTermCodeFromText($row[11]);
            $premium = $row[12];
            $premium_received = $row[13];
            $premium_term = $premium_received; // mặc định số phí nhận được là đủ
            $submit_date = Util::parseDateExcel($row[15], 'm/d/Y', 'Y-m-d');
            $status_code = $this->getStatusCodeFromText($row[16]);
            $ack_date = Util::parseDateExcel($row[17], 'm/d/Y', 'Y-m-d');
            $release_date = Util::parseDateExcel($row[18], 'm/d/Y', 'Y-m-d');
            $expire_date = Carbon::createFromFormat('Y-m-d', $release_date)->add('year', 1)->add('day', -1)->format('Y-m-d');
            $maturity_date = $expire_date;
            $contract_year = 1;
            // $calc_status = $row[25] == "Đã chi trả hoa hồng" ? 1 : 0;

            if (!isset($data[$partner_contract_code])) {
                $data[$partner_contract_code] = [
                    'contract' => [
                        'agent_code' => $agent_code,
                        'partner_contract_code' => $partner_contract_code,
                        'ack_date' => $ack_date,
                        'submit_date' => $submit_date,
                        'release_date' => $release_date,
                        'expire_date' => $expire_date,
                        'maturity_date' => $maturity_date,
                        'status_code' => $status_code,
                        'term_code' => $term_code,
                        'contract_year' => $contract_year,
                        'partner_code' => 'BV',
                        // 'calc_status' => $calc_status
                    ],
                    'customer' => [
                        'fullname' => $customer_name,
                        'day_of_birth' => null,
                        'identity_num' => $customer_identity_num,
                        'address' => $customer_address,
                        'mobile_phone' => $customer_phone,
                        'email' => $customer_email,
                        'type' => $customer_type
                    ],
                    'products' => []
                ];
            }
            if (!isset($data[$partner_contract_code]['perc'])) $data[$partner_contract_code]['perc'] = ['main_code' => '', 'sub_code' => [], 'main' => 0, 'sub' => 0];

            if (!isset($data[$partner_contract_code]['products'][$product_code])) {
                $data[$partner_contract_code]['products'][$product_code] = [
                    'premium' => $premium,
                    'premium_term' => $premium,
                    // 'confirmation' => null,
                    'premium_factor_rank' => null,
                    'transactions' => []
                ];
            }
            if(!count($data[$partner_contract_code]['products'][$product_code]['transactions'])) {
                $data[$partner_contract_code]['products'][$product_code]['transactions'][] = [
                    'premium_received' => $premium_received,
                    'trans_date' => $release_date
                ];
            } else $data[$partner_contract_code]['products'][$product_code]['transactions'][0]['premium_received'] += $premium_received;
            $data[$partner_contract_code]['products'][$product_code]['premium'] += $premium;
            $data[$partner_contract_code]['products'][$product_code]['premium_term'] += $premium_term;
        }
        $this->data = $data;
    }

    function getTermCodeFromText($term) {
        $term_code = '';
        switch($term) {
            case 'Năm':
                $term_code = 'y';
                break;
        }
        return $term_code;
    }

    function getStatusCodeFromText($status) {
        $status_code = '';
        switch($status) {
            case 'ACTIVE':
                $status_code = 'IF';
                break;
        }
        return $status_code;
    }
}
