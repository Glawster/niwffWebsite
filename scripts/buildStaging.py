#!/usr/bin/env python3
"""Build an isolated publishable NIWFF static staging directory.

Only allow-listed site files are copied: never expose recovery archives,
project notes, personal contact history, git files, or secrets as web assets.
"""
from __future__ import annotations

import argparse
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PAGES = [
    "index.html", "404.html",
    "find-a-club/index.html", "competitions/index.html",
    "international-teams/index.html",
    "international-teams/over-60s/index.html",
    "international-teams/over-65s/index.html",
    "international-teams/over-70s/index.html",
    "news/index.html", "resources/index.html",
    "about/index.html", "contact/index.html",
]
FILES = PAGES + ["assets/site.css", "assets/site.js"]
# Historical routes: preserve publicly linked URLs after cut-over.
REDIRECTS = {
    "player-profiles": "/international-teams/",
    "latest-news": "/news/",
    "affiliated-clubs": "/find-a-club/",
    "gallery": "/news/",
}


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", type=Path, default=ROOT / "output" / "staging")
    args = parser.parse_args()
    target = args.output.expanduser().resolve()

    # Keep generated deployment files separate from tracked source.
    if target == ROOT or ROOT in target.parents and target.parts[-1] != "staging":
        parser.error("Use a dedicated 'staging' output directory; refusing to modify source files")

    # Delete only the explicit generated staging tree, never the source root.
    if target.exists():
        shutil.rmtree(target)
    target.mkdir(parents=True)

    for filename in FILES:
        source = ROOT / filename
        if not source.is_file():
            raise FileNotFoundError(source)
        out = target / filename
        out.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(source, out)

    for legacy, destination in REDIRECTS.items():
        out = target / legacy / "index.html"
        out.parent.mkdir(parents=True, exist_ok=True)
        out.write_text(
            '<!doctype html><html lang="en-GB"><head><meta charset="utf-8">'
            '<meta name="robots" content="noindex,nofollow">'
            '<meta http-equiv="refresh" content="0;url=' + destination + '">'
            '<title>Redirecting - NIWFF</title></head><body>'
            '<p>This page has moved to <a href="' + destination + '">'
            + destination + '</a>.</p></body></html>\n',
            encoding="utf-8",
        )

    # This is for Apache deployments; hosting the output as DocumentRoot
    # ensures URLs such as /international-teams/ resolve to index.html.
    (target / ".htaccess").write_text(
        "Options -Indexes\nDirectoryIndex index.html\n"
        "ErrorDocument 404 /404.html\n"
        "<IfModule mod_headers.c>\n"
        "  Header always set X-Content-Type-Options nosniff\n"
        "  Header always set Referrer-Policy strict-origin-when-cross-origin\n"
        "</IfModule>\n",
        encoding="utf-8",
    )
    print(f"Prepared {len(FILES)} site files and {len(REDIRECTS)} redirects in {target}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
