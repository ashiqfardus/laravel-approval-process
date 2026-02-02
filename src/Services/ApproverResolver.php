<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\Approver;
use AshiqFardus\ApprovalProcess\Models\ApprovalStep;
use AshiqFardus\ApprovalProcess\Models\Delegation;
use Illuminate\Database\Eloquent\Model;

class ApproverResolver
{
    /**
     * Resolve approvers for a step.
     */
    public function resolve(ApprovalStep $step, Model $model): array
    {
        $approvers = $step->approvers()->get();
        $resolvedApprovers = [];

        foreach ($approvers as $approver) {
            if ($approver->approver_type === Approver::TYPE_USER) {
                $resolvedApprovers[] = $approver->user_id;
            } elseif ($approver->approver_type === Approver::TYPE_ROLE) {
                $resolvedApprovers = array_merge(
                    $resolvedApprovers,
                    $this->resolveByRole($approver->approver_id)
                );
            } elseif ($approver->approver_type === Approver::TYPE_MANAGER) {
                $resolvedApprovers[] = $this->resolveByManager($model);
            } elseif ($approver->approver_type === Approver::TYPE_DEPARTMENT_HEAD) {
                $resolvedApprovers[] = $this->resolveByDepartmentHead($model);
            } elseif ($approver->approver_type === Approver::TYPE_POSITION) {
                $resolvedApprovers = array_merge(
                    $resolvedApprovers,
                    $this->resolveByPosition($approver->approver_id)
                );
            } elseif ($approver->approver_type === Approver::TYPE_CUSTOM) {
                $resolvedApprovers = array_merge(
                    $resolvedApprovers,
                    $this->resolveCustom($approver->approver_id, $model)
                );
            }
        }

        return array_unique($resolvedApprovers);
    }

    /**
     * Resolve approvers by role.
     */
    protected function resolveByRole(string $role): array
    {
        $userModel = config('auth.providers.users.model');

        return $userModel::query()
            ->whereHas('roles', fn ($q) => $q->where('name', $role))
            ->pluck('id')
            ->toArray();
    }

    /**
     * Resolve approver by reporting manager.
     */
    protected function resolveByManager(Model $model): ?int
    {
        // This assumes model has manager_id or reportable relationship
        if (method_exists($model, 'getManagerId')) {
            return $model->getManagerId();
        }

        if (isset($model->manager_id)) {
            return $model->manager_id;
        }

        return null;
    }

    /**
     * Resolve approver by department head.
     */
    protected function resolveByDepartmentHead(Model $model): ?int
    {
        // Implementation depends on your user/department structure
        return null;
    }

    /**
     * Resolve approvers by position.
     */
    protected function resolveByPosition(string $position): array
    {
        $userModel = config('auth.providers.users.model');

        return $userModel::query()
            ->where('position', $position)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Resolve approvers using custom callback.
     */
    protected function resolveCustom(string $callableClass, Model $model): array
    {
        if (class_exists($callableClass)) {
            $resolver = new $callableClass();

            if (method_exists($resolver, 'resolve')) {
                return (array) $resolver->resolve($model);
            }
        }

        return [];
    }

    /**
     * Get effective approver considering delegations.
     */
    public function getEffectiveApprover(int $userId, ApprovalStep $step): int
    {
        $delegation = Delegation::active()
            ->where('user_id', $userId)
            ->where(function ($query) use ($step) {
                $query->whereNull('approval_step_id')
                    ->orWhere('approval_step_id', $step->id);
            })
            ->first();

        if ($delegation) {
            return $delegation->delegated_to_user_id;
        }

        return $userId;
    }

    /**
     * Check if user has fallback approver.
     */
    public function getFallbackApprover(ApprovalStep $step): ?int
    {
        // Implementation to get fallback approver from step config
        $config = $step->condition_config ?? [];

        return $config['fallback_approver_id'] ?? null;
    }
}
