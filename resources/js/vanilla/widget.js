/**
 * Approval Process Widgets - Vanilla JS
 * 
 * Usage:
 * <script src="/vendor/approval-process/widget.js"></script>
 * <script>
 *   ApprovalProcess.widget('stats', {
 *     container: '#stats-widget',
 *     refreshInterval: 10000
 *   });
 * </script>
 */

import { StatsWidget } from '../core/widgets/StatsWidget.js';
import { PendingApprovalsWidget } from '../core/widgets/PendingApprovalsWidget.js';
import { ActivityFeedWidget } from '../core/widgets/ActivityFeedWidget.js';
import { WorkflowListWidget } from '../core/widgets/WorkflowListWidget.js';

const widgets = {
    stats: StatsWidget,
    'pending-approvals': PendingApprovalsWidget,
    'activity-feed': ActivityFeedWidget,
    'workflow-list': WorkflowListWidget,
};

const instances = new Map();

window.ApprovalProcess = {
    /**
     * Initialize a widget
     * 
     * @param {string} type - Widget type (stats, pending-approvals, activity-feed, workflow-list)
     * @param {object} options - Widget options
     * @returns {object} Widget instance
     */
    widget(type, options = {}) {
        const WidgetClass = widgets[type];
        
        if (!WidgetClass) {
            console.error(`Unknown widget type: ${type}`);
            return null;
        }

        const container = typeof options.container === 'string' 
            ? document.querySelector(options.container)
            : options.container;

        if (!container) {
            console.error(`Container not found: ${options.container}`);
            return null;
        }

        const widget = new WidgetClass(container, options);
        widget.render();

        // Store instance
        const id = options.id || `${type}-${Date.now()}`;
        instances.set(id, widget);

        return widget;
    },

    /**
     * Get widget instance by ID
     * 
     * @param {string} id - Widget ID
     * @returns {object|null} Widget instance
     */
    getInstance(id) {
        return instances.get(id) || null;
    },

    /**
     * Destroy widget instance
     * 
     * @param {string} id - Widget ID
     */
    destroy(id) {
        const widget = instances.get(id);
        if (widget && widget.destroy) {
            widget.destroy();
        }
        instances.delete(id);
    },

    /**
     * Destroy all widgets
     */
    destroyAll() {
        instances.forEach(widget => {
            if (widget.destroy) {
                widget.destroy();
            }
        });
        instances.clear();
    }
};

// Auto-initialize widgets with data attributes
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-approval-widget]').forEach(element => {
        const type = element.dataset.approvalWidget;
        const options = {
            container: element,
            ...JSON.parse(element.dataset.approvalOptions || '{}')
        };
        
        window.ApprovalProcess.widget(type, options);
    });
});

export default window.ApprovalProcess;
