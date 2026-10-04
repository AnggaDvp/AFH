<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $databaseStatus = 'error';
        $databaseMessage = null;
        $httpStatus = 503;

        try {
            DB::connection()->getPdo();
            $databaseStatus = 'ok';
            $httpStatus = 200;
        } catch (Throwable $e) {
            $databaseStatus = 'error';
            $databaseMessage = 'Koneksi database gagal atau tidak tersedia.';
            $httpStatus = 503;
        }

        return response()->json([
            'app' => 'ok',
            'database' => $databaseStatus,
            'driver' => config('database.default'),
            'host' => config('database.connections.' . config('database.default') . '.host'),
            'port' => config('database.connections.' . config('database.default') . '.port'),
            'database_name' => config('database.connections.' . config('database.default') . '.database'),
            'message' => $databaseMessage,
        ], $httpStatus, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
