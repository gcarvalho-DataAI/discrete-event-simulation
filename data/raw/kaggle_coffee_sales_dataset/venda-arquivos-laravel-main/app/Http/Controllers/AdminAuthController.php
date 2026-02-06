<?php

namespace App\Http\Controllers;

use App\Models\MfaChallenge;
use App\Models\User;
use App\Services\MfaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        $this->ensureAdminUserFromEnv();
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $this->ensureAdminUserFromEnv();

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::query()->where('email', strtolower($credentials['email']))->first();
        if (!$user || $user->role !== 'ADMIN' || !Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Credenciais invalidas.']);
        }

        if ($user->mfa_enabled) {
            $token = (string) Str::uuid();
            MfaChallenge::query()->create([
                'token' => $token,
                'user_id' => $user->id,
                'expires_at' => now()->addMinutes(10),
            ]);

            return redirect()->route('admin.mfa', ['token' => $token]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function showMfa(Request $request)
    {
        $token = (string) $request->query('token');
        $challenge = MfaChallenge::query()->where('token', $token)->first();
        if (!$challenge || $challenge->expires_at->isPast()) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Sessao expirada.']);
        }

        return view('admin.mfa', ['token' => $token]);
    }

    public function verifyMfa(Request $request, MfaService $mfa)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'code' => 'required|string',
        ]);

        $challenge = MfaChallenge::query()->where('token', $data['token'])->first();
        if (!$challenge || $challenge->expires_at->isPast()) {
            return redirect()->route('admin.login')->withErrors(['email' => 'Sessao expirada.']);
        }

        $user = $challenge->user;
        if (!$user || !$user->mfa_secret) {
            return redirect()->route('admin.login')->withErrors(['email' => 'MFA invalido.']);
        }

        if (!$mfa->verifyToken($user->mfa_secret, $data['code'])) {
            return back()->withErrors(['code' => 'Codigo invalido.']);
        }

        $challenge->delete();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    private function ensureAdminUserFromEnv(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        if (!$email || !$password) {
            return;
        }

        $normalized = strtolower(trim($email));
        $user = User::query()->where('email', $normalized)->first();
        if ($user) {
            if ($user->role !== 'ADMIN') {
                $user->update(['role' => 'ADMIN']);
            }
            return;
        }

        User::query()->create([
            'name' => 'Admin',
            'email' => $normalized,
            'password' => Hash::make($password),
            'role' => 'ADMIN',
        ]);
    }
}
