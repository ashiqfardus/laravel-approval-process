<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowMetric extends Model
{
    protected $fillable = [
        'workflow_id',
        'metric_date',
        'total_requests',
        'approved_requests',
        'rejected_requests',
        'pending_requests',
        'cancelled_requests',
        'avg_approval_time_hours',
        'avg_steps_completed',
        'approval_rate',
        'rejection_rate',
        'sla_compliance_rate',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'total_requests' => 'integer',
        'approved_requests' => 'integer',
        'rejected_requests' => 'integer',
        'pending_requests' => 'integer',
        'cancelled_requests' => 'integer',
        'avg_approval_time_hours' => 'decimal:2',
        'avg_steps_completed' => 'decimal:2',
        'approval_rate' => 'decimal:2',
        'rejection_rate' => 'decimal:2',
        'sla_compliance_rate' => 'decimal:2',
    ];

    /**
     * Get the workflow.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * Scope to get metrics for date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('metric_date', [$start, $end]);
    }

    /**
     * Scope to get metrics for workflow.
     */
    public function scopeForWorkflow($query, int $workflowId)
    {
        return $query->where('workflow_id', $workflowId);
    }
}
