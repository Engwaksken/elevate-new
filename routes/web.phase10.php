<?php

use App\Http\Controllers\Admin\Assets\AssetAssignmentController;
use App\Http\Controllers\Admin\Assets\AssetController;
use App\Http\Controllers\Admin\Assets\AssetDisposalController;
use App\Http\Controllers\Admin\Assets\AssetMaintenanceController;
use App\Http\Controllers\Admin\Procurement\GoodsReceiptController;
use App\Http\Controllers\Admin\Procurement\PurchaseOrderController;
use App\Http\Controllers\Admin\Procurement\PurchaseRequestController;
use App\Http\Controllers\Admin\Procurement\QuotationController;
use App\Http\Controllers\Admin\Procurement\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth','staff'])->group(function () {
    Route::prefix('procurement')->name('procurement.')->group(function () {
        Route::get('/suppliers',[SupplierController::class,'index'])->middleware('permission:procurement.view')->name('suppliers.index');
        Route::post('/suppliers',[SupplierController::class,'store'])->middleware('permission:procurement.create')->name('suppliers.store');
        Route::post('/suppliers/{supplier}/approve',[SupplierController::class,'approve'])->middleware('permission:procurement.approve')->name('suppliers.approve');

        Route::get('/requests',[PurchaseRequestController::class,'index'])->middleware('permission:procurement.view')->name('requests.index');
        Route::post('/requests',[PurchaseRequestController::class,'store'])->middleware('permission:procurement.create')->name('requests.store');
        Route::post('/requests/{purchaseRequest}/submit',[PurchaseRequestController::class,'submit'])->middleware('permission:procurement.create')->name('requests.submit');
        Route::post('/requests/{purchaseRequest}/approve',[PurchaseRequestController::class,'approve'])->middleware('permission:procurement.approve')->name('requests.approve');

        Route::get('/requests/{purchaseRequest}/quotations',[QuotationController::class,'index'])->middleware('permission:procurement.view')->name('quotations.index');
        Route::post('/requests/{purchaseRequest}/quotations',[QuotationController::class,'store'])->middleware('permission:procurement.create')->name('quotations.store');
        Route::post('/quotations/{quotation}/evaluate',[QuotationController::class,'evaluate'])->middleware('permission:procurement.approve')->name('quotations.evaluate');

        Route::get('/purchase-orders',[PurchaseOrderController::class,'index'])->middleware('permission:procurement.view')->name('purchase-orders.index');
        Route::post('/purchase-orders',[PurchaseOrderController::class,'store'])->middleware('permission:procurement.approve')->name('purchase-orders.store');

        Route::post('/purchase-orders/{purchaseOrder}/receive',[GoodsReceiptController::class,'store'])->middleware('permission:procurement.receive')->name('receipts.store');
    });

    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/',[AssetController::class,'index'])->middleware('permission:assets.view')->name('index');
        Route::post('/',[AssetController::class,'store'])->middleware('permission:assets.manage')->name('store');

        Route::post('/{asset}/assign',[AssetAssignmentController::class,'assign'])->middleware('permission:assets.manage')->name('assign');
        Route::post('/assignments/{assignment}/return',[AssetAssignmentController::class,'return'])->middleware('permission:assets.manage')->name('return');

        Route::post('/{asset}/maintenance',[AssetMaintenanceController::class,'store'])->middleware('permission:assets.manage')->name('maintenance.store');
        Route::post('/maintenance/{maintenance}/complete',[AssetMaintenanceController::class,'complete'])->middleware('permission:assets.manage')->name('maintenance.complete');

        Route::post('/{asset}/disposal',[AssetDisposalController::class,'request'])->middleware('permission:assets.dispose')->name('disposal.request');
        Route::post('/{asset}/disposal/approve',[AssetDisposalController::class,'approve'])->middleware('permission:assets.dispose')->name('disposal.approve');
        Route::post('/{asset}/disposal/complete',[AssetDisposalController::class,'complete'])->middleware('permission:assets.dispose')->name('disposal.complete');
    });
});
