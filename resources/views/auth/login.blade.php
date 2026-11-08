@extends('layouts.app')

@section('title', 'Connexion | HPS GED')

@section('content')
    <div class="row justify-content-center py-3 py-lg-5">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">
            <section class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <p class="text-primary fw-semibold text-uppercase small mb-2">
                        Espace sécurisé
                    </p>
                    <h1 class="h3 mb-2">Connexion</h1>
                    <p class="text-body-secondary mb-4">
                        Connectez-vous pour accéder à vos dossiers et documents.
                    </p>

                    @if(session('status'))
                        <div class="alert alert-success" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="vstack gap-3">
                        @csrf

                        <div>
                            <label class="form-label" for="email">Adresse e-mail</label>
                            <input
                                class="form-control @error('email') is-invalid @enderror"
                                type="email"
                                name="email"
                                id="email"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                required
                                autofocus
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" for="password">Mot de passe</label>
                            <input
                                class="form-control @error('password') is-invalid @enderror"
                                type="password"
                                name="password"
                                id="password"
                                autocomplete="current-password"
                                required
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="remember"
                                id="remember"
                                value="1"
                                @checked(old('remember'))
                            >
                            <label class="form-check-label" for="remember">Se souvenir de moi</label>
                        </div>

                        <button class="btn btn-primary w-100" type="submit">
                            Se connecter
                        </button>
                    </form>

                    <p class="text-center text-body-secondary mt-4 mb-0">
                        Pas encore de compte ?
                        <a href="{{ route('register') }}">Demander un compte employé</a>
                    </p>
                </div>
            </section>
        </div>
    </div>
@endsection
