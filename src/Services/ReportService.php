<?php

namespace AshiqFardus\ApprovalProcess\Services;

use AshiqFardus\ApprovalProcess\Models\CustomReport;
use AshiqFardus\ApprovalProcess\Models\ReportExecution;
use AshiqFardus\ApprovalProcess\Models\ApprovalRequest;
use AshiqFardus\ApprovalProcess\Models\ApprovalAction;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportService
{
    /**
     * Execute a custom report.
     */
    public function executeReport(
        CustomReport $report,
        int $userId,
        array $parameters = [],
        string $format = ReportExecution::FORMAT_JSON
    ): ReportExecution {
        $execution = ReportExecution::create([
            'report_id' => $report->id,
            'executed_by_user_id' => $userId,
            'status' => ReportExecution::STATUS_PENDING,
            'file_format' => $format,
            'parameters' => $parameters,
        ]);

        try {
            $execution->markAsRunning();

            // Get report data
            $data = $this->getReportData($report, $parameters);

            // Generate file
            $filePath = $this->generateReportFile($report, $data, $format);

            $execution->markAsCompleted(count($data), $filePath);
        } catch (\Exception $e) {
            $execution->markAsFailed($e->getMessage());
        }

        return $execution->fresh();
    }

    /**
     * Get report data based on configuration.
     */
    protected function getReportData(CustomReport $report, array $parameters): array
    {
        $query = $this->buildReportQuery($report, $parameters);

        // Apply filters
        if ($report->filters) {
            $query = $this->applyFilters($query, $report->filters, $parameters);
        }

        // Apply grouping
        if ($report->grouping) {
            $query = $this->applyGrouping($query, $report->grouping);
        }

        // Apply sorting
        if ($report->sorting) {
            $query = $this->applySorting($query, $report->sorting);
        }

        return $query->get()->toArray();
    }

    /**
     * Build base query for report.
     */
    protected function buildReportQuery(CustomReport $report, array $parameters)
    {
        switch ($report->report_type) {
            case CustomReport::TYPE_SUMMARY:
                return ApprovalRequest::query()
                    ->selectRaw('status, COUNT(*) as count, AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_time')
                    ->groupBy('status');

            case CustomReport::TYPE_DETAILED:
                return ApprovalRequest::with(['workflow', 'currentStep', 'requestedBy']);

            case CustomReport::TYPE_COMPARISON:
                return ApprovalRequest::query()
                    ->selectRaw('workflow_id, status, COUNT(*) as count')
                    ->groupBy('workflow_id', 'status');

            case CustomReport::TYPE_TREND:
                return ApprovalRequest::query()
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date');

            default:
                return ApprovalRequest::query();
        }
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters($query, array $filters, array $parameters)
    {
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $parameters[$field] ?? ($filter['value'] ?? null);

            if ($field && $value !== null) {
                $query->where($field, $operator, $value);
            }
        }

        return $query;
    }

    /**
     * Apply grouping to query.
     */
    protected function applyGrouping($query, array $grouping)
    {
        foreach ($grouping as $field) {
            $query->groupBy($field);
        }

        return $query;
    }

    /**
     * Apply sorting to query.
     */
    protected function applySorting($query, array $sorting)
    {
        foreach ($sorting as $sort) {
            $field = $sort['field'] ?? null;
            $direction = $sort['direction'] ?? 'asc';

            if ($field) {
                $query->orderBy($field, $direction);
            }
        }

        return $query;
    }

    /**
     * Generate report file.
     */
    protected function generateReportFile(CustomReport $report, array $data, string $format): string
    {
        $fileName = $this->generateFileName($report, $format);
        $storagePath = config('approval-process.storage.reports_path', 'reports');
        $filePath = $storagePath . '/' . $fileName;

        switch ($format) {
            case ReportExecution::FORMAT_CSV:
                $content = $this->generateCSV($data);
                break;

            case ReportExecution::FORMAT_JSON:
                $content = json_encode($data, JSON_PRETTY_PRINT);
                break;

            case ReportExecution::FORMAT_PDF:
            case ReportExecution::FORMAT_EXCEL:
                // Placeholder - integrate with PDF/Excel libraries
                $content = json_encode($data);
                break;

            default:
                $content = json_encode($data);
        }

        Storage::put($filePath, $content);

        return $filePath;
    }

    /**
     * Generate CSV content.
     */
    protected function generateCSV(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Headers
        $headers = array_keys($data[0]);
        fputcsv($output, $headers);

        // Data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate file name.
     */
    protected function generateFileName(CustomReport $report, string $format): string
    {
        $slug = \Illuminate\Support\Str::slug($report->name);
        $timestamp = now()->format('Ymd_His');
        $extension = $this->getFileExtension($format);

        return "{$slug}_{$timestamp}.{$extension}";
    }

    /**
     * Get file extension for format.
     */
    protected function getFileExtension(string $format): string
    {
        return match ($format) {
            ReportExecution::FORMAT_CSV => 'csv',
            ReportExecution::FORMAT_PDF => 'pdf',
            ReportExecution::FORMAT_EXCEL => 'xlsx',
            default => 'json',
        };
    }

    /**
     * Generate audit report.
     */
    public function generateAuditReport(Carbon $start, Carbon $end, ?int $workflowId = null): array
    {
        $query = ApprovalAction::with(['request', 'user'])
            ->whereBetween('created_at', [$start, $end]);

        if ($workflowId) {
            $query->whereHas('request', function ($q) use ($workflowId) {
                $q->where('workflow_id', $workflowId);
            });
        }

        $actions = $query->orderBy('created_at', 'desc')->get();

        return $actions->map(function ($action) {
            return [
                'action_id' => $action->id,
                'request_id' => $action->approval_request_id,
                'user' => $action->user->name ?? 'Unknown',
                'action' => $action->action,
                'comments' => $action->remarks ?? '',
                'created_at' => $action->created_at->toIso8601String(),
                'ip_address' => $action->ip_address,
            ];
        })->toArray();
    }

    /**
     * Get report statistics.
     */
    public function getReportStatistics(int $reportId): array
    {
        $report = CustomReport::findOrFail($reportId);
        $executions = $report->executions;

        return [
            'report_id' => $report->id,
            'report_name' => $report->name,
            'total_executions' => $executions->count(),
            'successful_executions' => $executions->where('status', ReportExecution::STATUS_COMPLETED)->count(),
            'failed_executions' => $executions->where('status', ReportExecution::STATUS_FAILED)->count(),
            'avg_execution_time_ms' => $executions->where('status', ReportExecution::STATUS_COMPLETED)->avg('execution_time_ms'),
            'last_executed_at' => $executions->max('created_at'),
        ];
    }
}
