<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalSignature;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalAction;

class SignatureService
{
    /**
     * Create a signature for an approval action.
     */
    public function createSignature(
        ApprovalRequest $request,
        int $userId,
        string $signatureType,
        string $signatureData,
        ?int $actionId = null,
        array $options = []
    ): ApprovalSignature {
        $signature = ApprovalSignature::create([
            'approval_request_id' => $request->id,
            'approval_action_id' => $actionId,
            'user_id' => $userId,
            'signature_type' => $signatureType,
            'signature_data' => $signatureData,
            'signature_format' => $options['format'] ?? 'png',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_info' => $this->getDeviceInfo(),
            'geolocation' => $options['geolocation'] ?? null,
            'verification_method' => $options['verification_method'] ?? null,
            'is_verified' => false,
        ]);

        // Auto-verify if verification method is provided
        if (isset($options['verification_method'])) {
            $this->sendVerification($signature, $options['verification_method']);
        }

        return $signature;
    }

    /**
     * Get device information.
     */
    protected function getDeviceInfo(): array
    {
        $userAgent = request()->userAgent();
        
        return [
            'user_agent' => $userAgent,
            'platform' => $this->detectPlatform($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'is_mobile' => $this->isMobile($userAgent),
        ];
    }

    /**
     * Detect platform from user agent.
     */
    protected function detectPlatform(string $userAgent): string
    {
        if (stripos($userAgent, 'Windows') !== false) return 'Windows';
        if (stripos($userAgent, 'Mac') !== false) return 'macOS';
        if (stripos($userAgent, 'Linux') !== false) return 'Linux';
        if (stripos($userAgent, 'Android') !== false) return 'Android';
        if (stripos($userAgent, 'iOS') !== false) return 'iOS';
        return 'Unknown';
    }

    /**
     * Detect browser from user agent.
     */
    protected function detectBrowser(string $userAgent): string
    {
        if (stripos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (stripos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (stripos($userAgent, 'Safari') !== false) return 'Safari';
        if (stripos($userAgent, 'Edge') !== false) return 'Edge';
        return 'Unknown';
    }

    /**
     * Check if mobile device.
     */
    protected function isMobile(string $userAgent): bool
    {
        return preg_match('/Mobile|Android|iPhone|iPad/', $userAgent) === 1;
    }

    /**
     * Send verification for signature.
     */
    protected function sendVerification(ApprovalSignature $signature, string $method): void
    {
        // This is a placeholder. In production, integrate with:
        // - Email verification (send code via email)
        // - SMS verification (Twilio, AWS SNS)
        // - Biometric verification (device-specific APIs)
        
        // For now, auto-verify
        $signature->markAsVerified();
    }

    /**
     * Verify signature with code.
     */
    public function verifySignature(ApprovalSignature $signature, string $code): bool
    {
        // Implement verification logic
        // For now, mark as verified
        $signature->markAsVerified();
        return true;
    }

    /**
     * Get signatures for a request.
     */
    public function getRequestSignatures(int $requestId): array
    {
        return ApprovalSignature::where('approval_request_id', $requestId)
            ->with(['user', 'approvalAction'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Validate signature data.
     */
    public function validateSignatureData(string $type, string $data): array
    {
        $errors = [];

        switch ($type) {
            case ApprovalSignature::TYPE_DRAWN:
                if (!$this->isValidBase64Image($data)) {
                    $errors[] = 'Invalid drawn signature format';
                }
                break;

            case ApprovalSignature::TYPE_TYPED:
                if (empty($data) || strlen($data) > 100) {
                    $errors[] = 'Typed signature must be between 1 and 100 characters';
                }
                break;

            case ApprovalSignature::TYPE_UPLOADED:
                if (!$this->isValidBase64Image($data)) {
                    $errors[] = 'Invalid uploaded signature format';
                }
                break;

            case ApprovalSignature::TYPE_DIGITAL_CERTIFICATE:
                // Validate certificate format
                if (empty($data)) {
                    $errors[] = 'Digital certificate data is required';
                }
                break;
        }

        return $errors;
    }

    /**
     * Check if data is valid base64 image.
     */
    protected function isValidBase64Image(string $data): bool
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $matches)) {
            $imageType = $matches[1];
            $allowedTypes = ['png', 'jpg', 'jpeg', 'svg+xml'];
            return in_array($imageType, $allowedTypes);
        }
        return false;
    }

    /**
     * Convert signature to image file.
     */
    public function signatureToImage(ApprovalSignature $signature): string
    {
        if ($signature->signature_type === ApprovalSignature::TYPE_DRAWN ||
            $signature->signature_type === ApprovalSignature::TYPE_UPLOADED) {
            
            // Extract base64 data
            $data = $signature->signature_data;
            if (preg_match('/^data:image\/\w+;base64,/', $data)) {
                $data = substr($data, strpos($data, ',') + 1);
            }
            
            return base64_decode($data);
        }

        throw new \Exception('Cannot convert this signature type to image');
    }

    /**
     * Get signature statistics.
     */
    public function getStatistics(int $requestId): array
    {
        $signatures = ApprovalSignature::where('approval_request_id', $requestId)->get();

        return [
            'total_count' => $signatures->count(),
            'verified_count' => $signatures->where('is_verified', true)->count(),
            'by_type' => $signatures->groupBy('signature_type')->map->count()->toArray(),
            'by_verification_method' => $signatures->groupBy('verification_method')->map->count()->toArray(),
        ];
    }
}
