<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\OjtInformations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \NumberFormatter;
use App\Traits\ApiResponse;

class OJTCertificateReportController extends Controller
{
    use ApiResponse, GeneratesPdf;
use App\Traits\GeneratesPdf;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        try {
            $data  = DB::table('ojt_informations')
                ->select('ojt_informations.id', 'ojt_informations.name', 'ojt_informations.date_start', 'ojt_informations.date_end', 'ojt_informations.hours', 'ojt_informations.signatory_name', 'ojt_informations.signatory_position')
                ->orderBy('ojt_informations.name', 'asc')
                ->get();

            return $this->successResponse($data, 'OJT certificates retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve OJT certificates: ' . $e->getMessage());
        }
    }

    public function add(Request $request)
    {
        try {
            $ojt = DB::table('ojt_informations')->orderBy('name', 'desc')->get();
            return $this->successResponse($ojt, 'OJT certificate form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load OJT certificate form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name'              => 'required|unique:ojt_informations',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = array(
                'name' => $request->name,
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'hours' => $request->hours,
                'signatory_name' => $request->signatory_name,
                'signatory_position' => $request->signatory_position,
            );

            $ojt = OjtInformations::create($data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'OJT Certificate',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on ojt information.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $ojt->id], 'You have successfully added OJT Information!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add OJT Information: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $ojt  = DB::table('ojt_informations')
                ->select('ojt_informations.id', 'ojt_informations.name', 'ojt_informations.date_start', 'ojt_informations.date_end', 'ojt_informations.hours', 'ojt_informations.signatory_name', 'ojt_informations.signatory_position')
                ->where('ojt_informations.id', $id)
                ->get();

            if ($ojt->isEmpty()) {
                return $this->notFoundResponse('OJT certificate not found');
            }

            return $this->successResponse($ojt, 'OJT certificate data for editing retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve OJT certificate for editing: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name'              => 'required',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $ojt = DB::table('ojt_informations')->where('id', $id)->first();
            if (!$ojt) {
                return $this->notFoundResponse('OJT certificate not found');
            }

            $data = array(
                'name' => $request->name,
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'hours' => $request->hours,
                'signatory_name' => $request->signatory_name,
                'signatory_position' => $request->signatory_position,
            );

            DB::table('ojt_informations')->where('id', $id)->update($data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'HR Module',
                'menu'    => 'OJT Certificate',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on OJT Informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'You have successfully updated OJT Information!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update OJT Information: ' . $e->getMessage());
        }
    }

    public function print($id)
    {
        try {
            $validator = \Validator::make(['id' => $id], [
                'id' => 'required|integer|exists:ojt_informations,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $image = base64_encode(file_get_contents(public_path('/dist/img/logo.png')));

            $document_no = DB::table('document_numbers')->where('id', 11)->get();

            $employees = DB::table('ojt_informations as a')
                ->select(
                    'a.id',
                    'a.name',
                    'a.date_start',
                    'a.date_end',
                    'a.hours',
                    'a.signatory_name',
                    'a.signatory_position'
                )
                ->where('a.id', $id)
                ->get();

            if ($employees->isEmpty()) {
                return $this->notFoundResponse('OJT certificate not found');
            }

            if ($document_no->isNotEmpty()) {
                $footer = [
                    'document_no' => $document_no[0]->co_document_number,
                    'revision' => $document_no[0]->co_revision,
                ];
            } else {
                $footer = [
                    'document_no' => '',
                    'revision' => '',
                ];
            }

            $pdf = PDF::loadView('ojt_certificates.ojt_certificate_print', compact('employees', 'image', 'footer'))->setOptions(['defaultFont' => 'sans-serif']);
            $pdf->setPaper('A4');
            $pdfContent = $pdf->output();

            $filename = 'ojt_certificate_' . $id . '_' . date('Y-m-d') . '.pdf';
            
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to generate OJT certificate PDF: ' . $e->getMessage());
        }
    }
}
