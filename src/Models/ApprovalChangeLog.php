<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;

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
            'old_value' => static::serializeValue($oldValue),
            'new_value' => static::serializeValue($newValue),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Serialize a value for storage.
     */
    protected static function serializeValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Deserialize old value.
     */
    public function getOldValueAttribute($value)
    {
        return $this->deserializeValue($value);
    }

    /**
     * Deserialize new value.
     */
    public function getNewValueAttribute($value)
    {
        return $this->deserializeValue($value);
    }

    /**
     * Deserialize a stored value.
     */
    protected function deserializeValue(?string $value)
    {
        if ($value === null) {
            return null;
        }

        // Try to decode JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }

    /**
     * Get all changes for a request.
     */
    public static function getChangesForRequest(ApprovalRequest $request): Collection
    {
        return static::where('approval_request_id', $request->id)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get changes grouped by field.
     */
    public static function getChangesGroupedByField(ApprovalRequest $request): array
    {
        $changes = static::getChangesForRequest($request);
        $grouped = [];

        foreach ($changes as $change) {
            if (!isset($grouped[$change->field_name])) {
                $grouped[$change->field_name] = [];
            }
            $grouped[$change->field_name][] = $change;
        }

        return $grouped;
    }

    /**
     * Get the latest change for a specific field.
     */
    public static function getLatestChangeForField(ApprovalRequest $request, string $fieldName): ?self
    {
        return static::where('approval_request_id', $request->id)
            ->where('field_name', $fieldName)
            ->latest('created_at')
            ->first();
    }

    /**
     * Check if value has changed.
     */
    public function hasChanged(): bool
    {
        return $this->old_value !== $this->new_value;
    }

    /**
     * Get formatted change description.
     */
    public function getFormattedChange(): string
    {
        $old = $this->old_value ?? 'empty';
        $new = $this->new_value ?? 'empty';

        if (is_array($old)) {
            $old = json_encode($old);
        }
        if (is_array($new)) {
            $new = json_encode($new);
        }

        return "Changed {$this->field_name} from '{$old}' to '{$new}'";
    }
}
