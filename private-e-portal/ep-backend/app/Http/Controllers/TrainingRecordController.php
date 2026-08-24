<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingRecordController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $user = Auth::user();
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            $query = DB::table('training_records')->orderBy('created_at', 'desc');

            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                $query->where('user_id', $user->id);
            }

            $records = $query->get()->map(function ($row) {
                return [
                    'id' => $row->id,
                    'training_name' => $row->training_name,
                    'date_attended' => $row->date_attended,
                    'duration' => $row->duration,
                    'provider' => $row->provider,
                    'has_certificate' => (bool) $row->has_certificate,
                    'notes' => $row->notes,
                    'created_at' => $row->created_at,
                ];
            });

            return $this->successResponse(['records' => $records]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load training records: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'training_name' => 'required|string|max:500',
                'date_attended' => 'required|date',
                'duration' => 'required|integer|min:1',
                'provider' => 'required|string|max:500',
                'has_certificate' => 'nullable|boolean',
                'notes' => 'nullable|string|max:2000',
            ]);

            $user = Auth::user();
            $employee = DB::table('employees')
                ->where('employee_no', $user->employee_no)
                ->first();

            $id = DB::table('training_records')->insertGetId([
                'user_id' => $user->id,
                'employee_id' => $employee->id ?? null,
                'training_name' => $data['training_name'],
                'date_attended' => $data['date_attended'],
                'duration' => $data['duration'],
                'provider' => $data['provider'],
                'has_certificate' => $data['has_certificate'] ?? false,
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $record = DB::table('training_records')->where('id', $id)->first();

            Audit::create([
                'user_id' => $user->id,
                'module' => 'HR Module',
                'menu' => 'Training Record',
                'activity' => 'Add',
                'description' => 'Added training record: ' . $data['training_name'],
            ]);

            return $this->successResponse($record, 'Training record added successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save training record: ' . $e->getMessage());
        }
    }
}