@extends('layouts.app')

@section('content')
<div class="container">
    <div class="actions" style="justify-content: space-between; align-items: center;">
        <h1>{{ $title }}</h1>
        <div class="actions">
            <a class="button secondary" href="{{ route('admin.dashboard') }}">Voltar</a>
            <a class="button" href="{{ route('admin.ads.create') }}">Novo anuncio</a>
        </div>
    </div>

    @if($ads->isEmpty())
        <div class="card">Nenhum anuncio encontrado.</div>
    @else
        @foreach($ads as $ad)
            <div class="card">
                <div class="actions" style="justify-content: space-between; align-items: center;">
                    <div>
                        <strong>{{ $ad->title }}</strong>
                        <div class="text-muted">{{ $ad->price_text }}</div>
                    </div>
                    <div class="actions">
                        <a class="button secondary" href="{{ route('admin.ads.edit', $ad) }}">Editar</a>
                        <form method="post" action="{{ route('admin.ads.toggle', $ad) }}">
                            @csrf
                            <button class="button secondary" type="submit">{{ $ad->active ? 'Pausar' : 'Ativar' }}</button>
                        </form>
                        <form method="post" action="{{ route('admin.ads.delete', $ad) }}">
                            @csrf
                            <button class="button secondary" type="submit">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
