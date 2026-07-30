<?php

namespace App\Http\Controllers;

use Auth;
use App\Audit;
use App\Ratings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;

class RatingController extends Controller
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
            $data = DB::table('adjectival_ratings')
                ->orderBy('numerical_rating1', 'asc')
                ->get();

            return $this->successResponse($data, 'Rating data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve rating data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            $arr_len = count($data['adjectival_rating']);

            $rating_data = [];

            DB::table('adjectival_ratings')->truncate();

            for ($i = 0; $i < $arr_len; $i++) {
                if ($data['adjectival_rating'][$i] != NULL) {
                    $id =  $data['id'][$i];
                    $adjectival_rating = $data['adjectival_rating'][$i];
                    $numerical_rating1 = $data['numerical_rating1'][$i];
                    $numerical_rating2 = $data['numerical_rating2'][$i];

                    $rules = [
                        'adjectival_rating.' . $i => 'required|string|unique:adjectival_ratings,adjectival_rating,' . $id,
                        'numerical_rating1.' . $i => 'required|numeric|min:0',
                        'numerical_rating2.' . $i => 'required|numeric|min:0',
                    ];
                    $messages = [
                        'adjectival_rating.' . $i . '.unique' => 'The name already taken',
                        'adjectival_rating.' . $i . '.required' => 'This field is required',
                        'numerical_rating1.' . $i . '.numeric' => 'The "From" field must be a number.',
                        'numerical_rating1.' . $i . '.min' => 'The "From" field must be at least :min.',
                        'numerical_rating2.' . $i . '.numeric' => 'The "To" field must be a number.',
                        'numerical_rating2.' . $i . '.min' => 'The "To" field must be at least :min.',
                    ];
                    $validator = Validator::make($data, $rules, $messages);
                    if ($validator->fails()) {
                        return $this->validationErrorResponse($validator->errors());
                    }

                    $rating_data = [
                        'adjectival_rating' => $adjectival_rating,
                        'numerical_rating1' => $numerical_rating1,
                        'numerical_rating2' => $numerical_rating2,
                    ];

                    DB::table('adjectival_ratings')->updateOrInsert($rating_data);
                }
            }

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Ratings',
                'activity' => 'Update',
                'description' => 'Updated rating table information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'You have successfully updated rating!');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update rating: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $rating = DB::table('adjectival_ratings')->where('id', $id)->first();

            if (!$rating) {
                return $this->notFoundResponse('Rating not found');
            }

            return $this->successResponse($rating, 'Rating retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve rating: ' . $e->getMessage());
        }
    }

    public function create()
    {
        try {
            return $this->successResponse(null, 'Create rating form data');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to get create form: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $rating = DB::table('adjectival_ratings')->where('id', $id)->first();

            if (!$rating) {
                return $this->notFoundResponse('Rating not found');
            }

            return $this->successResponse($rating, 'Rating edit data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve rating for editing: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = validator($request->all(), [
                'adjectival_rating' => 'required|string|unique:adjectival_ratings,adjectival_rating,' . $id,
                'numerical_rating1' => 'required|numeric|min:0',
                'numerical_rating2' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $rating = DB::table('adjectival_ratings')->where('id', $id)->first();

            if (!$rating) {
                return $this->notFoundResponse('Rating not found');
            }

            DB::table('adjectival_ratings')->where('id', $id)->update([
                'adjectival_rating' => $request->adjectival_rating,
                'numerical_rating1' => $request->numerical_rating1,
                'numerical_rating2' => $request->numerical_rating2,
            ]);

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Ratings',
                'activity' => 'Update',
                'description' => 'Updated rating information.',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Rating updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update rating: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $rating = DB::table('adjectival_ratings')->where('id', $id)->first();

            if (!$rating) {
                return $this->notFoundResponse('Rating not found');
            }

            DB::table('adjectival_ratings')->where('id', $id)->delete();

            //Save audit trail
            $data_audit = array(
                'user_id' => Auth::user()->id,
                'module'  => 'Control Panel',
                'menu'    => 'Rating  Setup',
                'activity' => 'Delete',
                'description' => 'Deleted Rating table information',
            );

            Audit::create($data_audit);

            return $this->successResponse(null, 'Rating deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to delete rating: ' . $e->getMessage());
        }
    }
}
