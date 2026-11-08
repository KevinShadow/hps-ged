<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\DocumentRepositoryInterface;
use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function __construct(
        protected Document $model
    ) {}

    public function findById(int $id): ?Document
    {
        return $this->model
            ->with(['monthlySubmission', 'uploader'])
            ->find($id);
    }

    public function paginateBySubmission(
        int $submissionId,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->model
            ->where('monthly_submission_id', $submissionId)
            ->latest()
            ->paginate($perPage);
    }

    public function getOriginalNamesBySubmission(int $submissionId): array
    {
        return $this->model
            ->where('monthly_submission_id', $submissionId)
            ->pluck('original_name')
            ->all();
    }

    public function create(array $data): Document
    {
        return $this->model->create($data);
    }

    public function delete(int $id): bool
    {
        $document = $this->findById($id);

        if (! $document) {
            return false;
        }

        return (bool) $document->delete();
    }
}
