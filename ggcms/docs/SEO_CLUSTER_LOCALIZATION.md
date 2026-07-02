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
2. **Literary quality** — not raw machine output: natural grammar, syntax, and vocabulary for native readers.
3. Translate **block by block** (heading, paragraph, table cell, list item, `alt`, FAQ pair).
4. Keep UI terms in Latin where established: **Easy**, **Medium**, **Hard**, **Hardcore**, **Play**, **Cash Out**, **Chicken Road**, **Provably Fair**, game names.
5. Update `name`, `title`, `description` per locale; fix wrong legacy slugs/names (e.g. old Aviatrix rows on a Chicken Road page).
6. **Do not break JSON** — valid UTF-8, escaped quotes in `content`, `indent=4` export format.

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

For large clusters, optional repo builders follow the `tools/build_chickenroad_home_cluster.py` pattern (`*_locales.py` + `*_overrides*.py` + `build_*_cluster.py`). Rebuild writes back into the working `*-full.json`.

`authors` clusters are plain-text bios — skip table/figure normalization.

## Import

Import **only** `*-full.json`:

```bash
php site/scripts/import_seo_cluster_cli.php /path/to/seo-games-1-full.json games 1 full
```

Or Admin → SEO Monitor → Import → **Full**.

## Quality gates before handoff

- [ ] EN (canonical) passes all issues in `*-report.json` after re-export
- [ ] Every locale: same number of sections/tables/images as EN
- [ ] No Aviatrix / wrong-game copy left in sibling locales
- [ ] All `alt` translated; `src` unchanged
- [ ] Title/description within SEO limits per locale
- [ ] Read-aloud test: no obvious MT calques or broken HTML entities

## Related

- `.cursor/rules/seo-cluster-html-hygiene.mdc` — quick rule for agents
- `docs/AGENT_TELEMETRY.md` — prod cluster state after import
- `tools/build_chickenroad_home_cluster.py` — homepage cluster builder pattern
