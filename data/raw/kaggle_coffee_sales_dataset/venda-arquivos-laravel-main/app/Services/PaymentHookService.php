<?php

namespace App\Services;

use App\Models\PaymentHook;
use App\Models\PaymentHookDelivery;
use Illuminate\Support\Facades\Http;

class PaymentHookService
{
    public function dispatch(string $event, array $payload, ?string $provider = null): void
    {
        $hooks = PaymentHook::query()
            ->where('active', true)
            ->where('event', $event)
            ->get();

        if ($provider) {
            $hooks = $hooks->filter(function (PaymentHook $hook) use ($provider) {
                return $hook->provider === $provider || $hook->provider === 'outro';
            });
        }

        foreach ($hooks as $hook) {
            $this->deliver($hook, $event, $payload);
        }
    }

    private function deliver(PaymentHook $hook, string $event, array $payload): void
    {
        try {
            $response = Http::acceptJson()->post($hook->url, [
                'event' => $event,
                'payload' => $payload,
            ]);

            PaymentHookDelivery::query()->create([
                'hook_id' => $hook->id,
                'event' => $event,
                'payload' => $payload,
                'status_code' => $response->status(),
            ]);
        } catch (\Throwable $error) {
            PaymentHookDelivery::query()->create([
                'hook_id' => $hook->id,
                'event' => $event,
                'payload' => $payload,
                'error' => $error->getMessage(),
            ]);
        }
    }
}
