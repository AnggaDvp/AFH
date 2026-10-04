<?php

use App\Http\Controllers\BirdWhatsAppWebhookController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\CustomerMotorApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/regions', [RegionController::class, 'index']);
Route::get('/regions/{region}/districts', [RegionController::class, 'districts']);
Route::get('/districts/{district}/villages', [RegionController::class, 'villages']);
Route::get('/regions/{region}/districts/{district}/villages', [RegionController::class, 'villagesInRegion']);

Route::post('/webhooks/bird/whatsapp', BirdWhatsAppWebhookController::class)
    ->name('webhooks.bird.whatsapp');

Route::middleware(['web', 'customer.identified'])
    ->prefix('customer/motors')
    ->name('api.customer.motors.')
    ->group(function (): void {
        Route::get('/', [CustomerMotorApiController::class, 'index'])->name('index');
        Route::get('/{motor:code}', [CustomerMotorApiController::class, 'show'])->name('show');
    });
