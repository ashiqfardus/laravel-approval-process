import apiClient from '../api/client.js';
import { formatRelativeTime } from '../utils/formatters.js';

export class ActivityFeedWidget {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            limit: options.limit || 10,
            realtime: options.realtime || false,
            refreshInterval: options.refreshInterval || 30000,
            filters: options.filters || [],
            ...options
        };
        this.activities = [];
        this.refreshTimer = null;
    }

    async render() {
        try {
            const response = await apiClient.getActivityFeed({
                limit: this.options.limit,
                filters: this.options.filters
            });
            this.activities = response.data || [];
            this.container.innerHTML = this.template();
            
            if (this.options.refreshInterval > 0) {
                this.startAutoRefresh();
            }
        } catch (error) {
            this.container.innerHTML = this.errorTemplate(error);
        }
    }

    template() {
        if (this.activities.length === 0) {
            return this.emptyTemplate();
        }

        return `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Recent Activity
                    </h3>
                    ${this.options.realtime ? `
                        <span class="flex items-center text-xs text-green-600 dark:text-green-400">
                            <span class="w-2 h-2 bg-green-600 rounded-full mr-2 animate-pulse"></span>
                            Live
                        </span>
                    ` : ''}
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                    ${this.activities.map(activity => this.activityItem(activity)).join('')}
                </div>
            </div>
        `;
    }

    activityItem(activity) {
        const icon = this.getActivityIcon(activity.action);
        const color = this.getActivityColor(activity.action);

        return `
            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full ${color} flex items-center justify-center text-sm">
                            ${icon}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <span class="font-medium">${activity.user?.name || 'Someone'}</span>
                            <span class="text-gray-600 dark:text-gray-400"> ${this.getActionText(activity.action)} </span>
                            <span class="font-medium">${activity.subject || 'a request'}</span>
                        </p>
                        ${activity.description ? `
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                ${activity.description}
                            </p>
                        ` : ''}
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            ${formatRelativeTime(activity.created_at)}
                        </p>
                    </div>
                </div>
            </div>
        `;
    }

    getActivityIcon(action) {
        const icons = {
            created: '📝',
            submitted: '📤',
            approved: '✓',
            rejected: '✗',
            cancelled: '⊘',
            delegated: '👤',
            commented: '💬',
            updated: '✏️',
        };
        return icons[action?.toLowerCase()] || '•';
    }

    getActivityColor(action) {
        const colors = {
            created: 'bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
            submitted: 'bg-purple-100 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400',
            approved: 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400',
            rejected: 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400',
            cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-900/20 dark:text-gray-400',
            delegated: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400',
            commented: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400',
            updated: 'bg-orange-100 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400',
        };
        return colors[action?.toLowerCase()] || colors.created;
    }

    getActionText(action) {
        const texts = {
            created: 'created',
            submitted: 'submitted',
            approved: 'approved',
            rejected: 'rejected',
            cancelled: 'cancelled',
            delegated: 'delegated',
            commented: 'commented on',
            updated: 'updated',
        };
        return texts[action?.toLowerCase()] || action;
    }

    emptyTemplate() {
        return `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                <div class="text-6xl mb-4">📋</div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    No activity yet
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Recent approval activities will appear here.
                </p>
            </div>
        `;
    }

    errorTemplate(error) {
        return `
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-400">Failed to load activity feed</p>
                <p class="text-sm text-red-600 dark:text-red-500 mt-1">${error.message}</p>
            </div>
        `;
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
