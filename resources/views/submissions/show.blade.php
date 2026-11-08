@extends('layouts.app')

@section('title', 'Détail du dossier | HPS GED')

@section('content')
    @php
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

        $isOwnerEditable = auth()->user()->isEmployee()
            && $submission->user_id === auth()->id()
            && $submission->isEditable();
        $canAddDocuments = auth()->user()->isEmployee()
            && $submission->user_id === auth()->id()
            && $submission->canAddDocuments();
    @endphp

    <div class="mb-4">
        <a class="link-secondary text-decoration-none" href="{{ route('submissions.index') }}">
            &larr; Retour aux dossiers
        </a>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mt-3">
            <div>
                <p class="text-primary fw-semibold text-uppercase small mb-1">Dossier mensuel</p>
                <h1 class="h2 mb-2">
                    {{ sprintf('%02d', $submission->month) }}/{{ $submission->year }}
                </h1>
                <p class="text-body-secondary mb-0">
                    {{ $submission->department->name }}
                    @if($submission->user)
                        <span class="mx-1">&middot;</span>{{ $submission->user->name }}
                    @endif
                </p>
            </div>
            <span class="badge fs-6 {{ $statusClasses[$submission->status->value] ?? 'text-bg-secondary' }}">
                {{ $statusLabels[$submission->status->value] ?? $submission->status->value }}
            </span>
        </div>
    </div>

    @if($errors->has('submission') || $errors->has('documents'))
        <div class="alert alert-danger" role="alert">
            @foreach($errors->get('submission') as $message)
                <div>{{ $message }}</div>
            @endforeach
            @foreach($errors->get('documents') as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    @endif

    @if($submission->submitted_at)
        <div class="alert alert-light border mb-4">
            Dossier transmis le {{ $submission->submitted_at->format('d/m/Y à H:i') }}.
        </div>
    @endif
    @if(($submission->isSubmitted() || $submission->isApproved()) && $canAddDocuments)
        <div class="alert alert-info mb-4" role="status">
            Ce dossier a déjà été transmis ou approuvé. Ajouter des documents le rouvrira en brouillon et vous devrez le transmettre à nouveau pour relancer la validation.
        </div>
    @endif
    @if($submission->isRejected() && $isOwnerEditable)
        <div class="alert alert-warning mb-4" role="alert">
            Ce dossier a été refusé. Vous pouvez remplacer ou ajouter des documents, puis le transmettre à nouveau.
        </div>
    @endif

    <section class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div>
                    <h2 class="h5 mb-1">Documents du dossier</h2>
                    <p class="text-body-secondary small mb-0">
                        {{ $submission->documents->count() }} document(s)
                    </p>
                </div>
                @if($isOwnerEditable)
                    <form
                        action="{{ route('submissions.submit', $submission) }}"
                        method="POST"
                        data-confirm
                        data-confirm-title="Transmettre le dossier"
                        data-confirm-message="Confirmez-vous la transmission de ce dossier pour validation ?"
                        data-confirm-button="Transmettre"
                        data-confirm-variant="success"
                    >
                        @csrf
                        <button
                            class="btn btn-success"
                            type="submit"
                            @disabled($submission->documents->isEmpty())
                        >
                            Transmettre le dossier
                        </button>
                    </form>
                @endif
                @can('approve', $submission)
                    @if($submission->status === \App\Enums\SubmissionStatus::SUBMITTED)
                        <div class="d-flex flex-wrap gap-2">
                            <form
                                action="{{ route('submissions.approve', $submission) }}"
                                method="POST"
                                data-confirm
                                data-confirm-title="Approuver le dossier"
                                data-confirm-message="Confirmez-vous l’approbation de ce dossier ?"
                                data-confirm-button="Approuver"
                                data-confirm-variant="success"
                            >
                                @csrf
                                <button class="btn btn-success" type="submit">Approuver</button>
                            </form>
                            <form
                                action="{{ route('submissions.reject', $submission) }}"
                                method="POST"
                                data-confirm
                                data-confirm-title="Refuser le dossier"
                                data-confirm-message="Confirmez-vous le refus de ce dossier ?"
                                data-confirm-button="Refuser"
                                data-confirm-variant="danger"
                            >
                                @csrf
                                <button class="btn btn-outline-danger" type="submit">Refuser</button>
                            </form>
                        </div>
                    @endif
                @endcan
            </div>
        </div>

        @if($submission->documents->isEmpty())
            <div class="card-body px-4 pb-4">
                <p class="text-body-secondary mb-0">Aucun document pour le moment.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Document</th>
                            <th scope="col">Type</th>
                            <th scope="col">Taille</th>
                            <th scope="col">Ajouté par</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submission->documents as $document)
                            <tr>
                                <td class="fw-semibold">{{ $document->original_name }}</td>
                                <td>{{ $document->mime_type }}</td>
                                <td>{{ number_format($document->size / 1024, 1, ',', ' ') }} Ko</td>
                                <td>{{ $document->uploader->name }}</td>
                                <td>
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        <a
                                            class="btn btn-sm btn-outline-primary"
                                            href="{{ route('documents.download', $document) }}"
                                        >
                                            Télécharger
                                        </a>
                                        @can('delete', $document)
                                            <form
                                                action="{{ route('documents.destroy', $document) }}"
                                                method="POST"
                                                data-confirm
                                                data-confirm-title="Supprimer le document"
                                                data-confirm-message="Confirmez-vous la suppression de ce document ? Cette action est définitive."
                                                data-confirm-button="Supprimer"
                                                data-confirm-variant="danger"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    Supprimer
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    @if($canAddDocuments)
        <section class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5 mb-1">Ajouter des documents</h2>
                <p class="text-body-secondary mb-4">
                    Vous pouvez ajouter plusieurs fichiers ou un dossier, même si des documents ont déjà été envoyés pour ce mois. Les sous-dossiers seront conservés dans le dossier mensuel.
                    Un nom de fichier déjà présent dans le dossier mensuel sera ignoré. Formats acceptés : PDF, DOC, DOCX, XLS et XLSX, 10 Mo maximum par fichier.
                </p>

                <form
                    action="{{ route('documents.store', $submission) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="vstack gap-3"
                    data-document-upload
                >
                    @csrf
                    <input type="hidden" name="monthly_submission_id" value="{{ $submission->id }}">

                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold" for="files">Choisir plusieurs fichiers</label>
                            <input
                                class="form-control"
                                type="file"
                                name="files[]"
                                id="files"
                                accept=".pdf,.doc,.docx,.xls,.xlsx"
                                multiple
                                data-upload-input
                            >
                            <div class="form-text">Maintenez Ctrl (Windows) ou Cmd (Mac) pour sélectionner plusieurs fichiers.</div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label fw-semibold" for="folder">Ou choisir un dossier entier</label>
                            <input
                                class="form-control"
                                type="file"
                                name="files[]"
                                id="folder"
                                accept=".pdf,.doc,.docx,.xls,.xlsx"
                                webkitdirectory
                                multiple
                                data-upload-input
                            >
                            <div class="form-text">Les fichiers des sous-dossiers seront classés sous le dossier du mois.</div>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger mb-0" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <p class="small text-body-secondary mb-0" data-upload-selection>
                        Aucun fichier sélectionné.
                    </p>

                    <div class="vstack gap-2">
                        <span class="small text-body-secondary d-none" data-upload-progress-label></span>
                        <div class="progress d-none" role="progressbar" aria-label="Progression du téléversement" aria-valuemin="0" aria-valuemax="100" data-upload-progress>
                            <div class="progress-bar progress-bar-striped progress-bar-animated" data-upload-progress-bar></div>
                        </div>
                    </div>

                    <div>
                        <button class="btn btn-primary" type="submit" data-upload-submit>
                            Envoyer les documents
                        </button>
                    </div>
                </form>

                <div
                    class="modal fade"
                    tabindex="-1"
                    aria-labelledby="documentUploadModalTitle"
                    aria-hidden="true"
                    data-upload-modal
                    data-upload-result="{{ session()->has('upload_result') ? 'true' : 'false' }}"
                >
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header">
                                <h2 class="modal-title fs-5" id="documentUploadModalTitle" data-upload-modal-title>
                                    Résultat de l’ajout
                                </h2>
                                <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Fermer"
                                ></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0" data-upload-message>{{ session('upload_result.message', session('success', '')) }}</p>
                                <div class="border rounded-3 bg-success-subtle p-3 mt-3 {{ count(session('upload_result.added', [])) === 0 ? 'd-none' : '' }}" data-upload-added-wrapper>
                                    <strong class="text-success-emphasis">Fichiers ajoutés</strong>
                                    <ul class="small mb-0 mt-2" data-upload-added>
                                        @foreach(session('upload_result.added', []) as $name)
                                            <li>{{ $name }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="border rounded-3 bg-warning-subtle p-3 mt-3 {{ count(session('upload_result.duplicates', [])) === 0 ? 'd-none' : '' }}" data-upload-duplicates-wrapper>
                                    <strong>Fichiers ignorés (nom déjà présent)</strong>
                                    <ul class="small mb-0 mt-2" data-upload-duplicates>
                                        @foreach(session('upload_result.duplicates', []) as $name)
                                            <li>{{ $name }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                <div class="border rounded-3 bg-danger-subtle p-3 mt-3 d-none" data-upload-errors-wrapper>
                                    <strong>Fichiers en échec</strong>
                                    <ul class="small mb-0 mt-2" data-upload-errors></ul>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button
                                    class="btn btn-outline-secondary"
                                    type="button"
                                    data-bs-dismiss="modal"
                                >
                                    Fermer
                                </button>
                                <button class="btn btn-primary d-none" type="button" data-upload-refresh>
                                    Actualiser les documents
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                @if($submission->documents->isEmpty())
                    <p class="form-text mt-3 mb-0">
                        Ajoutez au moins un document pour pouvoir transmettre le dossier.
                    </p>
                @endif
            </div>
        </section>
    @endif
@endsection
