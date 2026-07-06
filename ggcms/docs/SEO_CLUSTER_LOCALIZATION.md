# SEO cluster localization workflow

Manual preparation of `seo_cluster_v1` JSON exports before import into `content_i18n` via SEO Monitor or CLI. This is **not** the autopilot / `seo_monitor_handoff` pipeline in PHP — it is the editorial handoff for agents and humans.

## Files and folders

| Item | Role |
|------|------|
| `seo-<entity>-<id>-full.json` | **Working file** — round-trip export/import (`mode: full`) |
| `seo-<entity>-<id>-report.json` | **SEO checklist only** — do not import |
| Reference cluster (e.g. `seo-pages-1-full.json`) | Canonical HTML layout |
| Target cluster (e.g. `seo-games-1-full.json`) | Material to clean and translate |

Typical local layout:

- `~/Downloads/06/` — finished reference clusters
- `~/Downloads/05/` — cluster currently in progress

## Two-phase process

### Phase 1 — Canonical locale (usually EN, `lang_id: 1`)

1. **Strip editor cruft** from Word / Google Docs paste:
   - inline `style="…"`, `font-size`, `c17`, `MsoNormal`, `docs-internal-*`
   - spacer `<p>&nbsp;</p>`, empty `<span>`, stray `<i></i>` in lists
   - pseudo-tables built with `<br/>` instead of `<table>`
   - pseudo-headings (`<p><strong>…</strong></p>`) → real `<h1>`–`<h3>`
2. **Match site markup** (reference: `pages#1` / `seo-pages-1-full.json`):
   - Tables: `<div class="table-responsive"><table class="table table-bordered">` + `<thead>` / `<tbody>`
   - Images: `<figure class="section-media__figure"><img src="…" border="0" alt="…" /></figure>`
   - Body: plain `<p>`, `<h1>` once, then `<h2>` / `<h3>`, `<ul>` / `<ol>`
   - **Do not** use `img-fluid`, `my-4`, `rounded` on figures (games/guides)
3. **Fix SEO report** (`*-report.json`):
   - `title_too_long` — title ≤ **70** display chars
   - `description_too_long` — meta description ≤ **160** plain chars
   - `h1_not_single` — exactly **one** `<h1>` in `content`
   - `img_missing_alt` — every `<img>` has meaningful `alt`
   - `body_empty` — real HTML body present
4. **Preserve** image `src` paths and JSON schema (`locales[]` shape, `lang_id`, `seo_monitor_ctx`).
5. **Currency in copy** — amounts in **USD** (`$0.50`, `$20,000`), not INR/EUR/RUB in localized text unless the user explicitly asks for an exception.

### Phase 2 — Target locales

1. Translate **from the finished canonical** — same HTML skeleton, same `src`, same tag order.
2. **Full parity — no shortening.** Every target locale must carry the **same editorial volume** as EN: every `<h1>`–`<h3>`, paragraph, list item, table row/cell, FAQ pair, and image `alt`. **Do not** summarise, compress, skip sections, or leave locales on EN fallback. A locale is incomplete if any block is missing or visibly shorter than the canonical.
3. **Literary quality** — not raw machine output: natural grammar, syntax, and vocabulary for native readers. Rephrase freely for fluency, but **never** drop facts, examples, or list entries to save space.
4. Translate **block by block** (heading, paragraph, table cell, list item, `alt`, FAQ pair).
5. Keep UI terms in Latin where established: **Easy**, **Medium**, **Hard**, **Hardcore**, **Play**, **Cash Out**, **Chicken Road**, **Provably Fair**, game names.
6. Update `name`, `title`, `description` per locale; fix wrong legacy slugs/names (e.g. old Aviatrix rows on a Chicken Road page).
7. **Do not break JSON** — valid UTF-8, escaped quotes in `content`, `indent=4` export format.

## HTML hygiene checklist

