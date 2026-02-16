@extends('approval-process::layout')

@section('title', 'Request Details')
@section('page-title', 'Request #' . $request->id)
@section('page-description', $request->workflow->name)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Request Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800">Request Information</h3>
                    <p class="text-sm text-gray-600 mt-1">Submitted {{ $request->created_at->diffForHumans() }}</p>
                </div>
                <span id="request-status" class="px-4 py-2 rounded-full text-sm font-semibold
                    {{ $request->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $request->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                    {{ in_array($request->status, ['submitted', 'in_progress']) ? 'bg-yellow-100 text-yellow-800' : '' }}">
                    {{ ucfirst($request->status) }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="text-sm font-medium text-gray-500">Workflow</label>
                    <p class="text-gray-800 font-medium">{{ $request->workflow->name }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Requested By</label>
                    <p class="text-gray-800 font-medium">{{ $request->requestedBy->name ?? 'Unknown' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Current Step</label>
                    <p class="text-gray-800 font-medium">{{ $request->currentStep->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Priority</label>
                    <p class="text-gray-800 font-medium">{{ ucfirst($request->priority ?? 'normal') }}</p>
                </div>
            </div>

            @if($request->data)
                <div class="border-t pt-4">
                    <h4 class="font-semibold text-gray-800 mb-3">Request Data</h4>
                    <div class="bg-gray-50 rounded p-4">
                        <pre class="text-sm text-gray-700">{{ json_encode($request->data, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif
        </div>

        <!-- Workflow Progress -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-6">Workflow Progress</h3>
            
            <div class="relative">
                @foreach($request->workflow->steps as $index => $step)
                    <div class="flex items-start mb-8 last:mb-0">
                        <!-- Step Indicator -->
                        <div class="flex-shrink-0 relative">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center
                                {{ $step->id === $request->current_step_id ? 'bg-yellow-500 text-white' : '' }}
                                {{ $step->sequence < ($request->currentStep->sequence ?? 0) ? 'bg-green-500 text-white' : '' }}
                                {{ $step->sequence > ($request->currentStep->sequence ?? 999) ? 'bg-gray-300 text-gray-600' : '' }}">
                                @if($step->sequence < ($request->currentStep->sequence ?? 0))
                                    <i class="fas fa-check"></i>
                                @else
                                    {{ $step->sequence }}
                                @endif
                            </div>
                            @if(!$loop->last)
                                <div class="absolute top-10 left-5 w-0.5 h-16 bg-gray-300"></div>
                            @endif
                        </div>

                        <!-- Step Details -->
                        <div class="ml-4 flex-1">
                            <h4 class="font-semibold text-gray-800">{{ $step->name }}</h4>
                            <p class="text-sm text-gray-600 mt-1">{{ $step->description }}</p>
                            
                            @if($step->approvers->count() > 0)
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($step->approvers as $approver)
                                        <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs">
                                            {{ $approver->approver_type }}: {{ $approver->approver_id }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Actions History -->
        <div class="bg-white rounded-lg shadow p-6" id="approval-timeline">
            <h3 class="text-lg font-semibold mb-6">Actions History</h3>
            
            <div class="space-y-4">
                @forelse($request->actions as $action)
                    <div class="border-l-4 pl-4
                        {{ $action->action_type === 'approved' ? 'border-green-500' : '' }}
                        {{ $action->action_type === 'rejected' ? 'border-red-500' : '' }}
                        {{ $action->action_type === 'sent_back' ? 'border-yellow-500' : '' }}">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-gray-800">
                                    {{ $action->user->name ?? 'Unknown' }}
                                    <span class="text-gray-600 font-normal">{{ $action->action_type }}</span>
                                </p>
                                <p class="text-sm text-gray-600 mt-1">{{ $action->created_at->format('M d, Y H:i') }}</p>
                                @if($action->comments)
                                    <p class="text-sm text-gray-700 mt-2 bg-gray-50 p-2 rounded">{{ $action->comments }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No actions yet</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Actions</h3>
            
            <div class="space-y-2">
                <button class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fas fa-check mr-2"></i>
                    Approve
                </button>
                <button class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    <i class="fas fa-times mr-2"></i>
                    Reject
                </button>
                <button class="w-full px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                    <i class="fas fa-undo mr-2"></i>
                    Send Back
                </button>
                <button class="w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    <i class="fas fa-pause mr-2"></i>
                    Hold
                </button>
            </div>
        </div>

        <!-- Attachments -->
        @if($request->attachments->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Attachments</h3>
                
                <div class="space-y-2">
                    @foreach($request->attachments as $attachment)
                        <a href="#" class="flex items-center justify-between p-3 bg-gray-50 rounded hover:bg-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-file text-gray-600"></i>
                                <span class="text-sm text-gray-800">{{ $attachment->file_name }}</span>
                            </div>
                            <i class="fas fa-download text-indigo-600"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Signatures -->
        @if($request->signatures->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Digital Signatures</h3>
                
                <div class="space-y-3">
                    @foreach($request->signatures as $signature)
                        <div class="border border-gray-200 rounded p-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium">{{ $signature->user->name ?? 'Unknown' }}</span>
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Verified
                                </span>
                            </div>
                            <p class="text-xs text-gray-500">{{ $signature->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Active Users -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Active Users</h3>
            <div id="active-users" class="space-y-2">
                <span class="text-gray-500 text-sm">Loading...</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('approval-process::partials.realtime-script')
<script>
    // Join this request's channel for real-time updates
    joinRequestChannel({{ $request->id }});
</script>
@endpush
