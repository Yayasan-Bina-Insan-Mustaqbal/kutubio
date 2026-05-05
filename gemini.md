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
