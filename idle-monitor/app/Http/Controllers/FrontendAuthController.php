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

        // ── Cek Single Device: apakah sudah ada sesi aktif? ───────────────
        // Admin bisa login dari mana saja (bypass single session)
        if ($user->role !== 'admin' && $user->session_token !== null) {
            // Cek apakah sesi yang ada sudah expired (> 1 jam)
            $sessionExpired = $user->login_at
                ? now()->diffInMinutes($user->login_at) >= 60
                : true;

            if (!$sessionExpired) {
                return back()->with(
                    'error',
                    'Akun ini sedang aktif digunakan di perangkat lain. ' .
                    'Tidak bisa login lebih dari 1 perangkat secara bersamaan.'
                );
            }
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
