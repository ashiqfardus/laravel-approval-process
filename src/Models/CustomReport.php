<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'report_type',
        'filters',
        'columns',
        'grouping',
        'sorting',
        'chart_config',
        'is_scheduled',
        'schedule_frequency',
        'schedule_recipients',
        'is_public',
        'created_by_user_id',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'grouping' => 'array',
        'sorting' => 'array',
        'chart_config' => 'array',
        'schedule_recipients' => 'array',
        'is_scheduled' => 'boolean',
        'is_public' => 'boolean',
    ];

    const TYPE_SUMMARY = 'summary';
    const TYPE_DETAILED = 'detailed';
    const TYPE_COMPARISON = 'comparison';
    const TYPE_TREND = 'trend';

    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';

    /**
     * Get the user who created the report.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'created_by_user_id');
    }

    /**
     * Get report executions.
     */
    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class, 'report_id');
    }

    /**
     * Scope to get public reports.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get scheduled reports.
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    /**
     * Scope to get reports by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Get available report types.
     */
    public static function getReportTypes(): array
    {
        return [
            self::TYPE_SUMMARY,
            self::TYPE_DETAILED,
            self::TYPE_COMPARISON,
            self::TYPE_TREND,
        ];
    }

    /**
     * Get available frequencies.
     */
    public static function getFrequencies(): array
    {
        return [
            self::FREQUENCY_DAILY,
            self::FREQUENCY_WEEKLY,
            self::FREQUENCY_MONTHLY,
        ];
    }
}
