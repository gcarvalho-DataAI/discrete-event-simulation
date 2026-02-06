<?php

namespace App\Services;

use App\Models\AppSetting;

class PaymentConfigService
{
    public function get(): array
    {
        return [
            'mp_access_token' => AppSetting::getValue('mp_access_token', env('MP_ACCESS_TOKEN')),
            'mp_webhook_secret' => AppSetting::getValue('mp_webhook_secret', env('MP_WEBHOOK_SECRET')),
            'mp_api_base_url' => AppSetting::getValue('mp_api_base_url', env('MP_API_BASE_URL', 'https://api.mercadopago.com')),
            'mp_public_key' => AppSetting::getValue('mp_public_key', env('MP_PUBLIC_KEY')),
            'mp_payment_method' => AppSetting::getValue('mp_payment_method', env('MP_PAYMENT_METHOD')),
        ];
    }
}
