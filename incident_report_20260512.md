# Incident Report: Production Deployment Failure
**Date:** 2026-05-12
**Status:** Resolved

## Executive Summary
Following the deployment of the "Author Extraction" feature, the production environment experienced multiple service failures. The root causes were identified as missing PHP extensions in the production Docker image, a database user/database name mismatch, and missing configuration files for the SearXNG service. All issues have been resolved, and the system is fully operational.

## Incident Timeline
- **10:00 AM:** Deployment of updated `CaptureBook` feature and `Dockerfile` via Envoy.
- **10:05 AM:** Identification of `500 Internal Server Error` and `Redis` exception in production.
- **10:15 AM:** `Dockerfile` updated with `redis` and `exif` extensions; redeployment triggered.
- **10:30 AM:** Deployment successful, but database connection errors persisted (`FATAL: role "kutubio" does not exist`).
- **10:45 AM:** Discovered production DB superuser is `root` and default DB is `laravel`.
- **10:55 AM:** Created `kutubio` role, assigned `laravel` database ownership, and updated production `.env`.
- **11:10 AM:** Resolved `searxng` container failure by manually creating `settings.yml` on the host.
- **11:15 AM:** Full system verification completed.

## Technical Details

### 1. Missing PHP Extensions
The production `Dockerfile` lacked the `redis` (required for session/cache) and `exif` (required for image processing) extensions.
- **Resolution:** Added `pecl install redis` and `docker-php-ext-install exif` to the Dockerfile.

### 2. Database Identity Mismatch
The production environment was initialized with:
- **User:** `root`
- **Database:** `laravel`
However, the application was configured to expect:
- **User:** `kutubio`
- **Database:** `kutubio`
- **Resolution:**
  - Manually created `kutubio` role via `root` superuser.
  - Renamed target database connection in `.env` to `laravel`.
  - Updated `docker-compose.prod.yml` to reflect these changes.

### 3. SearXNG Configuration
The `searxng` container failed due to a bind-mount for `settings.yml` pointing to a non-existent file on the production host.
- **Resolution:** Manually provisioned `/root/kutubio/docker/searxng/settings.yml` on the server.

## Remediation & Prevention
- **Docker Parity:** Ensure the production `Dockerfile.prod` (or equivalent) is periodically synced with the dev environment dependencies.
- **Infrastructure as Code:** Document the manual DB setup steps or automate role creation in the initialization script.
- **Health Monitoring:** Use `docker compose ps` and `artisan tinker` checks as part of the deployment pipeline to catch connection issues early.

## Current Status
- **Application:** UP (https://kutubio.insantaqwa.org/admin/capture-book)
- **Database:** CONNECTED
- **Redis/Horizon:** ACTIVE
- **SearXNG:** RUNNING
