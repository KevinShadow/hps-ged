<?php

namespace App\Services;

use App\Contracts\Repositories\DocumentRepositoryInterface;
use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentService
{
    public function __construct(
        protected DocumentRepositoryInterface $repository
    ) {}

    public function getDocument(int $id): ?Document
    {
        return $this->repository->findById($id);
    }

    public function getSubmissionDocuments(
        int $submissionId,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->repository->paginateBySubmission(
            $submissionId,
            $perPage
        );
    }

    /**
     * @return list<string>
     */
    public function getSubmissionDocumentNames(int $submissionId): array
    {
        return $this->repository->getOriginalNamesBySubmission($submissionId);
    }

    public function createDocument(array $data): Document
    {
        return $this->repository->create($data);
    }

    public function deleteDocument(int $id): bool
    {
        $document = $this->repository->findById($id);

        if (! $document) {
            return false;
        }

        $disk = Storage::disk($document->disk);

        if (! $disk->exists($document->path)) {
            throw new RuntimeException(
                'Le fichier physique est introuvable. Suppression annulée.'
            );
        }

        if (! $disk->delete($document->path)) {
            throw new RuntimeException(
                'Impossible de supprimer le fichier physique.'
            );
        }

        return $this->repository->delete($id);
    }
}
