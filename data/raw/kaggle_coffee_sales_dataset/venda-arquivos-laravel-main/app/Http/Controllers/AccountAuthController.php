<?php

namespace App\Http\Controllers;

use App\Models\MfaChallenge;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountAuthController extends Controller
{
    public function showLogin()
    {
        $user = Auth::user();
        if ($user) {
            return $user->role === 'ADMIN'
                ? redirect()->route('admin.dashboard')
                : redirect()->route('account.dashboard');
        }

        return view('account.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user && $user->role === 'ADMIN') {
                if ($user->mfa_enabled) {
                    $token = (string) Str::uuid();
                    MfaChallenge::query()->create([
                        'token' => $token,
                        'user_id' => $user->id,
                        'expires_at' => now()->addMinutes(10),
                    ]);

                    return redirect()->route('admin.mfa', ['token' => $token]);
                }

                return redirect()->route('admin.dashboard');
            }

            return redirect()->intended(route('account.dashboard'));
        }

        return back()->withErrors(['email' => 'Credenciais invalidas.']);
    }

    public function showRegister()
    {
        return view('account.register');
    }

    public function register(Request $request, CartService $cart)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::query()->create([
            'name' => $data['name'] ?: 'Cliente',
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'role' => 'CUSTOMER',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        if ($cart->totalItems() > 0) {
            return redirect()->route('checkout.show');
        }

        return redirect()->intended(route('account.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
