@extends('layouts.app')

@section('content')
<div class="container">
    <div class="actions" style="justify-content: space-between; align-items: center;">
        <h1>Dashboard admin</h1>
        <form method="post" action="{{ route('admin.logout') }}">
            @csrf
            <button class="button secondary" type="submit">Sair</button>
        </form>
    </div>

    <div class="card">
        <p>Logado como {{ $user->email }}.</p>
        <div class="actions">
            <a class="button secondary" href="{{ route('admin.mfa.setup') }}">MFA</a>
            <a class="button" href="{{ route('admin.ads.create') }}">Novo anuncio</a>
            <a class="button secondary" href="{{ route('admin.payments') }}">Pagamentos</a>
        </div>
    </div>

    <div class="grid grid-3">
        <div class="card">
            <h3>Ativos</h3>
            <p>{{ $activeAds->count() }} anuncios</p>
            <a href="{{ route('admin.ads.active') }}">Ver ativos</a>
        </div>
        <div class="card">
            <h3>Pausados</h3>
            <p>{{ $pausedAds->count() }} anuncios</p>
            <a href="{{ route('admin.ads.paused') }}">Ver pausados</a>
        </div>
        <div class="card">
            <h3>Pedidos</h3>
            <p>{{ $orders->count() }} recentes</p>
        </div>
    </div>
</div>
@endsection
