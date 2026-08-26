<?php

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

// Public routes (no authentication required)
Route::get('/vacancies', 'VacanciesController@index');
Route::get('/position-info/{id}/{type_id}', 'VacanciesController@positions');
Route::get('/applicant-add/{id}/{plantilla_id}', 'ApplicantsController@add');
Route::post('/applicant-add/{id}/{plantilla_id}', 'ApplicantsController@store');
Route::get('/applicant-registration', 'ApplicantsController@register');
Route::post('/applicant-registration', 'ApplicantsController@register_store');

// Temporary: 201-file route for testing (remove authentication)
Route::get('/201-files', 'EmployeeFileController@index');
Route::get('/hr-overview', 'HROverviewController@index');

// Authentication routes
Route::post('/login', 'Api\AuthController@login');
Route::post('/register', 'Api\AuthController@register');
// Authentication routes
// User authentication check route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json([
        'success' => true,
        'data' => [
            'user' => $request->user()
        ],
        'message' => 'User retrieved successfully'
    ]);
});


// Protected routes (authentication required)
Route::middleware(['auth:sanctum'])->group(function () {

    // Authentication (protected routes)
    Route::post('/logout', 'Api\AuthController@logout');
    Route::get('/profile', 'Api\AuthController@profile');
    Route::post('/refresh', 'Api\AuthController@refresh');

    // Dashboard
    Route::get('/home', 'HomeController@index');
    Route::get('/dashboard/{id}', 'DashboardController@index');

    // Announcements
    Route::get('/announcements', 'AnnouncementController@index');
    Route::post('/announcements', 'AnnouncementController@store');
    Route::get('/announcements/employees', 'AnnouncementController@getEmployees');

    // Applicant Examination Routes
    Route::get('/applicant-examination-intro/{id}', 'ApplicantsController@examIntro');
    Route::get('/applicant-examination-page/{id}', 'ApplicantsController@examPage');
    // Backward-compatible alias for legacy frontend route.
    Route::get('/exam-intro/{id}', 'ApplicantsController@examIntro');
    Route::get('/exam-page/{id}', 'ApplicantsController@examPage');
    Route::post('/applicant-examination-submit/{applicant_examination_id}', 'ApplicantsController@examSubmit');
    Route::post('/applicant-examination-auto-save/{applicant_examination_id}', 'ApplicantsController@examAutoSave');
    Route::get('/applicant-examination-result/{applicant_examination_id}', 'ApplicantsController@examResult');
    Route::post('/exam-submit/{applicant_examination_id}', 'ApplicantsController@examSubmit');
    Route::post('/exam-auto-save/{applicant_examination_id}', 'ApplicantsController@examAutoSave');
    Route::get('/exam-result/{applicant_examination_id}', 'ApplicantsController@examResult');
    Route::get('/applicant-examination-answers/{applicant_examination_id}', 'ApplicantsController@examAnswers');
    Route::post('/applicant-examination-answers/{applicant_examination_id}/review', 'ApplicantsController@examReviewAnswers');
    Route::post('/applicant-examination-answers/{applicant_examination_id}/tag-result', 'ApplicantsController@examTagResult');

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

    // Daily Time Record Routes
    Route::get('/daily-time-records/{id}', 'DailyTimeRecordController@index');
    Route::get('/daily-time-records/employee/{employee_no}', 'DailyTimeRecordController@getByEmployeeNo');
    Route::get('/daily-time-records/{id}/employee/{payroll_period_id}', 'DailyTimeRecordController@view');
    Route::get('/daily-time-records/{id}/logs', 'DailyTimeRecordController@logs');
    Route::get('/daily-time-records/{id}/logs/{from}/{to}', 'DailyTimeRecordController@getLogs');
    Route::post('/daily-time-records/{id}', 'DailyTimeRecordController@store');
    Route::get('/review-daily-time-records/{id}', 'DailyTimeRecordController@loadDTRRequest');
    Route::get('/review-daily-time-records/{id}/add', 'DailyTimeRecordController@reviewDTRRequest');
    Route::get('/review-daily-time-records/{id}/approve/{type_id}', 'DailyTimeRecordController@approve');
    Route::get('/review-daily-time-records/{id}/download', 'DailyTimeRecordController@download');

    // Payslip Routes
    Route::get('/payslips/{id}', 'PayslipController@index');
    Route::get('/payslips/{id}/view/{payroll_id}', 'PayslipController@view');
    Route::get('/payslips/{id}/print/{payroll_id}', 'PayslipController@print');

    // Work Experience Routes
    Route::get('/work-experiences', 'WorkExperienceController@index');
    Route::get('/work-experience/{employeeId}', 'WorkExperienceController@getData');
    Route::post('/work-experience/preview', 'WorkExperienceController@generatePreview');
    Route::post('/work-experience/download', 'WorkExperienceController@downloadPDF');
    Route::post('/work-experience/download-word', 'WorkExperienceController@downloadWord');
    Route::post('/work-experience/download-docx', 'WorkExperienceController@downloadDocx');
    Route::get('/work-experience/print/{id}', 'WorkExperienceController@print');

    // Panel Interview Routes
    Route::get('/panel-interviews/{id}', 'InterviewController@panel_interview');
    Route::get('/panel-interviews/{id}/applicant-pds/{applicant_id}', 'InterviewController@panel_interview_pds');
    Route::get('/panel-interviews/{id}/applicant-exam/{applicant_id}', 'InterviewController@panel_interview_exam');
    Route::get('/panel-interviews/{id}/applicant-rating/{applicant_id}/{employee_id}/{interview_id}', 'InterviewController@panel_interview_rating');
    Route::post('/panel-interviews/{id}/applicant-rating/{rating_id}', 'InterviewController@interview_rating');

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
    Route::get('/applicants', 'EmployeesController@indexApplicants');
    Route::get('/employees/search', 'EmployeesController@search');
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

    // COS Contract file attachments (attachments DB)
    Route::get('/cos-contract-files/{id}/download', 'COSContractFileController@download');

    // Employee Promotion Routes
    Route::get('/promotions', 'EmployeePromotionController@index');
    Route::get('/promotions/add/{id}', 'EmployeePromotionController@add');
    Route::get('/promotions/show/{id}', 'EmployeePromotionController@show');
    Route::post('/promotions/{id}', 'EmployeePromotionController@store');

    // Employee Off-Boarding Routes
    Route::get('/off-boardings', 'EmployeeOffBoardingController@index');
    Route::get('/off-boardings/add/{id}', 'EmployeeOffBoardingController@add');
    Route::get('/off-boardings/{id}/info/{employee_id}', 'EmployeeOffBoardingController@info');
    Route::get('/off-boardings/{id}/reactivate/{employee_id}', 'EmployeeOffBoardingController@activate');
    Route::post('/off-boardings/{id}', 'EmployeeOffBoardingController@store');
    Route::post('/off-boardings/{id}/reactivate/{employee_id}', 'EmployeeOffBoardingController@reactivate');


    //Employee Rehire

    Route::get('/off-boardings/rehire/{id}', 'OffBoardingTypeController@rehire');


    // Step Increment Routes
    Route::get('/step-increments', 'EmployeeStepIncrementController@index');
    Route::get('/step-increments/add', 'EmployeeStepIncrementController@add');
    Route::get('/step-increments/edit/{month_id}/{year_id}', 'EmployeeStepIncrementController@edit');
    Route::get('/step-increments/{month_id}/{year_id}/employees', 'EmployeeStepIncrementController@loadEmployees');
    Route::get('/step-increments/{step_increment_id}/employee/{employee_id}/salary', 'EmployeeStepIncrementController@changeNewSalary');
    Route::get('/step-increments/{month_id}/{year_id}/forward', 'EmployeeStepIncrementController@forwarded');
    Route::post('/step-increments', 'EmployeeStepIncrementController@store');
    Route::get('/step-increments/{month_id}/{year_id}/forward-approver', 'EmployeeStepIncrementController@forwardApprover');
    // Step Increment Approval (dedicated controller)
    Route::get('/step-increment-approval', 'StepIncrementApprovalController@index');
    Route::post('/step-increment-approval/{id}/approve', 'StepIncrementApprovalController@approve');
    Route::post('/step-increment-approval/{id}/reject', 'StepIncrementApprovalController@reject');
    Route::get('/step-increments/{month_id}/{year_id}/for-process', 'EmployeeStepIncrementController@forProcess');
    Route::get('/step-increments/{month_id}/{year_id}/approved', 'EmployeeStepIncrementController@approved');
    Route::get('/step-increments/{month_id}/{year_id}/disapproved/{remarks}', 'EmployeeStepIncrementController@disapproved');
    Route::get('/step-increments/{month_id}/{year_id}/print', 'EmployeeStepIncrementController@print');
    Route::delete('/step-increments/{step_increment_id}/employee', 'EmployeeStepIncrementController@delete');

    // Step Increment Management Routes (StepIncrementController)
    Route::get('/step-increment-management', 'StepIncrementController@index');
    Route::get('/step-increment-management/add/{id}', 'StepIncrementController@add');
    Route::post('/step-increment-management/{id}', 'StepIncrementController@store');
    Route::get('/step-increment-management/{id}', 'StepIncrementController@show');
    Route::post('/step-increment-management/add-step', 'StepIncrementController@addStep');
    Route::post('/step-increment-management/{id}/forward', 'StepIncrementController@forward');
    Route::post('/step-increment-management/{id}/approve', 'StepIncrementController@approve');
    Route::post('/step-increment-management/{id}/reject', 'StepIncrementController@reject');
    Route::delete('/step-increment-management/{id}', 'StepIncrementController@destroy');

    // Salary Adjustment Routes
    Route::get('/salary-adjustments', 'SalaryAdjustmentController@index');
    Route::post('/salary-adjustments', 'SalaryAdjustmentController@process');
    Route::get('/salary-adjustments/{salary_schedule_id}/affected-employees', 'SalaryAdjustmentController@getAffectedEmployees');
    Route::get('/salary-adjustments/{salary_schedule_id}/processed-employees', 'SalaryAdjustmentController@getProcessedEmployees');
    Route::get('/salary-adjustments/{salary_schedule_id}/unprocessed-employees', 'SalaryAdjustmentController@getUnprocessedEmployees');

    // IPCR Routes
    Route::get('/ipcr', 'IPCRController@index');
    Route::get('/ipcr/add/{id}', 'IPCRController@add');
    Route::post('/ipcr/{id}', 'IPCRController@store');
    Route::get('/ipcr/{id}/review', 'IPCRController@review');
    Route::get('/ipcr/{id}/summary-ratings', 'IPCRController@summaryRatings');
    Route::get('/ipcr/{id}/pmt-calibration', 'IPCRController@pmtCalibration');
    Route::get('/ipcr-adjective/{rating}', 'IPCRController@ipcr_adjective');
    Route::post('/ipcr/{id}/rating', 'IPCRController@rating');
    Route::delete('/ipcr/{id}', 'IPCRController@destroy');

    // OPCR Routes
    Route::get('/opcr', 'OPCRController@index');
    Route::get('/opcr/add/{id}', 'OPCRController@add');
    Route::post('/opcr/{id}', 'OPCRController@store');
    Route::get('/opcr/{id}/review', 'OPCRController@review');
    Route::get('/opcr-adjective/{rating}', 'OPCRController@opcr_adjective');
    Route::post('/opcr/{id}/rating', 'OPCRController@rating');
    Route::delete('/opcr/{id}', 'OPCRController@destroy');

    // DPCR Routes
    Route::get('/dpcr', 'DPCRController@index');
    Route::get('/dpcr/add/{id}', 'DPCRController@add');
    Route::post('/dpcr/{id}', 'DPCRController@store');
    Route::get('/dpcr/{id}/review', 'DPCRController@review');
    Route::post('/dpcr/{id}/rating', 'DPCRController@rating');
    Route::delete('/dpcr/{id}', 'DPCRController@destroy');

    // Review 201 Updates Routes
    Route::get('/review-201-updates', 'EmployeeRequestController@list');
    Route::get('/review-201-updates/{id}/review', 'EmployeeRequestController@review');
    Route::get('/review-201-updates/{id}/approval/{type_id}', 'EmployeeRequestController@approval');
    Route::post('/review-201-updates/{id}/approve/{type_id}', 'EmployeeRequestController@approve');

    // Update 201 Schedule Routes
    Route::get('/update-201-schedule', 'Update201ScheduleController@index');
    Route::post('/update-201-schedule/{id}', 'Update201ScheduleController@store');
    Route::put('/update-201-schedule/{id}', 'Update201ScheduleController@update');
    Route::delete('/update-201-schedule/{id}', 'Update201ScheduleController@destroy');
    Route::get('/update-201-schedule/{id}/employees', 'Update201ScheduleController@getEmployees');

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
    Route::delete('/examination-setup/{id}', 'ExaminationController@destroy');
    Route::post('/examination-setup/{id}/position', 'ExaminationController@add_position');
    Route::delete('/examination-setup/{id}/position', 'ExaminationController@delete_position');
    Route::get('/exam-types', 'ExaminationController@examTypes');
    Route::get('/examination-categories/{categoryId}/items-count', 'ExaminationController@categoryItemsCount');

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
    Route::delete('/examination-schedules/{id}', 'ExaminationController@schedules_destroy');

    // Applicant Ranking and Shortlisting Routes
    Route::get('/applicant-shortlisting', 'ApplicantShortlistingController@index');
    Route::get('/applicant-shortlisting/add/{id}/{rating}/{status_id}/{position_applied_id?}', 'ApplicantShortlistingController@add');
    Route::post('/applicant-shortlisting/proceed/{applicant_id}/{position_applied_id?}', 'ApplicantShortlistingController@proceedNextStep');
    Route::delete('/applicant-shortlisting/{id}', 'ApplicantShortlistingController@delete');

    // Recruitment: Applicants Records (replaces Applicants Monitoring)
    Route::get('/applicants-records', 'ApplicantsMonitoringController@index');
    Route::get('/applicants-records/application-statuses', 'ApplicantsMonitoringController@applicationStatuses');
    Route::post('/applicants-records/update-status', 'ApplicantsMonitoringController@updateStatus');
    Route::get('/applicants-records/progress/{applicant_id}/{position_applied_id?}', 'ApplicantsMonitoringController@progress');
    // Backwards-compatible route (optional)
    Route::get('/applicants-monitoring', 'ApplicantsMonitoringController@index');

    // Interview Schedule Routes
    Route::get('/interview-schedules', 'InterviewController@index');
    Route::get('/interview-schedules/add/{id}', 'InterviewController@add');
    Route::post('/interview-schedules/{id}', 'InterviewController@store');
    Route::post('/interview-schedules/{id}/panel', 'InterviewController@addPanel');
    Route::post('/interview-schedules/{id}/applicant', 'InterviewController@addApplicant');
    Route::delete('/interview-schedules/{id}/panel', 'InterviewController@deletePanel');
    Route::delete('/interview-schedules/{id}/applicant', 'InterviewController@deleteApplicant');
    Route::get('/interview-schedules/{id}/process/{type_id}', 'InterviewController@process');
    Route::delete('/interview-schedules/{id}', 'InterviewController@destroy');
    Route::get('/interview-schedules/panel-rating-document/{rating_id}', 'InterviewController@downloadPanelRatingDocument');
    Route::get('/interview-cancel/{id}/{applicant_id}', 'InterviewController@cancelInterview');

    // Newly hired and promotions
    Route::get('/newly-hired-and-promotions', 'NewhiredandpromotedController@index');
    Route::post('/newly-hired-and-promotions/print', 'NewhiredandpromotedController@print');
    Route::post('/newly-hired-and-promotions/word', 'NewhiredandpromotedController@word');
    Route::post('/newly-hired-and-promotions/excel', 'NewhiredandpromotedController@excel');

    // Regret Letter Routes
    Route::get('/regret-letter', 'RegretLetterController@index');
    Route::get('/regret-letter/{id}/generate', 'RegretLetterController@generateLetter');
    Route::get('/regret-letter/{id}/word', 'RegretLetterController@word');

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
    Route::get('/employee-certificates/create', 'EmployeeCertificateReportController@create');
    Route::get('/employee-certificates/{id}', 'EmployeeCertificateReportController@show');
    Route::post('/employee-certificates/print', 'EmployeeCertificateReportController@print');
    Route::post('/employee-certificates/word', 'EmployeeCertificateReportController@downloadDocx');

    // Salary Deduction Certificate
    Route::get('/salary-deduction/employees', 'SalaryDeductionCertificateController@employees');
    Route::post('/salary-deduction/print', 'SalaryDeductionCertificateController@print');
    Route::post('/salary-deduction/word', 'SalaryDeductionCertificateController@downloadDocx');

    // Rendered Service Certificate (COS employees)
    Route::get('/rendered-service/employees', 'RenderedServiceCertificateController@employees');
    Route::post('/rendered-service/print', 'RenderedServiceCertificateController@print');

    Route::get('/cos-cert/employees', 'COSCertController@employees');
    Route::post('/cos-cert/print', 'COSCertController@print');
    Route::post('/cos-cert/word', 'COSCertController@downloadDocx');

    // COS Contract Certificate
    Route::get('/cos-contract-cert/employees', 'COSContractCertificateController@employees');
    Route::get('/cos-contract-cert/signatories', 'COSContractCertificateController@signatories');
    Route::post('/cos-contract-cert/print', 'COSContractCertificateController@print');
    Route::post('/cos-contract-cert/word', 'COSContractCertificateController@downloadDocx');

    // COS Non-Disclosure Agreement
    Route::get('/cos-nda/employees', 'COSNonDisclosureAgreementController@employees');
    Route::get('/cos-nda/signatories', 'COSNonDisclosureAgreementController@signatories');
    Route::post('/cos-nda/print', 'COSNonDisclosureAgreementController@print');
    Route::post('/cos-nda/word', 'COSNonDisclosureAgreementController@downloadDocx');

    // Employee Compensation Certificates
    Route::get('/employee-compensation-certificates', 'EmployeeCertificateCompensationReportController@index');
    Route::get('/employee-compensation-certificates/create', 'EmployeeCertificateCompensationReportController@create');
    Route::get('/employee-compensation-certificates/{id}', 'EmployeeCertificateCompensationReportController@show');
    Route::post('/employee-compensation-certificates/print', 'EmployeeCertificateCompensationReportController@print');
    Route::post('/employee-compensation-certificates/word', 'EmployeeCertificateCompensationReportController@downloadDocx');

    // Employee Dependent Certificates
    Route::get('/employee-dependent-certificates', 'EmployeeDependentCertificateController@index');
    Route::post('/employee-dependent-certificates/print', 'EmployeeDependentCertificateController@print');

    // Employee Medical Certificates
    Route::get('/employee-medical-certificates', 'EmployeeMedicalCertificateController@index');
    Route::get('/employee-medical-certificates/create', 'EmployeeMedicalCertificateController@create');
    Route::get('/employee-medical-certificates/{id}', 'EmployeeMedicalCertificateController@show');
    Route::post('/employee-medical-certificates/print', 'EmployeeMedicalCertificateController@print');

    // Appointment Certificate
    Route::get('/appointment-certificate', 'AppointmentCertificateController@index');
    Route::post('/appointment-certificate/print', 'AppointmentCertificateController@print');
    Route::post('/appointment-certificate/word', 'AppointmentCertificateWordController@word');
    Route::post('/appointment-certificate/send-email', 'AppointmentCertificateController@sendEmail');

    // Assumption of Duty
    Route::get('/assumption-of-duty', 'AssumptionOfDutyController@index');
    Route::post('/assumption-of-duty/print', 'AssumptionOfDutyController@print');
    Route::post('/assumption-of-duty/download-docx', 'AssumptionOfDutyController@downloadDocx');
    Route::post('/assumption-of-duty/download-docx-v2', 'AssumptionOfDutyWordController@downloadDocx');
    Route::post('/assumption-of-duty/send-email', 'AssumptionOfDutyController@sendEmail');

    // Plantilla of Casual Appointment
    Route::get('/casual-appointment', 'PlantillaOfCasualAppointmentController@index');
    Route::post('/casual-appointment/print', 'PlantillaOfCasualAppointmentController@print');

    // Acceptance of Resignation
    Route::get('/acceptance-of-resignation', 'AcceptanceofResignationController@index');
    Route::post('/acceptance-of-resignation/print', 'AcceptanceofResignationController@print');
    Route::post('/acceptance-of-resignation/word', 'AcceptanceofResignationController@generateWord');
    Route::post('/acceptance-of-resignation/word-preview', 'AcceptanceofResignationController@previewWord');

    // Acceptance of Retirement
    Route::get('/acceptance-of-retirement', 'AcceptanceOfRetirementController@index');
    Route::post('/acceptance-of-retirement/print', 'AcceptanceOfRetirementController@print');
    Route::post('/acceptance-of-retirement/word', 'AcceptanceOfRetirementController@generateWord');
    Route::post('/acceptance-of-retirement/word-preview', 'AcceptanceOfRetirementController@previewWord');

    // Acceptance Letter Intern
    Route::get('/acceptance-letter-intern', 'AcceptanceLetterInternController@index');
    Route::get('/acceptance-letter-intern/{id}/students', 'AcceptanceLetterInternController@getStudents');
    Route::post('/acceptance-letter-intern/store', 'AcceptanceLetterInternController@store');
    Route::put('/acceptance-letter-intern/students/{studentId}/status', 'AcceptanceLetterInternController@updateStudentStatus');
    Route::post('/acceptance-letter-intern/print', 'AcceptanceLetterInternController@print');
    Route::post('/acceptance-letter-intern/word', 'AcceptanceLetterInternController@word');
    Route::post('/acceptance-letter-intern/word-preview', 'AcceptanceLetterInternController@wordPreview');

    // Acceptance Letter
    Route::get('/acceptance-letter/applicants', 'AcceptanceLetterController@applicants');
    Route::get('/acceptance-letter/signatories', 'AcceptanceLetterController@signatories');
    Route::get('/acceptance-letter/departments', 'AcceptanceLetterController@departments');
    Route::post('/acceptance-letter/print', 'AcceptanceLetterController@print');
    Route::post('/acceptance-letter/word', 'AcceptanceLetterController@word');
    Route::post('/acceptance-letter/send-email', 'AcceptanceLetterController@sendEmail');

    // Certificate of Completion
    Route::get('/cert-of-completion/students', 'CertOfCompletionController@getActiveStudents');
    Route::post('/cert-of-completion/print', 'CertOfCompletionController@print');
    Route::post('/cert-of-completion/word', 'CertOfCompletionController@downloadDocx');

    // Oath of Office
    Route::get('/oath-of-office', 'OathOfOfficeController@index');
    Route::post('/oath-of-office/print', 'OathOfOfficeController@print');
    Route::post('/oath-of-office/word', 'OathOfOfficeWordController@word');

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
    // Use generatePdf to return an actual PDF instead of JSON
    Route::post('/service-record/print', 'ServiceRecordController@generatePdf');
    Route::post('/service-record/excel', 'ServiceRecordController@generateExcel');

    // No Pending Certificate
    Route::get('/no-pending-certificates', 'NoPendingCertificateReportController@index');
    Route::post('/no-pending-certificates/print', 'NoPendingCertificateReportController@print');
    Route::post('/no-pending-certificates/word', 'NoPendingCertificateReportController@downloadDocx');
    Route::post('/no-pending-certificates/excel', 'NoPendingCertificateReportController@downloadExcel');

    // ATM Request Certificate
    Route::get('/atm-request-certificates', 'ATMRequestCertificateReportController@index');
    Route::post('/atm-request-certificates/print', 'ATMRequestCertificateReportController@print');
    Route::post('/atm-request-certificates/word', 'ATMRequestCertificateReportController@downloadDocx');

    // Offboarding Certificate
    Route::get('/offboarding-certificates', 'OffboardingCertificateReportController@index');
    Route::post('/offboarding-certificates/print', 'OffboardingCertificateReportController@print');
    Route::post('/offboarding-certificates/word', 'OffboardingCertificateReportController@word');

    // Certificate of Last Salary
    Route::get('/cert-of-last-salary/employees', 'CertOfLastSalaryController@getEmployees');
    Route::get('/cert-of-last-salary/employees/{id}', 'CertOfLastSalaryController@getEmployeeDetails');
    Route::post('/cert-of-last-salary/print', 'CertOfLastSalaryController@print');

    // Appearance Certificate
    Route::get('/appearance-certificates', 'AppearanceCertificateReportController@index');
    Route::post('/appearance-certificates/print', 'AppearanceCertificateReportController@print');
    Route::post('/appearance-certificates/word', 'AppearanceCertificateReportController@word');

    // OJT Certificate
    Route::get('/ojt-certificates', 'OJTCertificateReportController@index');
    Route::get('/ojt-certificates/create', 'OJTCertificateReportController@add');
    Route::post('/ojt-certificates', 'OJTCertificateReportController@store');
    Route::get('/ojt-certificates/{id}/edit', 'OJTCertificateReportController@edit');
    Route::patch('/ojt-certificates/{id}', 'OJTCertificateReportController@update');
    Route::get('/ojt-certificates/{id}/print', 'OJTCertificateReportController@print');

    // Terminal Leave Endorsement
    Route::get('/terminal-leave-endorsements', 'TerminalLeaveEndorsementReportController@index');
    Route::post('/terminal-leave-endorsements/data', 'TerminalLeaveEndorsementReportController@getEndorsementData');
    Route::post('/terminal-leave-endorsements/print', 'TerminalLeaveEndorsementReportController@generatePdf');

    // Birthday Summary Report
    Route::post('/birthday-summary/print', 'BirthdayReportController@print');

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


    //  Clearance Cert
    Route::get('/clearance-cert', 'ClearanceCertController@index');
    Route::get('/clearance-cert/employees', 'ClearanceCertController@employees');
    Route::get('/clearance-cert/clearing-officers', 'ClearanceCertController@clearingOfficers');
    Route::get('/clearance-cert/purposes', 'ClearanceCertController@purposes');
    Route::post('/clearance-cert', 'ClearanceCertController@store');
    Route::get('/clearance-cert/print', 'ClearanceCertController@print');
    Route::get('/clearance-cert/word', 'ClearanceCertController@word');
    Route::delete('/clearance-cert/{id}', 'ClearanceCertController@destroy');

    // Transfer Leave Credits
    Route::get('/transfer-leave-credits/employees', 'TransferLeaveCreditsController@employees');
    Route::get('/transfer-leave-credits/employee/{employeeId}/credits', 'TransferLeaveCreditsController@getLeaveCredits');
    Route::post('/transfer-leave-credits', 'TransferLeaveCreditsController@store');
    Route::get('/transfer-leave-credits', 'TransferLeaveCreditsController@index');
    Route::get('/transfer-leave-credits/{id}/print', 'TransferLeaveCreditsController@print');
    Route::get('/transfer-leave-credits/{id}/word', 'TransferLeaveCreditsController@word');

    // Position Description Routes
    Route::get('/position-description/positions', 'PositionDescriptionController@index');
    Route::get('/position-description/applicants', 'EmployeesController@indexApplicants');
    Route::post('/position-description/print', 'PositionDescriptionController@print');
    Route::post('/position-description/download-docx', 'PositionDescriptionController@downloadDocx');
    Route::post('/position-description/download-docx-v2', 'PositionDescriptionWordController@downloadDocx');

    // PDF Records Management
    Route::get('/position-description/pdf-records', 'PositionDescriptionController@getPdfRecords');
    Route::get('/position-description/pdf-records/{id}', 'PositionDescriptionController@getPdfRecord');
    Route::post('/position-description/pdf-records', 'PositionDescriptionController@createPdfRecord');
    Route::put('/position-description/pdf-records/{id}', 'PositionDescriptionController@updatePdfRecord');
    Route::delete('/position-description/pdf-records/{id}', 'PositionDescriptionController@deletePdfRecord');

    // SODAR Records Management
    Route::post('/position-description/sodar', 'PositionDescriptionController@addSodarRecord');
    Route::delete('/position-description/sodar/{id}', 'PositionDescriptionController@deleteSodarRecord');

    // Competencies Management
    Route::post('/position-description/core-competencies', 'PositionDescriptionController@addCoreCompetency');
    Route::delete('/position-description/core-competencies/{id}', 'PositionDescriptionController@deleteCoreCompetency');
    Route::post('/position-description/leadership-competencies', 'PositionDescriptionController@addLeadershipCompetency');
    Route::delete('/position-description/leadership-competencies/{id}', 'PositionDescriptionController@deleteLeadershipCompetency');
    Route::get('/position-description/competency-levels', 'PositionDescriptionController@getCompetencyLevels');
    Route::get('/position-description/plantilla-item-number/{positionId}', 'PositionDescriptionController@getPlantillaItemNumber');
    // Supervised Positions Management (Section 15)
    Route::post('/position-description/supervised-positions', 'PositionDescriptionController@addSupervisedPosition');
    Route::delete('/position-description/supervised-positions/{id}', 'PositionDescriptionController@deleteSupervisedPosition');

    // Work Experience Management
    Route::get('/position-description/work-experience', 'PositionDescriptionController@getWorkExperience');
    Route::get('/position-description/work-experience/{referenceId}', 'PositionDescriptionController@getWorkExperience');
    Route::post('/position-description/work-experience', 'PositionDescriptionController@addWorkExperience');

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

    // Request for Publication (Plantilla)
    Route::get('/request-for-publication/plantillas', 'RequestForPublicationController@plantillas');
    Route::get('/request-for-publication/form-data', 'RequestForPublicationController@getFormData');
    Route::get('/request-for-publication', 'RequestForPublicationController@index');
    Route::post('/request-for-publication', 'RequestForPublicationController@store');
    Route::post('/request-for-publication/print', 'RequestForPublicationController@print');
    Route::put('/request-for-publication/hrmo-email', 'RequestForPublicationController@saveHrmoEmail');

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

    // Assumption of Duty
    Route::get('/assumption-of-duty', 'AssumptionOfDutyController@index');
    Route::post('/assumption-of-duty/print', 'AssumptionOfDutyController@print');
    Route::post('/assumption-of-duty/download-docx', 'AssumptionOfDutyController@downloadDocx');
    Route::post('/assumption-of-duty/send-email', 'AssumptionOfDutyController@sendEmail');
});
