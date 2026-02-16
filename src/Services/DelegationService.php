<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalDelegation;
use Illuminate\Support\Collection;

class DelegationService
{
    /**
     * Create a new delegation.
     *
     * @param array $data
     * @return ApprovalDelegation
     */
    public function createDelegation(array $data): ApprovalDelegation
    {
        return ApprovalDelegation::create([
            'delegator_id' => $data['delegator_id'],
            'delegate_id' => $data['delegate_id'],
            'module_type' => $data['module_type'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * Get active delegation for a user.
     *
     * @param int $userId
     * @param string|null $module
     * @return ApprovalDelegation|null
     */
    public function getActiveDelegation(int $userId, ?string $module = null): ?ApprovalDelegation
    {
        return ApprovalDelegation::where('delegator_id', $userId)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
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
     *
     * @param int $delegationId
     * @return void
     */
    public function endDelegation(int $delegationId): void
    {
        ApprovalDelegation::where('id', $delegationId)
            ->update(['is_active' => false]);
    }

    /**
     * Check and auto-end expired delegations.
     *
     * @return int Number of delegations ended
     */
    public function checkAndAutoEnd(): int
    {
        return ApprovalDelegation::where('is_active', true)
            ->where('end_date', '<', now())
            ->update(['is_active' => false]);
    }

    /**
     * Get all active delegations for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserDelegations(int $userId): Collection
    {
        return ApprovalDelegation::where('delegator_id', $userId)
            ->where('is_active', true)
            ->with(['delegate'])
            ->get();
    }

    /**
     * Get delegations where user is the delegate.
     *
     * @param int $userId
     * @return Collection
     */
    public function getDelegationsAsDelegate(int $userId): Collection
    {
        return ApprovalDelegation::where('delegate_id', $userId)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with(['delegator'])
            ->get();
    }
}
