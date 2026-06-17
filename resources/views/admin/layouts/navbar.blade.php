{{-- ═══════════════════════════════════════════════════════
     FasoLivestock Admin — Navbar
═══════════════════════════════════════════════════════ --}}

<nav id="top-navbar">

    {{-- Toggle sidebar --}}
    <button class="btn-toggle-sidebar" @click="toggle()" title="Toggle sidebar">
        <i class="bi bi-list"></i>
    </button>

    {{-- Search --}}
    <div class="navbar-search">
        <i class="bi bi-search search-icon"></i>
        <input type="text" placeholder="Rechercher...">
    </div>

    {{-- Page title (mobile uniquement) --}}
    <h1 class="navbar-page-title">@yield('page-title', 'Dashboard')</h1>

    {{-- Actions droite --}}
    <div class="navbar-actions">

        {{-- Notifications --}}
        <button class="btn-nav-icon" title="Notifications">
            <i class="bi bi-bell"></i>
            <span class="notif-badge">3</span>
        </button>

        {{-- User dropdown --}}
        <div class="dropdown">
            <div class="navbar-user" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="navbar-avatar">
                    {{ strtoupper(substr(session('admin_user.name', 'A'), 0, 1)) }}
                </div>
                <span class="navbar-user-name">
                    {{ session('admin_user.name', 'Admin') }}
                </span>
                <i class="bi bi-chevron-down navbar-user-chevron"></i>
            </div>

            <ul class="dropdown-menu dropdown-menu-end navbar-dropdown">
                <li>
                    <span class="dropdown-header">
                        {{ session('admin_user.email', '') }}
                    </span>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-person"></i> Mon profil
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger w-100">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </button>
                    </form>
                </li>
            </ul>
        </div>

    </div>

</nav>