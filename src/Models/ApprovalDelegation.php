<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalDelegation extends Model
{
    protected $table = 'approval_delegations';

    protected $fillable = [
        'user_id',
        'delegated_to_user_id',
        'approval_step_id',
        'module_type',
        'role_type',
        'starts_at',
        'ends_at',
        'delegation_type',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the delegator user (who delegated).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'user_id');
    }

    /**
     * Get the delegate user (who received delegation).
     */
    public function delegatedToUser(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'delegated_to_user_id');
    }

    /**
     * Get the approval step.
     */
    public function approvalStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class);
    }

    /**
     * Check if delegation is currently active.
     */
    public function isActive(): bool
    {
        $now = now();
        return $this->is_active 
            && $this->starts_at <= $now 
            && ($this->ends_at === null || $this->ends_at >= $now);
    }
}
