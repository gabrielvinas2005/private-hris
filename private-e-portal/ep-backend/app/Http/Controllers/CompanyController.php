<?php

namespace App\Http\Controllers;

use Auth;
use Image;
use App\Audit;
use App\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use App\Traits\ApiResponse;

class CompanyController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Default all endpoints to require auth, except a small public endpoint used by the login page.
        $this->middleware('auth')->except(['publicInfo']);
    }

    /**
     * Public company info endpoint for unauthenticated pages (e.g. login).
     */
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
                    'logo_data_url' => null,
                ], 'Company data retrieved successfully');
            }

            $logoDataUrl = null;
            if (!empty($company->logo)) {
                // Stored as base64 jpeg in DB (see store()).
                $logoDataUrl = 'data:image/jpeg;base64,' . $company->logo;
            }

            return $this->successResponse([
                'id' => $company->id,
                'name' => $company->name ?? '',
                'address' => $company->address ?? '',
                'email' => $company->email ?? '',
                'telephone_no' => $company->telephone_no ?? '',
                'mobile_no' => $company->mobile_no ?? '',
                'logo_data_url' => $logoDataUrl,
            ], 'Company data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve company data: ' . $e->getMessage());
        }
    }

    public function index()
    {
        try {
            $company = DB::table('companies')->get();

            return $this->successResponse($company, 'Company data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve company data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name'      => 'required',
                'address'   => 'required',
                'email'     => 'required|email',
                'logo'     => 'image|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            if ($request->hasFile('logo')) {
                $image_file = $request->logo;
                $image = Image::make($image_file);

                Response::make($image->encode('jpeg'));

                $form_data = array(
                    'name'              => $request->name,
                    'address'           => $request->address,
                    'email'             => $request->email,
                    'telephone_no'      => isset($request->telephone_no) ? $request->telephone_no : '',
                    'mobile_no'         => isset($request->mobile_no) ? $request->mobile_no : '',
                    'logo'              => base64_encode($image),
                );
            } else {
                $form_data = array(
                    'name'              => $request->name,
                    'address'           => $request->address,
                    'email'             => $request->email,
                    'telephone_no'      => isset($request->telephone_no) ? $request->telephone_no : '',
                    'mobile_no'         => isset($request->mobile_no) ? $request->mobile_no : '',
                );
            }

            if ($request->id == null) {
                $id = 0 + DB::table('companies')->max('id');
                $id += 1;
            } else {
                $id = $request->id;
            }

            DB::unprepared('SET IDENTITY_INSERT companies ON');
            DB::table('companies')->updateOrInsert(['id' => $id], $form_data);
            DB::unprepared('SET IDENTITY_INSERT companies OFF');

            //Save audit trail
            $data_audit = array(
                'user_id'       => Auth::user()->id,
                'module'        => 'Control Panel',
                'menu'          => 'Company Setup',
                'activity'      => 'Update',
                'description'   => 'Update company informations.',
            );

            Audit::create($data_audit);

            return $this->successResponse([
                'id' => $id,
                'name' => $request->name,
                'address' => $request->address,
                'email' => $request->email,
                'telephone_no' => isset($request->telephone_no) ? $request->telephone_no : '',
                'mobile_no' => isset($request->mobile_no) ? $request->mobile_no : '',
                'has_logo' => $request->hasFile('logo')
            ], 'Company updated successfully!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update company: ' . $e->getMessage());
        }
    }
}
