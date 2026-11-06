# NI Walking Football Federation Website

NIWFF website redevelopment project. The existing public website remains at https://niwalkingfootball.com/ while its recoverable content and assets are archived for the new implementation.

## Documentation

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

For clannEolas staging, use `bash scripts/deployStaging.sh --init` once, then preview with `bash scripts/deployStaging.sh` and deploy with `bash scripts/deployStaging.sh --confirm` after setting `NIWFF_STAGING_ROOT` to the dedicated cPanel document root. This follows the HWFC server Git-pull approach while protecting cPanel authentication files. Do not set the Git repo as DocumentRoot.

To build the isolated web-ready package separately, run `python3 scripts/buildStaging.py`. It writes deployable files to `output/staging/` rather than exposing this entire repository. The `validate-staging.yml` GitHub Action checks JavaScript, validates the build and uploads a staging artifact. See [staging deployment instructions](documentation/stagingDeployment.md).

The replacement has **not** been deployed to a server. Do not change the existing DNS/hosting until new content, review workflow, accessibility, redirects and rollback are verified.
