# NIWFF staging deployment guide

## Status

This branch contains a static, mobile-responsive staging prototype, **not an approved live replacement**. Pages are deliberately marked `noindex,nofollow`; do not publish as the production site until federation approval, current content and privacy/accessibility review.

## Included pages

Home; Find a Club (search and club detail); Competitions & Events (clearly separated 2025 archive); International Teams overview; separate Over-60, Over-65 and Over-70 pages; News; Resources; About; Contact. Old URLs `/player-profiles`, `/latest-news`, `/affiliated-clubs` and `/gallery` get temporary HTML redirects.

## Prepare and verify

Run from the checkout of `feature/legacy-site-recovery`:

```sh
node --check assets/site.js
python3 -m unittest discover -s tests -p 'testStaging.py' -v
python3 scripts/buildStaging.py
```

The output is in `output/staging/`. The same build is run by GitHub Actions, where the `niwff-staging-site` artifact can be downloaded once CI succeeds. Only allow-listed public website files are copied; the original recovery archive, personal contacts, surveys and project documents are **not** exposed.

## Hosting

For an Apache server, set the staging virtual host's document root to the contents of `output/staging/` (not to the Git repository itself). Example:

```sh
rsync -av --delete output/staging/ user@host:/path/to/niwff-staging-document-root/
```

Substitute your actual account and hosting path. The build includes `.htaccess` for index routing, directory listing protection and the 404 page. The site assumes it is hosted at the root of a staging subdomain, e.g. `staging.niwalkingfootball.com`, **not** a subdirectory. HTTPS must be configured at the host.

Do not change the live `niwalkingfootball.com` DNS or hosting before acceptance testing. Protect staging with HTTP authentication or other access controls if sharing only with the committee; robots noindex is not access control.

## Staging acceptance checklist

- Confirm every route loads directly (including refreshing a squad detail page).
- Confirm club search and regional filter, keyboard navigation and mobile menu.
- Check the layout on mobile and desktop and run WCAG accessibility testing.
- Validate source attribution and usage permissions for images before including recovered photos.
- Confirm contact, official rules, current club schedules, eligibility, squad rosters, fixtures, results, policies and survey priorities.
- Implement a real, approved contact/event-submission workflow before public launch. The current preview does **not** claim that forms send or publish data.
- Replace the noindex policy and staging banners **only at approved production release**.
- Create final canonical URLs, a sitemap, official redirects, privacy notice and rollback procedure before production cutover.

## Scope limitations

The present prototype is dependency-free JavaScript rendering in static HTML shells. It is staging-review ready, but a production release should pre-render meaningful page content for search engines and visitors who disable JavaScript. Authentication, a content editor, event-submission approval and automatic Facebook publishing are **not implemented**. Current team/player data is intentionally withheld until verified.
