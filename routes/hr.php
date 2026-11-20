<?php

use App\Http\Controllers\Admin\HR\EmployeeController;
use App\Http\Controllers\Admin\HR\HrComplianceController;
use App\Http\Controllers\Admin\HR\HrDashboardController;
use App\Http\Controllers\Admin\HR\HrPolicyController;
use App\Http\Controllers\Admin\HR\RecruitmentController;
use App\Http\Controllers\Admin\HR\RecruitmentInterviewController;
use App\Http\Controllers\Public\CareersController;
use App\Http\Controllers\Staff\InterviewPanelController;
use App\Http\Controllers\Staff\MyHrController;
use Illuminate\Support\Facades\Route;

/*
| HR operations: recruitment (careers page, embeddable job widget, AI screening,
| interviews with staff panels), employee directory, policies and staff documents.
*/

// Public careers page and the widget other websites embed (framing allowed in SecurityHeaders).
Route::prefix('careers')->name('careers.')->group(function () {
    Route::get('/', [CareersController::class, 'index'])->name('index');
    Route::get('/embed.js', [CareersController::class, 'script'])->name('embed.script');
    Route::get('/embed', [CareersController::class, 'embed'])->name('embed');
    Route::get('/embed/{slug}', [CareersController::class, 'embedShow'])->name('embed.show');
    // CSRF-exempt (bootstrap/app.php): third-party iframes cannot carry the session cookie.
    Route::post('/{slug}/apply', [CareersController::class, 'apply'])->middleware('throttle:8,1')->name('apply');
    Route::get('/{slug}', [CareersController::class, 'show'])->name('show');
});

