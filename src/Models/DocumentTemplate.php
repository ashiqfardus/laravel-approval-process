<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'content',
        'file_type',
        'variables',
        'settings',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'variables' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    const FILE_TYPE_PDF = 'pdf';
    const FILE_TYPE_DOCX = 'docx';
    const FILE_TYPE_HTML = 'html';
    const FILE_TYPE_TXT = 'txt';

    const CATEGORY_CONTRACT = 'contract';
    const CATEGORY_INVOICE = 'invoice';
    const CATEGORY_REPORT = 'report';
    const CATEGORY_LETTER = 'letter';
    const CATEGORY_CERTIFICATE = 'certificate';

    /**
     * Get the user who created the template.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('approval-process.models.user'), 'created_by_user_id');
    }

    /**
     * Get generated documents.
     */
    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class, 'template_id');
    }

    /**
     * Scope to get active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get templates by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get all available categories.
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_CONTRACT,
            self::CATEGORY_INVOICE,
            self::CATEGORY_REPORT,
            self::CATEGORY_LETTER,
            self::CATEGORY_CERTIFICATE,
        ];
    }

    /**
     * Get all available file types.
     */
    public static function getFileTypes(): array
    {
        return [
            self::FILE_TYPE_PDF,
            self::FILE_TYPE_DOCX,
            self::FILE_TYPE_HTML,
            self::FILE_TYPE_TXT,
        ];
    }

    /**
     * Extract variables from template content.
     */
    public function extractVariables(): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $this->content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Validate template data against required variables.
     */
    public function validateData(array $data): array
    {
        $errors = [];
        $requiredVars = $this->variables['required'] ?? [];

        foreach ($requiredVars as $var) {
            if (!isset($data[$var]) || empty($data[$var])) {
                $errors[] = "Required variable '{$var}' is missing";
            }
        }

        return $errors;
    }

    /**
     * Render template with data.
     */
    public function render(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }
}
