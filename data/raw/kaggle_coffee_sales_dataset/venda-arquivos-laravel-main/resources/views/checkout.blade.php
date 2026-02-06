@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Checkout</h1>

    @if($items->isEmpty())
        <div class="card">Carrinho vazio.</div>
    @else
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div class="card">
                <h3>Resumo</h3>
                <ul>
                    @foreach($items as $item)
                        <li>{{ $item['ad']->title }} (x{{ $item['quantity'] }})</li>
                    @endforeach
                </ul>
                <p><strong>Total:</strong> R$ {{ number_format($total_cents / 100, 2, ',', '.') }}</p>
            </div>

            <div class="card">
                <h3>Dados para pagamento</h3>
                <form method="post" action="{{ route('checkout.submit') }}">
                    @csrf
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="{{ $user->email }}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Cliente</label>
                        <input type="text" value="{{ $user->full_name }}" readonly>
                    </div>
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" value="{{ $user->cpf }}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Metodo</label>
                        <select name="payment_method">
                            <option value="">Pix + Cartao</option>
                            <option value="pix">Pix</option>
                            <option value="card">Cartao</option>
                        </select>
                    </div>
                    <p class="text-muted">Para atualizar dados do cliente, altere em <a href="{{ route('account.profile') }}">Perfil</a>.</p>
                    <button class="button" type="submit">Pagar agora</button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
