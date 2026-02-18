<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovableEntity extends Model
{
    protected $table = 'approval_approvable_entities';

    protected $fillable = [
        'type',
        'name',
        'label',
        'connection',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the full identifier (with connection if applicable).
     */
    public function getFullIdentifierAttribute(): string
    {
        $identifier = $this->type === 'table' ? "table:{$this->name}" : $this->name;
        
        if ($this->connection) {
            $identifier .= "@{$this->connection}";
        }
        
        return $identifier;
    }

    /**
     * Parse a full identifier string.
     */
    public static function parseIdentifier(string $identifier): array
    {
        $connection = null;
        
        // Check for connection suffix
        if (str_contains($identifier, '@')) {
            [$identifier, $connection] = explode('@', $identifier, 2);
        }
        
        // Check if it's a table or model
        if (str_starts_with($identifier, 'table:')) {
            return [
                'type' => 'table',
                'name' => substr($identifier, 6),
                'connection' => $connection,
            ];
        }
        
        return [
            'type' => 'model',
            'name' => $identifier,
            'connection' => $connection,
        ];
    }
}