| Remove | Replace with |
|--------|----------------|
| `<span style="font-size: 24pt"><strong>Title</strong></span>` | `<h1>` or `<h2>` |
| Feature / Details via `<br/>` | Bootstrap table |
| `<p><img class="img-fluid" …></p>` | `<figure class="section-media__figure">…` |
| `data-admin-img-edit` attrs | (drop) |
| FAQ as `<br/>` chains | `<h2>FAQ</h2>` + `<p><strong>Q?</strong> A.</p>` |
| `&nbsp;` spacer paragraphs | (delete) |

## Automation

```bash
# Normalize HTML in place (games, guides, pages)
python3 scripts/normalize_seo_cluster_html.py /path/to/seo-games-1-full.json

# Games-specific pass (tables + figures)
python3 scripts/normalize_games_html.py /path/to/seo-games-1-full.json
```

### Blog cluster builders (EN only)

For one-off blog articles, keep **translations in the working `*-full.json`** — the agent edits and polishes locales there by hand. Do **not** commit `chickenroad_blog_*_overrides*.py` to git.

Repo tools (example: `tools/build_chickenroad_blog_3_cluster.py`):

- `chickenroad_blog_*_locales.py` — canonical **EN** structured body (`_EN_BODY`), image paths, partner hrefs
- `build_chickenroad_blog_*_cluster.py` — rebuilds **only `lang_id=1`** HTML into the JSON; other locales are left unchanged

```bash
python3 ggcms/sites/chickenroad/tools/build_chickenroad_blog_3_cluster.py
```

Homepage / casino article builders may still use `*_overrides*.py` where that pipeline is established — blog handoffs use JSON-only i18n.

For large clusters, optional repo builders follow the `tools/build_chickenroad_home_cluster.py` pattern. Rebuild writes back into the working `*-full.json`.

`authors` clusters are plain-text bios — skip table/figure normalization.

## Import

Import **only** `*-full.json`:

```bash
php site/scripts/import_seo_cluster_cli.php /path/to/seo-games-1-full.json games 1 full
```

Or Admin → SEO Monitor → Import → **Full**.

## Quality gates before handoff

- [ ] EN (canonical) passes all issues in `*-report.json` after re-export
- [ ] Every locale: **identical structure** to EN — same number of sections, tables, images, list items, FAQ entries, and paragraphs (no EN fallback rows, no omitted blocks)
- [ ] Every locale: `content` byte length within **±15%** of EN (shorter usually means accidental shortening — expand before handoff)
- [ ] No Aviatrix / wrong-game copy left in sibling locales
- [ ] All `alt` translated; `src` unchanged
- [ ] Title/description within SEO limits per locale
- [ ] Read-aloud test: no obvious MT calques or broken HTML entities

### Parity check (recommended)

```bash
python3 - <<'PY'
import json, re, sys
from pathlib import Path
p = Path(sys.argv[1])
d = json.load(p.open())
en = next(x for x in d["locales"] if x["lang_id"] == 1)
def stats(html):
    return {
        "bytes": len(html),
        "h2": len(re.findall(r"<h2", html, re.I)),
        "h3": len(re.findall(r"<h3", html, re.I)),
        "p": len(re.findall(r"<p[> ]", html, re.I)),
        "li": len(re.findall(r"<li", html, re.I)),
        "img": len(re.findall(r"<img", html, re.I)),
    }
base = stats(en["content"])
for loc in d["locales"]:
    s = stats(loc.get("content") or "")
    if not loc.get("content"):
        print(f"FAIL lang_id={loc['lang_id']}: empty content")
        continue
    bad = [k for k in base if s[k] != base[k]]
    ratio = s["bytes"] / base["bytes"] if base["bytes"] else 0
    flag = "OK" if not bad and 0.85 <= ratio <= 1.15 else "CHECK"
    print(f"{flag} lang_id={loc['lang_id']} bytes={ratio:.0%} tags={bad or 'match'}")
PY
/path/to/seo-*-full.json
```

## Related

- `.cursor/rules/seo-cluster-html-hygiene.mdc` — quick rule for agents
- `docs/AGENT_TELEMETRY.md` — prod cluster state after import
- `tools/build_chickenroad_home_cluster.py` — homepage cluster builder pattern
