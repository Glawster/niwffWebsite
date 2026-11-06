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

## Deployment to clannEolas (same Git → cPanel approach as HWFC)

Create a *dedicated* NIWFF staging subdomain in clannEolas cPanel, preferably `niwff-staging.clanneolas.com` while the NIWFF DNS is not under your control. Its document root should be separate, e.g. `$HOME/public_html/niwff-staging.clanneolas.com`. Confirm the actual path in cPanel before proceeding, and provision HTTPS as for HWFC (AutoSSL if available, otherwise the previously used Let's Encrypt/acme.sh method).

Clone the public NIWFF repository into the server's `~/repos/` folder. Do not place the Git checkout in `public_html`:

```bash
mkdir -p ~/repos
cd ~/repos
git clone --branch feature/legacy-site-recovery https://github.com/Glawster/niwffWebsite.git
cd niwffWebsite
export NIWFF_STAGING_ROOT="$HOME/public_html/niwff-staging.clanneolas.com"
bash scripts/deployStaging.sh --init
bash scripts/deployStaging.sh
```

`--init` creates a destination ownership marker and refuses a directory already containing another site's index page. The second command runs tests, builds the static package, and shows an rsync **dry run**. Review the destination and changes. When satisfied, deploy:

```bash
bash scripts/deployStaging.sh --confirm
```

For future updates, run the same `--confirm` command from `~/repos/niwffWebsite`. It checks the origin and branch, refuses a dirty working tree, pulls with `git pull --ff-only`, runs Python smoke tests, builds into `output/staging/`, and synchronises only public files to the separately marked document root. The generated `output/` directory is Git-ignored.

**Important:** Confirm the target is dedicated to NIWFF; deployment uses `rsync --delete` within that directory, protecting the ownership marker, `.well-known`, `cgi-bin` and `error_log`. Never point it at `public_html` itself or HWFC's live/staging root. The script requires a path under a cPanel `public_html` folder containing `niwff` in its name. No SSH credentials or GitHub secrets are needed because deployment runs on the host.

Verify the chosen staging hostname (including `/international-teams/` and `/international-teams/over-60s/`) using a browser and `curl -I`. Set up cPanel Directory Privacy/Basic Auth if committee-only; `noindex` is not access control. No production NIWFF DNS changes are involved.

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
