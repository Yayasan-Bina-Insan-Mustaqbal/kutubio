# Incident Report: 2026-05-05 - Tailscale HTTPS Domain & Asset Loading Issues

## Incident Description
The application was not responding via the Tailscale HTTPS domain (`https://dockerdev.tail0faa6b.ts.net`), and when accessed via direct IP, the CSS/assets were missing (unstyled layout with huge icons).

## Timeline
- **13:00**: Reported by user that the website is not responding.
- **13:05**: Verified that the server and containers were UP on `dockerdev`.
- **13:10**: Identified that `tailscale serve` was incorrectly proxying to port 80 instead of 8080.
- **13:15**: Identified that CSS was missing because Vite (`npm run dev`) was not running.
- **13:30**: Discovered that a stale `public/hot` file was pointing to the wrong host (`cachyos-abuhafi`).
- **13:40**: Ran `npm run build` and removed `public/hot` to restore styling via built assets.
- **14:20**: Updated `.env` and `tailscale serve` to support `npm run dev` (Vite) over HTTPS on port 5174.

## Root Causes
1. **Misconfigured Proxy**: `tailscale serve` was pointing to port 80, but Laravel Sail was mapped to port 8080 on the host.
2. **Missing Dev Server**: `npm run dev` was not active, and no production assets were built.
3. **Stale Hot File**: `public/hot` contained the wrong hostname, causing the browser to seek assets on a non-existent dev server.
4. **Port Mismatch**: `tailscale serve` was proxying port 5173, but Vite was configured to use 5174.

## Resolution
1. **Tailscale Config**: Updated `tailscale serve` on `dockerdev`:
   - `/` -> `http://127.0.0.1:8080` (HTTPS 443)
   - Port 5174 -> `http://127.0.0.1:5174` (HTTPS 5174)
2. **Asset Build**: Ran `npm run build` and deleted `public/hot`.
3. **Vite Start**: Started `./vendor/bin/sail npm run dev` in the background.
4. **Environment Fix**: Updated `.env` on `dockerdev` with:
   - `VITE_HMR_HOST=dockerdev.tail0faa6b.ts.net`
   - `VITE_HMR_PORT=5174`
   - `VITE_HMR_PROTOCOL=wss`

## Resolution - PDF Downloads
1. **Route Implementation**: Added a signed temporary download route at `/download/temp/{filename}`.
2. **Filament Update**: Changed `streamDownload` to a store-then-redirect pattern in `BookCopyResource` and `BookResource`.
3. **Browser Compatibility**: Identified that the Antigravity (Agentic) browser was handling binary streams incorrectly.

## Prevention & Future References
- Always check `tailscale serve status` if the domain is down.
- Ensure `VITE_HMR_HOST` matches the Tailscale domain being used to access the site.
- If switching between `npm run dev` and production assets, ensure `public/hot` is managed correctly.
- **Browser Compatibility Note**: If downloads fail or show UUID names, verify the behavior in a standard browser (Firefox/Chrome).