Route::prefix('admin/hr')->name('admin.hr.')->middleware(['auth', 'staff'])->group(function () {
    Route::get('/', HrDashboardController::class)->middleware('permission:hr.view')->name('dashboard');

    // Employee directory
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->whereNumber('employee')->middleware('permission:hr.view')->name('employees.show');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->whereNumber('employee')->middleware('permission:hr.manage')->name('employees.update');

    // Recruitment
    Route::middleware('permission:hr.view')->group(function () {
        Route::get('/recruitment', [RecruitmentController::class, 'index'])->name('recruitment.index');
        Route::get('/recruitment/{vacancy}', [RecruitmentController::class, 'show'])->whereNumber('vacancy')->name('recruitment.show');
        Route::get('/recruitment/applicants/{applicant}', [RecruitmentController::class, 'applicant'])->name('recruitment.applicant');
        Route::get('/recruitment/applicants/{applicant}/cv', [RecruitmentController::class, 'cv'])->name('recruitment.applicant.cv');
    });
    Route::middleware('permission:hr.manage')->group(function () {
        Route::post('/recruitment', [RecruitmentController::class, 'store'])->name('recruitment.store');
        Route::put('/recruitment/{vacancy}', [RecruitmentController::class, 'update'])->name('recruitment.update');
        Route::delete('/recruitment/{vacancy}', [RecruitmentController::class, 'destroy'])->name('recruitment.destroy');
        Route::post('/recruitment/{vacancy}/screen', [RecruitmentController::class, 'screen'])->middleware('throttle:10,1')->name('recruitment.screen');
        Route::post('/recruitment/{vacancy}/shortlist-top', [RecruitmentController::class, 'shortlistTop'])->name('recruitment.shortlist-top');
        Route::post('/recruitment/{vacancy}/bulk', [RecruitmentController::class, 'bulk'])->name('recruitment.bulk');
        Route::post('/recruitment/{vacancy}/questions', [RecruitmentController::class, 'storeQuestion'])->name('recruitment.questions.store');
        Route::post('/recruitment/{vacancy}/questions/suggest', [RecruitmentController::class, 'suggestQuestions'])->middleware('throttle:5,1')->name('recruitment.questions.suggest');
        Route::put('/recruitment/questions/{question}', [RecruitmentController::class, 'updateQuestion'])->name('recruitment.questions.update');
        Route::delete('/recruitment/questions/{question}', [RecruitmentController::class, 'destroyQuestion'])->name('recruitment.questions.destroy');
        Route::put('/recruitment/applicants/{applicant}', [RecruitmentController::class, 'updateApplicant'])->name('recruitment.applicant.update');
        Route::post('/recruitment/applicants/{applicant}/screen', [RecruitmentController::class, 'screenOne'])->middleware('throttle:20,1')->name('recruitment.applicant.screen');
        Route::post('/recruitment/{vacancy}/interviews', [RecruitmentInterviewController::class, 'store'])->name('recruitment.interviews.store');
        Route::put('/recruitment/interviews/{interview}', [RecruitmentInterviewController::class, 'update'])->name('recruitment.interviews.update');
        Route::post('/recruitment/interviews/{interview}/remind', [RecruitmentInterviewController::class, 'remind'])->name('recruitment.interviews.remind');
    });

    // Policies
    Route::get('/policies', [HrPolicyController::class, 'index'])->middleware('permission:hr.view')->name('policies.index');
    Route::get('/policies/{policy}', [HrPolicyController::class, 'show'])->middleware('permission:hr.view')->name('policies.show');
    Route::get('/policies/{policy}/file', [HrPolicyController::class, 'file'])->middleware('permission:hr.view')->name('policies.file');
    Route::middleware('permission:hr.manage')->group(function () {
        Route::post('/policies', [HrPolicyController::class, 'store'])->name('policies.store');
        Route::put('/policies/{policy}', [HrPolicyController::class, 'update'])->name('policies.update');
        Route::delete('/policies/{policy}', [HrPolicyController::class, 'destroy'])->name('policies.destroy');
        Route::post('/policies/{policy}/assign', [HrPolicyController::class, 'assign'])->name('policies.assign');
        Route::post('/policies/{policy}/remind', [HrPolicyController::class, 'remind'])->name('policies.remind');
    });

    // Staff documents & compliance
    Route::get('/compliance', [HrComplianceController::class, 'index'])->middleware('permission:hr.view')->name('compliance.index');
    Route::get('/compliance/documents/{document}', [HrComplianceController::class, 'download'])->middleware('permission:hr.view')->name('compliance.documents.download');
    Route::middleware('permission:hr.manage')->group(function () {
        Route::post('/compliance/remind', [HrComplianceController::class, 'remind'])->middleware('throttle:10,1')->name('compliance.remind');
        Route::post('/compliance/requirements', [HrComplianceController::class, 'storeRequirement'])->name('compliance.requirements.store');
        Route::put('/compliance/requirements/{requirement}', [HrComplianceController::class, 'updateRequirement'])->name('compliance.requirements.update');
        Route::delete('/compliance/requirements/{requirement}', [HrComplianceController::class, 'destroyRequirement'])->name('compliance.requirements.destroy');
        Route::post('/compliance/documents/{document}/review', [HrComplianceController::class, 'review'])->name('compliance.documents.review');
        Route::post('/compliance/users/{user}/documents', [HrComplianceController::class, 'upload'])->name('compliance.documents.upload');
    });
});

// Staff self-service: My HR (policies + documents) and interview panels.
Route::middleware(['auth', 'staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/hr', [MyHrController::class, 'index'])->name('hr.index');
    Route::get('/hr/policies/{policy}', [MyHrController::class, 'policy'])->name('hr.policies.show');
    Route::get('/hr/policies/{policy}/file', [MyHrController::class, 'file'])->name('hr.policies.file');
    Route::post('/hr/policies/{policy}/acknowledge', [MyHrController::class, 'acknowledge'])->name('hr.policies.acknowledge');
    Route::post('/hr/documents', [MyHrController::class, 'upload'])->middleware('throttle:20,1')->name('hr.documents.store');
    Route::get('/hr/documents/{document}', [MyHrController::class, 'download'])->name('hr.documents.download');
    Route::delete('/hr/documents/{document}', [MyHrController::class, 'destroy'])->name('hr.documents.destroy');

    Route::get('/interviews', [InterviewPanelController::class, 'index'])->name('interviews.index');
    Route::get('/interviews/{interview}', [InterviewPanelController::class, 'show'])->name('interviews.show');
    Route::post('/interviews/{interview}/scores', [InterviewPanelController::class, 'score'])->name('interviews.score');
    Route::get('/interviews/{interview}/cv', [InterviewPanelController::class, 'cv'])->name('interviews.cv');
});
