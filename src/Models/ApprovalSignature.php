<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalSignature extends Model
{
    protected $fillable = [
        'approval_request_id',
        'approval_action_id',
        'user_id',
        'signature_type',
        'signature_data',
        'signature_format',
        'ip_address',
        'user_agent',
        'device_info',
        'geolocation',
        'verification_method',
        'is_verified',
        'verified_at',
        'certificate_serial',
        'certificate_expires_at',
    ];

    protected $casts = [
        'device_info' => 'array',
        'geolocation' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'certificate_expires_at' => 'datetime',
    ];

    const TYPE_DRAWN = 'drawn';
    const TYPE_TYPED = 'typed';
    const TYPE_UPLOADED = 'uploaded';
    const TYPE_DIGITAL_CERTIFICATE = 'digital_certificate';

    const FORMAT_PNG = 'png';
    const FORMAT_SVG = 'svg';
    const FORMAT_PKCS7 = 'pkcs7';

    const VERIFICATION_EMAIL = 'email';
    const VERIFICATION_SMS = 'sms';
    const VERIFICATION_BIOMETRIC = 'biometric';

    /**
     * Get the approval request.
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    /**
     * Get the approval action.
     */
    public function approvalAction(): BelongsTo
    {
        return $this->belongsTo(ApprovalAction::class, 'approval_action_id');
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'user_id');
    }

    /**
     * Get signature types.
     */
    public static function getSignatureTypes(): array
    {
        return [
            self::TYPE_DRAWN,
            self::TYPE_TYPED,
            self::TYPE_UPLOADED,
            self::TYPE_DIGITAL_CERTIFICATE,
        ];
    }

    /**
     * Get verification methods.
     */
    public static function getVerificationMethods(): array
    {
        return [
            self::VERIFICATION_EMAIL,
            self::VERIFICATION_SMS,
            self::VERIFICATION_BIOMETRIC,
        ];
    }

    /**
     * Mark as verified.
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Check if certificate is expired.
     */
    public function isCertificateExpired(): bool
    {
        return $this->certificate_expires_at && $this->certificate_expires_at->isPast();
    }

    /**
     * Scope to get verified signatures.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get signatures by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('signature_type', $type);
    }
}
