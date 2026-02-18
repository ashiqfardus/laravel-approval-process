import { defineStore } from 'pinia';
import apiClient from '@core/api/client.js';

export const useAppStore = defineStore('app', {
    state: () => ({
        user: null,
        darkMode: false,
        sidebarOpen: true,
        loading: false,
        stats: null,
        autoRefreshEnabled: localStorage.getItem('autoRefreshEnabled') !== 'false',
    }),

    actions: {
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            }
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        toggleAutoRefresh() {
            this.autoRefreshEnabled = !this.autoRefreshEnabled;
            localStorage.setItem('autoRefreshEnabled', this.autoRefreshEnabled);
            
            // Emit event to notify widgets
            window.dispatchEvent(new CustomEvent('autoRefreshToggled', { 
                detail: { enabled: this.autoRefreshEnabled } 
            }));
        },

        async fetchStats() {
            try {
                this.stats = await apiClient.getStats();
            } catch (error) {
                console.error('Failed to fetch stats:', error);
            }
        },

        setLoading(loading) {
            this.loading = loading;
        }
    }
});
