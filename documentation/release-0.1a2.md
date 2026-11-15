# NIWFF 0.1a2 — Administration and submissions

Development milestone: feature/admin-submission-workflows.

- Submission Review, Club Maintenance and Event Maintenance.
- Consistent two-column public club profiles.
- Secure, expiring amendment links and previous versions; separate external messages and internal notes.
- Guided multi-venue weekly schedules and reusable approved event venues.
- Simplified shared footer, direct contact email and Resources Useful Links.
- Nine historical club drafts and dry-run-first import with duplicate checks.

## Deployment gates

Back up the staging database; apply scripts/migrateAmendments.sql; configure site_base_url and email delivery; run the club import dry-run and apply only after checking matches. Complete database/email/browser acceptance tests from databaseWorkflows.md. Verify all imported details before publication. No production deployment or integration into main is implied by this development milestone.
