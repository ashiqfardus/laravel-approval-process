<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalChangeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ChangeTrackingService
{
    /**
     * Track changes between old and new data snapshots.
     *
     * @param ApprovalRequest $request
     * @param array $oldData
     * @param array $newData
     * @param int $userId
     * @param array $options
     * @return Collection Collection of ApprovalChangeLog models
     */
    public function trackChanges(
        ApprovalRequest $request,
        array $oldData,
        array $newData,
        int $userId,
        array $options = []
    ): Collection {
        $changes = collect();
        $ignoredFields = $options['ignored_fields'] ?? ['id', 'created_at', 'updated_at', 'deleted_at'];
        $onlyFields = $options['only_fields'] ?? null;

        // Merge arrays to get all possible keys
        $allKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));

        foreach ($allKeys as $field) {
            // Skip ignored fields
            if (in_array($field, $ignoredFields)) {
                continue;
            }

            // If only_fields is specified, only track those
            if ($onlyFields !== null && !in_array($field, $onlyFields)) {
                continue;
            }

            $oldValue = $oldData[$field] ?? null;
            $newValue = $newData[$field] ?? null;

            // Check if value has changed
            if ($this->hasValueChanged($oldValue, $newValue)) {
                $change = ApprovalChangeLog::logChange(
                    $request,
                    $userId,
                    $field,
                    $oldValue,
                    $newValue,
                    $options['metadata'] ?? []
                );

                $changes->push($change);
            }
        }

        return $changes;
    }

    /**
     * Track changes from model update.
     *
     * @param ApprovalRequest $request
     * @param Model $oldModel
     * @param Model $newModel
     * @param int $userId
     * @param array $options
     * @return Collection
     */
    public function trackModelChanges(
        ApprovalRequest $request,
        Model $oldModel,
        Model $newModel,
        int $userId,
        array $options = []
    ): Collection {
        $oldData = $oldModel->getAttributes();
        $newData = $newModel->getAttributes();

        return $this->trackChanges($request, $oldData, $newData, $userId, $options);
    }

    /**
     * Track changes from data snapshot update.
     *
     * @param ApprovalRequest $request
     * @param array $newSnapshot
     * @param int $userId
     * @param array $options
     * @return Collection
     */
    public function trackSnapshotChanges(
        ApprovalRequest $request,
        array $newSnapshot,
        int $userId,
        array $options = []
    ): Collection {
        $oldSnapshot = $request->data_snapshot ?? [];

        return $this->trackChanges($request, $oldSnapshot, $newSnapshot, $userId, $options);
    }

    /**
     * Check if a value has changed.
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return bool
     */
    protected function hasValueChanged($oldValue, $newValue): bool
    {
        // Handle null cases
        if ($oldValue === null && $newValue === null) {
            return false;
        }

        if ($oldValue === null || $newValue === null) {
            return true;
        }

        // Compare arrays/objects
        if (is_array($oldValue) && is_array($newValue)) {
            return json_encode($oldValue) !== json_encode($newValue);
        }

        // Compare strings/numbers
        return (string) $oldValue !== (string) $newValue;
    }

    /**
     * Get change summary for a request.
     *
     * @param ApprovalRequest $request
     * @return array
     */
    public function getChangeSummary(ApprovalRequest $request): array
    {
        $changes = ApprovalChangeLog::getChangesForRequest($request);

        return [
            'total_changes' => $changes->count(),
            'changed_fields' => $changes->pluck('field_name')->unique()->values()->toArray(),
            'changes_by_field' => ApprovalChangeLog::getChangesGroupedByField($request),
            'latest_change_at' => $changes->max('created_at')?->toIso8601String(),
            'first_change_at' => $changes->min('created_at')?->toIso8601String(),
        ];
    }

    /**
     * Get changes for specific fields.
     *
     * @param ApprovalRequest $request
     * @param array $fields
     * @return Collection
     */
    public function getChangesForFields(ApprovalRequest $request, array $fields): Collection
    {
        return ApprovalChangeLog::where('approval_request_id', $request->id)
            ->whereIn('field_name', $fields)
            ->orderBy('created_at')
            ->get();
    }
}
