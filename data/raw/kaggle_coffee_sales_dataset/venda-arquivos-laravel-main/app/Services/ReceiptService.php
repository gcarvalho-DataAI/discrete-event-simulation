<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class ReceiptService
{
    public function sendReceipt(Order $order, string $toEmail): void
    {
        $apiKey = env('RESEND_EMAIL_API_KEY');
        $from = env('MAIL_FROM_ADDRESS', 'contato@example.com');
        $fromName = env('MAIL_FROM_NAME', 'Loja');

        if (!$apiKey) {
            return;
        }

        $subject = 'Comprovante de compra #' . $order->id;
        $html = $this->buildHtml($order);

        Http::withToken($apiKey)
            ->acceptJson()
            ->post('https://api.resend.com/emails', [
                'from' => $fromName . ' <' . $from . '>',
                'to' => [$toEmail],
                'subject' => $subject,
                'html' => $html,
            ]);
    }

    private function buildHtml(Order $order): string
    {
        $rows = '';
        foreach ($order->items as $item) {
            $rows .= '<tr>'
                . '<td style="padding:6px 0;">' . e($item->name) . '</td>'
                . '<td style="padding:6px 0;">' . (int) $item->quantity . '</td>'
                . '<td style="padding:6px 0;">R$ ' . number_format($item->unit_price_cents / 100, 2, ',', '.') . '</td>'
                . '</tr>';
        }

        $total = 'R$ ' . number_format($order->total_cents / 100, 2, ',', '.');

        return '<h2>Obrigado pela compra!</h2>'
            . '<p>Pedido #' . $order->id . '</p>'
            . '<table style="width:100%; border-collapse:collapse;">'
            . '<thead><tr><th align="left">Item</th><th align="left">Qtd</th><th align="left">Valor</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>'
            . '<p><strong>Total:</strong> ' . $total . '</p>';
    }
}
