<?php

namespace App\Imports;

use Carbon\Carbon;

use App\Util;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ContractsImportFWD implements ToCollection
{
    public $data;
    public function collection(Collection $rows)
    {
        $data = [];
        foreach ($rows as $i => $row) {
            if ($i < 1 || !$row[0]) {
                continue;
            }
            foreach ($row as $key => $field) {
                $row[$key] = trim($field);
            }

            $agent_code = str_replace(['TNDA'], '', $row[3]);
            $partner_contract_code = $row[5];
            $submit_date = Util::parseDateExcel($row[6], 'd/m/Y', 'Y-m-d');
            $ack_date = Util::parseDateExcel($row[7], 'd/m/Y', 'Y-m-d');
            $status_code = $row[8];
            $contract_year = $row[9];
            $customer_name = $row[10];
            $customer_type = $row[11] == 'cá nhân' ? 1 : 2;
            $customer_identity_num = $row[12];
            $customer_email = $row[13];
            $customer_phone = $row[14];
            // $customer_address = $row[13];
            $premium_received = $row[17];
            $premium_term = $premium_received; // mặc định số phí nhận được là đủ
            $product_code = $row[21];
            $term_code = $this->getTermCodeFromText($row[22]); // year
            $premium = $term_code == 'y' ? $premium_received : ($term_code == 'm6' ? intval($premium_received) * 2 : intval($premium_received) * 12);

            $release_date = Util::parseDateExcel($row[23], 'd/m/Y', 'Y-m-d');
            $expire_date = Util::parseDateExcel($row[24], 'd/m/Y', 'Y-m-d');
            $maturity_date = $expire_date;
            $premium_factor = $row[26];
            $premium_factor_min = $row[27];
            $premium_factor_1 = $row[28];
            $premium_factor_2 = $row[29];
            $premium_factor_max = $row[30];
            if ($premium_factor_1 && $premium_factor < $premium_factor_1) $premium_factor_rank = 0;
            else if ($premium_factor_2 && $premium_factor < $premium_factor_2) $premium_factor_rank = 1;
            else if ($premium_factor_2 && $premium_factor >= $premium_factor_2) $premium_factor_rank = 2;

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
                        'partner_code' => 'FWD'
                    ],
                    'customer' => [
                        'fullname' => $customer_name,
                        'day_of_birth' => null,
                        'identity_num' => $customer_identity_num,
                        'address' => null,
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
                    'premium_term' => $premium_term,
                    'premium_factor_rank' => $premium_factor_rank,
                    // 'confirmation' => null,
                    'transactions' => []
                ];
                if (in_array($product_code, ['UL05', 'IL01', 'CC01', 'BP01'])) {
                    $data[$partner_contract_code]['perc']['main'] += $premium;
                    $data[$partner_contract_code]['perc']['main_code'] = $product_code;
                } else {
                    $data[$partner_contract_code]['perc']['sub'] += $premium;
                    $data[$partner_contract_code]['perc']['sub_code'][] = $product_code;
                }
            }
            if(!count($data[$partner_contract_code]['products'][$product_code]['transactions'])) {
                $data[$partner_contract_code]['products'][$product_code]['transactions'][] = [
                    'premium_received' => $premium_received,
                    'trans_date' => $submit_date
                ];
            } else $data[$partner_contract_code]['products'][$product_code]['transactions'][0]['premium_received'] += $premium_received;
        }
        $this->data = $data;
    }

    function getTermCodeFromText($term)
    {
        $term_code = '';
        switch ($term) {
            case 'Đóng phí nửa năm':
                $term_code = 'm6';
                break;
        }
        return $term_code;
    }
}
