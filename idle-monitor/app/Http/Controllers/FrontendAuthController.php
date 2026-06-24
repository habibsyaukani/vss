<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Allow both admin and fleet_manager
            if (!in_array($user->role, ['admin', 'fleet_manager'])) {
                Auth::logout();
                return back()->with('error', '403: Access Denied.');
            }

            // Check if user is active
            if (!$user->isActive()) {
                Auth::logout();
                return back()->with('error', 'Your account is inactive.');
            }

            $request->session()->regenerate();
            return redirect('/dashboard')->with('success', 'Welcome back, ' . $user->name);
        }

        return back()->with('error', 'Invalid credentials.');
    }

    /**
     * Handle frontend logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Logged out successfully.');
    }
}
