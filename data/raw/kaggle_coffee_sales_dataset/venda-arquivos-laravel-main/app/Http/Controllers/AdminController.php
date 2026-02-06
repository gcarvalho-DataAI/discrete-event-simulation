<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AdsService;
use App\Services\MfaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard(AdsService $ads)
    {
        $user = Auth::user();
        $allAds = $ads->listAll();
        $activeAds = $allAds->where('active', true);
        $pausedAds = $allAds->where('active', false);

        $orders = Order::query()->orderByDesc('created_at')->limit(5)->get();

        return view('admin.dashboard', [
            'user' => $user,
            'activeAds' => $activeAds,
            'pausedAds' => $pausedAds,
            'orders' => $orders,
        ]);
    }

    public function mfaSetup(MfaService $mfa)
    {
        $user = Auth::user();
        if (!$user->mfa_secret) {
            $user->mfa_secret = $mfa->generateSecret();
            $user->mfa_enabled = false;
            $user->save();
        }

        $issuer = env('MFA_ISSUER', 'Solver Mind');
        $otpauth = $mfa->buildOtpAuthUrl($user->email, $user->mfa_secret, $issuer);
        $qr = $mfa->generateQrDataUrl($otpauth);

        return view('admin.mfa-setup', [
            'user' => $user,
            'secret' => $user->mfa_secret,
            'qr' => $qr,
        ]);
    }

    public function enableMfa(Request $request, MfaService $mfa)
    {
        $user = Auth::user();
        $data = $request->validate([
            'code' => 'required|string',
        ]);

        if (!$user->mfa_secret || !$mfa->verifyToken($user->mfa_secret, $data['code'])) {
            return redirect()->route('admin.mfa.setup')->withErrors(['code' => 'Codigo invalido.']);
        }

        $user->update(['mfa_enabled' => true]);
        return redirect()->route('admin.dashboard')->with('status', 'MFA ativado.');
    }

    public function disableMfa()
    {
        $user = Auth::user();
        $user->update([
            'mfa_enabled' => false,
            'mfa_secret' => null,
        ]);

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'MFA desativado.');
    }
}
