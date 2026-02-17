<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'approval_workflows';

    protected $fillable = [
        'name',
        'description',
        'model_type',
        'is_active',
        'version',
        'config',
    ];

    protected $casts = [
        'config' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Get the approval steps for this workflow.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'workflow_id');
    }

    /**
     * Get all approval requests for this workflow.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'workflow_id');
    }

    /**
     * Get active steps ordered by sequence.
     */
    public function activeSteps()
    {
        return $this->steps()
            ->where('is_active', true)
            ->orderBy('sequence');
    }

    /**
     * Clone a workflow with all its steps.
     */
    public function cloneWorkflow(string $newName): self
    {
        $clone = $this->replicate();
        $clone->name = $newName;
        $clone->version = $this->version + 1;
        $clone->save();

        foreach ($this->steps as $step) {
            $step->replicate()
                ->fill(['workflow_id' => $clone->id])
                ->save();
        }

        return $clone;
    }

    /**
     * Enable the workflow.
     */
    public function enable(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Disable the workflow.
     */
    public function disable(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Check if workflow can be applied to a request.
     */
    public function canApplyTo(Model $model): bool
    {
        return $this->model_type === $model::class && $this->is_active;
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \AshiqFardus\ApprovalProcess\Tests\Factories\WorkflowFactory::new();
    }
}
