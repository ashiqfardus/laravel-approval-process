<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalBottleneck extends Model
{
    protected $fillable = [
        'workflow_id',
        'step_id',
        'detected_date',
        'pending_count',
        'avg_wait_time_hours',
        'severity',
        'recommendation',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'detected_date' => 'date',
        'pending_count' => 'integer',
        'avg_wait_time_hours' => 'decimal:2',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Get the workflow.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class, 'workflow_id');
    }

    /**
     * Get the step.
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'step_id');
    }

    /**
     * Mark as resolved.
     */
    public function markAsResolved(): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Scope to get unresolved bottlenecks.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope to get bottlenecks by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Get available severities.
     */
    public static function getSeverities(): array
    {
        return [
            self::SEVERITY_LOW,
            self::SEVERITY_MEDIUM,
            self::SEVERITY_HIGH,
            self::SEVERITY_CRITICAL,
        ];
    }

    /**
     * Determine severity based on metrics.
     */
    public static function calculateSeverity(int $pendingCount, float $avgWaitTime): string
    {
        if ($pendingCount >= 50 || $avgWaitTime >= 72) {
            return self::SEVERITY_CRITICAL;
        } elseif ($pendingCount >= 30 || $avgWaitTime >= 48) {
            return self::SEVERITY_HIGH;
        } elseif ($pendingCount >= 15 || $avgWaitTime >= 24) {
            return self::SEVERITY_MEDIUM;
        }
        return self::SEVERITY_LOW;
    }
}
