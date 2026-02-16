@extends('approval-process::layout')

@section('title', 'Workflows')
@section('page-title', 'Workflows')
@section('page-description', 'Manage approval workflows')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <a href="{{ route('approval-process.workflows.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            <i class="fas fa-plus mr-2"></i>
            Create Workflow
        </a>
    </div>
    
    <div class="flex space-x-2">
        <input type="text" placeholder="Search workflows..." class="px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500">
        <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
            <i class="fas fa-filter"></i>
        </button>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Steps</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($workflows as $workflow)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-project-diagram text-indigo-600"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $workflow->name }}</div>
                                <div class="text-sm text-gray-500">{{ Str::limit($workflow->description, 50) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">{{ class_basename($workflow->model_type) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $workflow->steps->count() }} steps
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $workflow->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $workflow->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $workflow->created_at->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                            <a href="{{ route('approval-process.workflows.view', $workflow->id) }}" class="text-indigo-600 hover:text-indigo-900" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('approval-process.workflows.designer', $workflow->id) }}" class="text-purple-600 hover:text-purple-900" title="Designer">
                                <i class="fas fa-pencil-ruler"></i>
                            </a>
                            <a href="{{ route('approval-process.workflows.edit', $workflow->id) }}" class="text-blue-600 hover:text-blue-900" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="text-red-600 hover:text-red-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-project-diagram text-4xl mb-3"></i>
                        <p>No workflows found</p>
                        <a href="{{ route('approval-process.workflows.create') }}" class="text-indigo-600 hover:text-indigo-800 mt-2 inline-block">
                            Create your first workflow
                        </a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($workflows->hasPages())
    <div class="mt-6">
        {{ $workflows->links() }}
    </div>
@endif
@endsection
