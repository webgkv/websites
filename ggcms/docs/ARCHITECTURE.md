# GGCMS — архитектура монорепо

`ggcms/` — группа сайтов на одной PHP CMS. Рядом в `websites/` позже могут лежать другие группы; они не смешиваются с `ggcms`.

## Структура

```
websites/
├── HANDOFF.md
├── .gitignore
├── deploy.sh                        # единый деплой: --<brand> / --all
└── ggcms/
    ├── core/                         # единое ядро (admin, api, cron, jobs, functions, modules…)
    ├── sites/
    │   ├── aviator-log-in/
    │   │   ├── brand.php             # профиль бренда
    │   │   ├── site/                 # overlay: только то, что отличается от core
    │   │   ├── modules/              # брендовые модули / overrides модулей
    │   │   ├── plugins/              # крупные брендовые фичи
    │   │   ├── tools/                # контент/SEO-скрипты этого бренда
    │   │   ├── scripts/              # telemetry token, CLI
    │   │   ├── docs/
    │   │   ├── hestia/
    │   │   ├── deploy.ftp.example
    │   │   └── deploy.ftp.local      # gitignored
    │   ├── chickenroad/
    │   └── powerballjackpot/
    │       └── plugins/lottery/      # вертикаль только PowerBall
    ├── build/                        # gitignored — собранные standalone-сайты
    │   └── <brand>/site/
    ├── scripts/
    │   ├── build_site.sh
    │   └── deploy_site.sh        # воркер (вызывается из корневого deploy.sh)
    └── docs/
        ├── ARCHITECTURE.md
        └── LEGACY_CLEANUP.md
```

## Сборка (build)

```bash
./ggcms/scripts/build_site.sh aviator-log-in
```

Порядок наложения в `build/<brand>/site/`:

1. `ggcms/core/` — всё ядро
2. `ggcms/sites/<brand>/site/` — overlay (перекрывает core)
3. `ggcms/sites/<brand>/modules/` — в `site/modules/`
4. `ggcms/sites/<brand>/plugins/*/` — каждый плагин в корень `site/` (functions, cron, templates…)

На сервер уходит **только** `build/<brand>/site/` — полный изолированный `public_html`, без `ggcms/`, без `shared/`, без runtime-bootstrap.

## Что в core

- `admin/` (кроме `admin/config.php` — per-brand overlay)
- `api/`, `cron/`, `jobs/`
- универсальные `functions/` (`auth_func`, translation, telemetry, `site_brand` engine, `site_seo`, `site_section_urls`…)
- общие `modules/` (blog, guides, casinos, authors…)
- общие шаблоны, где нет брендовой разницы
- ассеты админки

## Что в sites/<brand>/site/ (overlay)

- `index.php`, `admin.php` — тонкие entrypoints (если отличаются)
- `templates/includes/layouts/index.php` — homepage
- брендовые layouts: `_template.php`, `demo*.php`, …
- `assets/` — CSS, images, JS фронта
- `config/config.php.example`
- `robots.txt`, `manifest.php`, `sw.js`
- уникальные `functions/` (legacy slugs, routing, median…)

## brand.php

Массив: `site_id`, `name`, `domain`, `legacy_canonical_hosts`, asset defaults, `section_slugs`, indexing flags, `plugins` (имена папок в `sites/<brand>/plugins/`).

## Деплой

```bash
./deploy.sh --chickenroad      # один сайт
./deploy.sh --all              # все сайты, по очереди
./deploy.sh --aviator-log-in --reset
```

Корневой `deploy.sh` — единый вход: разбирает `--<brand>` / `--all`, прокидывает флаги деплоя и вызывает воркер `ggcms/scripts/deploy_site.sh`. Каждый deploy: `build_site` → rsync/lftp `build/<brand>/site/` → сервер.
