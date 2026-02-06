@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Pagamento Pix</h1>
    <div class="card">
        <p>Pedido #{{ $order->id }}</p>
        @if($qr_base64)
            <img class="responsive" src="data:image/png;base64,{{ $qr_base64 }}" alt="QR Code">
        @endif
        @if($qr_code)
            <p>Codigo Pix:</p>
            <textarea rows="3" readonly>{{ $qr_code }}</textarea>
        @endif
        <p class="text-muted">Aguardando confirmacao do pagamento.</p>
        <a class="button" href="{{ route('checkout.pending') }}">Ja paguei</a>
    </div>
</div>
@endsection
