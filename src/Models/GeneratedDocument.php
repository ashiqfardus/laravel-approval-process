<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GeneratedDocument extends Model
{
    protected $fillable = [
        'approval_request_id',
        'template_id',
        'generated_by_user_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'template_data',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'template_data' => 'array',
        'file_size' => 'integer',
        'sent_at' => 'datetime',
    ];

    const STATUS_GENERATED = 'generated';
    const STATUS_SENT = 'sent';
    const STATUS_SIGNED = 'signed';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Get the approval request.
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * Get the template.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /**
     * Get the user who generated the document.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'generated_by_user_id');
    }

    /**
     * Get the full file URL.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get the file contents.
     */
    public function getContents(): string
    {
        return Storage::get($this->file_path);
    }

    /**
     * Check if file exists.
     */
    public function exists(): bool
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Scope to get documents by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
