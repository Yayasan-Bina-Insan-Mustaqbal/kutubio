# Kutubio Specification (SPEC.md)

## 1. Project Overview
**Kutubio** is a modern Library Management System (LMS) built with Laravel and Filament. It is designed to manage book collections, track loans (circulation), and handle library administrative tasks through a powerful, server-driven admin panel.

## 2. Technology Stack
- **Framework**: Laravel 11/13
- **Admin Panel**: Filament v5 (using Schemas API)
- **Frontend**: Livewire v4, Tailwind CSS v4, Alpine.js
- **Database**: PostgreSQL
- **Caching/Queues**: Redis / Laravel Horizon
- **Dev Ops**: Laravel Sail, Tailscale (Networking), Gotenberg (PDF rendering)

## 3. Core Modules

### 3.1 Book Management
- **Books**: Global record for a book title (ISBN, Author, Title, etc.).
- **Book Copies**: Individual physical copies of a book with unique `public_id` and status (Available, Loaned, Lost, Damaged).
- **Categories**: Subject classification for books.

### 3.2 Circulation
- **Borrowers**: The members of the library who borrow books.
- **Loans**: Tracking the lifecycle of a loan (Loaned, Due, Returned, Overdue). Includes "Flag for Deletion" workflow for staff.

### 3.3 User Management
- **Staff (Users)**: System administrators and library staff. Roles include Admin and Staff.

### 3.4 Utilities & Settings
- **General Settings**: Library branding and global configurations.
- **Print Profiles**: Configuration for printing stickers/labels.
- **Jobs & Queue**: Background processing for heavy tasks.

## 4. Development Standards
- **Follow Laravel Best Practices**: Use Eloquent Resources, Form Requests, and Policies.
- **Filament v5 Patterns**:
    - Use `Filament\Actions` for all action definitions.
    - Use static `make()` methods for components.
    - Use `Schemas` for form/table definitions.
- **RTL Support**: Arabic text support for workbook components.

## 5. Environment & Infrastructure (CRITICAL)

### 5.1 Dev Server Configuration
All development operations MUST be performed on the remote **DevServer** unless explicitly instructed otherwise.

- **DevServer Host**: `dockerdev.tail0faa6b.ts.net` (`100.64.8.38`)
- **Access**: via Tailscale
- **SSH User**: `root`
- **SSH Password**: `cemara153`
- **Project Root**: `/root/projects/kutubio`
- **App URL**: `https://dockerdev.tail0faa6b.ts.net`
- **Vite Port**: `5174` (exposed via Tailscale Serve)

### 5.2 Command Execution
- **Artisan/Composer/NPM**: Always run via `vendor/bin/sail` inside the SSH session on the DevServer.
- **Example**: `ssh root@100.64.8.38 "cd /root/projects/kutubio && ./vendor/bin/sail artisan migrate"`

## 6. Known Issues & Troubleshooting
- **Vite Connectivity**: Ensure `tailscale serve` is mapping port `5174` correctly on the devserver.
- **Permissions**: Ensure `/root/projects/kutubio` is owned by UID `1000` (`chown -R 1000:1000`) for Sail to operate correctly.
- **HMR**: Browser must be on the Tailnet to receive Hot Module Replacement updates.
