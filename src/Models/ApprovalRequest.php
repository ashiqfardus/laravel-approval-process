<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $table = 'approval_requests';

    protected $fillable = [
        'workflow_id',
        'current_step_id',
        'current_approval_percentage',
        'requestable_type',
        'requestable_id',
        'requested_by_user_id',
        'creator_level',
        'skip_previous_levels',
        'sla_deadline',
        'last_reminder_sent',
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
        'skip_previous_levels' => 'boolean',
        'sla_deadline' => 'datetime',
        'last_reminder_sent' => 'datetime',
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
     * Get the user who requested this approval.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user', \App\Models\User::class), 'requested_by_user_id');
    }

    /**
     * Get notifications for this request.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(ApprovalNotification::class);
    }

    /**
     * Get the query approval request (for query-based approvals).
     */
    public function queryApproval(): HasOne
    {
        return $this->hasOne(QueryApprovalRequest::class);
    }

    /**
     * Get escalations for this request.
     */
    public function escalations(): HasMany
    {
        return $this->hasMany(ApprovalEscalation::class);
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

    /**
     * Get creator's approval level.
     */
    public function getCreatorLevel(): ?int
    {
        return $this->creator_level;
    }

    /**
     * Check if SLA deadline has passed.
     */
    public function isOverdue(): bool
    {
        return $this->sla_deadline && now()->isAfter($this->sla_deadline);
    }

    /**
     * Get pending approvers for current step.
     */
    public function getPendingApprovers()
    {
        if (!$this->currentStep) {
            return collect();
        }

        return $this->currentStep->approvers;
    }

    /**
     * Calculate SLA deadline based on current step.
     */
    public function calculateSLADeadline()
    {
        if (!$this->currentStep || !$this->currentStep->sla_hours) {
            return null;
        }

        return now()->addHours($this->currentStep->sla_hours);
    }

    /**
     * Update SLA deadline.
     */
    public function updateSLADeadline(): void
    {
        $deadline = $this->calculateSLADeadline();
        if ($deadline) {
            $this->update(['sla_deadline' => $deadline]);
        }
    }

    /**
     * Resubmit the request after editing.
     */
    public function resubmit(): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \AshiqFardus\ApprovalProcess\Tests\Factories\ApprovalRequestFactory::new();
    }
}
