<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalAttachment;
use AshiqFardus\ApprovalProcess\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    protected AttachmentService $service;

    public function __construct(AttachmentService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all attachments for a request.
     */
    public function index($request_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        $type = request()->input('type');

        $attachments = $this->service->getRequestAttachments($request->id, $type);

        return response()->json($attachments);
    }

    /**
     * Upload an attachment.
     */
    public function store($request_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        $httpRequest = request();

        if (!$httpRequest->hasFile('file')) {
            return response()->json(['message' => 'No file provided'], 422);
        }

        $file = $httpRequest->file('file');

        // Validate file
        $errors = $this->service->validateUpload($file);
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        try {
            $attachment = $this->service->upload(
                $request,
                $file,
                auth()->id() ?? $request->requested_by_user_id,
                $httpRequest->input('type', ApprovalAttachment::TYPE_SUPPORTING_DOCUMENT),
                $httpRequest->input('description'),
                $httpRequest->input('metadata', [])
            );

            return response()->json([
                'message' => 'File uploaded successfully',
                'attachment' => $attachment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific attachment.
     */
    public function show($request_id, $attachment_id): JsonResponse
    {
        $attachment = ApprovalAttachment::where('approval_request_id', $request_id)
            ->findOrFail($attachment_id);

        return response()->json($attachment);
    }

    /**
     * Download an attachment.
     */
    public function download($request_id, $attachment_id)
    {
        $attachment = ApprovalAttachment::where('approval_request_id', $request_id)
            ->findOrFail($attachment_id);

        $userId = auth()->id() ?? 1;

        try {
            $content = $this->service->download($attachment, $userId);

            return response($content)
                ->header('Content-Type', $attachment->file_type)
                ->header('Content-Disposition', 'attachment; filename="' . $attachment->original_file_name . '"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Download failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an attachment.
     */
    public function destroy($request_id, $attachment_id): JsonResponse
    {
        $attachment = ApprovalAttachment::where('approval_request_id', $request_id)
            ->findOrFail($attachment_id);

        try {
            $this->service->delete($attachment);

            return response()->json([
                'message' => 'Attachment deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attachment statistics.
     */
    public function statistics($request_id): JsonResponse
    {
        $request = ApprovalRequest::findOrFail($request_id);
        $stats = $this->service->getStatistics($request->id);

        return response()->json($stats);
    }

    /**
     * Bulk download attachments.
     */
    public function bulkDownload($request_id): JsonResponse
    {
        $httpRequest = request();
        $attachmentIds = $httpRequest->input('attachment_ids', []);

        if (empty($attachmentIds)) {
            return response()->json(['message' => 'No attachments selected'], 422);
        }

        $userId = auth()->id() ?? 1;

        try {
            $zipPath = $this->service->bulkDownload($attachmentIds, $userId);

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Bulk download failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
