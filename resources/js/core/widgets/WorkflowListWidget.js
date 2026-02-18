import apiClient from '../api/client.js';
import { formatDate } from '../utils/formatters.js';
import { getStatusColor } from '../utils/helpers.js';

export class WorkflowListWidget {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            limit: options.limit || 10,
            status: options.status || 'all',
            showActions: options.showActions !== false,
            onSelect: options.onSelect || null,
            ...options
        };
        this.workflows = [];
    }

    async render() {
        try {
            const response = await apiClient.getWorkflows({
                limit: this.options.limit,
                status: this.options.status !== 'all' ? this.options.status : undefined
            });
            this.workflows = response.data || [];
            this.container.innerHTML = this.template();
            this.attachEvents();
        } catch (error) {
            this.container.innerHTML = this.errorTemplate(error);
        }
    }

    template() {
        if (this.workflows.length === 0) {
            return this.emptyTemplate();
        }

        return `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Workflows (${this.workflows.length})
                    </h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                    ${this.workflows.map(workflow => this.workflowCard(workflow)).join('')}
                </div>
            </div>
        `;
    }

    workflowCard(workflow) {
        const statusColor = workflow.is_active 
            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
            : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';

        return `
            <div class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                 data-workflow-id="${workflow.id}">
                <div class="flex items-start justify-between mb-3">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex-1 pr-2">
                        ${workflow.name}
                    </h4>
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${statusColor}">
                        ${workflow.is_active ? 'Active' : 'Inactive'}
                    </span>
                </div>
                
                ${workflow.description ? `
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                        ${workflow.description}
                    </p>
                ` : ''}
                
                <div class="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                    <div class="flex items-center justify-between">
                        <span>Model:</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            ${this.formatModelName(workflow.model_type)}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Steps:</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            ${workflow.steps_count || 0}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Active Requests:</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            ${workflow.active_requests_count || 0}
                        </span>
                    </div>
                </div>
                
                ${this.options.showActions ? `
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 flex gap-2">
                        <button data-action="view" data-id="${workflow.id}"
                                class="flex-1 px-3 py-1.5 text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 border border-primary-600 dark:border-primary-400 rounded-md hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors">
                            View
                        </button>
                        <button data-action="edit" data-id="${workflow.id}"
                                class="flex-1 px-3 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-700 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            Edit
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    formatModelName(modelType) {
        if (!modelType) return 'N/A';
        const parts = modelType.split('\\');
        return parts[parts.length - 1];
    }

    emptyTemplate() {
        return `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                <div class="text-6xl mb-4">⚙️</div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    No workflows found
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Create your first workflow to get started.
                </p>
                <a href="/approval-admin#/workflows/create"
                   class="inline-block px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md transition-colors">
                    Create Workflow
                </a>
            </div>
        `;
    }

    errorTemplate(error) {
        return `
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-400">Failed to load workflows</p>
                <p class="text-sm text-red-600 dark:text-red-500 mt-1">${error.message}</p>
            </div>
        `;
    }

    attachEvents() {
        // Card click
        this.container.querySelectorAll('[data-workflow-id]').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('[data-action]')) return;
                const id = card.dataset.workflowId;
                if (this.options.onSelect) {
                    this.options.onSelect(id);
                } else {
                    window.location.href = `/approval-admin#/workflows/${id}`;
                }
            });
        });

        // Action buttons
        this.container.querySelectorAll('[data-action="view"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = e.target.dataset.id;
                window.location.href = `/approval-admin#/workflows/${id}`;
            });
        });

        this.container.querySelectorAll('[data-action="edit"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = e.target.dataset.id;
                window.location.href = `/approval-admin#/workflows/${id}/edit`;
            });
        });
    }

    destroy() {
        // Cleanup if needed
    }
}
