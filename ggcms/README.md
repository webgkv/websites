# GGCMS — сайты на общей PHP CMS

Группа сайтов в монорепо `websites`. Рядом позже могут появиться другие группы (другие CMS) — они не смешиваются с `ggcms/`.

## Структура

```
websites/
├── HANDOFF.md
├── deploy.sh                  # единый деплой: --<brand> / --all
└── ggcms/
    ├── core/                  # единое ядро CMS (admin, api, cron, functions, modules…)
    ├── sites/
    │   ├── aviator-log-in/
    │   │   ├── brand.php
    │   │   ├── site/          # overlay (бренд, layouts, assets, config.example)
    │   │   ├── modules/       # брендовые модули
    │   │   ├── plugins/       # крупные фичи (lottery — только powerball)
    │   │   ├── tools/, scripts/, docs/, hestia/
    │   │   └── deploy.ftp.*
    │   ├── chickenroad/
    │   └── powerballjackpot/
    ├── build/                 # gitignored — собранные standalone-сайты
    └── scripts/
        ├── build_site.sh
        └── deploy_site.sh      # воркер (вызывается из ../../deploy.sh)
```

## Сборка

```bash
./ggcms/scripts/build_site.sh aviator-log-in
# → ggcms/build/aviator-log-in/site/  (полный public_html)
```

Слои: `core/` + `sites/<brand>/site/` + `modules/` + `plugins/*/` + `brand.php` → `config/brand.profile.php`

## Деплой

```bash
./deploy.sh --all                        # все сайты, по очереди
./deploy.sh --chickenroad                # один сайт
./deploy.sh --powerballjackpot --reset   # с флагами деплоя
```

`deploy.sh` сам собирает сайт и заливает его. Deploy: build → rsync `ggcms/build/<brand>/site/` → сервер. На проде нет зависимости от `ggcms/`.

Подробнее: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
