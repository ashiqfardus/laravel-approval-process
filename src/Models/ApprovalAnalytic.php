<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalAnalytic extends Model
{
    protected $fillable = [
        'metric_type',
        'dimension',
        'dimension_id',
        'period',
        'period_start',
        'period_end',
        'value',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'value' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    const METRIC_REQUEST_COUNT = 'request_count';
    const METRIC_APPROVAL_TIME = 'approval_time';
    const METRIC_REJECTION_RATE = 'rejection_rate';
    const METRIC_APPROVAL_RATE = 'approval_rate';
    const METRIC_SLA_COMPLIANCE = 'sla_compliance';
    const METRIC_BOTTLENECK_COUNT = 'bottleneck_count';

    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_YEARLY = 'yearly';

    /**
     * Scope to get metrics by type.
     */
    public function scopeByMetricType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope to get metrics by period.
     */
    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope to get metrics for date range.
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('period_start', [$start, $end]);
    }

    /**
     * Get available metric types.
     */
    public static function getMetricTypes(): array
    {
        return [
            self::METRIC_REQUEST_COUNT,
            self::METRIC_APPROVAL_TIME,
            self::METRIC_REJECTION_RATE,
            self::METRIC_APPROVAL_RATE,
            self::METRIC_SLA_COMPLIANCE,
            self::METRIC_BOTTLENECK_COUNT,
        ];
    }

    /**
     * Get available periods.
     */
    public static function getPeriods(): array
    {
        return [
            self::PERIOD_DAILY,
            self::PERIOD_WEEKLY,
            self::PERIOD_MONTHLY,
            self::PERIOD_YEARLY,
        ];
    }
}
