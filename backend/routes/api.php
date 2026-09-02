<?php

use App\Http\Controllers\Api\AqsatContractController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashFlowForecastController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PatientConditionController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PaymentPlanController;
use App\Http\Controllers\Api\ReceptionistController;
use App\Http\Controllers\Api\ToothChartController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - v1
|--------------------------------------------------------------------------
|
| Every route below (except health + login) requires a Sanctum bearer token.
| Permission checks are handled by Spatie's permission middleware aliases
| registered in bootstrap/app.php. See RolesAndPermissionsSeeder for the
| role -> permission mapping.
*/

Route::prefix('v1')->group(function () {
    // Public
    Route::get('health', [\App\Http\Controllers\Api\HealthController::class, 'check']);
    Route::post('login', [AuthController::class, 'login']);

    // Authenticated (token required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        // Admin-only user management
        Route::middleware('permission:users.manage')->group(function () {
            Route::get('users', [AuthController::class, 'index']);
            Route::post('users', [AuthController::class, 'store']);
            Route::patch('users/{user}', [AuthController::class, 'update']);
            Route::delete('users/{user}', [AuthController::class, 'destroy']);
        });

        // Doctor management (admin)
        Route::middleware('permission:users.manage')->group(function () {
            Route::get('doctors',         [DoctorController::class, 'index']);
            Route::post('doctors',        [DoctorController::class, 'store']);
            Route::get('doctors/{doctor}',[DoctorController::class, 'show']);
            Route::match(['put','patch'], 'doctors/{doctor}', [DoctorController::class, 'update']);
            Route::delete('doctors/{doctor}', [DoctorController::class, 'destroy']);
        });

        // Receptionist management (admin) - many-to-many with doctors
        Route::middleware('permission:users.manage')->group(function () {
            Route::get('receptionists',         [ReceptionistController::class, 'index']);
            Route::post('receptionists',        [ReceptionistController::class, 'store']);
            Route::get('receptionists/{user}',  [ReceptionistController::class, 'show']);
            Route::match(['put','patch'], 'receptionists/{user}', [ReceptionistController::class, 'update']);
            Route::delete('receptionists/{user}', [ReceptionistController::class, 'destroy']);
        });
// Patients
        Route::get('patients/stats', [PatientController::class, 'stats'])->middleware('permission:patients.view');
        Route::get('patients', [PatientController::class, 'index'])->middleware('permission:patients.view');
        Route::post('patients', [PatientController::class, 'store'])->middleware('permission:patients.create');
        Route::get('patients/{patient}', [PatientController::class, 'show'])->middleware('permission:patients.view');
        Route::match(['put', 'patch'], 'patients/{patient}', [PatientController::class, 'update'])->middleware('permission:patients.edit');
        Route::delete('patients/{patient}', [PatientController::class, 'destroy'])->middleware('permission:patients.delete');

        // Patient allergies & conditions
        Route::get('patients/{patient}/conditions', [PatientConditionController::class, 'index'])->middleware('permission:patients.view');
        Route::post('patients/{patient}/conditions', [PatientConditionController::class, 'store'])->middleware('permission:patients.edit');
        Route::patch('conditions/{condition}', [PatientConditionController::class, 'update'])->middleware('permission:patients.edit');
        Route::delete('conditions/{condition}', [PatientConditionController::class, 'destroy'])->middleware('permission:patients.edit');

        // Dental chart (per-tooth statuses, universal numbering 1-32)
        Route::get('patients/{patient}/teeth', [ToothChartController::class, 'show'])->middleware('permission:patients.view');
        Route::put('patients/{patient}/teeth', [ToothChartController::class, 'update'])->middleware('permission:visits.edit');

        // Aqsat contracts
        Route::get('aqsat-contracts', [AqsatContractController::class, 'index'])->middleware('permission:aqsat.view');
        Route::post('aqsat-contracts', [AqsatContractController::class, 'store'])->middleware('permission:aqsat.create');
        Route::get('aqsat-contracts/{aqsatContract}', [AqsatContractController::class, 'show'])->middleware('permission:aqsat.view');
        Route::match(['put', 'patch'], 'aqsat-contracts/{aqsatContract}', [AqsatContractController::class, 'update'])->middleware('permission:aqsat.edit');// Visits & queue
        Route::get('queue', [VisitController::class, 'queue'])->middleware('permission:queue.view');
        Route::get('visits/archive', [VisitController::class, 'archive'])->middleware('permission:archive.view');
        Route::post('visits', [VisitController::class, 'store'])->middleware('permission:visits.create');
        Route::patch('visits/{visit}', [VisitController::class, 'update'])->middleware('permission:visits.edit');
        Route::delete('visits/{visit}', [VisitController::class, 'destroy'])->middleware('permission:visits.edit');
        Route::patch('visits/{visit}/status', [VisitController::class, 'updateStatus'])->middleware('permission:queue.manage');
        Route::post('visits/{visit}/xray', [VisitController::class, 'uploadXray'])->middleware('permission:visits.xray');
        Route::post('visits/{visit}/checkout', [VisitController::class, 'checkout'])->middleware('permission:visits.checkout');
        Route::post('visits/{visit}/pay-debt', [VisitController::class, 'payDebt'])->middleware('permission:visits.pay_debt');

        // Expenses
        Route::get('expenses', [ExpenseController::class, 'index'])->middleware('permission:expenses.view');
        Route::post('expenses', [ExpenseController::class, 'store'])->middleware('permission:expenses.create');
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->middleware('permission:expenses.delete');

        // Financial dashboard
        Route::get('dashboard/metrics', [DashboardController::class, 'metrics'])->middleware('permission:dashboard.view');// Cash Flow Forecast
        Route::get('cash-flow/forecast', [CashFlowForecastController::class, 'forecast'])->middleware('permission:cash_flow.view');
        Route::get('cash-flow/weekly', [CashFlowForecastController::class, 'weekly'])->middleware('permission:cash_flow.view');
        Route::get('cash-flow/manual', [CashFlowForecastController::class, 'index'])->middleware('permission:cash_flow.view');
        Route::post('cash-flow/manual', [CashFlowForecastController::class, 'store'])->middleware('permission:cash_flow.manage');
        Route::match(['put', 'patch'], 'cash-flow/manual/{forecast}', [CashFlowForecastController::class, 'update'])->middleware('permission:cash_flow.manage');
        Route::delete('cash-flow/manual/{forecast}', [CashFlowForecastController::class, 'destroy'])->middleware('permission:cash_flow.manage');
        Route::post('cash-flow/generate-aqsat', [CashFlowForecastController::class, 'generateFromAqsat'])->middleware('permission:cash_flow.manage');

        // Payment Plans
        Route::get('payment-plans', [PaymentPlanController::class, 'index'])->middleware('permission:payment_plans.view');
        Route::get('payment-plans/overdue', [PaymentPlanController::class, 'overdue'])->middleware('permission:payment_plans.view');
        Route::get('payment-plans/upcoming', [PaymentPlanController::class, 'upcoming'])->middleware('permission:payment_plans.view');
        Route::get('payment-plans/{paymentPlan}', [PaymentPlanController::class, 'show'])->middleware('permission:payment_plans.view');
        Route::post('payment-plans', [PaymentPlanController::class, 'store'])->middleware('permission:payment_plans.create');
        Route::match(['put', 'patch'], 'payment-plans/{paymentPlan}', [PaymentPlanController::class, 'update'])->middleware('permission:payment_plans.edit');
        Route::delete('payment-plans/{paymentPlan}', [PaymentPlanController::class, 'destroy'])->middleware('permission:payment_plans.edit');
        Route::post('payment-plans/installments/{installment}/pay', [PaymentPlanController::class, 'payInstallment'])->middleware('permission:payment_plans.pay');
        Route::post('payment-plans/installments/{installment}/waive', [PaymentPlanController::class, 'waiveInstallment'])->middleware('permission:payment_plans.pay');

        // Inventory
        Route::get('inventory', [InventoryController::class, 'index'])->middleware('permission:inventory.view');
        Route::get('inventory/categories', [InventoryController::class, 'categories'])->middleware('permission:inventory.view');
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock'])->middleware('permission:inventory.view');
        Route::get('inventory/expiring', [InventoryController::class, 'expiring'])->middleware('permission:inventory.view');
        Route::get('inventory/{inventoryItem}', [InventoryController::class, 'show'])->middleware('permission:inventory.view');
        Route::get('inventory/{inventoryItem}/movements', [InventoryController::class, 'movements'])->middleware('permission:inventory.view');
        Route::post('inventory', [InventoryController::class, 'store'])->middleware('permission:inventory.adjust');
        Route::match(['put', 'patch'], 'inventory/{inventoryItem}', [InventoryController::class, 'update'])->middleware('permission:inventory.adjust');
        Route::delete('inventory/{inventoryItem}', [InventoryController::class, 'destroy'])->middleware('permission:inventory.adjust');
        Route::post('inventory/{inventoryItem}/move', [InventoryController::class, 'move'])->middleware('permission:inventory.move');
        Route::post('inventory/{inventoryItem}/adjust', [InventoryController::class, 'adjust'])->middleware('permission:inventory.adjust');

        // Vendors & Purchase Orders
        Route::get('vendors', [VendorController::class, 'index'])->middleware('permission:vendors.view');
        Route::post('vendors', [VendorController::class, 'store'])->middleware('permission:vendors.create');
        Route::get('vendors/{vendor}', [VendorController::class, 'show'])->middleware('permission:vendors.view');
        Route::match(['put', 'patch'], 'vendors/{vendor}', [VendorController::class, 'update'])->middleware('permission:vendors.edit');
        Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->middleware('permission:vendors.edit');
        Route::get('vendors/{vendor}/items', [VendorController::class, 'items'])->middleware('permission:vendors.view');

        Route::get('purchase-orders', [VendorController::class, 'purchaseOrders'])->middleware('permission:vendors.view');
        Route::post('purchase-orders', [VendorController::class, 'storePurchaseOrder'])->middleware('permission:vendors.po');
        Route::get('purchase-orders/{purchaseOrder}', [VendorController::class, 'showPurchaseOrder'])->middleware('permission:vendors.view');
        // DEBUG: diagnose patient query error for receptionist
        Route::get('debug/patient-trace', function (\\Illuminate\\Http\\Request $request) {
            try {
                $user = $request->user();
                $ids = $user->accessibleDoctorIds();
                return response()->json([
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'isAdmin' => $user->isAdmin(),
                    'ids' => $ids,
                ]);
            } catch (\\Throwable $e) {
                return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine()], 500);
            }
        });
        Route::match(['put', 'patch'], 'purchase-orders/{purchaseOrder}', [VendorController::class, 'updatePurchaseOrder'])->middleware('permission:vendors.po');
        Route::post('purchase-orders/{purchaseOrder}/receive', [VendorController::class, 'receivePurchaseOrder'])->middleware('permission:vendors.po');
    });
});