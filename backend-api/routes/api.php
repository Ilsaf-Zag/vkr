<?php

use App\Http\Controllers\Admin\AdminResourceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GpsController as AdminGpsController;
use App\Http\Controllers\Admin\MedicalInspectionController;
use App\Http\Controllers\Admin\TechnicalInspectionController;
use App\Http\Controllers\Admin\WaybillController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Mobile\FuelLogController as MobileFuelLogController;
use App\Http\Controllers\Mobile\GpsController as MobileGpsController;
use App\Http\Controllers\Mobile\InspectionController as MobileInspectionController;
use App\Http\Controllers\Mobile\OdometerCaptureController;
use App\Http\Controllers\Mobile\ShiftController;
use App\Http\Controllers\Mobile\WorkflowController;
use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('admin/login', [AuthController::class, 'adminLogin']);
    Route::post('driver/login', [AuthController::class, 'driverLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('mobile')
    ->middleware(['auth:sanctum', 'role:driver'])
    ->group(function () {
        Route::get('current-work-order', [WorkflowController::class, 'currentWorkOrder']);
        Route::get('workflow', [WorkflowController::class, 'workflow']);
        Route::post('waybills/open', [WorkflowController::class, 'openWaybill']);
        Route::post('waybills/{waybill}/odometer-captures', [OdometerCaptureController::class, 'store']);
        Route::post('waybills/{waybill}/odometer-captures/{capture}/confirm', [OdometerCaptureController::class, 'confirm']);
        Route::get('waybills/{waybill}/odometer-control', [OdometerCaptureController::class, 'control']);

        Route::post('inspections/medical/request', [MobileInspectionController::class, 'requestMedical']);
        Route::post('inspections/technical/request', [MobileInspectionController::class, 'requestTechnical']);

        Route::post('waybills/initial-print-done', [ShiftController::class, 'initialPrintDone']);
        Route::post('shift/start', [ShiftController::class, 'start']);
        Route::post('shift/finish-trip', [ShiftController::class, 'finishTrip']);
        Route::post('waybills/final-print-done', [ShiftController::class, 'finalPrintDone']);
        Route::post('shift/close', [ShiftController::class, 'close']);

        Route::get('fuel-logs', [MobileFuelLogController::class, 'index']);
        Route::post('fuel-logs', [MobileFuelLogController::class, 'store']);

        Route::post('gps-points', [MobileGpsController::class, 'store']);
        Route::post('gps-points/batch', [MobileGpsController::class, 'batch']);
    });

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin,dispatcher,medic,mechanic'])
    ->group(function () {
        Route::get('dashboard', DashboardController::class);

        Route::middleware('role:admin')->group(function () {
            Route::apiResource('users', AdminResourceController::class);
            Route::post('users/{id}/change-password', [AdminResourceController::class, 'changePassword']);
            Route::get('audit-logs', [AdminResourceController::class, 'index']);
        });

        Route::middleware('role:admin,dispatcher')->group(function () {
            Route::apiResource('drivers', AdminResourceController::class);
            Route::apiResource('vehicles', AdminResourceController::class)->except(['index', 'show']);
            Route::apiResource('work-orders', AdminResourceController::class);
            Route::post('drivers/{id}/change-password', [AdminResourceController::class, 'changePassword']);

            Route::get('waybills', [WaybillController::class, 'index']);
            Route::get('waybills/{waybill}', [WaybillController::class, 'show']);
            Route::get('waybills/{waybill}/odometer-control', [WaybillController::class, 'odometerControl']);
            Route::get('waybills/{waybill}/pdf/initial', [WaybillController::class, 'initialPdf']);
            Route::get('waybills/{waybill}/pdf/final', [WaybillController::class, 'finalPdf']);

            Route::get('gps/current', [AdminGpsController::class, 'current']);
            Route::get('gps/history', [AdminGpsController::class, 'history']);
            Route::get('fuel-logs', [AdminResourceController::class, 'index']);

            Route::get('reports/{type}', [ReportController::class, 'show']);
            Route::get('reports/{type}/export', [ReportController::class, 'export']);
        });

        Route::middleware('role:admin,dispatcher,mechanic')->group(function () {
            Route::get('vehicles', [AdminResourceController::class, 'index']);
            Route::get('vehicles/{id}', [AdminResourceController::class, 'show']);
        });

        Route::middleware('role:admin,dispatcher,medic')->group(function () {
            Route::get('medical-inspections', [MedicalInspectionController::class, 'index']);
        });

        Route::middleware('role:admin,medic')->group(function () {
            Route::post('medical-inspections/{medicalInspection}/approve', [MedicalInspectionController::class, 'approve']);
            Route::post('medical-inspections/{medicalInspection}/reject', [MedicalInspectionController::class, 'reject']);
        });

        Route::middleware('role:admin,dispatcher,mechanic')->group(function () {
            Route::get('technical-inspections', [TechnicalInspectionController::class, 'index']);
        });

        Route::middleware('role:admin,mechanic')->group(function () {
            Route::post('technical-inspections/{technicalInspection}/approve', [TechnicalInspectionController::class, 'approve']);
            Route::post('technical-inspections/{technicalInspection}/reject', [TechnicalInspectionController::class, 'reject']);
        });
    });
