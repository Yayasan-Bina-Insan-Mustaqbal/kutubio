import {
    defineConfig
} from 'vite';
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
        cors: {
            origin: 'https://dockerdev.tail0faa6b.ts.net',
            methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            credentials: true
        },
        host: '0.0.0.0',
        strictPort: true,
        port: 5173,
        origin: process.env.VITE_DEV_SERVER_URL || undefined,
        allowedHosts: [
            'dockerdev.tail0faa6b.ts.net',
            'localhost',
            '127.0.0.1'
        ],
        hmr: {
            host: process.env.VITE_HMR_HOST || 'localhost',
            protocol: process.env.VITE_HMR_PROTOCOL || 'ws',
            clientPort: process.env.VITE_HMR_CLIENT_PORT || 5173,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
