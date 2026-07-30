<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes(['verify' => true]);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/applicant_examination_intro/{id}', 'ApplicantsController@examIntro')->name('applicant_examination_intro');
    Route::get('/applicant_examination_page/{id}', 'ApplicantsController@examPage')->name('applicant_examination_page');
    Route::post('/applicant_examination_submit/{applicant_examination_id}', 'ApplicantsController@examSubmit')->name('applicant_examination_submit');
    Route::post('/applicant_examination_auto_save/{applicant_examination_id}', 'ApplicantsController@examAutoSave')->name('applicant_examination_auto_save');
    Route::get('/applicant_examination_result/{applicant_examination_id}', 'ApplicantsController@examResult')->name('applicant_examination_result');
    Route::get('/home', 'HomeController@index')->name('home');

    // Applicant Page
    Route::get('/applicant_page', 'ApplicantsController@applicant_page')->name('applicant_page');
    Route::get('/applicant_apply/{applicant_id}/{position_id}/{is_plantilla}', 'ApplicantsController@apply')->name('applicant_apply');
    Route::post('/applicant_pds_store/{id}', 'ApplicantsController@pds_store')->name('applicant_pds_store');
    Route::get('/applicant_delete/{type_id}/{id}', 'ApplicantsController@pds_delete')->name('applicant_delete');
    Route::delete('/applicant_destroy/{type_id}/{id}', 'ApplicantsController@pds_destroy')->name('applicant_destroy');
    Route::get('/applicant_download/{id}', 'ApplicantsController@download')->name('applicant_download');

    // OTP Authentication
    Route::get('/user_verification', 'UsersController@userVerification')->name('user_verification');
    Route::get('/user_verification_check', 'UsersController@userVerificationCheck')->name('user_verification_check');

    // Employee Portal Routes

    // 201 File
    Route::get('/201_files/{id}', 'EmployeeFileController@index')->name('201_files');
    Route::get('/201_ipcr_view/{id}', 'EmployeeFileController@ipcr_view')->name('201_ipcr_view');
    Route::get('/201_file_updates/{id}', 'EmployeeRequestController@index')->name('201_file_updates');
    Route::get('/201_file_updates_add/{id}/{employee_id}', 'EmployeeRequestController@add')->name('201_file_updates_add');
    Route::post('/201_file_updates_add/{id}/{request_id}', 'EmployeeRequestController@store')->name('201_file_updates_add');
    Route::get('/201_file_add/{id}', 'EmployeeFileController@update')->name('201_file_add');

    // SALN
    Route::get('/SALN/{id}', 'SALNController@index')->name('SALN');
    Route::post('/real-properties/store', 'SALNController@store')->name('real-properties.store');
    Route::post('/personal-properties/store', 'SALNController@storepersonal')->name('personal-properties.store');
    Route::post('/liabilities/store', 'SALNController@storeliabilities')->name('liabilities.store');
    Route::post('/business-interests/store', 'SALNController@storebusiness')->name('business-interests.store');
    Route::post('/relatives/store', 'SALNController@storerelatives')->name('relatives.store');
    Route::get('/saln_download/{id}', 'SALNController@download')->name('saln_download');
    Route::delete('/real-properties/{id}', 'SALNController@destroy')->name('real-properties.destroy');
    Route::delete('/personal-properties/{id}', 'SALNController@destroypersonal')->name('personal-properties.destroy');
    Route::delete('/liabilities/{id}', 'SALNController@destroyliabilities')->name('liabilities.destroy');
    Route::delete('/business-interests/{id}', 'SALNController@destroybusiness')->name('business-interests.destroy');
    Route::delete('/relatives/{id}', 'SALNController@destroyrelatives')->name('relatives.destroy');


    // Overtime Application
    Route::get('/overtime_application/{id}', 'OvertimeApplicationController@index')->name('overtime_applications');
    Route::post('/overtime_application_add', 'OvertimeApplicationController@store')->name('overtime_application_store');
    Route::post('/overtime_application_approve', 'OvertimeApplicationController@approve')->name('overtime_application_approve');
    Route::get('/overtime_application_disapprove/{id}/{remarks}', 'OvertimeApplicationController@disapprove')->name('overtime_application_disapprove');
    Route::get('/overtime_application_cancel/{id}/{emp_id}/{remarks}', 'OvertimeApplicationController@cancel')->name('overtime_application_cancel');
    Route::get('/overtime_application_delete/{id}', 'OvertimeApplicationController@destroy')->name('overtime_application_delete');
    Route::get('/overtime_attachments/{id}', 'OvertimeApplicationController@attachments')->name('overtime_attachments');
    Route::get('/overtime_remove_attachments/{id}', 'OvertimeApplicationController@remove_attachments')->name('overtime_remove_attachments');
    Route::post('/overtime_cancel_attachment', 'OvertimeApplicationController@cancel_attachment')->name('overtime_cancel_attachment');
    Route::get('/overtime_cancel_attachment_download/{id}', 'OvertimeApplicationController@download')->name('overtime_cancel_attachment_download');
    Route::get('/overtime_attachment_download/{id}', 'OvertimeApplicationController@downloadapproval')->name('overtime_attachment_download');
    Route::get('/overtime_download/{id}', 'OvertimeApplicationController@downloadAttachment')->name('overtime_download');
    // Official Business Application
    Route::get('/official_business_application/{id}', 'OfficialBusinessApplicationController@index')->name('official_business_applications');
    Route::post('/official_business_application_add', 'OfficialBusinessApplicationController@store')->name('official_business_application_store');
    Route::post('/official_business_application_unofficial', 'OfficialBusinessApplicationController@storeunofficial')->name('official_business_application_store_unofficial');
    Route::get('/official_business_application_approve/{id}/{remarks}', 'OfficialBusinessApplicationController@approve')->name('official_business_application_approve');
    Route::get('/official_business_application_disapprove/{id}/{remarks}', 'OfficialBusinessApplicationController@disapprove')->name('official_business_application_disapprove');
    Route::get('/official_business_application_cancel/{id}/{remarks}', 'OfficialBusinessApplicationController@cancel')->name('official_business_application_cancel');
    Route::get('/official_business_application_delete/{id}', 'OfficialBusinessApplicationController@destroy')->name('official_business_application_delete');
    Route::get('/official_business_attachments/{id}', 'OfficialBusinessApplicationController@attachments')->name('overtime_attachments');
    Route::get('/official_business_remove_attachments/{id}', 'OfficialBusinessApplicationController@remove_attachments')->name('official_business_remove_attachments');
    Route::post('/official_business_cancel_attachment', 'OfficialBusinessApplicationController@cancel_attachment')->name('official_business_cancel_attachment');
    Route::get('/official_business_cancel_attachment_download/{id}', 'OfficialBusinessApplicationController@download')->name('official_business_cancel_attachment_download');
    Route::get('/official_business_attachment_download/{id}', 'OfficialBusinessApplicationController@downloadapproval')->name('official_business_attachment_download');
    Route::get('/official_business_download/{id}', 'OfficialBusinessApplicationController@downloadAttachment')->name('official_business_download');
    Route::get('/official_business_print/{id}', 'OfficialBusinessApplicationController@print')->name('official_business_print');
    Route::get('/unofficial_business_print/{id}', 'OfficialBusinessApplicationController@printunofficial')->name('unofficial_business_print');
    Route::get('/order_business_print/{id}', 'OfficialBusinessApplicationController@printorder')->name('order_business_print');
    // Leave Application
    Route::get('/leaves/{id}', 'LeaveController@index')->name('leaves');
    Route::get('/leave_application/{id}/{view}', 'LeaveController@add')->name('leave_application');
    Route::post('/leave_add/{id}', 'LeaveController@store')->name('leave_add');
    Route::get('/leave_process/{id}/{process_id}/{remarks}', 'LeaveController@process')->name('leave_process');
    Route::get('/leave_delete/{id}', 'LeaveController@destroy')->name('leave_delete');
    Route::get('/leave_print/{id}', 'LeaveController@print')->name('leave_print');
    Route::get('/leave_attachments/{id}', 'LeaveController@attachments')->name('leave_attachments');
    Route::get('/leave_remove_attachments/{id}', 'LeaveController@remove_attachments')->name('leave_remove_attachments');
    Route::get('/leave_without_pay/{id}/{leave_type_id}', 'LeaveController@checkLWOP')->name('leave_without_pay');
    Route::get('/credit_cancelled_leave/{leave_cancelled_id}', 'LeaveController@creditCancelledLeave')->name('credit_cancelled_leave');
    Route::post('/leave_cancel_attachment', 'LeaveController@cancel_attachment')->name('leave_cancel_attachment');
    Route::get('/leave_cancel_attachment_download/{id}', 'LeaveController@download')->name('leave_cancel_attachment_download');
    Route::get('/leave_balance_validation/{leave_id}/{employee_id}/{date_from}/{date_to}', 'LeaveController@leaveValidation')->name('leave_balance_validation');
    Route::get('/leave_attachment_download/{id}', 'LeaveController@downloadAttachment')->name('leave_attachment_download');
    // Leave Monetization
    Route::get('/leave_monetization/{id}', 'MonetizationController@loadMonetization')->name('leave_monetization');
    Route::post('/leave_monetization_add', 'MonetizationController@storeMonetization')->name('leave_monetization_add');
    Route::get('/leave_monetization_delete/{id}', 'MonetizationController@deleteMonetization')->name('leave_monetization_delete');
    Route::get('/leave_monetization_process/{id}/{type_id}/{remarks}', 'MonetizationController@processMonetization')->name('leave_monetization_process');
    Route::get('/leave_monetization_attachment/{id}', 'MonetizationController@loadAttachments')->name('leave_monetization_attachment');
    Route::get('/leave_monetization_attachment_delete/{id}', 'MonetizationController@deleteAttachments')->name('leave_monetization_attachment_delete');
    Route::get('/leave_monetization_attachment_download/{id}', 'MonetizationController@download')->name('leave_monetization_attachment_download');
    // Daily Time Record
    Route::get('/daily_time_records/{id}', 'DailyTimeRecordController@index')->name('daily_time_records');
    Route::get('/daily_time_record_employee/{id}/{payroll_period_id}', 'DailyTimeRecordController@view')->name('daily_time_record_employee');
    Route::get('daily_time_record_logs/{id}', 'DailyTimeRecordController@logs')->name('daily_time_record_logs');
    Route::get('get_daily_time_record_logs/{id}/{from}/{to}', 'DailyTimeRecordController@getLogs')->name('get_daily_time_record_logs');
    Route::post('/daily_time_record_save/{id}', 'DailyTimeRecordController@store')->name('daily_time_record_save');
    Route::get('/review_daily_time_records/{id}', 'DailyTimeRecordController@loadDTRRequest')->name('review_daily_time_records');
    Route::get('/review_daily_time_record_add/{id}', 'DailyTimeRecordController@reviewDTRRequest')->name('review_daily_time_record_add');
    Route::get('/review_daily_time_record_approve/{id}/{type_id}', 'DailyTimeRecordController@approve')->name('review_daily_time_record_approve');
    Route::get('/review_daily_time_record_download/{id}', 'DailyTimeRecordController@download')->name('review_daily_time_record_download');
    // Payslip
    Route::get('/payslips/{id}', 'PayslipController@index')->name('payslips');
    Route::get('/payslip_view/{id}/{payroll_id}', 'PayslipController@view')->name('payslip_view');
    Route::get('/payslip_print/{id}/{payroll_id}', 'PayslipController@print')->name('payslip_print');
    // Panel Interview
    Route::get('/panel_interview_list/{id}', 'InterviewController@panel_interview')->name('panel_interview_list');
    Route::get('/panel_interview_applicant_pds/{applicant_id}', 'InterviewController@panel_interview_pds')->name('panel_interview_applicant_pds');
    Route::get('/panel_interview_applicant_exam/{applicant_id}', 'InterviewController@panel_interview_exam')->name('panel_interview_applicant_exam');
    Route::get('/panel_interview_applicant_rating/{applicant_id}/{employee_id}/{interview_id}', 'InterviewController@panel_interview_rating')->name('panel_interview_applicant_rating');
    Route::post('/panel_interview_applicant_rating_add/{rating_id}', 'InterviewController@interview_rating')->name('panel_interview_applicant_rating_add');

    // Human Resource Module Routes
    // Applicant Records
    Route::get('/applicants', 'ApplicantVacanciesController@index')->name('applicants');
    Route::get('/change_status/{id}/{status_id}', 'ApplicantVacanciesController@changestatus')->name('change_status');
    Route::post('/change_status/{id}/{status_id}', 'ApplicantVacanciesController@changestatus')->name('change_status');
    Route::get('/applicant_list/{plantilla_id}', 'ApplicantsController@index')->name('applicant_list');
    Route::get('/applicant_info/{id}/{plantilla_id}', 'ApplicantsController@info')->name('applicant_info');
    Route::get('/applicant_not_qualified/{id}', 'ApplicantsController@notqualified')->name('applicant_not_qualified');
    Route::get('/applicant_will_not_proceed/{id}', 'ApplicantsController@willnotproceed')->name('applicant_will_not_proceed');
    Route::get('/applicant_proceed/{id}', 'ApplicantsController@proceed')->name('applicant_proceed');
    Route::post('/applicant_not_qualified/{id}', 'ApplicantsController@notqualified')->name('applicant_not_qualified');
    Route::post('/applicant_will_not_proceed/{id}', 'ApplicantsController@willnotproceed')->name('applicant_will_not_proceed');
    Route::post('/applicant_proceed/{id}', 'ApplicantsController@proceed')->name('applicant_proceed');
    Route::get('/applicant_for_hiring/{id}/{plantilla_id}', 'ApplicantsController@forhiring')->name('applicant_for_hiring');
    Route::post('/applicant_for_hiring/{id}/{plantilla_id}', 'ApplicantsController@forhiring')->name('applicant_for_hiring');
    // Applicant Hiring
    Route::get('/applicant_hiring', 'ApplicantHiringController@index')->name('applicant_hiring');
    Route::get('/applicant_hiring_list/{id}/{type}', 'ApplicantHiringController@hiring')->name('applicant_hiring_list');
    Route::get('/applicant_resume/{id}', 'ApplicantHiringController@download')->name('applicant_resume');
    Route::get('/applicant_process/{id}/{position_id}/{is_plantilla}/{type}', 'ApplicantHiringController@process')->name('applicant_process');
    Route::get('/applicant_download_zip/{id}/{position}', 'ApplicantHiringController@download_zip')->name('applicant_download_zip');
    Route::get('/applicant_hiring_applicant_info/{id}', 'ApplicantHiringController@info')->name('applicant_hiring_applicant_info');
    Route::post('/applicant_eete_rating/{id}', 'ApplicantHiringController@rating')->name('applicant_eete_rating');
    // Employee Records
    Route::get('/employees', 'EmployeesController@index')->name('employees');
    Route::get('/employee_add/{id}', 'EmployeesController@add')->name('employee_add');
    Route::get('/employee_delete/{type_id}/{id}', 'EmployeesController@delete')->name('employee_delete');
    Route::post('/employee_add/{id}/{is_employee_portal}', 'EmployeesController@store')->name('employee_store');
    Route::delete('/employee_delete/{type_id}/{id}', 'EmployeesController@destroy')->name('employee_destroy');
    Route::get('/employee_download/{id}', 'EmployeesController@download')->name('employee_download');

    // Employee Documents
    Route::get('/employee_documents/{employee_document_id}', 'EmployeeDocumentController@getAttachments')->name('get_employee_documents');
    Route::get('/employee_documents_delete/{employee_document_id}', 'EmployeeDocumentController@delete')->name('delete_employee_documents');
    Route::get('/load_employee_documents/{employee_id}', 'EmployeeDocumentController@loadDocuments')->name('load_employee_documents');
    Route::get('/load_employee_documents_range/{employee_id}/{date_from}/{date_to}', 'EmployeeDocumentController@loadDocumentsRange')->name('load_employee_documents_range');
    Route::get('/download_employee_documents/{employee_document_id}', 'EmployeeDocumentController@download')->name('download_employee_documents');
    Route::post('/employee_documents_store/{employee_document_id}', 'EmployeeDocumentController@store')->name('employee_documents_store');
    Route::get('/employee_document_preview/{employee_document_id}', 'EmployeeDocumentController@preview')->name('employee_document_preview');
    Route::get('/delete_preview/{document_id}', 'EmployeeDocumentController@deletePreview')->name('delete_preview');

    // Employee Promotion
    Route::get('/promotion_list', 'EmployeePromotionController@index')->name('promotions');
    Route::get('/promotion_add/{id}', 'EmployeePromotionController@add')->name('promotion_add');
    Route::post('/promotion_add/{id}', 'EmployeePromotionController@store')->name('promotion_add');
    // Employee Off-Boarding
    Route::get('/off_boarding_list', 'EmployeeOffBoardingController@index')->name('off_boardings');
    Route::get('/off_boarding_add/{id}', 'EmployeeOffBoardingController@add')->name('off_boarding_add');
    Route::get('/off_boarding_info/{id}/{employee_id}', 'EmployeeOffBoardingController@info')->name('off_boarding_info');
    Route::get('/off_boarding_reactivate/{id}/{employee_id}', 'EmployeeOffBoardingController@activate')->name('off_boarding_activate');
    Route::post('/off_boarding_add/{id}', 'EmployeeOffBoardingController@store')->name('off_boarding_add');
    Route::post('/off_boarding_reactivate/{id}/{employee_id}', 'EmployeeOffBoardingController@reactivate')->name('off_boarding_reactivate');
    // Step Increment
    // Route::get('/step_increments', 'StepIncrementController@index')->name('step_increments');
    // Route::get('/step_increment_add/{id}', 'StepIncrementController@add')->name('step_increment_add');
    // Route::post('/step_increment_add/{id}', 'StepIncrementController@store')->name('step_increment_add');
    // Route::post('/step_increments', 'StepIncrementController@addStep')->name('step_increments');
    Route::get('/step_increments', 'EmployeeStepIncrementController@index')->name('step_increments');
    Route::get('/step_increments_add', 'EmployeeStepIncrementController@add')->name('step_increments_add');
    Route::get('/step_increments_edit/{month_id}/{year_id}', 'EmployeeStepIncrementController@edit')->name('step_increments_edit');
    Route::get('/load_employee_step_increment/{month_id}/{year_id}', 'EmployeeStepIncrementController@loadEmployees')->name('load_employee_step_increment');
    Route::get('/get_employee_increments_salary/{step_increment_id}/{employee_id}', 'EmployeeStepIncrementController@changeNewSalary')->name('get_employee_increments_salary');
    Route::get('/forward_employee_step_increment/{month_id}/{year_id}', 'EmployeeStepIncrementController@forwarded')->name('forward_employee_step_increment');
    Route::post('/step_increments_store', 'EmployeeStepIncrementController@store')->name('step_increments_store');
    Route::get('/step_increment_forward/{month_id}/{year_id}', 'EmployeeStepIncrementController@forwardApprover')->name('step_increment_forward');
    Route::get('/step_increment_approval', 'EmployeeStepIncrementController@process')->name('step_increment_approval');
    Route::get('/step_increment_for_process/{month_id}/{year_id}', 'EmployeeStepIncrementController@forProcess')->name('step_increment_for_process');
    Route::get('/step_increment_approved/{month_id}/{year_id}', 'EmployeeStepIncrementController@approved')->name('step_increment_approved');
    Route::get('/step_increment_disapproved/{month_id}/{year_id}/{remarks}', 'EmployeeStepIncrementController@disapproved')->name('step_increment_disapproved');
    Route::get('/step_increment_print/{month_id}/{year_id}', 'EmployeeStepIncrementController@print')->name('step_increment_print');
    Route::get('/step_increment_delete_employee/{step_increment_id}', 'EmployeeStepIncrementController@delete');

    // Salary Adjustment
    Route::get('/salary_adjustments', 'SalaryAdjustmentController@index')->name('salary_adjustments');
    Route::post('/salary_adjustment_add', 'SalaryAdjustmentController@process')->name('salary_adjustment_add');
    // IPCR
    Route::get('/ipcr', 'IPCRController@index')->name('ipcr');
    Route::get('/ipcr_add/{id}', 'IPCRController@add')->name('ipcr_add');
    Route::post('/ipcr_add/{id}', 'IPCRController@store')->name('ipcr_add');
    Route::get('/ipcr_review/{id}', 'IPCRController@review')->name('ipcr_review');
    Route::get('/ipcr_adjective/{rating}', 'IPCRController@ipcr_adjective')->name('ipcr_adjective');
    Route::post('/ipcr_rating/{id}', 'IPCRController@rating')->name('ipcr_rating');
    // Review 201 Updates
    Route::get('/review_201_updates', 'EmployeeRequestController@list')->name('review_201_updates');
    Route::get('/review_201_updates_review/{id}', 'EmployeeRequestController@review')->name('review_201_updates_review');
    Route::get('/review_201_updates_approval/{id}/{type_id}', 'EmployeeRequestController@approval')->name('review_201_updates_approval');
    Route::post('/review_201_updates_approve/{id}/{type_id}', 'EmployeeRequestController@approve')->name('review_201_updates_approve');
    // Update 201 Schedule
    Route::get('/update_201_schedule', 'Update201ScheduleController@index')->name('update_201_schedule');
    Route::post('/update_201_schedule/{id}', 'Update201ScheduleController@store')->name('update_201_schedule_add');
    // Export Employee Data
    Route::get('/export_employee_data', 'EmployeeFileController@export')->name('export_employee_data');
    // Vacant Position Posting
    Route::get('/vacant_position_posting', 'VacantPositionController@index')->name('vacant_position_posting');
    Route::get('/vacant_position_details/{id}', 'VacantPositionController@details')->name('vacant_position_details');
    Route::post('/vacant_position_process/{id}/{process_id}', 'VacantPositionController@process')->name('vacant_position_process');
    // Examination Setup
    Route::get('/examination_setup', 'ExaminationController@index')->name('examination_setup');
    Route::get('/examination_setup_add/{id}', 'ExaminationController@add')->name('examination_setup_add');
    Route::get('/examination_setup_questionaire/{id}/{type_id}', 'ExaminationController@questionaire')->name('examination_setup_questionaire');
    Route::post('/examination_setup_add/{id}', 'ExaminationController@store')->name('examination_setup_add');
    Route::post('/examination_setup_add_position/{id}', 'ExaminationController@add_position')->name('examination_setup_add_position');
    Route::post('/examination_setup_delete_position/{id}', 'ExaminationController@delete_position')->name('examination_setup_delete_position');
    // Employee Competency
    Route::get('/employees_competency', 'EmployeesCompetenciesController@index')->name('employees_competency');
    Route::get('/employees_competency_edit/{id}', 'EmployeesCompetenciesController@edit')->name('employees_competency_edit');
    Route::patch('/employees_competency_edit/{id}', 'EmployeesCompetenciesController@update');
    Route::get('/employees_competency_details/{id}', 'EmployeesCompetenciesController@details')->name('employees_competency_details');
    // Examination Schedule
    Route::get('/examination_schedule_list', 'ExaminationController@schedules')->name('examination_schedule_list');
    Route::get('/examination_schedule_add/{id}', 'ExaminationController@schedules_add')->name('examination_schedule_add');
    Route::post('/examination_schedule_add/{id}', 'ExaminationController@schedules_store')->name('examination_schedule_add');
    Route::get('/examination_schedule_process/{id}/{type_id}', 'ExaminationController@schedule_process')->name('examination_schedule_process');
    Route::get('/examination_schedule_result/{id}', 'ExaminationController@schedules_result')->name('examination_schedule_result');
    Route::post('/examination_schedule_add_examinees/{id}', 'ExaminationController@addExaminees')->name('examination_schedule_add_examinees');
    Route::get('/examination_schedule_delete_examinees/{id}', 'ExaminationController@deleteExaminees')->name('examination_schedule_delete_examinees');

    // Applicant Ranking and Shortlisting
    Route::get('/applicant_shortlisting_list', 'ApplicantShortlistingController@index')->name('applicant_shortlisting_list');
    Route::get('/applicant_shortlisting_add/{id}/{rating}/{status_id}', 'ApplicantShortlistingController@add')->name('applicant_shortlisting_add');
    Route::get('/applicant_shortlisting_delete/{id}', 'ApplicantShortlistingController@delete')->name('applicant_shortlisting_delete');
    // Interview Schedule
    Route::get('/interview_schedule_list', 'InterviewController@index')->name('interview_schedule_list');
    Route::get('/interview_schedule_add/{id}', 'InterviewController@add')->name('interview_schedule_add');
    Route::post('/interview_schedule_add/{id}', 'InterviewController@store')->name('interview_schedule_add');
    Route::post('/interview_schedule_add_panel/{id}', 'InterviewController@addPanel')->name('interview_schedule_add_panel');
    Route::post('/interview_schedule_add_applicant/{id}', 'InterviewController@addApplicant')->name('interview_schedule_add_applicant');
    Route::get('/interview_schedule_delete_panel/{id}', 'InterviewController@deletePanel')->name('interview_schedule_delete_panel');
    Route::get('/interview_schedule_delete_applicant/{id}', 'InterviewController@deleteApplicant')->name('interview_schedule_delete_applicant');
    Route::get('/interview_schedule_process/{id}/{type_id}', 'InterviewController@process')->name('interview_schedule_process');
    Route::get('/interview_cancel/{id}/{applicant_id}', 'InterviewController@cancelInterview')->name('interview_cancel');
    // HRDD Review
    Route::get('/hrdd_review_per_position', 'HRDDReviewController@hrdd_review_per_position')->name('hrdd_review_per_position');
    Route::get('/hrdd_review_list/{position_id}', 'HRDDReviewController@index')->name('hrdd_review_list');
    Route::get('/hrdd_review_pds/{id}', 'HRDDReviewController@hrdd_review_pds')->name('hrdd_review_pds');
    Route::get('/hrdd_review_exam/{id}', 'HRDDReviewController@hrdd_review_exam')->name('hrdd_review_exam');
    Route::get('/hrdd_review_admin/{id}', 'HRDDReviewController@forwardAdmin')->name('hrdd_review_admin');
    Route::post('/hrdd_review_submit/{id}', 'HRDDReviewController@submitToAdmin')->name('hrdd_review_submit');
    Route::get('/hrdd_review_delete_docs/{id}/{document_type_id}', 'HRDDReviewController@deleteDocs')->name('hrdd_review_delete_docs');
    Route::post('/hrdd_review_store', 'HRDDReviewController@hrdd_review_store')->name('hrdd_review_store');
    // Administrator Selection
    Route::get('/administrator_selection_per_position', 'AdministratorSelectionController@administrator_selection_per_position')->name('administrator_selection_per_position');
    Route::get('/administrator_selection/{position_id}', 'AdministratorSelectionController@index')->name('administrator_selection');
    Route::get('/administrator_selection_pds/{id}', 'AdministratorSelectionController@administrator_selection_pds')->name('administrator_selection_pds');
    Route::get('/administrator_selection_exam/{id}', 'AdministratorSelectionController@administrator_selection_exam')->name('administrator_selection_exam');
    Route::get('/administrator_selection_hrdd_rating/{id}', 'AdministratorSelectionController@hrdd_rating')->name('administrator_selection_hrdd_rating');
    Route::get('/administrator_selection_download/{id}/{type_id}', 'AdministratorSelectionController@download')->name('administrator_selection_download');
    Route::get('/administrator_selection_appoint/{id}', 'AdministratorSelectionController@appoint')->name('administrator_selection_appoint');
    Route::post('/administrator_selection_br_upload/{id}', 'AdministratorSelectionController@br_upload')->name('br_upload');
    Route::post('/CS_Form5_print', 'AdministratorSelectionController@print')->name('CS_Form5_print');
    //Training Management
    Route::get('/training_management', 'EmployeeTrainingController@index')->name('training_management');
    Route::post('/training_management_add', 'EmployeeTrainingController@process')->name('training_management_add');
    Route::post('/training_management_submit/{id}/{subcompetency_id}', 'EmployeeTrainingController@submittraining')->name('training_management_submit');
    Route::post('/training_management_cancel/{id}/{subcompetency_id}', 'EmployeeTrainingController@canceltraining')->name('training_management_cancel');
    // Training Requisitioner Setup
    Route::get('/training_requisitioner_list', 'TrainingRequisitionersController@index')->name('training_requisitioner');
    // Training Requisition
    Route::get('/training_requisition', 'TrainingRequisitionController@index')->name('training_requisition');
    Route::get('/training_requisition_add/{id}', 'TrainingRequisitionController@add')->name('training_requisition_add');
    Route::post('/training_add/{id}', 'TrainingRequisitionController@store')->name('training_add');
    Route::get('/training_remove_attachments/{id}', 'TrainingRequisitionController@remove_attachments')->name('training_remove_attachments');
    Route::get('/training_attachments/{id}', 'TrainingRequisitionController@attachments')->name('training_attachments');
    Route::get('/training_attachment_download/{id}', 'TrainingRequisitionController@downloadAttachment')->name('training_attachment_download');
    Route::get('/training_requisition_load', 'TrainingRequisitionController@loadUnassignedEmployees')->name('training_requisition_load');
    Route::post('/training_requisition_add_employee/{id}', 'TrainingRequisitionController@addEmployees')->name('training_requisition_add_employee');
    Route::post('/training_requisition_remove_employee/{header_id}/{id}', 'TrainingRequisitionController@removeEmployees')->name('training_requisition_remove_employee');

    // HR Reports
    // Personal Data Sheet
    Route::get('/pds', 'PersonalDataSheetController@index')->name('pds');
    Route::post('/pds_print', 'PersonalDataSheetController@print')->name('pds_print');
    Route::get('/pds_download/{id}', 'PersonalDataSheetController@download')->name('pds_download');
    // Employee Certificates
    Route::get('/employee_certificates', 'EmployeeCertificateReportController@index')->name('employee_certificates');
    Route::post('/employee_certificates_print', 'EmployeeCertificateReportController@print')->name('employee_certificates_print');
    // Employee Compensation Certificates
    Route::get('/employee_compensation_certificates', 'EmployeeCertificateCompensationReportController@index')->name('employee_compensation_certificates');
    Route::post('/employee_certificates_compensation_print', 'EmployeeCertificateCompensationReportController@print')->name('employee_certificates_compensation_print');
    // Employee Dependent Certificates
    Route::get('/employee_dependent_certificates', 'EmployeeDependentCertificateController@index')->name('employee_dependent_certificates');
    Route::post('/employee_dependent_certificates_print', 'EmployeeDependentCertificateController@print')->name('employee_dependent_certificates_print');
    // Employee Dependent Certificates
    Route::get('/employee_medical_certificates', 'EmployeeMedicalCertificateController@index')->name('employee_medical_certificates');
    Route::post('/employee_medical_certificates_print', 'EmployeeMedicalCertificateController@print')->name('employee_medical_certificates_print');
    // Appointment Certificate
    Route::get('/appointment_certificate', 'AppointmentCertificateController@index')->name('appointment_certificate');
    Route::post('/appointment_certificate_print', 'AppointmentCertificateController@print')->name('appointment_certificate_print');
    // Assumption of Duty
    Route::get('/assumption_of_duty', 'AssumptionOfDutyController@index')->name('assumption_of_duty');
    Route::post('/assumption_of_duty_print', 'AssumptionOfDutyController@print')->name('assumption_of_duty_print');
    // Plantilla of Casual Appointment
    Route::get('/casual_appointment', 'PlantillaOfCasualAppointmentController@index')->name('casual_appointment');
    Route::post('/casual_appointment_print', 'PlantillaOfCasualAppointmentController@print')->name('casual_appointment_print');
    //Acceptance of Resignation
    Route::get('/acceptance_of_resignation', 'AcceptanceofResignationController@index')->name('acceptance_of_resignation');
    Route::post('/acceptance_of_resignation_print', 'AcceptanceofResignationController@print')->name('acceptance_of_resignation_print');
    // Oath of Office
    Route::get('/oath_of_office', 'OathOfOfficeController@index')->name('oath_of_office');
    Route::post('/oath_of_office_print', 'OathOfOfficeController@print')->name('oath_of_office_print');
    // Congratulatory Letter
    Route::get('/congratulatory_letter', 'CongratulatoryLetterController@index')->name('congratulatory_letter');
    Route::post('/congratulatory_letter_print', 'CongratulatoryLetterController@print')->name('congratulatory_letter_print');
    // Board Resolution
    Route::get('/board_resolution', 'BoardResolutionController@index')->name('board_resolution');
    Route::post('/board_resolution_print', 'BoardResolutionController@print')->name('board_resolution_print');
    // NOSI
    Route::get('/nosi', 'NoticeOfSalaryStepController@index')->name('nosi');
    Route::post('/nosi_print', 'NoticeOfSalaryStepController@print')->name('nosi_print');
    // NOSA
    Route::get('nosa', 'NoticeOfSalaryAdjustmentController@index')->name('nosa');
    Route::post('nosa_print', 'NoticeOfSalaryAdjustmentController@print')->name('nosa_print');
    // Service Record
    Route::get('/service_record', 'ServiceRecordController@index')->name('service_record');
    Route::post('/service_record_print', 'ServiceRecordController@print')->name('service_record_print');
    // No Pending Certificate
    Route::get('/no_pending_certificates', 'NoPendingCertificateReportController@index')->name('no_pending_certificates');
    Route::post('/no_pending_certificates_print', 'NoPendingCertificateReportController@print')->name('no_pending_certificates_print');
    // ATM Request Certificate
    Route::get('/atm_request_certificates', 'ATMRequestCertificateReportController@index')->name('atm_request_certificates');
    Route::post('/atm_request_certificates_print', 'ATMRequestCertificateReportController@print')->name('atm_request_certificates_print');
    // Offboarding Certificate
    Route::get('/offboarding_certificates', 'OffboardingCertificateReportController@index')->name('offboarding_certificates');
    Route::post('/offboarding_certificates_print', 'OffboardingCertificateReportController@print')->name('offboarding_certificates_print');
    // Appearance Certificate
    Route::get('/appearance_certificates', 'AppearanceCertificateReportController@index')->name('appearance_certificates');
    Route::post('/appearance_certificates_print', 'AppearanceCertificateReportController@print')->name('appearance_certificates_print');
    // OJT Certificate
    Route::get('/ojt_certificates', 'OJTCertificateReportController@index')->name('ojt_certificates');
    Route::get('/ojt_certificates_add', 'OJTCertificateReportController@add')->name('ojt_certificates_add');
    Route::get('/ojt_certificates_edit/{id}', 'OJTCertificateReportController@edit')->name('ojt_certificates_edit');
    Route::post('/ojt_certificates_add', 'OJTCertificateReportController@store');
    Route::patch('/ojt_certificates_edit/{id}', 'OJTCertificateReportController@update');
    Route::get('/ojt_certificates_print/{id}', 'OJTCertificateReportController@print')->name('ojt_certificates_print');
    // Terminal Leave Endorsement
    Route::get('/terminal_leave_endorsements', 'TerminalLeaveEndorsementReportController@index')->name('terminal_leave_endorsements');
    Route::post('/terminal_leave_endorsement_print', 'TerminalLeaveEndorsementReportController@print')->name('terminal_leave_endorsement_print');
    // Travel Abroad Endorsement
    Route::get('/travel_abroad_endorsements', 'TravelAbroadEndorsementReportController@index')->name('travel_abroad_endorsements');
    Route::post('/travel_abroad_endorsement_print', 'TravelAbroadEndorsementReportController@print')->name('travel_abroad_endorsement_print');
    // Land Registration Endorsement
    Route::get('/land_registration_endorsements', 'LandRegistrationEndorsementReportController@index')->name('land_registration_endorsements');
    Route::post('/land_registration_endorsement_print', 'LandRegistrationEndorsementReportController@print')->name('land_registration_endorsement_print');
    // DOJ Endorsement
    Route::get('/doj_endorsements', 'DOJEndorsementReportController@index')->name('doj_endorsements');
    Route::post('/doj_endorsement_print', 'DOJEndorsementReportController@print')->name('doj_endorsement_print');
    // Plantilla Report
    Route::get('/plantilla_reports', 'PlantillaReportController@index')->name('plantilla_reports');
    Route::post('/plantilla_report_print', 'PlantillaReportController@print')->name('plantilla_report_print');
    //Length of Service
    Route::get('/length_of_service', 'LengthofServiceController@index')->name('length_of_service');
    // Time Keeping Module

    // Fix Schedule
    Route::get('/fix_schedules', 'FixScheduleController@index')->name('fix_schedules');
    Route::get('/fix_schedule_add/{id}', 'FixScheduleController@add')->name('fix_schedule_add');
    Route::post('/fix_schedule_add/{id}', 'FixScheduleController@store')->name('fix_schedule_add');
    // Assign Schedule
    Route::get('/assign_schedules', 'AssignFixScheduleController@index')->name('assign_schedules');
    Route::post('/assign_schedules_add', 'AssignFixScheduleController@store')->name('assign_schedule_add');
    // Leave Credits
    Route::get('/leave_credits', 'LeaveCreditController@index')->name('leave_credits');
    Route::post('/leave_credit_add', 'LeaveCreditController@store')->name('leave_credit_add');
    // Shifting Schedule
    Route::get('/shift_schedules', 'ShiftScheduleController@index')->name('shift_schedules');
    Route::get('/shift_schedule_add/{id}', 'ShiftScheduleController@add')->name('shift_schedule_add');
    Route::post('/shift_schedule_add/{id}', 'ShiftScheduleController@store')->name('shift_schedule_add');
    Route::get('/shift_schedule_load', 'ShiftScheduleController@loadUnassignedEmployees')->name('shift_schedule_load');
    Route::post('/shift_schedule_add_employee/{id}', 'ShiftScheduleController@addEmployees')->name('shift_schedule_add_employee');
    Route::post('/shift_schedule_remove_employee/{header_id}/{id}', 'ShiftScheduleController@removeEmployees')->name('shift_schedule_remove_employee');
    // Process Daily Time Records
    Route::post('/process_attendance_add', 'ProcessAttendanceController@process')->name('process_attendance_add');
    Route::get('/process_attendance', 'ProcessAttendanceController@index')->name('process_attendance');
    Route::get('/process_attendance_view/{id}/{payroll_period_id}', 'ProcessAttendanceController@view')->name('process_attendance_view');
    Route::post('/process_attendance_reprocess/{id}/{payroll_period_id}', 'ProcessAttendanceController@reprocess')->name('process_attendance_reprocess');
    Route::get('/process_attendance_report/{employee_id}/{payroll_period_id}', 'ProcessAttendanceController@print')->name('process_attendance_report');
    Route::post('/process_attendance_offset', 'ProcessAttendanceController@offset')->name('process_attendance_offset');
    Route::post('/process_attendance_cancel_offset/{id}/{payroll_period_id}', 'ProcessAttendanceController@cancel_offset')->name('process_attendance_cancel_offset');
    Route::post('/process_attendance_offset_details/{id}/{payroll_period_id}', 'ProcessAttendanceController@offset_details')->name('process_attendance_offset_details');
    Route::post('/process_attendance_cancel_offset_details/{id}/{payroll_period_id}', 'ProcessAttendanceController@cancel_offset_details')->name('process_attendance_cancel_offset_details');
    // leave Approval
    Route::get('/leave_approvals', 'LeaveController@monitoring')->name('leave_approvals');
    // OB Approval
    Route::get('/official_business_approvals', 'OfficialBusinessApplicationController@monitoring')->name('official_business_approvals');
    // OT Approval
    Route::get('/overtime_approvals', 'OvertimeApplicationController@monitoring')->name('overtime_approvals');
    // Work Cancellation
    Route::get('/work_cancellations', 'WorkCancellationController@index')->name('work_cancellations');
    Route::get('/work_cancellation_add/{id}', 'WorkCancellationController@add')->name('work_cancellation_add');
    Route::post('/work_cancellation_add/{id}', 'WorkCancellationController@store')->name('work_cancellation_add');
    //COC Details
    Route::get('/coc_details', 'COCDetailsController@index')->name('coc_details');
    // Tardiness Report
    Route::get('/tardiness_report', 'TradionessReportController@index')->name('tardiness_report');
    Route::get('/tardiness_report_view', 'TradionessReportController@view')->name('tardiness_report_view');
    // Leave Taken Monitoring
    Route::get('/leave_taken_monitoring', 'LeaveTakenController@index')->name('leave_taken_monitoring');
    Route::get('/leave_taken/{id}', 'LeaveTakenController@load')->name('leave_taken');
    // Leave Credit Card
    Route::get('/leave_credit_card', 'LeaveCreditCardController@index')->name('leave_credit_card');
    Route::get('/leave_credit_card_details/{id}', 'LeaveCreditCardController@details')->name('leave_credit_card_details');
    Route::get('/leave_credit_card_load/{id}/{year}', 'LeaveCreditCardController@getLeaveCredits')->name('leave_credit_card_load');
    // Biometrics Data
    Route::get('/biometrics', 'BiometricsController@index')->name('biometrics');
    Route::get('/biometrics_load', 'BiometricsController@load')->name('biometrics_load');
    Route::get('/biometrics_setup', 'BiometricsController@configLoad')->name('biometrics_setup');
    Route::post('/biometrics_setup', 'BiometricsController@config')->name('biometrics_setup');

    // Payroll Module

    // Payroll Period
    Route::get('/payroll_periods', 'PayrollPeriodController@index')->name('payroll_periods');
    Route::get('/payroll_period_add/{id}', 'PayrollPeriodController@add')->name('payroll_period_add');
    Route::post('/payroll_period_add/{id}', 'PayrollPeriodController@store')->name('payroll_period_add');
    // Payroll Item Schedule
    Route::get('/payroll_item_schedules', 'PayrollItemScheduleController@index')->name('payroll_item_schedules');
    Route::post('/payroll_item_schedule_store', 'PayrollItemScheduleController@store')->name('payroll_item_schedule_store');
    Route::get('/payroll_item_schedule_incomes/{payroll_interval_type_id}/{payroll_period_type_id}/{employment_type_id}', 'PayrollItemScheduleController@getIncome');
    Route::get('/payroll_item_schedule_deductions/{payroll_interval_type_id}/{payroll_period_type_id}/{employment_type_id}', 'PayrollItemScheduleController@getDeduction');
    Route::get('/get_payroll_item_header/{payroll_interval_type_id}/{payroll_period_type_id}/{employment_type_id}', 'PayrollItemScheduleController@getHeader');
    // Payroll Income and Deduction
    Route::get('/payroll_income_and_deductions', 'IncomeDeductionAjustmentController@index')->name('payroll_income_and_deductions');
    Route::get('/payroll_income_and_deductions_getincome/{payroll_period_type_id}/{employment_type_id}', 'IncomeDeductionAjustmentController@getIncomeList')->name('payroll_income_and_deductions_getincome');
    Route::get('/payroll_income_and_deductions_getdeduction/{payroll_period_type_id}/{employment_type_id}', 'IncomeDeductionAjustmentController@getDeductionList')->name('payroll_income_and_deductions_getdeduction');
    Route::get('/payroll_income_and_deductions_getEmployeeIncome/{payroll_period_type_id}/{employment_type_id}/{income_id}', 'IncomeDeductionAjustmentController@getEmployeeIncome')->name('payroll_income_and_deductions_getEmployeeIncome');
    Route::get('/payroll_income_and_deductions_getEmployeePreviousIncome/{payroll_period_type_id}/{employment_type_id}/{income_id}', 'IncomeDeductionAjustmentController@getEmployeePreviousIncome')->name('payroll_income_and_deductions_getEmployeePreviousIncome');
    Route::get('/payroll_income_and_deductions_getEmployeeDeduction/{payroll_period_type_id}/{employment_type_id}/{deduction_id}', 'IncomeDeductionAjustmentController@getEmployeeDeduction')->name('payroll_income_and_deductions_getEmployeeDeduction');
    Route::get('/payroll_income_and_deductions_getEmployeePreviousDeduction/{payroll_period_type_id}/{employment_type_id}/{deduction_id}', 'IncomeDeductionAjustmentController@getEmployeePreviousDeduction')->name('payroll_income_and_deductions_getEmployeePreviousDeduction');
    Route::get('/payroll_income_and_deductions_getEmployeeList/{payroll_period_type_id}/{employment_type_id}/{type_id}/{item_id}', 'IncomeDeductionAjustmentController@getEmployeeList')->name('payroll_income_and_deductions_getEmployeeList');
    Route::post('/payroll_income_store', 'IncomeDeductionAjustmentController@storeIncome')->name('payroll_income_store');
    Route::post('/payroll_deduction_store', 'IncomeDeductionAjustmentController@storeDeduction')->name('payroll_deduction_store');
    // Loan Application
    Route::get('/loan_applications', 'LoanApplicationController@index')->name('loan_applications');
    Route::get('/loan_application_add/{id}', 'LoanApplicationController@add')->name('loan_application_add')->middleware('password.confirm');
    Route::post('/loan_application_add/{id}', 'LoanApplicationController@store')->name('loan_application_add');
    Route::get('/loan_application_reconstruct/{id}', 'LoanApplicationController@reconstruct')->name('loan_application_reconstruct')->middleware('password.confirm');
    Route::post('/loan_application_reconstruct/{id}', 'LoanApplicationController@reconstructstore')->name('loan_application_reconstruct');
    Route::get('/loan_validation/{id}', 'LoanApplicationController@loanValidation')->name('loan_validation');
    // Payroll Process
    Route::get('/payroll_process', 'PayrollProcessController@index')->name('payroll_process');
    Route::get('/payroll_process_summary/{id}', 'PayrollProcessController@summary')->name('payroll_process_summary');
    Route::post('/payroll_processing/{id}', 'PayrollProcessController@process')->name('payroll_processing');
    Route::post('/payroll_posting/{id}/{type_id}', 'PayrollProcessController@posting')->name('payroll_posting');
    Route::get('/payroll_print/{id}', 'PayrollProcessController@printTabulate')->name('payroll_print');
    Route::get('/payroll_summary', 'PayrollProcessController@payrollSummary')->name('payroll_summary');
    Route::get('/payroll_summary_detail', 'PayrollProcessController@payrollSummaryDetail')->name('payroll_summary_detail');
    Route::get('/payroll_summary_print', 'PayrollProcessController@payrollSummaryPrint')->name('payroll_summary_print');
    Route::get('/payroll_summary_with_details_print', 'PayrollProcessController@payrollSummaryDetailsPrint')->name('payroll_summary_with_details_print');
    Route::post('/tax_amount_adjustment/{payroll_period_id}', 'PayrollProcessController@adjustment')->name('tax_amount_adjustment');
    // Overtime Payroll Process
    Route::get('/overtime_payroll', 'OvertimePayrollController@load')->name('overtime_payroll');
    Route::get('/overtime_payroll_add/{id}', 'OvertimePayrollController@add')->name('overtime_payroll_add');
    Route::post('/overtime_payroll_add/{id}', 'OvertimePayrollController@storeOvertimePayroll')->name('overtime_payroll_add');
    Route::post('/overtime_payroll_add_employee/{id}', 'OvertimePayrollController@storeOvertimePayrollEmployee')->name('overtime_payroll_add_employee');
    Route::get('/overtime_payroll_delete_employee/{id}', 'OvertimePayrollController@deleteOvertimePayrollEmployee')->name('overtime_payroll_delete_employee');
    Route::post('/overtime_posting/{id}/{type_id}', 'OvertimePayrollController@processOvertimePayroll')->name('overtime_posting');
    Route::get('/overtime_payroll_process', 'OvertimePayrollController@index')->name('overtime_payroll_process');
    Route::get('/overtime_payroll_print', 'OvertimePayrollController@print')->name('overtime_payroll_print');
    // pacsval
    Route::get('/pacsval', 'PACSVALController@index')->name('pacsval');
    Route::post('/pacsval_export', 'PACSVALController@export')->name('pacsval_export');
    // Payslip Report
    Route::get('/payment_slip', 'EmployeePayslipReportController@index')->name('payment_slip');
    Route::post('/payment_slip_print', 'EmployeePayslipReportController@print')->name('payment_slip_print');
    // Subsistence Report
    Route::get('/subsistence_report', 'SubsistenceController@index')->name('subsistence_report');
    Route::post('/subsistence_report_print', 'SubsistenceController@print')->name('subsistence_report_print');
    // Pending Deductions
    Route::get('/pending_deduction_report', 'PendingDeductionController@index')->name('pending_deduction_report');
    Route::get('/pending_deduction_view', 'PendingDeductionController@view')->name('pending_deduction_view');
    // RATA Report
    Route::get('/rata_payroll_report', 'RATAController@rataReport')->name('rata_payroll_report');
    Route::get('/rata_payroll_print', 'RATAController@print')->name('rata_payroll_print');
    // Hazard Pay Allowance Report
    Route::get('/hazard_pay_report', 'HazardPayController@Report')->name('hazard_pay_report');
    Route::get('/hazard_pay_print', 'HazardPayController@print')->name('hazard_pay_print');
    // Process Mid Year Bonus
    Route::post('/process_midyear_add', 'MidYearBonusController@process')->name('process_midyear_add');
    Route::get('/process_midyear', 'MidYearBonusController@index')->name('process_midyear');
    Route::post('/midyear_post', 'MidYearBonusController@post')->name('midyear_post');
    // Process Year End Bonus
    Route::post('/process_yearend_add', 'YearEndBonusController@process')->name('process_yearend_add');
    Route::get('/process_yearend', 'YearEndBonusController@index')->name('process_yearend');
    Route::post('/yearend_post', 'YearEndBonusController@post')->name('yearend_post');
    // Mid Year Bonus Report
    Route::get('/midyear_report', 'MidYearBonusController@report')->name('midyear_report');
    Route::post('/midyear_print', 'MidYearBonusController@print')->name('midyear_print');
    // Year End Bonus Report
    Route::get('/yearend_report', 'YearEndBonusController@report')->name('yearend_report');
    Route::post('/yearend_print', 'YearEndBonusController@print')->name('yearend_print');
    // Philhealth Remittance Report
    Route::get('/philhealth_remittance', 'PhilhealthRemittanceController@report')->name('philhealth_remittance');
    Route::post('/philhealth_remittance_print', 'PhilhealthRemittanceController@print')->name('philhealth_remittance_print');

    // Pag Ibig Loan Report
    Route::get('/pagibig_loan', 'PagIbigLoanController@index')->name('pagibig_loan');
    Route::post('/print/mandatory', 'PagIbigLoanController@print')->name('mandatory.print');
    Route::post('/print/mp2', 'PagIbigLoanController@printMP2')->name('mp2.print');
    Route::get('/pag_ibig_loan', 'PagIbigLoanController@loan')->name('pag_ibig_loan');
    Route::post('/loan_print', 'PagIbigLoanController@loanprint')->name('loan_print');


    // GSIS Remittance Report
    Route::get('/gsis_remittance', 'GSISRemittanceController@index')->name('gsis_remittance');
    Route::post('/gsis_remittance_print', 'GSISRemittanceController@print')->name('gsis_remittance_print');
    // Bank Remittance Report
    Route::get('/bank_remittance', 'BankRemittanceController@index')->name('bank_remittance');
    Route::post('/bank_remittance_print', 'BankRemittanceController@print')->name('bank_remittance_print');
    // Monetization Payroll
    Route::get('/monetization_payroll', 'MonetizationController@loadMonetizationPayroll')->name('monetization_payroll');
    Route::get('/monetization_payroll_add/{id}', 'MonetizationController@addMonetizationPayroll')->name('monetization_payroll_add');
    Route::post('/monetization_payroll_add/{id}', 'MonetizationController@storeMonetizationPayroll')->name('monetization_payroll_add');
    Route::post('/monetization_payroll_add_employees/{id}', 'MonetizationController@storeMonetizationPayrollEmployee')->name('monetization_payroll_add_employees');
    Route::get('/monetization_payroll_delete_employees/{id}', 'MonetizationController@deleteMonetizationPayrollEmployees')->name('monetization_payroll_delete_employees');
    Route::post('/monetization_payroll_process/{id}/{type_id}', 'MonetizationController@processMonetizationPayroll')->name('monetization_payroll_process');
    Route::get('/monetization_payroll_report', 'MonetizationController@monetizationReport')->name('monetization_payroll_report');
    Route::get('/monetization_payroll_print', 'MonetizationController@print')->name('monetization_payroll_print');
    // Obligation Requests Report
    Route::get('/ors_payroll_report/{id}', 'ObligationRequestController@orsPayroll')->name('ors_payroll_report');
    Route::get('/ors_overtime_report/{id}', 'ObligationRequestController@orsOvertime')->name('ors_overtime_report');
    Route::get('/ors_rata_report/{id}', 'ObligationRequestController@orsRATA')->name('ors_rata_report');
    Route::get('/ors_monetization_report/{id}', 'ObligationRequestController@orsMonetization')->name('ors_monetization_report');
    Route::get('/ors_clothing_report/{id}', 'ObligationRequestController@orsClothing')->name('ors_clothing_report');
    Route::get('/ors_loyalty_report/{id}', 'ObligationRequestController@orsLoyalty')->name('ors_loyalty_report');
    Route::get('/ors_midyear_report/{year_id}', 'ObligationRequestController@orsMidYear')->name('ors_midyear_report');
    Route::get('/ors_yearend_report/{year_id}', 'ObligationRequestController@orsYearEnd')->name('ors_yearend_report');
    // Disbursement Vouchers Report
    Route::get('/dv_payroll_report/{id}', 'DisbursementVoucherController@dvPayroll')->name('dv_payroll_report');
    Route::get('/dv_overtime_report/{id}', 'DisbursementVoucherController@dvOvertime')->name('dv_overtime_report');
    Route::get('/dv_rata_report/{id}', 'DisbursementVoucherController@dvRATA')->name('dv_rata_report');
    Route::get('/dv_monetization_report/{id}', 'DisbursementVoucherController@dvMonetization')->name('dv_monetization_report');
    Route::get('/dv_clothing_report/{id}', 'DisbursementVoucherController@dvClothing')->name('dv_clothing_report');
    Route::get('/dv_loyalty_report/{id}', 'DisbursementVoucherController@dvLoyalty')->name('dv_loyalty_report');
    Route::get('/dv_midyear_report/{year_id}', 'DisbursementVoucherController@dvMidYear')->name('dv_midyear_report');
    Route::get('/dv_yearend_report/{year_id}', 'DisbursementVoucherController@dvYearEnd')->name('dv_yearend_report');
    // Payroll Extra Bonus
    Route::get('/payroll_extra_bonus', 'PayrollExtraBonusController@index')->name('payroll_extra_bonus');
    Route::get('/payroll_extra_bonus_add/{extra_bonus_id}', 'PayrollExtraBonusController@show')->name('payroll_extra_bonus_add');
    Route::post('/payroll_extra_bonus_add', 'PayrollExtraBonusController@store')->name('payroll_extra_bonus_store');
    Route::get('/payroll_extra_bonus_process/{extra_bonus_id}/{type_id}', 'PayrollExtraBonusController@process')->name('payroll_extra_bonus_process');
    Route::get('/payroll_extra_bonus_report', 'PayrollExtraBonusController@report')->name('payroll_extra_bonus_report');
    Route::get('/payroll_extra_bonus_print', 'PayrollExtraBonusController@print')->name('payroll_extra_bonus_print');
    Route::get('/get_extra_bonus_employees/{extra_bonus_type_id}/{department_id}/{year_id}', 'PayrollExtraBonusController@loadEmployees')->name('get_extra_bonus_employees');
    // Pagibig Payroll Amount
    Route::get('/pagibig_payroll', 'PagibigPayrollHeaderController@index')->name('pagibig_payroll');
    Route::get('/pagibig_payroll_employees/{payroll_period_id}', 'PagibigPayrollHeaderController@loadEmployees')->name('pagibig_payroll_employees');
    Route::post('/pagibig_payroll_employees_store', 'PagibigPayrollHeaderController@store')->name('pagibig_payroll_employees_store');
    Route::get('/validate_pagibig_amount/{employee_id}/{amount}/{payroll_period_id}', 'PagibigPayrollHeaderController@validateAmount')->name('validate_pagibig_amount');
    //PagIbig Setup
    Route::get('/pag-ibig_setup', 'PagibigSetupController@index')->name('pagibig_setup');
    Route::get('/pag-ibig_setup_delete/{id}', 'PagibigSetupController@delete')->name('pagibig_setup_delete');
    Route::post('/pag-ibig_setup', 'PagibigSetupController@store')->name('pagibig_setup_store');
    Route::delete('/pag-ibig_setup_delete/{id}', 'PagibigSetupController@destroy')->name('pagibig_setup_destroy');

    // Control Pannel Routes
    // User Profile
    Route::get('/profile', 'ProfileController@index')->name('profile');
    Route::patch('/profile', 'ProfileController@update')->name('profile');
    Route::get('/profile_change_password', 'ProfileController@profilePassword')->name('profile_change_password');
    // User List
    Route::get('/list', 'UsersController@index')->name('list');
    Route::post('/list_add', 'UsersController@store')->name('list_add');
    // User List Access Rights
    Route::get('/access_rights/{id}', 'AccessRightsController@index')->name('access_rigths');
    Route::post('/access_rights/{id}', 'AccessRightsController@update_access')->name('access_rigths');
    // User Activiti List
    Route::get('/audits', 'AuditController@index')->name('audits');
    Route::get('/optimize/audits', 'AuditController@lazyGet')->name('lazyGet');

    // Department Setup
    Route::get('/department_list', 'DepartmentsController@index')->name('departments');
    Route::get('/department_add', 'DepartmentsController@add')->name('department_add');
    Route::get('/department_edit/{id}', 'DepartmentsController@edit')->name('department_edit');
    Route::post('/department_add', 'DepartmentsController@store');
    Route::patch('/department_edit/{id}', 'DepartmentsController@update');
    // Division Setup
    Route::get('/division_list', 'DivisionsController@index')->name('divisions');
    Route::get('/division_add', 'DivisionsController@add')->name('division_add');
    Route::get('/division_edit/{id}', 'DivisionsController@edit')->name('division_edit');
    Route::post('/division_add', 'DivisionsController@store');
    Route::patch('/division_edit/{id}', 'DivisionsController@update');
    // Section Setup
    Route::get('/section_list', 'SectionsController@index')->name('sections');
    Route::get('/Section_add', 'SectionsController@add')->name('section_add');
    Route::get('/section_edit/{id}', 'SectionsController@edit')->name('section_edit');
    Route::post('/section_add', 'SectionsController@store');
    Route::patch('/section_edit/{id}', 'SectionsController@update');
    // Position Setup
    Route::get('/position_list', 'PositionsController@index')->name('positions');
    Route::get('/position_add', 'PositionsController@add')->name('position_add');
    Route::get('/position_edit/{id}', 'PositionsController@edit')->name('position_edit');
    Route::post('/position_add', 'PositionsController@store');
    Route::patch('/position_edit/{id}', 'PositionsController@update');
    // Employment Type
    Route::get('/employment_type_list', 'EmploymentTypesController@index')->name('employments');
    Route::get('/employment_type_add', 'EmploymentTypesController@add')->name('employment_add');
    Route::get('/employment_type_edit/{id}', 'EmploymentTypesController@edit')->name('employment_edit');
    Route::post('/employment_type_add', 'EmploymentTypesController@store');
    Route::patch('/employment_type_edit/{id}', 'EmploymentTypesController@update');
    // Civil Status
    Route::get('/civil_status_list', 'CivilStatusController@index')->name('civil_status');
    Route::get('/civil_status_add', 'CivilStatusController@add')->name('civil_status_add');
    Route::get('/civil_status_edit/{id}', 'CivilStatusController@edit')->name('civil_status_edit');
    Route::post('/civil_status_add', 'CivilStatusController@store');
    Route::patch('/civil_status_edit/{id}', 'CivilStatusController@update');
    // Citizenship Setup
    Route::get('/citizenship_list', 'CitizenshipsController@index')->name('citizenships');
    Route::get('/citizenship_add', 'CitizenshipsController@add')->name('citizenship_add');
    Route::get('/citizenship_edit/{id}', 'CitizenshipsController@edit')->name('citizenship_edit');
    Route::post('/citizenship_add', 'CitizenshipsController@store');
    Route::patch('/citizenship_edit/{id}', 'CitizenshipsController@update');
    // Gender Setup
    Route::get('/gender_list', 'GendersController@index')->name('genders');
    Route::get('/gender_add', 'GendersController@add')->name('gender_add');
    Route::get('/gender_edit/{id}', 'GendersController@edit')->name('gender_edit');
    Route::post('/gender_add', 'GendersController@store');
    Route::patch('/gender_edit/{id}', 'GendersController@update');
    // Religion Setup
    Route::get('/religion_list', 'ReligionsController@index')->name('religions');
    Route::get('/religion_add', 'ReligionsController@add')->name('religion_add');
    Route::get('/religion_edit/{id}', 'ReligionsController@edit')->name('religion_edit');
    Route::post('/religion_add', 'ReligionsController@store');
    Route::patch('/religion_edit/{id}', 'ReligionsController@update');
    // Name Prefix
    Route::get('/name_prefix_list', 'NamePrefixController@index')->name('name_prefix');
    Route::get('/name_prefix_add', 'NamePrefixController@add')->name('name_prefix_add');
    Route::get('/name_prefix_edit/{id}', 'NamePrefixController@edit')->name('name_prefix_edit');
    Route::post('/name_prefix_add', 'NamePrefixController@store');
    Route::patch('/name_prefix_edit/{id}', 'NamePrefixController@update');
    Route::get('/name_prefix_delete/{id}', 'NamePrefixController@delete');
    // Name Suffix
    Route::get('/name_suffix_list', 'NameSuffixController@index')->name('name_suffix');
    Route::get('/name_suffix_add', 'NameSuffixController@add')->name('name_suffix_add');
    Route::get('/name_suffix_edit/{id}', 'NameSuffixController@edit')->name('name_suffix_edit');
    Route::post('/name_suffix_add', 'NameSuffixController@store');
    Route::patch('/name_suffix_edit/{id}', 'NameSuffixController@update');
    Route::get('/name_suffix_delete/{id}', 'NameSuffixController@delete');
    // Eligibility Setup
    Route::get('/eligibility_list', 'EligibilityController@index')->name('eligibilities');
    Route::get('/eligibility_add', 'EligibilityController@add')->name('eligibility_add');
    Route::get('/eligibility_edit/{id}', 'EligibilityController@edit')->name('eligibility_edit');
    Route::post('/eligibility_add', 'EligibilityController@store');
    Route::patch('/eligibility_edit/{id}', 'EligibilityController@update');
    // learning and Development Setup
    Route::get('/learning_list', 'LearningsController@index')->name('learnings');
    Route::get('/learning_add', 'LearningsController@add')->name('learning_add');
    Route::get('/learning_edit/{id}', 'LearningsController@edit')->name('learning_edit');
    Route::post('/learning_add', 'LearningsController@store');
    Route::patch('/learning_edit/{id}', 'LearningsController@update');
    // Plantilla Setup
    Route::get('/plantilla_list', 'PlantillasController@index')->name('plantillas');
    Route::get('/plantilla_add', 'PlantillasController@add')->name('plantilla_add');
    Route::get('/plantilla_edit/{id}', 'PlantillasController@edit')->name('plantilla_edit');
    Route::post('/plantilla_add', 'PlantillasController@store');
    Route::patch('/plantilla_edit/{id}', 'PlantillasController@update');
    Route::get('/plantilla_delete/{type_id}/{id}', 'PlantillasController@delete')->name('plantilla_delete');
    Route::delete('/plantilla_delete/{type_id}/{id}', 'PlantillasController@destroy')->name('plantilla_destroy');
    Route::get('/vacancies_list', 'VacanciesController@index')->name('vacancies');
    // Non Plantilla Setup
    Route::get('/non_plantilla_list', 'NonPlantillasController@index')->name('non_plantilla_list');
    Route::get('/non_plantilla_add/{id}', 'NonPlantillasController@add')->name('non_plantilla_add');
    Route::post('/non_plantilla_add/{id}', 'NonPlantillasController@store')->name('non_plantilla_add');
    // Salary Schedule Setup
    Route::get('/salary_schedule_list', 'SalarySchedulesController@index')->name('salary_schedules');
    Route::get('/salary_schedule_add', 'SalarySchedulesController@add')->name('salary_schedule_add');
    Route::get('/salary_schedule_edit/{id}', 'SalarySchedulesController@edit')->name('salary_schedule_edit');
    Route::get('/salary_schedule_delete/{id}', 'SalarySchedulesController@delete')->name('salary_schedule_delete');
    Route::post('/salary_schedule_add', 'SalarySchedulesController@store');
    Route::patch('/salary_schedule_edit/{id}', 'SalarySchedulesController@update');
    Route::delete('/salary_schedule_delete/{id}', 'SalarySchedulesController@destroy')->name('salary_schdule_destroy');
    // Tax Table Setup
    Route::get('/tax_table', 'TaxController@index')->name('tax_tables');
    Route::get('/tax_table_delete/{id}', 'TaxController@delete')->name('tax_table_delete');
    Route::post('/tax_table', 'TaxController@store')->name('tax_table_add');
    Route::delete('/tax_table_delete/{id}', 'TaxController@destroy')->name('tax_table_destroy');
    // PhilHealth Setup
    Route::get('/philhealth_table', 'PhilhealthController@index')->name('philhealth_tables');
    Route::get('/philhealth_table_delete/{id}', 'PhilhealthController@delete')->name('philhealth_table_delete');
    Route::post('/philhealth_table', 'PhilhealthController@store')->name('philhealth_table_add');
    Route::delete('/philhealth_table_delete/{id}', 'PhilhealthController@destroy')->name('philhealth_table_destroy');
    // GSIS Setup
    Route::get('/gsis_table', 'GSISController@index')->name('gsis_tables');
    Route::get('/gsis_table_delete/{id}', 'GSISController@delete')->name('gsis_table_delete');
    Route::post('/gsis_table', 'GSISController@store')->name('gsis_table_add');
    Route::delete('/gsis_table_delete/{id}', 'GSISController@destroy')->name('gsis_table_destroy');
    Route::get('/gsis_list', 'GSISController@index')->name('gsis_list');
    Route::get('/gsis_add/{id}', 'GSISController@add')->name('gsis_add');
    Route::post('/gsis_add/{id}', 'GSISController@storeGSIS')->name('gsis_add');
    // SSS Setup
    Route::get('/sss_table', 'SSSController@index')->name('sss_tables');
    Route::get('/sss_table_delete/{id}', 'SSSController@delete')->name('sss_table_delete');
    Route::post('/sss_table', 'SSSController@store')->name('sss_table_add');
    Route::delete('/sss_table_delete/{id}', 'SSSController@destroy')->name('sss_table_destroy');
    // Salary Step Table Setup
    Route::get('/salary_steps', 'SalaryStepController@index')->name('salary_steps');
    Route::get('/salary_step_delete/{id}', 'SalaryStepController@delete')->name('salary_step_delete');
    Route::post('/salary_steps', 'SalaryStepController@store')->name('salary_step_add');
    Route::delete('/salary_step_delete/{id}', 'SalaryStepController@destroy')->name('salary_step_destroy');
    // Salary Grade Table Setup
    Route::get('/salary_grades', 'SalaryGradeController@index')->name('salary_grades');
    Route::get('/salary_grade_delete/{id}', 'SalaryGradeController@delete')->name('salary_grade_delete');
    Route::post('/salary_grades', 'SalaryGradeController@store')->name('salary_grade_add');
    Route::delete('/salary_grade_delete/{id}', 'SalaryGradeController@destroy')->name('salary_grade_destroy');
    // Company Setup
    Route::get('/companies', 'CompanyController@index')->name('companies');
    Route::post('/companies', 'CompanyController@store')->name('company_add');
    // Branch Setup
    Route::get('/branch', 'BranchController@index')->name('branches');
    Route::get('/branch_delete/{id}', 'BranchController@delete')->name('branch_delete');
    Route::post('/branch', 'BranchController@store')->name('branch_add');
    Route::delete('/branch_delete/{id}', 'BranchController@destroy')->name('branch_destroy');
    // Blood Type Setup
    Route::get('/blood_types', 'BloodTypeController@index')->name('blood_types');
    Route::get('/blood_type_delete/{id}', 'BloodTypeController@delete')->name('blood_type_delete');
    Route::post('/blood_types', 'BloodTypeController@store')->name('blood_type_add');
    Route::delete('/blood_type_delete/{id}', 'BloodTypeController@destroy')->name('blood_type_destroy');
    // Payroll Interval Setup
    Route::get('/payroll_intervals', 'PayrollIntervalController@index')->name('payroll_intervals');
    Route::get('/payroll_interval_delete/{id}', 'PayrollIntervalController@delete')->name('payroll_interval_delete');
    Route::post('/payroll_intervals', 'PayrollIntervalController@store')->name('payroll_interval_add');
    Route::patch('/payroll_intervals/{id}/active', 'PayrollIntervalController@updateActive')->name('payroll_interval_update_active');
    Route::delete('/payroll_interval_delete/{id}', 'PayrollIntervalController@destroy')->name('payroll_interval_destroy');
    // Payroll Cut-off
    Route::get('/payroll_cutoffs', 'PayrollCutOffController@index')->name('payroll_cutoffs');
    Route::get('/payroll_cutoff_add/{id}', 'PayrollCutOffController@add')->name('payroll_cutoff_add');
    Route::post('/payroll_cutoff_add/{id}', 'PayrollCutOffController@store')->name('payroll_cutoff_add');
    // Promotion Type Setup
    Route::get('/promotion_type', 'PromotionTypeController@index')->name('promotion_types');
    Route::get('/promotion_type_delete/{id}', 'PromotionTypeController@delete')->name('promotion_type_delete');
    Route::post('/promotion_type', 'PromotionTypeController@store')->name('promotion_type_add');
    Route::delete('/promotion_type_delete/{id}', 'PromotionTypeController@destroy')->name('promotion_type_destroy');
    // Off-Boarding Type Setup
    Route::get('/offboarding_type', 'OffBoardingTypeController@index')->name('offboarding_types');
    Route::get('/offboarding_type_delete/{id}', 'OffBoardingTypeController@delete')->name('offboarding_type_delete');
    Route::post('/offboarding_type', 'OffBoardingTypeController@store')->name('offboarding_type_add');
    Route::delete('/offboarding_type_delete/{id}', 'OffBoardingTypeController@destroy')->name('offboarding_type_destroy');
    // Overtime Types
    Route::get('/overtime_type', 'OvertimeTypeController@index')->name('overtime_types');
    Route::get('/overtime_type_delete/{id}', 'OvertimeTypeController@delete')->name('overtime_type_delete');
    Route::post('/overtime_type', 'OvertimeTypeController@store')->name('overtime_type_add');
    Route::delete('/overtime_type_delete/{id}', 'OvertimeTypeController@destroy')->name('overtime_type_destroy');
    // Holiday Types
    Route::get('/holiday_type', 'HolidayTypeController@index')->name('holiday_types');
    Route::get('/holiday_type_delete/{id}', 'HolidayTypeController@delete')->name('holiday_type_delete');
    Route::post('/holiday_type', 'HolidayTypeController@store')->name('holiday_type_add');
    Route::delete('/holiday_type_delete/{id}', 'HolidayTypeController@destroy')->name('holiday_type_destroy');
    // Holidays
    Route::get('/holiday', 'HolidayController@index')->name('holidays');
    Route::get('/holiday_delete/{id}', 'HolidayController@delete')->name('holiday_delete');
    Route::post('/holiday', 'HolidayController@store')->name('holiday_add');
    Route::delete('/holiday_delete/{id}', 'HolidayController@destroy')->name('holiday_destroy');
    // Leave Types
    Route::get('/leave_type', 'LeaveTypeController@index')->name('leave_types');
    Route::get('/leave_type_delete/{id}', 'LeaveTypeController@delete')->name('leave_type_delete');
    Route::post('/leave_type', 'LeaveTypeController@store')->name('leave_type_add');
    Route::delete('/leave_type_delete/{id}', 'LeaveTypeController@destroy')->name('leave_type_destroy');
    // Official Business Types
    Route::get('/official_business_type', 'OfficialBusinessTypeController@index')->name('official_business_types');
    Route::get('/official_business_type_delete/{id}', 'OfficialBusinessTypeController@delete')->name('official_business_type_delete');
    Route::post('/official_business_type', 'OfficialBusinessTypeController@store')->name('official_business_type_add');
    Route::delete('/official_business_type_delete/{id}', 'OfficialBusinessTypeController@destroy')->name('official_business_type_destroy');
    // Holiday Taggings
    Route::get('/holiday_tagging', 'HolidayTaggingController@index')->name('holiday_taggings');
    Route::get('/holiday_tagging_delete/{id}/{holiday_id}', 'HolidayTaggingController@delete')->name('holiday_tagging_delete');
    Route::post('/holiday_tagging_add', 'HolidayTaggingController@store')->name('holiday_tagging_add');
    Route::delete('/holiday_tagging_destroy/{id}/{holiday_id}', 'HolidayTaggingController@destroy')->name('holiday_tagging_destroy');
    // TimeKeeping Setup
    Route::get('/time_keeping_setups', 'TimeKeepingSetupController@index')->name('time_keeping_setups');
    Route::get('/time_keeping_setup_data/{id}', 'TimeKeepingSetupController@getdata')->name('time_keeping_setup_data');
    Route::post('/time_keeping_setup_update', 'TimeKeepingSetupController@store')->name('time_keeping_setup_update');
    // Deduction Types Setup
    Route::get('/deduction_type', 'DeductionController@index')->name('deduction_type');
    // Route::get('/deduction_type_delete/{id}', 'DeductionController@delete')->name('deduction_type_delete');
    Route::post('/deduction_type_add', 'DeductionController@store')->name('deduction_type_add');
    Route::delete('/deduction_type_delete/{id}', 'DeductionController@destroy')->name('deduction_type_delete');
    // Income Types Setup
    Route::get('/income_types', 'IncomeController@index')->name('income_types');
    Route::post('/income_type_add', 'IncomeController@store')->name('income_type_add');
    Route::delete('/income_type_delete/{id}', 'IncomeController@destroy')->name('income_type_delete');
    // Deduction Priority Setup
    Route::get('/deduction_priorities', 'DeductionPriorityController@index')->name('deduction_priorities');
    Route::post('/deduction_priorities_add', 'DeductionPriorityController@store')->name('deduction_priorities_add');
    // Adjectival Ratings
    Route::get('/rating', 'RatingController@index')->name('rating');
    Route::post('/rating_add', 'RatingController@store')->name('rating_add');
    Route::delete('/rating_delete/{id}', 'RatingController@destroy')->name('rating_delete');
    // Semester Ratings
    Route::get('/semester_rating_list', 'SemesterRatingController@index')->name('semester_ratings');
    Route::get('/semester_rating_add', 'SemesterRatingController@add')->name('semester_rating_add');
    Route::get('/semester_rating_edit/{id}', 'SemesterRatingController@edit')->name('semester_rating_edit');
    Route::post('/semester_rating_add', 'SemesterRatingController@store');
    Route::patch('/semester_rating_edit/{id}', 'SemesterRatingController@update');
    // Leave Approvers
    Route::get('/leave_approver', 'LeaveApproverController@index')->name('leave_approver');
    Route::get('/leave_approver_add/{id}', 'LeaveApproverController@add')->name('leave_approver_add');
    Route::post('/leave_approver_add/{id}', 'LeaveApproverController@store')->name('leave_approver_add');
    Route::get('/leave_subordinates/{department_id}/{id}/{type_id}', 'LeaveApproverController@subordinates')->name('leave_subordinates');
    Route::post('/leave_subordinates_add/{id}/{type_id}', 'LeaveApproverController@addSubordinates')->name('leave_subordinates_add');
    Route::post('/leave_subordinates_delete/{id}', 'LeaveApproverController@deleteSubordinates')->name('leave_subordinates_delete');
    Route::get('/leave_approver_department/{id}', 'LeaveApproverController@getDepartments')->name('leave_approver_department');
    Route::get('/leave_approver_division/{id}', 'LeaveApproverController@getDivisions')->name('leave_approver_division');
    Route::get('/leave_approver_section/{id}', 'LeaveApproverController@getSections')->name('leave_approver_section');
    // Document Number Setup
    Route::get('/document_number_setup', 'DocumentNumberController@index')->name('document_number_setup');
    Route::post('/document_number_add', 'DocumentNumberController@store')->name('document_number_add');
    // Competencies Setup
    Route::get('/competencies_list', 'CompetenciesController@index')->name('competencies');
    Route::get('/competencies_add', 'CompetenciesController@add')->name('competencies_add');
    Route::get('/competencies_edit/{id}', 'CompetenciesController@edit')->name('competencies_edit');
    Route::post('/competencies_add', 'CompetenciesController@store');
    Route::get('/competencies_delete/{type_id}/{id}', 'CompetenciesController@delete')->name('competencies_delete');
    Route::delete('/competencies_delete/{type_id}/{id}', 'CompetenciesController@destroy')->name('competencies_destroy');
    Route::patch('/competencies_edit/{id}', 'CompetenciesController@update');
    // Loyalty Award
    Route::get('/loyalty_award', 'LoyaltyAwardController@index')->name('loyalty_award');
    Route::get('/loyalty_award_add/{id}', 'LoyaltyAwardController@add')->name('loyalty_award_add');
    Route::post('/loyalty_award_add/{id}', 'LoyaltyAwardController@store')->name('loyalty_award_add');
    Route::post('/loyalty_award_post/{id}', 'LoyaltyAwardController@post')->name('loyalty_award_post');
    Route::get('/loyalty_award_unpost/{id}', 'LoyaltyAwardController@unpost')->name('loyalty_award_unpost');
    // Loyalty Award Setup
    Route::get('/loyalty_award_setup', 'LoyaltyAwardSetupController@index')->name('loyalty_award_setup');
    Route::get('/loyalty_award_setup_delete/{id}', 'LoyaltyAwardSetupController@delete')->name('loyalty_award_setup_delete');
    Route::post('/loyalty_award_setup_add', 'LoyaltyAwardSetupController@store')->name('loyalty_award_setup_add');
    Route::delete('/loyalty_award_setup_delete/{id}', 'LoyaltyAwardSetupController@destroy')->name('loyalty_award_setup_destroy');
    // Uniform Clothing Setup
    Route::get('/uniform_clothing_setup', 'UniformClothingSetupController@index')->name('uniform_clothing_setup');
    Route::get('/uniform_clothing_setup_delete/{id}', 'UniformClothingSetupController@delete')->name('uniform_clothing_setup_delete');
    Route::post('/uniform_clothing_setup_add', 'UniformClothingSetupController@store')->name('uniform_clothing_setup_add');
    Route::delete('/uniform_clothing_setup_delete/{id}', 'UniformClothingSetupController@destroy')->name('uniform_clothing_setup_destroy');
    // Uniform Clothing
    Route::get('/uniform_clothing', 'UniformClothingController@index')->name('uniform_clothing');
    Route::get('/uniform_clothing_add/{id}', 'UniformClothingController@add')->name('uniform_clothing_add');
    Route::post('/uniform_clothing_add/{id}', 'UniformClothingController@store')->name('uniform_clothing_add');
    Route::post('/uniform_clothing_post/{id}', 'UniformClothingController@post')->name('uniform_clothing_post');
    Route::get('/uniform_clothing_unpost/{id}', 'UniformClothingController@unpost')->name('uniform_clothing_unpost');
    Route::post('/uniform_clothing_employee_add/{id}', 'UniformClothingController@addEmployee')->name('uniform_clothing_employee_add');
    Route::get('/uniform_clothing_details_destroy/{id}', 'UniformClothingController@delete')->name('uniform_clothing_details_destroy');
    Route::delete('/uniform_clothing_details_delete/{id}', 'UniformClothingController@destroy')->name('uniform_clothing_details_delete');
    //RATA Positions Setup
    Route::get('/rata_positions', 'RATAController@index')->name('rata_positions');
    Route::post('/rata_positions', 'RATAController@store')->name('rata_positions');
    // RATA Table Setup
    Route::get('/rata_table', 'RATAController@loadRATATable')->name('rata_table');
    Route::post('/rata_table', 'RATAController@storeRATATable')->name('rata_table');
    Route::get('/rata_table_delete/{id}', 'RATAController@deleteRATATable')->name('rata_table_delete');
    Route::get('/rata_payroll', 'RATAController@loadRATAPayroll')->name('rata_payroll');
    Route::get('/rata_payroll_add/{id}', 'RATAController@addRATAPayroll')->name('rata_payroll_add');
    Route::post('/rata_payroll_add/{id}', 'RATAController@storeRATAPayroll')->name('rata_payroll_add');
    Route::post('/rata_payroll_add_employees/{id}', 'RATAController@storeRATAPayrollEmployees')->name('rata_payroll_add_employees');
    Route::get('/rata_payroll_delete_employees/{id}', 'RATAController@deleteRATAPayrollEmployees')->name('rata_payroll_delete_employees');
    Route::post('/rata_payroll_process/{id}/{type_id}', 'RATAController@processRATAPayroll')->name('rata_payroll_process');
    Route::post('/rata_payroll_update/{id}', 'RATAController@updateRATAPayroll')->name('rata_payroll_update');

    // Hazard Pay Setup
    Route::get('/hazard_pay', 'HazardPayController@index')->name('hazard_pay_table');
    Route::post('/hazard_pay', 'HazardPayController@store')->name('hazard_pay_table');
    Route::get('/hazard_pay_delete/{id}', 'HazardPayController@delete')->name('hazard_pay_delete');
    Route::delete('/hazard_pay_delete/{id}', 'HazardPayController@destroy')->name('hazard_pay_destroy');
    Route::get('/hazard_pay_list', 'HazardPayController@loadHazard')->name('hazard_list');
    Route::get('/hazard_pay_add/{id}', 'HazardPayController@addHazard')->name('hazard_pay_add');
    Route::post('/hazard_pay_add/{id}', 'HazardPayController@storeHazard')->name('hazard_pay_add');
    Route::post('/hazard_pay_add_employees/{id}', 'HazardPayController@storeHazardEmployees')->name('hazard_pay_add_employees');
    Route::get('/hazard_pay_delete_employees/{id}', 'HazardPayController@deleteHazardEmployees')->name('hazard_pay_delete_employees');
    Route::post('/hazard_pay_process/{id}/{type_id}', 'HazardPayController@processHazard')->name('hazard_pay_process');

    // OT Tax Table
    Route::get('/overtime_tax_table', 'OvertimeTaxController@index')->name('overtime_tax_table');
    Route::post('/overtime_tax_table', 'OvertimeTaxController@store')->name('overtime_tax_table');
    Route::get('/overtime_tax_table_delete/{id}', 'OvertimeTaxController@delete')->name('overtime_tax_table_delete');
    Route::get('/overtime_tax_table_load/{year}', 'OvertimeTaxController@load')->name('overtime_tax_table_load');
    // Mid Year Table Setup
    Route::get('/midyear_table', 'MidYearController@index')->name('midyear_tables');
    Route::get('/midyear_table_delete/{id}', 'MidYearController@delete')->name('midyear_table_delete');
    Route::post('/midyear_table', 'MidYearController@store')->name('midyear_table_add');
    Route::delete('/midyear_table_delete/{id}', 'MidYearController@destroy')->name('midyear_table_destroy');
    // Year End Table Setup
    Route::get('/yearend_table', 'YearEndController@index')->name('yearend_tables');
    Route::get('/yearend_table_delete/{id}', 'YearEndController@delete')->name('yearend_table_delete');
    Route::post('/yearend_table', 'YearEndController@store')->name('yearend_table_add');
    Route::delete('/yearend_table_delete/{id}', 'YearEndController@destroy')->name('yearend_table_destroy');
    // Cash gift Table Setup
    Route::get('/cashgift_table', 'CashGiftController@index')->name('cashgift_tables');
    Route::get('/cashgift_table_delete/{id}', 'CashGiftController@delete')->name('cashgift_table_delete');
    Route::post('/cashgift_table', 'CashGiftController@store')->name('cashgift_table_add');
    Route::delete('/cashgift_table_delete/{id}', 'CashGiftController@destroy')->name('cashgift_table_destroy');
    // Document Type Setup
    Route::get('/document_type', 'DocumentTypeController@index')->name('document_type_setup');
    Route::post('/document_type', 'DocumentTypeController@store')->name('document_type_store');
    Route::get('/document_delete/{id}', 'DocumentTypeController@delete')->name('document_type_delete');
    Route::delete('/document_delete/{id}', 'DocumentTypeController@destroy')->name('document_type_destroy');

    // Loyalty Award Report
    Route::get('/loyalty_award_report', 'LoyaltyAwardController@loyalty_award_report')->name('loyalty_award_report');
    Route::post('/loyalty_award_report_print', 'LoyaltyAwardController@print')->name('loyalty_award_report_print');

    // Payroll Communication Macco
    Route::get('/reimbursement_report', 'ReimbursementCommunicationExpensesController@index')->name('reimbursement_report');
    Route::get('/reimbursement_set_report', 'ReimbursementCommunicationExpensesController@report')->name('reimbursement_set_report');
    Route::post('/reimbursement_report_print', 'ReimbursementCommunicationExpensesController@print')->name('reimbursement_report_print');
    Route::get('/reimbursement_add/{id}', 'ReimbursementCommunicationExpensesController@add')->name('reimbursement_add');
    Route::post('/reimbursement_add/{id}', 'ReimbursementCommunicationExpensesController@store')->name('reimbursement_add');
    Route::post('/reimbursement_employee_add/{id}', 'ReimbursementCommunicationExpensesController@addEmployee')->name('reimbursement_employee_add');
    Route::get('/reimbursement_employee_delete/{id}', 'ReimbursementCommunicationExpensesController@deleteEmployee')->name('reimbursement_employee_delete');
    Route::post('/reimbursement_process/{id}/{type_id}', 'ReimbursementCommunicationExpensesController@process')->name('reimbursement_process');
    // Uniform Clothing Allowance Report
    Route::get('/uniform_clothing_allowance_report', 'UniformClothingController@uniform_clothing_allowance_report')->name('uniform_clothing_allowance_report');
    Route::post('/uniform_clothing_allowance_report_print', 'UniformClothingController@print')->name('uniform_clothing_allowance_report_print');

    // Monetization Setup
    Route::get('/monetization_setups', 'MonetizationController@index')->name('monetization_setups');
    Route::post('/monetization_setups_add/{id}', 'MonetizationController@store')->name('monetization_setups_add');

    // EETE Rating Setup
    Route::get('/eete_rating_setup', 'EETEController@index')->name('eete_rating_setup');
    Route::post('/eete_rating/{id}', 'EETEController@store')->name('eete_rating');

    // Exam Category Setup
    Route::get('/exam_category_setup', 'ExamCategoryController@index')->name('exam_category_setup');
    Route::get('/exam_category_add/{id}', 'ExamCategoryController@add')->name('exam_category_add');
    Route::post('/exam_category_add/{id}', 'ExamCategoryController@store')->name('exam_category_add');
    Route::delete('/exam_category_delete/{id}', 'ExamCategoryController@destroy')->name('exam_category_delete');
    Route::get('/exam_sub_category_delete/{id}', 'ExamCategoryController@deleteSubCategory')->name('exam_sub_category_delete');
    Route::get('/exam_sub_categories_positions/{id}', 'ExamCategoryController@subCategoryPositions')->name('exam_sub_categories_positions');
    Route::post('/exam_sub_categories_positions_add/{id}', 'ExamCategoryController@addPosition')->name('exam_sub_categories_positions_add');
    Route::get('/exam_sub_categories_questions/{id}', 'ExamCategoryController@subCategoryQuestions')->name('exam_sub_categories_questions');
    Route::post('/exam_sub_categories_questions_add/{id}/{question_id}', 'ExamCategoryController@addQuestions')->name('exam_sub_categories_questions_add');
    Route::get('/exam_sub_categories_questions_delete/{id}', 'ExamCategoryController@deleteQuestion')->name('exam_sub_categories_questions_delete');
    Route::get('/exam_sub_categories_choice_delete/{id}', 'ExamCategoryController@deleteChoice')->name('exam_sub_categories_choice_delete');
    Route::get('/exam_sub_categories_choice_get_delete/{id}', 'ExamCategoryController@getDeleteChoice')->name('exam_sub_categories_choice_get_delete');

    // Migrations
    Route::get('/migrate_branches', 'MigrateBranchController@index')->name('migrate_branches');
    Route::post('/import_branches', 'MigrateBranchController@import')->name('import_branches');

    Route::get('/migrate_offices', 'MigrateOfficeController@index')->name('migrate_offices');
    Route::post('/import_offices', 'MigrateOfficeController@import')->name('import_offices');

    Route::get('/migrate_divisions', 'MigrateDivisionController@index')->name('migrate_divisions');
    Route::post('/import_divisions', 'MigrateDivisionController@import')->name('import_divisions');

    Route::get('/migrate_sections', 'MigrateSectionController@index')->name('migrate_sections');
    Route::post('/import_sections', 'MigrateSectionController@import')->name('import_sections');

    Route::get('/migrate_employment_types', 'MigrateEmploymentTypeController@index')->name('migrate_employment_types');
    Route::post('/import_employment_types', 'MigrateEmploymentTypeController@import')->name('import_employment_types');
    Route::get('/migration_template/{template}', 'MigrationTemplateController@download')->name('migration_template');

    // Global Controllers
    Route::get('/get_plantilla/{id}', 'GlobalController@getPlantilla');
    Route::get('/get_salary/{amount}', 'GlobalController@getSalary');
    Route::get('/get_address/{id}', 'GlobalController@getAddress');
    Route::get('/get_address_temp/{request_id}', 'GlobalController@getAddress_temp');
    Route::get('/get_promotion/{id}', 'GlobalController@getEmployeePromotion');
    Route::get('/get_employee_plantilla/{id}', 'GlobalController@getEmployeePlantilla');
    Route::get('/get_assign_schedule/{id}', 'GlobalController@getAssignSchedule');
    Route::get('/get_holiday/{id}/{header_id}', 'GlobalController@getHolidays');
    Route::get('/get_holiday_details/{holiday_type_id}/{branch_id}/{year}', 'GlobalController@getHolidayTaggingDetails');
    Route::get('/get_employee_step/{id}', 'GlobalController@getEmployeeStep');
    Route::get('/get_plantilla_step/{id}/{step_id}', 'GlobalController@getPlantillaStep');
    Route::get('/get_plantilla_step_id/{id}/{step_id}', 'GlobalController@getPlantillaStepID');
    Route::get('/get_employees_leave_credits/{id}/{user_id}', 'GlobalController@getEmployeeForLeaveCredits');
    Route::get('/get_payroll_cutoff/{id}', 'GlobalController@getPayrollCutoff');
    Route::get('/get_payroll_period/{id}', 'GlobalController@getPayrollPeriod');
    Route::get('/get_payroll_period_posted/{id}', 'GlobalController@getPayrollPeriodPosted');
    Route::get('/get_overtime_payroll_period/{id}/{overtime_payroll_id}', 'GlobalController@getOvertimePayrollPeriod');
    Route::get('/get_overtime_payroll_period_print/{id}', 'GlobalController@getOvertimePayrollPeriodPrint');
    Route::get('/get_branches_payroll/{id}', 'GlobalController@getBranchesPayroll');
    Route::get('/get_departments/{id}/{payroll_period_id}', 'GlobalController@getDepartments');
    Route::get('/get_deductions', 'GlobalController@getDeductions');
    Route::get('/get_incomes', 'GlobalController@getIncomes');
    Route::get('/get_time_data/{id}', 'GlobalController@getTimeData');
    Route::get('/get_time_data_offset/{id}', 'GlobalController@getTimeDataOffset');
    Route::get('/get_time_data_offset_details/{id}/{employee_id}', 'GlobalController@getTimeDataOffset_details');
    Route::get('/get_employee_departments/{id}/{payroll_period_id}', 'GlobalController@getEmployeeDepartments');
    Route::get('/get_unavailable_dates/{id}', 'GlobalController@getUnavailableDates');
    Route::get('/get_document_number/{key}', 'GlobalController@getDocumentNumbers');
    Route::get('/get_unavailable_dates_ot/{id}', 'GlobalController@getUnavailableDates_OT');
    Route::get('/get_loan_filter', 'GlobalController@getLoanApplicationFilter');
    Route::get('/get_approvers/{id}/{type_id}/{approver_id}', 'GlobalController@getApprovers');
    Route::get('/get_approvers_data/{id}', 'GlobalController@getApproversData');
    Route::get('/get_coc_details', 'GlobalController@getCOCdetails');
    Route::get('/get_branch_employees/{branch_id}', 'GlobalController@getBranchEmployee');
    Route::get('/get_loyalty_award_signatory/{branch_id}', 'GlobalController@get_loyalty_award_signatory');
    Route::get('/get_uniform_clothing_allowance_signatory/{branch_id}', 'GlobalController@get_uniform_clothing_allowance_signatory');
    Route::get('/get_midyear_bonus/{years}/{branch_id}', 'GlobalController@getMidYearBonus');
    Route::get('/get_yearend_bonus/{years}/{branch_id}', 'GlobalController@getYearEndBonus');
    Route::get('/get_midyear_signatory/{branch_id}', 'GlobalController@getMidYearSignatory');
    Route::get('/get_yearend_signatory/{branch_id}', 'GlobalController@getYearEndSignatory');
    Route::get('/get_emloyees_without_schedule', 'GlobalController@getEmployeesWithoutSchedule');
    Route::get('/get_emloyees_without_payroll', 'GlobalController@getEmployeesWithoutPayrollID');
    Route::get('/get_leave_taken/{id}/{year}', 'GlobalController@getEmployeeLeave');
    Route::get('/get_employee_incomes/{id}', 'GlobalController@getEmployeeIncomes');
    Route::get('/get_employee_bonus/{id}', 'GlobalController@getEmployeeBonus');
    Route::get('/get_employee_leave_earned/{id}', 'GlobalController@getEmployeeLeaveEarned');
    Route::get('/loyalty_award_employee/{month_id}/{branch_id}/{year}', 'LoyaltyAwardController@loyalty_award_employee')->name('loyalty_award_employee');
    Route::get('/get_subcompetencies/{id}', 'GlobalController@getSubCompetencies');
    Route::get('/get_training_employee_list/{id}/{position_id}', 'GlobalController@getTrainingEmployeeList');
    Route::get('/get_training_employee_list_all/{position_id}', 'GlobalController@getTrainingEmployeeListAll');
    Route::get('/get_departments_req/{id}', 'GlobalController@getDepartmentsReq');
    Route::get('/get_leave_is_maximum_availment/{leave_type_id}', 'GlobalController@getLeaveIsMaximumAvailment');
});
