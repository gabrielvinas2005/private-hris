<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApplicantsController;
use App\Http\Controllers\ApplicantPdsController;
use App\Http\Controllers\ApplicantExamController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ApplicationsController;
use App\Http\Controllers\ApplicantPortalController;
use App\Http\Controllers\ApplicantVacanciesController;
use App\Http\Controllers\PlantillasController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\DropdownsController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\PdsDisplayController;
use App\Http\Controllers\WorkExperienceController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\ApplicantsMonitoringController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication Routes (Public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/password/forgot', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('/password/reset', [PasswordResetController::class, 'reset']);

// Public Routes
Route::get('/company-public', [\App\Http\Controllers\CompanyController::class, 'publicInfo']);
Route::get('/vacancies', [ApplicantVacanciesController::class, 'index']);
Route::get('/position-info/{id}/{type_id}', [ApplicantVacanciesController::class, 'positions']);
Route::get('/monthly-applicant-count', [ApplicantVacanciesController::class, 'monthlyApplicantCount']);
Route::get('/plantillas', [PlantillasController::class, 'index']);
Route::get('/applicant-add/{id}/{plantilla_id}', [ApplicantsController::class, 'add']);
Route::post('/applicant-add/{id}/{plantilla_id}', [ApplicantsController::class, 'store']);
Route::get('/applicant-registration', [RegistrationController::class, 'register']);
Route::post('/applicant-registration', [RegistrationController::class, 'store']);

// Dropdown Data Routes
Route::get('/dropdown-data/civil-status', [DropdownsController::class, 'getCivilStatus']);
Route::get('/dropdown-data/blood-types', [DropdownsController::class, 'getBloodTypes']);
Route::get('/dropdown-data/name-prefixes', [DropdownsController::class, 'getNamePrefixes']);
Route::get('/dropdown-data/name-suffixes', [DropdownsController::class, 'getNameSuffixes']);
Route::get('/dropdown-data/citizenships', [DropdownsController::class, 'getCitizenships']);
Route::get('/dropdown-data/religions', [DropdownsController::class, 'getReligions']);
Route::get('/dropdown-data/regions', [DropdownsController::class, 'getRegions']);
Route::get('/dropdown-data/provinces', [DropdownsController::class, 'getProvinces']);
Route::get('/dropdown-data/municipalities', [DropdownsController::class, 'getMunicipalities']);
Route::get('/dropdown-data/barangays', [DropdownsController::class, 'getBarangays']);
Route::get('/dropdown-data/document-types', [DropdownsController::class, 'getDocumentTypes']);
Route::get('/dropdown-data/genders', [DropdownsController::class, 'getGenders']);


Route::middleware('auth:sanctum')->group(function () {


    route::get('/pds/{employee_no}', [PdsDisplayController::class, 'getPdsDisplayData']);
    route::post('/pds-update/{employee_no}', [PdsDisplayController::class, 'updatePersonalInfo']);
    // Protected Authentication Routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::get('/pds-data', [AuthController::class, 'pds_data']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOTP']);
    Route::post('/resend-otp', [AuthController::class, 'resendOTP']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Applicant Portal Routes
    Route::get('/applicant-page', [ApplicantsController::class, 'applicant_page']);
    Route::get('/applicant-apply/{applicant_id}/{position_id}/{is_plantilla}', [ApplicantsController::class, 'apply']);
    Route::get('/plantilla-requirement-check/{plantilla_id}', [ApplicantsController::class, 'checkPlantillaRequirements']);
    Route::delete('/applicant-application/{id}', [ApplicantsController::class, 'withdrawApplication']);
    Route::post('/applicant-pds-store/{id}', [ApplicantPdsController::class, 'store']);
    Route::get('/applicant-delete/{type_id}/{id}', [ApplicantsController::class, 'delete']);
    Route::delete('/applicant-destroy/{type_id}/{id}', [ApplicantsController::class, 'destroy']);
    Route::get('/applicant-download/{id}', [ApplicantsController::class, 'download']);
    Route::get('/applicant-application-download/{id}', [ApplicantsController::class, 'downloadApplication']);
    Route::post('/applicant-notifications/read', [ApplicantsController::class, 'markNotificationsRead']);
    Route::get('/applicant-progress', [ApplicantsMonitoringController::class, 'progressForCurrentApplicant']);

    // Job Offer Routes
    Route::get('/applicant/job-offers', [JobOfferController::class, 'getJobOffers']);
    Route::post('/applicant/job-offer/{id}/accept', [ApplicantPortalController::class, 'acceptOffer']);
    Route::post('/applicant/job-offer/{id}/reject', [ApplicantPortalController::class, 'rejectOffer']);

    // Applicant PDS Routes
    Route::get('/applicant-pds-data/{id}', [ApplicantsController::class, 'applicant_page']);
    Route::get('/applicant-pds-questions/{id}', [ApplicantPdsController::class, 'getQuestions']);
    Route::post('/applicant-pds-questionnaire/{id}', [ApplicantPdsController::class, 'storeQuestionnaire']);

    // Examination Routes
    Route::get('/exam-intro/{id}', [ApplicantsController::class, 'examIntro']);
    Route::get('/exam-page/{id}', [ApplicantsController::class, 'examPage']);
    Route::post('/exam-auto-save/{applicant_examination_id}', [ApplicantsController::class, 'examAutoSave']);
    Route::post('/exam-submit/{applicant_examination_id}', [ApplicantsController::class, 'examSubmit']);
    Route::post('/exam-external-submit/{applicant_examination_id}', [ApplicantsController::class, 'examExternalSubmit']);
    Route::get('/exam-result/{applicant_examination_id}', [ApplicantsController::class, 'examResult']);

    // Work Experience Report Routes
    Route::post('/work-experience/preview', [WorkExperienceController::class, 'generatePreview']);
    Route::post('/work-experience/download-pdf', [WorkExperienceController::class, 'downloadPDF']);
    Route::post('/work-experience/download-word', [WorkExperienceController::class, 'downloadWord']);
    Route::post('/work-experience/download-docx', [WorkExperienceController::class, 'downloadDocx']);
});
