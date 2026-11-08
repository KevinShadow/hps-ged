<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HPS GED')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand me-auto me-lg-0 d-flex align-items-center gap-2 fw-semibold" href="{{ route('home') }}">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white text-primary fw-bold px-2 py-1">
                    H
                </span>
                <span>
                    HPS GED
                    <span class="d-block text-white-50 small fw-normal lh-1">Gestion documentaire</span>
                </span>
            </a>
            @auth
                <div class="d-flex align-items-center gap-2 ms-auto ms-lg-0 me-2">
                    <span class="d-none d-md-inline d-lg-none text-white small fw-semibold">
                        {{ auth()->user()->name }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-light btn-sm text-primary fw-semibold px-3 text-nowrap" type="submit">
                            Déconnexion
                        </button>
                    </form>
                </div>
            @else
                <a class="btn btn-outline-light btn-sm d-lg-none ms-auto me-2 fw-semibold" href="{{ route('login') }}">
                    Se connecter
                </a>
            @endauth
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar"
                aria-controls="mainNavbar"
                aria-expanded="false"
                aria-label="Afficher le menu"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a
                            class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                            href="{{ route('home') }}"
                            @if(request()->routeIs('home')) aria-current="page" @endif
                        >
                            Accueil
                        </a>
                    </li>
                    @can('viewAny', \App\Models\User::class)
                        <li class="nav-item">
                            <a
                                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                href="{{ route('admin.dashboard') }}"
                                @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif
                            >
                                Tableau de bord
                            </a>
                        </li>
                    @endcan
                    @can('viewAny', \App\Models\MonthlySubmission::class)
                        <li class="nav-item">
                            <a
                                class="nav-link {{ request()->routeIs('submissions.*') ? 'active' : '' }}"
                                href="{{ route('submissions.index') }}"
                                @if(request()->routeIs('submissions.*')) aria-current="page" @endif
                            >
                                Dossiers mensuels
                            </a>
                        </li>
                    @endcan
                    @can('create', \App\Models\MonthlySubmission::class)
                        <li class="nav-item d-lg-none">
                            <a class="nav-link" href="{{ route('submissions.create') }}">
                                Nouvelle sauvegarde
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', \App\Models\User::class)
                        <li class="nav-item">
                            <a
                                class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                                href="{{ route('users.index') }}"
                                @if(request()->routeIs('users.*')) aria-current="page" @endif
                            >
                                Utilisateurs
                            </a>
                        </li>
                    @endcan

                    @can('viewAny', \App\Models\Department::class)
                        <li class="nav-item">
                            <a
                                class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}"
                                href="{{ route('departments.index') }}"
                                @if(request()->routeIs('departments.*')) aria-current="page" @endif
                            >
                                Services
                            </a>
                        </li>
                    @endcan
                </ul>

                @auth
                    @php
                        $currentUser = auth()->user();
                        $roleLabels = [
                            'admin' => 'Administrateur',
                            'manager' => 'Responsable',
                            'employee' => 'Employé',
                        ];
                    @endphp
                    <div class="d-none d-lg-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 mt-3 mt-lg-0">
                        @can('create', \App\Models\MonthlySubmission::class)
                            <a class="btn btn-light text-primary fw-semibold" href="{{ route('submissions.create') }}">
                                Nouvelle sauvegarde
                            </a>
                        @endcan
                        <div class="d-flex align-items-center gap-2 px-lg-2">
                            <span
                                class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white bg-opacity-25 text-white fw-semibold px-2 py-2"
                                aria-hidden="true"
                            >
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($currentUser->name, 0, 1)) }}
                            </span>
                            <span class="d-flex flex-column lh-sm">
                                <span class="text-white fw-semibold">{{ $currentUser->name }}</span>
                                <span class="text-white-50 small">
                                    {{ $roleLabels[$currentUser->role->value] ?? 'Utilisateur' }}
                                </span>
                            </span>
                        </div>
                    </div>
                @else
                    <div class="d-none d-lg-flex flex-column flex-sm-row gap-2 mt-3 mt-lg-0">
                        <a class="btn btn-outline-light fw-semibold px-3" href="{{ route('login') }}">
                            Se connecter
                        </a>
                        <a class="btn btn-light text-primary fw-semibold px-3" href="{{ route('register') }}">
                            Créer un compte
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    <main class="container py-4 py-lg-5">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Fermer"
                ></button>
            </div>
        @endif

        @yield('content')
    </main>

    <div
        class="modal fade"
        tabindex="-1"
        aria-labelledby="actionConfirmationTitle"
        aria-hidden="true"
        data-action-confirmation-modal
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="actionConfirmationTitle" data-confirm-title></h2>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Fermer"
                    ></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" data-confirm-message></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button class="btn btn-danger" type="button" data-confirm-submit>
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer class="container py-4 text-center text-body-secondary small">
        HPS GED &middot; Gestion électronique des documents
    </footer>
</body>
</html>
