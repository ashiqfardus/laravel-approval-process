<?php

namespace AshiqFardus\ApprovalProcess\Http\Controllers;

use AshiqFardus\ApprovalProcess\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected AnalyticsService $service;

    public function __construct(AnalyticsService $service)
    {
        $this->service = $service;
    }

    /**
     * Get workflow metrics.
     */
    public function workflowMetrics($workflow_id): JsonResponse
    {
        $start = request()->input('start_date', now()->subDays(30)->toDateString());
        $end = request()->input('end_date', now()->toDateString());

        $date = Carbon::parse(request()->input('date', now()->toDateString()));
        
        $metrics = $this->service->calculateWorkflowMetrics($workflow_id, $date);

        return response()->json($metrics);
    }

    /**
     * Get user metrics.
     */
    public function userMetrics($user_id): JsonResponse
    {
        $date = Carbon::parse(request()->input('date', now()->toDateString()));
        
        $metrics = $this->service->calculateUserMetrics($user_id, $date);

        return response()->json($metrics);
    }

    /**
     * Detect bottlenecks.
     */
    public function bottlenecks(): JsonResponse
    {
        $date = Carbon::parse(request()->input('date', now()->toDateString()));
        
        $bottlenecks = $this->service->detectBottlenecks($date);

        return response()->json([
            'date' => $date->toDateString(),
            'bottlenecks' => $bottlenecks,
            'count' => count($bottlenecks),
        ]);
    }

    /**
     * Get trend data.
     */
    public function trends(): JsonResponse
    {
        $metricType = request()->input('metric_type', 'request_count');
        $period = request()->input('period', 'daily');
        $start = Carbon::parse(request()->input('start_date', now()->subDays(30)));
        $end = Carbon::parse(request()->input('end_date', now()));

        $data = $this->service->getTrendData($metricType, $period, $start, $end);

        return response()->json([
            'metric_type' => $metricType,
            'period' => $period,
            'data' => $data,
        ]);
    }

    /**
     * Get top performers.
     */
    public function topPerformers(): JsonResponse
    {
        $limit = request()->input('limit', 10);
        $start = request()->has('start_date') 
            ? Carbon::parse(request()->input('start_date'))
            : null;
        $end = request()->has('end_date')
            ? Carbon::parse(request()->input('end_date'))
            : null;

        $performers = $this->service->getTopPerformers($limit, $start, $end);

        return response()->json($performers);
    }

    /**
     * Compare workflows.
     */
    public function compareWorkflows(): JsonResponse
    {
        $workflowIds = request()->input('workflow_ids', []);
        $start = Carbon::parse(request()->input('start_date', now()->subDays(30)));
        $end = Carbon::parse(request()->input('end_date', now()));

        if (empty($workflowIds)) {
            return response()->json(['message' => 'No workflows selected'], 422);
        }

        $comparison = $this->service->compareWorkflows($workflowIds, $start, $end);

        return response()->json($comparison);
    }
}
