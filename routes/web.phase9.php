<?php

use App\Http\Controllers\Admin\HR\AppraisalController;
use App\Http\Controllers\Admin\HR\ContractController;
use App\Http\Controllers\Admin\HR\EmployeeController;
use App\Http\Controllers\Admin\HR\LeaveApprovalController;
use App\Http\Controllers\Admin\HR\StaffExitController;
use App\Http\Controllers\Admin\ProgrammeManagement\DeliverableController;
use App\Http\Controllers\Admin\ProgrammeManagement\TaskController;
use App\Http\Controllers\HR\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','verified'])->group(function () {
    Route::get('/hr/leave',[LeaveRequestController::class,'index'])->name('hr.leave.index');
    Route::post('/hr/leave',[LeaveRequestController::class,'store'])->name('hr.leave.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::get('/tasks',[TaskController::class,'index'])->middleware('permission:tasks.manage')->name('tasks.index');
    Route::post('/activities/{activity}/tasks',[TaskController::class,'store'])->middleware('permission:tasks.manage')->name('tasks.store');
    Route::put('/tasks/{task}',[TaskController::class,'update'])->middleware('permission:tasks.manage')->name('tasks.update');

    Route::get('/deliverables',[DeliverableController::class,'index'])->middleware('permission:tasks.manage')->name('deliverables.index');
    Route::post('/activities/{activity}/deliverables',[DeliverableController::class,'store'])->middleware('permission:tasks.manage')->name('deliverables.store');
    Route::put('/deliverables/{deliverable}',[DeliverableController::class,'update'])->middleware('permission:tasks.manage')->name('deliverables.update');

    Route::prefix('hr')->name('hr.')->group(function () {
        Route::get('/employees',[EmployeeController::class,'index'])->middleware('permission:hr.view')->name('employees.index');
        Route::post('/employees',[EmployeeController::class,'store'])->middleware('permission:hr.manage')->name('employees.store');

        Route::post('/employees/{employee}/contracts',[ContractController::class,'store'])->middleware('permission:hr.manage')->name('contracts.store');

        Route::get('/leave',[LeaveApprovalController::class,'index'])->middleware('permission:leave.view')->name('leave.index');
        Route::post('/leave/{leave}/supervisor-approve',[LeaveApprovalController::class,'supervisorApprove'])->middleware('permission:leave.approve')->name('leave.supervisor-approve');
        Route::post('/leave/{leave}/hr-approve',[LeaveApprovalController::class,'hrApprove'])->middleware('permission:leave.approve')->name('leave.hr-approve');
        Route::post('/leave/{leave}/reject',[LeaveApprovalController::class,'reject'])->middleware('permission:leave.approve')->name('leave.reject');

        Route::get('/appraisals',[AppraisalController::class,'index'])->middleware('permission:appraisals.view')->name('appraisals.index');
        Route::post('/appraisal-cycles',[AppraisalController::class,'createCycle'])->middleware('permission:appraisals.manage')->name('appraisal-cycles.store');
        Route::post('/appraisals/assign',[AppraisalController::class,'assign'])->middleware('permission:appraisals.manage')->name('appraisals.assign');
        Route::post('/appraisals/{appraisal}/objectives',[AppraisalController::class,'addObjective'])->middleware('permission:appraisals.manage')->name('appraisals.objectives.store');
        Route::post('/appraisals/{appraisal}/recalculate',[AppraisalController::class,'recalculate'])->middleware('permission:appraisals.manage')->name('appraisals.recalculate');

        Route::get('/exits',[StaffExitController::class,'index'])->middleware('permission:staff_exit.manage')->name('exits.index');
        Route::post('/exits',[StaffExitController::class,'store'])->middleware('permission:staff_exit.manage')->name('exits.store');
        Route::post('/exits/{exit}/clear',[StaffExitController::class,'clear'])->middleware('permission:staff_exit.manage')->name('exits.clear');
        Route::post('/exits/{exit}/complete',[StaffExitController::class,'complete'])->middleware('permission:staff_exit.manage')->name('exits.complete');
    });
});
