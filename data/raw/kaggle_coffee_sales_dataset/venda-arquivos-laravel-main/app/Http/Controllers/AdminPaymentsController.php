<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\PaymentHook;
use App\Services\PaymentHookService;
use Illuminate\Http\Request;

class AdminPaymentsController extends Controller
{
    public function index()
    {
        $settings = [
            'mp_access_token' => AppSetting::getValue('mp_access_token', env('MP_ACCESS_TOKEN')),
            'mp_public_key' => AppSetting::getValue('mp_public_key', env('MP_PUBLIC_KEY')),
            'mp_webhook_secret' => AppSetting::getValue('mp_webhook_secret', env('MP_WEBHOOK_SECRET')),
            'mp_api_base_url' => AppSetting::getValue('mp_api_base_url', env('MP_API_BASE_URL', 'https://api.mercadopago.com')),
            'mp_payment_method' => AppSetting::getValue('mp_payment_method', env('MP_PAYMENT_METHOD')),
        ];
        $hooks = PaymentHook::query()->orderByDesc('created_at')->get();

        return view('admin.payments', [
            'settings' => $settings,
            'hooks' => $hooks,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'mp_access_token' => 'nullable|string',
            'mp_public_key' => 'nullable|string',
            'mp_webhook_secret' => 'nullable|string',
            'mp_api_base_url' => 'nullable|string',
            'mp_payment_method' => 'nullable|string',
        ]);

        foreach ($data as $key => $value) {
            AppSetting::setValue($key, $value);
        }

        return redirect()->route('admin.payments')->with('status', 'Configuracoes salvas.');
    }

    public function storeHook(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'url' => 'required|url',
            'provider' => 'required|string',
            'event' => 'required|string',
            'active' => 'nullable|string',
        ]);

        PaymentHook::query()->create([
            'name' => $data['name'],
            'url' => $data['url'],
            'provider' => $data['provider'],
            'event' => $data['event'],
            'active' => isset($data['active']) && $data['active'] === 'on',
        ]);

        return redirect()->route('admin.payments');
    }

    public function deleteHook(PaymentHook $hook)
    {
        $hook->delete();
        return redirect()->route('admin.payments');
    }

    public function testHook(PaymentHook $hook, PaymentHookService $service)
    {
        $service->dispatch('payment.test', [
            'hookId' => $hook->id,
            'message' => 'Teste de webhook',
        ], $hook->provider);

        return redirect()->route('admin.payments');
    }
}
