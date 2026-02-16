<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Models\CustomReport;
use AshiqFardus\ApprovalProcess\Models\ReportExecution;
use AshiqFardus\ApprovalProcess\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected ReportService $service;

    public function __construct(ReportService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all reports.
     */
    public function index(): JsonResponse
    {
        $query = CustomReport::with('createdBy');

        if (request()->input('public_only', false)) {
            $query->public();
        }

        if (request()->has('report_type')) {
            $query->byType(request()->input('report_type'));
        }

        $reports = $query->orderBy('name')->get();

        return response()->json($reports);
    }

    /**
     * Create a new report.
     */
    public function store(): JsonResponse
    {
        $httpRequest = request();

        $report = CustomReport::create([
            'name' => $httpRequest->input('name'),
            'code' => $httpRequest->input('code'),
            'description' => $httpRequest->input('description'),
            'report_type' => $httpRequest->input('report_type'),
            'filters' => $httpRequest->input('filters', []),
            'columns' => $httpRequest->input('columns', []),
            'grouping' => $httpRequest->input('grouping', []),
            'sorting' => $httpRequest->input('sorting', []),
            'chart_config' => $httpRequest->input('chart_config'),
            'is_scheduled' => $httpRequest->input('is_scheduled', false),
            'schedule_frequency' => $httpRequest->input('schedule_frequency'),
            'schedule_recipients' => $httpRequest->input('schedule_recipients', []),
            'is_public' => $httpRequest->input('is_public', false),
            'created_by_user_id' => auth()->id() ?? 1,
        ]);

        return response()->json([
            'message' => 'Report created successfully',
            'report' => $report,
        ], 201);
    }

    /**
     * Get a specific report.
     */
    public function show($report_id): JsonResponse
    {
        $report = CustomReport::with(['createdBy', 'executions'])->findOrFail($report_id);

        return response()->json($report);
    }

    /**
     * Update a report.
     */
    public function update($report_id): JsonResponse
    {
        $report = CustomReport::findOrFail($report_id);
        $httpRequest = request();

        $report->update($httpRequest->only([
            'name',
            'description',
            'report_type',
            'filters',
            'columns',
            'grouping',
            'sorting',
            'chart_config',
            'is_scheduled',
            'schedule_frequency',
            'schedule_recipients',
            'is_public',
        ]));

        return response()->json([
            'message' => 'Report updated successfully',
            'report' => $report->fresh(),
        ]);
    }

    /**
     * Delete a report.
     */
    public function destroy($report_id): JsonResponse
    {
        $report = CustomReport::findOrFail($report_id);
        $report->delete();

        return response()->json([
            'message' => 'Report deleted successfully'
        ], 200);
    }

    /**
     * Execute a report.
     */
    public function execute($report_id): JsonResponse
    {
        $report = CustomReport::findOrFail($report_id);
        $httpRequest = request();

        $parameters = $httpRequest->input('parameters', []);
        $format = $httpRequest->input('format', ReportExecution::FORMAT_JSON);

        try {
            $execution = $this->service->executeReport(
                $report,
                auth()->id() ?? 1,
                $parameters,
                $format
            );

            return response()->json([
                'message' => 'Report executed successfully',
                'execution' => $execution,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Report execution failed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get report executions.
     */
    public function executions($report_id): JsonResponse
    {
        $report = CustomReport::findOrFail($report_id);
        
        $executions = $report->executions()
            ->with('executedBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($executions);
    }

    /**
     * Get report statistics.
     */
    public function statistics($report_id): JsonResponse
    {
        $stats = $this->service->getReportStatistics($report_id);

        return response()->json($stats);
    }

    /**
     * Generate audit report.
     */
    public function auditReport(): JsonResponse
    {
        $start = Carbon::parse(request()->input('start_date', now()->subDays(30)));
        $end = Carbon::parse(request()->input('end_date', now()));
        $workflowId = request()->input('workflow_id');

        $data = $this->service->generateAuditReport($start, $end, $workflowId);

        return response()->json([
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'workflow_id' => $workflowId,
            'total_actions' => count($data),
            'data' => $data,
        ]);
    }

    /**
     * Get available report types.
     */
    public function reportTypes(): JsonResponse
    {
        return response()->json([
            'report_types' => CustomReport::getReportTypes()
        ]);
    }

    /**
     * Get available frequencies.
     */
    public function frequencies(): JsonResponse
    {
        return response()->json([
            'frequencies' => CustomReport::getFrequencies()
        ]);
    }
}
