# Telemetry for agents and developers

Primary source of truth for **runtime** cluster/translation state: live `GET /api/telemetry_snapshot`. Do not infer prod DB state from code alone.

## Snapshot API

- **URL:** `GET https://<host>/api/telemetry_snapshot?token=…&limit=25&translation_limit=150` (or header `X-Telemetry-Token`).
- **Token (local, gitignored):** `scripts/telemetry_token.local.txt` — same as Admin → Telemetry.
- **`translation_limit`** (50–300): widens `translations` section (cluster state, jobs queue, manual monitor). Use for cluster / Mark review / SEO handoff diagnostics without server access.

### Root fields

- **`code`:** `deploy_revision`, `git_head` (if `.git/HEAD` readable), `autopilot_build` (`TRANSLATION_AUTOPILOT_BUILD`), `file_sha256_12` — short SHA-256 of key PHP files. Deploy id: `site/deploy_revision.txt` (one line) or env `SITE_DEPLOY_REVISION`.
- **`translation_clusters`:** alias of `translations.cluster_state` (same rows).

### `translations` section (inspect first)

| Path | Purpose |
|------|---------|
| `translations.cluster_state[]` | Per-cluster row in `translation_cluster_state` — **includes handoff fields** |
| `translations.cluster_blocking_detail` | Orchestrator counts (`translate_cluster`, `cluster_pipeline`) vs leaf queue |
| `translations.autopilot_build` | Code fingerprint for autopilot logic |
| `translations.jobs.queue_health` | Stale running jobs, future-scheduled pending, recent failures |
| `translations.jobs.recent` | Recent translation admin_jobs |
| `translations.logs_translations` | `system_logs` channel `translations` |
| `translations.manual_monitor` | Translation orders / candidates (if tables exist) |

Cron `translation_autopilot_tick` payload may include **`cron_max_wall_seconds`**, **`cron_wall_budget_hit`**.

## Control API

`POST /api/telemetry_control` with JSON `action` + `token`. Requires **control enabled** in Telemetry settings (not snapshot-only).

See `.cursor/rules/telemetry-dobivay.mdc` for actions (`cluster_snapshot`, `cluster_drive`, `meta_fix_tick`, `autopilot_tick`, `seo_page_meta_patch`, …).

Per-page SEO (DB vs live HTML): `GET /api/telemetry_page_seo?token=…` — params `url=` or `entity`+`entity_id`, optional `lang_id`, `fetch=0`, `normalize=1`.

## Cluster handoff (freeze for autopilot)

**Handoff** = cluster is treated as **human-approved reference material**. Autopilot **skips** it for:

- new `translate` / `translate_cluster` enqueue,
- `cluster_pipeline` / `validate_cluster` as active cluster,
- **meta-fix** scan (`metadata_normalize` jobs).

Validation (`validate_cluster` on demand) and manual admin actions still work. Vector RAG ingests approved pairs.

### Two ways to enter handoff

| Mechanism | DB field | How it is set |
|-----------|----------|---------------|
| **SEO Monitor import** | `translation_cluster_state.seo_monitor_handoff = 1` | After successful **non-dry** `seo_monitor_import_cluster()` when **all scope locales** exist in `content_i18n` (`translation_cluster_has_full_scope_locales_in_ci`). Typical flow: Admin → SEO Monitor → import cluster JSON (`meta` or `full`). Also sets vector ingest `approved`. |
| **Mark review** (Translations → Review) | `translation_cluster_state.human_reviewed_at` | `translation_cluster_mark_human_reviewed()` when **no blockers**, **all scope locales present**. Sets `cluster_status=ready_to_publish`, `pipeline_stage=publish_ready`, updates `content_i18n` to `review` (or keeps `published`), ingests vector `approved`. |
| **Manual total approve** (admin) | `human_reviewed_at` | `translation_cluster_manual_total_approve()` — force ready, cancel pending pipeline jobs, same freeze semantics as Mark review. |

### Freeze rule (code: `translation_cluster_autopilot_freeze_exists_sql`)

Cluster is **frozen** when **either**:

1. `seo_monitor_handoff = 1`, **or**
2. `human_reviewed_at` is set **and** `max(content_i18n.updated_at) <= human_reviewed_at` (no edits since human sign-off).

If `content_i18n` is edited **after** `human_reviewed_at`, freeze **lifts** automatically on next `translation_cluster_refresh_state` (`human_reviewed_at` cleared). `seo_monitor_handoff` clears if scope locales drop below full set.

### Handoff in telemetry

In snapshot, per cluster row:

```json
{
  "seo_monitor_handoff": 0,
  "human_reviewed_at": "2026-04-15 12:00:00",
  "cluster_status": "ready_to_publish",
  "blocker_count": 0
}
```

**Diagnosis:**

- `seo_monitor_handoff: 1` → loaded via SEO Monitor; autopilot will not touch unless flag cleared or locales incomplete.
- `human_reviewed_at` set + no newer `content_i18n` → Mark review / manual approve; same skip behaviour.
- Cluster stuck in queue but never picked → check freeze fields; frozen clusters are excluded from `translation_cluster_find_active_state` and autopilot missing-entity scans.

`POST /api/telemetry_control` `action=cluster_snapshot` returns `locales_compact[]` blockers but **does not yet echo** `seo_monitor_handoff` / `human_reviewed_at` — use full snapshot `translations.cluster_state` filtered by `entity` + `entity_id`.

### Mark review UI preconditions (Translations → Review)

Button enabled only when: not already human-reviewed, not published-only lockout, **blocker_count = 0**, all scope locales filled. Action: `?u=cluster_review&ce=<entity>&cid=<entity_id>`.

## Safe operational sequence for a cluster

1. `cluster_snapshot` for `entity` + `entity_id` — `cluster_status`, `pending_jobs`, `running_jobs`, `scheduled_at` on pending jobs.
2. Check handoff: `seo_monitor_handoff`, `human_reviewed_at` in `translations.cluster_state`.
3. If pending jobs have **`scheduled_at` in the future**, worker waits — use `run_job` with `job_id` or fix scheduling.
4. **`translate_pipeline` / `cluster_drive`:** prefer **`process_jobs: 1`** per request (avoid nginx 504). Repeat or rely on cron.
5. If **`blocked`:** inspect `locales_compact[].blockers` — repair iterations or manual HTML/meta fixes.
6. After PHP edits: `php -l` on changed files.

## Related code

- `site/functions/translation_cluster.php` — state, freeze SQL, Mark review, manual approve
- `site/functions/seo_monitor.php` — `seo_monitor_import_cluster`, sets `seo_monitor_handoff`
- `site/functions/translation_autopilot.php` — enqueue skips frozen clusters (`autopilot_respect_monitor`, comments on handoff)
- `site/functions/site_telemetry.php` — snapshot + `cluster_snapshot` control action
