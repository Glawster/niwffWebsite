# NIWFF project-specific instructions

## Website version

The authoritative website version source is root-level `version.css`, using the `--siteVersion` custom property, following HWFC. The shared `assets/site.css` displays this value in public footers and PHP workflow screens. Do not hard-code another version in page templates.

Use the next intended release with an alpha suffix for development builds (e.g. 0.1a1, 0.1a2), increment for distinct shared builds, and use vX.Y annotated tags for final releases. Before allocating a version, check main, all local and remote branches and existing tags; never reuse a published version. Keep the version and release notes consistent. Update the version as the first change on new development branches, and tag completed branches when integrated in line with the shared release process.

Normal staging deployment is git pull into the existing document root; no separate version generation step is needed.
