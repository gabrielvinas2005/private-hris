<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\ImportSections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MigrateSectionController extends Controller
{
    function index()
    {
        try {
            $divisions = DB::table('divisions')->get();

            return $this->successResponse($divisions, 'Section migration data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve section migration data: ' . $e->getMessage());
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

            $rows = Excel::toArray(new ImportSections, $request->file('attachment'));
        $data = [];
        $migrated_data = 0;

        for ($i = 1; $i < count($rows[0]); $i++) {
            if (($rows[0][$i][0] <> null || $rows[0][$i][0] <> '') && ($rows[0][$i][1] <> null || $rows[0][$i][1] <> '')) {

                $division = DB::table('divisions')->select('id')->where('name', $rows[0][$i][2])->get();

                if ($division->isNotEmpty()) {
                    $division_id = $division[0]->id;
                } else {
                    $division_id = 0;
                }

                $data = array(
                    'code' => $rows[0][$i][0],
                    'name' => $rows[0][$i][1],
                    'division_id' => $division_id,
                    'section_chief_id' => 0,
                    'active' => true
                );

                try {
                    DB::table('sections')->updateOrInsert(['name' => $rows[0][$i][1]], $data);
                    $migrated_data++;
                } catch (\Throwable $th) {
                    return $this->errorResponse('Error Exception on Division. Please make sure division name for sections are supplied correctly.');
                }
            }
        }

        if ($migrated_data == 0) {
            return $this->errorResponse('No data to migrate.');
        }

        return $this->successResponse([
            'migrated_count' => $migrated_data,
            'message' => $migrated_data . ' Sections Successfully Migrated.'
        ], 'Sections migration completed successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to import sections: ' . $e->getMessage());
        }
    }
}
