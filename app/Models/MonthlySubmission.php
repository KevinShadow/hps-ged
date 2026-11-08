<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlySubmission extends Model
{
    protected $fillable = [
        'user_id',
        'department_id',
        'year',
        'month',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'submitted_at' => 'datetime',
            'status' => SubmissionStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isSubmitted(): bool
    {
        return $this->status === SubmissionStatus::SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this->status === SubmissionStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === SubmissionStatus::REJECTED;
    }

    public function isEditable(): bool
    {
        return in_array(
            $this->status,
            [SubmissionStatus::DRAFT, SubmissionStatus::REJECTED],
            true
        );
    }

    public function canAddDocuments(): bool
    {
        return $this->isEditable() || $this->isSubmitted() || $this->isApproved();
    }
}
