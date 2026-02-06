@extends('layouts.app')

@section('content')
<div class="container">
    <div class="actions" style="justify-content: space-between; align-items: center;">
        <h1>Configurar MFA</h1>
        <a class="button secondary" href="{{ route('admin.dashboard') }}">Voltar</a>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
        <div class="card">
            <h3>1. Escaneie o QR Code</h3>
            <p class="text-muted">Use Google Authenticator, Authy, 1Password, etc.</p>
            <p><strong>Chave:</strong> {{ $secret }}</p>
            @if($user->mfa_enabled)
                <form method="post" action="{{ route('admin.mfa.disable') }}">
                    @csrf
                    <button class="button secondary" type="submit">Desativar MFA</button>
                </form>
            @endif
        </div>

        <div class="card">
            <h3>2. Confirme o codigo</h3>
            <img class="responsive" src="{{ $qr }}" alt="QR Code">
            @if(!$user->mfa_enabled)
                <form method="post" action="{{ route('admin.mfa.enable') }}">
                    @csrf
                    <div class="form-group">
                        <label>Codigo</label>
                        <input type="text" name="code" required>
                    </div>
                    <button class="button" type="submit">Ativar MFA</button>
                </form>
            @else
                <p class="text-muted">MFA ja esta ativo.</p>
            @endif
        </div>
    </div>
</div>
@endsection
