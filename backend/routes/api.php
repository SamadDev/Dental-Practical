<?php

use App\Http\Controllers\Api\AqsatContractController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Patients
    Route::apiResource('patients', PatientController::class);

    // Aqsat contracts
    Route::apiResource('aqsat-contracts', AqsatContractController::class)
        ->only(['index', 'store', 'show', 'update']);
    Route::post('aqsat-contracts/{aqsat_contract}/pay-installment',
        [AqsatContractController::class, 'payInstallment']);

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
