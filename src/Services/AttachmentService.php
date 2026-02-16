<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalAttachment;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\DocumentAccessLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    /**
     * Upload an attachment for an approval request.
     */
    public function upload(
        ApprovalRequest $request,
        UploadedFile $file,
        int $userId,
        string $type = ApprovalAttachment::TYPE_SUPPORTING_DOCUMENT,
        ?string $description = null,
        array $metadata = []
    ): ApprovalAttachment {
        // Generate unique filename
        $fileName = $this->generateFileName($file);
        $storageDisk = config('approval-process.storage.disk', 'local');
        $storagePath = config('approval-process.storage.attachments_path', 'approval-attachments');
        
        // Store file
        $filePath = $file->storeAs(
            $storagePath . '/' . $request->id,
            $fileName,
            $storageDisk
        );

        // Calculate file hash
        $hash = hash_file('sha256', $file->getRealPath());

        // Create attachment record
        $attachment = ApprovalAttachment::create([
            'approval_request_id' => $request->id,
            'uploaded_by_user_id' => $userId,
            'file_name' => $fileName,
            'original_file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getMimeType(),
            'file_extension' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'storage_disk' => $storageDisk,
            'attachment_type' => $type,
            'description' => $description,
            'metadata' => $metadata,
            'hash' => $hash,
            'scan_status' => ApprovalAttachment::SCAN_STATUS_PENDING,
        ]);

        // Trigger virus scan if enabled
        if (config('approval-process.security.enable_virus_scan', false)) {
            $this->scanForVirus($attachment);
        } else {
            $attachment->update(['scan_status' => ApprovalAttachment::SCAN_STATUS_CLEAN]);
        }

        return $attachment;
    }

    /**
     * Generate a unique filename.
     */
    protected function generateFileName(UploadedFile $file): string
    {
        return Str::uuid() . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Download an attachment.
     */
    public function download(ApprovalAttachment $attachment, int $userId): string
    {
        // Log access
        DocumentAccessLog::logAccess(
            $attachment,
            $userId,
            DocumentAccessLog::ACTION_DOWNLOADED
        );

        return $attachment->getContents();
    }

    /**
     * Delete an attachment.
     */
    public function delete(ApprovalAttachment $attachment): bool
    {
        // Delete physical file
        $attachment->deleteFile();

        // Soft delete record
        return $attachment->delete();
    }

    /**
     * Get attachments for a request.
     */
    public function getRequestAttachments(int $requestId, ?string $type = null): array
    {
        $query = ApprovalAttachment::where('approval_request_id', $requestId)
            ->with('uploadedBy');

        if ($type) {
            $query->byType($type);
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Scan attachment for viruses (placeholder - integrate with actual AV service).
     */
    protected function scanForVirus(ApprovalAttachment $attachment): void
    {
        // This is a placeholder. In production, integrate with:
        // - ClamAV
        // - VirusTotal API
        // - AWS S3 Malware Scanning
        // - Azure Defender for Storage
        
        // For now, mark as clean
        $attachment->update([
            'scan_status' => ApprovalAttachment::SCAN_STATUS_CLEAN,
            'scanned_at' => now(),
        ]);
    }

    /**
     * Validate file upload.
     */
    public function validateUpload(UploadedFile $file): array
    {
        $errors = [];
        
        $maxSize = config('approval-process.storage.max_file_size', 10 * 1024 * 1024); // 10MB default
        $allowedTypes = config('approval-process.storage.allowed_mime_types', [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
        }

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }

        return $errors;
    }

    /**
     * Get attachment statistics.
     */
    public function getStatistics(int $requestId): array
    {
        $attachments = ApprovalAttachment::where('approval_request_id', $requestId)->get();

        return [
            'total_count' => $attachments->count(),
            'total_size' => $attachments->sum('file_size'),
            'by_type' => $attachments->groupBy('attachment_type')->map->count()->toArray(),
            'by_extension' => $attachments->groupBy('file_extension')->map->count()->toArray(),
        ];
    }

    /**
     * Bulk download attachments as ZIP.
     */
    public function bulkDownload(array $attachmentIds, int $userId): string
    {
        $attachments = ApprovalAttachment::whereIn('id', $attachmentIds)->get();
        
        $zip = new \ZipArchive();
        $zipFileName = 'attachments_' . time() . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            foreach ($attachments as $attachment) {
                if ($attachment->exists()) {
                    $zip->addFromString(
                        $attachment->original_file_name,
                        $attachment->getContents()
                    );

                    // Log access
                    DocumentAccessLog::logAccess(
                        $attachment,
                        $userId,
                        DocumentAccessLog::ACTION_DOWNLOADED
                    );
                }
            }
            $zip->close();
        }

        return $zipPath;
    }
}
