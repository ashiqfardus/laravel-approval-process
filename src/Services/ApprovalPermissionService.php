<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\Approver;
use AshiqFardus\ApprovalProcess\Models\ApprovalDelegation;
use Illuminate\Support\Collection;

class ApprovalPermissionService
{
    /**
     * Get user's approval level for a specific module.
     *
     * @param int $userId
     * @param string $module Model class name
     * @return int|null Lowest sequence number (highest authority)
     */
    public function getUserLevel(int $userId, string $module): ?int
    {
        // Check for active delegation first
        $effectiveUserId = $this->getEffectiveApprover($userId, $module);

        return Approver::whereHas('step.workflow', function ($q) use ($module) {
                $q->where('model_type', $module);
            })
            ->where('user_id', $effectiveUserId)
            ->join('approval_steps', 'approvers.approval_step_id', '=', 'approval_steps.id')
            ->min('approval_steps.sequence');
    }

    /**
     * Check if user can create documents for a module.
     *
     * @param int $userId
     * @param string $module
     * @return bool
     */
    public function canCreateDocument(int $userId, string $module): bool
    {
        // Any user in the approval chain can create documents
        return $this->getUserLevel($userId, $module) !== null;
    }

    /**
     * Get levels that should be auto-approved when higher-level user creates.
     *
     * @param int $creatorLevel
     * @return array
     */
    public function getLevelsToAutoApprove(int $creatorLevel): array
    {
        // If Level 3 creates, auto-approve Levels 1 and 2
        if ($creatorLevel <= 1) {
            return [];
        }

        return range(1, $creatorLevel - 1);
    }

    /**
     * Get effective approver considering delegations.
     *
     * @param int $userId
     * @param string|null $module
     * @return int
     */
    public function getEffectiveApprover(int $userId, ?string $module = null): int
    {
        $delegation = ApprovalDelegation::where('delegator_id', $userId)
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

        return $delegation ? $delegation->delegate_id : $userId;
    }

    /**
     * Check if user is an approver at any level for a module.
     *
     * @param int $userId
     * @param string $module
     * @return bool
     */
    public function isApprover(int $userId, string $module): bool
    {
        return Approver::whereHas('step.workflow', function ($q) use ($module) {
                $q->where('model_type', $module);
            })
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Get all users who can approve at a specific level.
     *
     * @param string $module
     * @param int $level
     * @return Collection
     */
    public function getApproversAtLevel(string $module, int $level): Collection
    {
        return Approver::whereHas('step.workflow', function ($q) use ($module) {
                $q->where('model_type', $module);
            })
            ->whereHas('step', function ($q) use ($level) {
                $q->where('sequence', $level);
            })
            ->with('user')
            ->get();
    }
}
