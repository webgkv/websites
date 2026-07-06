#!/usr/bin/env bash
# White scroll-to-top hook (cropped viewBox, tip points up) from hook.svg
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/assets/images/hook.svg"
DEST="$ROOT/assets/images/hook-scroll-white.svg"

if [[ ! -f "$SRC" ]]; then
	echo "Missing: $SRC" >&2
	exit 1
fi

python3 - "$SRC" "$DEST" <<'PY'
import re
import sys

src, dest = sys.argv[1:3]
text = open(src, encoding="utf-8", errors="replace").read()
path = re.search(r'(<path\b[^>]*d="[^"]+"[^/]*/>)', text, re.I | re.S)
if not path:
    raise SystemExit("path not found in hook.svg")
path_tag = path.group(1).replace('fill="#000000"', 'fill="#ffffff"')
out = '''<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="880 390 1070 2070" aria-hidden="true">
  {path}
</svg>
'''.format(path=path_tag)
open(dest, "w", encoding="utf-8").write(out)
PY

echo "Wrote $DEST"
