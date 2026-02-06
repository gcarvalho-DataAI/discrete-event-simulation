@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <h1>Pagamento falhou</h1>
        <p>Algo deu errado. Tente novamente ou escolha outro metodo.</p>
        <a class="button" href="{{ route('checkout.show') }}">Voltar ao checkout</a>
    </div>
</div>
@endsection
