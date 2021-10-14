<?php
namespace App;

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
        foreach($rows as $row) {
            if($row[0] == "STT")
                continue;
    
            $identity_alloc_date = Carbon::createFromFormat('m/d/Y', is_numeric($row[7]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[7])->format('m/d/Y') : $row[7])->format('Y-m-d');
            $day_of_birth = Carbon::createFromFormat('d-m-Y', $row[2] . '-' . $row[3] . '-' . $row[4])->format('Y-m-d');
            $marital_status_code = strtolower($row[12]) == 'kết hôn' ? 'M' : (strtolower($row[12]) == 'độc thân' ? 'S' : (strtolower($row[12]) == 'ly hôn' ? 'D' : ''));
            $designation_code = str_replace(['"', 'TNDA'], '', $row[13]);
            $IFA_supervisor_designation_code = str_replace(['"', 'TNDA'], '', $row[19]);
            $user = [
                'fullname' => $row[1],
                'day_of_birth' => $day_of_birth,
                'gender' => strtolower($row[5]) == 'nam' ? 0 : 1,
                'identity_num' => $row[6],
                'identity_alloc_date' => $identity_alloc_date,
                'identity_alloc_place' => $row[8],
                'resident_place' => $row[9],
                'email' => $row[10],
                'mobile_phone' => $row[11],
                'marital_status_code' => $marital_status_code,
                'IFA_start_date' => null,
                'IFA_branch' => null,
                'IFA' => null,
                'designation_code' => $designation_code,
                'IFA_ref_code' => trim($row[14]),
                'IFA_ref_name' => $row[15],
                'IFA_supervisor_code' => trim($row[17]),
                'IFA_supervisor_name' => $row[18],
                'IFA_supervisor_designation_code' => $IFA_supervisor_designation_code,
                'IFA_TD_code' => null,
                'IFA_TD_name' => $row[25],
            ];
            $reference = User::where(['username' => $user['IFA_ref_code']])->orWhere(['identity_num' => $user['IFA_ref_code']])->first();
            if ($reference) {
                $user['reference_code'] = $reference->agent_code;
            }
            $supervisor = User::where(['username' => $user['IFA_supervisor_code']])->orWhere(['identity_num' => $user['IFA_supervisor_code']])->first();
            if ($supervisor) {
                $user['supervisor_code'] = $supervisor->agent_code;
            }
            $user['alloc_code_date'] = Carbon::now()->format('Y-m-d');
            $user['promote_date'] = Carbon::now()->format('Y-m-d');
            $user['password'] = Hash::make($user['identity_num']);
            $user['highest_designation_code'] = $user['designation_code'];
            
            $data[] = $user;
        }
        $this->data = $data;
    }
}