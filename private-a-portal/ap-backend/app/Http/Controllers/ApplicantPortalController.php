<?php

namespace App\Http\Controllers;

use App\Services\ApplicantPortalService;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;

class ApplicantPortalController extends Controller
{
    use ApiResponse;

    private ApplicantPortalService $portalService;

    public function __construct(ApplicantPortalService $portalService)
    {
        $this->portalService = $portalService;
    }

    public function page()
    {
        $data = $this->portalService->getApplicantPageData(Auth::user()->id);
        return $this->successResponse($data, 'Applicant page data loaded successfully');
    }

    public function acceptOffer($applicationId)
    {
        try {
            $userId = Auth::user()->id;
            $result = $this->portalService->acceptJobOffer($applicationId, $userId);

            if ($result) {
                return $this->successResponse($result, 'Job offer accepted successfully');
            }

            return $this->errorResponse('Failed to accept job offer', 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function rejectOffer($applicationId)
    {
        try {
            $userId = Auth::user()->id;
            $result = $this->portalService->rejectJobOffer($applicationId, $userId);

            if ($result) {
                return $this->successResponse($result, 'Job offer declined');
            }

            return $this->errorResponse('Failed to decline job offer', 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
