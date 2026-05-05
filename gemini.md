# Gemini Reminders

## Remote Dev Server SSH Details
- **Host**: 100.64.8.38 (dockerdev)
- **User**: root
- **Password**: cemara153

### Usage
```bash
ssh root@100.64.8.38
```

### Purpose
This server is the primary dev environment where Docker containers for Kutubio should run. If Docker is stuck, SSH in and run:
```bash
systemctl restart docker
```

### Tailscale Serve & Vite HMR
If the Tailscale HTTPS domain is not responding or CSS is missing:
1. **Check Tailscale Serve Status** on `dockerdev`:
   ```bash
   tailscale serve status
   ```
2. **Correct Configuration**:
   - Laravel should be on port 8080: `tailscale serve --https 443 http://127.0.0.1:8080`
   - Vite HMR should be on port 5174: `tailscale serve --https 5174 http://127.0.0.1:5174`
3. **Start Vite**:
   ```bash
   ./vendor/bin/sail npm run dev
   ```
4. **Environment Variables**:
   Ensure `.env` on `dockerdev` has:
   ```env
   VITE_HMR_HOST=dockerdev.tail0faa6b.ts.net
   VITE_HMR_PORT=5174
   VITE_HMR_PROTOCOL=wss
   ```
5. **Clean Assets**:
   Remove `public/hot` if switching between dev and production:
   ```bash
   rm public/hot
   ```

### Troubleshooting PDF Downloads
- **Livewire/Filament Actions**: Use the "Store to temp and redirect" pattern via signed URLs.
- **Gotenberg**: Ensure `gotenberg:3000` is reachable within the docker network.
- **Browser Issues**: If downloads come as UUIDs without `.pdf`, test in a standard browser (Firefox/Chrome) instead of the agentic browser.
