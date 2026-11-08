<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\StoreDocumentRequest;
use App\Models\Document;
use App\Models\MonthlySubmission;
use App\Services\DocumentService;
use App\Services\ExternalStorageService;
use App\Services\MonthlySubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected ExternalStorageService $externalStorage,
        protected MonthlySubmissionService $submissionService
    ) {}

    /**
     * Ajouter un document à un dossier mensuel.
     */
    public function store(
        StoreDocumentRequest $request,
        MonthlySubmission $submission
    ): RedirectResponse|JsonResponse {

        $user = Auth::user();

        // Vérifier que l'utilisateur peut consulter ce dossier.
        Gate::authorize('view', $submission);

        // Seul l'employé propriétaire peut ajouter un document.
        abort_unless(
            $user->isEmployee()
            && $submission->user_id === $user->id,
            403,
            'Vous ne pouvez pas ajouter de document à ce dossier.'
        );

        // Vérifier que le statut du dossier autorise l’ajout de documents.
        abort_unless(
            $submission->canAddDocuments(),
            403,
            'Ce dossier ne peut plus être modifié.'
        );

        // Vérifier que le formulaire concerne bien ce dossier.
        abort_unless(
            (int) $request->validated('monthly_submission_id')
                === $submission->id,
            422,
            'Le dossier sélectionné est invalide.'
        );

        try {
            $storageIsAvailable = $this->externalStorage->ensureAvailable();
        } catch (Throwable $exception) {
            report($exception);

            return $this->uploadFailure(
                $request,
                'Le stockage externe est temporairement indisponible.',
                503
            );
        }

        if (! $storageIsAvailable) {
            return $this->uploadFailure(
                $request,
                'Le stockage externe est indisponible ou inaccessible en écriture.',
                503
            );
        }

        $baseDirectory = sprintf(
            'documents/%d/%s/%s_%d/month_%02d/submission_%d',
            $submission->year,
            Str::slug($submission->department->name) ?: 'service',
            Str::slug($submission->user->name) ?: 'employe',
            $submission->user_id,
            $submission->month,
            $submission->id
        );

        $files = $request->file('files');
        $relativePaths = $request->input('relative_paths', []);
        $storedPaths = [];
        $documentData = [];

        try {
            foreach ($files as $index => $file) {
                $originalName = basename(str_replace(
                    '\\',
                    '/',
                    $file->getClientOriginalName()
                ));
                $relativePath = $relativePaths[$index] ?? $originalName;
                $pathSegments = $this->safeRelativePathSegments($relativePath);
                $folderSegments = count($pathSegments) > 1
                    ? array_slice($pathSegments, 0, -1)
                    : [];
                $folderSegments = array_map(
                    fn (string $segment): string => Str::slug($segment) ?: 'dossier',
                    $folderSegments
                );

                $targetDirectory = $baseDirectory;

                if ($folderSegments !== []) {
                    $targetDirectory .= '/'.implode('/', $folderSegments);
                }

                $storedName = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
                $path = $file->storeAs($targetDirectory, $storedName, 'ged_external');

                if (! $path) {
                    throw new RuntimeException('Impossible d’enregistrer un document sur le disque externe.');
                }

                $storedPaths[] = $path;
                $documentData[] = [
                    'monthly_submission_id' => $submission->id,
                    'uploaded_by' => $user->id,
                    'original_name' => implode('/', array_merge(
                        $folderSegments,
                        [$originalName]
                    )),
                    'stored_name' => $storedName,
                    'disk' => 'ged_external',
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }

            $uploadResult = DB::transaction(function () use ($documentData, $submission): array {
                $uploadState = $this->submissionService
                    ->prepareForAdditionalDocuments($submission->id, false);

                abort_unless(
                    $uploadState['allowed'],
                    403,
                    'Ce dossier ne peut plus être modifié.'
                );

                $existingNames = array_map(
                    fn (string $name): string => $this->duplicateNameKey($name),
                    $this->documentService->getSubmissionDocumentNames($submission->id)
                );
                $seenNames = array_fill_keys($existingNames, true);
                $documentsToCreate = [];
                $duplicateNames = [];

                foreach ($documentData as $document) {
                    $nameKey = $this->duplicateNameKey($document['original_name']);

                    if (isset($seenNames[$nameKey])) {
                        $duplicateNames[] = $document['original_name'];

                        continue;
                    }

                    $seenNames[$nameKey] = true;
                    $documentsToCreate[] = $document;
                }

                $reopened = false;

                if ($documentsToCreate !== []) {
                    $uploadState = $this->submissionService
                        ->prepareForAdditionalDocuments($submission->id);

                    abort_unless(
                        $uploadState['allowed'],
                        403,
                        'Ce dossier ne peut plus être modifié.'
                    );

                    foreach ($documentsToCreate as $document) {
                        $this->documentService->createDocument($document);
                    }

                    $reopened = $uploadState['reopened'];
                }

                return [
                    'documents' => $documentsToCreate,
                    'duplicates' => $duplicateNames,
                    'reopened' => $reopened,
                ];
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                try {
                    if (! Storage::disk('ged_external')->delete($storedPaths)) {
                        report(new RuntimeException(
                            'Impossible de nettoyer les fichiers après l’échec de leur enregistrement.'
                        ));
                    }
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            if ($exception instanceof HttpExceptionInterface) {
                throw $exception;
            }

            report($exception);

            return $this->uploadFailure(
                $request,
                'Une erreur est survenue pendant l’enregistrement des documents.',
                500
            );
        }

        $duplicatePaths = array_values(array_diff(
            $storedPaths,
            array_column($uploadResult['documents'], 'path')
        ));

        if ($duplicatePaths !== []) {
            try {
                if (! Storage::disk('ged_external')->delete($duplicatePaths)) {
                    report(new RuntimeException(
                        'Impossible de nettoyer les fichiers dupliqués ignorés.'
                    ));
                }
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }
        }

        $uploadedCount = count($uploadResult['documents']);
        $duplicateCount = count($uploadResult['duplicates']);
        $addedNames = array_column($uploadResult['documents'], 'original_name');
        $message = $uploadedCount === 0
            ? 'Aucun document ajouté : les fichiers sélectionnés existent déjà dans ce dossier mensuel.'
            : ($uploadedCount === 1
                ? 'Document ajouté avec succès.'
                : $uploadedCount.' documents ajoutés avec succès.');

        if ($duplicateCount > 0) {
            $message .= ' '.$duplicateCount.' doublon'.($duplicateCount > 1 ? 's' : '')
                .' ignoré'.($duplicateCount > 1 ? 's' : '').'.';
        }

        if ($uploadResult['reopened']) {
            $message .= ' Le dossier a été rouvert en brouillon ; transmettez-le à nouveau pour relancer la validation.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'uploaded' => $uploadedCount,
                'added' => $addedNames,
                'duplicates' => $uploadResult['duplicates'],
                'reopened' => $uploadResult['reopened'],
            ]);
        }

        $redirect = back()->with('upload_result', [
            'message' => $message,
            'added' => $addedNames,
            'duplicates' => $uploadResult['duplicates'],
        ]);

        if ($uploadedCount > 0) {
            $redirect->with('success', $message);
        }

        return $redirect;
    }

    private function duplicateNameKey(string $originalName): string
    {
        return Str::lower(basename(str_replace('\\', '/', $originalName)));
    }

    private function uploadFailure(
        StoreDocumentRequest $request,
        string $message,
        int $status
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return back()->withErrors(['files' => $message]);
    }

    /**
     * @return list<string>
     */
    private function safeRelativePathSegments(string $relativePath): array
    {
        $normalizedPath = str_replace('\\', '/', trim($relativePath));
        $segments = array_values(array_filter(
            explode('/', $normalizedPath),
            fn (string $segment): bool => $segment !== ''
        ));

        abort_if(
            $segments === []
            || in_array('.', $segments, true)
            || in_array('..', $segments, true),
            422,
            'Le chemin du sous-dossier sélectionné est invalide.'
        );

        return $segments;
    }

    /**
     * Télécharger un document.
     */
    public function download(Document $document): JsonResponse|StreamedResponse
    {
        $submission = $document->monthlySubmission;

        Gate::authorize('view', $submission);

        try {
            $disk = Storage::disk($document->disk);

            abort_unless(
                $disk->exists($document->path),
                404,
                'Document introuvable sur le disque externe.'
            );

            return $disk->download(
                $document->path,
                basename(str_replace('\\', '/', $document->original_name))
            );
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Le stockage externe est temporairement indisponible.',
                ], 503);
            }

            abort(503, 'Le stockage externe est temporairement indisponible.');
        }
    }

    /**
     * Supprimer un document.
     */
    public function destroy(
        Document $document
    ): RedirectResponse {
        $submission = $document->monthlySubmission;
        Gate::authorize('delete', $document);

        try {
            $deleted = $this->documentService->deleteDocument($document->id);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'document' => 'Une erreur est survenue pendant la suppression du document.',
            ]);
        }

        if (! $deleted) {
            return back()->withErrors([
                'document' => 'Impossible de supprimer ce document.',
            ]);
        }

        return back()->with(
            'success',
            'Document supprimé avec succès.'
        );
    }
}
