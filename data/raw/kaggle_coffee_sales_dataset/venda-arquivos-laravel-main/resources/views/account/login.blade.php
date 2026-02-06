@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Entrar</h1>
    <div class="card">
        <form method="post" action="{{ route('account.login.submit') }}">
            @csrf
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="password" required>
            </div>
            <button class="button" type="submit">Entrar</button>
        </form>
        <p class="text-muted">Nao tem conta? <a href="{{ route('account.register') }}">Criar agora</a></p>
    </div>
</div>
@endsection
