# 11. Local Development Networking & Proxying

## Incident: Vite HMR and CSS Failures over Tailscale (Host Mismatch)

### Context
When switching Tailscale environments or nodes (e.g., from `dockerdev` to `cachyos-abuhafi`), hardcoded hostnames in `vite.config.js` cause:
1.  **CSS Loading Failures:** The browser attempts to load assets from the old, hardcoded host, resulting in 404s or connection timeouts.
2.  **HMR Connection Errors:** WebSockets fail to connect because the HMR host doesn't match the current access URL.
3.  **Port Conflicts (Zombie Processes):** Restaring `npm run dev` after a config change can fail with `Port in use` if the previous Vite process wasn't cleanly terminated inside the container.

### Resolution

To prevent these issues, `vite.config.js` should be refactored to be **dynamic** using environment variables.

#### 1. Dynamic Vite Configuration (`vite.config.js`)
Use `loadEnv` to pull settings directly from `.env`:

```javascript
import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'],
                refresh: true,
            }),
        ],
        server: {
            host: '0.0.0.0',
            port: parseInt(env.VITE_PORT ?? '5174'),
            strictPort: true,
            cors: true,
            allowedHosts: [env.VITE_HMR_HOST],
            hmr: {
                host: env.VITE_HMR_HOST,
                protocol: env.VITE_HMR_PROTOCOL ?? 'wss',
                clientPort: parseInt(env.VITE_HMR_PORT ?? '5174'),
            },
        },
    };
});
```

#### 2. Environment Variables (`.env`)
Ensure these variables match your current Tailscale node:

```env
APP_URL=https://cachyos-abuhafi.tail0faa6b.ts.net
VITE_HMR_HOST=cachyos-abuhafi.tail0faa6b.ts.net
VITE_PORT=5174
VITE_HMR_PORT=5174
VITE_HMR_PROTOCOL=wss
```

### Troubleshooting: "Port already in use"

If Vite fails to start even after stopping the command, a zombie process might be holding the port inside the container.

**Fix:**
```bash
# Enter the container and kill any process on the Vite port
./vendor/bin/sail exec laravel.test fuser -k 5174/tcp

# OR (if fuser is missing)
./vendor/bin/sail exec laravel.test ps aux | grep vite
./vendor/bin/sail exec laravel.test kill -9 <PID>

# OR Restart the container entirely
./vendor/bin/sail restart laravel.test
```

### Troubleshooting: Tailscale Serve Misconfiguration

If the HTTPS domain is not responding at all, the `tailscale serve` configuration on the node might be pointing to the wrong port.

**Check Status:**
```bash
tailscale serve status
```

**Fix:**
Ensure the mapping matches your Sail ports (default Laravel is 8080 on host, and Vite is 5174).
```bash
# Map root to Laravel
tailscale serve --https 443 http://127.0.0.1:8080

# Map Vite port
tailscale serve --https 5174 http://127.0.0.1:5174
```

### Verification & Cleanup
- **Filament CSS**: If the admin panel looks "plain" (no styling), check the browser console for failed requests to `:5174`.
- **HMR**: Ensure the browser console shows `[vite] connected`.
- **Hot File**: Verify `public/hot` contains the correct URL. If you are switching between `npm run dev` and production built assets, you MUST delete `public/hot` manually if it gets stuck.
  ```bash
  rm public/hot
  ```
