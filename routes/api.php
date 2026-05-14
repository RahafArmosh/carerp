<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceEmployeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\DailyLeaveController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FacebookLeadController;
use App\Http\Controllers\Api\ExternalLeadApiController;
use App\Http\Controllers\PosController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('login', 'ApiController@login');

// POS API Login - Get token with company and warehouse information
Route::post('pos-api/login', [ApiController::class, 'posApiLogin'])->name('api.pos.login');

// Print queue API endpoints for local print service (no CSRF - uses token auth)
Route::get('print-jobs/pending', [PosController::class, 'getPendingPrintJobs'])->name('api.print-jobs.pending');
Route::post('print-jobs/{id}/complete', [PosController::class, 'completePrintJob'])->name('api.print-jobs.complete');
Route::post('print-jobs/{id}/fail', [PosController::class, 'failPrintJob'])->name('api.print-jobs.fail');

// Attendance by device ID + static token (see config/attendance.php, env ATTENDANCE_DEVICE_API_TOKEN)
Route::post('attendance/device/checkin', [AttendanceEmployeeController::class, 'deviceCheckIn'])->name('api.attendance.device.checkin');
Route::post('attendance/device/checkout', [AttendanceEmployeeController::class, 'deviceCheckOut'])->name('api.attendance.device.checkout');
Route::post('attendance-sync', [AttendanceController::class, 'sync'])->name('api.attendance.sync');

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('logout', [ApiController::class, 'logout']);
    Route::get('get-projects', [ApiController::class, 'getProjects']);
    Route::post('add-tracker', [ApiController::class, 'addTracker']);
    Route::post('stop-tracker', [ApiController::class, 'stopTracker']);
    Route::post('upload-photos', [ApiController::class, 'uploadImage']);

    //attendance api
    Route::post('attendance/checkin', [AttendanceEmployeeController::class, 'checkIn']);
    Route::post('attendance/checkout', [AttendanceEmployeeController::class, 'checkOut']);
    Route::get('attendance', [AttendanceEmployeeController::class, 'index']);

    Route::get('reports/attendance', [ReportController::class, 'attendanceReport']);
    Route::get('admin/reports/attendance', [ReportController::class, 'getAllEmployeeAttendance']);




    Route::post('leave', [LeaveController::class, 'request_leave']);
    Route::get('leave', [LeaveController::class, 'request_my_leave']);
    Route::put('leave/{id}', [LeaveController::class, 'update']);


    Route::post('loan', [LoanController::class, 'request_loan']);
    Route::get('loan', [LoanController::class, 'request_my_loan']);
    Route::put('loan/{id}', [LoanController::class, 'update']);


    Route::post('daily-leave', [DailyLeaveController::class, 'request_earlyLeave']);
    Route::get('daily-leave', [DailyLeaveController::class, 'request_my_early_leave']);
    Route::get('daily-leave/{id}', [DailyLeaveController::class, 'show']);
    Route::put('daily-leave/{id}', [DailyLeaveController::class, 'update_early_leave']);
    Route::delete('daily-leave/{id}', [DailyLeaveController::class, 'destroy_early_leave']);


    Route::get('/settings', [SettingController::class, 'getDefaultSettings']);
    Route::get('/dashboard', [SettingController::class, 'getDashboardData']);
    Route::post('user/attendance/offline', [AttendanceEmployeeController::class, 'offlineCheckInOut']);
    Route::get('attendance/IsCheckIn', [AttendanceEmployeeController::class, 'IsCheckIn']);
    Route::get('attendance/IsCheckOut', [AttendanceEmployeeController::class, 'IsCheckOut']);
    Route::get('/attendance/monthly-stats', [AttendanceEmployeeController::class, 'getCurrentMonthStatistics']);
    Route::get('/attendance/report', [AttendanceEmployeeController::class, 'monthlyReport']);


    Route::apiResource('notifications', NotificationController::class);

    Route::post('tracking', 'TrackingController@store');
    // Route::get('tracking/{user}', 'TrackingController@show');
    Route::get('/tracking/{user_id}', [TrackingController::class, 'getTrackingDataForUser']);
    Route::get('/employees', [EmployeeController::class, 'getAllEmployees']);

    // POS Report API
    Route::get('/pos/report', [PosController::class, 'getPosReportApi'])->name('api.pos.report');
});


Route::post('/facebook/leads/webhook', [FacebookLeadController::class, 'webhook']);
Route::get('/facebook/leads/webhook', [FacebookLeadController::class, 'verify']);


Route::post('/external-leads', [ExternalLeadApiController::class, 'store']);
