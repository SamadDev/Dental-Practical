<?php

use App\Http\Controllers\Api\AqsatContractController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashFlowForecastController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PaymentPlanController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Health / deploy check
    Route::get('health', [\App\Http\Controllers\Api\HealthController::class, 'check']);

    // Auth
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');

    // Users (admin only)
    Route::middleware(['auth:sanctum', 'permission:users.manage'])->group(function () {
        Route::get('users', [AuthController::class, 'index']);
        Route::post('users', [AuthController::class, 'store']);
        Route::patch('users/{user}', [AuthController::class, 'update']);
        Route::delete('users/{user}', [AuthController::class, 'destroy']);
    });

    // Patients
    Route::get('patients/stats', [PatientController::class, 'stats']);
    Route::apiResource('patients', PatientController::class);

    // Aqsat contracts
    Route::apiResource('aqsat-contracts', AqsatContractController::class)
        ->only(['index', 'store', 'show', 'update']);

    // Visits & queue
    Route::get('queue', [VisitController::class, 'queue']);
    Route::get('visits/archive', [VisitController::class, 'archive']);
    Route::post('visits', [VisitController::class, 'store']);
    Route::patch('visits/{visit}', [VisitController::class, 'update']);
    Route::delete('visits/{visit}', [VisitController::class, 'destroy']);
    Route::patch('visits/{visit}/status', [VisitController::class, 'updateStatus']);
    Route::post('visits/{visit}/xray', [VisitController::class, 'uploadXray']);
    Route::post('visits/{visit}/checkout', [VisitController::class, 'checkout']);
    Route::post('visits/{visit}/pay-debt', [VisitController::class, 'payDebt']);

    // Expenses
    Route::get('expenses', [ExpenseController::class, 'index']);
    Route::post('expenses', [ExpenseController::class, 'store']);
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);

    // Financial dashboard
    Route::get('dashboard/metrics', [DashboardController::class, 'metrics']);

    // Cash Flow Forecast
    Route::middleware(['auth:sanctum', 'permission:cash_flow.view'])->group(function () {
        Route::get('cash-flow/forecast', [CashFlowForecastController::class, 'forecast']);
        Route::get('cash-flow/weekly', [CashFlowForecastController::class, 'weekly']);
        Route::apiResource('cash-flow/manual', CashFlowForecastController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::post('cash-flow/generate-aqsat', [CashFlowForecastController::class, 'generateFromAqsat']);
    });

    // Payment Plans
    Route::middleware(['auth:sanctum', 'permission:payment_plans.view'])->group(function () {
        Route::get('payment-plans', [PaymentPlanController::class, 'index']);
        Route::get('payment-plans/overdue', [PaymentPlanController::class, 'overdue']);
        Route::get('payment-plans/upcoming', [PaymentPlanController::class, 'upcoming']);
        Route::get('payment-plans/{paymentPlan}', [PaymentPlanController::class, 'show']);
        Route::post('payment-plans', [PaymentPlanController::class, 'store'])->middleware('permission:payment_plans.create');
        Route::patch('payment-plans/{paymentPlan}', [PaymentPlanController::class, 'update'])->middleware('permission:payment_plans.edit');
        Route::delete('payment-plans/{paymentPlan}', [PaymentPlanController::class, 'destroy'])->middleware('permission:payment_plans.edit');
        Route::post('payment-plans/installments/{installment}/pay', [PaymentPlanController::class, 'payInstallment'])->middleware('permission:payment_plans.pay');
        Route::post('payment-plans/installments/{installment}/waive', [PaymentPlanController::class, 'waiveInstallment'])->middleware('permission:payment_plans.pay');
    });

    // Inventory
    Route::middleware(['auth:sanctum', 'permission:inventory.view'])->group(function () {
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::get('inventory/categories', [InventoryController::class, 'categories']);
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);
        Route::get('inventory/expiring', [InventoryController::class, 'expiring']);
        Route::post('inventory', [InventoryController::class, 'store'])->middleware('permission:inventory.move');
        Route::get('inventory/{inventoryItem}', [InventoryController::class, 'show']);
        Route::patch('inventory/{inventoryItem}', [InventoryController::class, 'update'])->middleware('permission:inventory.move');
        Route::delete('inventory/{inventoryItem}', [InventoryController::class, 'destroy'])->middleware('permission:inventory.move');
        Route::post('inventory/{inventoryItem}/move', [InventoryController::class, 'move'])->middleware('permission:inventory.move');
        Route::post('inventory/{inventoryItem}/adjust', [InventoryController::class, 'adjust'])->middleware('permission:inventory.adjust');
        Route::get('inventory/{inventoryItem}/movements', [InventoryController::class, 'movements']);
    });

    // Vendors & Purchase Orders
    Route::middleware(['auth:sanctum', 'permission:vendors.view'])->group(function () {
        Route::get('vendors', [VendorController::class, 'index']);
        Route::post('vendors', [VendorController::class, 'store'])->middleware('permission:vendors.create');
        Route::get('vendors/{vendor}', [VendorController::class, 'show']);
        Route::patch('vendors/{vendor}', [VendorController::class, 'update'])->middleware('permission:vendors.edit');
        Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->middleware('permission:vendors.edit');
        Route::get('vendors/{vendor}/items', [VendorController::class, 'items']);

        Route::get('purchase-orders', [VendorController::class, 'purchaseOrders']);
        Route::post('purchase-orders', [VendorController::class, 'storePurchaseOrder'])->middleware('permission:vendors.po');
        Route::get('purchase-orders/{purchaseOrder}', [VendorController::class, 'showPurchaseOrder']);
        Route::patch('purchase-orders/{purchaseOrder}', [VendorController::class, 'updatePurchaseOrder'])->middleware('permission:vendors.po');
        Route::post('purchase-orders/{purchaseOrder}/receive', [VendorController::class, 'receivePurchaseOrder'])->middleware('permission:vendors.po');
    });
});