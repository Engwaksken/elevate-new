<?php

use App\Http\Controllers\Admin\DataImportCentreController;
use App\Http\Controllers\Admin\Reports\TrackingReportsController;
use App\Http\Controllers\Admin\WorkspaceController;
use App\Http\Controllers\HR\AppraisalWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/staff/performance', [AppraisalWorkspaceController::class, 'index'])
        ->name('staff.performance.index');

    Route::get('/staff/performance/{appraisal}', [AppraisalWorkspaceController::class, 'show'])
        ->name('staff.performance.show');

    Route::put('/staff/performance/{appraisal}/employee', [AppraisalWorkspaceController::class, 'saveEmployee'])
        ->name('staff.performance.employee.save');

    Route::post('/staff/performance/{appraisal}/submit', [AppraisalWorkspaceController::class, 'submitEmployee'])
        ->name('staff.performance.employee.submit');

    Route::put('/staff/performance/{appraisal}/supervisor', [AppraisalWorkspaceController::class, 'saveSupervisor'])
        ->name('staff.performance.supervisor.save');

    Route::post('/staff/performance/{appraisal}/return', [AppraisalWorkspaceController::class, 'returnForRevision'])
        ->name('staff.performance.return');

    Route::post('/staff/performance/{appraisal}/review-complete', [AppraisalWorkspaceController::class, 'reviewComplete'])
        ->name('staff.performance.review.complete');

    Route::put('/staff/performance/{appraisal}/meeting', [AppraisalWorkspaceController::class, 'saveMeeting'])
        ->name('staff.performance.meeting.save');

    Route::post('/staff/performance/{appraisal}/employee-confirm', [AppraisalWorkspaceController::class, 'employeeConfirm'])
        ->name('staff.performance.employee.confirm');

    Route::post('/staff/performance/{appraisal}/supervisor-confirm', [AppraisalWorkspaceController::class, 'supervisorConfirm'])
        ->name('staff.performance.supervisor.confirm');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'staff'])
    ->group(function () {
        Route::get('/workspace/learning', [WorkspaceController::class, 'learning'])->name('workspace.learning');
        Route::get('/workspace/planning-meal', [WorkspaceController::class, 'planningMeal'])->name('workspace.planning-meal');
        Route::get('/workspace/mentorship', [WorkspaceController::class, 'mentorship'])->name('workspace.mentorship');
        Route::get('/workspace/jobs', [WorkspaceController::class, 'jobs'])->name('workspace.jobs');
        Route::get('/workspace/reports', [WorkspaceController::class, 'reports'])->name('workspace.reports');

        Route::get('/import-centre', [DataImportCentreController::class, 'index'])->name('import-centre.index');
        Route::get('/import-centre/template/{m}', [DataImportCentreController::class, 'template'])->name('import-centre.template');
        Route::post('/import-centre/upload', [DataImportCentreController::class, 'upload'])->name('import-centre.upload');
        Route::get('/import-centre/{dataImport}/preview', [DataImportCentreController::class, 'preview'])->name('import-centre.preview');

        Route::get('/reports/mentorship-tracking', [TrackingReportsController::class, 'mentorship'])
            ->name('reports.mentorship-tracking');

        Route::get('/reports/jobs-tracking', [TrackingReportsController::class, 'jobs'])
            ->name('reports.jobs-tracking');
    });

/*
|--------------------------------------------------------------------------
| Staff tasks
|--------------------------------------------------------------------------
|
| Personal daily / weekly / past task tracking for staff, linked to the KPIs
| in their appraisal. Supervisors can assign to and follow their team.
|
*/

Route::middleware(['auth', 'staff'])
    ->prefix('staff/tasks')
    ->name('staff.tasks.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\StaffTaskController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Staff\StaffTaskController::class, 'store'])->name('store');
        Route::post('/move-pending', [\App\Http\Controllers\Staff\StaffTaskController::class, 'moveAllPending'])->name('move-pending');
        Route::post('/{task}/move', [\App\Http\Controllers\Staff\StaffTaskController::class, 'moveToNextDay'])->name('move');
        Route::put('/{task}', [\App\Http\Controllers\Staff\StaffTaskController::class, 'update'])->name('update');
        Route::patch('/{task}/complete', [\App\Http\Controllers\Staff\StaffTaskController::class, 'complete'])->name('complete');
        Route::delete('/{task}', [\App\Http\Controllers\Staff\StaffTaskController::class, 'destroy'])->name('destroy');
    });

// Contract KPIs: set per employment contract, approved by the supervisor, used each quarter.
Route::middleware(['auth', 'staff'])
    ->prefix('staff/kpis')
    ->name('staff.kpis.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\StaffKpiController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Staff\StaffKpiController::class, 'store'])->name('store');
        Route::post('/submit', [\App\Http\Controllers\Staff\StaffKpiController::class, 'submit'])->name('submit');
        Route::post('/review/{employee}', [\App\Http\Controllers\Staff\StaffKpiController::class, 'review'])->name('review');
        Route::post('/quarters/{cycle}/start', [\App\Http\Controllers\Staff\StaffKpiController::class, 'startQuarter'])->name('quarters.start');
        Route::post('/appraisals/{appraisal}/import', [\App\Http\Controllers\Staff\StaffKpiController::class, 'import'])->name('appraisals.import');
        Route::put('/{kpi}', [\App\Http\Controllers\Staff\StaffKpiController::class, 'update'])->name('update');
        Route::delete('/{kpi}', [\App\Http\Controllers\Staff\StaffKpiController::class, 'destroy'])->name('destroy');
    });

// Staff self-service purchase requests: any staff member raises and tracks their own.
Route::middleware(['auth', 'staff'])
    ->prefix('staff/purchase-requests')
    ->name('staff.purchase-requests.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\StaffPurchaseRequestController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Staff\StaffPurchaseRequestController::class, 'store'])->name('store');
        Route::post('/{purchaseRequest}/submit', [\App\Http\Controllers\Staff\StaffPurchaseRequestController::class, 'submit'])->name('submit');
        Route::delete('/{purchaseRequest}', [\App\Http\Controllers\Staff\StaffPurchaseRequestController::class, 'destroy'])->name('destroy');
    });
