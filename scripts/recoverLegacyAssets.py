#!/usr/bin/env python3
"""Archive publicly accessible NIWFF visual assets listed in the legacy manifest.

This tool is intentionally limited to the known niwalkingfootball.com origin.
It never visits private paths, scrapes user data or overwrites existing assets.
"""
from __future__ import annotations

import hashlib
import json
from datetime import datetime, timezone
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import urlparse
from urllib.request import Request, urlopen

ROOT = Path(__file__).resolve().parents[1]
ARCHIVE = ROOT / "data" / "legacySite" / "2026-11-06"
MANIFEST = ARCHIVE / "assetManifest.json"
MAX_BYTES = 12 * 1024 * 1024


def assetValidate(content: bytes, suffix: str) -> bool:
    """Recognise a small allow-list of expected image formats."""
    if suffix == ".png":
        return content.startswith(b"\x89PNG\r\n\x1a\n")
    if suffix in {".jpg", ".jpeg"}:
        return content.startswith(b"\xff\xd8\xff")
    if suffix == ".svg":
        head = content[:4096].lstrip().lower()
        return b"<svg" in head and b"<!doctype" not in head and b"<!entity" not in head
    return False


def assetFetch(url: str) -> bytes:
    """Read a bounded response, rejecting redirects to foreign domains."""
    request = Request(url, headers={"User-Agent": "NIWFF-Public-Archive/1.0"})
    with urlopen(request, timeout=25) as response:
        if urlparse(response.url).hostname != "niwalkingfootball.com":
            raise ValueError("Unexpected redirect off the original domain")
        content = response.read(MAX_BYTES + 1)
        if len(content) > MAX_BYTES:
            raise ValueError("Asset exceeds the archive size limit")
        return content


def main() -> int:
    manifest = json.loads(MANIFEST.read_text(encoding="utf-8"))
    report = {"capturedAt": datetime.now(timezone.utc).isoformat(), "assets": []}
    failed = 0

    for entry in manifest["assets"]:
        url = entry["url"]
        relative = Path(entry["archivePath"])
        target = ARCHIVE / relative

        if urlparse(url).hostname != "niwalkingfootball.com":
            raise ValueError(f"Not a permitted archive origin: {url}")
        if relative.is_absolute() or ".." in relative.parts or relative.parts[0] != "assets":
            raise ValueError(f"Invalid archive destination: {relative}")

        result = {"url": url, "file": str(relative)}
        try:
            if target.exists():
                data = target.read_bytes()
                if not assetValidate(data, target.suffix.lower()):
                    raise ValueError("Existing file is not a valid expected image")
                result["status"] = "already-present"
            else:
                data = assetFetch(url)
                if not assetValidate(data, target.suffix.lower()):
                    raise ValueError("Response not a recognised expected image")
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_bytes(data)
                result["status"] = "downloaded"
            result["size"] = len(data)
            result["sha256"] = hashlib.sha256(data).hexdigest()
        except (HTTPError, URLError, TimeoutError, ValueError, OSError) as exc:
            result["status"] = "failed"
            result["reason"] = str(exc)
            failed += 1
        report["assets"].append(result)
        print(f"{result['status']}: {relative}")

    (ARCHIVE / "assetRecoveryReport.json").write_text(
        json.dumps(report, indent=2) + "\n", encoding="utf-8"
    )
    print(f"Recovered/verified {len(report['assets']) - failed} assets; failed: {failed}")
    return 1 if failed else 0


if __name__ == "__main__":
    raise SystemExit(main())
