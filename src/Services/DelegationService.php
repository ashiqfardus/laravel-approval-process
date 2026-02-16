<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalDelegation;
use Illuminate\Support\Collection;

class DelegationService
{
    /**
     * Create a new delegation.
     */
    public function createDelegation(array $data): ApprovalDelegation
    {
        // Map common field names to database column names for flexibility
        $mapped = [
            'user_id' => $data['delegator_id'] ?? $data['user_id'] ?? null,
            'delegated_to_user_id' => $data['delegate_id'] ?? $data['delegated_to_user_id'] ?? null,
            'approval_step_id' => $data['approval_step_id'] ?? null,
            'module_type' => $data['module_type'] ?? null,
            'role_type' => $data['role_type'] ?? null,
            'starts_at' => $data['start_date'] ?? $data['starts_at'] ?? now(),
            'ends_at' => $data['end_date'] ?? $data['ends_at'] ?? null,
            'delegation_type' => $data['delegation_type'] ?? 'temporary',
            'reason' => $data['reason'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ];
        
        return ApprovalDelegation::create($mapped);
    }

    /**
     * Get active delegation for a user.
     */
    public function getActiveDelegation(int $userId, ?string $module = null): ?ApprovalDelegation
    {
        return ApprovalDelegation::where('user_id', $userId)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->when($module, function ($q) use ($module) {
                $q->where(function ($query) use ($module) {
                    $query->where('module_type', $module)
                          ->orWhereNull('module_type');
                });
            })
            ->first();
    }

    /**
     * End a delegation.
     */
    public function endDelegation(int $delegationId): void
    {
        ApprovalDelegation::where('id', $delegationId)
            ->update(['is_active' => false]);
    }

    /**
     * Check and auto-end expired delegations.
     */
    public function checkAndAutoEnd(): int
    {
        return ApprovalDelegation::where('is_active', true)
            ->where('ends_at', '<', now())
            ->update(['is_active' => false]);
    }

    /**
     * Get all active delegations for a user.
     */
    public function getUserDelegations(int $userId): Collection
    {
        return ApprovalDelegation::where('user_id', $userId)
            ->where('is_active', true)
            ->with(['delegatedToUser'])
            ->get();
    }

    /**
     * Get delegations where user is the delegate.
     */
    public function getDelegationsAsDelegate(int $userId): Collection
    {
        return ApprovalDelegation::where('delegated_to_user_id', $userId)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->with(['user'])
            ->get();
    }
}
