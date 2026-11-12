# NI Walking Football Federation Website

NIWFF website redevelopment project. The existing public website remains at https://niwalkingfootball.com/ while its recoverable content and assets are archived for the new implementation.

## Documentation

- [Website design and approval workflows](documentation/siteDesign.md)

- [Staging build and deployment instructions](documentation/stagingDeployment.md)
- [Legacy-site content migration model](documentation/contentMigrationModel.md)
- [Legacy-site review and outstanding decisions](project/reviews/2026-11-06-legacySiteReview.md)
- [Legacy website archive index](data/legacySite/2026-11-06/legacySiteIndex.md)
- [Legacy image inventory](data/legacySite/2026-11-06/assetManifest.json)
- [Asset download verification report](data/legacySite/2026-11-06/assetRecoveryReport.json)
- [Website requirements, including International Teams](REQUIREMENTS.md)
- [Shared requirements-management guidance](documentation/requirementsManagement.md)
- [Shared repository-layout guidance](documentation/repositoryLayout.md)
- [Shared testing process](documentation/testingProcess.md)
- [Shared release process](documentation/howToRelease.md)

## Content migration status

The first archive preserves five publicly accessible pages. The image archive attempted 37 references, successfully recovered 36 and recorded one missing scene/background URL. Derived content records contain nine club profiles, eight historical competition listings, seven news topics and three historic international-team category records.

**None of the derived records is approved for immediate publication.** Every record must be reviewed against current NIWFF information and the original survey findings. The dated competitions belong in a historical archive, not the upcoming-events feed.

## Directory overview

- `data/legacySite/2026-11-06/`: the original public-site recovery, asset inventory, recovered images, and verification report.
- `data/legacySite/derived/`: structured migration candidates extracted from the legacy capture, including `internationalTeams.json`.
- `scripts/recoverLegacyAssets.py`: repeatable restricted public-asset recovery routine.
- `project/reviews/`: dated assessments and rebuilding decisions.
- `documentation/`: living project guidance.

## Recovery command

From the repository root:

```sh
python3 scripts/recoverLegacyAssets.py
```

The script does not overwrite existing downloaded assets, only fetches from the original NIWFF domain, and generates a SHA-256 verification report. GitHub Actions can run the same script on the recovery branch.

## Staging prototype

A responsive static website prototype now includes Home, Find a Club, Events, International Teams (Over 60s, Over 65s, Over 70s), News, Resources, About and Contact. Club search and historic content browsing work without a backend. The prototype uses unverified historic listings only with explicit review warnings; no current player roster, event submission, direct contact form or live fixtures are implied.

The agreed staging hostname is `stagingniwf.hillsboroughwalkingfootball.com`, with a dedicated cPanel document root at `$HOME/public_html/stagingniwf.hillsboroughwalkingfootball.com` (confirm the actual cPanel path). This does not depend on control of the NIWFF domain's DNS.

**Deployment is exactly like HWFC:** the Git checkout is the staging web root, and changes are deployed with `git pull --ff-only`. No `rsync` or public subfolder is required. The staging document root is the checkout at `$HOME/public_html/stagingniwf.hillsboroughwalkingfootball.com`.

A tracked root `.htaccess` prevents HTTP access to `.git`, legacy `data/` (including recovered assets), documentation, project files and scripts. Verify that Apache applies the rules before sharing the staging link. Existing cPanel `.htaccess` directives must be preserved/merged.

See [staging deployment instructions](documentation/stagingDeployment.md) for cloning/moving the repo into the web root, DNS/SSL checks and the necessary 403 tests. The optional static package builder and CI remain available for separate archived artifacts but are not part of normal deployment.

The replacement has **not** been deployed to a server. Do not change the existing DNS/hosting until new content, review workflow, accessibility, redirects and rollback are verified.

## Database-backed submissions (staging)

The initial PHP/MySQL workflow adds Create an Event, Register My Club and an authenticated administration review queue. The existing website remains the Git checkout document root. See [database setup and acceptance tests](documentation/databaseWorkflows.md) before enabling the forms. No database credentials or user accounts are shipped in Git.
