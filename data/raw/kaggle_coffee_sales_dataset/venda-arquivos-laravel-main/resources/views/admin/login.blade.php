@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Admin - Login</h1>
    <div class="card">
        <form method="post" action="{{ route('admin.login.submit') }}">
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
    </div>
</div>
@endsection
