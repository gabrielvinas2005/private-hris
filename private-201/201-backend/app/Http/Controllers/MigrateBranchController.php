<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Imports\ImportBranches;
use Illuminate\Support\Facades\DB;

class MigrateBranchController extends Controller
{
    function index()
    {
        try {
            return $this->successResponse([], 'Migrate branches data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load migrate branches data: ' . $e->getMessage());
        }
    }

    function import(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'attachment' => 'required|file|mimes:xlsx,xls,csv|max:2048'
            ], [
                'attachment.required' => 'Please select a file to upload.',
                'attachment.file' => 'The uploaded file is not valid.',
                'attachment.mimes' => 'The file must be an Excel file (xlsx, xls) or CSV file.',
                'attachment.max' => 'The file size must not exceed 2MB.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $rows = Excel::toArray(new ImportBranches, $request->file('attachment'));
        $data = [];
        $migrated_data = 0;

        for ($i = 1; $i < count($rows[0]); $i++) {
            if ($rows[0][$i][0] <> null || $rows[0][$i][0] <> '') {
                $data = array(
                    'name' => $rows[0][$i][0],
                    'is_main_branch' => $rows[0][$i][1],
                    'branch_head_id' => 0,
                );

                DB::table('branches')->updateOrInsert(['name' => $rows[0][$i][0]], $data);
                $migrated_data++;
            }
        }

        if ($migrated_data == 0) {
            return $this->errorResponse('No data to migrate.');
        }

        return $this->successResponse([
            'migrated_count' => $migrated_data,
            'message' => $migrated_data . ' Branches Successfully Migrated.'
        ], 'Branches migration completed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to import branches: ' . $e->getMessage());
        }
    }
}
