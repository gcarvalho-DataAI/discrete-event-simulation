@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Carrinho</h1>
    <p class="text-muted">{{ $total_items }} itens adicionados.</p>

    @if($items->isEmpty())
        <div class="card">Seu carrinho esta vazio.</div>
    @else
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Qtd</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item['ad']->title }}</td>
                            <td>
                                <form method="post" action="{{ route('cart.update') }}" class="actions">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $item['ad']->code }}">
                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" style="max-width: 80px;">
                                    <button class="button secondary" type="submit">Atualizar</button>
                                </form>
                            </td>
                            <td>R$ {{ number_format($item['line_total_cents'] / 100, 2, ',', '.') }}</td>
                            <td>
                                <form method="post" action="{{ route('cart.remove') }}">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $item['ad']->code }}">
                                    <button class="button secondary" type="submit">Remover</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p><strong>Total:</strong> R$ {{ number_format($total_cents / 100, 2, ',', '.') }}</p>
            <div class="actions">
                <form method="post" action="{{ route('cart.clear') }}">
                    @csrf
                    <button class="button secondary" type="submit">Limpar carrinho</button>
                </form>
                <a class="button" href="{{ route('checkout.show') }}">Ir para checkout</a>
            </div>
        </div>
    @endif
</div>
@endsection
