<?php

namespace App\Http\Controllers;

use App\Helpers\BranchHelper;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    use ApiResponse;

    public function publicInfo()
    {
        try {
            $company = DB::table('companies')->orderBy('id', 'asc')->first();

            if (!$company) {
                return $this->successResponse([
                    'name' => '',
                    'address' => '',
                    'email' => '',
                    'telephone_no' => '',
                    'mobile_no' => '',
                    'branch_code' => BranchHelper::getMainBranchCode(),
                    'logo_data_url' => null,
                ], 'Company data retrieved successfully');
            }

            $logoDataUrl = null;
            if (!empty($company->logo)) {
                $logoDataUrl = 'data:image/jpeg;base64,' . $company->logo;
            }

            return $this->successResponse([
                'id' => $company->id,
                'name' => $company->name ?? '',
                'address' => $company->address ?? '',
                'email' => $company->email ?? '',
                'telephone_no' => $company->telephone_no ?? '',
                'mobile_no' => $company->mobile_no ?? '',
                'branch_code' => BranchHelper::getMainBranchCode(),
                'logo_data_url' => $logoDataUrl,
            ], 'Company data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve company data: ' . $e->getMessage());
        }
    }
}
