<?php

use App\Http\Controllers\Api\AqsatContractController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PaymentPlanController;
use App\Http\Controllers\Api\PaymentPlanInstallmentController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Patients — /stats is declared first so /patients/{patient} doesn't swallow it.
    Route::get   ('patients/stats', [PatientController::class, 'stats']);
    Route::apiResource('patients', PatientController::class);

    // Aqsat contracts
    Route::apiResource('aqsat-contracts', AqsatContractController::class)
        ->only(['index', 'store', 'show', 'update']);

    // Payment Plans
    Route::apiResource('payment-plans', PaymentPlanController::class)
        ->only(['index', 'store', 'show', 'update']);
    Route::get('payment-plans/overdue', [PaymentPlanController::class, 'overdue']);
    Route::post('payment-plans/installments/{installment}/pay', [PaymentPlanInstallmentController::class, 'pay']);
    Route::post('payment-plans/installments/{installment}/waive', [PaymentPlanInstallmentController::class, 'waive']);

    // Inventory
    Route::apiResource('inventory', InventoryController::class)
        ->only(['index', 'store']);
    Route::get('inventory/categories', [InventoryController::class, 'categories']);
    Route::post('inventory/{inventory}/move', [InventoryController::class, 'move']);

    // Vendors
    Route::apiResource('vendors', VendorController::class)
        ->only(['index', 'store']);

    // Purchase Orders
    Route::apiResource('purchase-orders', PurchaseOrderController::class)
        ->only(['index', 'store', 'show']);
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);

    // Visits & queue
    Route::get   ('queue',                       [VisitController::class, 'queue']);
    Route::get   ('visits/archive',              [VisitController::class, 'archive']);
    Route::post  ('visits',                      [VisitController::class, 'store']);
    Route::patch ('visits/{visit}',              [VisitController::class, 'update']);
    Route::delete('visits/{visit}',              [VisitController::class, 'destroy']);
    Route::patch ('visits/{visit}/status',       [VisitController::class, 'updateStatus']);
    Route::post  ('visits/{visit}/xray',         [VisitController::class, 'uploadXray']);
    Route::post  ('visits/{visit}/checkout',     [VisitController::class, 'checkout']);
    Route::post  ('visits/{visit}/pay-debt',     [VisitController::class, 'payDebt']);

    // Expenses (dynamic quick form)
    Route::get   ('expenses',               [ExpenseController::class, 'index']);
    Route::post  ('expenses',               [ExpenseController::class, 'store']);
    Route::delete('expenses/{expense}',     [ExpenseController::class, 'destroy']);

    // Financial dashboard
    Route::get   ('dashboard/metrics', [DashboardController::class, 'metrics']);
});
