<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\DocumentTemplate;
use AshiqFardus\ApprovalProcess\Models\GeneratedDocument;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentTemplateService
{
    /**
     * Create a new template.
     */
    public function createTemplate(array $data, int $userId): DocumentTemplate
    {
        // Extract variables from content
        $template = DocumentTemplate::create(array_merge($data, [
            'created_by_user_id' => $userId,
        ]));

        // Auto-extract variables if not provided
        if (empty($template->variables)) {
            $extractedVars = $template->extractVariables();
            $template->update([
                'variables' => [
                    'available' => $extractedVars,
                    'required' => [],
                ],
            ]);
        }

        return $template;
    }

    /**
     * Generate document from template.
     */
    public function generateDocument(
        DocumentTemplate $template,
        ApprovalRequest $request,
        array $data,
        int $userId
    ): GeneratedDocument {
        // Validate template data
        $errors = $template->validateData($data);
        if (!empty($errors)) {
            throw new \Exception('Template data validation failed: ' . implode(', ', $errors));
        }

        // Render template
        $content = $template->render($data);

        // Generate file based on type
        $fileName = $this->generateFileName($template, $request);
        $filePath = $this->saveGeneratedDocument($content, $fileName, $template->file_type);

        // Create generated document record
        return GeneratedDocument::create([
            'approval_request_id' => $request->id,
            'template_id' => $template->id,
            'generated_by_user_id' => $userId,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $template->file_type,
            'file_size' => Storage::size($filePath),
            'template_data' => $data,
            'status' => GeneratedDocument::STATUS_GENERATED,
        ]);
    }

    /**
     * Generate filename for document.
     */
    protected function generateFileName(DocumentTemplate $template, ApprovalRequest $request): string
    {
        $slug = Str::slug($template->name);
        $timestamp = now()->format('Ymd_His');
        return "{$slug}_{$request->id}_{$timestamp}.{$template->file_type}";
    }

    /**
     * Save generated document to storage.
     */
    protected function saveGeneratedDocument(string $content, string $fileName, string $fileType): string
    {
        $storagePath = config('approval-process.storage.generated_documents_path', 'generated-documents');

        switch ($fileType) {
            case DocumentTemplate::FILE_TYPE_PDF:
                return $this->generatePdf($content, $storagePath, $fileName);
            
            case DocumentTemplate::FILE_TYPE_HTML:
                return $this->saveHtml($content, $storagePath, $fileName);
            
            case DocumentTemplate::FILE_TYPE_TXT:
            default:
                return $this->saveText($content, $storagePath, $fileName);
        }
    }

    /**
     * Generate PDF from HTML content.
     */
    protected function generatePdf(string $htmlContent, string $path, string $fileName): string
    {
        // This is a placeholder. In production, integrate with:
        // - dompdf/dompdf
        // - barryvdh/laravel-dompdf
        // - mpdf/mpdf
        // - wkhtmltopdf
        
        // For now, save as HTML
        $filePath = $path . '/' . $fileName;
        Storage::put($filePath, $htmlContent);
        return $filePath;
    }

    /**
     * Save HTML content.
     */
    protected function saveHtml(string $content, string $path, string $fileName): string
    {
        $filePath = $path . '/' . $fileName;
        Storage::put($filePath, $content);
        return $filePath;
    }

    /**
     * Save text content.
     */
    protected function saveText(string $content, string $path, string $fileName): string
    {
        $filePath = $path . '/' . $fileName;
        Storage::put($filePath, $content);
        return $filePath;
    }

    /**
     * Get template by code.
     */
    public function getTemplateByCode(string $code): ?DocumentTemplate
    {
        return DocumentTemplate::where('code', $code)->active()->first();
    }

    /**
     * Get templates by category.
     */
    public function getTemplatesByCategory(string $category): array
    {
        return DocumentTemplate::byCategory($category)
            ->active()
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Preview template with sample data.
     */
    public function preview(DocumentTemplate $template, array $sampleData): string
    {
        return $template->render($sampleData);
    }

    /**
     * Clone a template.
     */
    public function cloneTemplate(DocumentTemplate $template, int $userId, ?string $newName = null): DocumentTemplate
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $newName ?? ($template->name . ' (Copy)');
        $newTemplate->code = $template->code . '_copy_' . time();
        $newTemplate->created_by_user_id = $userId;
        $newTemplate->save();

        return $newTemplate;
    }

    /**
     * Get template statistics.
     */
    public function getTemplateStatistics(int $templateId): array
    {
        $template = DocumentTemplate::findOrFail($templateId);
        $generatedDocs = $template->generatedDocuments;

        return [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'total_generated' => $generatedDocs->count(),
            'by_status' => $generatedDocs->groupBy('status')->map->count()->toArray(),
            'total_size' => $generatedDocs->sum('file_size'),
            'last_generated_at' => $generatedDocs->max('created_at'),
        ];
    }

    /**
     * Validate template syntax.
     */
    public function validateTemplate(string $content, array $variables): array
    {
        $errors = [];

        // Extract variables from content
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);
        $usedVars = array_unique($matches[1] ?? []);

        // Check for undefined variables
        $availableVars = $variables['available'] ?? [];
        foreach ($usedVars as $var) {
            if (!in_array($var, $availableVars)) {
                $errors[] = "Variable '{$var}' is used but not defined";
            }
        }

        // Check for unused variables
        foreach ($availableVars as $var) {
            if (!in_array($var, $usedVars)) {
                $errors[] = "Variable '{$var}' is defined but not used";
            }
        }

        return $errors;
    }
}
