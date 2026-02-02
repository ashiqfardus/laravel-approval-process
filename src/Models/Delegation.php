<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delegation extends Model
{
    protected $table = 'approval_delegations';

    protected $fillable = [
        'user_id',
        'delegated_to_user_id',
        'approval_step_id',
        'starts_at',
        'ends_at',
        'delegation_type',
        'module_type',
        'role_type',
        'is_active',
        'reason',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Delegation types
    const TYPE_TEMPORARY = 'temporary';
    const TYPE_PERMANENT = 'permanent';
    const TYPE_EMERGENCY = 'emergency';

    /**
     * Get the user who delegated.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Get the user who received the delegation.
     */
    public function delegatedToUser(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'delegated_to_user_id');
    }

    /**
     * Get the approval step (if applicable).
     */
    public function approvalStep(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'approval_step_id');
    }

    /**
     * Check if delegation is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        $now = now();

        return $this->is_active &&
               $this->starts_at <= $now &&
               (!$this->ends_at || $this->ends_at >= $now);
    }

    /**
     * Activate the delegation.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the delegation.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope to get active delegations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }
}
