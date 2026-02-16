<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExecution extends Model
{
    protected $fillable = [
        'report_id',
        'executed_by_user_id',
        'status',
        'record_count',
        'file_path',
        'file_format',
        'execution_time_ms',
        'error_message',
        'parameters',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'record_count' => 'integer',
        'execution_time_ms' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const FORMAT_PDF = 'pdf';
    const FORMAT_EXCEL = 'excel';
    const FORMAT_CSV = 'csv';
    const FORMAT_JSON = 'json';

    /**
     * Get the report.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class, 'report_id');
    }

    /**
     * Get the user who executed the report.
     */
    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'executed_by_user_id');
    }

    /**
     * Mark as running.
     */
    public function markAsRunning(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark as completed.
     */
    public function markAsCompleted(int $recordCount, string $filePath): void
    {
        $executionTime = now()->diffInMilliseconds($this->started_at);
        
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'record_count' => $recordCount,
            'file_path' => $filePath,
            'execution_time_ms' => $executionTime,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
            'completed_at' => now(),
        ]);
    }

    /**
     * Scope to get executions by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get available formats.
     */
    public static function getFormats(): array
    {
        return [
            self::FORMAT_PDF,
            self::FORMAT_EXCEL,
            self::FORMAT_CSV,
            self::FORMAT_JSON,
        ];
    }
}
