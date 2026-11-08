@extends('layouts.app')

@section('title', 'Inscription employé | HPS GED')

@section('content')
    <div class="row justify-content-center py-3 py-lg-5">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <section class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <p class="text-primary fw-semibold text-uppercase small mb-2">
                        Demande de compte
                    </p>
                    <h1 class="h3 mb-2">Inscription employé</h1>
                    <p class="text-body-secondary mb-4">
                        Votre compte sera accessible après validation par un administrateur.
                    </p>

                    <form method="POST" action="{{ route('register.store') }}" class="vstack gap-3">
                        @csrf

                        <div>
                            <label class="form-label" for="name">Nom complet</label>
                            <input
                                class="form-control @error('name') is-invalid @enderror"
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name') }}"
                                autocomplete="name"
                                required
                                autofocus
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" for="email">Adresse e-mail</label>
                            <input
                                class="form-control @error('email') is-invalid @enderror"
                                type="email"
                                name="email"
                                id="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" for="department_id">Service</label>
                            <select
                                class="form-select @error('department_id') is-invalid @enderror"
                                name="department_id"
                                id="department_id"
                                required
                            >
                                <option value="">Choisir un service</option>
                                @foreach($departments as $department)
                                    <option
                                        value="{{ $department->id }}"
                                        @selected(old('department_id') == $department->id)
                                    >
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
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
                                autocomplete="new-password"
                                minlength="12"
                                required
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                            <input
                                class="form-control"
                                type="password"
                                name="password_confirmation"
                                id="password_confirmation"
                                autocomplete="new-password"
                                minlength="12"
                                required
                            >
                        </div>

                        <button class="btn btn-primary w-100" type="submit">
                            Envoyer ma demande
                        </button>
                    </form>

                    <p class="text-center text-body-secondary mt-4 mb-0">
                        Vous avez déjà un compte ?
                        <a href="{{ route('login') }}">Se connecter</a>
                    </p>
                </div>
            </section>
        </div>
    </div>
@endsection
