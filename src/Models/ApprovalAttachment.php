<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ApprovalAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'approval_request_id',
        'uploaded_by_user_id',
        'file_name',
        'original_file_name',
        'file_path',
        'file_type',
        'file_extension',
        'file_size',
        'storage_disk',
        'attachment_type',
        'description',
        'metadata',
        'is_required',
        'is_public',
        'hash',
        'scanned_at',
        'scan_status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_required' => 'boolean',
        'is_public' => 'boolean',
        'file_size' => 'integer',
        'scanned_at' => 'datetime',
    ];

    const TYPE_SUPPORTING_DOCUMENT = 'supporting_document';
    const TYPE_SIGNATURE = 'signature';
    const TYPE_TEMPLATE_OUTPUT = 'template_output';

    const SCAN_STATUS_PENDING = 'pending';
    const SCAN_STATUS_CLEAN = 'clean';
    const SCAN_STATUS_INFECTED = 'infected';

    /**
     * Get the approval request.
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * Get the user who uploaded the attachment.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'uploaded_by_user_id');
    }

    /**
     * Get access logs.
     */
    public function accessLogs(): MorphMany
    {
        return $this->morphMany(DocumentAccessLog::class, 'document');
    }

    /**
     * Get shares.
     */
    public function shares(): MorphMany
    {
        return $this->morphMany(DocumentShare::class, 'document');
    }

    /**
     * Get the full file URL.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->storage_disk)->url($this->file_path);
    }

    /**
     * Get the file contents.
     */
    public function getContents(): string
    {
        return Storage::disk($this->storage_disk)->get($this->file_path);
    }

    /**
     * Check if file exists.
     */
    public function exists(): bool
    {
        return Storage::disk($this->storage_disk)->exists($this->file_path);
    }

    /**
     * Delete the physical file.
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            return Storage::disk($this->storage_disk)->delete($this->file_path);
        }
        return false;
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->file_type, 'image/');
    }

    /**
     * Check if file is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    /**
     * Check if file is scanned and virus-free.
     */
    public function isVirusFree(): bool
    {
        return $this->scan_status === self::SCAN_STATUS_CLEAN;
    }

    /**
     * Scope to get attachments by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('attachment_type', $type);
    }

    /**
     * Scope to get public attachments.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get virus-free attachments.
     */
    public function scopeVirusFree($query)
    {
        return $query->where('scan_status', self::SCAN_STATUS_CLEAN);
    }
}
