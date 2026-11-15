#!/usr/bin/env python3
"""Staging build smoke tests using Python's standard library."""
from __future__ import annotations

import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


class StagingTests(unittest.TestCase):
    def test_build_contains_allowed_files_only(self):
        with tempfile.TemporaryDirectory() as td:
            out = Path(td) / "staging"
            subprocess.run(
                [sys.executable, str(ROOT / "scripts" / "buildStaging.py"),
                 "--output", str(out)],
                check=True, capture_output=True, text=True,
            )
            routes = [
                "index.html", "find-a-club/index.html",
                "competitions/index.html", "international-teams/index.html",
                "international-teams/over-60s/index.html",
                "international-teams/over-65s/index.html",
                "international-teams/over-70s/index.html",
                "news/index.html", "gallery/index.html", "resources/index.html",
                "about/index.html", "contact/index.html",
            ]
            for route in routes:
                html = (out / route).read_text(encoding="utf-8")
                self.assertIn('id="site-nav"', html, route)
                self.assertIn('id="app"', html, route)
                self.assertIn("noindex,nofollow", html, route)
            self.assertTrue((out / "assets/site.js").is_file())
            self.assertTrue((out / "assets/site.css").is_file())
            self.assertTrue((out / "player-profiles/index.html").is_file())
            self.assertFalse((out / "data").exists(), "Never deploy raw data archive")
            self.assertFalse((out / "REQUIREMENTS.md").exists())
            self.assertFalse((out / ".github").exists())

    def test_repo_root_apache_protects_archives(self):
        rules = (ROOT / ".htaccess").read_text(encoding="utf-8")
        for entry in (".git", ".github", "data", "documentation",
                      "project", "scripts", "tests", "output"):
            self.assertIn(entry, rules)
        self.assertIn("Require all denied", rules)
        self.assertIn("Options -Indexes", rules)
        self.assertIn('X-Robots-Tag', rules)
        self.assertIn('FilesMatch', rules)

    def test_archive_and_links(self):
        js = (ROOT / "assets/site.js").read_text(encoding="utf-8")
        self.assertIn("length:5", js)
        self.assertIn('concat("older")', js)
        self.assertIn("Calendar of forthcoming events", js)
        self.assertIn("Past Events", js)
        self.assertIn("602931127880903", js)
        self.assertIn("info@niwalkingfootball.com", js)
        self.assertIn("EMAIL_ACTIVE=false", js)
        self.assertIn('role="tab"', js)
        self.assertTrue((ROOT / "gallery/index.html").is_file())

    def test_navigation_and_team_routes_in_script(self):
        js = (ROOT / "assets/site.js").read_text(encoding="utf-8")
        for path in ("/find-a-club/", "/competitions/", "/international-teams/",
                     "/news/", "/resources/", "/about/", "/contact/",
                     "over-60s", "over-65s", "over-70s"):
            self.assertIn(path, js)
        self.assertIn('aria-expanded', js)
        self.assertIn('club-query', js)
        self.assertIn("historical", js.lower())


if __name__ == "__main__":
    unittest.main()
