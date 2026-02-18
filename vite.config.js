import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [vue()],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                // Standalone admin panel (primary)
                standalone: resolve(__dirname, 'resources/js/standalone/app.js'),
                
                // Vanilla JS widgets
                widget: resolve(__dirname, 'resources/js/vanilla/widget.js'),
                
                // Vue components (for extraction)
                vue: resolve(__dirname, 'resources/js/vue/index.js'),
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return '[name].css';
                    }
                    return 'assets/[name].[ext]';
                }
            }
        },
        manifest: true,
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@core': resolve(__dirname, 'resources/js/core'),
        }
    }
});
