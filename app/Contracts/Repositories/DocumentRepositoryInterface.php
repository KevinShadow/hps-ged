<?php

namespace App\Contracts\Repositories;

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DocumentRepositoryInterface
{
    public function findById(int $id): ?Document;

    public function paginateBySubmission(
        int $submissionId,
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * @return list<string>
     */
    public function getOriginalNamesBySubmission(int $submissionId): array;

    public function create(array $data): Document;

    public function delete(int $id): bool;
}
