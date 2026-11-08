@extends('layouts.app')

@section('title', 'Accueil | HPS GED')

@section('content')
    <section class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-4 p-md-5">
            <p class="text-primary fw-semibold text-uppercase small mb-2">
                Gestion documentaire
            </p>
            <h1 class="display-6 fw-semibold mb-3">Bienvenue sur HPS GED</h1>
            <p class="lead text-body-secondary mb-0">
                Retrouvez et suivez les dossiers mensuels et les documents de votre organisation.
            </p>
            <div class="d-flex flex-wrap gap-2 mt-4">
                @guest
                    <a class="btn btn-primary" href="{{ route('login') }}">Se connecter pour accéder à mes dossiers</a>
                    <a class="btn btn-outline-primary" href="{{ route('register') }}">Créer un compte employé</a>
                @else
                    @can('viewAny', \App\Models\MonthlySubmission::class)
                        <a class="btn btn-primary" href="{{ route('submissions.index') }}">Accéder aux dossiers mensuels</a>
                    @endcan
                    @can('viewAny', \App\Models\User::class)
                        <a class="btn btn-outline-primary" href="{{ route('admin.dashboard') }}">Ouvrir le tableau de bord</a>
                    @endcan
                @endguest
            </div>
        </div>
    </section>

    @auth
        <div class="row g-3 mt-2">
            @can('viewAny', \App\Models\User::class)
                <div class="col-12 col-md-6">
                    <a class="card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('admin.dashboard') }}">
                        <div class="card-body p-4">
                            <h2 class="h5 text-body">Tableau de bord administrateur</h2>
                            <p class="text-body-secondary mb-0">
                                Suivez les utilisateurs, les services, les dossiers et le stockage documentaire.
                            </p>
                        </div>
                    </a>
                </div>
            @endcan

            @can('viewAny', \App\Models\MonthlySubmission::class)
                <div class="col-12 col-md-6">
                    <a class="card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('submissions.index') }}">
                        <div class="card-body p-4">
                            <h2 class="h5 text-body">Dossiers mensuels</h2>
                            <p class="text-body-secondary mb-0">
                                Consultez les dossiers et accédez aux documents associés.
                            </p>
                        </div>
                    </a>
                </div>
            @endcan

            @can('viewAny', \App\Models\User::class)
                <div class="col-12 col-md-6">
                    <a class="card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('users.index') }}">
                        <div class="card-body p-4">
                            <h2 class="h5 text-body">Utilisateurs</h2>
                            <p class="text-body-secondary mb-0">
                                Gérez les comptes et leurs affectations.
                            </p>
                        </div>
                    </a>
                </div>
            @endcan

            @can('viewAny', \App\Models\Department::class)
                <div class="col-12 col-md-6">
                    <a class="card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('departments.index') }}">
                        <div class="card-body p-4">
                            <h2 class="h5 text-body">Services</h2>
                            <p class="text-body-secondary mb-0">
                                Consultez les services et leur état.
                            </p>
                        </div>
                    </a>
                </div>
            @endcan
        </div>
    @endauth
@endsection
