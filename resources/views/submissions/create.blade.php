@extends('layouts.app')

@section('title', 'Créer un dossier mensuel | HPS GED')

@section('content')
    <div class="mb-4">
        <a class="link-secondary text-decoration-none" href="{{ route('submissions.index') }}">
            &larr; Retour aux dossiers
        </a>
        <p class="text-primary fw-semibold text-uppercase small mt-3 mb-1">Suivi documentaire</p>
        <h1 class="h2 mb-0">Créer un dossier mensuel</h1>
    </div>

    <section class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <p class="text-body-secondary">
                Choisissez la période concernée. Si un dossier existe déjà pour ce mois, il sera ouvert afin que vous puissiez y ajouter des documents.
            </p>

            @if($errors->has('submission'))
                <div class="alert alert-danger" role="alert">
                    @foreach($errors->get('submission') as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('submissions.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-sm-6">
                    <label class="form-label" for="month">Mois</label>
                    <select
                        class="form-select @error('month') is-invalid @enderror"
                        name="month"
                        id="month"
                        required
                    >
                        @foreach([
                            1 => 'Janvier',
                            2 => 'Février',
                            3 => 'Mars',
                            4 => 'Avril',
                            5 => 'Mai',
                            6 => 'Juin',
                            7 => 'Juillet',
                            8 => 'Août',
                            9 => 'Septembre',
                            10 => 'Octobre',
                            11 => 'Novembre',
                            12 => 'Décembre',
                        ] as $monthNumber => $monthName)
                            <option
                                value="{{ $monthNumber }}"
                                @selected((int) old('month', now()->month) === $monthNumber)
                            >
                                {{ $monthName }}
                            </option>
                        @endforeach
                    </select>
                    @error('month')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-sm-6">
                    <label class="form-label" for="year">Année</label>
                    <input
                        class="form-control @error('year') is-invalid @enderror"
                        type="number"
                        name="year"
                        id="year"
                        min="2020"
                        max="2100"
                        value="{{ old('year', now()->year) }}"
                        required
                    >
                    @error('year')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                    <button class="btn btn-primary" type="submit">Ouvrir ou créer le dossier</button>
                    <a class="btn btn-outline-secondary" href="{{ route('submissions.index') }}">Annuler</a>
                </div>
            </form>
        </div>
    </section>
@endsection
