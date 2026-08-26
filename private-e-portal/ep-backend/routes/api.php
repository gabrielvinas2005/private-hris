<?php
use App\Http\Controllers\DocumentRequestController;
use App\Http\Controllers\TrainingRecordController;
use App\Http\Controllers\DownloadablesController;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('/sanctum/csrf-cookie', 'Laravel\Sanctum\Http\Controllers\CsrfCookieController@show');


// Public routes (no authentication required)
Route::get('/company-public', 'CompanyController@publicInfo');
Route::get('/dtr-debug/{id}', 'DailyTimeRecordController@debugDtrApprover');
Route::get('/vacancies', 'VacanciesController@index');
Route::get('/position-info/{id}/{type_id}', 'VacanciesController@positions');
Route::get('/applicant-add/{id}/{plantilla_id}', 'ApplicantsController@add');
Route::post('/applicant-add/{id}/{plantilla_id}', 'ApplicantsController@store');
Route::get('/applicant-registration', 'ApplicantsController@register');
Route::post('/applicant-registration', 'ApplicantsController@register_store');

// Public routes (no authentication required)
Route::get('/daily-time-records/today-status/{userId}', 'DailyTimeRecordController@getTodayStatus');
Route::get('/201-files', 'EmployeeFileController@index');

// Authentication routes
Route::post('/login', 'Api\AuthController@login');
Route::post('/register', 'Api\AuthController@register');
Route::post('/verify-otp', 'Api\AuthController@verifyOtp');

//Downloadables Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/downloadables', [DownloadablesController::class, 'index']);
    Route::get('/downloadables/{id}/download', [DownloadablesController::class, 'download']);
    Route::get('/downloadables/{id}/preview', [DownloadablesController::class, 'preview']);
});

//Training Records Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/training-records', [TrainingRecordController::class, 'index']);
    Route::post('/training-records', [TrainingRecordController::class, 'store']);
});

//Document Request  
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/document-requests', [DocumentRequestController::class, 'index']);
    Route::post('/document-requests', [DocumentRequestController::class, 'store']);
    Route::delete('/document-requests/{id}', [DocumentRequestController::class, 'cancel']);
    Route::patch('/document-requests/{id}/status', [DocumentRequestController::class, 'updateStatus']);
});

// Authentication routes
// User authentication check route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = $request->user();
    return response()->json([
        'success' => true,
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo' => $user->photo,
                'employee_no' => $user->employee_no,
                'is_applicant' => (bool) ($user->is_applicant ?? false),
                'has_change_password' => (bool) ($user->has_change_password ?? false),
                'is_admin' => (bool) ($user->is_admin ?? false),
                'with_hrm_access' => (bool) ($user->with_hrm_access ?? false),
                'with_hrt_access' => (bool) ($user->with_hrt_access ?? false),
                'with_hrp_access' => (bool) ($user->with_hrp_access ?? false),
                'with_cpm_access' => (bool) ($user->with_cpm_access ?? false),
            ]
        ],
        'message' => 'User retrieved successfully'
    ]);
});

// Tab access (controls which Vue tabs/sections are visible in employee portal)
Route::middleware('auth:sanctum')->get('/user-tab-access', 'TabAccessController@index');

// Attachment view route (handles auth manually to avoid redirects in iframe)
Route::get('/employee-non-dtr/attachment/{id}/view', 'NonDTRController@viewAttachment');

// Pass Slip PDF route (handles auth manually to avoid redirects in iframe)
Route::get('/pass-slips/{id}/pdf', 'PassSlipController@generatePDF');

