<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Workflows</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage approval workflows and their steps</p>
            </div>
            <button @click="openCreateModal"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Workflow
            </button>
        </div>

        <!-- Search + Filter -->
        <div class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input v-model="searchQuery" placeholder="Search workflows..."
                       class="w-full pl-9 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
            </div>
            <select v-model="statusFilter"
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                <option value="all">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-red-700 dark:text-red-400">
            {{ error }}
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredWorkflows.length === 0"
             class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                {{ searchQuery || statusFilter !== 'all' ? 'No workflows match your filters' : 'No workflows yet' }}
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ searchQuery || statusFilter !== 'all' ? 'Try adjusting your search or filter.' : 'Create your first workflow to start managing approvals.' }}
            </p>
            <button v-if="!searchQuery && statusFilter === 'all'" @click="openCreateModal"
                    class="mt-6 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md transition-colors">
                Create Workflow
            </button>
        </div>

        <!-- Workflow cards -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="wf in filteredWorkflows" :key="wf.id"
                 class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 hover:shadow-md transition-shadow flex flex-col">

                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1 min-w-0 pr-2">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ wf.name }}</h4>
                        <p v-if="wf.description" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ wf.description }}</p>
                    </div>
                    <span :class="wf.is_active
                              ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                          class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                        {{ wf.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="space-y-1.5 text-xs text-gray-600 dark:text-gray-400 flex-1">
                    <div class="flex items-center justify-between">
                        <span>Entity</span>
                        <span class="font-medium text-gray-900 dark:text-white font-mono text-xs truncate max-w-[140px]">
                            {{ wf.entity?.label || wf.model_type?.split('\\').pop() || '—' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Steps</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ wf.steps_count ?? wf.steps?.length ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Active Requests</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ wf.active_requests_count ?? 0 }}</span>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex gap-1">
                        <button @click="openEditModal(wf)" title="Edit"
                                class="p-1.5 rounded-md text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:text-primary-400 dark:hover:bg-primary-900/20 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button @click="cloneWorkflow(wf)" title="Clone"
                                class="p-1.5 rounded-md text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        <button @click="confirmDelete(wf)" title="Delete"
                                class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                    <button @click="toggleStatus(wf)"
                            :class="wf.is_active ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'"
                            class="text-xs font-medium transition-colors hover:underline">
                        {{ wf.is_active ? 'Disable' : 'Enable' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================
             CREATE / EDIT MODAL
             ================================================================ -->
        <div v-if="showFormModal"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
             @click.self="closeFormModal">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-3xl mx-4 max-h-[92vh] flex flex-col">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ editingWorkflow ? 'Edit Workflow' : 'Create Workflow' }}
                    </h3>
                    <button @click="closeFormModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">

                    <!-- Basic info -->
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Workflow Name <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.name" placeholder="e.g. Purchase Order Approval"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea v-model="form.description" rows="2" placeholder="Brief description..."
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Entity (Model Type) <span class="text-red-500">*</span>
                            </label>
                            <div v-if="loadingEntities" class="flex items-center gap-2 text-sm text-gray-500 py-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600"></div>
                                Loading entities...
                            </div>
                            <template v-else>
                                <select v-if="entities.length" v-model="form.model_type"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                                    <option value="">— Select an entity —</option>
                                    <optgroup v-if="modelEntities.length" label="Models">
                                        <option v-for="e in modelEntities" :key="e.id" :value="e.name">{{ e.label }} ({{ e.name }})</option>
                                    </optgroup>
                                    <optgroup v-if="tableEntities.length" label="Tables">
                                        <option v-for="e in tableEntities" :key="e.id" :value="e.name">{{ e.label }} ({{ e.name }})</option>
                                    </optgroup>
                                </select>
                                <input v-else v-model="form.model_type" placeholder="App\Models\PurchaseOrder"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 font-mono">
                            </template>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active"
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                        </label>
                    </div>

                    <!-- ── Steps builder ── -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Approval Steps</h4>
                                <p class="text-xs text-gray-400 mt-0.5">Each step has approvers with weighted voting power</p>
                            </div>
                            <button type="button" @click="addStep"
                                    class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 border border-primary-300 dark:border-primary-600 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Step
                            </button>
                        </div>

                        <div v-if="form.steps.length === 0"
                             class="text-center py-6 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-500 dark:text-gray-400">
                            No steps yet. Add at least one approval step.
                        </div>

                        <div class="space-y-4">
                            <div v-for="(step, idx) in form.steps" :key="idx"
                                 class="border border-gray-200 dark:border-gray-600 rounded-xl overflow-hidden">

                                <!-- Step header -->
                                <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-700/60">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 text-xs font-bold flex items-center justify-center">
                                            {{ idx + 1 }}
                                        </span>
                                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            {{ step.name || 'Untitled Step' }}
                                        </span>
                                        <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">
                                            {{ approvalTypeLabel(step.approval_type) }}
                                        </span>
                                    </div>
                                    <div class="flex gap-1">
                                        <button type="button" @click="moveStep(idx, -1)" :disabled="idx === 0"
                                                class="p-1 rounded text-gray-400 hover:text-gray-600 disabled:opacity-30">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            </svg>
                                        </button>
                                        <button type="button" @click="moveStep(idx, 1)" :disabled="idx === form.steps.length - 1"
                                                class="p-1 rounded text-gray-400 hover:text-gray-600 disabled:opacity-30">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        <button type="button" @click="removeStep(idx)"
                                                class="p-1 rounded text-gray-400 hover:text-red-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step body -->
                                <div class="px-4 py-4 space-y-4">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Step Name <span class="text-red-500">*</span></label>
                                            <input v-model="step.name" placeholder="e.g. Manager Approval"
                                                   class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                                        </div>

                                        <!-- Approval type with explanation -->
                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Approval Type</label>
                                            <div class="grid grid-cols-3 gap-2">
                                                <button v-for="t in approvalTypes" :key="t.value" type="button"
                                                        @click="step.approval_type = t.value"
                                                        :class="step.approval_type === t.value
                                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                                                            : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
                                                        class="flex flex-col items-start p-2 border rounded-lg text-left transition-colors">
                                                    <span class="text-xs font-semibold">{{ t.label }}</span>
                                                    <span class="text-xs opacity-70 mt-0.5 leading-tight">{{ t.hint }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">SLA (hours)</label>
                                            <input v-model.number="step.sla_hours" type="number" min="0" placeholder="e.g. 24"
                                                   class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Level Alias</label>
                                            <input v-model="step.level_alias" placeholder="e.g. line_manager"
                                                   class="w-full px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 font-mono">
                                        </div>

                                        <!-- Min approval % -->
                                        <div class="col-span-2">
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                Minimum Approval %
                                                <span class="text-gray-400 font-normal ml-1">— step completes when approved weightage ≥ this</span>
                                            </label>
                                            <div class="flex items-center gap-3">
                                                <input v-model.number="step.minimum_approval_percentage" type="range" min="1" max="100"
                                                       class="flex-1 h-2 accent-primary-600">
                                                <div class="flex items-center gap-1 w-20 flex-shrink-0">
                                                    <input v-model.number="step.minimum_approval_percentage" type="number" min="1" max="100"
                                                           class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs text-center focus:ring-2 focus:ring-primary-500">
                                                    <span class="text-xs text-gray-400">%</span>
                                                </div>
                                            </div>
                                            <div class="flex justify-between text-xs text-gray-400 mt-1">
                                                <span>Any one (1%)</span>
                                                <span class="font-medium" :class="step.minimum_approval_percentage === 100 ? 'text-blue-600' : step.minimum_approval_percentage >= 51 ? 'text-green-600' : 'text-amber-600'">
                                                    {{ step.minimum_approval_percentage === 100 ? 'Unanimous' : step.minimum_approval_percentage >= 51 ? 'Majority' : 'Partial' }}
                                                </span>
                                                <span>Unanimous (100%)</span>
                                            </div>
                                        </div>

                                        <div class="flex gap-4 items-center">
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="checkbox" v-model="step.allow_send_back"
                                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                <span class="text-xs text-gray-600 dark:text-gray-400">Allow send back</span>
                                            </label>
                                            <label class="flex items-center gap-1.5 cursor-pointer">
                                                <input type="checkbox" v-model="step.allows_delegation"
                                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                <span class="text-xs text-gray-600 dark:text-gray-400">Allow delegation</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- ── Approvers with weightage ── -->
                                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                                                Approvers &amp; Weightage
                                            </span>
                                            <div class="flex gap-2 items-center">
                                                <!-- Auto-distribute -->
                                                <select v-if="step.approvers.length > 0"
                                                        @change="suggestDistribution(idx, $event.target.value); $event.target.value = ''"
                                                        class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                    <option value="">Auto-distribute…</option>
                                                    <option value="equal">Equal</option>
                                                    <option value="hierarchical">Hierarchical</option>
                                                    <option value="majority-one">Majority-one</option>
                                                </select>
                                                <button type="button" @click="addApprover(idx)"
                                                        class="text-xs px-2 py-1 text-primary-600 dark:text-primary-400 border border-primary-300 dark:border-primary-600 rounded hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                                                    + Add Approver
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Weightage bar -->
                                        <div v-if="step.approvers.length > 0" class="mb-3">
                                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                                <span>Total weightage: <strong :class="totalWeightage(step) === 100 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">{{ totalWeightage(step) }}</strong></span>
                                                <span :class="totalWeightage(step) === 100 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">
                                                    {{ totalWeightage(step) === 100 ? '✓ Balanced' : 'Recommended: 100' }}
                                                </span>
                                            </div>
                                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden flex">
                                                <div v-for="(ap, ai) in step.approvers" :key="ai"
                                                     :style="{ width: weightagePercent(step, ap) + '%' }"
                                                     :class="approverBarColor(ai)"
                                                     class="h-full transition-all duration-300">
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1.5">
                                                <div v-for="(ap, ai) in step.approvers" :key="ai"
                                                     class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400">
                                                    <span :class="approverBarColor(ai)" class="w-2 h-2 rounded-full inline-block flex-shrink-0"></span>
                                                    <span class="truncate max-w-[120px]">{{ ap.label || ap.name || `Approver ${ai+1}` }}</span>
                                                    <span class="text-gray-400">({{ weightagePercent(step, ap) }}%)</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Approver rows -->
                                        <div v-if="step.approvers.length === 0"
                                             class="text-xs text-gray-400 py-2 text-center border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                                            No approvers added. They can also be assigned dynamically at runtime.
                                        </div>

                                        <div class="space-y-2">
                                            <div v-for="(ap, ai) in step.approvers" :key="ai"
                                                 class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700/40 rounded-lg">

                                                <!-- Searchable user dropdown -->
                                                <div class="flex-1 min-w-0 relative">
                                                    <div class="relative">
                                                        <input
                                                            v-model="ap._search"
                                                            @input="onApproverSearch(idx, ai, ap._search)"
                                                            @focus="ap._open = true"
                                                            @blur="onApproverBlur(idx, ai)"
                                                            :placeholder="ap.label || 'Search user by name or email…'"
                                                            class="w-full text-xs px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 pr-6">
                                                        <svg v-if="ap._loading" class="absolute right-2 top-1/2 -translate-y-1/2 w-3 h-3 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                                        </svg>
                                                        <button v-else-if="ap.user_id" type="button" @click="clearApprover(idx, ai)"
                                                                class="absolute right-1.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <!-- Dropdown results -->
                                                    <div v-if="ap._open && ap._results && ap._results.length > 0"
                                                         class="absolute z-50 top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-40 overflow-y-auto">
                                                        <button v-for="u in ap._results" :key="u.id" type="button"
                                                                @mousedown.prevent="selectUser(idx, ai, u)"
                                                                class="w-full text-left px-3 py-2 text-xs hover:bg-primary-50 dark:hover:bg-primary-900/20 flex flex-col">
                                                            <span class="font-medium text-gray-900 dark:text-white">{{ u.name }}</span>
                                                            <span v-if="u.email" class="text-gray-400">{{ u.email }}</span>
                                                        </button>
                                                    </div>
                                                    <div v-else-if="ap._open && ap._search && ap._search.length >= 2 && !ap._loading && (!ap._results || ap._results.length === 0)"
                                                         class="absolute z-50 top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg px-3 py-2 text-xs text-gray-400">
                                                        No users found for "{{ ap._search }}"
                                                    </div>
                                                </div>

                                                <!-- Weightage -->
                                                <div class="flex items-center gap-1 flex-shrink-0">
                                                    <input v-model.number="ap.weightage" type="number" min="0" max="100"
                                                           class="w-14 text-xs px-2 py-1.5 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-center focus:ring-2 focus:ring-primary-500"
                                                           placeholder="50">
                                                    <span class="text-xs text-gray-400">wt</span>
                                                </div>

                                                <!-- Remove -->
                                                <button type="button" @click="removeApprover(idx, ai)"
                                                        class="p-1 text-gray-400 hover:text-red-500 flex-shrink-0">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form error -->
                    <div v-if="formError" class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                        {{ formError }}
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" @click="closeFormModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button @click="saveWorkflow" :disabled="saving || !canSubmit"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 rounded-lg transition-colors flex items-center gap-2">
                        <svg v-if="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        {{ editingWorkflow ? 'Save Changes' : 'Create Workflow' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ================================================================
             DELETE CONFIRM MODAL
             ================================================================ -->
        <div v-if="deletingWorkflow"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
             @click.self="deletingWorkflow = null">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Delete Workflow</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
                    </div>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-6">
                    Are you sure you want to delete <strong>{{ deletingWorkflow.name }}</strong>?
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="deletingWorkflow = null"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button @click="deleteWorkflow" :disabled="saving"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 rounded-lg transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import apiClient from '../../core/api/client.js';

// ── State ──────────────────────────────────────────────────────────────────
const workflows        = ref([]);
const loading          = ref(true);
const error            = ref(null);
const saving           = ref(false);
const entities         = ref([]);
const loadingEntities  = ref(false);
const searchQuery      = ref('');
const statusFilter     = ref('all');
const showFormModal    = ref(false);
const editingWorkflow  = ref(null);
const formError        = ref(null);
const form             = ref(defaultForm());
const deletingWorkflow = ref(null);

// Approval type definitions with clear explanations
const approvalTypes = [
    { value: 'serial',   label: 'Serial',   hint: 'One by one in sequence' },
    { value: 'parallel', label: 'Parallel',  hint: 'All notified at once' },
    { value: 'any-one',  label: 'Any-One',   hint: 'First to approve wins' },
];

const BAR_COLORS = [
    'bg-blue-500', 'bg-emerald-500', 'bg-violet-500',
    'bg-amber-500', 'bg-rose-500', 'bg-cyan-500',
    'bg-orange-500', 'bg-teal-500',
];

// Debounce timers per approver (keyed by "stepIdx-apIdx")
const searchTimers = {};

// ── Helpers ────────────────────────────────────────────────────────────────

function defaultForm() {
    return { name: '', description: '', model_type: '', is_active: true, steps: [] };
}

function defaultStep() {
    return {
        name: '',
        approval_type: 'serial',
        sla_hours: null,
        level_alias: '',
        allow_send_back: true,
        allows_delegation: true,
        minimum_approval_percentage: 100,
        approvers: [],
    };
}

function defaultApprover() {
    return {
        user_id: null,
        name: '',
        label: '',
        weightage: 50,
        // UI-only fields (not sent to backend)
        _search: '',
        _results: [],
        _open: false,
        _loading: false,
    };
}

function approvalTypeLabel(type) {
    return approvalTypes.find(t => t.value === type)?.label ?? type;
}

function approverBarColor(idx) {
    return BAR_COLORS[idx % BAR_COLORS.length];
}

function totalWeightage(step) {
    return step.approvers.reduce((sum, a) => sum + (Number(a.weightage) || 0), 0);
}

function weightagePercent(step, ap) {
    const total = totalWeightage(step);
    if (!total) return 0;
    return Math.round((Number(ap.weightage) / total) * 100);
}

// ── Computed ───────────────────────────────────────────────────────────────

const filteredWorkflows = computed(() => {
    let list = workflows.value;
    if (statusFilter.value === 'active')   list = list.filter(w => w.is_active);
    if (statusFilter.value === 'inactive') list = list.filter(w => !w.is_active);
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        list = list.filter(w =>
            w.name.toLowerCase().includes(q) ||
            (w.description || '').toLowerCase().includes(q) ||
            (w.model_type || '').toLowerCase().includes(q)
        );
    }
    return list;
});

const modelEntities = computed(() => entities.value.filter(e => e.type === 'model'));
const tableEntities = computed(() => entities.value.filter(e => e.type === 'table'));
const canSubmit     = computed(() => !!form.value.name.trim() && !!form.value.model_type.trim());

// ── Load data ──────────────────────────────────────────────────────────────

async function loadWorkflows() {
    loading.value = true;
    error.value   = null;
    try {
        const data      = await apiClient.getWorkflows({ limit: 100 });
        workflows.value = data.data ?? (Array.isArray(data) ? data : []);
    } catch {
        error.value = 'Failed to load workflows. Please try again.';
    } finally {
        loading.value = false;
    }
}

async function loadEntities() {
    loadingEntities.value = true;
    try {
        const data     = await apiClient.getEntities();
        entities.value = Array.isArray(data) ? data : (data.data ?? []);
    } catch {
        entities.value = [];
    } finally {
        loadingEntities.value = false;
    }
}

// ── User search (for approver dropdown) ───────────────────────────────────

function onApproverSearch(stepIdx, apIdx, query) {
    const key = `${stepIdx}-${apIdx}`;
    clearTimeout(searchTimers[key]);
    const ap = form.value.steps[stepIdx].approvers[apIdx];
    ap._results = [];
    if (!query || query.length < 2) return;
    ap._loading = true;
    searchTimers[key] = setTimeout(() => fetchUsers(stepIdx, apIdx, query), 300);
}

async function fetchUsers(stepIdx, apIdx, query) {
    const ap = form.value.steps[stepIdx]?.approvers[apIdx];
    if (!ap) return;
    try {
        const res = await apiClient.client.get('/users', { params: { search: query, limit: 30 } });
        ap._results = Array.isArray(res) ? res : (res.data ?? res);
    } catch {
        ap._results = [];
    } finally {
        ap._loading = false;
    }
}

function selectUser(stepIdx, apIdx, user) {
    const ap = form.value.steps[stepIdx].approvers[apIdx];
    ap.user_id  = user.id;
    ap.name     = user.name;
    ap.label    = user.label || user.name;
    ap._search  = user.name;
    ap._results = [];
    ap._open    = false;
}

function clearApprover(stepIdx, apIdx) {
    const ap = form.value.steps[stepIdx].approvers[apIdx];
    ap.user_id  = null;
    ap.name     = '';
    ap.label    = '';
    ap._search  = '';
    ap._results = [];
}

function onApproverBlur(stepIdx, apIdx) {
    // Small delay so mousedown on result fires first
    setTimeout(() => {
        const ap = form.value.steps[stepIdx]?.approvers[apIdx];
        if (ap) ap._open = false;
    }, 150);
}

// ── Modal ──────────────────────────────────────────────────────────────────

function openCreateModal() {
    editingWorkflow.value = null;
    form.value            = defaultForm();
    formError.value       = null;
    showFormModal.value   = true;
    if (!entities.value.length) loadEntities();
}

async function openEditModal(wf) {
    editingWorkflow.value = wf;
    formError.value       = null;

    let steps = [];
    try {
        const full = await apiClient.getWorkflow(wf.id);
        steps = (full.steps || []).map(s => ({
            _id:                         s.id,
            name:                        s.name,
            approval_type:               s.approval_type || 'serial',
            sla_hours:                   s.sla_hours || null,
            level_alias:                 s.level_alias || '',
            allow_send_back:             s.allow_send_back ?? true,
            allows_delegation:           s.allows_delegation ?? true,
            minimum_approval_percentage: s.minimum_approval_percentage ?? 100,
            approvers: (s.approvers || []).map(a => ({
                _id:      a.id,
                user_id:  a.user_id ?? null,
                name:     a.user?.name ?? a.label ?? '',
                label:    a.user?.name ?? a.label ?? `User #${a.user_id}`,
                weightage: Number(a.weightage) || 0,
                _search:  a.user?.name ?? a.label ?? (a.user_id ? `User #${a.user_id}` : ''),
                _results: [],
                _open:    false,
                _loading: false,
            })),
        }));
    } catch {
        steps = (wf.steps || []).map(s => ({ ...defaultStep(), name: s.name, _id: s.id }));
    }

    form.value = {
        name:        wf.name,
        description: wf.description || '',
        model_type:  wf.model_type || '',
        is_active:   wf.is_active,
        steps,
    };
    showFormModal.value = true;
    if (!entities.value.length) loadEntities();
}

function closeFormModal() {
    showFormModal.value   = false;
    editingWorkflow.value = null;
}

// ── Steps ──────────────────────────────────────────────────────────────────

function addStep() {
    form.value.steps.push(defaultStep());
}

function removeStep(idx) {
    form.value.steps.splice(idx, 1);
}

function moveStep(idx, dir) {
    const newIdx = idx + dir;
    if (newIdx < 0 || newIdx >= form.value.steps.length) return;
    const steps = [...form.value.steps];
    [steps[idx], steps[newIdx]] = [steps[newIdx], steps[idx]];
    form.value.steps = steps;
}

// ── Approvers ──────────────────────────────────────────────────────────────

function addApprover(stepIdx) {
    form.value.steps[stepIdx].approvers.push(defaultApprover());
}

function removeApprover(stepIdx, apIdx) {
    form.value.steps[stepIdx].approvers.splice(apIdx, 1);
}

async function suggestDistribution(stepIdx, strategy) {
    if (!strategy) return;
    const step  = form.value.steps[stepIdx];
    const count = step.approvers.length;
    if (!count) return;

    let distribution = [];
    try {
        const res  = await apiClient.client.post('/weightage/suggest', { approver_count: count, strategy });
        distribution = res.data?.distribution ?? res.distribution ?? [];
    } catch {
        // Local fallback: equal
        const base = Math.floor(100 / count);
        const rem  = 100 - base * count;
        distribution = Array.from({ length: count }, (_, i) => base + (i === 0 ? rem : 0));
    }

    step.approvers.forEach((ap, i) => {
        ap.weightage = distribution[i] ?? 0;
    });
}

// ── Save ───────────────────────────────────────────────────────────────────

async function saveWorkflow() {
    saving.value    = true;
    formError.value = null;

    const payload = {
        name:        form.value.name,
        description: form.value.description || null,
        model_type:  form.value.model_type,
        is_active:   form.value.is_active,
    };

    try {
        let workflowId;

        if (editingWorkflow.value) {
            await apiClient.updateWorkflow(editingWorkflow.value.id, payload);
            workflowId = editingWorkflow.value.id;
        } else {
            const created = await apiClient.createWorkflow(payload);
            workflowId = created.id;
        }

        await syncSteps(workflowId);

        closeFormModal();
        await loadWorkflows();
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error;
        formError.value = msg || 'Failed to save workflow. Please check your input.';
    } finally {
        saving.value = false;
    }
}

async function syncSteps(workflowId) {
    for (let i = 0; i < form.value.steps.length; i++) {
        const step = form.value.steps[i];
        const stepPayload = {
            name:                        step.name,
            approval_type:               step.approval_type,
            sla_hours:                   step.sla_hours || null,
            level_alias:                 step.level_alias || null,
            allow_send_back:             step.allow_send_back,
            allows_delegation:           step.allows_delegation,
            minimum_approval_percentage: step.minimum_approval_percentage ?? 100,
            sequence:                    i + 1,
            // Approvers are sent as part of step payload for backend to handle
            approvers: step.approvers
                .filter(ap => ap.user_id)
                .map((ap, ai) => ({
                    user_id:   ap.user_id,
                    weightage: Number(ap.weightage) || 0,
                    sequence:  ai + 1,
                })),
        };

        if (step._id) {
            await apiClient.client.put(`/workflows/${workflowId}/steps/${step._id}`, stepPayload);
        } else {
            await apiClient.client.post(`/workflows/${workflowId}/steps`, stepPayload);
        }
    }
}

// ── Status toggle ──────────────────────────────────────────────────────────

async function toggleStatus(wf) {
    try {
        const endpoint = wf.is_active ? 'disable' : 'enable';
        await apiClient.client.patch(`/workflows/${wf.id}/${endpoint}`);
        wf.is_active = !wf.is_active;
    } catch {
        await loadWorkflows();
    }
}

// ── Clone ──────────────────────────────────────────────────────────────────

async function cloneWorkflow(wf) {
    try {
        await apiClient.client.post(`/workflows/${wf.id}/clone`, { name: wf.name + ' — Copy' });
        await loadWorkflows();
    } catch { /* ignore */ }
}

// ── Delete ─────────────────────────────────────────────────────────────────

function confirmDelete(wf) {
    deletingWorkflow.value = wf;
}

async function deleteWorkflow() {
    if (!deletingWorkflow.value) return;
    saving.value = true;
    try {
        await apiClient.deleteWorkflow(deletingWorkflow.value.id);
        deletingWorkflow.value = null;
        await loadWorkflows();
    } catch { /* ignore */ } finally {
        saving.value = false;
    }
}

// ── Init ───────────────────────────────────────────────────────────────────

onMounted(() => {
    loadWorkflows();
    loadEntities();
});
</script>
