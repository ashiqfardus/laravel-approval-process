<?php

namespace AshiqFardus\ApprovalProcess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class QueryApprovalRequest extends Model
{
    protected $fillable = [
        'approval_request_id',
        'query_type',
        'query_definition',
        'result_snapshot',
        'identifier',
        'description',
    ];

    protected $casts = [
        'query_definition' => 'array',
        'result_snapshot' => 'array',
    ];

    // Query types
    const TYPE_SQL = 'sql';
    const TYPE_VIEW = 'view';
    const TYPE_BUILDER = 'builder';
    const TYPE_API = 'api';

    /**
     * Get the approval request.
     */
    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Execute the query and return fresh results.
     *
     * @return array
     */
    public function executeQuery(): array
    {
        return match ($this->query_type) {
            self::TYPE_SQL => $this->executeSqlQuery(),
            self::TYPE_VIEW => $this->executeViewQuery(),
            self::TYPE_BUILDER => $this->executeBuilderQuery(),
            default => [],
        };
    }

    /**
     * Execute SQL query.
     */
    protected function executeSqlQuery(): array
    {
        $sql = $this->query_definition['sql'] ?? '';
        $bindings = $this->query_definition['bindings'] ?? [];

        return DB::select($sql, $bindings);
    }

    /**
     * Execute view query.
     */
    protected function executeViewQuery(): array
    {
        $viewName = $this->query_definition['view_name'] ?? '';
        $conditions = $this->query_definition['conditions'] ?? [];

        $query = DB::table($viewName);

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->get()->toArray();
    }

    /**
     * Execute query builder query.
     */
    protected function executeBuilderQuery(): array
    {
        $table = $this->query_definition['table'] ?? '';
        $wheres = $this->query_definition['wheres'] ?? [];
        $selects = $this->query_definition['selects'] ?? ['*'];

        $query = DB::table($table)->select($selects);

        foreach ($wheres as $where) {
            $query->where($where['column'], $where['operator'] ?? '=', $where['value']);
        }

        return $query->get()->toArray();
    }

    /**
     * Get the current query result.
     */
    public function getCurrentResult(): array
    {
        return $this->executeQuery();
    }

    /**
     * Get the snapshot result (at submission time).
     */
    public function getSnapshotResult(): array
    {
        return $this->result_snapshot ?? [];
    }

    /**
     * Check if query result has changed since submission.
     */
    public function hasResultChanged(): bool
    {
        $current = $this->getCurrentResult();
        $snapshot = $this->getSnapshotResult();

        return json_encode($current) !== json_encode($snapshot);
    }
}
