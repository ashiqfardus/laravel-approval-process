@extends('layouts.app')

@section('title', 'Approvals Inbox')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl">
        <h1 class="text-3xl font-bold mb-6">Approval Inbox</h1>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in-review">In Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <input type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" placeholder="Search requests..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition">
                        Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-600 text-sm font-medium">Pending</div>
                <div class="text-3xl font-bold text-blue-600">12</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-600 text-sm font-medium">In Review</div>
                <div class="text-3xl font-bold text-yellow-600">5</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-600 text-sm font-medium">Approved Today</div>
                <div class="text-3xl font-bold text-green-600">8</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-600 text-sm font-medium">Rejected</div>
                <div class="text-3xl font-bold text-red-600">2</div>
            </div>
        </div>

        <!-- Requests List -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Request ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Submitted By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Current Step</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm font-medium text-blue-600">#APR-001</td>
                        <td class="px-6 py-4 text-sm text-gray-700">Purchase Order</td>
                        <td class="px-6 py-4 text-sm text-gray-700">John Doe</td>
                        <td class="px-6 py-4 text-sm text-gray-700">Manager Approval</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Pending</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">Feb 2, 2026</td>
                        <td class="px-6 py-4 text-sm">
                            <a href="#" class="text-blue-600 hover:text-blue-800 font-medium">Review</a>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm font-medium text-blue-600">#APR-002</td>
                        <td class="px-6 py-4 text-sm text-gray-700">Expense Claim</td>
                        <td class="px-6 py-4 text-sm text-gray-700">Jane Smith</td>
                        <td class="px-6 py-4 text-sm text-gray-700">Finance Approval</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">In Review</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">Feb 1, 2026</td>
                        <td class="px-6 py-4 text-sm">
                            <a href="#" class="text-blue-600 hover:text-blue-800 font-medium">Review</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex justify-center">
            <nav class="inline-flex rounded-lg shadow">
                <button class="px-4 py-2 border border-r-0 border-gray-300 rounded-l-lg text-gray-700 hover:bg-gray-50">Previous</button>
                <button class="px-4 py-2 border border-r-0 border-gray-300 bg-blue-600 text-white">1</button>
                <button class="px-4 py-2 border border-r-0 border-gray-300 text-gray-700 hover:bg-gray-50">2</button>
                <button class="px-4 py-2 border border-gray-300 rounded-r-lg text-gray-700 hover:bg-gray-50">Next</button>
            </nav>
        </div>
    </div>
</div>
@endsection
