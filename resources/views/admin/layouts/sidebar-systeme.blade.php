{{-- ═⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
     FasoLivestock Admin — Sidebar Système
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀ --}}

<aside id="sidebar">

    {{-- Brand --}}
    <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
        <div class="sidebar-brand-logo">
             <img src="{{ asset('admin/images/logo.png') }}" alt="Logo FasoLivestock" class="img-fluid">
        </div>
        <div class="sidebar-brand-text">
            FasoLivestock
            <small>Administration</small>
        </div>
    </a>

    {{-- Navigation --}}
    <nav class="sidebar-nav">

        {{-- ── Principal ──────────────────────────────── --}}
        <div class="nav-section-label">Principal</div>

        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2 nav-icon"></i>
            Dashboard
        </a>

        {{-- ── Administration ──────────────────────────── --}}
        <div class="nav-section-label">Administration</div>

        {{-- Utilisateurs --}}
        <a href="{{ route('admin.users.index') }}"
           class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="bi bi-people nav-icon"></i>
            Utilisateurs
        </a>

        {{-- Rôles --}}
        <a href="{{ route('admin.roles.index') }}"
           class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
            <i class="bi bi-shield-check nav-icon"></i>
            Rôles & Permissions
        </a>

        {{-- ── Gestion ─────────────────────────────────── --}}
        <div class="nav-section-label">Gestion</div>

        {{-- Fermes --}}
        <a href="{{ route('admin.farms.index') }}"
           class="nav-link {{ request()->routeIs('admin.farms.*') ? 'active' : '' }}">
            <i class="bi bi-house-heart nav-icon"></i>
            Fermes
        </a>

        {{-- Espèces --}}
        <a href="{{ route('admin.especes.index') }}"
           class="nav-link {{ request()->routeIs('admin.especes.*') ? 'active' : '' }}">
            <i class="bi bi-tree nav-icon"></i>
            Espèces
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
