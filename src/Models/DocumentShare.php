<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class DocumentShare extends Model
{
    protected $fillable = [
        'document_type',
        'document_id',
        'shared_by_user_id',
        'share_token',
        'recipient_email',
        'recipient_name',
        'message',
        'requires_password',
        'password_hash',
        'expires_at',
        'max_views',
        'view_count',
        'allow_download',
        'is_active',
        'first_accessed_at',
        'last_accessed_at',
    ];

    protected $casts = [
        'requires_password' => 'boolean',
        'max_views' => 'integer',
        'view_count' => 'integer',
        'allow_download' => 'boolean',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'first_accessed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    protected $hidden = [
        'password_hash',
    ];

    /**
     * Get the document.
     */
    public function document(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who shared.
     */
    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'shared_by_user_id');
    }

    /**
     * Generate a unique share token.
     */
    public static function generateToken(): string
    {
        return Str::random(32);
    }

    /**
     * Check if share is valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_views && $this->view_count >= $this->max_views) {
            return false;
        }

        return true;
    }

    /**
     * Check if password is correct.
     */
    public function checkPassword(string $password): bool
    {
        if (!$this->requires_password) {
            return true;
        }

        return password_verify($password, $this->password_hash);
    }

    /**
     * Increment view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
        
        if (!$this->first_accessed_at) {
            $this->update(['first_accessed_at' => now()]);
        }
        
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Revoke share.
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope to get active shares.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get shares by token.
     */
    public function scopeByToken($query, string $token)
    {
        return $query->where('share_token', $token);
    }
}
