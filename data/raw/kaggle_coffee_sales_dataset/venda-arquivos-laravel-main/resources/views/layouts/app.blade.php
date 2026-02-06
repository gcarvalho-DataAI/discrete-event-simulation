<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Venda Arquivos' }}</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="logo">
                <span class="header-chip">SM</span>
                <span>
                    Loja Solver Mind<br>
                    <small class="text-muted">Produtos digitais oficiais</small>
                </span>
            </a>
            <nav class="nav">
                <a href="{{ route('cart.show') }}" class="icon-link" aria-label="Carrinho" title="Carrinho">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L22 7H7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="10" cy="20" r="1.5" fill="currentColor"/>
                        <circle cx="18" cy="20" r="1.5" fill="currentColor"/>
                    </svg>
                </a>
                <a
                    href="{{ auth()->check() ? (auth()->user()->role === 'ADMIN' ? route('admin.dashboard') : route('account.dashboard')) : route('account.login') }}"
                    class="icon-link"
                    aria-label="Perfil"
                    title="Perfil"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M4 20c1.8-3.4 4.5-5 8-5s6.2 1.6 8 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </a>
            </nav>
        </div>
    </header>

    <main class="page">
        @if(session('status'))
            <div class="container">
                <div class="alert success">{{ session('status') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="container">
                <div class="alert error">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
