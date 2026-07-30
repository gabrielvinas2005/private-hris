<?php

namespace App\Http\Controllers;

use App\Audit;
use App\DocumentType;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentTypeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $document_types = DocumentType::all();

            return $this->successResponse($document_types, 'Document types retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve document types: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            $created_count = 0;
            $updated_count = 0;
            $errors = [];

            for ($i = 1; $i < count($data['name']); $i++) {
                if ($data['id'][$i] == 0) {
                    $id = DB::table('document_types')->max('id') + 1;
                } else {
                    $id = $data['id'][$i];
                }

                $validator = validator([
                    'name' => $data['name'][$i]
                ], [
                    'name' => 'required|min:3|max:255|unique:document_types,name,' . $id,
                ], [
                    'name.required' => 'Document Type Name is required.',
                    'name.min' => 'Document Type Name must be atleast 3 characters.',
                    'name.unique' => 'Document Type Name has already been taken.',
                ]);

                if ($validator->fails()) {
                    $errors[] = [
                        'index' => $i,
                        'errors' => $validator->errors()
                    ];
                    continue;
                }

                $document_type_data = [
                    'name' => $data['name'][$i],
                    'active' => isset($data['active'][$data['id'][$i]]) ? true : false,
                ];

                if ($data['id'][$i] == 0) {
                    DocumentType::create($document_type_data);
                    $created_count++;
                } else {
                    DocumentType::where('id', $id)->update($document_type_data);
                    $updated_count++;
                }
            }

            if (!empty($errors)) {
                return $this->validationErrorResponse(['bulk_errors' => $errors]);
            }

            // Save audit trail
            $data_audit = [
                'user_id' => auth()->user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Document Type Setup',
                'activity' => 'Update',
                'description' => 'Updated Document Type table information.',
            ];

            Audit::create($data_audit);

            return $this->successResponse([
                'created_count' => $created_count,
                'updated_count' => $updated_count,
                'total_processed' => $created_count + $updated_count
            ], 'Document types updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update document types: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $data = DB::table('document_types')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Document type not found');
            }

            return $this->successResponse($data, 'Document type data for deletion retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve document type for deletion: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $document_type = DB::table('document_types')->where('id', $id)->first();

            if (!$document_type) {
                return $this->notFoundResponse('Document type not found');
            }

            DB::table('document_types')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => auth()->user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Document Type Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Document Type: ' . $document_type->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(['deleted_id' => $id, 'deleted_name' => $document_type->name], 'Document type deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete document type: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $document_type = DocumentType::find($id);

            if (!$document_type) {
                return $this->notFoundResponse('Document type not found');
            }

            return $this->successResponse($document_type, 'Document type retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve document type: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true, 'label' => 'Document Type Name'],
                    'active' => ['type' => 'checkbox', 'required' => false, 'label' => 'Active Status']
                ]
            ], 'Create document type form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $document_type = DocumentType::find($id);

            if (!$document_type) {
                return $this->notFoundResponse('Document type not found');
            }

            return $this->successResponse($document_type, 'Document type edit data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load document type edit data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|min:3|max:255|unique:document_types,name,' . $id,
                'active' => 'boolean'
            ], [
                'name.required' => 'Document Type Name is required.',
                'name.min' => 'Document Type Name must be atleast 3 characters.',
                'name.unique' => 'Document Type Name has already been taken.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $document_type = DocumentType::find($id);

            if (!$document_type) {
                return $this->notFoundResponse('Document type not found');
            }

            $document_type->update([
                'name' => $request->name,
                'active' => $request->has('active') ? true : false
            ]);

            //Save audit trail
            $data_audit = array(
                'user_id' => auth()->user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Document Type Setup',
                'activity' => 'Update',
                'description' => 'Updated Document Type: ' . $request->name,
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Document type updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update document type: ' . $e->getMessage());
        }
    }
}
