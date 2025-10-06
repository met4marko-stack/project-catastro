@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

<nav
    class="main-header navbar
    {{ config('adminlte.classes_topnav_nav', 'navbar-expand') }}
    {{ config('adminlte.classes_topnav', 'navbar-white navbar-light') }}">

    {{-- Navbar left links --}}
    <ul class="navbar-nav">
        {{-- Left sidebar toggler link --}}
        @include('adminlte::partials.navbar.menu-item-left-sidebar-toggler')

        {{-- Configured left links --}}
        @each('adminlte::partials.navbar.menu-item', $adminlte->menu('navbar-left'), 'item')

        {{-- Custom left links --}}
        @yield('content_top_nav_left')

        @if (Auth::user() && Auth::user()->municipio)
            @php
                $municipio = Auth::user()->municipio;
            @endphp
            <li class="nav-item d-none d-sm-inline-block">
                <div class="nav-link d-flex align-items-center">
                    {{-- Muestra el logo --}}
                    @if ($municipio->logo)
                        <img src="{{ asset('storage/' . $municipio->logo) }}" alt="Logo de {{ $municipio->nombre }}"
                            style="height: 30px; margin-right: 10px; border-radius: 4px;">
                    @endif
                    {{-- Muestra el texto --}}
                    <span class="navbar-text font-weight-bold">
                        GOBIERNO AUTONOMO MUNICIPAL DE {{ $municipio->nombre }}
                    </span>
                </div>
            </li>
        @endif
    </ul>

    {{-- Navbar right links --}}
    <ul class="navbar-nav ml-auto">
        {{-- Custom right links --}}
        @yield('content_top_nav_right')

        {{-- Configured right links --}}
        @each('adminlte::partials.navbar.menu-item', $adminlte->menu('navbar-right'), 'item')

        {{-- User menu link --}}
        @if (Auth::user())
            @if (config('adminlte.usermenu_enabled'))
                @include('adminlte::partials.navbar.menu-item-dropdown-user-menu')
            @else
                @include('adminlte::partials.navbar.menu-item-logout-link')
            @endif
        @endif

        {{-- Right sidebar toggler link --}}
        @if ($layoutHelper->isRightSidebarEnabled())
            @include('adminlte::partials.navbar.menu-item-right-sidebar-toggler')
        @endif
    </ul>

</nav>
