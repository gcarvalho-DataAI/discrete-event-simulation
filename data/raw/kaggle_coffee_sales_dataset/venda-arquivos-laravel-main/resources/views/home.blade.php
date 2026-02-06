@extends('layouts.app')

@section('content')
<div class="container">
    <div class="hero">
        <div class="eyebrow">Loja oficial</div>
        <h1>Materiais digitais para apoiar a educacao infantil com carinho.</h1>
        <p class="text-muted">
            Explore atividades, e-books, jogos pedagogicos e kits criativos para criancas.
            Conteudos prontos para sala de aula e para casa.
        </p>
    </div>

    <div class="section-title">
        <h2>Materiais disponiveis</h2>
        <p class="text-muted">Apenas anuncios ativos aparecem aqui.</p>
    </div>

    <div class="toolbar">
        <strong>{{ $ads->count() }} produtos</strong>
        <div class="actions">
            <label class="text-muted">Ordenar por:</label>
            <select>
                <option>Mais relevantes</option>
            </select>
        </div>
    </div>

    <div class="grid grid-3" style="margin-top: 18px;">
        @forelse($ads as $ad)
            <div class="card product-card">
                @if(!empty($ad->images))
                    <div class="media-square carousel" data-carousel>
                        @foreach($ad->images as $index => $image)
                            <img src="{{ $image }}" alt="{{ $ad->title }}" class="media-img{{ $index === 0 ? ' is-active' : '' }}">
                        @endforeach
                        @if(count($ad->images) > 1)
                            <button class="carousel-btn prev" type="button" aria-label="Anterior">‹</button>
                            <button class="carousel-btn next" type="button" aria-label="Proxima">›</button>
                        @endif
                    </div>
                @endif
                <div class="product-body">
                    <h3>{{ $ad->title }}</h3>
                    <div class="product-meta">Material digital</div>
                    <p class="text-muted clamp-3">{{ $ad->description }}</p>
                </div>
                <div class="price">{{ $ad->price_text ?: 'R$ ' . number_format($ad->price_cents / 100, 2, ',', '.') }}</div>
                <div class="actions row product-actions">
                    <a class="button secondary" href="{{ route('ad.show', $ad) }}">Ver anuncio</a>
                    <form method="post" action="{{ route('cart.add') }}" class="inline-form">
                        @csrf
                        <input type="hidden" name="id" value="{{ $ad->code }}">
                        <button class="button" type="submit">Adicionar ao carrinho</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card">Nenhum anuncio ativo.</div>
        @endforelse
    </div>
</div>

<script>
  (function () {
    document.querySelectorAll('[data-carousel]').forEach((carousel) => {
      const slides = Array.from(carousel.querySelectorAll('.media-img'));
      if (slides.length <= 1) return;
      let index = 0;

      function show(next) {
        slides[index].classList.remove('is-active');
        index = (next + slides.length) % slides.length;
        slides[index].classList.add('is-active');
      }

      const prev = carousel.querySelector('.carousel-btn.prev');
      const next = carousel.querySelector('.carousel-btn.next');
      if (prev) prev.addEventListener('click', () => show(index - 1));
      if (next) next.addEventListener('click', () => show(index + 1));
    });
  })();
</script>
@endsection
