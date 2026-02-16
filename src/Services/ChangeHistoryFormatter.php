<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalChangeLog;
use Illuminate\Support\Collection;

class ChangeHistoryFormatter
{
    /**
     * Format change log as human-readable text.
     *
     * @param ApprovalChangeLog $change
     * @return string
     */
    public function formatChange(ApprovalChangeLog $change): string
    {
        $userName = $change->user?->name ?? 'System';
        $timestamp = $change->created_at->format('Y-m-d H:i:s');
        $fieldName = $this->formatFieldName($change->field_name);
        
        $oldValue = $this->formatValue($change->old_value);
        $newValue = $this->formatValue($change->new_value);

        if ($change->old_value === null) {
            return "{$userName} added {$fieldName}: {$newValue} on {$timestamp}";
        }

        if ($change->new_value === null) {
            return "{$userName} removed {$fieldName}: {$oldValue} on {$timestamp}";
        }

        return "{$userName} changed {$fieldName} from '{$oldValue}' to '{$newValue}' on {$timestamp}";
    }

    /**
     * Format multiple changes as a list.
     *
     * @param Collection $changes
     * @return string
     */
    public function formatChanges(Collection $changes): string
    {
        if ($changes->isEmpty()) {
            return 'No changes recorded.';
        }

        $lines = [];
        foreach ($changes as $change) {
            $lines[] = '- ' . $this->formatChange($change);
        }

        return implode("\n", $lines);
    }

    /**
     * Format change history for a request.
     *
     * @param ApprovalRequest $request
     * @param array $options
     * @return string
     */
    public function formatRequestHistory(ApprovalRequest $request, array $options = []): string
    {
        $changes = ApprovalChangeLog::getChangesForRequest($request);

        if ($changes->isEmpty()) {
            return "No changes recorded for this request.";
        }

        $groupBy = $options['group_by'] ?? 'date'; // 'date', 'user', 'field', null

        if ($groupBy === 'date') {
            return $this->formatGroupedByDate($changes);
        }

        if ($groupBy === 'user') {
            return $this->formatGroupedByUser($changes);
        }

        if ($groupBy === 'field') {
            return $this->formatGroupedByField($changes);
        }

        return $this->formatChanges($changes);
    }

    /**
     * Format changes grouped by date.
     *
     * @param Collection $changes
     * @return string
     */
    protected function formatGroupedByDate(Collection $changes): string
    {
        $grouped = $changes->groupBy(function ($change) {
            return $change->created_at->format('Y-m-d');
        });

        $lines = [];
        foreach ($grouped as $date => $dateChanges) {
            $lines[] = "\n## {$date}";
            foreach ($dateChanges as $change) {
                $lines[] = '  - ' . $this->formatChange($change);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Format changes grouped by user.
     *
     * @param Collection $changes
     * @return string
     */
    protected function formatGroupedByUser(Collection $changes): string
    {
        $grouped = $changes->groupBy('user_id');

        $lines = [];
        foreach ($grouped as $userId => $userChanges) {
            $userName = $userChanges->first()->user?->name ?? 'System';
            $lines[] = "\n## Changes by {$userName}";
            foreach ($userChanges as $change) {
                $lines[] = '  - ' . $this->formatChange($change);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Format changes grouped by field.
     *
     * @param Collection $changes
     * @return string
     */
    protected function formatGroupedByField(Collection $changes): string
    {
        $grouped = $changes->groupBy('field_name');

        $lines = [];
        foreach ($grouped as $fieldName => $fieldChanges) {
            $formattedFieldName = $this->formatFieldName($fieldName);
            $lines[] = "\n## {$formattedFieldName}";
            foreach ($fieldChanges as $change) {
                $userName = $change->user?->name ?? 'System';
                $timestamp = $change->created_at->format('H:i:s');
                $oldValue = $this->formatValue($change->old_value);
                $newValue = $this->formatValue($change->new_value);
                
                if ($change->old_value === null) {
                    $lines[] = "  - {$userName} set to '{$newValue}' at {$timestamp}";
                } elseif ($change->new_value === null) {
                    $lines[] = "  - {$userName} removed (was '{$oldValue}') at {$timestamp}";
                } else {
                    $lines[] = "  - {$userName} changed from '{$oldValue}' to '{$newValue}' at {$timestamp}";
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Format field name for display.
     *
     * @param string $fieldName
     * @return string
     */
    protected function formatFieldName(string $fieldName): string
    {
        // Convert snake_case to Title Case
        return str_replace('_', ' ', ucwords($fieldName, '_'));
    }

    /**
     * Format value for display.
     *
     * @param mixed $value
     * @param int $maxLength
     * @return string
     */
    protected function formatValue($value, int $maxLength = 100): string
    {
        if ($value === null) {
            return '(empty)';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value) || is_object($value)) {
            $json = json_encode($value, JSON_PRETTY_PRINT);
            if (strlen($json) > $maxLength) {
                return substr($json, 0, $maxLength) . '...';
            }
            return $json;
        }

        $stringValue = (string) $value;
        if (strlen($stringValue) > $maxLength) {
            return substr($stringValue, 0, $maxLength) . '...';
        }

        return $stringValue;
    }

    /**
     * Get HTML formatted change history.
     *
     * @param ApprovalRequest $request
     * @return string
     */
    public function formatAsHtml(ApprovalRequest $request): string
    {
        $changes = ApprovalChangeLog::getChangesForRequest($request);

        if ($changes->isEmpty()) {
            return '<p>No changes recorded.</p>';
        }

        $html = '<div class="change-history">';
        foreach ($changes as $change) {
            $html .= '<div class="change-item">';
            $html .= '<span class="field-name">' . htmlspecialchars($this->formatFieldName($change->field_name)) . '</span>';
            $html .= '<span class="old-value">' . htmlspecialchars($this->formatValue($change->old_value)) . '</span>';
            $html .= ' → ';
            $html .= '<span class="new-value">' . htmlspecialchars($this->formatValue($change->new_value)) . '</span>';
            $html .= '<span class="timestamp">' . $change->created_at->format('Y-m-d H:i:s') . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
