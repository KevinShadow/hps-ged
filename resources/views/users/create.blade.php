@extends('layouts.app')

@section('title', 'Créer un utilisateur | HPS GED')

@section('content')
    <div class="mb-4">
        <a class="link-secondary text-decoration-none" href="{{ route('users.index') }}">
            &larr; Retour aux utilisateurs
        </a>
        <p class="text-primary fw-semibold text-uppercase small mt-3 mb-1">Administration</p>
        <h1 class="h2 mb-0">Créer un utilisateur</h1>
    </div>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('users.store') }}" class="row g-3">
                @csrf

                <div class="col-12 col-md-6">
                    <label class="form-label" for="name">Nom complet</label>
                    <input
                        class="form-control @error('name') is-invalid @enderror"
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        autocomplete="name"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
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

                <div class="col-12 col-md-6">
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
                    <div class="form-text">12 caractères minimum.</div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
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

                <div class="col-12 col-md-6">
                    <label class="form-label" for="department_id">Service</label>
                    <select
                        class="form-select @error('department_id') is-invalid @enderror"
                        name="department_id"
                        id="department_id"
                        required
                    >
                        <option value="">Sélectionner un service</option>
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

                <div class="col-12 col-md-6">
                    <label class="form-label" for="role">Rôle</label>
                    <select
                        class="form-select @error('role') is-invalid @enderror"
                        name="role"
                        id="role"
                        required
                    >
                        <option value="employee" @selected(old('role', 'employee') === 'employee')>Employé</option>
                        <option value="manager" @selected(old('role') === 'manager')>Responsable</option>
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                    <button class="btn btn-primary" type="submit">Créer le compte</button>
                    <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">Annuler</a>
                </div>
            </form>
        </div>
    </section>
@endsection