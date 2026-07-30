<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Dotenv\Regex\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayrollCutOffController extends Controller
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

    public function index()
    {
        try {
            $data = DB::table('payroll_cutoffs as a')
                ->join('payroll_intervals as b', 'a.payroll_interval_id', '=', 'b.id')
                ->select(
                    'a.id as id',
                    'a.name as payroll_cutoff',
                    'b.name as payroll_interval'
                )
                ->orderby('a.name', 'asc')
                ->get();

            return $this->successResponse($data, 'Payroll cutoffs retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve payroll cutoffs: ' . $e->getMessage());
        }
    }

    public function add($id)
    {
        try {
            $intervals = DB::table('payroll_intervals')->where('active', true)->get();

            if ($id ==  0) {
                $dummy_cutoffs = array(
                    'id' => 0,
                    'name' => null,
                    'payroll_interval_id' => 0
                );

                $cutoffs = (object)$dummy_cutoffs;
                $cutoffs = collect([$cutoffs]);
            } else {
                $cutoffs = DB::table('payroll_cutoffs')->where('id', $id)->get();

                if ($cutoffs->isEmpty()) {
                    return $this->notFoundResponse('Payroll cutoff not found');
                }
            }

            return $this->successResponse([
                'intervals' => $intervals,
                'cutoffs' => $cutoffs
            ], 'Payroll cutoff form data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load payroll cutoff form data: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:payroll_cutoffs,name' . ($id ? ",$id" : ''),
                'payroll_interval_id' => 'required'
            ], [
                'name.required' => 'Payroll Cut-off Name is required.',
                'name.unique' => 'Payroll Cut-off Name must be unique.',
                'payroll_interval_id.required' => 'Payroll Interval is required.',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = array(
                'name' => $request->name,
                'payroll_interval_id' => $request->payroll_interval_id
            );

            //Save audit trail
            if ($id == 0) {
                DB::table('payroll_cutoffs')->Insert($data);
                $new_id = DB::getPdo()->lastInsertId();

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Payroll Cut-off Setup',
                    'activity' => 'Add',
                    'description' => 'Added Payroll Cut-off information',
                );

                return $this->successResponse([
                    'id' => $new_id,
                    'name' => $request->name,
                    'payroll_interval_id' => $request->payroll_interval_id
                ], 'You have successfully added payroll cut-off!');
            } else {
                DB::table('payroll_cutoffs')->updateOrInsert(['id' => $id], $data);

                $data_audit = array(
                    'user_id' => Auth::user()->id,
                    'module'  => 'Control Panel',
                    'menu'    => 'Payroll Cut-off Setup',
                    'activity' => 'Update',
                    'description' => 'Updated Payroll Cut-off information',
                );

                return $this->successResponse([
                    'id' => $id,
                    'name' => $request->name,
                    'payroll_interval_id' => $request->payroll_interval_id
                ], 'You have successfully updated payroll cut-off!');
            }

            Audit::create($data_audit);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save payroll cut-off: ' . $e->getMessage());
        }
    }
}
