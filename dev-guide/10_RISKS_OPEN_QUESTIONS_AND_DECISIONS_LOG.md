# 10. Risks, Open Questions, And Decisions Log

## Context

Previous step expected: architecture and build order docs up to [09_MVP_BUILD_ORDER.md](/home/abuhafi/Project/kutubio/dev-guide/09_MVP_BUILD_ORDER.md:1) exist.

This step keeps unresolved items visible. Goal is prevent quiet assumption drift while implementation starts. This file should stay short, updated, and operational.

## Steps

1. Track current high-risk areas.
   - mobile browser camera behavior across devices
   - QR scan reliability on small printed labels
   - capture under glare or poor light
   - metadata quality for local-language books
   - duplicate detection false positives
   - print alignment drift on real hardware
   - external SurrealDB availability, auth expiry, and network latency
2. Track current open questions.
   - exact QR payload token format beyond `v1`
   - public ID strategy: UUID vs ULID vs custom token
   - whether one review screen should also create `BookCopy` every time
   - whether every intake item must have QR before approval
   - whether backlog bulk flow remains in MVP or later phase
3. Track current deferred items.
   - SIS sync detail
   - advanced circulation rules
   - branch-aware inventory logic
   - advanced analytics
   - richer category editor
4. Track current decisions already made.
   - monolith only
   - phone-browser-first intake
   - QR only, no barcode
   - detached async sequential pipeline per book
   - append-only metadata revisions
   - initial DDC category seed lives in [reference/tier2DDC.json](/home/abuhafi/Project/kutubio/dev-guide/reference/tier2DDC.json:1)
   - Filament review queue is mandatory human checkpoint
   - current student-data source reference lives in [reference/surrealDB-connection.md](/home/abuhafi/Project/kutubio/dev-guide/reference/surrealDB-connection.md:1)
   - detailed SurrealDBV2 schema and graph queries live under [reference/SurrealDBV2/README.md](/home/abuhafi/Project/kutubio/dev-guide/reference/SurrealDBV2/README.md:1)
   - SurrealDB is already running and available for future integration work
5. Review this file whenever new feature changes one of:
   - physical workflow
   - identity model
   - queue handoff
   - approved data rules
   - print behavior

## Checks

### Automation

- None yet. This is decision-tracking document, not executable spec.

### Manual

- Revisit before each major implementation phase.
- Move resolved questions into concrete docs, not vague chat memory.
- If new assumption changes earlier files, update source doc and this log together.

## Incident Log

### 2026-05-05: Missing CSS on Remote/Tailscale Access
- **Symptom**: Admin panel appears without styles (no CSS).
- **Root Cause**: Laravel's @vite helper detected `public/hot` and attempted to load assets from the Vite dev server on port 5174. However, Tailscale was only serving port 80/443 for the main app, making the Vite port unreachable from the external browser.
- **Immediate Fix**: Removed `public/hot` and ran `vendor/bin/sail npm run build`.
- **Long-term Decision**: Prefer static builds (`npm run build`) for remote development sessions over Tailscale. Consider adding `npm run build` to the Docker container startup sequence to ensure consistent state.
