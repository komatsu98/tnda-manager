<?php

namespace App\Imports;

use App\Util;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ContractsImportVBI implements ToCollection
{
    public $data;

    public function collection(Collection $rows)
    {
        $data = [];
        // dd($rows); exit;
        foreach ($rows as $row) {
            if ($row[0] == "STT" || is_null($row[0])) {
                continue;
            }
            foreach ($row as $key => $field) {
                $row[$key] = trim($field);
            }
            $product_code = $row[7];
            $partner_contract_code = $row[8];
            $GCN = $row[9];
            $submit_date = Util::parseDateExcel($row[10], 'd/m/Y', 'Y-m-d');
            $release_date = Util::parseDateExcel($row[10], 'd/m/Y', 'Y-m-d');
            $expire_date = Util::parseDateExcel($row[12], 'd/m/Y', 'Y-m-d');
            $maturity_date = $expire_date;
            $customer_type = $row[14] == 'Cá nhân' ? 1 : 2;
            $customer_identity_num = $row[17];
            $customer_name = $row[18];
            $customer_day_of_birth = Util::parseDateExcel($row[19], 'd/m/Y', 'Y-m-d');
            $customer_address = $row[20];
            $customer_phone = $row[21];
            $customer_email = $row[23];
            $premium = $row[24];
            $status_code = $this->getStatusCodeFromText($row[30]);
            $agent_code = str_replace(['TND', 'TNDA'], '', $row[38]);
            $term_code = 'y'; // year
            // if($agent_code != '000022') continue;
            if (!isset($data[$partner_contract_code])) {
                $data[$partner_contract_code] = [
                    'contract' => [
                        'agent_code' => $agent_code,
                        'partner_contract_code' => $partner_contract_code,
                        // 'ack_date' => $release_date,
                        'submit_date' => $submit_date,
                        'release_date' => $release_date,
                        'expire_date' => $expire_date,
                        'maturity_date' => $maturity_date,
                        'status_code' => $status_code,
                        'term_code' => $term_code,
                        'contract_year' => 1,
                        'partner_code' => 'VBI'
                    ],
                    'customer' => [
                        'fullname' => $customer_name,
                        'day_of_birth' => $customer_day_of_birth,
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
                    'premium' => 0,
                    'premium_term' => 0,
                    // 'confirmation' => null,
                    'premium_factor_rank' => null,
                    'transactions' => []
                ];
            }
            if(!count($data[$partner_contract_code]['products'][$product_code]['transactions'])) {
                $data[$partner_contract_code]['products'][$product_code]['transactions'][] = [
                    'premium_received' => $premium,
                    'trans_date' => $release_date
                ];
            } else $data[$partner_contract_code]['products'][$product_code]['transactions'][0]['premium_received'] += $premium;
            $data[$partner_contract_code]['products'][$product_code]['premium'] += $premium;
            $data[$partner_contract_code]['products'][$product_code]['premium_term'] += $premium;
        }

        $this->data = $data;
    }

    function getStatusCodeFromText($status)
    {
        $status_code = '';
        switch ($status) {
            case 'Đã ký số':
                $status_code = 'RL';
                break;
        }
        return $status_code;
    }
}
