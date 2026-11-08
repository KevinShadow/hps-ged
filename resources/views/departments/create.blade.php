@extends('layouts.app')

@section('title', 'Créer un service | HPS GED')

@section('content')
    <div class="mb-4">
        <a class="link-secondary text-decoration-none" href="{{ route('departments.index') }}">
            &larr; Retour aux services
        </a>
        <p class="text-primary fw-semibold text-uppercase small mt-3 mb-1">Administration</p>
        <h1 class="h2 mb-0">Créer un service</h1>
    </div>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('departments.store') }}" class="row g-3">
                @csrf

                <div class="col-12">
                    <label class="form-label" for="name">Nom du service</label>
                    <input
                        class="form-control @error('name') is-invalid @enderror"
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
                        maxlength="100"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea
                        class="form-control @error('description') is-invalid @enderror"
                        name="description"
                        id="description"
                        rows="4"
                        maxlength="1000"
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="is_active"
                            id="is_active"
                            value="1"
                            @checked((bool) old('is_active', true))
                        >
                        <label class="form-check-label" for="is_active">Service actif</label>
                    </div>
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                    <button class="btn btn-primary" type="submit">Créer le service</button>
                    <a class="btn btn-outline-secondary" href="{{ route('departments.index') }}">Annuler</a>
                </div>
            </form>
        </div>
    </section>
@endsection