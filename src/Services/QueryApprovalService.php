<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\QueryApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\Workflow;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class QueryApprovalService
{
    protected ApprovalEngine $approvalEngine;

    public function __construct(ApprovalEngine $approvalEngine)
    {
        $this->approvalEngine = $approvalEngine;
    }

    /**
     * Submit approval for a database view.
     *
     * @param string $viewName
     * @param array $conditions
     * @param int $userId
     * @param array $metadata
     * @return ApprovalRequest
     */
    public function submitViewApproval(
        string $viewName,
        array $conditions,
        int $userId,
        array $metadata = []
    ): ApprovalRequest {
        // Execute query to get current data
        $query = DB::table($viewName);
        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }
        $result = $query->get()->toArray();

        // Create identifier
        $identifier = $viewName . '_' . md5(json_encode($conditions));

        // Create query definition
        $queryDefinition = [
            'view_name' => $viewName,
            'conditions' => $conditions,
        ];

        return $this->submitQueryRequest(
            queryType: QueryApprovalRequest::TYPE_VIEW,
            queryDefinition: $queryDefinition,
            resultData: $result,
            identifier: $identifier,
            userId: $userId,
            metadata: $metadata,
            description: "Approval for view: {$viewName}"
        );
    }

    /**
     * Submit approval for a raw SQL query.
     *
     * @param string $sql
     * @param array $bindings
     * @param int $userId
     * @param array $metadata
     * @return ApprovalRequest
     */
    public function submitSqlApproval(
        string $sql,
        array $bindings,
        int $userId,
        array $metadata = []
    ): ApprovalRequest {
        // Execute query to get current data
        $result = DB::select($sql, $bindings);

        // Create identifier
        $identifier = 'sql_' . md5($sql . json_encode($bindings));

        // Create query definition
        $queryDefinition = [
            'sql' => $sql,
            'bindings' => $bindings,
        ];

        return $this->submitQueryRequest(
            queryType: QueryApprovalRequest::TYPE_SQL,
            queryDefinition: $queryDefinition,
            resultData: $result,
            identifier: $identifier,
            userId: $userId,
            metadata: $metadata,
            description: "Approval for SQL query"
        );
    }

    /**
     * Submit approval for a query builder query.
     *
     * @param Builder $query
     * @param int $userId
     * @param array $metadata
     * @return ApprovalRequest
     */
    public function submitBuilderApproval(
        Builder $query,
        int $userId,
        array $metadata = []
    ): ApprovalRequest {
        // Get query components
        $table = $query->from;
        $wheres = [];
        
        foreach ($query->wheres ?? [] as $where) {
            if ($where['type'] === 'Basic') {
                $wheres[] = [
                    'column' => $where['column'],
                    'operator' => $where['operator'],
                    'value' => $where['value'],
                ];
            }
        }

        $selects = $query->columns ?? ['*'];

        // Execute query to get current data
        $result = $query->get()->toArray();

        // Create identifier
        $identifier = 'builder_' . $table . '_' . md5(json_encode($wheres));

        // Create query definition
        $queryDefinition = [
            'table' => $table,
            'wheres' => $wheres,
            'selects' => $selects,
        ];

        return $this->submitQueryRequest(
            queryType: QueryApprovalRequest::TYPE_BUILDER,
            queryDefinition: $queryDefinition,
            resultData: $result,
            identifier: $identifier,
            userId: $userId,
            metadata: $metadata,
            description: "Approval for query on table: {$table}"
        );
    }

    /**
     * Submit a query-based approval request.
     *
     * @param string $queryType
     * @param array $queryDefinition
     * @param array $resultData
     * @param string $identifier
     * @param int $userId
     * @param array $metadata
     * @param string|null $description
     * @return ApprovalRequest
     */
    public function submitQueryRequest(
        string $queryType,
        array $queryDefinition,
        array $resultData,
        string $identifier,
        int $userId,
        array $metadata = [],
        ?string $description = null
    ): ApprovalRequest {
        // Create a generic object for the approval request
        $queryObject = new \stdClass();
        $queryObject->id = $identifier;
        $queryObject->type = $queryType;
        $queryObject->data = $resultData;

        // Find workflow for query-based approvals
        $workflowType = "query:{$queryType}";
        
        // Submit the approval request using ApprovalEngine
        $approvalRequest = $this->approvalEngine->submitRequest(
            $queryObject,
            $userId,
            array_merge($metadata, ['query_based' => true])
        );

        // Create the query approval record
        QueryApprovalRequest::create([
            'approval_request_id' => $approvalRequest->id,
            'query_type' => $queryType,
            'query_definition' => $queryDefinition,
            'result_snapshot' => $resultData,
            'identifier' => $identifier,
            'description' => $description,
        ]);

        return $approvalRequest;
    }

    /**
     * Get current query result for an approval request.
     *
     * @param ApprovalRequest $request
     * @return array|null
     */
    public function getCurrentQueryResult(ApprovalRequest $request): ?array
    {
        $queryApproval = $request->queryApproval;
        
        if (!$queryApproval) {
            return null;
        }

        return $queryApproval->getCurrentResult();
    }

    /**
     * Check if query result has changed since submission.
     *
     * @param ApprovalRequest $request
     * @return bool
     */
    public function hasQueryResultChanged(ApprovalRequest $request): bool
    {
        $queryApproval = $request->queryApproval;
        
        if (!$queryApproval) {
            return false;
        }

        return $queryApproval->hasResultChanged();
    }

    /**
     * Re-execute query and update snapshot.
     *
     * @param ApprovalRequest $request
     * @return void
     */
    public function refreshQuerySnapshot(ApprovalRequest $request): void
    {
        $queryApproval = $request->queryApproval;
        
        if (!$queryApproval) {
            return;
        }

        $currentResult = $queryApproval->getCurrentResult();
        $queryApproval->update(['result_snapshot' => $currentResult]);
    }
}
