#!/usr/bin/env bash
# White scroll-to-top icon from assets/images/egg.svg
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/assets/images/egg.svg"
DEST="$ROOT/assets/images/egg-scroll-white.svg"

if [[ ! -f "$SRC" ]]; then
	echo "Missing: $SRC" >&2
	exit 1
fi

python3 - "$SRC" "$DEST" <<'PY'
import re
import sys

src, dest = sys.argv[1:3]
text = open(src, encoding="utf-8", errors="replace").read()
text = re.sub(r"<!DOCTYPE[^>]*>", "", text, flags=re.I | re.S)
text = re.sub(r"<\?xml[^?]*\?>", "", text, flags=re.I)
text = text.replace('fill="#000000"', 'fill="#ffffff"')
text = text.replace("fill:#000000", "fill:#ffffff")
open(dest, "w", encoding="utf-8").write(text.strip() + "\n")
PY

echo "Wrote $DEST"
