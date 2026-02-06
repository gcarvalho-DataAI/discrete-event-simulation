@extends('layouts.app')

@section('content')
<div class="container">
    <div class="actions" style="justify-content: space-between; align-items: center;">
        <h1>Meus pedidos</h1>
        <a class="button secondary" href="{{ route('account.dashboard') }}">Voltar</a>
    </div>

    @if($orders->isEmpty())
        <div class="card">Nenhum pedido encontrado.</div>
    @else
        @foreach($orders as $order)
            <div class="card">
                <div class="actions" style="justify-content: space-between; align-items: center;">
                    <div>
                        <strong>Pedido #{{ $order->id }}</strong>
                        <span class="badge">{{ $order->status }}</span>
                    </div>
                    <a class="button secondary" href="{{ route('account.orders.detail', $order->id) }}">Detalhes</a>
                </div>
                <ul>
                    @foreach($order->items as $item)
                        <li>{{ $item->name }} (x{{ $item->quantity }})</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    @endif
</div>
@endsection
