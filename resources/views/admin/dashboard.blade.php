@extends('layouts.app')

@section('title', 'Tableau de bord | HPS GED')

@section('content')
    @php
        $statusLabels = [
            'draft' => 'Brouillon',
            'submitted' => 'À traiter',
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

    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3 mb-4">
        <div>
            <p class="text-primary fw-semibold text-uppercase small mb-1">Administration</p>
            <h1 class="h2 mb-0">Tableau de bord</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('users.create') }}">Ajouter un utilisateur</a>
            <a class="btn btn-outline-primary" href="{{ route('departments.create') }}">Ajouter un service</a>
            <a class="btn btn-outline-secondary" href="{{ route('submissions.index', ['year' => $dashboardYear]) }}">
                Tous les dossiers
            </a>
        </div>
    </div>

    @if(!$storageAvailable)
        <div class="alert alert-warning" role="alert">
            Le répertoire de stockage externe est absent ou inaccessible en écriture.
            Les téléversements de documents ne pourront pas aboutir.
            Vérifiez la variable <code>GED_STORAGE_PATH</code> et les droits du répertoire.
        </div>
    @endif
    @if($pendingEmployeeApprovalsCount > 0)
        <div class="alert alert-warning d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2" role="status">
            <span>
                {{ $pendingEmployeeApprovalsCount }} demande(s) d'inscription employé en attente de validation.
            </span>
            <a class="btn btn-sm btn-outline-dark" href="{{ route('users.index', ['approval' => 'pending']) }}">
                Examiner les demandes
            </a>
        </div>
    @endif
    <div class="d-flex justify-content-end mb-3">
        <span class="badge {{ $storageAvailable ? 'text-bg-success' : 'text-bg-danger' }} fs-6">
            Stockage {{ $storageAvailable ? 'disponible' : 'indisponible' }}
        </span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-body-secondary mb-2">Utilisateurs actifs</p>
                    <p class="display-6 fw-semibold mb-0">{{ $activeUsersCount }}</p>
                    <p class="small text-body-secondary mb-0">
                        {{ $inactiveUsersCount }} inactif(s) · {{ $employeesCount }} employé(s)
                    </p>
                    <a class="small link-primary" href="{{ route('users.index') }}">Gérer les utilisateurs</a>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-body-secondary mb-2">Services</p>
                    <p class="display-6 fw-semibold mb-0">{{ $departmentsCount }}</p>
                    <p class="small text-body-secondary mb-0">{{ $activeDepartmentsCount }} service(s) actif(s)</p>
                    <a class="small link-primary" href="{{ route('departments.index') }}">Gérer les services</a>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-body-secondary mb-2">Dossiers · {{ $dashboardYear }}</p>
                    <p class="display-6 fw-semibold mb-0">{{ $submissionsCount }}</p>
                    <p class="small text-body-secondary mb-0">
                        {{ $pendingSubmissionsCount }} à traiter · {{ $draftSubmissionsCount }} brouillon(s)
                    </p>
                    <a class="small link-primary" href="{{ route('submissions.index', ['year' => $dashboardYear]) }}">
                        Gérer les dossiers
                    </a>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-body-secondary mb-2">Documents archivés</p>
                    <p class="display-6 fw-semibold mb-0">{{ $documentsCount }}</p>
                    <p class="small text-body-secondary mb-0">
                        {{ number_format($documentsSize / 1048576, 1, ',', ' ') }} Mo
                    </p>
                </div>
            </section>
        </div>
    </div>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-column flex-sm-row justify-content-between gap-2">
            <h2 class="h5 mb-0">Suivi des dossiers · {{ $dashboardYear }}</h2>
            <span class="small text-body-secondary">
                {{ $administratorsCount }} administrateur(s) · {{ $managersCount }} responsable(s)
            </span>
        </div>
        <div class="card-body pt-2">
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary" href="{{ route('submissions.index', ['year' => $dashboardYear, 'status' => 'submitted']) }}">
                    À traiter <span class="badge text-bg-primary ms-1">{{ $pendingSubmissionsCount }}</span>
                </a>
                <a class="btn btn-outline-secondary" href="{{ route('submissions.index', ['year' => $dashboardYear, 'status' => 'draft']) }}">
                    Brouillons <span class="badge text-bg-secondary ms-1">{{ $draftSubmissionsCount }}</span>
                </a>
                <a class="btn btn-outline-success" href="{{ route('submissions.index', ['year' => $dashboardYear, 'status' => 'approved']) }}">
                    Approuvés <span class="badge text-bg-success ms-1">{{ $approvedSubmissionsCount }}</span>
                </a>
                <a class="btn btn-outline-danger" href="{{ route('submissions.index', ['year' => $dashboardYear, 'status' => 'rejected']) }}">
                    Refusés <span class="badge text-bg-danger ms-1">{{ $rejectedSubmissionsCount }}</span>
                </a>
            </div>
        </div>
    </section>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <h2 class="h5 mb-0">Dossiers récents</h2>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('submissions.index') }}">
                Gérer tous les dossiers
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Période</th>
                        <th scope="col">Employé</th>
                        <th scope="col">Service</th>
                        <th scope="col">Documents</th>
                        <th scope="col">Statut</th>
                        <th scope="col" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestSubmissions as $submission)
                        <tr>
                            <td class="fw-semibold">
                                {{ sprintf('%02d', $submission->month) }}/{{ $submission->year }}
                            </td>
                            <td>{{ $submission->user->name }}</td>
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
                            <td colspan="6" class="text-center text-body-secondary py-5">
                                Aucun dossier mensuel n’a encore été créé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div>
                <h2 class="h5 mb-1">Documents récents</h2>
                <p class="small text-body-secondary mb-0">Consultation, téléchargement et suppression des documents archivés.</p>
            </div>
            <span class="small text-body-secondary">
                {{ $documentsCount }} document(s) · {{ number_format($documentsSize / 1048576, 1, ',', ' ') }} Mo
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Document</th>
                        <th scope="col">Dossier</th>
                        <th scope="col">Ajouté par</th>
                        <th scope="col">Taille</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestDocuments as $document)
                        <tr>
                            <td class="fw-semibold">{{ $document->original_name }}</td>
                            <td>
                                <a href="{{ route('submissions.show', $document->monthlySubmission) }}">
                                    {{ sprintf('%02d', $document->monthlySubmission->month) }}/{{ $document->monthlySubmission->year }}
                                    · {{ $document->monthlySubmission->user->name }}
                                </a>
                            </td>
                            <td>{{ $document->uploader->name }}</td>
                            <td>{{ number_format($document->size / 1024, 1, ',', ' ') }} Ko</td>
                            <td>
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('documents.download', $document) }}">
                                        Télécharger
                                    </a>
                                    @can('delete', $document)
                                        <form
                                            method="POST"
                                            action="{{ route('documents.destroy', $document) }}"
                                            data-confirm
                                            data-confirm-title="Supprimer le document"
                                            data-confirm-message="Confirmez-vous la suppression définitive de ce document ?"
                                            data-confirm-button="Supprimer"
                                            data-confirm-variant="danger"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-body-secondary py-5">
                                Aucun document n’a encore été archivé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <a class="card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('users.index') }}">
                <div class="card-body p-4">
                    <h2 class="h5 text-body">Gestion des utilisateurs</h2>
                    <p class="text-body-secondary mb-0">
                        Créez des comptes, affectez-les aux services et gérez leur accès.
                    </p>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6">
            <a class="card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('departments.index') }}">
                <div class="card-body p-4">
                    <h2 class="h5 text-body">Gestion des services</h2>
                    <p class="text-body-secondary mb-0">
                        Consultez, modifiez et désactivez les services.
                    </p>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6">
            <a class="card h-100 border-0 shadow-sm text-decoration-none" href="{{ route('submissions.index', ['year' => $dashboardYear]) }}">
                <div class="card-body p-4">
                    <h2 class="h5 text-body">Gestion des dossiers</h2>
                    <p class="text-body-secondary mb-0">
                        Consultez les dossiers de tous les services et traitez les transmissions.
                    </p>
                </div>
            </a>
        </div>
    </div>
@endsection
