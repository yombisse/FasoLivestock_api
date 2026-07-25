{{-- ═══════════════════════════════════════════════════════
     FasoLivestock Admin — Sidebar Ferme
═══════════════════════════════════════════════════════ --}}

@php
$farmId = is_array($farm) ? ($farm['id'] ?? null) : $farm;
$farmName = is_array($farm) ? ($farm['name'] ?? 'Ferme') : 'Ferme';
@endphp

<aside id="sidebar">

    {{-- Brand --}}
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
        <div class="sidebar-brand-logo">
             <img src="{{ asset('admin/images/logo.png') }}" alt="Logo FasoLivestock" class="img-fluid">
        </div>
        <div class="sidebar-brand-text">
            FasoLivestock
            <small>{{ $farmName }}</small>
        </div>
    </a>

    {{-- Navigation --}}
    <nav class="sidebar-nav">

        {{-- ── Ferme ───────────────────────────────────── --}}
        <div class="nav-section-label">{{ $farmName }}</div>

        {{-- Dashboard ferme --}}
        <a href="{{ $farmId ? route('admin.ferme.dashboard', ['farm' => $farmId]) : '#' }}"
           class="nav-link {{ request()->routeIs('admin.ferme.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2 nav-icon"></i>
            Dashboard
        </a>

        {{-- Administration --}}
        <a href="{{ $farmId ? route('admin.farms.manage', ['farm' => $farmId]) : '#' }}"
           class="nav-link {{ request()->routeIs('admin.farms.manage') ? 'active' : '' }}">
            <i class="bi bi-gear nav-icon"></i>
            Administration
        </a>

        {{-- ── Opérations ─────────────────────────────── --}}
        <div class="nav-section-label">Opérations</div>

        {{-- Animaux avec sous-menu --}}
        <div x-data="navSubmenu({{ request()->routeIs('admin.animals.*') || request()->routeIs('admin.lots.*') ? 'true' : 'false' }})">
            <button class="nav-link {{ request()->routeIs('admin.animals.*') || request()->routeIs('admin.lots.*') ? 'active' : '' }}"
                    @click="toggle()">
                <i class="bi bi-box2-heart nav-icon"></i>
                Animaux
                <i class="bi bi-chevron-down nav-chevron" :class="{ rotated: open }"></i>
            </button>
            <div class="nav-submenu" x-show="open" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-1"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-cloak>
                <a href="{{ $farmId ? route('admin.animals.index', ['farm' => $farmId]) : '#' }}"
                   class="nav-link {{ request()->routeIs('admin.animals.index') ? 'active' : '' }}">
                    <i class="bi bi-list-ul nav-icon"></i>
                    Liste des animaux
                </a>
                <a href="{{ $farmId ? route('admin.lots.index', ['farm' => $farmId]) : '#' }}"
                   class="nav-link {{ request()->routeIs('admin.lots.*') ? 'active' : '' }}">
                    <i class="bi bi-collection nav-icon"></i>
                    Lots
                </a>
            </div>
        </div>

        {{-- Santé --}}
        <a href="{{ $farmId ? route('admin.sante-evenements.index', ['farm' => $farmId]) : '#' }}"
           class="nav-link {{ request()->routeIs('admin.sante-evenements.*', 'admin.sante-rappels.*') ? 'active' : '' }}">
            <i class="bi bi-heart-pulse nav-icon"></i>
            Santé
        </a>

        {{-- Reproduction --}}
        <a href="{{ $farmId ? route('admin.evenements-reproduction.index', ['farm' => $farmId]) : '#' }}"
           class="nav-link {{ request()->routeIs('admin.evenements-reproduction.*', 'admin.naissances.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-event nav-icon"></i>
            Reproduction
        </a>

        {{-- Finances --}}
        <a href="{{ $farmId ? route('admin.finance.index', ['farm' => $farmId]) : '#' }}"
           class="nav-link {{ request()->routeIs('admin.finance.*') ? 'active' : '' }}">
            <i class="bi bi-cash-stack nav-icon"></i>
            Finances
        </a>

        {{-- Mouvements --}}
        <a href="{{ $farmId ? route('admin.mouvements.index', ['farm' => $farmId]) : '#' }}"
           class="nav-link {{ request()->routeIs('admin.mouvements.*') ? 'active' : '' }}">
            <i class="bi bi-arrow-left-right nav-icon"></i>
            Mouvements
        </a>

        {{-- ── Système ─────────────────────────────────── --}}
        <div class="nav-section-label">Système</div>

        {{-- Rapports --}}
        <a href="{{ $farmId ? route('admin.rapports.index', ['farm' => $farmId]) : '#' }}" class="nav-link {{ request()->routeIs('admin.rapports.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-bar-graph nav-icon"></i>
            Rapports
        </a>

        {{-- Logs --}}
        <a href="{{ $farmId ? route('admin.logs.index', ['farm' => $farmId]) : '#' }}" class="nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
            <i class="bi bi-journal-text nav-icon"></i>
            Logs d'audit
        </a>

    </nav>

    {{-- Footer sidebar --}}
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                {{ strtoupper(substr(session('admin_user.name', 'A'), 0, 1)) }}
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">
                    {{ session('admin_user.name', 'Administrateur') }}
                </div>
                <div class="sidebar-user-role">
                    {{ session('admin_user.roles.0.name', '—') }}
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="sidebar-logout" title="Déconnexion">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

</aside>
