<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EETEController extends Controller
{
    use ApiResponse;

    public function index()
    {
        try {
            $eete_ratings = DB::table('eete_ratings')->get();

            if ($eete_ratings->isEmpty()) {
                $eete_ratings = [
                    'id' => 0,
                    'education_rating' => null,
                    'experience_rating' => null,
                    'training_rating' => null,
                    'eligibility_rating' => null
                ];

                $eete_ratings = (object)$eete_ratings;
                $eete_ratings = collect([$eete_ratings]);
            }

            return $this->successResponse($eete_ratings, 'EETE ratings retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve EETE ratings: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'education_rating' => 'required|numeric|min:0|max:100',
                'experience_rating' => 'required|numeric|min:0|max:100',
                'training_rating' => 'required|numeric|min:0|max:100',
                'eligibility_rating' => 'required|numeric|min:0|max:100'
            ], [
                'education_rating.required' => 'Education rating is required.',
                'education_rating.numeric' => 'Education rating must be a number.',
                'education_rating.min' => 'Education rating must be at least 0.',
                'education_rating.max' => 'Education rating cannot exceed 100.',
                'experience_rating.required' => 'Experience rating is required.',
                'experience_rating.numeric' => 'Experience rating must be a number.',
                'experience_rating.min' => 'Experience rating must be at least 0.',
                'experience_rating.max' => 'Experience rating cannot exceed 100.',
                'training_rating.required' => 'Training rating is required.',
                'training_rating.numeric' => 'Training rating must be a number.',
                'training_rating.min' => 'Training rating must be at least 0.',
                'training_rating.max' => 'Training rating cannot exceed 100.',
                'eligibility_rating.required' => 'Eligibility rating is required.',
                'eligibility_rating.numeric' => 'Eligibility rating must be a number.',
                'eligibility_rating.min' => 'Eligibility rating must be at least 0.',
                'eligibility_rating.max' => 'Eligibility rating cannot exceed 100.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = [
                'education_rating' => $request->education_rating,
                'experience_rating' => $request->experience_rating,
                'training_rating' => $request->training_rating,
                'eligibility_rating' => $request->eligibility_rating
            ];

            $id = DB::table('eete_ratings')->insertGetId($data);

            $total_rating = ($request->education_rating + $request->experience_rating + $request->training_rating + $request->eligibility_rating);
            $average_rating = $total_rating / 4;

            return $this->successResponse([
                'id' => $id,
                'ratings' => $data,
                'summary' => [
                    'total_rating' => $total_rating,
                    'average_rating' => round($average_rating, 2),
                    'max_possible' => 400
                ]
            ], 'EETE ratings saved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to save EETE ratings: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $eete_rating = DB::table('eete_ratings')->where('id', $id)->first();

            if (!$eete_rating) {
                return $this->notFoundResponse('EETE rating not found');
            }

            $total_rating = $eete_rating->education_rating + $eete_rating->experience_rating + $eete_rating->training_rating + $eete_rating->eligibility_rating;
            $average_rating = $total_rating / 4;

            return $this->successResponse([
                'rating' => $eete_rating,
                'summary' => [
                    'total_rating' => $total_rating,
                    'average_rating' => round($average_rating, 2),
                    'max_possible' => 400
                ]
            ], 'EETE rating retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve EETE rating: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            return $this->successResponse([
                'fields' => [
                    'education_rating' => ['type' => 'number', 'required' => true, 'label' => 'Education Rating', 'min' => 0, 'max' => 100],
                    'experience_rating' => ['type' => 'number', 'required' => true, 'label' => 'Experience Rating', 'min' => 0, 'max' => 100],
                    'training_rating' => ['type' => 'number', 'required' => true, 'label' => 'Training Rating', 'min' => 0, 'max' => 100],
                    'eligibility_rating' => ['type' => 'number', 'required' => true, 'label' => 'Eligibility Rating', 'min' => 0, 'max' => 100]
                ],
                'description' => 'EETE (Education, Experience, Training, Eligibility) Rating System',
                'max_total' => 400,
                'max_per_category' => 100
            ], 'Create EETE rating form structure');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load create form: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $eete_rating = DB::table('eete_ratings')->where('id', $id)->first();

            if (!$eete_rating) {
                return $this->notFoundResponse('EETE rating not found');
            }

            return $this->successResponse($eete_rating, 'EETE rating edit data loaded successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to load EETE rating edit data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'education_rating' => 'required|numeric|min:0|max:100',
                'experience_rating' => 'required|numeric|min:0|max:100',
                'training_rating' => 'required|numeric|min:0|max:100',
                'eligibility_rating' => 'required|numeric|min:0|max:100'
            ], [
                'education_rating.required' => 'Education rating is required.',
                'education_rating.numeric' => 'Education rating must be a number.',
                'education_rating.min' => 'Education rating must be at least 0.',
                'education_rating.max' => 'Education rating cannot exceed 100.',
                'experience_rating.required' => 'Experience rating is required.',
                'experience_rating.numeric' => 'Experience rating must be a number.',
                'experience_rating.min' => 'Experience rating must be at least 0.',
                'experience_rating.max' => 'Experience rating cannot exceed 100.',
                'training_rating.required' => 'Training rating is required.',
                'training_rating.numeric' => 'Training rating must be a number.',
                'training_rating.min' => 'Training rating must be at least 0.',
                'training_rating.max' => 'Training rating cannot exceed 100.',
                'eligibility_rating.required' => 'Eligibility rating is required.',
                'eligibility_rating.numeric' => 'Eligibility rating must be a number.',
                'eligibility_rating.min' => 'Eligibility rating must be at least 0.',
                'eligibility_rating.max' => 'Eligibility rating cannot exceed 100.'
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $eete_rating = DB::table('eete_ratings')->where('id', $id)->first();

            if (!$eete_rating) {
                return $this->notFoundResponse('EETE rating not found');
            }

            $data = [
                'education_rating' => $request->education_rating,
                'experience_rating' => $request->experience_rating,
                'training_rating' => $request->training_rating,
                'eligibility_rating' => $request->eligibility_rating
            ];

            DB::table('eete_ratings')->where('id', $id)->update($data);

            $total_rating = $request->education_rating + $request->experience_rating + $request->training_rating + $request->eligibility_rating;
            $average_rating = $total_rating / 4;

            return $this->successResponse([
                'id' => $id,
                'ratings' => $data,
                'summary' => [
                    'total_rating' => $total_rating,
                    'average_rating' => round($average_rating, 2),
                    'max_possible' => 400
                ]
            ], 'EETE rating updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update EETE rating: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $eete_rating = DB::table('eete_ratings')->where('id', $id)->first();

            if (!$eete_rating) {
                return $this->notFoundResponse('EETE rating not found');
            }

            DB::table('eete_ratings')->where('id', $id)->delete();

            return $this->successResponse(['deleted_id' => $id], 'EETE rating deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete EETE rating: ' . $e->getMessage());
        }
    }
}
