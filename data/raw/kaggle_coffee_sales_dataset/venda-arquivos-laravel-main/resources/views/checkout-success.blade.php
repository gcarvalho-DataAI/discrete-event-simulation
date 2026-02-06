@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <h1>Pagamento aprovado</h1>
        <p>Seu pedido foi confirmado. Acesse sua conta para baixar os arquivos.</p>
        <a class="button" href="{{ route('account.dashboard') }}">Ir para minha conta</a>
    </div>
</div>
@endsection
