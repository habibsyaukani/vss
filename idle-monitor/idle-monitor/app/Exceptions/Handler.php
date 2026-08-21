<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            $isAdmin = $request->is('admin') || $request->is('admin/*');
            $loginRoute = $isAdmin ? route('admin.login.form') : route('login');

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Sesi telah habis.', 'redirect' => $loginRoute], 419);
            }
            return redirect($loginRoute)->with('error', 'Sesi Anda telah habis. Silakan login kembali.');
        });
    }
}
