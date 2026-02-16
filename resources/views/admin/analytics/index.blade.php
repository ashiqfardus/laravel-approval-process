@extends('approval-process::layout')

@section('title', 'Analytics')
@section('page-title', 'Analytics Dashboard')
@section('page-description', 'Comprehensive workflow analytics and insights')

@section('content')
<!-- Time Range Selector -->
<div class="mb-6 bg-white rounded-lg shadow p-4">
    <div class="flex items-center justify-between">
        <div class="flex space-x-2">
            <button class="px-4 py-2 bg-indigo-600 text-white rounded">Last 7 Days</button>
            <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Last 30 Days</button>
            <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Last 90 Days</button>
            <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Custom</button>
        </div>
        
        <div class="flex space-x-2">
            <button class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                <i class="fas fa-download mr-2"></i>
                Export Report
            </button>
        </div>
    </div>
</div>

<!-- Key Metrics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-gray-500 text-sm font-medium">Avg Processing Time</h3>
            <i class="fas fa-clock text-blue-600 text-xl"></i>
        </div>
        <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['avg_processing_time_hours'] ?? 0, 1) }}h</p>
        <p class="text-sm text-green-600 mt-2">
            <i class="fas fa-arrow-down mr-1"></i>
            12% faster than last period
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-gray-500 text-sm font-medium">Approval Rate</h3>
            <i class="fas fa-percentage text-green-600 text-xl"></i>
        </div>
        <p class="text-3xl font-bold text-gray-800">{{ number_format($stats['approval_rate'] ?? 0, 1) }}%</p>
        <p class="text-sm text-green-600 mt-2">
            <i class="fas fa-arrow-up mr-1"></i>
            3% increase
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-gray-500 text-sm font-medium">Active Workflows</h3>
            <i class="fas fa-project-diagram text-purple-600 text-xl"></i>
        </div>
        <p class="text-3xl font-bold text-gray-800">{{ $workflows->count() }}</p>
        <p class="text-sm text-gray-600 mt-2">
            {{ $stats['total_requests'] ?? 0 }} total requests
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-gray-500 text-sm font-medium">Bottlenecks</h3>
            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
        </div>
        <p class="text-3xl font-bold text-gray-800">{{ $stats['bottleneck_count'] ?? 0 }}</p>
        <p class="text-sm text-red-600 mt-2">
            Requires attention
        </p>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Request Volume Trend -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Request Volume Trend</h3>
        <canvas id="volumeTrendChart" height="250"></canvas>
    </div>

    <!-- Approval vs Rejection -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Approval vs Rejection</h3>
        <canvas id="approvalPieChart" height="250"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Processing Time by Workflow -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Processing Time by Workflow</h3>
        <canvas id="processingTimeChart" height="250"></canvas>
    </div>

    <!-- Top Performers -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Top Performers (Response Time)</h3>
        <div class="space-y-4">
            @for($i = 1; $i <= 5; $i++)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-semibold">
                            {{ $i }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">User {{ $i }}</p>
                            <p class="text-xs text-gray-500">{{ rand(50, 200) }} approvals</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-green-600">{{ number_format(rand(1, 5), 1) }}h</p>
                        <p class="text-xs text-gray-500">avg response</p>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>

<!-- Workflow Comparison -->
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Workflow Performance Comparison</h3>
        <select class="px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500">
            <option>All Workflows</option>
            @foreach($workflows as $workflow)
                <option value="{{ $workflow->id }}">{{ $workflow->name }}</option>
            @endforeach
        </select>
    </div>
    <canvas id="workflowComparisonChart" height="100"></canvas>
</div>
@endsection

@push('scripts')
<script>
    // Volume Trend Chart
    new Chart(document.getElementById('volumeTrendChart'), {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Requests',
                data: [45, 59, 80, 81],
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Approval Pie Chart
    new Chart(document.getElementById('approvalPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Rejected', 'Pending'],
            datasets: [{
                data: [{{ $stats['approved_requests'] ?? 0 }}, {{ $stats['rejected_requests'] ?? 0 }}, {{ $stats['pending_requests'] ?? 0 }}],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Processing Time Chart
    new Chart(document.getElementById('processingTimeChart'), {
        type: 'bar',
        data: {
            labels: @json($workflows->pluck('name')->take(5)),
            datasets: [{
                label: 'Hours',
                data: [12, 19, 8, 15, 22],
                backgroundColor: 'rgba(99, 102, 241, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Workflow Comparison Chart
    new Chart(document.getElementById('workflowComparisonChart'), {
        type: 'bar',
        data: {
            labels: @json($workflows->pluck('name')),
            datasets: [{
                label: 'Total Requests',
                data: @json($workflows->pluck('requests_count')),
                backgroundColor: 'rgba(99, 102, 241, 0.8)'
            }, {
                label: 'Avg Processing Time (h)',
                data: [12, 15, 8, 20, 10],
                backgroundColor: 'rgba(34, 197, 94, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
@endpush
