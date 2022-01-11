<?php

namespace App\Imports;

use Carbon\Carbon;

use App\Util;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ContractsImportBML implements ToCollection
{
    public $data;
    public function collection(Collection $rows)
    {
        $data = [];
        foreach ($rows as $i => $row) {
            if ($i < 4) {
                continue;
            }
            foreach ($row as $key => $field) {
                $row[$key] = trim($field);
            }

            $partner_contract_code = $row[1];
            $agent_code = str_replace(['TNDA'], '', $row[2]);
            $customer_name = $row[8];
            $customer_type = $row[9] == 'Cá nhân' ? 1 : 2;
            $customer_identity_num = $row[10];
            $customer_phone = $row[11];
            $customer_email = $row[12];
            $customer_address = $row[13];
            $status_code = $row[14];
            $product_code = $row[15];
            $submit_date = Util::parseDateExcel($row[20], 'd/m/Y', 'Y-m-d');
            $release_date = Util::parseDateExcel($row[21], 'd/m/Y', 'Y-m-d');
            $ack_date = Util::parseDateExcel($row[22], 'd/m/Y', 'Y-m-d');
            $expire_date = Carbon::createFromFormat('Y-m-d', $release_date)->add('year', 1)->add('day', -1)->format('Y-m-d');
            $maturity_date = $expire_date;
            $premium_received = $row[16];
            $premium = $row[17]; // phí bảo hiểm quy năm
            $premium_term = $premium_received; // mặc định số phí nhận được là đủ
            $term_code = $this->getTermCodeFromText($row[18]); // year
            $contract_year = $row[19];
            $calc_status = $row[25] == "Đã chi trả thưởng bán hàng cá nhân" ? 1 : 0;

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
                        'partner_code' => 'BML',
                        'calc_status' => $calc_status
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
                    'premium_term' => $premium_term,
                    'confirmation' => null,
                    'premium_factor_rank' => null,
                    'transactions' => []
                ];
                if($product_code == 'WUL1') $data[$partner_contract_code]['perc']['main'] += $premium;
                else $data[$partner_contract_code]['perc']['sub'] += $premium;
            }
            $data[$partner_contract_code]['products'][$product_code]['transactions'][] = [
                'premium_received' => $premium,
                'trans_date' => $submit_date,
            ];
            

        }
        $this->data = $data;
    }

    function getTermCodeFromText($term)
    {
        $term_code = '';
        switch ($term) {
            case 1:
                $term_code = 'y';
                break;
            case 12:
                $term_code = 'm';
                break;
            case 2:
                $term_code = 'm6';
                break;
        }
        return $term_code;
    }
}
