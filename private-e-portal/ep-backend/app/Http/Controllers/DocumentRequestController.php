<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentRequestController extends Controller
{
    use ApiResponse;

    protected $documentTypes = [
        ['value' => 'coe', 'label' => 'Certificate of Employment (COE)'],
        ['value' => 'health_clearance', 'label' => 'Health Clearance'],
        ['value' => 'workspace_clearance', 'label' => 'Workspace Clearance'],
    ];

    public function index()
    {
        try {
            $user = Auth::user();

            // TODO: confirm the actual admin/approver flag this app uses.
            // EmployeesController uses access_all_branches to decide branch-wide
            // visibility, so borrowing that here as the closest equivalent —
            // swap for whatever ServiceRenderedController actually checks.
            $isAdmin = (bool) ($user->access_all_branches ?? false);

            $query = DB::table('document_requests')->orderBy('created_at', 'desc');

            if (!$isAdmin) {
                $query->where('user_id', $user->id);
            }

            $requests = $query->get()->map(function ($row) {
                return $this->formatRequest($row);
            });

            return $this->successResponse([
                'document_types' => $this->documentTypes,
                'requests' => $requests,
            ]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load document requests: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'document_type' => 'required|in:coe,health_clearance,workspace_clearance',
                'purpose' => 'required|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);

            $user = Auth::user();

            $id = DB::table('document_requests')->insertGetId([
                'user_id' => $user->id,
                'document_type' => $data['document_type'],
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? null,
                'status' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $docRequest = DB::table('document_requests')->where('id', $id)->first();

            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module' => 'HR Module',
                'menu' => 'Document Request',
                'activity' => 'Add',
                'description' => 'Submitted a document request (' . $data['document_type'] . ').',
            );

            Audit::create($data_audit);

            return $this->successResponse($this->formatRequest($docRequest), 'Document request submitted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to submit document request: ' . $e->getMessage());
        }
    }

    protected function formatRequest($row)
    {
        $typeLabel = collect($this->documentTypes)->firstWhere('value', $row->document_type)['label']
            ?? $row->document_type;

        return [
            'id' => $row->id,
            'request_date' => $row->created_at,
            'document_type' => $typeLabel,
            'purpose' => $row->purpose,
            'notes' => $row->notes,
            'status_label' => $row->status,
        ];
    }
}