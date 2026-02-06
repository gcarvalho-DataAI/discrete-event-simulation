<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MercadoPagoService
{
    public function createPreference(array $input, string $accessToken, string $apiBaseUrl): array
    {
        $excluded = $this->buildExcludedPaymentTypes($input['payment_method'] ?? null);

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post(rtrim($apiBaseUrl, '/') . '/checkout/preferences', [
                'items' => $input['items'],
                'payer' => ['email' => $input['payer_email']],
                'back_urls' => $input['back_urls'],
                'auto_return' => 'approved',
                'notification_url' => $input['notification_url'],
                'external_reference' => $input['external_reference'],
                'payment_methods' => [
                    'excluded_payment_types' => $excluded,
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Erro ao criar preferencia: ' . $response->status() . ' ' . $response->body());
        }

        return $response->json();
    }

    public function createPixPayment(array $input, string $accessToken, string $apiBaseUrl): array
    {
        $idempotencyKey = (string) Str::uuid();
        $response = Http::withToken($accessToken)
            ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
            ->acceptJson()
            ->post(rtrim($apiBaseUrl, '/') . '/v1/payments', [
                'transaction_amount' => $input['amount'],
                'description' => $input['description'],
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $input['payer']['email'],
                    'first_name' => $input['payer']['first_name'],
                    'last_name' => $input['payer']['last_name'],
                    'identification' => [
                        'type' => 'CPF',
                        'number' => $input['payer']['cpf'],
                    ],
                ],
                'notification_url' => $input['notification_url'],
                'external_reference' => $input['external_reference'],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Erro ao criar pagamento Pix: ' . $response->status() . ' ' . $response->body());
        }

        return $response->json();
    }

    public function fetchPayment(string $paymentId, string $accessToken, string $apiBaseUrl): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get(rtrim($apiBaseUrl, '/') . '/v1/payments/' . $paymentId);

        if (!$response->successful()) {
            throw new \RuntimeException('Erro ao buscar pagamento: ' . $response->status() . ' ' . $response->body());
        }

        return $response->json();
    }

    private function buildExcludedPaymentTypes(?string $method): array
    {
        if ($method === 'pix') {
            return [
                ['id' => 'credit_card'],
                ['id' => 'debit_card'],
                ['id' => 'ticket'],
            ];
        }
        if ($method === 'card') {
            return [
                ['id' => 'pix'],
                ['id' => 'ticket'],
            ];
        }
        return [
            ['id' => 'ticket'],
        ];
    }
}
