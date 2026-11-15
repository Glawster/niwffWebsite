# NIWFF staging deployment — repository root is website root

## Hosting model

The NIWFF staging website uses the same arrangement as Hillsborough Walking Football: the **Git repository root itself is the Apache/cPanel document root**. There is no build, copying, rsync or separate `public/` directory in the normal deploy.

Website: `https://stagingniwf.hillsboroughwalkingfootball.com/`

Suggested cPanel document root: `$HOME/public_html/stagingniwf.hillsboroughwalkingfootball.com` (confirm the **actual directory** in cPanel).

The repository contains older public-site assets, archives and development files as requested. **These files are not intended to be web-accessible.** The tracked root `.htaccess` blocks requests to source control, historic data, documentation, project plans, scripts, tests, generated output and non-web source formats. Apache must honour `.htaccess` and `Require all denied` for those rules to work.

## Initial installation (new empty web directory)

First create the `stagingniwf.hillsboroughwalkingfootball.com` subdomain and its dedicated document root in cPanel, using Hillsborough's DNS zone (no NIWFF DNS access needed).

SSH into the cPanel account and enter the exact document root created by cPanel:

```bash
cd "$HOME/public_html/stagingniwf.hillsboroughwalkingfootball.com"
pwd -P
ls -la
```

If that folder is empty, clone directly into it:

```bash
git clone --branch feature/legacy-site-recovery \
  https://github.com/Glawster/niwffWebsite.git .
```

**If it already contains files**, don't overwrite anything or delete cPanel configuration. Review them first. If it contains a cPanel-managed `.htaccess` (for Directory Privacy, PHP handling or redirects), back it up elsewhere and merge its directives with the repository `.htaccess`. Do not remove cPanel-managed access controls to force Git cloning.

## If the repository was already cloned to ~/repos

Check the intended document root is an empty and separate directory, then move the existing checkout rather than cloning twice:

```bash
# First inspect both locations; do not run mv onto a populated site directory.
ls -la "$HOME/repos/niwffWebsite"
ls -la "$HOME/public_html/stagingniwf.hillsboroughwalkingfootball.com"
```

If the destination is empty and you are certain it is the NIWFF staging document root, use:

```bash
rmdir "$HOME/public_html/stagingniwf.hillsboroughwalkingfootball.com"
mv "$HOME/repos/niwffWebsite" \
   "$HOME/public_html/stagingniwf.hillsboroughwalkingfootball.com"
```

If the directory contains existing server-managed files, resolve their configuration before attempting this move. **Never run these commands against the HWFC site root.**

## Regular deployment

From the cPanel staging document root:

```bash
cd "$HOME/public_html/stagingniwf.hillsboroughwalkingfootball.com"
git status --short
git branch --show-current
git pull --ff-only
```

The tracked branch is currently `feature/legacy-site-recovery`; when merging to `main` in future, switch the deployed checkout deliberately and ensure that the right branch is used.

The website is plain HTML, CSS and JavaScript. No npm build step, rsync or deployment automation is needed for normal updates.

## Server checks — important

1. Set up an SSL certificate for `stagingniwf.hillsboroughwalkingfootball.com`, using cPanel AutoSSL or the previously proven Let's Encrypt/acme.sh process.
2. Test homepage and nested team URL, including a reload:
   ```bash
   curl -I https://stagingniwf.hillsboroughwalkingfootball.com/
   curl -I https://stagingniwf.hillsboroughwalkingfootball.com/international-teams/over-60s/
   ```
3. Verify that the Apache access rules actually protect private paths (these should return **403** or **404**, never **200**):
   ```bash
   curl -I https://stagingniwf.hillsboroughwalkingfootball.com/.git/config
   curl -I https://stagingniwf.hillsboroughwalkingfootball.com/data/legacySite/2026-11-06/affiliatedClubs.md
   curl -I https://stagingniwf.hillsboroughwalkingfootball.com/REQUIREMENTS.md
   ```
4. Confirm that `/assets/site.js` and `/assets/site.css` are accessible, and that the club search and mobile navigation work.
5. Ensure Apache honours `AllowOverride` and the appropriate modules. If the protection checks fail, **remove public access to staging** until server configuration is corrected; `noindex` alone does not provide privacy.
6. Configure cPanel Directory Privacy or HTTP authentication for committee-only review. Carefully merge any cPanel-added `.htaccess` rules with the tracked repository file; a `git pull` may otherwise conflict with uncommitted edits.

## Legacy archives and images

The recovered old-site images and assets remain in `data/legacySite/` as requested. They do not need to be moved. The `data/` path is blocked from web requests, but is readable through the server filesystem for future content migration.

This deployment model differs from the optional `scripts/buildStaging.py` package/CI workflow, which is still available for an isolated static artifact if needed. **It is not part of the normal staging deployment.**

## Release limitations

This is a review site, not the public production replacement: historic clubs/squads/events remain unverified; there is not yet a working contact form, club event submission/approval service or approved privacy/governance content. Leave the `noindex` policy in place until production release.

The existing `niwalkingfootball.com` DNS and both live HWFC sites remain unchanged.
