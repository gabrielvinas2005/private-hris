<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\ImportOffices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MigrateOfficeController extends Controller
{
    function index()
    {
        try {
            $branches = DB::table('branches')->get();
            return $this->successResponse($branches, 'Office migration data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve office migration data: ' . $e->getMessage());
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

            $rows = Excel::toArray(new ImportOffices, $request->file('attachment'));
        $data = [];
        $migrated_data = 0;

        for ($i = 1; $i < count($rows[0]); $i++) {
            if (($rows[0][$i][0] <> null || $rows[0][$i][0] <> '') && ($rows[0][$i][1] <> null || $rows[0][$i][1] <> '')) {

                $branches = DB::table('branches')->select('id')->where('name', $rows[0][$i][3])->get();

                if ($branches->isNotEmpty()) {
                    $branch_id = $branches[0]->id;
                } else {
                    $branch_id = 0;
                }

                $data = array(
                    'code' => $rows[0][$i][0],
                    'name' => $rows[0][$i][1],
                    'functionality' => $rows[0][$i][2],
                    'is_academic' => false,
                    'active' => true,
                    'branch_id' => $branch_id,
                    'employee_id' => 0,
                );

                try {
                    DB::table('departments')->updateOrInsert(['name' => $rows[0][$i][1]], $data);
                    $migrated_data++;
                } catch (\Throwable $th) {
                    return $this->errorResponse('Error Exception on Branch. Please make sure branch/region for office are supplied correctly.');
                }
            }
        }

        if ($migrated_data == 0) {
            return $this->errorResponse('No data to migrate.');
        }

        return $this->successResponse([
            'migrated_count' => $migrated_data,
            'message' => $migrated_data . ' Offices Successfully Migrated.'
        ], 'Offices migration completed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to import offices: ' . $e->getMessage());
        }
    }
}
