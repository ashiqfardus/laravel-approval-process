<template>
    <div class="flex h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Sidebar -->
        <Sidebar :open="store.sidebarOpen" @toggle="store.toggleSidebar" />

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <Navbar @toggle-sidebar="store.toggleSidebar" />

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900 p-6">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import { useAppStore } from './store.js';
import Navbar from './components/Navbar.vue';
import Sidebar from './components/Sidebar.vue';

const store = useAppStore();
</script>
