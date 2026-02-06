@extends('layouts.app')

@section('content')
<div class="container">
    <div class="actions" style="justify-content: space-between; align-items: center;">
        <h1>Pagamentos</h1>
        <a class="button secondary" href="{{ route('admin.dashboard') }}">Voltar</a>
    </div>

    <div class="card">
        <h3>Configuracoes Mercado Pago</h3>
        <form method="post" action="{{ route('admin.payments.update') }}">
            @csrf
            <div class="form-group">
                <label>Access Token</label>
                <input type="text" name="mp_access_token" value="{{ $settings['mp_access_token'] }}">
            </div>
            <div class="form-group">
                <label>Public Key</label>
                <input type="text" name="mp_public_key" value="{{ $settings['mp_public_key'] }}">
            </div>
            <div class="form-group">
                <label>Webhook Secret</label>
                <input type="text" name="mp_webhook_secret" value="{{ $settings['mp_webhook_secret'] }}">
            </div>
            <div class="form-group">
                <label>API Base URL</label>
                <input type="text" name="mp_api_base_url" value="{{ $settings['mp_api_base_url'] }}">
            </div>
            <div class="form-group">
                <label>Metodo padrao (pix/card)</label>
                <select name="mp_payment_method">
                    <option value="" {{ empty($settings['mp_payment_method']) ? 'selected' : '' }}>Pix + Cartao</option>
                    <option value="pix" {{ $settings['mp_payment_method'] === 'pix' ? 'selected' : '' }}>Somente Pix</option>
                    <option value="card" {{ $settings['mp_payment_method'] === 'card' ? 'selected' : '' }}>Somente Cartao</option>
                </select>
            </div>
            <button class="button" type="submit">Salvar</button>
        </form>
    </div>

    <div class="card">
        <h3>Webhooks</h3>
        <form method="post" action="{{ route('admin.payments.hooks.store') }}">
            @csrf
            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>URL</label>
                <input type="url" name="url" required>
            </div>
            <div class="form-group">
                <label>Provider</label>
                <input type="text" name="provider" value="mercadopago" required>
            </div>
            <div class="form-group">
                <label>Evento</label>
                <input type="text" name="event" value="payment.paid" required>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="active" checked> Ativo</label>
            </div>
            <button class="button" type="submit">Adicionar hook</button>
        </form>

        <table class="table" style="margin-top:16px;">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Evento</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($hooks as $hook)
                    <tr>
                        <td>{{ $hook->name }}</td>
                        <td>{{ $hook->event }}</td>
                        <td>{{ $hook->active ? 'Ativo' : 'Inativo' }}</td>
                        <td class="actions">
                            <form method="post" action="{{ route('admin.payments.hooks.test', $hook) }}">
                                @csrf
                                <button class="button secondary" type="submit">Testar</button>
                            </form>
                            <form method="post" action="{{ route('admin.payments.hooks.delete', $hook) }}">
                                @csrf
                                <button class="button secondary" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
