<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Contract;
use App\Comission;
use App\Transaction;
use App\Customer;
use App\Util;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class ContractsImportVBI implements ToCollection
{
    public $data;

    public function collection(Collection $rows)
    {
        $data = [];
        foreach($rows as $row) {
            if($row[0] == "STT") {
                continue;
            }
            $product_code = $row[7];
            $partner_product_code = $row[8];
            $submit_date = Carbon::createFromFormat('m/d/Y', is_numeric($row[10]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[10])->format('m/d/Y') : $row[10])->format('Y-m-d');
            $release_date = $row[11];
            $expire_date = Carbon::createFromFormat('m/d/Y', is_numeric($row[12]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[12])->format('m/d/Y') : $row[12])->format('Y-m-d');
            $maturity_date = Carbon::createFromFormat('Y-m-d', $expire_date)->addYear()->format('Y-m-d');
            $customer_type = $row[14] == 'Cá nhân' ? 1 : 2;
            $customer_identity_num = $row[17];
            $customer_name = $row[18];
            $customer_day_of_birth = $row[19];
            $customer_address = $row[20];
            $customer_phone = $row[21];
            $customer_email = $row[23];
            $premium = $row[26];
            $status_code = $this->getStatusCodeFromText($row[30]);
            $agent_code = str_replace(['TND', 'TNDA'], '', $row[38]);
            $comisison = round(Util::get_comission_perc($product_code) * $premium);
            if(!isset($data[$partner_product_code])) {
                $data[$partner_product_code] = [];
                $data[$partner_product_code] = [
                    'contract' => [
                        'agent_code' => $agent_code,
                        'customer_id' => '',
                        'partner_product_code' => $partner_product_code,
                        'submit_date' => $submit_date,
                        'release_date' => $release_date,
                        'expire_date' => $expire_date,
                        'maturity_date' => $maturity_date,
                        'status_code' => $status_code,
                        'premium' => $premium,
                        'total_premium_received' => $premium,
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
                    'transaction' => [],
                    'comission' => [],
                ];
            }
            $data[$partner_product_code]['transaction'][] = [
                'agent_code' => $agent_code,
                'contract_id' => '',
                'premium_received' => $premium,
                'product_code' => $product_code,
                'trans_date' => $submit_date
            ];
            $data[$partner_product_code]['comission'][] =[
                'agent_code' => $agent_code,
                'contract_id' => '',
                'amount' => $comisison,
                'received_date' => $submit_date
            ];
        }
        
        $this->data = $data;
    }

    function getStatusCodeFromText($status) {
        $status_code = '';
        switch($status) {
            case 'Đã ký số':
                $status_code = 'RL';
                break;
        }
        return $status_code;
    }

}
