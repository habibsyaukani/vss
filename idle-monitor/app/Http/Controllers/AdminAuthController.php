<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    /**
     * Show admin login form
     */
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect('/admin/dashboard');
        }
        
        return view('admin.auth.login');
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Check if user is admin
            if (!$user->isAdmin()) {
                Auth::logout();
                return back()->with('error', '403: Access Denied. Admin role required.');
            }

            // Check if user is active
            if (!$user->isActive()) {
                Auth::logout();
                return back()->with('error', 'Your account is inactive.');
            }

            $request->session()->regenerate();
            return redirect('/admin/dashboard')->with('success', 'Welcome back, ' . $user->name);
        }

        return back()->with('error', 'Invalid credentials.');
    }

    /**
     * Handle admin logout
     * 
     * IMPROVEMENTS:
     * - Flash new CSRF token before logout to prevent "Page Expired"
     * - Clear all session data properly
     * - Redirect to admin login
     */
    public function logout(Request $request)
    {
        // Logout user
        Auth::logout();
        
        // Invalidate current session
        $request->session()->invalidate();
        
        // Generate fresh CSRF token for next request
        $request->session()->regenerateToken();
        
        // Flash success message with fresh session
        return redirect('/admin/login')
            ->with('success', 'Logged out successfully.')
            ->with('_token', csrf_token()); // Fresh token for next page
    }
}
