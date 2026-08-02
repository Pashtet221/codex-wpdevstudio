#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAMP="$ROOT/node_modules/.codex-capture-ready"

command -v node >/dev/null 2>&1 || { echo "capture setup: Node.js is not available in this Codex environment" >&2; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "capture setup: npm is not available in this Codex environment" >&2; exit 1; }

cd "$ROOT"

if [[ ! -d node_modules/playwright || ! -d node_modules/sharp ]]; then
  echo "capture setup: installing Node.js dependencies..." >&2
  npm install --no-audit --no-fund
fi

# The stamp avoids running the Playwright browser installer on every capture.
# If Chromium was removed from the environment, capture-media.mjs will fail with
# a clear Playwright message; deleting the stamp forces automatic reinstall.
if [[ ! -f "$STAMP" ]]; then
  echo "capture setup: installing Playwright Chromium..." >&2
  npx playwright install chromium
  mkdir -p "$(dirname "$STAMP")"
  touch "$STAMP"
fi
