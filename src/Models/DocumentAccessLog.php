<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentAccessLog extends Model
{
    protected $fillable = [
        'document_type',
        'document_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    const ACTION_VIEWED = 'viewed';
    const ACTION_DOWNLOADED = 'downloaded';
    const ACTION_PRINTED = 'printed';
    const ACTION_SHARED = 'shared';

    /**
     * Get the document.
     */
    public function document(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'user_id');
    }

    /**
     * Get available actions.
     */
    public static function getActions(): array
    {
        return [
            self::ACTION_VIEWED,
            self::ACTION_DOWNLOADED,
            self::ACTION_PRINTED,
            self::ACTION_SHARED,
        ];
    }

    /**
     * Log an access event.
     */
    public static function logAccess(
        Model $document,
        int $userId,
        string $action,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'document_type' => get_class($document),
            'document_id' => $document->id,
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }

    /**
     * Scope to get logs by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
