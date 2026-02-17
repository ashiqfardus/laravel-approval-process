<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approver extends Model
{
    use HasFactory;

    protected $table = 'approval_approvers';

    protected $fillable = [
        'approval_step_id',
        'approver_type',
        'approver_id',
        'user_id',
        'is_approved',
        'approval_at',
        'sequence',
        'weightage',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'approval_at' => 'datetime',
    ];

    // Approver types
    const TYPE_USER = 'user';
    const TYPE_ROLE = 'role';
    const TYPE_MANAGER = 'manager';
    const TYPE_DEPARTMENT_HEAD = 'department-head';
    const TYPE_POSITION = 'position';
    const TYPE_CUSTOM = 'custom';

    /**
     * Get the approval step.
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'approval_step_id');
    }

    /**
     * Get the assigned user (if approver_type is user).
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Mark as approved.
     */
    public function markApproved(): void
    {
        $this->update([
            'is_approved' => true,
            'approval_at' => now(),
        ]);
    }

    /**
     * Check if approver has approved.
     */
    public function hasApproved(): bool
    {
        return $this->is_approved;
    }

    /**
     * Get the actual approver (resolving if needed).
     */
    public function getActualApprover()
    {
        if ($this->approver_type === self::TYPE_USER && $this->user_id) {
            return $this->assignedUser;
        }

        // Implementation for resolving other types
        return null;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \AshiqFardus\ApprovalProcess\Tests\Factories\ApproverFactory::new();
    }
}
