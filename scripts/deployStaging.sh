#!/usr/bin/env bash
# Deploy the NIWFF static staging build from a Git checkout on the cPanel host.
# Unlike a bare git pull in public_html, publish only the isolated site build.
set -euo pipefail

usage() {
  cat <<'USAGE'
Usage: scripts/deployStaging.sh [--target PATH] [--init | --confirm]
       scripts/deployStaging.sh --help

Run from the NIWFF Git checkout on the clannEolas cPanel host.

Target: explicit --target PATH, or NIWFF_STAGING_ROOT environment variable.
Default: PREVIEW ONLY (rsync --dry-run), with no Git pull or file changes.

--init     Establish an ownership marker in a pre-existing staging document root.
           Refuses directories containing a non-NIWFF index.html/index.php.
--confirm  Pull the tracked branch, validate/build, and rsync the site into
           the staging document root (requires --init once).
--help     Show this information.

The target must be the dedicated public_html/stagingNIWF.hillsboroughwalkingfootball.com
folder. Never use a live domain's document root.
USAGE
}

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
TARGET="${NIWFF_STAGING_ROOT:-}"
MODE="preview"
MARKER=".niwff-staging-managed"
BRANCH="feature/legacy-site-recovery"

while (($#)); do
  case "$1" in
    --target)
      if (($# < 2)); then echo "--target needs a path" >&2; exit 2; fi
      TARGET="$2"; shift 2 ;;
    --init) MODE="init"; shift ;;
    --confirm) MODE="deploy"; shift ;;
    --help|-h) usage; exit 0 ;;
    *) echo "Unknown option: $1" >&2; usage >&2; exit 2 ;;
  esac
done

if [[ -z "$TARGET" ]]; then
  echo "Supply --target /home/ACCOUNT/public_html/stagingNIWF.hillsboroughwalkingfootball.com (or NIWFF_STAGING_ROOT)." >&2
  exit 2
fi
if [[ ! -d "$TARGET" ]]; then
  echo "Staging document root must already exist: $TARGET" >&2
  exit 2
fi
TARGET="$(cd "$TARGET" && pwd -P)"
# Allow only the specific, dedicated staging subdomain document root.
# Resolving the directory with pwd -P above prevents a symlink to a live site.
case "$TARGET" in
  /home/*/public_html/stagingNIWF.hillsboroughwalkingfootball.com) ;;
  *)
    echo "Refusing unsafe target. Expected a dedicated stagingNIWF.hillsboroughwalkingfootball.com directory under /home/*/public_html/: $TARGET" >&2
    exit 2 ;;
esac
if [[ "$(git -C "$ROOT" rev-parse --show-toplevel 2>/dev/null)" != "$ROOT" ]]; then
  echo "Please run this script from the NIWFF Git checkout." >&2
  exit 2
fi
if [[ "$(git -C "$ROOT" remote get-url origin 2>/dev/null)" != *"niwffWebsite"* ]]; then
  echo "Git origin does not appear to be the NIWFF repository." >&2
  exit 2
fi
if [[ "$(git -C "$ROOT" branch --show-current)" != "$BRANCH" ]]; then
  echo "Checkout $BRANCH before deployment." >&2
  exit 2
fi
if [[ "$MODE" == "init" ]]; then
  if [[ -e "$TARGET/$MARKER" ]]; then
    echo "Already initialised: $TARGET"
    exit 0
  fi
  if [[ -e "$TARGET/index.html" || -e "$TARGET/index.php" ]]; then
    echo "Refusing to initialise a document root with an existing index file." >&2
    exit 2
  fi
  printf 'NIWFF managed staging document root\n' > "$TARGET/$MARKER"
  echo "Initialised dedicated staging root: $TARGET"
  exit 0
fi
if [[ ! -f "$TARGET/$MARKER" ]]; then
  echo "Uninitialised root. Run with --init once, after checking its path." >&2
  exit 2
fi
for tool in python3 rsync git; do
  command -v "$tool" >/dev/null || { echo "Missing required tool: $tool" >&2; exit 2; }
done

if [[ "$MODE" == "deploy" ]]; then
  if [[ -n "$(git -C "$ROOT" status --porcelain)" ]]; then
    echo "Refusing to deploy a dirty working tree." >&2
    exit 2
  fi
  git -C "$ROOT" pull --ff-only origin "$BRANCH"
fi

python3 -m unittest discover -s "$ROOT/tests" -p 'testStaging.py' -v
python3 "$ROOT/scripts/buildStaging.py"
SOURCE="$ROOT/output/staging/"
# Protect cPanel/ACME files and the ownership marker from --delete.
RSYNC_OPTS=(
  --archive --verbose --itemize-changes --delete
  --filter="P /$MARKER"
  --filter='- /.htaccess'
  --filter='P /.htpasswd'
  --filter='P /.well-known/***'
  --filter='P /cgi-bin/***'
  --filter='P /error_log'
)
if [[ "$MODE" != "deploy" ]]; then RSYNC_OPTS+=(--dry-run); fi
printf '\nStaging source: %s\nDestination:    %s\nMode:           %s\n\n' "$SOURCE" "$TARGET/" "$MODE"
rsync "${RSYNC_OPTS[@]}" "$SOURCE" "$TARGET/"
if [[ "$MODE" == "deploy" ]]; then
  echo "Deployed NIWFF staging at commit $(git -C "$ROOT" rev-parse --short HEAD)"
else
  echo "Preview only. Run --confirm to pull and deploy."
fi
