<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\CustomFieldController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\EncounterController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\MedicalHistoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\RecurringAppointmentController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\TierController;
use App\Http\Controllers\Api\TwoFactorAuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WaitingQueueController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'timestamp' => now()]));

Route::get('/tier', [TierController::class, 'index']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);

        Route::get('/two-factor/status', [TwoFactorAuthController::class, 'status']);
        Route::post('/two-factor/setup', [TwoFactorAuthController::class, 'setup']);
        Route::post('/two-factor/enable', [TwoFactorAuthController::class, 'verifyAndEnable']);
        Route::post('/two-factor/disable', [TwoFactorAuthController::class, 'disable']);
        Route::post('/two-factor/verify', [TwoFactorAuthController::class, 'verifyCode']);
        Route::post('/two-factor/regenerate-codes', [TwoFactorAuthController::class, 'regenerateRecoveryCodes']);
    });
});

Route::prefix('dashboard')->middleware('auth:sanctum')->group(function () {
    Route::get('/stats', [DashboardController::class, 'stats']);
    Route::get('/recent-appointments', [DashboardController::class, 'recentAppointments']);
    Route::get('/appointment-stats', [DashboardController::class, 'appointmentStats']);
});

Route::apiResource('clinics', ClinicController::class)->middleware('auth:sanctum');
Route::apiResource('services', ServiceController::class)->middleware('auth:sanctum');
Route::apiResource('doctors', DoctorController::class)->middleware('auth:sanctum');
Route::apiResource('patients', PatientController::class)->middleware('auth:sanctum');
Route::apiResource('appointments', AppointmentController::class)->middleware('auth:sanctum');
Route::apiResource('recurring-appointments', RecurringAppointmentController::class)->middleware('auth:sanctum');
Route::apiResource('users', UserController::class)->middleware('auth:sanctum');

Route::apiResource('encounters', EncounterController::class)->middleware('auth:sanctum');
Route::apiResource('prescriptions', PrescriptionController::class)->middleware('auth:sanctum');
Route::apiResource('bills', BillController::class)->middleware('auth:sanctum');
Route::apiResource('medical-histories', MedicalHistoryController::class)->middleware('auth:sanctum');
Route::apiResource('waiting-queue', WaitingQueueController::class)->middleware('auth:sanctum');
Route::apiResource('custom-fields', CustomFieldController::class)->middleware('auth:sanctum');
Route::get('/custom-fields/entity/{entityType}', [CustomFieldController::class, 'getByEntity'])->middleware('auth:sanctum');
Route::get('/custom-fields/entity/{entityType}/values/{entityId}', [CustomFieldController::class, 'getEntityValues'])->middleware('auth:sanctum');
Route::post('/custom-fields/entity/{entityType}/save', [CustomFieldController::class, 'saveEntityValues'])->middleware('auth:sanctum');

Route::get('/appointments/{doctor}/available-slots', [AppointmentController::class, 'availableSlots'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/roles', [UserController::class, 'roles']);
    Route::get('/permissions', [UserController::class, 'permissions']);
    Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/encounters/by-appointment/{appointment}', [EncounterController::class, 'byAppointment']);
    Route::post('/encounters/{encounter}/complete', [EncounterController::class, 'complete']);
    Route::get('/encounters/by-patient/{patient}', [EncounterController::class, 'byPatient']);

    Route::get('/prescriptions/by-patient/{patient}', [PrescriptionController::class, 'byPatient']);
    Route::get('/prescriptions/by-encounter/{encounter}', [PrescriptionController::class, 'byEncounter']);
    Route::get('/prescriptions/export', [PrescriptionController::class, 'export']);
    Route::get('/prescriptions/{prescription}/download', [PrescriptionController::class, 'downloadPdf']);

    Route::get('/bills/by-encounter/{encounter}', [BillController::class, 'byEncounter']);
    Route::get('/bills/by-patient/{patient}', [BillController::class, 'byPatient']);
    Route::post('/bills/{bill}/pay', [BillController::class, 'pay']);
    Route::get('/bills/{bill}/download', [BillController::class, 'downloadPdf']);

    Route::get('/medical-histories/by-patient/{patient}', [MedicalHistoryController::class, 'byPatient']);

    Route::get('/waiting-queue/stats', [WaitingQueueController::class, 'stats']);
    Route::get('/waiting-queue/display', [WaitingQueueController::class, 'display']);
    Route::post('/waiting-queue/reorder', [WaitingQueueController::class, 'reorder']);
    Route::post('/waiting-queue/{id}/call', [WaitingQueueController::class, 'call']);
    Route::post('/waiting-queue/{id}/start', [WaitingQueueController::class, 'start']);
    Route::post('/waiting-queue/{id}/complete', [WaitingQueueController::class, 'complete']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unread']);
    Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications/clear-all', [NotificationController::class, 'clearAll']);

    Route::get('/reports', [ReportsController::class, 'index']);
    Route::get('/reports/export', [ReportsController::class, 'export']);

    Route::post('/email/prescription/{prescription}', [PrescriptionController::class, 'sendEmail']);
    Route::post('/email/send-report', [EmailController::class, 'sendReport']);
    Route::post('/email/send-reminder', [EmailController::class, 'sendReminder']);
    Route::post('/email/send-bulk-reminders', [EmailController::class, 'sendBulkReminders']);

    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{id}', [AuditLogController::class, 'show']);
    Route::get('/audit-logs/entity/{entityType}/{entityId}', [AuditLogController::class, 'entityHistory']);

    Route::get('/google-calendar/status', [GoogleCalendarController::class, 'isConfigured']);
    Route::get('/google-calendar/auth-url', [GoogleCalendarController::class, 'authUrl']);
    Route::post('/google-calendar/callback', [GoogleCalendarController::class, 'callback']);
    Route::get('/google-calendar/calendars', [GoogleCalendarController::class, 'listCalendars']);
    Route::post('/google-calendar/sync', [GoogleCalendarController::class, 'syncAppointment']);
    Route::post('/google-calendar/disconnect', [GoogleCalendarController::class, 'disconnect']);
});

Route::get('/routes', function () {
    $routes = [];
    $apiRoutes = app('router')->getApiRoutes();

    foreach ($apiRoutes as $route) {
        $routes[] = [
            'uri' => '/'.ltrim($route->uri(), '/'),
            'methods' => $route->methods(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
            'type' => 'api',
        ];
    }

    return response()->json([
        'routes' => $routes,
        'count' => count($routes),
    ]);
});
