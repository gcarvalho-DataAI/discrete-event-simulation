@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Confirmar MFA</h1>
    <div class="card">
        <form method="post" action="{{ route('admin.mfa.verify') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group">
                <label>Codigo</label>
                <input type="text" name="code" required>
            </div>
            <button class="button" type="submit">Confirmar</button>
        </form>
    </div>
</div>
@endsection
