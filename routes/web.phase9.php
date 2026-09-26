<?php

use App\Http\Controllers\Admin\HR\AppraisalController;
use App\Http\Controllers\Admin\HR\AppraisalKpiController;
use App\Http\Controllers\Admin\HR\KpiTemplateController;
use App\Http\Controllers\Admin\Jobs\JobAdminController;
use App\Http\Controllers\Admin\HR\ContractController;
use App\Http\Controllers\Admin\HR\EmployeeController;
use App\Http\Controllers\Admin\HR\LeaveApprovalController;
use App\Http\Controllers\Admin\HR\StaffExitController;
use App\Http\Controllers\Admin\ProgrammeManagement\DeliverableController;
use App\Http\Controllers\Admin\ProgrammeManagement\TaskController;
use App\Http\Controllers\HR\LeaveRequestController;
use App\Http\Controllers\HR\StaffAppraisalController;
use App\Http\Controllers\HR\AppraisalExportController;
use App\Http\Controllers\Admin\HR\AppraisalWorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/hr/leave',[LeaveRequestController::class,'index'])->name('hr.leave.index');
    Route::post('/hr/leave',[LeaveRequestController::class,'store'])->name('hr.leave.store');

    Route::get('/staff/appraisals',[StaffAppraisalController::class,'index'])->name('staff.appraisals.index');
    Route::get('/staff/appraisals/team',[StaffAppraisalController::class,'team'])->name('staff.appraisals.team');
    Route::get('/staff/appraisals/{appraisal}',[StaffAppraisalController::class,'show'])->name('staff.appraisals.show');

    Route::put('/staff/appraisals/{appraisal}/self-assessment',[StaffAppraisalController::class,'saveSelf'])->name('staff.appraisals.self.save');
    Route::post('/staff/appraisals/{appraisal}/submit',[StaffAppraisalController::class,'submitSelf'])->name('staff.appraisals.self.submit');

    Route::put('/staff/appraisals/{appraisal}/manager-review',[StaffAppraisalController::class,'saveManager'])->name('staff.appraisals.manager.save');
    Route::post('/staff/appraisals/{appraisal}/manager-submit',[StaffAppraisalController::class,'submitManager'])->name('staff.appraisals.manager.submit');

    Route::post('/staff/appraisals/{appraisal}/acknowledge',[StaffAppraisalController::class,'acknowledge'])->name('staff.appraisals.acknowledge');

    Route::get('/staff/appraisals/{appraisal}/export/excel',[AppraisalExportController::class,'excel'])->name('staff.appraisals.export.excel');
    Route::get('/staff/appraisals/{appraisal}/print',[AppraisalExportController::class,'print'])->name('staff.appraisals.print');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::get('/tasks',[TaskController::class,'index'])->middleware('permission:tasks.manage')->name('tasks.index');
    Route::post('/activities/{activity}/tasks',[TaskController::class,'store'])->middleware('permission:tasks.manage')->name('tasks.store');
    Route::put('/tasks/{task}',[TaskController::class,'update'])->middleware('permission:tasks.manage')->name('tasks.update');

    Route::get('/deliverables',[DeliverableController::class,'index'])->middleware('permission:tasks.manage')->name('deliverables.index');
    Route::post('/activities/{activity}/deliverables',[DeliverableController::class,'store'])->middleware('permission:tasks.manage')->name('deliverables.store');
    Route::put('/deliverables/{deliverable}',[DeliverableController::class,'update'])->middleware('permission:tasks.manage')->name('deliverables.update');

    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/jobs',[JobAdminController::class,'index'])->middleware('permission:hr.view')->name('jobs.index');
        Route::post('/jobs',[JobAdminController::class,'store'])->middleware('permission:hr.manage')->name('jobs.store');
        Route::post('/jobs/import',[JobAdminController::class,'import'])->middleware('permission:hr.manage')->name('jobs.import');
        Route::put('/jobs/{job}',[JobAdminController::class,'update'])->middleware('permission:hr.manage')->name('jobs.update');
        Route::delete('/jobs/{job}',[JobAdminController::class,'destroy'])->middleware('permission:hr.manage')->name('jobs.destroy');
        Route::post('/jobs/{job}/publish',[JobAdminController::class,'publish'])->middleware('permission:hr.manage')->name('jobs.publish');
        Route::post('/jobs/{job}/reject',[JobAdminController::class,'reject'])->middleware('permission:hr.manage')->name('jobs.reject');

        Route::get('/employees',[EmployeeController::class,'index'])->middleware('permission:hr.view')->name('employees.index');
        Route::post('/employees',[EmployeeController::class,'store'])->middleware('permission:hr.manage')->name('employees.store');

        Route::post('/employees/{employee}/contracts',[ContractController::class,'store'])->middleware('permission:hr.manage')->name('contracts.store');

        Route::get('/leave',[LeaveApprovalController::class,'index'])->middleware('permission:leave.view')->name('leave.index');
        Route::post('/leave/{leave}/supervisor-approve',[LeaveApprovalController::class,'supervisorApprove'])->middleware('permission:leave.approve')->name('leave.supervisor-approve');
        Route::post('/leave/{leave}/hr-approve',[LeaveApprovalController::class,'hrApprove'])->middleware('permission:leave.approve')->name('leave.hr-approve');
        Route::post('/leave/{leave}/reject',[LeaveApprovalController::class,'reject'])->middleware('permission:leave.approve')->name('leave.reject');

        Route::get('/kpi-templates',[KpiTemplateController::class,'index'])->middleware('permission:appraisals.view')->name('kpi-templates.index');
        Route::post('/kpi-templates',[KpiTemplateController::class,'store'])->middleware('permission:appraisals.manage')->name('kpi-templates.store');
        Route::put('/kpi-templates/{template}',[KpiTemplateController::class,'update'])->middleware('permission:appraisals.manage')->name('kpi-templates.update');
        Route::delete('/kpi-templates/{template}',[KpiTemplateController::class,'destroy'])->middleware('permission:appraisals.manage')->name('kpi-templates.destroy');

        Route::get('/appraisals',[AppraisalController::class,'index'])->middleware('permission:appraisals.view')->name('appraisals.index');
        Route::get('/appraisals/{appraisal}/kpis',[AppraisalKpiController::class,'show'])->middleware('permission:appraisals.view')->name('appraisals.kpis');
        Route::post('/appraisals/{appraisal}/kpi-template',[AppraisalKpiController::class,'assignTemplate'])->middleware('permission:appraisals.manage')->name('appraisals.kpi-template');
        Route::post('/appraisals/{appraisal}/kpi-score',[AppraisalKpiController::class,'score'])->middleware('permission:appraisals.manage')->name('appraisals.kpi-score');
        Route::post('/appraisal-cycles',[AppraisalController::class,'createCycle'])->middleware('permission:appraisals.manage')->name('appraisal-cycles.store');
        Route::post('/appraisals/assign',[AppraisalController::class,'assign'])->middleware('permission:appraisals.manage')->name('appraisals.assign');
        Route::post('/appraisals/{appraisal}/objectives',[AppraisalController::class,'addObjective'])->middleware('permission:appraisals.manage')->name('appraisals.objectives.store');
        Route::post('/appraisals/{appraisal}/recalculate',[AppraisalController::class,'recalculate'])->middleware('permission:appraisals.manage')->name('appraisals.recalculate');
        Route::post('/appraisals/{appraisal}/finalise',[AppraisalWorkflowController::class,'finalise'])->middleware('permission:appraisals.manage')->name('appraisals.finalise');

        Route::get('/exits',[StaffExitController::class,'index'])->middleware('permission:staff_exit.manage')->name('exits.index');
        Route::post('/exits',[StaffExitController::class,'store'])->middleware('permission:staff_exit.manage')->name('exits.store');
        Route::post('/exits/{exit}/clear',[StaffExitController::class,'clear'])->middleware('permission:staff_exit.manage')->name('exits.clear');
        Route::post('/exits/{exit}/complete',[StaffExitController::class,'complete'])->middleware('permission:staff_exit.manage')->name('exits.complete');
    });
});
