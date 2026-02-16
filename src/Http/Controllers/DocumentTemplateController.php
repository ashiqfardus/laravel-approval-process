<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\DocumentTemplate;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Services\DocumentTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentTemplateController extends Controller
{
    protected DocumentTemplateService $service;

    public function __construct(DocumentTemplateService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all templates.
     */
    public function index(): JsonResponse
    {
        $query = DocumentTemplate::with('createdBy');

        if (request()->has('category')) {
            $query->byCategory(request()->input('category'));
        }

        if (request()->input('active_only', false)) {
            $query->active();
        }

        $templates = $query->orderBy('name')->get();

        return response()->json($templates);
    }

    /**
     * Create a new template.
     */
    public function store(): JsonResponse
    {
        $httpRequest = request();

        try {
            $template = $this->service->createTemplate(
                $httpRequest->only([
                    'name',
                    'code',
                    'description',
                    'category',
                    'content',
                    'file_type',
                    'variables',
                    'settings',
                    'is_active',
                ]),
                auth()->id() ?? 1
            );

            return response()->json([
                'message' => 'Template created successfully',
                'template' => $template,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Creation failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get a specific template.
     */
    public function show($template_id): JsonResponse
    {
        $template = DocumentTemplate::with('createdBy')->findOrFail($template_id);

        return response()->json($template);
    }

    /**
     * Update a template.
     */
    public function update($template_id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($template_id);
        $httpRequest = request();

        $template->update($httpRequest->only([
            'name',
            'description',
            'category',
            'content',
            'file_type',
            'variables',
            'settings',
            'is_active',
        ]));

        return response()->json([
            'message' => 'Template updated successfully',
            'template' => $template->fresh('createdBy'),
        ]);
    }

    /**
     * Delete a template.
     */
    public function destroy($template_id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($template_id);
        $template->delete();

        return response()->json([
            'message' => 'Template deleted successfully'
        ], 200);
    }

    /**
     * Generate document from template.
     */
    public function generate($template_id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($template_id);
        $httpRequest = request();

        $requestId = $httpRequest->input('approval_request_id');
        $data = $httpRequest->input('data', []);

        if (!$requestId) {
            return response()->json(['message' => 'Approval request ID is required'], 422);
        }

        $request = ApprovalRequest::findOrFail($requestId);

        try {
            $document = $this->service->generateDocument(
                $template,
                $request,
                $data,
                auth()->id() ?? 1
            );

            return response()->json([
                'message' => 'Document generated successfully',
                'document' => $document,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Generation failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Preview template with sample data.
     */
    public function preview($template_id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($template_id);
        $sampleData = request()->input('data', []);

        $preview = $this->service->preview($template, $sampleData);

        return response()->json([
            'preview' => $preview
        ]);
    }

    /**
     * Clone a template.
     */
    public function clone($template_id): JsonResponse
    {
        $template = DocumentTemplate::findOrFail($template_id);
        $newName = request()->input('name');

        try {
            $clonedTemplate = $this->service->cloneTemplate(
                $template,
                auth()->id() ?? 1,
                $newName
            );

            return response()->json([
                'message' => 'Template cloned successfully',
                'template' => $clonedTemplate,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cloning failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get template statistics.
     */
    public function statistics($template_id): JsonResponse
    {
        $stats = $this->service->getTemplateStatistics($template_id);

        return response()->json($stats);
    }

    /**
     * Validate template syntax.
     */
    public function validate(): JsonResponse
    {
        $content = request()->input('content');
        $variables = request()->input('variables', []);

        $errors = $this->service->validateTemplate($content, $variables);

        return response()->json([
            'valid' => empty($errors),
            'errors' => $errors,
        ]);
    }

    /**
     * Get available categories.
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'categories' => DocumentTemplate::getCategories()
        ]);
    }

    /**
     * Get available file types.
     */
    public function fileTypes(): JsonResponse
    {
        return response()->json([
            'file_types' => DocumentTemplate::getFileTypes()
        ]);
    }
}
