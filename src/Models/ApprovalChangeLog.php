<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalChangeLog extends Model
{
    protected $table = 'approval_change_logs';

    protected $fillable = [
        'approval_request_id',
        'user_id',
        'field_name',
        'old_value',
        'new_value',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    /**
     * Get the approval request.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * Get the user who made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Log a change.
     */
    public static function logChange(
        ApprovalRequest $request,
        int $userId,
        string $fieldName,
        $oldValue,
        $newValue,
        array $metadata = []
    ): self {
        return static::create([
            'approval_request_id' => $request->id,
            'user_id' => $userId,
            'field_name' => $fieldName,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
            'metadata' => $metadata,
        ]);
    }
}
