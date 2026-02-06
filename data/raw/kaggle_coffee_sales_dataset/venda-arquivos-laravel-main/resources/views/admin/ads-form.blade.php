@extends('layouts.app')

@section('content')
<div class="container">
    <div class="actions" style="justify-content: space-between; align-items: center;">
        <h1>{{ $ad ? 'Editar anuncio' : 'Novo anuncio' }}</h1>
        <a class="button secondary" href="{{ route('admin.dashboard') }}">Voltar</a>
    </div>

    <div class="card">
        <form method="post" enctype="multipart/form-data" action="{{ $ad ? route('admin.ads.update', $ad) : route('admin.ads.store') }}">
            @csrf
            <div class="form-group">
                <label>Codigo</label>
                <input type="text" name="code" value="{{ old('code', $ad->code ?? '') }}" {{ $ad ? 'readonly' : '' }}>
            </div>
            <div class="form-group">
                <label>Titulo</label>
                <input type="text" name="title" value="{{ old('title', $ad->title ?? '') }}" required>
            </div>
            <div class="form-group">
                <label>Descricao</label>
                <textarea name="description" rows="4">{{ old('description', $ad->description ?? '') }}</textarea>
            </div>
            <div class="form-group">
                <label>Preco</label>
                <input type="text" name="price_text" value="{{ old('price_text', $ad->price_text ?? '') }}">
            </div>
            <div class="form-group">
                <label>Imagens (uma por linha, separadas por virgula ou envie arquivos)</label>
                <div class="dropzone" data-dropzone>
                    <input type="file" name="images_upload[]" multiple accept="image/*" webkitdirectory directory hidden>
                    <div class="dropzone-content">
                        <strong>Arraste as imagens aqui</strong>
                        <span>ou clique para selecionar (pasta ou arquivos)</span>
                    </div>
                    <div class="dropzone-list" data-dropzone-list></div>
                </div>
                <textarea name="images" rows="3">{{ old('images', $ad ? implode("\n", $ad->images ?? []) : '') }}</textarea>
            </div>
            <div class="form-group">
                <label>Link de arquivo</label>
                <input type="text" name="file_url" value="{{ old('file_url', $ad->file_url ?? '') }}">
            </div>
            <div class="form-group">
                <label>CTA</label>
                <input type="text" name="cta_label" value="{{ old('cta_label', $ad->cta_label ?? '') }}">
            </div>
            <div class="form-group">
                <label>CTA Link</label>
                <input type="text" name="cta_href" value="{{ old('cta_href', $ad->cta_href ?? '') }}">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="active" {{ old('active', $ad->active ?? true) ? 'checked' : '' }}> Ativo</label>
            </div>
            <button class="button" type="submit">Salvar</button>
        </form>
    </div>
</div>

<script>
  (function () {
    const zone = document.querySelector('[data-dropzone]');
    if (!zone) return;
    const input = zone.querySelector('input[type="file"]');
    const list = zone.querySelector('[data-dropzone-list]');

    function renderFiles() {
      const files = Array.from(input.files || []);
      if (!list) return;
      if (files.length === 0) {
        list.textContent = '';
        return;
      }
      list.innerHTML = files.map((file) => `<div>${file.name}</div>`).join('');
    }

    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover', (event) => {
      event.preventDefault();
      zone.classList.add('is-dragging');
    });
    zone.addEventListener('dragleave', () => zone.classList.remove('is-dragging'));
    zone.addEventListener('drop', (event) => {
      event.preventDefault();
      zone.classList.remove('is-dragging');
      if (event.dataTransfer && event.dataTransfer.files.length) {
        input.files = event.dataTransfer.files;
        renderFiles();
      }
    });
    input.addEventListener('change', renderFiles);
  })();
</script>
@endsection
