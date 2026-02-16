<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalSignature;
use AshiqFardus\ApprovalProcess\Services\SignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignatureController extends Controller
{
    protected SignatureService $service;

    public function __construct(SignatureService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all signatures for a request.
     */
    public function index($request_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        $signatures = $this->service->getRequestSignatures($request->id);

        return response()->json($signatures);
    }

    /**
     * Create a signature.
     */
    public function store($request_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        $httpRequest = request();

        $signatureType = $httpRequest->input('signature_type');
        $signatureData = $httpRequest->input('signature_data');

        // Validate signature data
        $errors = $this->service->validateSignatureData($signatureType, $signatureData);
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        try {
            $signature = $this->service->createSignature(
                $request,
                auth()->id() ?? $request->requested_by_user_id,
                $signatureType,
                $signatureData,
                $httpRequest->input('approval_action_id'),
                [
                    'format' => $httpRequest->input('format', 'png'),
                    'geolocation' => $httpRequest->input('geolocation'),
                    'verification_method' => $httpRequest->input('verification_method'),
                ]
            );

            return response()->json([
                'message' => 'Signature created successfully',
                'signature' => $signature,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Signature creation failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get a specific signature.
     */
    public function show($request_id, $signature_id): JsonResponse
    {
        $signature = ApprovalSignature::where('approval_request_id', $request_id)
            ->with(['user', 'approvalAction'])
            ->findOrFail($signature_id);

        return response()->json($signature);
    }

    /**
     * Verify a signature.
     */
    public function verify($request_id, $signature_id): JsonResponse
    {
        $signature = ApprovalSignature::where('approval_request_id', $request_id)
            ->findOrFail($signature_id);

        $code = request()->input('verification_code');

        try {
            $verified = $this->service->verifySignature($signature, $code);

            return response()->json([
                'message' => $verified ? 'Signature verified successfully' : 'Verification failed',
                'verified' => $verified,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get signature statistics.
     */
    public function statistics($request_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        $stats = $this->service->getStatistics($request->id);

        return response()->json($stats);
    }

    /**
     * Get available signature types.
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'signature_types' => ApprovalSignature::getSignatureTypes()
        ]);
    }

    /**
     * Get available verification methods.
     */
    public function verificationMethods(): JsonResponse
    {
        return response()->json([
            'verification_methods' => ApprovalSignature::getVerificationMethods()
        ]);
    }

    /**
     * Download signature as image.
     */
    public function downloadImage($request_id, $signature_id)
    {
        $signature = ApprovalSignature::where('approval_request_id', $request_id)
            ->findOrFail($signature_id);

        try {
            $imageData = $this->service->signatureToImage($signature);

            return response($imageData)
                ->header('Content-Type', 'image/' . $signature->signature_format)
                ->header('Content-Disposition', 'attachment; filename="signature_' . $signature->id . '.' . $signature->signature_format . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Download failed: ' . $e->getMessage()
            ], 422);
        }
    }
}