// Protected routes (authentication required)
Route::middleware(['auth:sanctum'])->group(function () {
    // Change password route
    Route::post('/change-password', 'Api\AuthController@changePassword');
    Route::post('/resend-otp', 'Api\AuthController@resendOtp');

    // Authentication (protected routes)
    Route::post('/logout', 'Api\AuthController@logout');
    Route::get('/profile', 'Api\AuthController@profile');
    Route::post('/profile/upload-photo', 'ProfileController@uploadPhoto');
    Route::post('/refresh', 'Api\AuthController@refresh');

    // Dashboard
    Route::get('/home', 'HomeController@index');
    Route::get('/dashboard/{id}', 'DashboardController@index');


    // Notifications
    Route::get('/notifications', 'NotificationController@index');
    Route::post('/notifications/{id}/read', 'NotificationController@markAsRead');
    Route::post('/notifications/read-all', 'NotificationController@markAllAsRead');
    Route::post('/notifications/{id}/archive', 'NotificationController@archive');
    Route::post('/notifications/archive-all-read', 'NotificationController@archiveAllRead');

    // Announcements
    Route::get('/announcements', 'AnnouncementController@index');
    Route::post('/announcements', 'AnnouncementController@store');
    Route::get('/announcements/employees', 'AnnouncementController@getEmployees');

    // Applicant Examination Routes
    Route::get('/applicant-examination-intro/{id}', 'ApplicantsController@examIntro');
    Route::get('/applicant-examination-page/{id}', 'ApplicantsController@examPage');
    Route::post('/applicant-examination-submit/{applicant_examination_id}', 'ApplicantsController@examSubmit');
    Route::post('/applicant-examination-auto-save/{applicant_examination_id}', 'ApplicantsController@examAutoSave');
    Route::get('/applicant-examination-result/{applicant_examination_id}', 'ApplicantsController@examResult');

    // Applicant Portal Routes
    Route::get('/applicant-page', 'ApplicantsController@applicant_page');
    Route::get('/applicant-apply/{applicant_id}/{position_id}/{is_plantilla}', 'ApplicantsController@apply');
    Route::post('/applicant-pds-store/{id}', 'ApplicantsController@pds_store');
    Route::get('/applicant-delete/{type_id}/{id}', 'ApplicantsController@pds_delete');
    Route::delete('/applicant-destroy/{type_id}/{id}', 'ApplicantsController@pds_destroy');
    Route::get('/applicant-download/{id}', 'ApplicantsController@download');

    // OTP Authentication
    Route::get('/user-verification', 'UsersController@userVerification');
    Route::get('/user-verification-check', 'UsersController@userVerificationCheck');

    // Employee Portal Routes - 201 File
    Route::get('/201-files/{id}', 'EmployeeFileController@index');
    Route::get('/201-ipcr-view/{id}', 'EmployeeFileController@ipcr_view');
    Route::get('/201-file-updates/{id}', 'EmployeeRequestController@index');
    Route::get('/201-file-updates-add/{id}/{employee_id}', 'EmployeeRequestController@add');
    Route::post('/201-file-updates-add/{id}/{request_id}', 'EmployeeRequestController@store');
    Route::post('/201-file-autosave/{employee_id}', 'EmployeeRequestController@autosave');
    Route::get('/201-file-add/{id}', 'EmployeeFileController@update');

    // Overtime Application Routes
    Route::get('/overtime-applications/{id}', 'OvertimeApplicationController@index');
    Route::post('/overtime-applications/store', 'OvertimeApplicationController@store');
    Route::post('/overtime-applications/approve', 'OvertimeApplicationController@approve');
    Route::get('/overtime-applications/{id}/disapprove/{remarks}', 'OvertimeApplicationController@disapprove');
    Route::get('/overtime-applications/{id}/cancel/{emp_id}/{remarks}', 'OvertimeApplicationController@cancel');
    Route::delete('/overtime-applications/{id}', 'OvertimeApplicationController@destroy');
    Route::get('/overtime-applications/{id}/attachments', 'OvertimeApplicationController@attachments');
    Route::delete('/overtime-applications/attachments/{id}', 'OvertimeApplicationController@remove_attachments');
    Route::get('/overtime-applications/attachments/{id}/download', 'OvertimeApplicationController@downloadAttachment');
    Route::get('/overtime-applications/attachments/{id}/download-approval', 'OvertimeApplicationController@downloadapproval');
    Route::get('/overtime-applications/monitoring', 'OvertimeApplicationController@monitoring');
    Route::post('/overtime-authorization-request', 'OvertimeApplicationController@printOvertimeAuthorizationRequest');
    Route::post('/overtime-authorization-request-word', 'OvertimeApplicationController@downloadOvertimeAuthorizationDocx');
    Route::post('/overtime-authorization-request-excel', 'OvertimeApplicationController@downloadOvertimeAuthorizationExcel');
    Route::get('/overtime-attachments/{id}', 'OvertimeApplicationController@attachments');
    Route::get('/overtime-remove-attachments/{id}', 'OvertimeApplicationController@remove_attachments');
    Route::post('/overtime-cancel-attachment', 'OvertimeApplicationController@cancel_attachment');
    Route::get('/overtime-cancel-attachment-download/{id}', 'OvertimeApplicationController@download');
    Route::get('/overtime-attachment-download/{id}', 'OvertimeApplicationController@downloadapproval');
    Route::get('/overtime-download/{id}', 'OvertimeApplicationController@downloadAttachment');

    // Official Business Application Routes
    Route::get('/official-business-applications/{id}', 'OfficialBusinessApplicationController@index');
    Route::post('/official-business-applications', 'OfficialBusinessApplicationController@store');
    Route::post('/official-business-applications/unofficial', 'OfficialBusinessApplicationController@storeunofficial');
    Route::get('/ta-types', 'OfficialBusinessApplicationController@getTATypes');
    Route::get('/to-types', 'OfficialBusinessApplicationController@getTOTypes');
    Route::get('/employee-info-for-pickup/{id}', 'OfficialBusinessApplicationController@getEmployeeInfoForPickup');
    Route::get('/official-business/budget-officers', 'OfficialBusinessApplicationController@getBudgetOfficers');
    Route::get('/official-business-applications/{id}/approve/{remarks}', 'OfficialBusinessApplicationController@approve');
    Route::get('/official-business-applications/{id}/disapprove/{remarks}', 'OfficialBusinessApplicationController@disapprove');
    Route::get('/official-business-applications/{id}/cancel/{remarks}', 'OfficialBusinessApplicationController@cancel');
    Route::delete('/official-business-applications/{id}', 'OfficialBusinessApplicationController@destroy');
    Route::get('/official-business-attachments/{id}', 'OfficialBusinessApplicationController@attachments');
    Route::get('/official-business-remove-attachments/{id}', 'OfficialBusinessApplicationController@remove_attachments');
    Route::post('/official-business-cancel-attachment', 'OfficialBusinessApplicationController@cancel_attachment');
    Route::get('/official-business-cancel-attachment-download/{id}', 'OfficialBusinessApplicationController@download');
    Route::get('/official-business-attachment-download/{id}', 'OfficialBusinessApplicationController@downloadapproval');
    Route::get('/official-business-download/{id}', 'OfficialBusinessApplicationController@downloadAttachment');
    Route::get('/official-business-print/{id}', 'OfficialBusinessApplicationController@print');
    Route::get('/unofficial-business-print/{id}', 'OfficialBusinessApplicationController@printunofficial');
    Route::get('/order-business-print/{id}', 'OfficialBusinessApplicationController@printorder');
    Route::get('/request-pickup-print/{id}', 'OfficialBusinessApplicationController@printRequestForPickup');
    Route::get('/request-pickup-word/{id}', 'OfficialBusinessApplicationController@downloadRequestForPickupDocx');
    Route::get('/request-pickup-excel/{id}', 'OfficialBusinessApplicationController@downloadRequestForPickupExcel');
    Route::get('/travel-authority-word/{id}', 'OfficialBusinessApplicationController@downloadTravelAuthorityDocx');
    Route::get('/travel-authority-personal-word/{id}', 'OfficialBusinessApplicationController@downloadTravelAuthorityPersonalDocx');
    Route::get('/travel-order-ldsd-word/{id}', 'OfficialBusinessApplicationController@downloadTravelOrderLDSDDocx');
    Route::get('/official-business-word/{id}', 'OfficialBusinessApplicationController@downloadOfficialBusinessDocx');
    Route::get('/official-business-excel/{id}', 'OfficialBusinessApplicationController@downloadOfficialBusinessExcel');

    // Leave Application Routes
    Route::get('/leaves/{id}', 'LeaveController@index');
    Route::get('/leave-application/{id}/{view}', 'LeaveController@add');
    Route::post('/leaves/{id}', 'LeaveController@store');
    Route::get('/leaves/{id}/process/{process_id}/{remarks}', 'LeaveController@process');
    Route::delete('/leaves/{id}', 'LeaveController@destroy');
    Route::get('/leaves/{id}/print', 'LeaveController@print');
    Route::get('/leave-attachments/{id}', 'LeaveController@attachments');
    Route::get('/leave-remove-attachments/{id}', 'LeaveController@remove_attachments');
    Route::get('/leave-without-pay/{id}/{leave_type_id}', 'LeaveController@checkLWOP');
    Route::get('/credit-cancelled-leave/{leave_cancelled_id}', 'LeaveController@creditCancelledLeave');
    Route::post('/leave-cancel-attachment', 'LeaveController@cancel_attachment');
    Route::get('/leave-cancel-attachment-download/{id}', 'LeaveController@download');
    Route::get('/leave-balance-validation/{leave_id}/{employee_id}/{date_from}/{date_to}', 'LeaveController@leaveValidation');
    Route::get('/leave-attachment-download/{id}', 'LeaveController@downloadAttachment');

    // Leave Monetization Routes
    Route::get('/leave-monetization/{id}', 'MonetizationController@loadMonetization');
    Route::post('/leave-monetization', 'MonetizationController@storeMonetization');
    Route::delete('/leave-monetization/{id}', 'MonetizationController@deleteMonetization');
    Route::get('/leave-monetization/{id}/process/{type_id}/{remarks}', 'MonetizationController@processMonetization');
    Route::get('/leave-monetization-attachment/{id}', 'MonetizationController@loadAttachments');
    Route::delete('/leave-monetization-attachment/{id}', 'MonetizationController@deleteAttachments');
    Route::get('/leave-monetization-attachment-download/{id}', 'MonetizationController@download');

    // Pass Slip Routes
    Route::get('/pass-slips/{id}', 'PassSlipController@index');
    Route::get('/pass-slips/detail/{id}', 'PassSlipController@show');
    Route::post('/pass-slips', 'PassSlipController@store');
    Route::put('/pass-slips/{id}', 'PassSlipController@update');
    Route::post('/pass-slips/{id}/status', 'PassSlipController@updateStatus');
    Route::delete('/pass-slips/{id}', 'PassSlipController@destroy');

    // Daily Time Record Routes
    Route::post('/daily-time-records/web-clock', 'DailyTimeRecordController@webClock');
    Route::get('/daily-time-records/{id}', 'DailyTimeRecordController@index');
    Route::get('/daily-time-records/employee/{employee_no}', 'DailyTimeRecordController@getByEmployeeNo');
    Route::get('/daily-time-records/{id}/employee/{payroll_period_id}', 'DailyTimeRecordController@view');
    Route::get('/daily-time-records/{id}/print/{payroll_period_id}', 'DailyTimeRecordController@print');
    Route::get('/daily-time-records/{id}/logs', 'DailyTimeRecordController@logs');
    Route::get('/daily-time-records/{id}/logs/{from}/{to}', 'DailyTimeRecordController@getLogs');
    Route::post('/daily-time-records/{id}', 'DailyTimeRecordController@store');
    Route::get('/dtr-applications/{id}', 'DailyTimeRecordController@employeeApplications');
    Route::get('/dtr-applications/employee/{employeeId}/payroll-periods', 'DailyTimeRecordController@payrollPeriodsForApplication');
    Route::post('/dtr-applications/employee/{employeeId}', 'DailyTimeRecordController@storeApplication');
    Route::post('/dtr-applications/request/{requestId}', 'DailyTimeRecordController@updateApplication');
    Route::get('/dtr-applications/request/{requestId}/download', 'DailyTimeRecordController@downloadApplicationAttachment');
    Route::get('/dtr-applications/request/{requestId}/approved-dtr', 'DailyTimeRecordController@downloadApprovedDtr');
    Route::get('/dtr-approver-access/{id}', 'DailyTimeRecordController@checkDtrApproverAccess');
    Route::get('/review-daily-time-records/{id}', 'DailyTimeRecordController@loadDTRRequest');
    Route::get('/review-daily-time-records/{id}/add', 'DailyTimeRecordController@reviewDTRRequest');
    Route::get('/review-daily-time-records/{id}/approve/{type_id}', 'DailyTimeRecordController@approve');
    Route::post('/review-daily-time-records/{id}/approve/{type_id}', 'DailyTimeRecordController@approve');
    Route::get('/review-daily-time-records/{id}/download', 'DailyTimeRecordController@download');
    Route::get('/dtr-debug/{id}', 'DailyTimeRecordController@debugDtrApprover');

    // WFH Attendance Routes
    Route::get('/wfh-attendance/{id}/employee-info', 'WFHAttendanceController@getEmployeeInfo');
    Route::get('/wfh-attendance/{employee_id}/payroll-periods', 'WFHAttendanceController@getPayrollPeriods');
    Route::get('/wfh-attendance/{employee_id}/time-data/{payroll_period_id}', 'WFHAttendanceController@getTimeData');
    Route::post('/wfh-attendance/{employee_id}', 'WFHAttendanceController@store');
    Route::get('/wfh-attendance/{employee_id}/summary/{payroll_period_id}', 'WFHAttendanceController@getAttendanceSummary');
    Route::delete('/wfh-attendance/{employee_id}/record/{time_data_id}', 'WFHAttendanceController@delete');

    // WFH Time Clock Routes
    Route::get('/wfh-attendance/{employee_id}/today-status', 'WFHAttendanceController@getTodayStatus');
    Route::post('/wfh-attendance/{employee_id}/time-in', 'WFHAttendanceController@timeIn');
    Route::post('/wfh-attendance/{employee_id}/time-out', 'WFHAttendanceController@timeOut');
    Route::post('/wfh-attendance/{employee_id}/break-in', 'WFHAttendanceController@breakIn');
    Route::post('/wfh-attendance/{employee_id}/break-out', 'WFHAttendanceController@breakOut');

    // WFH Application Routes
    Route::get('/wfh-applications', 'WfhApplicationController@index');
    Route::get('/wfh-applications/{id}', 'WfhApplicationController@show');
    Route::post('/wfh-applications', 'WfhApplicationController@store');
    Route::put('/wfh-applications/{id}', 'WfhApplicationController@update');
    Route::post('/wfh-applications/{id}/cancel', 'WfhApplicationController@cancel');
    Route::post('/wfh-applications/{id}/approve', 'WfhApplicationController@approve');
    Route::post('/wfh-applications/{id}/disapprove', 'WfhApplicationController@disapprove');

    // Payslip Routes
    Route::get('/payslips/{id}', 'PayslipController@index');
    Route::get('/payslips/{id}/view/{payroll_id}', 'PayslipController@view');
    Route::get('/payslips/{id}/print/{payroll_id}', 'PayslipController@print');

    // Panel Interview Routes
    Route::get('/panel-interviews/{id}', 'InterviewController@panel_interview');
    Route::get('/panel-interviews/{id}/applicant-pds/{applicant_id}', 'InterviewController@panel_interview_pds');
    Route::get('/panel-interviews/{id}/applicant-exam/{applicant_id}', 'InterviewController@panel_interview_exam');
    Route::get('/panel-interviews/{id}/applicant-rating/{applicant_id}/{employee_id}/{interview_id}', 'InterviewController@panel_interview_rating');
    Route::post('/panel-interviews/{id}/applicant-rating/{rating_id}', 'InterviewController@interview_rating');
    Route::get('/panel-interviews/{id}/attachments/{interview_id}/{employee_id}/{applicant_id}', 'InterviewController@panel_interview_list_attachments');
    Route::post('/panel-interviews/{id}/attachments', 'InterviewController@panel_interview_upload_attachment');
    Route::get('/panel-interviews/{id}/attachment/{attachment_id}', 'InterviewController@panel_interview_preview_attachment');
    Route::post('/panel-interviews/{id}/done', 'InterviewController@panel_interview_done');

    // Human Resource Module Routes - Applicant Records
    Route::get('/applicants', 'ApplicantVacanciesController@index');
    Route::get('/applicants/{id}/change-status/{status_id}', 'ApplicantVacanciesController@changestatus');
    Route::post('/applicants/{id}/change-status/{status_id}', 'ApplicantVacanciesController@changestatus');
    Route::get('/applicant-list/{plantilla_id}', 'ApplicantsController@index');
    Route::get('/applicant-info/{id}/{plantilla_id}', 'ApplicantsController@info');
    Route::get('/applicant-not-qualified/{id}', 'ApplicantsController@notqualified');
    Route::get('/applicant-will-not-proceed/{id}', 'ApplicantsController@willnotproceed');
    Route::get('/applicant-proceed/{id}', 'ApplicantsController@proceed');
    Route::post('/applicant-not-qualified/{id}', 'ApplicantsController@notqualified');
    Route::post('/applicant-will-not-proceed/{id}', 'ApplicantsController@willnotproceed');
    Route::post('/applicant-proceed/{id}', 'ApplicantsController@proceed');
    Route::get('/applicant-for-hiring/{id}/{plantilla_id}', 'ApplicantsController@forhiring');
    Route::post('/applicant-for-hiring/{id}/{plantilla_id}', 'ApplicantsController@forhiring');

    // Applicant Hiring Routes
    Route::get('/applicant-hiring', 'ApplicantHiringController@index');
    Route::get('/applicant-hiring/{id}/{type}', 'ApplicantHiringController@hiring');
    Route::get('/applicant-resume/{id}', 'ApplicantHiringController@download');
    Route::get('/applicant-process/{id}/{position_id}/{is_plantilla}/{type}', 'ApplicantHiringController@process');
    Route::get('/applicant-download-zip/{id}/{position}', 'ApplicantHiringController@download_zip');
    Route::get('/applicant-hiring-info/{id}', 'ApplicantHiringController@info');
    Route::post('/applicant-eete-rating/{id}', 'ApplicantHiringController@rating');

    // Employee Records Routes
    Route::get('/employees', 'EmployeesController@index');
    Route::get('/employees/add/{id}', 'EmployeesController@add');
    Route::get('/employees/delete/{type_id}/{id}', 'EmployeesController@delete');
    Route::post('/employees/{id}/{is_employee_portal}', 'EmployeesController@store');
    Route::delete('/employees/{type_id}/{id}', 'EmployeesController@destroy');
    Route::get('/employees/{id}/download', 'EmployeesController@download');

    // Employee Documents Routes
    Route::get('/employee-documents/{employee_document_id}', 'EmployeeDocumentController@getAttachments');
    Route::delete('/employee-documents/{employee_document_id}', 'EmployeeDocumentController@delete');
    Route::get('/employee-documents/{employee_id}/load', 'EmployeeDocumentController@loadDocuments');
    Route::get('/employee-documents/{employee_id}/load-range/{date_from}/{date_to}', 'EmployeeDocumentController@loadDocumentsRange');
    Route::get('/employee-documents/{employee_document_id}/download', 'EmployeeDocumentController@download');
    Route::post('/employee-documents/{employee_document_id}', 'EmployeeDocumentController@store');
    Route::get('/employee-documents/{employee_document_id}/preview', 'EmployeeDocumentController@preview');
    Route::delete('/preview/{document_id}', 'EmployeeDocumentController@deletePreview');

    // Employee Promotion Routes
    Route::get('/promotions', 'EmployeePromotionController@index');
    Route::get('/promotions/add/{id}', 'EmployeePromotionController@add');
    Route::post('/promotions/{id}', 'EmployeePromotionController@store');

    // Employee Off-Boarding Routes
    Route::get('/off-boardings', 'EmployeeOffBoardingController@index');
    Route::get('/off-boardings/add/{id}', 'EmployeeOffBoardingController@add');
    Route::get('/off-boardings/{id}/info/{employee_id}', 'EmployeeOffBoardingController@info');
    Route::get('/off-boardings/{id}/reactivate/{employee_id}', 'EmployeeOffBoardingController@activate');
    Route::post('/off-boardings/{id}', 'EmployeeOffBoardingController@store');
    Route::post('/off-boardings/{id}/reactivate/{employee_id}', 'EmployeeOffBoardingController@reactivate');

    // Step Increment Routes
    Route::get('/step-increments', 'EmployeeStepIncrementController@index');
    Route::get('/step-increments/add', 'EmployeeStepIncrementController@add');
    Route::get('/step-increments/edit/{month_id}/{year_id}', 'EmployeeStepIncrementController@edit');
    Route::get('/step-increments/{month_id}/{year_id}/employees', 'EmployeeStepIncrementController@loadEmployees');
    Route::get('/step-increments/{step_increment_id}/employee/{employee_id}/salary', 'EmployeeStepIncrementController@changeNewSalary');
    Route::get('/step-increments/{month_id}/{year_id}/forward', 'EmployeeStepIncrementController@forwarded');
    Route::post('/step-increments', 'EmployeeStepIncrementController@store');
    Route::get('/step-increments/{month_id}/{year_id}/forward-approver', 'EmployeeStepIncrementController@forwardApprover');
    Route::get('/step-increment-approval', 'EmployeeStepIncrementController@process');
    Route::get('/step-increments/{month_id}/{year_id}/for-process', 'EmployeeStepIncrementController@forProcess');
    Route::get('/step-increments/{month_id}/{year_id}/approved', 'EmployeeStepIncrementController@approved');
    Route::get('/step-increments/{month_id}/{year_id}/disapproved/{remarks}', 'EmployeeStepIncrementController@disapproved');
    Route::get('/step-increments/{month_id}/{year_id}/print', 'EmployeeStepIncrementController@print');
    Route::delete('/step-increments/{step_increment_id}/employee', 'EmployeeStepIncrementController@delete');

    // Salary Adjustment Routes
    Route::get('/salary-adjustments', 'SalaryAdjustmentController@index');
    Route::post('/salary-adjustments', 'SalaryAdjustmentController@process');

    // IPCR Routes (Admin/HR)
    Route::get('/ipcr', 'IPCRController@index');
    Route::get('/ipcr/add/{id}', 'IPCRController@add');
    Route::post('/ipcr/{id}', 'IPCRController@store');
    Route::get('/ipcr/{id}/review', 'IPCRController@review');
    Route::get('/ipcr-adjective/{rating}', 'IPCRController@ipcr_adjective');
    Route::post('/ipcr/{id}/rating', 'IPCRController@rating');

    // IPCR Routes (Employee Portal)
    Route::get('/employee-ipcr/check-access', 'IPCRController@checkDivisionChiefAccess');
    Route::get('/employee-ipcr/agency-head-approvals', 'IPCRController@agencyHeadApprovalList');
    Route::get('/employee-ipcr/hr-recalibrations', 'IPCRController@hrRecalibrationList');
    Route::get('/employee-ipcr', 'IPCRController@employeeList');
    Route::get('/employee-ipcr/form-data', 'IPCRController@employeeFormData');
    Route::get('/employee-ipcr/{id}', 'IPCRController@employeeGet');
    Route::get('/employee-ipcr/{id}/print', 'IPCRController@employeePrint');
    Route::post('/employee-ipcr', 'IPCRController@employeeStore');
    Route::put('/employee-ipcr/{id}', 'IPCRController@employeeStore');
    Route::delete('/employee-ipcr/{id}', 'IPCRController@employeeDelete');
    Route::post('/employee-ipcr/{id}/agency-head-approval', 'IPCRController@processAgencyHeadApproval');
    Route::post('/employee-ipcr/{id}/recalibrate', 'IPCRController@saveRecalibration');

    // OPCR Routes (Employee Portal)
    Route::get('/employee-opcr/check-access', 'OPCRController@checkDivisionChiefAccess');
    Route::get('/employee-opcr', 'OPCRController@employeeList');
    Route::get('/employee-opcr/form-data', 'OPCRController@employeeFormData');
    Route::get('/employee-opcr/{id}', 'OPCRController@employeeGet');
    Route::get('/employee-opcr/{id}/print', 'OPCRController@employeePrint');
    Route::post('/employee-opcr/{id}/recalibrate', 'OPCRController@saveRecalibration');
    Route::post('/employee-opcr', 'OPCRController@employeeStore');
    Route::put('/employee-opcr/{id}', 'OPCRController@employeeStore');
    Route::delete('/employee-opcr/{id}', 'OPCRController@employeeDelete');

    // DPCR Routes (Employee Portal)
    Route::get('/employee-dpcr/check-access', 'DPCRController@checkAccess');
    Route::get('/employee-dpcr', 'DPCRController@employeeList');
    Route::get('/employee-dpcr/form-data', 'DPCRController@employeeFormData');
    Route::get('/employee-dpcr/{id}', 'DPCRController@employeeGet');
    Route::get('/employee-dpcr/{id}/print', 'DPCRController@employeePrint');
    Route::post('/employee-dpcr/{id}/recalibrate', 'DPCRController@saveRecalibration');
    Route::post('/employee-dpcr', 'DPCRController@employeeStore');
    Route::put('/employee-dpcr/{id}', 'DPCRController@employeeStore');
    Route::delete('/employee-dpcr/{id}', 'DPCRController@employeeDelete');

    // Non DTR (Contract of Service Accomplishment) - Employee Portal
    Route::get('/employee-non-dtr/debug', 'NonDTRController@debugUserEmployee'); // Debug endpoint
    Route::get('/employee-non-dtr/check-access', 'NonDTRController@checkAccess');
    Route::get('/employee-non-dtr/payroll-periods', 'NonDTRController@employeePayrollPeriods');
    Route::get('/employee-non-dtr', 'NonDTRController@employeeList');
    Route::get('/employee-non-dtr/form-data', 'NonDTRController@employeeFormData');
    Route::get('/employee-non-dtr/{id}/print', 'NonDTRController@employeePrint');
    Route::get('/employee-non-dtr/{id}', 'NonDTRController@employeeGet');
    Route::post('/employee-non-dtr', 'NonDTRController@employeeStore');
    Route::post('/employee-non-dtr/{id}/submit', 'NonDTRController@employeeSubmit');
    Route::put('/employee-non-dtr/{id}', 'NonDTRController@employeeUpdate');
    Route::delete('/employee-non-dtr/{id}', 'NonDTRController@employeeDelete');
    Route::post('/employee-non-dtr/upload-attachment', 'NonDTRController@employeeUploadAttachment');
    Route::delete('/employee-non-dtr/attachment/{id}', 'NonDTRController@employeeDeleteAttachment');
    Route::get('/employee-non-dtr/attachment/{id}/download', 'NonDTRController@downloadAttachment');

    // Service Rendered (COS Certificate)
    Route::get('/service-rendered/form-data', 'ServiceRenderedController@formData');
    Route::post('/service-rendered/print', 'ServiceRenderedController@print');
    Route::get('/service-rendered-applications/{id}', 'ServiceRenderedController@employeeApplications');
    Route::post('/service-rendered-applications', 'ServiceRenderedController@storeApplication');
    Route::put('/service-rendered-applications/{id}', 'ServiceRenderedController@updateApplication');
    Route::get('/service-rendered-applications/{id}/certificate', 'ServiceRenderedController@downloadCertificate');
    Route::get('/service-rendered-approver-access/{id}', 'ServiceRenderedController@checkApproverAccess');
    Route::get('/review-service-rendered/{id}', 'ServiceRenderedController@loadRequests');
    Route::get('/review-service-rendered/{id}/add', 'ServiceRenderedController@reviewRequest');
    Route::get('/review-service-rendered/{id}/approve/{type_id}', 'ServiceRenderedController@approve');
    Route::post('/review-service-rendered/{id}/approve/{type_id}', 'ServiceRenderedController@approve');

    // Non DTR - Division Head Approval
    Route::get('/division-head-non-dtr', 'NonDTRController@divisionHeadList');
    Route::post('/division-head-non-dtr/{id}/approve', 'NonDTRController@divisionHeadApprove');

    // Accomplishment Report - Employee applications & HR approver workflow
    Route::get('/accomplishment-applications/{id}', 'NonDTRController@employeeApplications');
    Route::get('/accomplishment-applications/report/{id}/approved-report', 'NonDTRController@downloadApprovedAccomplishmentReport');
    Route::get('/accomplishment-approver-access/{id}', 'NonDTRController@checkAccomplishmentApproverAccess');
    Route::get('/review-accomplishment-reports/{id}', 'NonDTRController@loadAccomplishmentRequests');
    Route::get('/review-accomplishment-reports/{id}/add', 'NonDTRController@reviewAccomplishmentRequest');
    Route::get('/review-accomplishment-reports/{id}/approve/{type_id}', 'NonDTRController@approveAccomplishmentRequest');

    // Review 201 Updates Routes
    Route::get('/review-201-updates', 'EmployeeRequestController@list');
    Route::get('/review-201-updates/{id}/review', 'EmployeeRequestController@review');
    Route::get('/review-201-updates/{id}/approval/{type_id}', 'EmployeeRequestController@approval');
    Route::post('/review-201-updates/{id}/approve/{type_id}', 'EmployeeRequestController@approve');

    // Update 201 Schedule Routes
    Route::get('/update-201-schedule', 'Update201ScheduleController@index');
    Route::post('/update-201-schedule/{id}', 'Update201ScheduleController@store');

    // Export Employee Data Routes
    Route::get('/export-employee-data', 'EmployeeFileController@export');

    // Vacant Position Posting Routes
    Route::get('/vacant-position-posting', 'VacantPositionController@index');
    Route::get('/vacant-position-details/{id}', 'VacantPositionController@details');
    Route::post('/vacant-position-process/{id}/{process_id}', 'VacantPositionController@process');

    // Examination Setup Routes
    Route::get('/examination-setup', 'ExaminationController@index');
    Route::get('/examination-setup/add/{id}', 'ExaminationController@add');
    Route::get('/examination-setup/{id}/questionnaire/{type_id}', 'ExaminationController@questionaire');
    Route::post('/examination-setup/{id}', 'ExaminationController@store');
    Route::post('/examination-setup/{id}/position', 'ExaminationController@add_position');
    Route::delete('/examination-setup/{id}/position', 'ExaminationController@delete_position');

    // Employee Competency Routes
    Route::get('/employees-competency', 'EmployeesCompetenciesController@index');
    Route::get('/employees-competency/{id}/edit', 'EmployeesCompetenciesController@edit');
    Route::patch('/employees-competency/{id}', 'EmployeesCompetenciesController@update');
    Route::get('/employees-competency/{id}/details', 'EmployeesCompetenciesController@details');

    // Examination Schedule Routes
    Route::get('/examination-schedules', 'ExaminationController@schedules');
    Route::get('/examination-schedules/add/{id}', 'ExaminationController@schedules_add');
    Route::post('/examination-schedules/{id}', 'ExaminationController@schedules_store');
    Route::get('/examination-schedules/{id}/process/{type_id}', 'ExaminationController@schedule_process');
    Route::get('/examination-schedules/{id}/result', 'ExaminationController@schedules_result');
    Route::post('/examination-schedules/{id}/examinees', 'ExaminationController@addExaminees');
    Route::delete('/examination-schedules/{id}/examinees', 'ExaminationController@deleteExaminees');

    // Applicant Ranking and Shortlisting Routes
    Route::get('/applicant-shortlisting', 'ApplicantShortlistingController@index');
    Route::get('/applicant-shortlisting/add/{id}/{rating}/{status_id}', 'ApplicantShortlistingController@add');
    Route::delete('/applicant-shortlisting/{id}', 'ApplicantShortlistingController@delete');

    // Interview Schedule Routes
    Route::get('/interview-schedules', 'InterviewController@index');
    Route::get('/interview-schedules/add/{id}', 'InterviewController@add');
    Route::post('/interview-schedules/{id}', 'InterviewController@store');
    Route::post('/interview-schedules/{id}/panel', 'InterviewController@addPanel');
    Route::post('/interview-schedules/{id}/applicant', 'InterviewController@addApplicant');
    Route::delete('/interview-schedules/{id}/panel', 'InterviewController@deletePanel');
    Route::delete('/interview-schedules/{id}/applicant', 'InterviewController@deleteApplicant');
    Route::get('/interview-schedules/{id}/process/{type_id}', 'InterviewController@process');
    Route::get('/interview-cancel/{id}/{applicant_id}', 'InterviewController@cancelInterview');

    // HRDD Review Routes
    Route::get('/hrdd-review-per-position', 'HRDDReviewController@hrdd_review_per_position');
    Route::get('/hrdd-review/{position_id}', 'HRDDReviewController@index');
    Route::get('/hrdd-review/{id}/pds', 'HRDDReviewController@hrdd_review_pds');
    Route::get('/hrdd-review/{id}/exam', 'HRDDReviewController@hrdd_review_exam');
    Route::get('/hrdd-review/{id}/admin', 'HRDDReviewController@forwardAdmin');
    Route::post('/hrdd-review/{id}/submit', 'HRDDReviewController@submitToAdmin');
    Route::delete('/hrdd-review/{id}/docs/{document_type_id}', 'HRDDReviewController@deleteDocs');
    Route::post('/hrdd-review', 'HRDDReviewController@hrdd_review_store');

    // Administrator Selection Routes
    Route::get('/administrator-selection-per-position', 'AdministratorSelectionController@administrator_selection_per_position');
    Route::get('/administrator-selection/{position_id}', 'AdministratorSelectionController@index');
    Route::get('/administrator-selection/{id}/pds', 'AdministratorSelectionController@administrator_selection_pds');
    Route::get('/administrator-selection/{id}/exam', 'AdministratorSelectionController@administrator_selection_exam');
    Route::get('/administrator-selection/{id}/hrdd-rating', 'AdministratorSelectionController@hrdd_rating');
    Route::get('/administrator-selection/{id}/download/{type_id}', 'AdministratorSelectionController@download');
    Route::get('/administrator-selection/{id}/appoint', 'AdministratorSelectionController@appoint');
    Route::post('/administrator-selection/{id}/br-upload', 'AdministratorSelectionController@br_upload');
    Route::post('/cs-form5-print', 'AdministratorSelectionController@print');

    // Training Management Routes
    Route::get('/training-management', 'EmployeeTrainingController@index');
    Route::post('/training-management', 'EmployeeTrainingController@process');
    Route::post('/training-management/{id}/submit/{subcompetency_id}', 'EmployeeTrainingController@submittraining');
    Route::post('/training-management/{id}/cancel/{subcompetency_id}', 'EmployeeTrainingController@canceltraining');

    // Training Requisitioner Setup
    Route::get('/training-requisitioners', 'TrainingRequisitionersController@index');

    // Training Requisition
    Route::get('/training-requisitions', 'TrainingRequisitionController@index');
    Route::get('/training-requisitions/{id}/add', 'TrainingRequisitionController@add');
    Route::post('/training-requisitions/{id}', 'TrainingRequisitionController@store');
    Route::get('/training-requisitions/{id}/attachments', 'TrainingRequisitionController@attachments');
    Route::delete('/training-requisitions/{id}/attachments', 'TrainingRequisitionController@remove_attachments');
    Route::get('/training-requisitions/{id}/attachments/download', 'TrainingRequisitionController@downloadAttachment');
    Route::get('/training-requisitions/unassigned-employees', 'TrainingRequisitionController@loadUnassignedEmployees');
    Route::post('/training-requisitions/{id}/employees', 'TrainingRequisitionController@addEmployees');
    Route::delete('/training-requisitions/{header_id}/employees/{id}', 'TrainingRequisitionController@removeEmployees');

    // HR Reports
    // Personal Data Sheet
    Route::get('/pds', 'PersonalDataSheetController@index');
    Route::post('/pds/print', 'PersonalDataSheetController@print');
    Route::get('/pds/{id}/download', 'PersonalDataSheetController@download');

    // Employee Certificates
    Route::get('/employee-certificates', 'EmployeeCertificateReportController@index');
    Route::post('/employee-certificates/print', 'EmployeeCertificateReportController@print');

    // Employee Compensation Certificates
    Route::get('/employee-compensation-certificates', 'EmployeeCertificateCompensationReportController@index');
    Route::post('/employee-compensation-certificates/print', 'EmployeeCertificateCompensationReportController@print');

    // Employee Dependent Certificates
    Route::get('/employee-dependent-certificates', 'EmployeeDependentCertificateController@index');
    Route::post('/employee-dependent-certificates/print', 'EmployeeDependentCertificateController@print');

    // Employee Medical Certificates
    Route::get('/employee-medical-certificates', 'EmployeeMedicalCertificateController@index');
    Route::post('/employee-medical-certificates/print', 'EmployeeMedicalCertificateController@print');

    // Appointment Certificate
    Route::get('/appointment-certificate', 'AppointmentCertificateController@index');
    Route::post('/appointment-certificate/print', 'AppointmentCertificateController@print');

    // Assumption of Duty
    Route::get('/assumption-of-duty', 'AssumptionOfDutyController@index');
    Route::post('/assumption-of-duty/print', 'AssumptionOfDutyController@print');

    // Plantilla of Casual Appointment
    Route::get('/casual-appointment', 'PlantillaOfCasualAppointmentController@index');
    Route::post('/casual-appointment/print', 'PlantillaOfCasualAppointmentController@print');

    // Acceptance of Resignation
    Route::get('/acceptance-of-resignation', 'AcceptanceofResignationController@index');
    Route::post('/acceptance-of-resignation/print', 'AcceptanceofResignationController@print');

    // Oath of Office
    Route::get('/oath-of-office', 'OathOfOfficeController@index');
    Route::post('/oath-of-office/print', 'OathOfOfficeController@print');

    // Congratulatory Letter
    Route::get('/congratulatory-letter', 'CongratulatoryLetterController@index');
    Route::post('/congratulatory-letter/print', 'CongratulatoryLetterController@print');

    // Board Resolution
    Route::get('/board-resolution', 'BoardResolutionController@index');
    Route::post('/board-resolution/print', 'BoardResolutionController@print');

    // NOSI
    Route::get('/nosi', 'NoticeOfSalaryStepController@index');
    Route::post('/nosi/print', 'NoticeOfSalaryStepController@print');

    // NOSA
    Route::get('/nosa', 'NoticeOfSalaryAdjustmentController@index');
    Route::post('/nosa/print', 'NoticeOfSalaryAdjustmentController@print');

    // Service Record
    Route::get('/service-record', 'ServiceRecordController@index');
    Route::post('/service-record/print', 'ServiceRecordController@print');

    // No Pending Certificate
    Route::get('/no-pending-certificates', 'NoPendingCertificateReportController@index');
    Route::post('/no-pending-certificates/print', 'NoPendingCertificateReportController@print');

    // ATM Request Certificate
    Route::get('/atm-request-certificates', 'ATMRequestCertificateReportController@index');
    Route::post('/atm-request-certificates/print', 'ATMRequestCertificateReportController@print');

    // Offboarding Certificate
    Route::get('/offboarding-certificates', 'OffboardingCertificateReportController@index');
    Route::post('/offboarding-certificates/print', 'OffboardingCertificateReportController@print');

    // Appearance Certificate
    Route::get('/appearance-certificates', 'AppearanceCertificateReportController@index');
    Route::post('/appearance-certificates/print', 'AppearanceCertificateReportController@print');

    // OJT Certificate
    Route::get('/ojt-certificates', 'OJTCertificateReportController@index');
    Route::get('/ojt-certificates/create', 'OJTCertificateReportController@add');
    Route::post('/ojt-certificates', 'OJTCertificateReportController@store');
    Route::get('/ojt-certificates/{id}/edit', 'OJTCertificateReportController@edit');
    Route::patch('/ojt-certificates/{id}', 'OJTCertificateReportController@update');
    Route::get('/ojt-certificates/{id}/print', 'OJTCertificateReportController@print');

    // Terminal Leave Endorsement
    Route::get('/terminal-leave-endorsements', 'TerminalLeaveEndorsementReportController@index');
    Route::post('/terminal-leave-endorsements/print', 'TerminalLeaveEndorsementReportController@print');

    // Travel Abroad Endorsement
    Route::get('/travel-abroad-endorsements', 'TravelAbroadEndorsementReportController@index');
    Route::post('/travel-abroad-endorsements/print', 'TravelAbroadEndorsementReportController@print');

    // Land Registration Endorsement
    Route::get('/land-registration-endorsements', 'LandRegistrationEndorsementReportController@index');
    Route::post('/land-registration-endorsements/print', 'LandRegistrationEndorsementReportController@print');

    // DOJ Endorsement
    Route::get('/doj-endorsements', 'DOJEndorsementReportController@index');
    Route::post('/doj-endorsements/print', 'DOJEndorsementReportController@print');

    // Plantilla Report
    Route::get('/plantilla-reports', 'PlantillaReportController@index');
    Route::post('/plantilla-reports/print', 'PlantillaReportController@print');

    // Length of Service
    Route::get('/length-of-service', 'LengthofServiceController@index');

    // Time Keeping Module
    // Fix Schedule
    Route::get('/fix-schedules', 'FixScheduleController@index');
    Route::get('/fix-schedules/{id}/add', 'FixScheduleController@add');
    Route::post('/fix-schedules/{id}', 'FixScheduleController@store');

    // Assign Schedule
    Route::get('/assign-schedules', 'AssignFixScheduleController@index');
    Route::post('/assign-schedules', 'AssignFixScheduleController@store');

    // Leave Credits
    Route::get('/leave-credits', 'LeaveCreditController@index');
    Route::post('/leave-credits', 'LeaveCreditController@store');

    // Shifting Schedule
    Route::get('/shift-schedules', 'ShiftScheduleController@index');
    Route::get('/shift-schedules/{id}/add', 'ShiftScheduleController@add');
    Route::post('/shift-schedules/{id}', 'ShiftScheduleController@store');
    Route::get('/shift-schedules/unassigned-employees', 'ShiftScheduleController@loadUnassignedEmployees');
    Route::post('/shift-schedules/{id}/employees', 'ShiftScheduleController@addEmployees');
    Route::delete('/shift-schedules/{header_id}/employees/{id}', 'ShiftScheduleController@removeEmployees');

    // Process Daily Time Records
    Route::post('/process-attendance', 'ProcessAttendanceController@process');
    Route::get('/process-attendance', 'ProcessAttendanceController@index');
    Route::get('/process-attendance/{id}/{payroll_period_id}', 'ProcessAttendanceController@view');
    Route::post('/process-attendance/{id}/{payroll_period_id}/reprocess', 'ProcessAttendanceController@reprocess');
    Route::get('/process-attendance/{employee_id}/{payroll_period_id}/report', 'ProcessAttendanceController@print');
    Route::post('/process-attendance/offset', 'ProcessAttendanceController@offset');
    Route::post('/process-attendance/{id}/{payroll_period_id}/cancel-offset', 'ProcessAttendanceController@cancel_offset');
    Route::post('/process-attendance/{id}/{payroll_period_id}/offset-details', 'ProcessAttendanceController@offset_details');
    Route::post('/process-attendance/{id}/{payroll_period_id}/cancel-offset-details', 'ProcessAttendanceController@cancel_offset_details');

    // Leave Approval
    Route::get('/leave-approvals', 'LeaveController@monitoring');

    // OB Approval
    Route::get('/official-business-approvals', 'OfficialBusinessApplicationController@monitoring');

    // OT Approval
    Route::get('/overtime-approvals', 'OvertimeApplicationController@monitoring');

    // Work Cancellation
    Route::get('/work-cancellations', 'WorkCancellationController@index');
    Route::get('/work-cancellations/{id}/add', 'WorkCancellationController@add');
    Route::post('/work-cancellations/{id}', 'WorkCancellationController@store');

    // COC Details
    Route::get('/coc-details', 'COCDetailsController@index');

    // Tardiness Report
    Route::get('/tardiness-report', 'TradionessReportController@index');
    Route::get('/tardiness-report/view', 'TradionessReportController@view');

    // Leave Taken Monitoring
    Route::get('/leave-taken-monitoring', 'LeaveTakenController@index');
    Route::get('/leave-taken/{id}', 'LeaveTakenController@load');

    // Leave Credit Card
    Route::get('/leave-credit-card', 'LeaveCreditCardController@index');
    Route::get('/leave-credit-card/{id}/details', 'LeaveCreditCardController@details');
    Route::get('/leave-credit-card/{id}/{year}/load', 'LeaveCreditCardController@getLeaveCredits');

    // Biometrics Data
    Route::get('/biometrics', 'BiometricsController@index');
    Route::get('/biometrics/load', 'BiometricsController@load');
    Route::get('/biometrics/setup', 'BiometricsController@configLoad');
    Route::post('/biometrics/setup', 'BiometricsController@config');
    // Payroll Module
    // Payroll Period
    Route::get('/payroll-periods', 'PayrollPeriodController@index');
    Route::get('/payroll-periods/{id}/add', 'PayrollPeriodController@add');
    Route::post('/payroll-periods/{id}', 'PayrollPeriodController@store');

    // Payroll Item Schedule
    Route::get('/payroll-item-schedules', 'PayrollItemScheduleController@index');
    Route::post('/payroll-item-schedules', 'PayrollItemScheduleController@store');
    Route::get('/payroll-item-schedules/{payroll_interval_type_id}/{payroll_period_type_id}/{employment_type_id}/incomes', 'PayrollItemScheduleController@getIncome');
    Route::get('/payroll-item-schedules/{payroll_interval_type_id}/{payroll_period_type_id}/{employment_type_id}/deductions', 'PayrollItemScheduleController@getDeduction');
    Route::get('/payroll-item-schedules/{payroll_interval_type_id}/{payroll_period_type_id}/{employment_type_id}/header', 'PayrollItemScheduleController@getHeader');

    // Payroll Income and Deduction
    Route::get('/payroll-income-and-deductions', 'IncomeDeductionAjustmentController@index');
    Route::get('/payroll-income-and-deductions/{payroll_period_type_id}/{employment_type_id}/incomes', 'IncomeDeductionAjustmentController@getIncomeList');
    Route::get('/payroll-income-and-deductions/{payroll_period_type_id}/{employment_type_id}/deductions', 'IncomeDeductionAjustmentController@getDeductionList');
    Route::get('/payroll-income-and-deductions/{payroll_period_type_id}/{employment_type_id}/{income_id}/employee-income', 'IncomeDeductionAjustmentController@getEmployeeIncome');
    Route::get('/payroll-income-and-deductions/{payroll_period_type_id}/{employment_type_id}/{income_id}/employee-previous-income', 'IncomeDeductionAjustmentController@getEmployeePreviousIncome');
    Route::get('/payroll-income-and-deductions/{payroll_period_type_id}/{employment_type_id}/{deduction_id}/employee-deduction', 'IncomeDeductionAjustmentController@getEmployeeDeduction');
    Route::get('/payroll-income-and-deductions/{payroll_period_type_id}/{employment_type_id}/{deduction_id}/employee-previous-deduction', 'IncomeDeductionAjustmentController@getEmployeePreviousDeduction');
    Route::get('/payroll-income-and-deductions/{payroll_period_type_id}/{employment_type_id}/{type_id}/{item_id}/employee-list', 'IncomeDeductionAjustmentController@getEmployeeList');
    Route::post('/payroll-income', 'IncomeDeductionAjustmentController@storeIncome');
    Route::post('/payroll-deduction', 'IncomeDeductionAjustmentController@storeDeduction');

    // Loan Application
    Route::get('/loan-applications', 'LoanApplicationController@index');
    Route::get('/loan-applications/{id}/add', 'LoanApplicationController@add');
    Route::post('/loan-applications/{id}', 'LoanApplicationController@store');
    Route::get('/loan-applications/{id}/reconstruct', 'LoanApplicationController@reconstruct');
    Route::post('/loan-applications/{id}/reconstruct', 'LoanApplicationController@reconstructstore');
    Route::get('/loan-applications/{id}/validation', 'LoanApplicationController@loanValidation');

    // Payroll Process
    Route::get('/payroll-process', 'PayrollProcessController@index');
    Route::get('/payroll-process/{id}/summary', 'PayrollProcessController@summary');
    Route::post('/payroll-process/{id}', 'PayrollProcessController@process');
    Route::post('/payroll-process/{id}/{type_id}/posting', 'PayrollProcessController@posting');
    Route::get('/payroll-process/{id}/print', 'PayrollProcessController@printTabulate');
    Route::get('/payroll-summary', 'PayrollProcessController@payrollSummary');
    Route::get('/payroll-summary-detail', 'PayrollProcessController@payrollSummaryDetail');
    Route::get('/payroll-summary/print', 'PayrollProcessController@payrollSummaryPrint');
    Route::get('/payroll-summary-with-details/print', 'PayrollProcessController@payrollSummaryDetailsPrint');
    Route::post('/tax-amount-adjustment/{payroll_period_id}', 'PayrollProcessController@adjustment');

    // Overtime Payroll Process
    Route::get('/overtime-payroll', 'OvertimePayrollController@load');
    Route::get('/overtime-payroll/{id}/add', 'OvertimePayrollController@add');
    Route::post('/overtime-payroll/{id}', 'OvertimePayrollController@storeOvertimePayroll');
    Route::post('/overtime-payroll/{id}/employees', 'OvertimePayrollController@storeOvertimePayrollEmployee');
    Route::delete('/overtime-payroll/{id}/employees', 'OvertimePayrollController@deleteOvertimePayrollEmployee');
    Route::post('/overtime-payroll/{id}/{type_id}/posting', 'OvertimePayrollController@processOvertimePayroll');
    Route::get('/overtime-payroll/process', 'OvertimePayrollController@index');
    Route::get('/overtime-payroll/print', 'OvertimePayrollController@print');

    // PACSVAL
    Route::get('/pacsval', 'PACSVALController@index');
    Route::post('/pacsval/export', 'PACSVALController@export');

    // Payslip Report
    Route::get('/payment-slip', 'EmployeePayslipReportController@index');
    Route::post('/payment-slip/print', 'EmployeePayslipReportController@print');

    // Subsistence Report
    Route::get('/subsistence-report', 'SubsistenceController@index');
    Route::post('/subsistence-report/print', 'SubsistenceController@print');

    // Pending Deductions
    Route::get('/pending-deduction-report', 'PendingDeductionController@index');
    Route::get('/pending-deduction-report/view', 'PendingDeductionController@view');

    // RATA Report
    Route::get('/rata-payroll-report', 'RATAController@rataReport');
    Route::get('/rata-payroll/print', 'RATAController@print');

    // Hazard Pay Allowance Report
    Route::get('/hazard-pay-report', 'HazardPayController@Report');
    Route::get('/hazard-pay/print', 'HazardPayController@print');

    // Process Mid Year Bonus
    Route::post('/process-midyear', 'MidYearBonusController@process');
    Route::get('/process-midyear', 'MidYearBonusController@index');
    Route::post('/midyear/post', 'MidYearBonusController@post');

    // Process Year End Bonus
    Route::post('/process-yearend', 'YearEndBonusController@process');
    Route::get('/process-yearend', 'YearEndBonusController@index');
    Route::post('/yearend/post', 'YearEndBonusController@post');

    // Mid Year Bonus Report
    Route::get('/midyear-report', 'MidYearBonusController@report');
    Route::post('/midyear/print', 'MidYearBonusController@print');

    // Year End Bonus Report
    Route::get('/yearend-report', 'YearEndBonusController@report');
    Route::post('/yearend/print', 'YearEndBonusController@print');

    // Philhealth Remittance Report
    Route::get('/philhealth-remittance', 'PhilhealthRemittanceController@report');
    Route::post('/philhealth-remittance/print', 'PhilhealthRemittanceController@print');

    // Pag Ibig Loan Report
    Route::get('/pagibig-loan', 'PagIbigLoanController@index');
    Route::post('/print/mandatory', 'PagIbigLoanController@print');
    Route::post('/print/mp2', 'PagIbigLoanController@printMP2');
    Route::get('/pag-ibig-loan', 'PagIbigLoanController@loan');
    Route::post('/loan/print', 'PagIbigLoanController@loanprint');

    // GSIS Remittance Report
    Route::get('/gsis-remittance', 'GSISRemittanceController@index');
    Route::post('/gsis-remittance/print', 'GSISRemittanceController@print');

    // Bank Remittance Report
    Route::get('/bank-remittance', 'BankRemittanceController@index');
    Route::post('/bank-remittance/print', 'BankRemittanceController@print');

    // Monetization Payroll
    Route::get('/monetization-payroll', 'MonetizationController@loadMonetizationPayroll');
    Route::get('/monetization-payroll/{id}/add', 'MonetizationController@addMonetizationPayroll');
    Route::post('/monetization-payroll/{id}', 'MonetizationController@storeMonetizationPayroll');
    Route::post('/monetization-payroll/{id}/employees', 'MonetizationController@storeMonetizationPayrollEmployee');
    Route::delete('/monetization-payroll/{id}/employees', 'MonetizationController@deleteMonetizationPayrollEmployees');
    Route::post('/monetization-payroll/{id}/{type_id}/process', 'MonetizationController@processMonetizationPayroll');
    Route::get('/monetization-payroll/report', 'MonetizationController@monetizationReport');
    Route::get('/monetization-payroll/print', 'MonetizationController@print');

    // Obligation Requests Report
    Route::get('/ors-payroll-report/{id}', 'ObligationRequestController@orsPayroll');
    Route::get('/ors-overtime-report/{id}', 'ObligationRequestController@orsOvertime');
    Route::get('/ors-rata-report/{id}', 'ObligationRequestController@orsRATA');
    Route::get('/ors-monetization-report/{id}', 'ObligationRequestController@orsMonetization');
    Route::get('/ors-clothing-report/{id}', 'ObligationRequestController@orsClothing');
    Route::get('/ors-loyalty-report/{id}', 'ObligationRequestController@orsLoyalty');
    Route::get('/ors-midyear-report/{year_id}', 'ObligationRequestController@orsMidYear');
    Route::get('/ors-yearend-report/{year_id}', 'ObligationRequestController@orsYearEnd');

    // Disbursement Vouchers Report
    Route::get('/dv-payroll-report/{id}', 'DisbursementVoucherController@dvPayroll');
    Route::get('/dv-overtime-report/{id}', 'DisbursementVoucherController@dvOvertime');
    Route::get('/dv-rata-report/{id}', 'DisbursementVoucherController@dvRATA');
    Route::get('/dv-monetization-report/{id}', 'DisbursementVoucherController@dvMonetization');
    Route::get('/dv-clothing-report/{id}', 'DisbursementVoucherController@dvClothing');
    Route::get('/dv-loyalty-report/{id}', 'DisbursementVoucherController@dvLoyalty');
    Route::get('/dv-midyear-report/{year_id}', 'DisbursementVoucherController@dvMidYear');
    Route::get('/dv-yearend-report/{year_id}', 'DisbursementVoucherController@dvYearEnd');

    // Payroll Extra Bonus
    Route::get('/payroll-extra-bonus', 'PayrollExtraBonusController@index');
    Route::get('/payroll-extra-bonus/{extra_bonus_id}/add', 'PayrollExtraBonusController@show');
    Route::post('/payroll-extra-bonus', 'PayrollExtraBonusController@store');
    Route::get('/payroll-extra-bonus/{extra_bonus_id}/{type_id}/process', 'PayrollExtraBonusController@process');
    Route::get('/payroll-extra-bonus/report', 'PayrollExtraBonusController@report');
    Route::get('/payroll-extra-bonus/print', 'PayrollExtraBonusController@print');
    Route::get('/payroll-extra-bonus/{extra_bonus_type_id}/{department_id}/{year_id}/employees', 'PayrollExtraBonusController@loadEmployees');

    // Pagibig Payroll Amount
    Route::get('/pagibig-payroll', 'PagibigPayrollHeaderController@index');
    Route::get('/pagibig-payroll/{payroll_period_id}/employees', 'PagibigPayrollHeaderController@loadEmployees');
    Route::post('/pagibig-payroll/employees', 'PagibigPayrollHeaderController@store');
    Route::get('/pagibig-payroll/{employee_id}/{amount}/{payroll_period_id}/validate', 'PagibigPayrollHeaderController@validateAmount');

    // PagIbig Setup
    Route::get('/pagibig-setup', 'PagibigSetupController@index');
    Route::get('/pagibig-setup/{id}/delete', 'PagibigSetupController@delete');
    Route::post('/pagibig-setup', 'PagibigSetupController@store');
    Route::delete('/pagibig-setup/{id}', 'PagibigSetupController@destroy');

    // Control Panel Routes
    // User Profile
    Route::get('/profile', 'ProfileController@index');
    Route::patch('/profile', 'ProfileController@update');
    Route::get('/profile/change-password', 'ProfileController@profilePassword');

    // User List
    Route::get('/users', 'UsersController@index');
    Route::post('/users', 'UsersController@store');

    // User List Access Rights
    Route::get('/access-rights/{id}', 'AccessRightsController@index');
    Route::post('/access-rights/{id}', 'AccessRightsController@update_access');

    // User Activity List
    Route::get('/audits', 'AuditController@index');
    Route::get('/audits/optimize', 'AuditController@lazyGet');

    // Department Setup
    Route::get('/departments', 'DepartmentsController@index');
    Route::get('/departments/create', 'DepartmentsController@add');
    Route::post('/departments', 'DepartmentsController@store');
    Route::get('/departments/{id}/edit', 'DepartmentsController@edit');
    Route::patch('/departments/{id}', 'DepartmentsController@update');

    // Division Setup
    Route::get('/divisions', 'DivisionsController@index');
    Route::get('/divisions/create', 'DivisionsController@add');
    Route::post('/divisions', 'DivisionsController@store');
    Route::get('/divisions/{id}/edit', 'DivisionsController@edit');
    Route::patch('/divisions/{id}', 'DivisionsController@update');

    // Section Setup
    Route::get('/sections', 'SectionsController@index');
    Route::get('/sections/create', 'SectionsController@add');
    Route::post('/sections', 'SectionsController@store');
    Route::get('/sections/{id}/edit', 'SectionsController@edit');
    Route::patch('/sections/{id}', 'SectionsController@update');

    // Position Setup
    Route::get('/positions', 'PositionsController@index');
    Route::get('/positions/create', 'PositionsController@add');
    Route::post('/positions', 'PositionsController@store');
    Route::get('/positions/{id}/edit', 'PositionsController@edit');
    Route::patch('/positions/{id}', 'PositionsController@update');

    // Employment Type
    Route::get('/employment-types', 'EmploymentTypesController@index');
    Route::get('/employment-types/create', 'EmploymentTypesController@add');
    Route::post('/employment-types', 'EmploymentTypesController@store');
    Route::get('/employment-types/{id}/edit', 'EmploymentTypesController@edit');
    Route::patch('/employment-types/{id}', 'EmploymentTypesController@update');

    // Civil Status
    Route::get('/civil-status', 'CivilStatusController@index');
    Route::get('/civil-status/create', 'CivilStatusController@add');
    Route::post('/civil-status', 'CivilStatusController@store');
    Route::get('/civil-status/{id}/edit', 'CivilStatusController@edit');
    Route::patch('/civil-status/{id}', 'CivilStatusController@update');

    // Citizenship Setup
    Route::get('/citizenships', 'CitizenshipsController@index');
    Route::get('/citizenships/create', 'CitizenshipsController@add');
    Route::post('/citizenships', 'CitizenshipsController@store');
    Route::get('/citizenships/{id}/edit', 'CitizenshipsController@edit');
    Route::patch('/citizenships/{id}', 'CitizenshipsController@update');

    // Gender Setup
    Route::get('/genders', 'GendersController@index');
    Route::get('/genders/create', 'GendersController@add');
    Route::post('/genders', 'GendersController@store');
    Route::get('/genders/{id}/edit', 'GendersController@edit');
    Route::patch('/genders/{id}', 'GendersController@update');

    // Religion Setup
    Route::get('/religions', 'ReligionsController@index');
    Route::get('/religions/create', 'ReligionsController@add');
    Route::post('/religions', 'ReligionsController@store');
    Route::get('/religions/{id}/edit', 'ReligionsController@edit');
    Route::patch('/religions/{id}', 'ReligionsController@update');

    // Name Prefix
    Route::get('/name-prefixes', 'NamePrefixController@index');
    Route::get('/name-prefixes/create', 'NamePrefixController@add');
    Route::post('/name-prefixes', 'NamePrefixController@store');
    Route::get('/name-prefixes/{id}/edit', 'NamePrefixController@edit');
    Route::patch('/name-prefixes/{id}', 'NamePrefixController@update');
    Route::get('/name-prefixes/{id}/delete', 'NamePrefixController@delete');

    // Name Suffix
    Route::get('/name-suffixes', 'NameSuffixController@index');
    Route::get('/name-suffixes/create', 'NameSuffixController@add');
    Route::post('/name-suffixes', 'NameSuffixController@store');
    Route::get('/name-suffixes/{id}/edit', 'NameSuffixController@edit');
    Route::patch('/name-suffixes/{id}', 'NameSuffixController@update');
    Route::get('/name-suffixes/{id}/delete', 'NameSuffixController@delete');

    // Eligibility Setup
    Route::get('/eligibilities', 'EligibilityController@index');
    Route::get('/eligibilities/create', 'EligibilityController@add');
    Route::post('/eligibilities', 'EligibilityController@store');
    Route::get('/eligibilities/{id}/edit', 'EligibilityController@edit');
    Route::patch('/eligibilities/{id}', 'EligibilityController@update');

    // Learning and Development Setup
    Route::get('/learnings', 'LearningsController@index');
    Route::get('/learnings/create', 'LearningsController@add');
    Route::post('/learnings', 'LearningsController@store');
    Route::get('/learnings/{id}/edit', 'LearningsController@edit');
    Route::patch('/learnings/{id}', 'LearningsController@update');

    // Plantilla Setup
    Route::get('/plantillas', 'PlantillasController@index');
    Route::get('/plantillas/create', 'PlantillasController@add');
    Route::post('/plantillas', 'PlantillasController@store');
    Route::get('/plantillas/{id}/edit', 'PlantillasController@edit');
    Route::patch('/plantillas/{id}', 'PlantillasController@update');
    Route::get('/plantillas/{type_id}/{id}/delete', 'PlantillasController@delete');
    Route::delete('/plantillas/{type_id}/{id}', 'PlantillasController@destroy');
    Route::get('/vacancies', 'VacanciesController@index');

    // Non Plantilla Setup
    Route::get('/non-plantillas', 'NonPlantillasController@index');
    Route::get('/non-plantillas/{id}/add', 'NonPlantillasController@add');
    Route::post('/non-plantillas/{id}', 'NonPlantillasController@store');

    // Salary Schedule Setup
    Route::get('/salary-schedules', 'SalarySchedulesController@index');
    Route::get('/salary-schedules/create', 'SalarySchedulesController@add');
    Route::post('/salary-schedules', 'SalarySchedulesController@store');
    Route::get('/salary-schedules/{id}/edit', 'SalarySchedulesController@edit');
    Route::patch('/salary-schedules/{id}', 'SalarySchedulesController@update');
    Route::get('/salary-schedules/{id}/delete', 'SalarySchedulesController@delete');
    Route::delete('/salary-schedules/{id}', 'SalarySchedulesController@destroy');

    // Tax Table Setup
    Route::get('/tax-tables', 'TaxController@index');
    Route::get('/tax-tables/{id}/delete', 'TaxController@delete');
    Route::post('/tax-tables', 'TaxController@store');
    Route::delete('/tax-tables/{id}', 'TaxController@destroy');

    // PhilHealth Setup
    Route::get('/philhealth-tables', 'PhilhealthController@index');
    Route::get('/philhealth-tables/{id}/delete', 'PhilhealthController@delete');
    Route::post('/philhealth-tables', 'PhilhealthController@store');
    Route::delete('/philhealth-tables/{id}', 'PhilhealthController@destroy');

    // GSIS Setup
    Route::get('/gsis-tables', 'GSISController@index');
    Route::get('/gsis-tables/{id}/delete', 'GSISController@delete');
    Route::post('/gsis-tables', 'GSISController@store');
    Route::delete('/gsis-tables/{id}', 'GSISController@destroy');
    Route::get('/gsis', 'GSISController@index');
    Route::get('/gsis/{id}/add', 'GSISController@add');
    Route::post('/gsis/{id}', 'GSISController@storeGSIS');

    // SSS Setup
    Route::get('/sss-tables', 'SSSController@index');
    Route::get('/sss-tables/{id}/delete', 'SSSController@delete');
    Route::post('/sss-tables', 'SSSController@store');
    Route::delete('/sss-tables/{id}', 'SSSController@destroy');

    // Salary Step Table Setup
    Route::get('/salary-steps', 'SalaryStepController@index');
    Route::get('/salary-steps/{id}/delete', 'SalaryStepController@delete');
    Route::post('/salary-steps', 'SalaryStepController@store');
    Route::delete('/salary-steps/{id}', 'SalaryStepController@destroy');

    // Salary Grade Table Setup
    Route::get('/salary-grades', 'SalaryGradeController@index');
    Route::get('/salary-grades/{id}/delete', 'SalaryGradeController@delete');
    Route::post('/salary-grades', 'SalaryGradeController@store');
    Route::delete('/salary-grades/{id}', 'SalaryGradeController@destroy');

    // Company Setup
    Route::get('/companies', 'CompanyController@index');
    Route::post('/companies', 'CompanyController@store');

    // Branch Setup
    Route::get('/branches', 'BranchController@index');
    Route::get('/branches/{id}/delete', 'BranchController@delete');
    Route::post('/branches', 'BranchController@store');
    Route::delete('/branches/{id}', 'BranchController@destroy');

    // Blood Type Setup
    Route::get('/blood-types', 'BloodTypeController@index');
    Route::get('/blood-types/{id}/delete', 'BloodTypeController@delete');
    Route::post('/blood-types', 'BloodTypeController@store');
    Route::delete('/blood-types/{id}', 'BloodTypeController@destroy');

    // Payroll Interval Setup
    Route::get('/payroll-intervals', 'PayrollIntervalController@index');
    Route::get('/payroll-intervals/{id}/delete', 'PayrollIntervalController@delete');
    Route::post('/payroll-intervals', 'PayrollIntervalController@store');
    Route::delete('/payroll-intervals/{id}', 'PayrollIntervalController@destroy');

    // Payroll Cut-off
    Route::get('/payroll-cutoffs', 'PayrollCutOffController@index');
    Route::get('/payroll-cutoffs/{id}/add', 'PayrollCutOffController@add');
    Route::post('/payroll-cutoffs/{id}', 'PayrollCutOffController@store');

    // Promotion Type Setup
    Route::get('/promotion-types', 'PromotionTypeController@index');
    Route::get('/promotion-types/{id}/delete', 'PromotionTypeController@delete');
    Route::post('/promotion-types', 'PromotionTypeController@store');
    Route::delete('/promotion-types/{id}', 'PromotionTypeController@destroy');

    // Off-Boarding Type Setup
    Route::get('/offboarding-types', 'OffBoardingTypeController@index');
    Route::get('/offboarding-types/{id}/delete', 'OffBoardingTypeController@delete');
    Route::post('/offboarding-types', 'OffBoardingTypeController@store');
    Route::delete('/offboarding-types/{id}', 'OffBoardingTypeController@destroy');

    // Overtime Types
    Route::get('/overtime-types', 'OvertimeTypeController@index');
    Route::get('/overtime-types/{id}/delete', 'OvertimeTypeController@delete');
    Route::post('/overtime-types', 'OvertimeTypeController@store');
    Route::delete('/overtime-types/{id}', 'OvertimeTypeController@destroy');

    // Holiday Types
    Route::get('/holiday-types', 'HolidayTypeController@index');
    Route::get('/holiday-types/{id}/delete', 'HolidayTypeController@delete');
    Route::post('/holiday-types', 'HolidayTypeController@store');
    Route::delete('/holiday-types/{id}', 'HolidayTypeController@destroy');

    // Holidays
    Route::get('/holidays', 'HolidayController@index');
    Route::get('/holidays/{id}/delete', 'HolidayController@delete');
    Route::post('/holidays', 'HolidayController@store');
    Route::delete('/holidays/{id}', 'HolidayController@destroy');

    // Leave Types
    Route::get('/leave-types', 'LeaveTypeController@index');
    Route::get('/leave-types/{id}/delete', 'LeaveTypeController@delete');
    Route::post('/leave-types', 'LeaveTypeController@store');
    Route::delete('/leave-types/{id}', 'LeaveTypeController@destroy');

    // Official Business Types
    Route::get('/official-business-types', 'OfficialBusinessTypeController@index');
    Route::get('/official-business-types/{id}/delete', 'OfficialBusinessTypeController@delete');
    Route::post('/official-business-types', 'OfficialBusinessTypeController@store');
    Route::delete('/official-business-types/{id}', 'OfficialBusinessTypeController@destroy');

    // Holiday Taggings
    Route::get('/holiday-taggings', 'HolidayTaggingController@index');
    Route::get('/holiday-taggings/{id}/{holiday_id}/delete', 'HolidayTaggingController@delete');
    Route::post('/holiday-taggings', 'HolidayTaggingController@store');
    Route::delete('/holiday-taggings/{id}/{holiday_id}', 'HolidayTaggingController@destroy');

    // TimeKeeping Setup
    Route::get('/time-keeping-setups', 'TimeKeepingSetupController@index');
    Route::get('/time-keeping-setups/{id}/data', 'TimeKeepingSetupController@getdata');
    Route::post('/time-keeping-setups/update', 'TimeKeepingSetupController@store');

    // Deduction Types Setup
    Route::get('/deduction-types', 'DeductionController@index');
    Route::post('/deduction-types', 'DeductionController@store');
    Route::delete('/deduction-types/{id}', 'DeductionController@destroy');

    // Income Types Setup
    Route::get('/income-types', 'IncomeController@index');
    Route::post('/income-types', 'IncomeController@store');
    Route::delete('/income-types/{id}', 'IncomeController@destroy');

    // Deduction Priority Setup
    Route::get('/deduction-priorities', 'DeductionPriorityController@index');
    Route::post('/deduction-priorities', 'DeductionPriorityController@store');

    // Adjectival Ratings
    Route::get('/ratings', 'RatingController@index');
    Route::post('/ratings', 'RatingController@store');
    Route::delete('/ratings/{id}', 'RatingController@destroy');

    // Semester Ratings
    Route::get('/semester-ratings', 'SemesterRatingController@index');
    Route::get('/semester-ratings/create', 'SemesterRatingController@add');
    Route::post('/semester-ratings', 'SemesterRatingController@store');
    Route::get('/semester-ratings/{id}/edit', 'SemesterRatingController@edit');
    Route::patch('/semester-ratings/{id}', 'SemesterRatingController@update');

    // Leave Approvers
    Route::get('/leave-approvers', 'LeaveApproverController@index');
    Route::get('/leave-approvers/{id}/add', 'LeaveApproverController@add');
    Route::post('/leave-approvers/{id}', 'LeaveApproverController@store');
    Route::get('/leave-approvers/{department_id}/{id}/{type_id}/subordinates', 'LeaveApproverController@subordinates');
    Route::post('/leave-approvers/{id}/{type_id}/subordinates', 'LeaveApproverController@addSubordinates');
    Route::post('/leave-approvers/{id}/subordinates', 'LeaveApproverController@deleteSubordinates');
    Route::get('/leave-approvers/{id}/departments', 'LeaveApproverController@getDepartments');
    Route::get('/leave-approvers/{id}/divisions', 'LeaveApproverController@getDivisions');
    Route::get('/leave-approvers/{id}/sections', 'LeaveApproverController@getSections');

    // Document Number Setup
    Route::get('/document-numbers', 'DocumentNumberController@index');
    Route::post('/document-numbers', 'DocumentNumberController@store');

    // Competencies Setup
    Route::get('/competencies', 'CompetenciesController@index');
    Route::get('/competencies/create', 'CompetenciesController@add');
    Route::post('/competencies', 'CompetenciesController@store');
    Route::get('/competencies/{id}/edit', 'CompetenciesController@edit');
    Route::patch('/competencies/{id}', 'CompetenciesController@update');
    Route::get('/competencies/{type_id}/{id}/delete', 'CompetenciesController@delete');
    Route::delete('/competencies/{type_id}/{id}', 'CompetenciesController@destroy');

    // Loyalty Award
    Route::get('/loyalty-awards', 'LoyaltyAwardController@index');
    Route::get('/loyalty-awards/{id}/add', 'LoyaltyAwardController@add');
    Route::post('/loyalty-awards/{id}', 'LoyaltyAwardController@store');
    Route::post('/loyalty-awards/{id}/post', 'LoyaltyAwardController@post');
    Route::get('/loyalty-awards/{id}/unpost', 'LoyaltyAwardController@unpost');

    // Loyalty Award Setup
    Route::get('/loyalty-award-setup', 'LoyaltyAwardSetupController@index');
    Route::get('/loyalty-award-setup/{id}/delete', 'LoyaltyAwardSetupController@delete');
    Route::post('/loyalty-award-setup', 'LoyaltyAwardSetupController@store');
    Route::delete('/loyalty-award-setup/{id}', 'LoyaltyAwardSetupController@destroy');

    // Uniform Clothing Setup
    Route::get('/uniform-clothing-setup', 'UniformClothingSetupController@index');
    Route::get('/uniform-clothing-setup/{id}/delete', 'UniformClothingSetupController@delete');
    Route::post('/uniform-clothing-setup', 'UniformClothingSetupController@store');
    Route::delete('/uniform-clothing-setup/{id}', 'UniformClothingSetupController@destroy');

    // Uniform Clothing
    Route::get('/uniform-clothing', 'UniformClothingController@index');
    Route::get('/uniform-clothing/{id}/add', 'UniformClothingController@add');
    Route::post('/uniform-clothing/{id}', 'UniformClothingController@store');
    Route::post('/uniform-clothing/{id}/post', 'UniformClothingController@post');
    Route::get('/uniform-clothing/{id}/unpost', 'UniformClothingController@unpost');
    Route::post('/uniform-clothing/{id}/employees', 'UniformClothingController@addEmployee');
    Route::get('/uniform-clothing/{id}/details/delete', 'UniformClothingController@delete');
    Route::delete('/uniform-clothing/{id}/details', 'UniformClothingController@destroy');

    // RATA Positions Setup
    Route::get('/rata-positions', 'RATAController@index');
    Route::post('/rata-positions', 'RATAController@store');

    // RATA Table Setup
    Route::get('/rata-table', 'RATAController@loadRATATable');
    Route::post('/rata-table', 'RATAController@storeRATATable');
    Route::get('/rata-table/{id}/delete', 'RATAController@deleteRATATable');
    Route::get('/rata-payroll', 'RATAController@loadRATAPayroll');
    Route::get('/rata-payroll/{id}/add', 'RATAController@addRATAPayroll');
    Route::post('/rata-payroll/{id}', 'RATAController@storeRATAPayroll');
    Route::post('/rata-payroll/{id}/employees', 'RATAController@storeRATAPayrollEmployees');
    Route::get('/rata-payroll/{id}/employees/delete', 'RATAController@deleteRATAPayrollEmployees');
    Route::post('/rata-payroll/{id}/{type_id}/process', 'RATAController@processRATAPayroll');
    Route::post('/rata-payroll/{id}/update', 'RATAController@updateRATAPayroll');

    // Hazard Pay Setup
    Route::get('/hazard-pay', 'HazardPayController@index');
    Route::post('/hazard-pay', 'HazardPayController@store');
    Route::get('/hazard-pay/{id}/delete', 'HazardPayController@delete');
    Route::delete('/hazard-pay/{id}', 'HazardPayController@destroy');
    Route::get('/hazard-pay/list', 'HazardPayController@loadHazard');
    Route::get('/hazard-pay/{id}/add', 'HazardPayController@addHazard');
    Route::post('/hazard-pay/{id}', 'HazardPayController@storeHazard');
    Route::post('/hazard-pay/{id}/employees', 'HazardPayController@storeHazardEmployees');
    Route::get('/hazard-pay/{id}/employees/delete', 'HazardPayController@deleteHazardEmployees');
    Route::post('/hazard-pay/{id}/{type_id}/process', 'HazardPayController@processHazard');

    // OT Tax Table
    Route::get('/overtime-tax-table', 'OvertimeTaxController@index');
    Route::post('/overtime-tax-table', 'OvertimeTaxController@store');
    Route::get('/overtime-tax-table/{id}/delete', 'OvertimeTaxController@delete');
    Route::get('/overtime-tax-table/{year}/load', 'OvertimeTaxController@load');

    // Mid Year Table Setup
    Route::get('/midyear-tables', 'MidYearController@index');
    Route::get('/midyear-tables/{id}/delete', 'MidYearController@delete');
    Route::post('/midyear-tables', 'MidYearController@store');
    Route::delete('/midyear-tables/{id}', 'MidYearController@destroy');

    // Year End Table Setup
    Route::get('/yearend-tables', 'YearEndController@index');
    Route::get('/yearend-tables/{id}/delete', 'YearEndController@delete');
    Route::post('/yearend-tables', 'YearEndController@store');
    Route::delete('/yearend-tables/{id}', 'YearEndController@destroy');

    // Cash Gift Table Setup
    Route::get('/cashgift-tables', 'CashGiftController@index');
    Route::get('/cashgift-tables/{id}/delete', 'CashGiftController@delete');
    Route::post('/cashgift-tables', 'CashGiftController@store');
    Route::delete('/cashgift-tables/{id}', 'CashGiftController@destroy');

    // Document Type Setup
    Route::get('/document-types', 'DocumentTypeController@index');
    Route::post('/document-types', 'DocumentTypeController@store');
    Route::get('/document-types/{id}/delete', 'DocumentTypeController@delete');
    Route::delete('/document-types/{id}', 'DocumentTypeController@destroy');

    // Loyalty Award Report
    Route::get('/loyalty-award-report', 'LoyaltyAwardController@loyalty_award_report');
    Route::post('/loyalty-award-report/print', 'LoyaltyAwardController@print');

    // Payroll Communication Macco
    Route::get('/reimbursement-report', 'ReimbursementCommunicationExpensesController@index');
    Route::get('/reimbursement-set-report', 'ReimbursementCommunicationExpensesController@report');
    Route::post('/reimbursement-report/print', 'ReimbursementCommunicationExpensesController@print');
    Route::get('/reimbursement/{id}/add', 'ReimbursementCommunicationExpensesController@add');
    Route::post('/reimbursement/{id}', 'ReimbursementCommunicationExpensesController@store');
    Route::post('/reimbursement/{id}/employees', 'ReimbursementCommunicationExpensesController@addEmployee');
    Route::get('/reimbursement/{id}/employees/delete', 'ReimbursementCommunicationExpensesController@deleteEmployee');
    Route::post('/reimbursement/{id}/{type_id}/process', 'ReimbursementCommunicationExpensesController@process');

    // Uniform Clothing Allowance Report
    Route::get('/uniform-clothing-allowance-report', 'UniformClothingController@uniform_clothing_allowance_report');
    Route::post('/uniform-clothing-allowance-report/print', 'UniformClothingController@print');

    // Monetization Setup
    Route::get('/monetization-setups', 'MonetizationController@index');
    Route::post('/monetization-setups/{id}', 'MonetizationController@store');

    // EETE Rating Setup
    Route::get('/eete-rating-setup', 'EETEController@index');
    Route::post('/eete-rating/{id}', 'EETEController@store');

    // Exam Category Setup
    Route::get('/exam-category-setup', 'ExamCategoryController@index');
    Route::get('/exam-category-setup/{id}/add', 'ExamCategoryController@add');
    Route::post('/exam-category-setup/{id}', 'ExamCategoryController@store');
    Route::get('/exam-sub-category/{id}/delete', 'ExamCategoryController@deleteSubCategory');
    Route::get('/exam-sub-categories/{id}/positions', 'ExamCategoryController@subCategoryPositions');
    Route::post('/exam-sub-categories/{id}/positions', 'ExamCategoryController@addPosition');
    Route::get('/exam-sub-categories/{id}/questions', 'ExamCategoryController@subCategoryQuestions');
    Route::post('/exam-sub-categories/{id}/questions/{question_id}', 'ExamCategoryController@addQuestions');
    Route::get('/exam-sub-categories/{id}/questions/delete', 'ExamCategoryController@deleteQuestion');
    Route::get('/exam-sub-categories/{id}/choice/delete', 'ExamCategoryController@deleteChoice');
    Route::get('/exam-sub-categories/{id}/choice/get-delete', 'ExamCategoryController@getDeleteChoice');

    // Migrations
    Route::get('/migrate-branches', 'MigrateBranchController@index');
    Route::post('/import-branches', 'MigrateBranchController@import');

    Route::get('/migrate-offices', 'MigrateOfficeController@index');
    Route::post('/import-offices', 'MigrateOfficeController@import');

    Route::get('/migrate-divisions', 'MigrateDivisionController@index');
    Route::post('/import-divisions', 'MigrateDivisionController@import');

    Route::get('/migrate-sections', 'MigrateSectionController@index');
    Route::post('/import-sections', 'MigrateSectionController@import');

    Route::get('/migrate-employment-types', 'MigrateEmploymentTypeController@index');
    Route::post('/import-employment-types', 'MigrateEmploymentTypeController@import');
    Route::get('/migration-template/{template}', 'MigrationTemplateController@download');

    // Global Controllers
    Route::get('/global/plantilla/{id}', 'GlobalController@getPlantilla');
    Route::get('/global/salary/{amount}', 'GlobalController@getSalary');
    Route::get('/global/address/{id}', 'GlobalController@getAddress');
    Route::get('/global/address-temp/{request_id}', 'GlobalController@getAddress_temp');
    Route::get('/global/promotion/{id}', 'GlobalController@getEmployeePromotion');
    Route::get('/global/employee-plantilla/{id}', 'GlobalController@getEmployeePlantilla');
    Route::get('/global/assign-schedule/{id}', 'GlobalController@getAssignSchedule');
    Route::get('/global/holiday/{id}/{header_id}', 'GlobalController@getHolidays');
    Route::get('/global/holiday-details/{holiday_type_id}/{branch_id}/{year}', 'GlobalController@getHolidayTaggingDetails');
    Route::get('/global/employee-step/{id}', 'GlobalController@getEmployeeStep');
    Route::get('/global/plantilla-step/{id}/{step_id}', 'GlobalController@getPlantillaStep');
    Route::get('/global/plantilla-step-id/{id}/{step_id}', 'GlobalController@getPlantillaStepID');
    Route::get('/global/employees-leave-credits/{id}/{user_id}', 'GlobalController@getEmployeeForLeaveCredits');
    Route::get('/global/payroll-cutoff/{id}', 'GlobalController@getPayrollCutoff');
    Route::get('/global/payroll-period/{id}', 'GlobalController@getPayrollPeriod');
    Route::get('/global/payroll-period-posted/{id}', 'GlobalController@getPayrollPeriodPosted');
    Route::get('/global/overtime-payroll-period/{id}/{overtime_payroll_id}', 'GlobalController@getOvertimePayrollPeriod');
    Route::get('/global/overtime-payroll-period-print/{id}', 'GlobalController@getOvertimePayrollPeriodPrint');
    Route::get('/global/branches-payroll/{id}', 'GlobalController@getBranchesPayroll');
    Route::get('/global/departments/{id}/{payroll_period_id}', 'GlobalController@getDepartments');
    Route::get('/global/deductions', 'GlobalController@getDeductions');
    Route::get('/global/incomes', 'GlobalController@getIncomes');
    Route::get('/global/time-data/{id}', 'GlobalController@getTimeData');
    Route::get('/global/time-data-offset/{id}', 'GlobalController@getTimeDataOffset');
    Route::get('/global/time-data-offset-details/{id}/{employee_id}', 'GlobalController@getTimeDataOffset_details');
    Route::get('/global/employee-departments/{id}/{payroll_period_id}', 'GlobalController@getEmployeeDepartments');
    Route::get('/global/unavailable-dates/{id}', 'GlobalController@getUnavailableDates');
    Route::get('/global/document-number/{key}', 'GlobalController@getDocumentNumbers');
    Route::get('/global/unavailable-dates-ot/{id}', 'GlobalController@getUnavailableDates_OT');
    Route::get('/global/loan-filter', 'GlobalController@getLoanApplicationFilter');
    Route::get('/global/approvers/{id}/{type_id}/{approver_id}', 'GlobalController@getApprovers');
    Route::get('/global/approvers-data/{id}', 'GlobalController@getApproversData');
    Route::get('/global/coc-details', 'GlobalController@getCOCdetails');
    Route::get('/global/branch-employees/{branch_id}', 'GlobalController@getBranchEmployee');
    Route::get('/global/loyalty-award-signatory/{branch_id}', 'GlobalController@get_loyalty_award_signatory');
    Route::get('/global/uniform-clothing-allowance-signatory/{branch_id}', 'GlobalController@get_uniform_clothing_allowance_signatory');
    Route::get('/global/midyear-bonus/{years}/{branch_id}', 'GlobalController@getMidYearBonus');
    Route::get('/global/yearend-bonus/{years}/{branch_id}', 'GlobalController@getYearEndBonus');
    Route::get('/global/midyear-signatory/{branch_id}', 'GlobalController@getMidYearSignatory');
    Route::get('/global/yearend-signatory/{branch_id}', 'GlobalController@getYearEndSignatory');
    Route::get('/global/employees-without-schedule', 'GlobalController@getEmployeesWithoutSchedule');
    Route::get('/global/employees-without-payroll', 'GlobalController@getEmployeesWithoutPayrollID');
    Route::get('/global/leave-taken/{id}/{year}', 'GlobalController@getEmployeeLeave');
    Route::get('/global/employee-incomes/{id}', 'GlobalController@getEmployeeIncomes');
    Route::get('/global/employee-bonus/{id}', 'GlobalController@getEmployeeBonus');
    Route::get('/global/employee-leave-earned/{id}', 'GlobalController@getEmployeeLeaveEarned');
    Route::get('/global/loyalty-award-employee/{month_id}/{branch_id}/{year}', 'LoyaltyAwardController@loyalty_award_employee');
    Route::get('/global/subcompetencies/{id}', 'GlobalController@getSubCompetencies');
    Route::get('/global/training-employee-list/{id}/{position_id}', 'GlobalController@getTrainingEmployeeList');
    Route::get('/global/training-employee-list-all/{position_id}', 'GlobalController@getTrainingEmployeeListAll');
    Route::get('/global/departments-req/{id}', 'GlobalController@getDepartmentsReq');
    Route::get('/global/leave-maximum-availment/{leave_type_id}', 'GlobalController@getLeaveIsMaximumAvailment');
});
