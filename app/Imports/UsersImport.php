<?php
namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\User;
use App\Util;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class UsersImport implements ToCollection
{
    public $data;

    public function collection(Collection $rows)
    {
        $data = [];
        // echo "<pre>";print_r($rows); exit;
        foreach($rows as $row) {
            if($row[0] == "STT" || strlen($row[1]) > 0 || !$row[2])
                continue;
    
            $day_of_birth = Carbon::createFromFormat('d-m-Y', $row[3] . '-' . $row[4] . '-' . $row[5])->format('Y-m-d');
            $identity_alloc_date = Carbon::createFromFormat('m/d/Y', is_numeric($row[8]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[8])->format('m/d/Y') : $row[8])->format('Y-m-d');
            $marital_status_code = strtolower($row[14]) == 'kết hôn' ? 'M' : (strtolower($row[14]) == 'độc thân' ? 'S' : (strtolower($row[14]) == 'ly hôn' ? 'D' : ''));
            $designation_code = str_replace(['"', 'TNDA'], '', trim($row[15]));
            $IFA_ref_code = str_replace(['"', "'"], '', trim($row[16]));
            $IFA_supervisor_code = str_replace(['"', "'"], '', trim($row[18]));
            $IFA_supervisor_designation_code = str_replace(['"', "'"], '', trim($row[20]));
            $alloc_code_date = Carbon::createFromFormat('d/m/Y', is_numeric($row[23]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[23])->format('d/m/Y') : $row[23])->format('Y-m-d');
            $user = [
                'fullname' => $row[2],
                'day_of_birth' => $day_of_birth,
                'gender' => strtolower($row[6]) == 'nam' ? 0 : 1,
                'identity_num' => $row[7],
                'identity_alloc_date' => $identity_alloc_date,
                'identity_alloc_place' => $row[9],
                'resident_address' => $row[10],
                'email' => $row[11],
                'company_email' => $row[12],
                'mobile_phone' => $row[13],
                'marital_status_code' => $marital_status_code,
                'IFA_start_date' => null,
                'IFA_branch' => null,
                'IFA' => null,
                'designation_code' => $designation_code,
                'IFA_ref_code' => $IFA_ref_code,
                'IFA_ref_name' => $row[17],
                'IFA_supervisor_code' => $IFA_supervisor_code,
                'IFA_supervisor_name' => $row[19],
                'IFA_supervisor_designation_code' => $IFA_supervisor_designation_code,
                'IFA_TD_code' => null,
                'IFA_TD_name' => $row[22],
                'alloc_code_date' => $alloc_code_date,
                'promote_date' => $alloc_code_date
            ];
            
            $user['password'] = Hash::make($user['identity_num']);
            $user['password2'] = $user['password'];
            $user['highest_designation_code'] = $user['designation_code'];
            
            $data[] = $user;
        }
        
        // $data = Util::sortByDesDesc($data); 
        $this->data = $data;
    }

}

