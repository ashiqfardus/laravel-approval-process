@extends('approval-process::layout')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-description', 'Overview of your approval workflows')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Requests -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Total Requests</p>
                <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($stats['total_requests']) }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-4">
                <i class="fas fa-file-alt text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Pending Approvals -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Pending</p>
                <p class="text-3xl font-bold text-yellow-600 mt-2">{{ number_format($stats['pending_requests']) }}</p>
            </div>
            <div class="bg-yellow-100 rounded-full p-4">
                <i class="fas fa-clock text-yellow-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Approved -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Approved</p>
                <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($stats['approved_requests']) }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-4">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
        </div>
        <div class="mt-2">
            <span class="text-sm text-gray-600">{{ number_format($stats['approval_rate'], 1) }}% approval rate</span>
        </div>
    </div>

    <!-- Overdue -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium">Overdue</p>
                <p class="text-3xl font-bold text-red-600 mt-2">{{ number_format($stats['overdue_requests']) }}</p>
            </div>
            <div class="bg-red-100 rounded-full p-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- My Pending Approvals -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">My Pending Approvals</h3>
        </div>
        <div class="p-6">
            @if($myPendingApprovals->count() > 0)
                <div class="space-y-4">
                    @foreach($myPendingApprovals as $request)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-semibold text-gray-800">{{ $request->workflow->name }}</span>
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                            {{ ucfirst($request->status) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Requested by {{ $request->requestedBy->name ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="far fa-clock mr-1"></i>
                                        {{ $request->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="{{ route('approval-process.requests.show', $request->id) }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm">
                                        Review
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 text-center">
                    <a href="{{ route('approval-process.my-approvals') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                        View all pending approvals →
                    </a>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-check-circle text-4xl mb-3"></i>
                    <p>No pending approvals</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Active Workflows -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Active Workflows</h3>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                @foreach($activeWorkflows as $workflow)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-medium text-gray-800">{{ $workflow->name }}</p>
                            <p class="text-xs text-gray-500">{{ $workflow->requests_count }} requests</p>
                        </div>
                        <a href="{{ route('approval-process.workflows.view', $workflow->id) }}" class="text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Charts and Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Approval Trends Chart -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Approval Trends (Last 30 Days)</h3>
        </div>
        <div class="p-6">
            <canvas id="approvalTrendsChart" height="200"></canvas>
        </div>
    </div>

    <!-- Recent Requests -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Recent Requests</h3>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                @foreach($recentRequests as $request)
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 last:border-0">
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 text-sm">{{ $request->workflow->name }}</p>
                            <p class="text-xs text-gray-500">{{ $request->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="px-3 py-1 text-xs rounded-full 
                            {{ $request->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $request->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                            {{ in_array($request->status, ['submitted', 'in_progress']) ? 'bg-yellow-100 text-yellow-800' : '' }}">
                            {{ ucfirst($request->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Bottlenecks Alert -->
@if($bottlenecks->count() > 0)
    <div class="mt-6 bg-red-50 border-l-4 border-red-500 p-6 rounded-lg">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-red-500 text-2xl mr-4 mt-1"></i>
            <div class="flex-1">
                <h4 class="text-lg font-semibold text-red-800 mb-2">Bottlenecks Detected</h4>
                <div class="space-y-2">
                    @foreach($bottlenecks as $bottleneck)
                        <div class="bg-white p-3 rounded">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-gray-800">{{ $bottleneck->workflow->name }}</span>
                                    <span class="text-gray-600"> - {{ $bottleneck->step->name }}</span>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full 
                                    {{ $bottleneck->severity === 'critical' ? 'bg-red-200 text-red-800' : '' }}
                                    {{ $bottleneck->severity === 'high' ? 'bg-orange-200 text-orange-800' : '' }}
                                    {{ $bottleneck->severity === 'medium' ? 'bg-yellow-200 text-yellow-800' : '' }}">
                                    {{ ucfirst($bottleneck->severity) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mt-2">
                                {{ $bottleneck->pending_count }} pending requests, avg wait: {{ number_format($bottleneck->avg_wait_time_hours, 1) }}h
                            </p>
                            <p class="text-sm text-indigo-600 mt-1">
                                <i class="fas fa-lightbulb mr-1"></i>
                                {{ $bottleneck->recommendation }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
    // Sample chart data - in production, load from API
    const ctx = document.getElementById('approvalTrendsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
            datasets: [{
                label: 'Approved',
                data: [12, 19, 15, 25, 22, 30, 28],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4
            }, {
                label: 'Rejected',
                data: [3, 5, 2, 4, 3, 6, 4],
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
@endpush
