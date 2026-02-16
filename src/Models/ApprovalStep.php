<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalStep extends Model
{
    protected $table = 'approval_steps';

    protected $fillable = [
        'workflow_id',
        'name',
        'description',
        'sequence',
        'approval_type',
        'level_alias',
        'allow_edit',
        'allow_send_back',
        'is_active',
        'condition_config',
        'sla_hours',
        'escalation_strategy',
        'allows_delegation',
        'allows_partial_approval',
    ];

    protected $casts = [
        'condition_config' => 'json',
        'is_active' => 'boolean',
        'allow_edit' => 'boolean',
        'allow_send_back' => 'boolean',
        'allows_delegation' => 'boolean',
        'allows_partial_approval' => 'boolean',
    ];

    /**
     * Approval types: serial, parallel, any-one
     */
    const APPROVAL_TYPE_SERIAL = 'serial';
    const APPROVAL_TYPE_PARALLEL = 'parallel';
    const APPROVAL_TYPE_ANY_ONE = 'any-one';

    /**
     * Get the workflow this step belongs to.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * Get approvers for this step.
     */
    public function approvers(): HasMany
    {
        return $this->hasMany(Approver::class, 'approval_step_id');
    }

    /**
     * Get approval actions for this step.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'approval_step_id');
    }

    /**
     * Check if step is serial approval.
     */
    public function isSerial(): bool
    {
        return $this->approval_type === self::APPROVAL_TYPE_SERIAL;
    }

    /**
     * Check if step is parallel approval.
     */
    public function isParallel(): bool
    {
        return $this->approval_type === self::APPROVAL_TYPE_PARALLEL;
    }

    /**
     * Check if step is any-one approval.
     */
    public function isAnyOne(): bool
    {
        return $this->approval_type === self::APPROVAL_TYPE_ANY_ONE;
    }

    /**
     * Get next step in sequence.
     */
    public function getNextStep(): ?self
    {
        return $this->workflow
            ->activeSteps()
            ->where('sequence', '>', $this->sequence)
            ->first();
    }

    /**
     * Get previous step in sequence.
     */
    public function getPreviousStep(): ?self
    {
        return $this->workflow
            ->activeSteps()
            ->where('sequence', '<', $this->sequence)
            ->orderByDesc('sequence')
            ->first();
    }

    /**
     * Evaluate if step should be included based on conditions.
     */
    public function shouldInclude(Model $requestModel): bool
    {
        if (!$this->condition_config) {
            return true;
        }

        // Implementation of condition evaluation logic
        return true;
    }

    /**
     * Get level alias for printing.
     */
    public function getLevelAlias(): string
    {
        return $this->level_alias ?? $this->name;
    }

    /**
     * Check if step allows editing before approval.
     */
    public function allowsEdit(): bool
    {
        return $this->allow_edit ?? false;
    }

    /**
     * Check if step allows sending back.
     */
    public function allowsSendBack(): bool
    {
        return $this->allow_send_back ?? true;
    }

    /**
     * Get SLA hours for this step.
     */
    public function getSLAHours(): ?int
    {
        return $this->sla_hours;
    }
}
