@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Criar conta</h1>
    <div class="card">
        <form method="post" action="{{ route('account.register.submit') }}">
            @csrf
            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="name">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirmar senha</label>
                <input type="password" name="password_confirmation" required>
            </div>
            <button class="button" type="submit">Criar conta</button>
        </form>
    </div>
</div>
@endsection
