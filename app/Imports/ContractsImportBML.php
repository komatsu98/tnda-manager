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
            $premium = $row[16];
            $premium_received = $row[17];
            $premim_term = $premium_received; // mặc định số phí nhận được là đủ
            $term_code = 'y'; // year
            $contract_year = 1;

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
                        'partner_code' => 'BML'
                    ],
                    'customer' => [
                        'fullname' => $customer_name,
                        // 'day_of_birth' => $customer_day_of_birth,
                        'identity_num' => $customer_identity_num,
                        'address' => $customer_address,
                        'mobile_phone' => $customer_phone,
                        'email' => $customer_email,
                        'type' => $customer_type
                    ],
                    'products' => []
                ];
            }
            if (!isset($data[$partner_contract_code]['products'][$product_code])) {
                $data[$partner_contract_code]['products'][$product_code] = [
                    'premium' => $premium,
                    'premium_term' => $premium,
                    'transactions' => []
                ];
            }
            $data[$partner_contract_code]['products'][$product_code]['transactions'][] = [
                'premium_received' => $premium,
                'trans_date' => $submit_date,
            ];
        }
        $this->data = $data;
    }
}
