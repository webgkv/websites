# Аудит папок бренда (2026-07-02)

## Что оставляем и зачем

| Папка | Нужна? | Содержимое |
|-------|--------|------------|
| `site/` | **Да** | Overlay: layouts, assets, config.example, entrypoints — деплоится на сервер |
| `brand.php` | **Да** | Профиль бренда → `config/brand.profile.php` при build |
| `plugins/` | **Только если есть фичи** | powerball: `lottery/` (10 файлов). У aviator/chickenroad нет |
| `scripts/` | **Только brand-specific** | chickenroad: DB import (`prepare_chickenroad_db.py`, `DB_AND_IMAGES.txt`). Общие — в `ggcms/scripts/shared/` |
| `tools/` | **Да, per-brand** | Python для SEO-кластеров и контента. Одноразовые миграции, не runtime |
| `docs/` | **Да, per-brand** | aviator: backlog + гайды. Общие: `ggcms/docs/AGENT_TELEMETRY.md` |
| `hestia/` | **Да** | nginx-шаблоны для деплоя на HestiaCP |
| `.cursor/` | **Да** | Правила агента (aviator, chickenroad). powerball — нет |
| `deploy.ftp.*` | **Да** | Секреты и пример деплоя |

## Что удалили / консолидировали

### Дубликаты (были копии chickenroad в powerball)
- `powerballjackpot/scripts/` — целиком (7 файлов = копия chickenroad)
- `powerballjackpot/tools/` — 68 файлов-дубликатов; осталось 14 powerball-specific
- `docs/AGENT_TELEMETRY.md`, `SEO_CLUSTER_LOCALIZATION.md` — одна копия в `ggcms/docs/`

### Общие скрипты → `ggcms/scripts/shared/`
- `telemetry_token.example.txt`
- `seo_monitor_hub_seed_and_audit.php`
- `normalize_games_html.py`, `normalize_seo_cluster_html.py`

### Мусор из `site/` overlay
- `api/sitemap/del/` — старые sitemap
- `templates/includes/menu/old/`, `*.old`
- powerball: `files/chickenroad.apk`, `seo_structured-chickenroad-preset.json`
- `plugins/lottery/plugin.php` — runtime loader от legacy `shared/`, не нужен при build-merge

## tools/ — актуальность

| Бренд | Файлов | Назначение |
|-------|--------|------------|
| chickenroad | ~71 | `build_chickenroad_*_cluster.py`, casino article locales/overrides — **актуально** для доработки контента Chicken Road |
| aviator-log-in | ~111 | `jackpot_24_*`, `fansport_25_*` — **актуально** для Aviator guides |
| powerballjackpot | ~14 | `apply_powerball_*`, `build_powerball_home_seo_cluster.py` — **актуально** для PowerBall |

Старые `chickenroad_*` скрипты в powerball **удалены** (были мёртвые копии).

## Замечания / TODO

1. **powerball `hestia/`** — шаблоны названы `caching-chickenroad.*` (наследие rebrand). Работают, но лучше переименовать под powerball.
2. **`tools/`** — одноразовые миграции; в git для истории, на сервер не деплоятся.
3. **`aviator-log-in/docs/source-json-archive/`** — архив JSON, можно вынести из git или в LFS при росте.
4. Пустые `plugins/`, `modules/`, `scripts/` не создаём заранее — только когда появится фича.

## Пустые папки

Не храним намеренно. `find ggcms -type d -empty -delete` после изменений.

## Восстановление (2026-07-02)

Исходники брендов временно оказались в `ggcms/core/brand/<brand>/` после незавершённой миграции. Восстановлено обратно в `ggcms/sites/<brand>/` (`public/` → `site/`). Слот `ggcms/core/brand/` снова пустой (только `.gitkeep`).

Проверка против legacy `/home/lenovo/bin/<brand>/`:
- overlay `sites/<brand>/site/` совпадает с build-overlay на 100%
- build/site ≈ legacy/site минус мусор (`debug/`, `!tools/*.csv`, `api/sitemap/del/`)
- изменённые файлы в build vs legacy — ожидаемые улучшения ядра (`site_brand.php`, `site_seo.php`, `brand.profile.php`)
- powerball `tools/`: 14 файлов (очищены от 68 дубликатов chickenroad; legacy имел 1260 в корневом `tools/`)
- общие docs: `ggcms/docs/AGENT_TELEMETRY.md`, `SEO_CLUSTER_LOCALIZATION.md`
