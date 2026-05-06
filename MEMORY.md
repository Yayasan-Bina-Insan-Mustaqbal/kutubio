# Project Memory & Persistent Rules

## Development Environment
- **PRIMARY RULE**: Always use the **DevServer** (`100.64.8.38`) via SSH for all development operations.
- **SSH Credentials**: `root@100.64.8.38` / `cemara153`.
- **Project Path**: `/root/projects/kutubio`.
- **Sail usage**: Prefix Artisan, Composer, and NPM commands with `./vendor/bin/sail` within the SSH session.

## System Labels & Navigation
- **Users Resource**: The navigation and model labels for `UserResource` are set to **Staff** (formerly "Lenders") to distinguish system users from library borrowers.
- **Borrowers Resource**: Use `BorrowerResource` for library members/borrowers.

## Action Namespaces (Filament v5)
- **CRITICAL**: Always use `Filament\Actions\` for all actions (DeleteAction, CreateAction, EditAction, ViewAction, etc.). 
- **DO NOT USE**: `Filament\Tables\Actions\*` or `Filament\Forms\Actions\*` as these will cause "Class not found" errors in v5.

## Networking
- **Vite Port**: `5174`.
- **Tailscale Serve**: Port `443` maps to `8080`, Port `5174` maps to `5174`.
