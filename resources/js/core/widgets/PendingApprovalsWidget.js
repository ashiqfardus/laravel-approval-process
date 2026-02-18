import apiClient from '../api/client.js';
import { formatRelativeTime } from '../utils/formatters.js';
import { getStatusColor } from '../utils/helpers.js';

export class PendingApprovalsWidget {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            limit: options.limit || 5,
            showActions: options.showActions !== false,
            refreshInterval: options.refreshInterval || 0,
            onApprove: options.onApprove || null,
            onReject: options.onReject || null,
            ...options
        };
        this.requests = [];
        this.refreshTimer = null;
    }

    async render() {
        try {
            const response = await apiClient.getPendingApprovals({
                limit: this.options.limit
            });
            this.requests = response.data || [];
            this.container.innerHTML = this.template();
            this.attachEvents();
            
            if (this.options.refreshInterval > 0) {
                this.startAutoRefresh();
            }
        } catch (error) {
            this.container.innerHTML = this.errorTemplate(error);
        }
    }

    template() {
        if (this.requests.length === 0) {
            return this.emptyTemplate();
        }

        return `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Pending Approvals (${this.requests.length})
                    </h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    ${this.requests.map(request => this.requestItem(request)).join('')}
                </div>
                ${this.requests.length >= this.options.limit ? `
                    <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900 text-center">
                        <a href="/approval-admin#/requests?status=pending" 
                           class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                            View all pending approvals →
                        </a>
                    </div>
                ` : ''}
            </div>
        `;
    }

    requestItem(request) {
        return `
            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            ${request.title || `Request #${request.id}`}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            ${request.workflow?.name || 'Unknown Workflow'}
                        </p>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>By ${request.requester?.name || 'Unknown'}</span>
                            <span>•</span>
                            <span>${formatRelativeTime(request.created_at)}</span>
                            ${request.sla_deadline ? `
                                <span>•</span>
                                <span class="${this.isOverdue(request.sla_deadline) ? 'text-red-600 font-medium' : ''}">
                                    Due ${formatRelativeTime(request.sla_deadline)}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    ${this.options.showActions ? `
                        <div class="flex items-center gap-2 ml-4">
                            <button data-action="approve" data-id="${request.id}"
                                    class="px-3 py-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors">
                                Approve
                            </button>
                            <button data-action="reject" data-id="${request.id}"
                                    class="px-3 py-1 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                                Reject
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    emptyTemplate() {
        return `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                <div class="text-6xl mb-4">✓</div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    All caught up!
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    You have no pending approvals at the moment.
                </p>
            </div>
        `;
    }

    errorTemplate(error) {
        return `
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-400">Failed to load pending approvals</p>
                <p class="text-sm text-red-600 dark:text-red-500 mt-1">${error.message}</p>
            </div>
        `;
    }

    isOverdue(deadline) {
        return new Date(deadline) < new Date();
    }

    attachEvents() {
        if (!this.options.showActions) return;

        this.container.querySelectorAll('[data-action="approve"]').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = e.target.dataset.id;
                if (this.options.onApprove) {
                    await this.options.onApprove(id);
                } else {
                    await this.handleApprove(id);
                }
            });
        });

        this.container.querySelectorAll('[data-action="reject"]').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const id = e.target.dataset.id;
                if (this.options.onReject) {
                    await this.options.onReject(id);
                } else {
                    await this.handleReject(id);
                }
            });
        });
    }

    async handleApprove(id) {
        try {
            await apiClient.approveRequest(id);
            await this.render();
        } catch (error) {
            alert('Failed to approve request: ' + error.message);
        }
    }

    async handleReject(id) {
        const reason = prompt('Please provide a reason for rejection:');
        if (!reason) return;

        try {
            await apiClient.rejectRequest(id, { reason });
            await this.render();
        } catch (error) {
            alert('Failed to reject request: ' + error.message);
        }
    }

    startAutoRefresh() {
        if (this.options.refreshInterval > 0) {
            this.refreshTimer = setInterval(() => {
                this.render();
            }, this.options.refreshInterval);
        }
    }

    destroy() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }
}
