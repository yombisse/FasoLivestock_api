<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FasoLivestock') — Administration</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('admin/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/navbar.css') }}">

    @stack('styles')
</head>

<body x-data="sidebarManager()" x-init="init()">

    <div id="sidebar-overlay" @click="close()"></div>

    @include('admin.layouts.sidebar-systeme')

    <div id="main-content">

        @include('admin.layouts.navbar')

        @hasSection('breadcrumb')
        <div id="breadcrumb-bar">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-house"></i>
                        </a>
                    </li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>
        @endif

        @include('admin.layouts.flash')

        <main class="page-content fade-in">
            @yield('content')
        </main>

        <footer id="admin-footer">
            © {{ date('Y') }} FasoLivestock — Plateforme de gestion des fermes animales
        </footer>

    </div>

    {{-- 1. Bootstrap --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- 2. JS globaux — sans defer, disponibles immédiatement --}}
    <script src="{{ asset('admin/js/app.js') }}"></script>
    <script src="{{ asset('admin/js/layout/sidebar.js') }}"></script>

    {{-- 3. Scripts spécifiques à la page — avant Alpine --}}
    @stack('scripts')

    {{-- 4. Alpine en dernier — trouve tous les composants déjà définis --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

</body>
</html>
