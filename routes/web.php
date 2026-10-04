<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\CustomerAccessController;
use App\Http\Controllers\CustomerAreaController;
use App\Http\Controllers\CustomerChatController;
use App\Http\Controllers\CashLoanRequestController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminActivityLogController;
use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\AdminCreditApplicationController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminDanaTunaiController;
use App\Http\Controllers\AdminDocumentController;
use App\Http\Controllers\AdminMotorController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminWhatsAppNotificationController;
use App\Http\Controllers\ChatAttachmentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::post('/customer/register', [CustomerAccessController::class, 'register'])->name('customer.register');
Route::post('/customer/identify', [CustomerAccessController::class, 'identify'])->name('customer.identify');

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('admin.login.store');

Route::middleware(['admin', 'admin.capability:dashboard'])
    ->get('/admin', [AdminPanelController::class, 'index'])->name('admin.home');

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
    Route::middleware('admin.capability:notifications')->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
        Route::post('/read-all', [AdminNotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('/{notification}/read', [AdminNotificationController::class, 'markRead'])->name('read');
    });
    Route::get('/activity-logs', [AdminActivityLogController::class, 'index'])
        ->middleware('admin.capability:settings')
        ->name('activity-logs.index');
    Route::get('/whatsapp', [AdminWhatsAppNotificationController::class, 'index'])
        ->middleware('admin.capability:settings')
        ->name('whatsapp.index');
    Route::post('/whatsapp/{notification}/retry', [AdminWhatsAppNotificationController::class, 'retry'])
        ->middleware('admin.capability:settings')
        ->name('whatsapp.retry');
    Route::get('/whatsapp-notifications', [AdminWhatsAppNotificationController::class, 'index'])
        ->middleware('admin.capability:settings')
        ->name('whatsapp-notifications.index');
    Route::middleware('admin.capability:reports')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [AdminReportController::class, 'index'])->name('index');
        Route::get('/export', [AdminReportController::class, 'export'])->name('export');
    });
    Route::get('/documents', [AdminDocumentController::class, 'index'])
        ->middleware('admin.capability:customers')
        ->name('documents.index');
    Route::get('/documents/{document}', [AdminDocumentController::class, 'show'])
        ->middleware('admin.capability:customers')
        ->name('documents.show');

    Route::middleware('admin.capability:customers')->prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [AdminCustomerController::class, 'index'])->name('index');
        Route::get('/{customer}', [AdminCustomerController::class, 'show'])->name('show');
    });
    Route::middleware('admin.capability:motors')->prefix('motors')->name('motors.')->group(function () {
        Route::get('/', [AdminMotorController::class, 'index'])->name('index');
    });
    Route::middleware('admin.capability:applications')->prefix('pengajuan')->name('applications.')->group(function () {
        Route::get('/', [AdminCreditApplicationController::class, 'index'])->name('index');
        Route::get('/{application}', [AdminCreditApplicationController::class, 'show'])->name('show');
        Route::post('/{application}/status', [AdminCreditApplicationController::class, 'updateStatus'])->name('status');
    });
    Route::middleware('admin.capability:dana_tunai')->prefix('dana-tunai')->name('dana-tunai.')->group(function () {
        Route::get('/', [AdminDanaTunaiController::class, 'index'])->name('index');
        Route::get('/{application}', [AdminDanaTunaiController::class, 'show'])->name('show');
        Route::post('/{application}/status', [AdminDanaTunaiController::class, 'updateStatus'])->name('status');
    });
    Route::middleware('admin.capability:chat')->prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [AdminChatController::class, 'index'])->name('index');
        Route::get('/attachments/{attachment}', [ChatAttachmentController::class, 'admin'])->name('attachments.show');
        Route::get('/{conversation}', [AdminChatController::class, 'show'])->name('show');
        Route::post('/{conversation}/messages', [AdminChatController::class, 'sendMessage'])->name('messages.store');
    });
});

Route::middleware('customer.identified')->prefix('customer')->name('customer.')->group(function () {
    Route::get('/', [CustomerAreaController::class, 'show'])->name('show');
    Route::get('/motor', [CustomerAreaController::class, 'motor'])->name('motor');
    Route::get('/motor/{motor:code}', [CustomerAreaController::class, 'motorDetail'])->name('motor.show');
    Route::get('/dana-tunai', [CustomerAreaController::class, 'danaTunai'])->name('dana-tunai');
    Route::post('/dana-tunai', [CashLoanRequestController::class, 'store'])->name('dana-tunai.store');
    Route::get('/simulasi', [CustomerAreaController::class, 'simulasi'])->name('simulasi');
    Route::get('/tentang', [CustomerAreaController::class, 'tentang'])->name('tentang');
    Route::post('/chat/start', [CustomerChatController::class, 'start'])->name('chat.start');
    Route::get('/chat', [CustomerChatController::class, 'index'])->name('chat');
    Route::get('/chat/attachments/{attachment}', [ChatAttachmentController::class, 'customer'])->name('chat.attachments.show');
    Route::get('/chat/{conversation}', [CustomerChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{conversation}/messages', [CustomerChatController::class, 'sendMessage'])->name('chat.messages.store');
    Route::post('/logout', [CustomerAccessController::class, 'logout'])->name('logout');
});

Route::get('/health', HealthCheckController::class);
