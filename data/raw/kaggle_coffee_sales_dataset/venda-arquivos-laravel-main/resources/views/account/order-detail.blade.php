@extends('layouts.app')

@section('content')
<div class="container">
    <div class="actions" style="justify-content: space-between; align-items: center;">
        <h1>Pedido #{{ $order->id }}</h1>
        <div class="actions">
            <a class="button secondary" href="{{ route('account.orders') }}">Voltar</a>
            <a class="button" href="{{ route('account.orders.receipt', $order->id) }}">Nota</a>
        </div>
    </div>

    <div class="card">
        <p>Status: <span class="badge">{{ $order->status }}</span></p>
        <p>Total: R$ {{ number_format($order->total_cents / 100, 2, ',', '.') }}</p>

        <h3>Itens</h3>
        <ul>
            @foreach($order->items as $item)
                <li>
                    {{ $item->name }} (x{{ $item->quantity }})
                    @if($item->ad && $item->ad->file_url)
                        - <a href="{{ $item->ad->file_url }}" target="_blank">Baixar</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
