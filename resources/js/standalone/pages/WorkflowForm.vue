<template>
    <div class="space-y-6">
        <div>
            <router-link to="/workflows" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                ← Back to Workflows
            </router-link>
            <h2 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                {{ isEdit ? 'Edit' : 'Create' }} Workflow
            </h2>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <p class="text-gray-600 dark:text-gray-400">Workflow form coming soon...</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const isEdit = computed(() => !!route.params.id);
</script>
