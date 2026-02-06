@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <h1>Comprovante</h1>
        <p>Pedido #{{ $order->id }}</p>
        <p>Cliente: {{ $user->full_name ?? $user->name ?? $user->email }}</p>
        <p>CPF: {{ $user->cpf ?? '-' }}</p>
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qtd</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>R$ {{ number_format($item->unit_price_cents / 100, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p><strong>Total:</strong> R$ {{ number_format($order->total_cents / 100, 2, ',', '.') }}</p>
    </div>
</div>
@endsection
