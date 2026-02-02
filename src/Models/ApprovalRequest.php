<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $fillable = [
        'workflow_id',
        'current_step_id',
        'requestable_type',
        'requestable_id',
        'requested_by_user_id',
        'status',
        'data_snapshot',
        'submitted_at',
        'completed_at',
        'rejected_at',
        'rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'data_snapshot' => 'json',
        'metadata' => 'json',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_IN_REVIEW = 'in-review';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Get the workflow for this request.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * Get the current step.
     */
    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'current_step_id');
    }

    /**
     * Get the requestable model.
     */
    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get approval actions for this request.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'approval_request_id')
            ->orderByDesc('created_at');
    }

    /**
     * Submit the request for approval.
     */
    public function submit(): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'current_step_id' => $this->workflow->activeSteps()->first()->id,
        ]);
    }

    /**
     * Approve the request.
     */
    public function approve(int $userId, ?string $remarks = null): void
    {
        // Check if all approvers at current step have approved
        $nextStep = $this->currentStep->getNextStep();

        if ($nextStep) {
            $this->update(['current_step_id' => $nextStep->id]);
        } else {
            $this->update([
                'status' => self::STATUS_APPROVED,
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Reject the request.
     */
    public function reject(int $userId, string $reason, ?string $remarks = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Cancel the request.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Check if request can be edited.
     */
    public function canEdit(): bool
    {
        return $this->status === self::STATUS_DRAFT ||
               $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if request is pending approval.
     */
    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_IN_REVIEW,
            self::STATUS_PENDING,
        ]);
    }
}
