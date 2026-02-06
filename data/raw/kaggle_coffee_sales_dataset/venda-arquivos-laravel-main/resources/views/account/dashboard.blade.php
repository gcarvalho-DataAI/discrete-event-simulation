@extends('layouts.app')

@section('content')
<div class="container">
    <div class="actions" style="justify-content: space-between; align-items: center;">
        <h1>Minha conta</h1>
        <form method="post" action="{{ route('account.logout') }}">
            @csrf
            <button class="button secondary" type="submit">Sair</button>
        </form>
    </div>

    <div class="card">
        <p>Ola, {{ $user->name ?? $user->email }}.</p>
        <div class="actions">
            <a class="button secondary" href="{{ route('account.profile') }}">Completar cadastro</a>
            <a class="button" href="{{ route('account.orders') }}">Ver pedidos</a>
        </div>
    </div>

    <h2>Pedidos recentes</h2>
    @if($orders->isEmpty())
        <div class="card">Nenhum pedido encontrado.</div>
    @else
        @foreach($orders as $order)
            <div class="card">
                <div class="actions" style="justify-content: space-between; align-items: center;">
                    <div>
                        <strong>Pedido #{{ $order->id }}</strong><br>
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
