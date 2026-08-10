<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FrontendAuthController extends Controller
{
    /**
     * Show frontend login form
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        
        return view('frontend.auth.login');
    }

    /**
     * Handle frontend login (Admin + Fleet Manager)
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|min:6',
        ]);

        // Cari user terlebih dahulu sebelum Auth::attempt
        $user = User::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->with('error', 'Username atau password salah.');
        }

        // Cek role
        if (!in_array($user->role, ['admin', 'fleet_manager'])) {
            return back()->with('error', '403: Access Denied.');
        }

        // Cek status aktif
        if (!$user->isActive()) {
            return back()->with('error', 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        // ── Single Device Check (Safe Execution) ──────────────────────────────
        try {
            if ($user->role !== 'admin' && !empty($user->session_token) && !empty($user->login_at)) {
                $loginTime = \Carbon\Carbon::parse($user->login_at);
                $sessionExpired = now()->diffInMinutes($loginTime) >= 60;

                if (!$sessionExpired) {
                    // Update session_token untuk perangkat baru (Kick sesi lama, izinkan login baru)
                    // Hal ini mencegah 500 Server Error & memblokir pengguna yang lupa logout
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('[LOGIN-CHECK] Session token check ignored', ['error' => $e->getMessage()]);
        }

        // Login berhasil — buat token sesi baru
        Auth::login($user);

        $sessionToken = Str::random(60);

        // Simpan token ke DB
        $user->update([
            'session_token' => $sessionToken,
            'login_at'      => now(),
        ]);

        // Simpan token ke session browser
        $request->session()->regenerate();
        session(['session_token' => $sessionToken]);
        session()->save();

        // DEBUG: log session state after login
        \Log::info('[LOGIN-DEBUG] Login success', [
            'user'          => $user->username,
            'role'          => $user->role,
            'status'        => $user->status,
            'session_id'    => session()->getId(),
            'auth_check'    => Auth::check(),
            'session_token' => substr($sessionToken, 0, 10) . '...',
        ]);

        return redirect('/dashboard')->with('success', 'Selamat datang, ' . $user->name . '!');
    }

    /**
     * Handle frontend logout
     * 
     * IMPROVEMENTS:
     * - Clear session token from database
     * - Flash new CSRF token to prevent "Page Expired"
     * - Redirect to frontend login
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        // Bersihkan token di DB saat logout
        if ($user) {
            $user->update(['session_token' => null, 'login_at' => null]);
        }

        // Logout user
        Auth::logout();
        
        // Invalidate current session
        $request->session()->invalidate();
        
        // Generate fresh CSRF token for next request
        $request->session()->regenerateToken();

        // Redirect to frontend login with fresh token
        return redirect('/login')
            ->with('success', 'Anda telah logout.')
            ->with('_token', csrf_token()); // Fresh token for next page
    }
}
