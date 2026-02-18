<template>
    <aside :class="[
        'bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-300',
        open ? 'w-64' : 'w-0 md:w-20'
    ]">
        <div class="h-full flex flex-col">
            <!-- Logo -->
            <div class="h-16 flex items-center justify-center border-b border-gray-200 dark:border-gray-700 px-4">
                <div v-if="open" class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold">
                        A
                    </div>
                    <span class="text-lg font-semibold text-gray-900 dark:text-white">Approval</span>
                </div>
                <div v-else class="h-8 w-8 rounded-lg bg-primary-600 flex items-center justify-center text-white font-bold">
                    A
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4 scrollbar-thin">
                <div class="px-3 space-y-1">
                    <router-link v-for="item in navigation" :key="item.name"
                                 :to="item.to"
                                 class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md transition-colors"
                                 :class="isActive(item.to) 
                                     ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400' 
                                     : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'">
                        <component :is="item.icon" class="h-5 w-5 flex-shrink-0" />
                        <span v-if="open">{{ item.name }}</span>
                    </router-link>
                </div>
            </nav>

            <!-- Footer -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-4">
                <a href="/" class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 rounded-md transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span v-if="open">Back to App</span>
                </a>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

defineProps({
    open: {
        type: Boolean,
        default: true
    }
});

const route = useRoute();

const navigation = [
    {
        name: 'Dashboard',
        to: '/',
        icon: 'IconDashboard'
    },
    {
        name: 'Entities',
        to: '/entities',
        icon: 'IconDatabase'
    },
    {
        name: 'Workflows',
        to: '/workflows',
        icon: 'IconWorkflow'
    },
    {
        name: 'Requests',
        to: '/requests',
        icon: 'IconRequests'
    },
    {
        name: 'Analytics',
        to: '/analytics',
        icon: 'IconChart'
    },
    {
        name: 'Settings',
        to: '/settings',
        icon: 'IconSettings'
    }
];

const isActive = (to) => {
    if (to === '/') {
        return route.path === '/';
    }
    return route.path.startsWith(to);
};

// Icon components (inline SVG)
const IconDashboard = {
    template: `
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
    `
};

const IconDatabase = {
    template: `
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
        </svg>
    `
};

const IconWorkflow = {
    template: `
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
        </svg>
    `
};

const IconRequests = {
    template: `
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
    `
};

const IconChart = {
    template: `
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
    `
};

const IconSettings = {
    template: `
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    `
};
</script>
