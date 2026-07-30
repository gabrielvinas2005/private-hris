<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\SemesterRating;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SemesterRatingController extends Controller
{
    use ApiResponse;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get all semester ratings
     */
    public function index()
    {
        try {
            $data = DB::table('semester_ratings')->get();

            return $this->successResponse($data, 'Semester ratings retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve semester ratings: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for adding semester rating
     */
    public function add()
    {
        try {
            $employees = DB::table('employees')
                ->select(
                    'employees.id',
                    DB::raw("CONCAT(employees.first_name,' ',employees.last_name) as name")
                )
                ->orderBy('employees.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'employees' => $employees,
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Add semester rating form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load add form data: ' . $e->getMessage());
        }
    }

    /**
     * Store semester rating
     */
    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|unique:semester_ratings'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $sem_rating_data = array(
                'name' =>  $request->name,
                'active' => $request->has('active') ? true : false,
            );

            $semester_rating = SemesterRating::create($sem_rating_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Semester Rating Setup',
                'activity' => 'Add',
                'description' => 'Added ' . $request->name . ' on semester rating setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $semester_rating->id], 'Semester rating added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to add semester rating: ' . $e->getMessage());
        }
    }

    /**
     * Get form data for editing semester rating
     */
    public function edit($id)
    {
        try {
            $semester_rating = DB::table('semester_ratings')->where('id', $id)->first();

            if (!$semester_rating) {
                return $this->notFoundResponse('Semester rating not found');
            }

            $employees = DB::table('employees')
                ->select(
                    'employees.id',
                    DB::raw("CONCAT(employees.first_name,' ',employees.last_name) as name")
                )
                ->where(['active' => true, 'is_employee' => true])
                ->orderBy('employees.first_name', 'asc')
                ->get();

            return $this->successResponse([
                'semester_rating' => $semester_rating,
                'employees' => $employees
            ], 'Edit semester rating form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load edit form data: ' . $e->getMessage());
        }
    }

    /**
     * Update semester rating
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|unique:semester_ratings,name,' . $id
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $semester_rating = DB::table('semester_ratings')->where('id', $id)->first();

            if (!$semester_rating) {
                return $this->notFoundResponse('Semester rating not found');
            }

            $sem_rating_data = array(
                'name' =>  $request->name,
                'active' => $request->has('active') ? true : false,
            );

            DB::table('semester_ratings')->where('id', $id)->update($sem_rating_data);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Semester Rating Setup',
                'activity' => 'Update',
                'description' => 'Updated ' . $request->name . ' on semester rating setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(['id' => $id], 'Semester rating updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update semester rating: ' . $e->getMessage());
        }
    }

    /**
     * Show specific semester rating
     */
    public function show($id)
    {
        try {
            $data = DB::table('semester_ratings')->where('id', $id)->first();

            if (!$data) {
                return $this->notFoundResponse('Semester rating not found');
            }

            return $this->successResponse($data, 'Semester rating retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve semester rating: ' . $e->getMessage());
        }
    }

    /**
     * Create new semester rating form data
     */
    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'name' => ['type' => 'text', 'required' => true],
                    'active' => ['type' => 'checkbox', 'required' => false]
                ]
            ], 'Create semester rating form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    /**
     * Delete semester rating
     */
    public function destroy($id)
    {
        try {
            $semester_rating = DB::table('semester_ratings')->where('id', $id)->first();

            if (!$semester_rating) {
                return $this->notFoundResponse('Semester rating not found');
            }

            DB::table('semester_ratings')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Semester Rating Setup',
                'activity' => 'Delete',
                'description' => 'Deleted ' . $semester_rating->name . ' from semester rating setup.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Semester rating deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete semester rating: ' . $e->getMessage());
        }
    }
}
