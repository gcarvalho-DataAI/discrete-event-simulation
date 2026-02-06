@extends('layouts.app')

@section('content')
<div class="container">
    <div class="actions" style="justify-content: space-between; align-items: center;">
        <h1>Perfil</h1>
        <a class="button secondary" href="{{ route('account.dashboard') }}">Voltar</a>
    </div>

    <div class="card">
        <form method="post" action="{{ route('account.profile.update') }}">
            @csrf
            <div class="form-group">
                <label>Nome completo</label>
                <input type="text" name="full_name" value="{{ old('full_name', $user->full_name) }}">
            </div>
            <div class="form-group">
                <label>CPF</label>
                <input type="text" name="cpf" value="{{ old('cpf', $user->cpf) }}">
            </div>
            <div class="form-group">
                <label>Data de nascimento</label>
                <input type="date" name="birth_date" value="{{ old('birth_date', optional($user->birth_date)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label>Telefone</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="form-group">
                <label>Endereco</label>
                <input type="text" name="address_line1" value="{{ old('address_line1', $user->address_line1) }}">
            </div>
            <div class="form-group">
                <label>Complemento</label>
                <input type="text" name="address_line2" value="{{ old('address_line2', $user->address_line2) }}">
            </div>
            <div class="form-group">
                <label>Cidade</label>
                <input type="text" name="city" value="{{ old('city', $user->city) }}">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <input type="text" name="state" value="{{ old('state', $user->state) }}">
            </div>
            <div class="form-group">
                <label>CEP</label>
                <input type="text" name="zip" value="{{ old('zip', $user->zip) }}">
            </div>
            <button class="button" type="submit">Salvar</button>
        </form>
    </div>
</div>
@endsection
