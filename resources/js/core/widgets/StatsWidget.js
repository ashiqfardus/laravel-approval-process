import apiClient from '../api/client.js';
import { formatNumber } from '../utils/formatters.js';

export class StatsWidget {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            refreshInterval: options.refreshInterval || 0,
            showTrends: options.showTrends !== false,
            ...options
        };
        this.stats = null;
        this.refreshTimer = null;
    }

    async render() {
        try {
            this.stats = await apiClient.getStats();
            this.container.innerHTML = this.template();
            
            if (this.options.refreshInterval > 0) {
                this.startAutoRefresh();
            }
        } catch (error) {
            this.container.innerHTML = this.errorTemplate(error);
        }
    }

    template() {
        if (!this.stats) return '<div class="text-center py-8">Loading...</div>';

        const stats = [
            {
                label: 'Pending Approvals',
                value: this.stats.pending_count || 0,
                trend: this.stats.pending_trend || 0,
                icon: '⏳',
                color: 'yellow'
            },
            {
                label: 'Approved Today',
                value: this.stats.approved_today || 0,
                trend: this.stats.approved_trend || 0,
                icon: '✓',
                color: 'green'
            },
            {
                label: 'Active Workflows',
                value: this.stats.active_workflows || 0,
                trend: this.stats.workflow_trend || 0,
                icon: '⚙️',
                color: 'blue'
            },
            {
                label: 'Overdue Requests',
                value: this.stats.overdue_count || 0,
                trend: this.stats.overdue_trend || 0,
                icon: '⚠️',
                color: 'red'
            }
        ];

        return `
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                ${stats.map(stat => this.statCard(stat)).join('')}
            </div>
        `;
    }

    statCard(stat) {
        const colorClasses = {
            yellow: 'bg-yellow-50 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400',
            green: 'bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400',
            blue: 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400',
            red: 'bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400'
        };

        const trendIcon = stat.trend > 0 ? '↑' : stat.trend < 0 ? '↓' : '→';
        const trendColor = stat.trend > 0 ? 'text-green-600' : stat.trend < 0 ? 'text-red-600' : 'text-gray-600';

        return `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">${stat.label}</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                            ${formatNumber(stat.value)}
                        </p>
                        ${this.options.showTrends ? `
                            <p class="text-sm ${trendColor} mt-2">
                                <span>${trendIcon}</span>
                                <span>${Math.abs(stat.trend)}%</span>
                                <span class="text-gray-500">vs last week</span>
                            </p>
                        ` : ''}
                    </div>
                    <div class="text-4xl ${colorClasses[stat.color]} p-3 rounded-full">
                        ${stat.icon}
                    </div>
                </div>
            </div>
        `;
    }

    errorTemplate(error) {
        return `
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-400">Failed to load statistics</p>
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
