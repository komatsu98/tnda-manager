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

class ContractsImportBML implements ToCollection
{
    public $data;

    public function collection(Collection $rows)
    {
        $data = [];
        // dd($rows); exit;
        foreach ($rows as $row) {
            if (strpos($row[2], "TNDA") === false) {
                continue;
            }
            foreach ($row as $key => $field) {
                $row[$key] = trim($field);
            }
            $application_no = $row[0];
            $policy_no = $row[1];
            $agent_code = str_replace(['TND', 'TNDA'], '', $row[2]);
            $region = $row[4];
            $po_name = $row[5];
            $payor_name = $row[6];
            $policy_status = $row[7];
            $product_code = $row[8];
            $sumof_IP_target = $row[9];
            $IP_excess = $row[10];
            $sumof_IP_total = $row[11];
            $sumof_APE = $row[12];
            $bill_freq = $row[13];
            $proposal_date = Util::parseDateExcel($row[14], 'm/d/Y', 'Y-m-d');
            $i_issue_date = Util::parseDateExcel($row[15], 'm/d/Y', 'Y-m-d');
            $ack_date = Util::parseDateExcel($row[16], 'm/d/Y', 'Y-m-d');

            if (!isset($data[$policy_no])) {
                $data[$policy_no] = [
                    'contract' => [
                        'application_no' => $application_no,
                        'agent_code' => $agent_code,
                        'policy_no' => $policy_no,
                        'region' => $region,
                        'po_name' => $po_name,
                        'payor_name' => $payor_name,
                        'policy_status' => $policy_status,
                        'proposal_date' => $proposal_date,
                        'i_issue_date' => $i_issue_date,
                        'ack_date' => $ack_date,
                    ],
                    'products' => []
                ];
            }
            if (!isset($data[$policy_no]['products'][$product_code])) {
                $data[$policy_no]['products'][$product_code] = [
                    'sumof_IP_target' => $sumof_IP_target,
                    'IP_excess' => $IP_excess,
                    'sumof_IP_total' => $sumof_IP_total,
                    'sumof_APE' => $sumof_APE,
                    'bill_freq' => $bill_freq,
                ];
            }
        }

        $this->data = $data;
    }
}
