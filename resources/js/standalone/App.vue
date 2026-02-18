<template>
    <div id="approval-admin" class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <router-view />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
    // Check for dark mode preference
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});
</script>
