@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <h1>Pagamento pendente</h1>
        <p>Assim que o pagamento for aprovado, seus materiais aparecem na sua conta.</p>
        <a class="button" href="{{ route('account.dashboard') }}">Ver pedidos</a>
    </div>
</div>
@endsection
