<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\ImportEmploymentTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MigrateEmploymentTypeController extends Controller
{
    function index()
    {
        try {
            return $this->successResponse([], 'Migrate employment types data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load employment types migration data: ' . $e->getMessage());
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

            $rows = Excel::toArray(new ImportEmploymentTypes, $request->file('attachment'));
        $data = [];
        $migrated_data = 0;

        for ($i = 1; $i < count($rows[0]); $i++) {
            if ($rows[0][$i][0] <> null || $rows[0][$i][0] <> '') {
                $data = array(
                    'name' => $rows[0][$i][0],
                    'with_end_contract' => $rows[0][$i][1] == null ? false : $rows[0][$i][1],
                    'active' => true,
                );

                DB::table('employment_types')->updateOrInsert(['name' => $rows[0][$i][0]], $data);
                $migrated_data++;
            }
        }

        if ($migrated_data == 0) {
            return $this->errorResponse('No data to migrate.');
        }

        return $this->successResponse([
            'migrated_count' => $migrated_data,
            'message' => $migrated_data . ' Employment Types Successfully Migrated.'
        ], 'Employment types migration completed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to import employment types: ' . $e->getMessage());
        }
    }
}
