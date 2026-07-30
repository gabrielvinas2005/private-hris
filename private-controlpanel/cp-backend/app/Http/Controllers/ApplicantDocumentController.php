<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicantDocumentController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * List all applicant documents
     */
    public function index()
    {
        try {
            $data = DB::table('acceptance_letter_documents')
                ->orderBy('name', 'asc')
                ->get();

            return $this->successResponse($data, 'Applicant documents retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve applicant documents: ' . $e->getMessage());
        }
    }

    /**
     * Store a new document
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string|min:3|max:255|unique:acceptance_letter_documents,name',
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = [
                'name' => $request->name,
                'active' => $request->boolean('active', true) ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now()
            ];

            $id = DB::table('acceptance_letter_documents')->insertGetId($data);

            //Save audit trail
            if (class_exists(Audit::class) && Auth::check()) {
                $data_audit = [
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Applicant Documents',
                    'activity' => 'Add',
                    'description' => 'Added applicant document: ' . $request->name,
                ];

                Audit::create($data_audit);
            }

            return $this->successResponse(['id' => $id], 'Applicant document added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add applicant document: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing document
     */
    public function update(Request $request, $id)
    {
        try {
            $doc = DB::table('acceptance_letter_documents')->where('id', $id)->first();
            if (!$doc) {
                return $this->notFoundResponse('Applicant document not found');
            }

            $validator = validator($request->all(), [
                'name' => 'required|string|min:3|max:255|unique:acceptance_letter_documents,name,' . $id,
                'active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = [
                'name' => $request->name,
                'active' => $request->boolean('active', true) ? 1 : 0,
                'updated_at' => now()
            ];

            DB::table('acceptance_letter_documents')->where('id', $id)->update($data);

            //Save audit trail
            if (class_exists(Audit::class) && Auth::check()) {
                $data_audit = [
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Applicant Documents',
                    'activity' => 'Update',
                    'description' => 'Updated applicant document: ' . $request->name,
                ];

                Audit::create($data_audit);
            }

            return $this->successResponse(['id' => $id], 'Applicant document updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update applicant document: ' . $e->getMessage());
        }
    }

    /**
     * Delete a document
     */
    public function destroy($id)
    {
        try {
            $doc = DB::table('acceptance_letter_documents')->where('id', $id)->first();
            if (!$doc) {
                return $this->notFoundResponse('Applicant document not found');
            }

            DB::table('acceptance_letter_documents')->where('id', $id)->delete();

            //Save audit trail
            if (class_exists(Audit::class) && Auth::check()) {
                $data_audit = [
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Applicant Documents',
                    'activity' => 'Delete',
                    'description' => 'Deleted applicant document: ' . $doc->name,
                ];

                Audit::create($data_audit);
            }

            return $this->successResponse(['id' => $id], 'Applicant document deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete applicant document: ' . $e->getMessage());
        }
    }
}
