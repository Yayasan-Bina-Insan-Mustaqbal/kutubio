# 11. Local Development Networking & Proxying

## Incident: Vite HMR and CORS Failures over Tailscale

### Context
When developing using **Tailscale Funnel/Serve** to access the local development environment via a public-ish tailnet HTTPS URL (e.g., `https://dockerdev.tail0faa6b.ts.net`), the default Vite configuration fails due to:
1.  **CORS Mismatch:** The browser accesses the site via HTTPS, but Vite attempts to serve assets/HMR via `http://localhost:5173`.
2.  **Host Header Security:** Vite 6+ blocks requests with unrecognized `Host` headers by default (returning `403 Forbidden`).
3.  **HMR Protocol Mismatch:** Secure pages (HTTPS) require Secure WebSockets (`wss://`) for HMR to connect.

### Resolution

To fix this, `vite.config.js` must be explicitly configured to recognize the proxy and force the correct protocols.

#### 1. Vite Configuration (`vite.config.js`)
The `server` block must include `allowedHosts`, `origin`, and a specific `hmr` configuration:

```javascript
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
```

*   **`allowedHosts`**: Prevents 403 errors when accessing via Tailscale URL.
*   **`hmr.protocol: 'wss'`**: Ensures HMR works over the HTTPS proxy.
*   **`hmr.host`**: Forces the browser to connect back to the Tailscale URL rather than localhost.

#### 2. Environment Variables (`.env`)
The Laravel application must be aware of the external URL to generate correct asset links:

```env
APP_URL=https://dockerdev.tail0faa6b.ts.net
# Note: VITE_DEV_SERVER_URL can also be used if dynamic switching is needed
```

#### 3. Tailscale Setup
Tailscale should proxy both the web server (80) and the Vite dev server (5173):

```bash
# Proxy the main app
tailscale serve --bg 127.0.0.1:80

# Proxy Vite (required for HMR and asset loading via HTTPS)
tailscale serve --bg --https=5173 127.0.0.1:5173
```

### Verification
- Check `public/hot` file: It should contain the Tailscale URL if `npm run dev` is running.
- Browser Console: Ensure `@vite/client` loads with `200 OK` and MIME type `application/javascript`.
- WebSocket: Ensure the "Network" tab shows a successful `wss://` connection to the HMR host.
