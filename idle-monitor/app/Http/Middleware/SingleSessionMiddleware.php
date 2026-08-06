<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SingleSessionMiddleware
{
    /**
     * Waktu sesi maksimal (dalam menit)
     */
    const SESSION_LIFETIME_MINUTES = 60;

    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            \Log::warning('[SINGLE-SESSION] Auth::check() = false, redirecting to login', [
                'url'        => $request->url(),
                'session_id' => session()->getId(),
                'has_session'=> !empty(session()->all()),
            ]);
            return $next($request);
        }

        $user           = Auth::user();

        // ── Admin bebas login dari banyak perangkat/tab sekaligus ───────────
        if ($user->role === 'admin') {
            return $next($request);
        }

        $sessionToken   = session('session_token');

        // ── Cek 1: Token sesi cocok dengan DB? ────────────────────────────
        // Jika tidak cocok → ada orang lain yang sudah login dengan akun ini
        if (!$sessionToken || $user->session_token !== $sessionToken) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with(
                'error',
                'Sesi Anda telah berakhir karena akun ini digunakan di perangkat lain.'
            );
        }

        // ── Cek 2: Sesi sudah lebih dari 1 jam? ───────────────────────────
        if ($user->login_at && now()->diffInMinutes($user->login_at) >= self::SESSION_LIFETIME_MINUTES) {
            // Bersihkan token di DB
            $user->update(['session_token' => null, 'login_at' => null]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with(
                'error',
                'Sesi Anda telah berakhir setelah 1 jam. Silakan login kembali.'
            );
        }

        return $next($request);
    }
}
