<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if (!$request->is('customer/chat', 'customer/chat/*', 'admin/chat', 'admin/chat/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $message = match ($status) {
                403 => 'Anda tidak memiliki akses ke percakapan ini.',
                404 => 'Percakapan tidak ditemukan atau tidak dapat diakses.',
                413 => 'Ukuran total unggahan terlalu besar. Kurangi jumlah atau ukuran file, lalu coba lagi.',
                419 => 'Sesi Anda telah berakhir. Silakan masuk kembali.',
                default => 'Chat belum dapat dimuat. Silakan coba lagi nanti.',
            };

            if ($request->expectsJson() && !$request->header('X-Inertia')) {
                return response()->json(['message' => $message], $status);
            }

            return Inertia::render('ChatErrorPage', [
                'message' => $message,
                'backUrl' => $request->is('admin/*') ? '/admin/chat' : '/customer/chat',
            ])->toResponse($request)->setStatusCode($status);
        });

        $this->renderable(function (QueryException $e, $request) {
            if (config('app.debug') === false) {
                return response()->json([
                    'app' => 'error',
                    'database' => 'error',
                    'message' => 'Terjadi kesalahan pada sistem. Silakan coba lagi nanti.',
                ], 500);
            }
        });
    }

    public function report(Throwable $e)
    {
        if ($e instanceof QueryException) {
            $this->maskAndLogCredentials($e);
            return;
        }

        parent::report($e);
    }

    protected function maskAndLogCredentials(Throwable $e): void
    {
        $message = $e->getMessage();
        $dbUser = config('database.connections.' . config('database.default') . '.username', '');
        $dbPass = config('database.connections.' . config('database.default') . '.password', '');
        $dbHost = config('database.connections.' . config('database.default') . '.host', '');
        $dbPort = config('database.connections.' . config('database.default') . '.port', '');
        $dbName = config('database.connections.' . config('database.default') . '.database', '');

        if ($dbPass !== '') {
            $message = str_replace($dbPass, '***', $message);
        }
        if ($dbUser !== '') {
            $message = str_replace($dbUser, '***', $message);
        }
        if ($dbHost !== '' && $dbHost !== '127.0.0.1' && $dbHost !== 'localhost') {
            $message = str_replace($dbHost, '[masked_host]', $message);
        }

        logger()->error('QueryException (credential masked): ' . get_class($e) . ' | host=' . $dbHost . ':' . $dbPort . ' | db=' . $dbName . ' | ' . $message);
    }
}
