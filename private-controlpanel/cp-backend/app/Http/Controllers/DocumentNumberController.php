<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentNumberController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $data = DB::table('document_numbers')->get();

            return $this->successResponse($data, 'Document number setup retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve document numbers: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|array',
                'name.*' => 'required|string|min:1',
                'rd_document_number' => 'required|array',
                'rd_document_number.*' => 'required|string|min:1',
                'rd_revision' => 'required|array',
                'rd_revision.*' => 'required|string|min:1',
                'co_document_number' => 'required|array',
                'co_document_number.*' => 'required|string|min:1',
                'co_revision' => 'required|array',
                'co_revision.*' => 'required|string|min:1',
                'id' => 'array',
                'id.*' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data_arr = $request->all();
            if (!isset($data_arr['name']) || !is_array($data_arr['name']) || count($data_arr['name']) === 0) {
                return $this->validationErrorResponse(
                    ['name' => ['Please input document details before saving.']],
                    'Please input document details before saving.'
                );
            }
            $document_data = [];
            $created_count = 0;
            $updated_count = 0;

            for ($i = 0; $i < count($data_arr['name']); $i++) {
                if ($data_arr['name'][$i] != null) {
                    $document_data = [
                        'name' => $data_arr['name'][$i],
                        'rd_document_number' => $data_arr['rd_document_number'][$i],
                        'rd_revision' => $data_arr['rd_revision'][$i],
                        'co_document_number' => $data_arr['co_document_number'][$i],
                        'co_revision' => $data_arr['co_revision'][$i]
                    ];

                    $id = isset($data_arr['id'][$i]) ? $data_arr['id'][$i] : null;

                    if ($id) {
                        // Update existing record
                        DB::table('document_numbers')->where('id', $id)->update($document_data);
                        $updated_count++;
                    } else {
                        // Create new record
                        DB::table('document_numbers')->insert($document_data);
                        $created_count++;
                    }
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Document Number Setup',
                'activity' => 'Update',
                'description' => 'Updated document number information',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'created_count' => $created_count,
                'updated_count' => $updated_count,
                'total_processed' => $created_count + $updated_count
            ], 'Document numbers updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update document numbers: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $data = DB::table('document_numbers')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Document number not found');
            }

            return $this->successResponse($data, 'Document number retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve document number: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true, 'label' => 'Document Name'],
                    'rd_document_number' => ['type' => 'text', 'required' => true, 'label' => 'RD Document Number'],
                    'rd_revision' => ['type' => 'text', 'required' => true, 'label' => 'RD Revision'],
                    'co_document_number' => ['type' => 'text', 'required' => true, 'label' => 'CO Document Number'],
                    'co_revision' => ['type' => 'text', 'required' => true, 'label' => 'CO Revision']
                ]
            ], 'Create document number form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $data = DB::table('document_numbers')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Document number not found');
            }

            return $this->successResponse($data, 'Document number edit data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load document number edit data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:1',
                'rd_document_number' => 'required|string|min:1',
                'rd_revision' => 'required|string|min:1',
                'co_document_number' => 'required|string|min:1',
                'co_revision' => 'required|string|min:1'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $document = DB::table('document_numbers')->where('id', $id)->first();

            if (!$document) {
                return $this->notFoundResponse('Document number not found');
            }

            $document_data = [
                'name' => $request->name,
                'rd_document_number' => $request->rd_document_number,
                'rd_revision' => $request->rd_revision,
                'co_document_number' => $request->co_document_number,
                'co_revision' => $request->co_revision
            ];

            DB::table('document_numbers')->where('id', $id)->update($document_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Document Number Setup',
                'activity' => 'Update',
                'description' => 'Updated document number: ' . $request->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Document number updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update document number: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $document = DB::table('document_numbers')->where('id', $id)->first();

            if (!$document) {
                return $this->notFoundResponse('Document number not found');
            }

            DB::table('document_numbers')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Document Number Setup',
                'activity' => 'Delete',
                'description' => 'Deleted document number: ' . $document->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Document number deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete document number: ' . $e->getMessage());
        }
    }
}
