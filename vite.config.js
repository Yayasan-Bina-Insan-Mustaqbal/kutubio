import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        cors: true,
        allowedHosts: ['dockerdev.tail0faa6b.ts.net'],
        hmr: {
            host: 'dockerdev.tail0faa6b.ts.net',
            protocol: 'wss',
            clientPort: 5173,
        },
    },
});
