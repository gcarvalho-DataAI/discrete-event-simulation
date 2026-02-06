@extends('layouts.app')

@section('content')
<div class="container">
    <div class="actions" style="justify-content: space-between; align-items: center;">
        <h1>{{ $ad->title }}</h1>
        <a class="button secondary" href="{{ route('home') }}">Voltar</a>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
        <div class="card">
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
        </div>
        <div class="card">
            <div class="product-meta">Material digital</div>
            <p class="text-muted">{{ $ad->description }}</p>
            <div class="price">{{ $ad->price_text ?: 'R$ ' . number_format($ad->price_cents / 100, 2, ',', '.') }}</div>
            <div class="actions row" style="margin-top: 16px;">
                <form method="post" action="{{ route('cart.add') }}" class="inline-form">
                    @csrf
                    <input type="hidden" name="id" value="{{ $ad->code }}">
                    <button class="button" type="submit">Adicionar ao carrinho</button>
                </form>
            </div>
        </div>
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
