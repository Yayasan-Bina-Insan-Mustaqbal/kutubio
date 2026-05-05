import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            host: '0.0.0.0',
            port: parseInt(env.VITE_PORT ?? '5174'),
            strictPort: true,
            cors: true,
            allowedHosts: [env.VITE_HMR_HOST ?? 'cachyos-abuhafi.tail0faa6b.ts.net'],
            hmr: {
                host: env.VITE_HMR_HOST ?? 'cachyos-abuhafi.tail0faa6b.ts.net',
                protocol: env.VITE_HMR_PROTOCOL ?? 'wss',
                clientPort: parseInt(env.VITE_HMR_PORT ?? '5174'),
            },
        },
    };
});
