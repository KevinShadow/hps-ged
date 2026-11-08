@extends('layouts.app')

@section('title', 'Dossiers mensuels | HPS GED')

@section('content')
    @php
        $monthNames = [
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
        ];

        $statusLabels = [
            'draft' => 'Brouillon',
            'submitted' => 'Transmis',
            'approved' => 'Approuvé',
            'rejected' => 'Refusé',
        ];

        $statusClasses = [
            'draft' => 'text-bg-secondary',
            'submitted' => 'text-bg-primary',
            'approved' => 'text-bg-success',
            'rejected' => 'text-bg-danger',
        ];
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold text-uppercase small mb-1">Suivi documentaire</p>
            <h1 class="h2 mb-0">Dossiers mensuels</h1>
        </div>
        @if(auth()->user()->isEmployee())
            <a class="btn btn-primary" href="{{ route('submissions.create') }}">
                Créer un dossier
            </a>
        @endif
    </div>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('submissions.index') }}" class="row g-3 align-items-end">
                <div class="col-12 col-sm-5 col-lg-3">
                    <label class="form-label" for="year">Année</label>
                    <input
                        class="form-control"
                        type="number"
                        name="year"
                        id="year"
                        min="2020"
                        max="2100"
                        value="{{ $year }}"
                        required
                    >
                </div>
                <div class="col-12 col-sm-5 col-lg-3">
                    <label class="form-label" for="month">Mois</label>
                    <select class="form-select" name="month" id="month">
                        <option value="">Tous les mois</option>
                        @foreach($monthNames as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" @selected($month == $monthNumber)>
                                {{ $monthName }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->isAdmin())
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="department_id">Service</label>
                        <select class="form-select" name="department_id" id="department_id">
                            <option value="">Tous les services</option>
                            @foreach($departments as $department)
                                <option
                                    value="{{ $department->id }}"
                                    @selected($departmentId == $department->id)
                                >
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if(!auth()->user()->isEmployee())
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="status">Statut</label>
                        <select class="form-select" name="status" id="status">
                            <option value="">Tous les statuts</option>
                            @foreach($statusLabels as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" @selected($status?->value === $statusValue)>
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-auto">
                    <button class="btn btn-outline-primary" type="submit">Filtrer</button>
                    <a class="btn btn-link text-secondary" href="{{ route('submissions.index') }}">Réinitialiser</a>
                </div>
            </form>
        </div>
    </section>

    <section class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Période</th>
                        @if(auth()->user()->isManager())
                            <th scope="col">Employé</th>
                        @endif
                        <th scope="col">Service</th>
                        <th scope="col">Documents</th>
                        <th scope="col">Statut</th>
                        <th scope="col" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $submission)
                        <tr>
                            <td class="fw-semibold">
                                {{ $monthNames[$submission->month] ?? $submission->month }}
                                {{ $submission->year }}
                            </td>
                            @if(auth()->user()->isManager())
                                <td>{{ $submission->user->name }}</td>
                            @endif
                            <td>{{ $submission->department->name }}</td>
                            <td>{{ $submission->documents_count }}</td>
                            <td>
                                <span class="badge {{ $statusClasses[$submission->status->value] ?? 'text-bg-secondary' }}">
                                    {{ $statusLabels[$submission->status->value] ?? $submission->status->value }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('submissions.show', $submission) }}">
                                    Consulter
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="{{ auth()->user()->isAdmin() || auth()->user()->isManager() ? 6 : 5 }}"
                                class="text-center text-body-secondary py-5"
                            >
                                @if(auth()->user()->isEmployee() && $month === null)
                                    Sélectionnez un mois pour consulter votre dossier.
                                @else
                                    Aucun dossier trouvé pour cette période.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($submissions instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $submissions->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $submissions->withQueryString()->links() }}
            </div>
        @endif
    </section>
@endsection
