<template>
    <div class="space-y-6">
        <!-- Page Header -->
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Overview of your approval process
            </p>
        </div>

        <!-- Stats Widget -->
        <div ref="statsContainer"></div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Pending Approvals Widget -->
            <div ref="pendingContainer"></div>

            <!-- Activity Feed Widget -->
            <div ref="activityContainer"></div>
        </div>

        <!-- Workflows Widget -->
        <div ref="workflowsContainer"></div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useAppStore } from '../store.js';
import { StatsWidget } from '@core/widgets/StatsWidget.js';
import { PendingApprovalsWidget } from '@core/widgets/PendingApprovalsWidget.js';
import { ActivityFeedWidget } from '@core/widgets/ActivityFeedWidget.js';
import { WorkflowListWidget } from '@core/widgets/WorkflowListWidget.js';

const store = useAppStore();

const statsContainer = ref(null);
const pendingContainer = ref(null);
const activityContainer = ref(null);
const workflowsContainer = ref(null);

let statsWidget = null;
let pendingWidget = null;
let activityWidget = null;
let workflowsWidget = null;

onMounted(() => {
    // Get refresh intervals from meta tags
    const getRefreshInterval = (name) => {
        const meta = document.querySelector(`meta[name="widget-refresh-${name}"]`);
        const interval = meta ? parseInt(meta.content) : 0;
        // Return 0 if auto-refresh is disabled
        return store.autoRefreshEnabled ? interval : 0;
    };

    // Initialize Stats Widget
    statsWidget = new StatsWidget(statsContainer.value, {
        refreshInterval: getRefreshInterval('stats'),
        showTrends: true
    });
    statsWidget.render();

    // Initialize Pending Approvals Widget
    pendingWidget = new PendingApprovalsWidget(pendingContainer.value, {
        limit: 5,
        showActions: true,
        refreshInterval: getRefreshInterval('pending'),
        onApprove: async (id) => {
            console.log('Approved:', id);
            await Promise.all([
                statsWidget.render(),
                pendingWidget.render(),
                activityWidget.render()
            ]);
        },
        onReject: async (id) => {
            console.log('Rejected:', id);
            await Promise.all([
                statsWidget.render(),
                pendingWidget.render(),
                activityWidget.render()
            ]);
        }
    });
    pendingWidget.render();

    // Initialize Activity Feed Widget
    activityWidget = new ActivityFeedWidget(activityContainer.value, {
        limit: 10,
        realtime: true,
        refreshInterval: getRefreshInterval('activity')
    });
    activityWidget.render();

    // Initialize Workflows Widget
    workflowsWidget = new WorkflowListWidget(workflowsContainer.value, {
        limit: 6,
        status: 'active',
        showActions: true
    });
    workflowsWidget.render();

    // Listen for auto-refresh toggle
    const handleAutoRefreshToggle = (event) => {
        const enabled = event.detail.enabled;
        
        // Destroy and recreate widgets with new refresh settings
        if (statsWidget) statsWidget.destroy();
        if (pendingWidget) pendingWidget.destroy();
        if (activityWidget) activityWidget.destroy();
        
        // Reinitialize with new settings
        const getInterval = (name) => {
            const meta = document.querySelector(`meta[name="widget-refresh-${name}"]`);
            const interval = meta ? parseInt(meta.content) : 0;
            return enabled ? interval : 0;
        };
        
        statsWidget = new StatsWidget(statsContainer.value, {
            refreshInterval: getInterval('stats'),
            showTrends: true
        });
        statsWidget.render();
        
        pendingWidget = new PendingApprovalsWidget(pendingContainer.value, {
            limit: 5,
            showActions: true,
            refreshInterval: getInterval('pending'),
            onApprove: async (id) => {
                await Promise.all([statsWidget.render(), pendingWidget.render(), activityWidget.render()]);
            },
            onReject: async (id) => {
                await Promise.all([statsWidget.render(), pendingWidget.render(), activityWidget.render()]);
            }
        });
        pendingWidget.render();
        
        activityWidget = new ActivityFeedWidget(activityContainer.value, {
            limit: 10,
            realtime: enabled,
            refreshInterval: getInterval('activity')
        });
        activityWidget.render();
    };
    
    window.addEventListener('autoRefreshToggled', handleAutoRefreshToggle);
    
    onUnmounted(() => {
        window.removeEventListener('autoRefreshToggled', handleAutoRefreshToggle);
    });
});

onUnmounted(() => {
    // Cleanup widgets
    if (statsWidget) statsWidget.destroy();
    if (pendingWidget) pendingWidget.destroy();
    if (activityWidget) activityWidget.destroy();
    if (workflowsWidget) workflowsWidget.destroy();
});
</script>
