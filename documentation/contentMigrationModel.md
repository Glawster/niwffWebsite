# NIWFF content migration model

## Purpose

The original public-site capture under `data/legacySite/2026-11-06` is immutable reference material. These derived JSON files are **migration candidates only**, not approved content for a published website.

## Derived datasets

- `data/legacySite/derived/clubs.json` — nine club descriptions from the legacy Affiliated Clubs page.
- `data/legacySite/derived/events.json` — eight dated listings from its 2025 competition calendar.
- `data/legacySite/derived/news.json` — seven identifiable article/topics from the old Latest News page.

## Data safety rules

All imported records have `publishReady: false` and `recordStatus: "historical_unverified"`. An editorial review must validate the information and confirm the responsible club or federation officer before publishing. Missing information is left out or set to null rather than fabricated. Historical age and gender descriptions must not become present-day eligibility claims. Publicly available personal contact details are retained in the immutable raw capture but are deliberately *not* copied into the structured public datasets until confirmed.

The event publication process described in the existing root `REQUIREMENTS.md` is authoritative: club submission -> NIWFF review/approval -> events page -> Facebook Event preparation. These archive records do not bypass that workflow.

## Next development decisions

- Approve schema and edit roles; identify the source of the committee survey responses.
- Determine whether clubs update their own records or NIWFF editors update records on their behalf.
- Set minimum club profile fields and the last-verified timestamp; avoid appearing to offer active sessions from unverified historical data.
- Link recovered assets to records only after manually identifying subjects and obtaining any required usage permissions.
- Build the site against a new, separately validated production content dataset, not by publishing these migration files directly.
