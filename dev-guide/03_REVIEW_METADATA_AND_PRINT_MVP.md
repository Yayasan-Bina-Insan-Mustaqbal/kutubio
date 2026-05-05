# 03. Review, Metadata, And Print MVP

## Context

Previous step expected: phone capture and detached pipeline from [02_PHONE_CAPTURE_AND_ASYNC_INGESTION.md](/home/abuhafi/Project/kutubio/dev-guide/02_PHONE_CAPTURE_AND_ASYNC_INGESTION.md:1) are working and produce reviewable draft records.

This step turns raw and AI-enriched intake into trusted library data. Main focus is human review, metadata provenance, and minimal QR label printing. Approval quality matters more than automation rate here.

## Steps

1. Build Filament Review Queue as primary librarian workspace.
   - Show front image
   - Show back image
   - Show decoded QR payload
   - Show extracted fields
   - Show enrichment fields
   - Show failure reason if pipeline degraded
2. Make metadata provenance visible.
   - Mark field source:
     - raw capture
     - QR
     - LLM
     - enrichment
     - human review
   - Show revision timeline
   - Preserve prior payload snapshots
3. Make approval action append revision, not mutate history blindly.
   - Librarian edits draft
   - System saves `human_reviewed` revision
   - System marks record approved
   - System keeps earlier revisions for audit and debugging
4. Add duplicate detection review affordance.
   - Match by QR identity if applicable
   - Match by ISBN if extracted
   - Match by normalized title/author similarity
   - Let reviewer merge instead of creating duplicate canonical book
5. Keep category handling practical for MVP.
   - Start from imported DDC tier-2 data in [reference/tier2DDC.json](/home/abuhafi/Project/kutubio/dev-guide/reference/tier2DDC.json:1)
   - Assign category in review flow
   - Store the selected DDC `number` through `categories.code`
   - Delay advanced category-management UX until base intake is stable
6. Keep print MVP narrow.
   - Print QR labels only after record approval
   - Generate HTML/CSS in physical units
   - Render through Gotenberg
   - Support starting slot offset if sticker sheet format is fixed
   - Document exact label dimensions (e.g., [T&J 103](/home/abuhafi/Project/kutubio/dev-guide/reference/LABEL_DIMENSIONS.md))
7. Keep printer calibration adjustable.
   - Store printer profile or settings record
   - Support X/Y nudge values
   - Avoid hardcoded per-environment magic numbers
8. Defer non-MVP integrations intentionally.
   - Student SIS sync details
   - Advanced circulation rules
   - Bulk backlog automation
   - Rich analytics

## Checks

### Automation

- Add tests proving review approval creates new metadata revision.
- Add tests proving old revisions remain queryable after approval.
- Add tests for duplicate-detection suggestions from ISBN or normalized title match.
- Add tests proving only approved records can enter print flow.
- Add tests for generated print HTML dimensions and required QR payload presence.

### Manual

- Review queue should let librarian resolve one draft without opening multiple pages.
- Verify revision timeline is understandable to non-technical operators.
- Confirm approved record can print QR label without layout drift on real hardware.
- Confirm manual edit path is faster than re-capturing when AI draft is close but imperfect.
