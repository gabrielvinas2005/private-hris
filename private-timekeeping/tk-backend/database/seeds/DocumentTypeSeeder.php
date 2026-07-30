<?php

use App\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                "name" => "Appointment CS Form No. 33/ Malacañang Appointment (Presidential Appointee)",
                "active" => true,
            ],
            [
                "name" => "Position Description Form CS No. 1",
                "active" => true
            ],
            [
                "name" => "Oath of Office and/or Panunumpa sa Katungkulan CS Form No. 32",
                "active" => true,
            ],
            ["name" => "Personal Data Sheet CS Form No. 212", "active" => true],
            ["name" => "Affidavit of Applicants (Newly Hired)", "active" => true],
            ["name" => "Certificate of Assumption/Report for Duty", "active" => true],
            ["name" => "NBI Clearance", "active" => true],
            ["name" => "Medical Certificate CS Form No. 211", "active" => true],
            [
                "name" =>
                "Certificate of Eligibilities (C5-Professional, CS-Sub-Professional)/Board Exam/BAR Exam/PD 907/CESB Rating, etc.)",
                "active" => true,
            ],
            ["name" => "Transcript of Records", "active" => true],
            [
                "name" =>
                "Certification/Ombudsman & Sandiganbayan Clearances/Diploma/Commendation/Award",
                "active" => true,
            ],
            [
                "name" => "SALN (Sworn Statement of Assets, Liabilities, and Networth)",
                "active" => true,
            ],
            [
                "name" =>
                "Birth Certificate/Change of Name/Change of Birthdate & Birthplace",
                "active" => true,
            ],
            [
                "name" =>
                "Change of Status/Marriage Contract/Annulment of Marriage (Decision)/Death Certificate",
                "active" => true,
            ],
            [
                "name" =>
                "PhilHealth Member Data Record (MDR) and PAG-IBlG Member\'s Data Form (MDF)",
                "active" => true,
            ],
            [
                "name" => "GSIS (Membership Form/Policy Contract/Others)",
                "active" => true,
            ],
            ["name" => "Service Records from Previous Employers", "active" => true],
            [
                "name" =>
                "Clearance from Property, Records and Money Accountabilities from Previous Employer",
                "active" => true,
            ],
            [
                "name" =>
                "Authority To Transfer/Certificate of Leave Balance from Previous Employer (Transferred Leave)",
                "active" => true,
            ],
            ["name" => "Notice of Salary Adjustment", "active" => true],
            ["name" => "Notice of Step lncrement", "active" => true],
            [
                "name" =>
                "Certification of Yearly Accumulated Leave Credits/ Unused Leaves (LRA)",
                "active" => true,
            ],
            ["name" => "Administrative Order/Designation", "active" => true],
            ["name" => "Memorandum", "active" => true],
            ["name" => "Miscellaneous", "active" => true],
            ["name" => "Letter", "active" => true],
            [
                "name" =>
                "Disciplinary Actions/Decision/Resolution/Order/Motion for Reconsideration/lmplementation of Decision",
                "active" => true,
            ],
            ["name" => "Subpoena (Ombudsman/Court/NBl)", "active" => true],
            ["name" => "Contract of Service", "active" => true],
            ["name" => "Specimen Signature", "active" => true],
            [
                "name" =>
                "Request for Extension of Service/ Civil Service Commission Resolution",
                "active" => true,
            ],
            ["name" => "Terminal Leave (Supporting Documents)", "active" => true],
            ["name" => "Leave Records (A)", "active" => true],
            ["name" => "Leave Records (B)", "active" => true],
            ["name" => "Employee Service and Leave Records", "active" => true],
            ["name" => "Service Records (ESLR)", "active" => true],
            ["name" => "Photo", "active" => true],
        ];

        DocumentType::truncate();

        foreach ($data as $value) {
            DocumentType::create($value);
        }
    }
}
