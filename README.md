# NI Walking Football Federation Website

NIWFF website redevelopment project. The existing public website remains at https://niwalkingfootball.com/ while its recoverable content and assets are archived for the new implementation.

## Documentation

- [Legacy-site content migration model](documentation/contentMigrationModel.md)
- [Legacy-site review and outstanding decisions](project/reviews/2026-11-06-legacySiteReview.md)
- [Legacy website archive index](data/legacySite/2026-11-06/legacySiteIndex.md)
- [Legacy image inventory](data/legacySite/2026-11-06/assetManifest.json)
- [Asset download verification report](data/legacySite/2026-11-06/assetRecoveryReport.json)
- [Original requirements backlog](REQUIREMENTS.md)
- [Shared requirements-management guidance](documentation/requirementsManagement.md)
- [Shared repository-layout guidance](documentation/repositoryLayout.md)
- [Shared testing process](documentation/testingProcess.md)
- [Shared release process](documentation/howToRelease.md)

## Content migration status

The first archive preserves five publicly accessible pages. The image archive attempted 37 references, successfully recovered 36 and recorded one missing scene/background URL. Derived content records contain nine club profiles, eight historical competition listings and seven news topics.

**None of the derived records is approved for immediate publication.** Every record must be reviewed against current NIWFF information and the original survey findings. The dated competitions belong in a historical archive, not the upcoming-events feed.

## Directory overview

- `data/legacySite/2026-11-06/`: the original public-site recovery, asset inventory, recovered images, and verification report.
- `data/legacySite/derived/`: structured migration candidates extracted from the legacy capture.
- `scripts/recoverLegacyAssets.py`: repeatable restricted public-asset recovery routine.
- `project/reviews/`: dated assessments and rebuilding decisions.
- `documentation/`: living project guidance.

## Recovery command

From the repository root:

```sh
python3 scripts/recoverLegacyAssets.py
```

The script does not overwrite existing downloaded assets, only fetches from the original NIWFF domain, and generates a SHA-256 verification report. GitHub Actions can run the same script on the recovery branch.

## Deployment

The replacement website has **not** been deployed. Do not change the existing DNS/hosting until new content, review workflow, accessibility, redirects and migration rollback are verified.
