@extends('layouts.app')

@section('title', 'Approval Request Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-bold">Request #APR-001</h1>
                    <p class="text-gray-600 mt-1">Purchase Order Request</p>
                </div>
                <div class="text-right">
                    <span class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full font-medium">Pending Approval</span>
                </div>
            </div>
        </div>

        <!-- Request Details -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Request Details</h2>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="text-sm font-medium text-gray-600">Submitted By</label>
                    <p class="text-gray-900 mt-1">John Doe (john.doe@company.com)</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Department</label>
                    <p class="text-gray-900 mt-1">Operations</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Submitted Date</label>
                    <p class="text-gray-900 mt-1">Feb 2, 2026 at 10:30 AM</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Amount</label>
                    <p class="text-gray-900 mt-1">$5,000.00</p>
                </div>
            </div>
        </div>

        <!-- Approval Timeline -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold mb-6">Approval Timeline</h2>
            <div class="space-y-4">
                <!-- Step 1: Manager Approval -->
                <div class="flex">
                    <div class="flex flex-col items-center mr-4">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">✓</div>
                        <div class="w-1 h-16 bg-green-500 mt-2"></div>
                    </div>
                    <div class="pb-8">
                        <h3 class="font-bold text-green-700">Manager Approval - Completed</h3>
                        <p class="text-sm text-gray-600 mt-1">Approved by: Sarah Johnson</p>
                        <p class="text-sm text-gray-600">Date: Feb 2, 2026 at 11:00 AM</p>
                        <p class="text-sm text-gray-600 mt-2"><strong>Remarks:</strong> Looks good, proceeding to next level.</p>
                    </div>
                </div>

                <!-- Step 2: Finance Approval -->
                <div class="flex">
                    <div class="flex flex-col items-center mr-4">
                        <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold">⏳</div>
                        <div class="w-1 h-16 bg-gray-300 mt-2"></div>
                    </div>
                    <div class="pb-8">
                        <h3 class="font-bold text-gray-700">Finance Approval - Pending</h3>
                        <p class="text-sm text-gray-600 mt-1">Waiting for: Mike Chen</p>
                        <p class="text-sm text-gray-600">SLA: Feb 4, 2026</p>
                    </div>
                </div>

                <!-- Step 3: Director Approval -->
                <div class="flex">
                    <div class="flex flex-col items-center mr-4">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-700 font-bold">○</div>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-700">Director Approval - Pending</h3>
                        <p class="text-sm text-gray-600 mt-1">Not yet submitted to this level</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Your Decision</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Add Comments (Optional)</label>
                    <textarea rows="4" placeholder="Add your comments here..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="flex gap-4">
                    <button class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition">
                        ✓ Approve
                    </button>
                    <button class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-4 rounded-lg transition">
                        ✗ Reject
                    </button>
                    <button class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-3 px-4 rounded-lg transition">
                        ← Send Back
                    </button>
                    <button class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-4 rounded-lg transition">
                        ⏸ Hold
                    </button>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-6">
            <a href="#" class="text-blue-600 hover:text-blue-800 font-medium">← Back to Inbox</a>
        </div>
    </div>
</div>
@endsection
