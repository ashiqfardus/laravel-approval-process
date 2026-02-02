<?php

namespace AshiqFardus\ApprovalProcess\Traits;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Approvable
{
    /**
     * Get all approval requests for this model.
     */
    public function approvalRequests(): MorphMany
    {
        return $this->morphMany(ApprovalRequest::class, 'requestable');
    }

    /**
     * Submit this model for approval.
     */
    public function submitForApproval(int $userId, array $metadata = []): ApprovalRequest
    {
        $engine = app('approval-engine');

        return $engine->submitRequest($this, $userId, $metadata);
    }

    /**
     * Get the pending approval request.
     */
    public function getPendingApproval(): ?ApprovalRequest
    {
        return $this->approvalRequests()
            ->whereIn('status', [
                ApprovalRequest::STATUS_SUBMITTED,
                ApprovalRequest::STATUS_IN_REVIEW,
                ApprovalRequest::STATUS_PENDING,
            ])
            ->first();
    }

    /**
     * Check if model has a pending approval.
     */
    public function hasPendingApproval(): bool
    {
        return $this->getPendingApproval() !== null;
    }

    /**
     * Get the last approval request.
     */
    public function getLastApproval(): ?ApprovalRequest
    {
        return $this->approvalRequests()->latest()->first();
    }
}
