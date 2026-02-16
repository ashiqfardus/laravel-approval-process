<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMetric extends Model
{
    protected $fillable = [
        'user_id',
        'metric_date',
        'requests_submitted',
        'approvals_given',
        'rejections_given',
        'pending_approvals',
        'avg_response_time_hours',
        'overdue_approvals',
        'delegations_created',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'requests_submitted' => 'integer',
        'approvals_given' => 'integer',
        'rejections_given' => 'integer',
        'pending_approvals' => 'integer',
        'avg_response_time_hours' => 'decimal:2',
        'overdue_approvals' => 'integer',
        'delegations_created' => 'integer',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'user_id');
    }

    /**
     * Scope to get metrics for date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('metric_date', [$start, $end]);
    }

    /**
     * Scope to get metrics for user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
