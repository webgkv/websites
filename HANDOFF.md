# Handoff: монорепозиторий `websites`

Документ для передачи контекста по всем сайтам в `/home/lenovo/bin/websites/`.  
Обновлено: **2026-07-02**.

---

## 1. Назначение

Монорепозиторий для SEO-сайтов на общем PHP-стеке (**GGCMS** — группа в `ggcms/`).

| Бренд (`ggcms/sites/…`) | Домен | Назначение |
|-------------------------|-------|------------|
| `chickenroad` | https://chickenroad.run | Chicken Road |
| `aviator-log-in` | https://aviator-log-in.com | Aviator |
| `powerballjackpot` | https://powerballjackpot.run | PowerBall + lottery plugin |

**Git:** `git@github.com:webgkv/websites.git`

**Legacy git-репозитории** (история до монорепо): `/home/lenovo/bin/chickenroad/`, `aviator-log-in/`, `powerballjackpot/`.

---

## 2. Структура монорепо

```
/home/lenovo/bin/websites/
├── HANDOFF.md
├── deploy.sh                        # единый деплой: --<brand> / --all
└── ggcms/
    ├── core/                         # ядро CMS (одна копия)
    ├── sites/
    │   ├── chickenroad/
    │   │   ├── brand.php
    │   │   ├── site/                 # overlay (только отличия от core)
    │   │   ├── modules/, plugins/
    │   │   ├── tools/, scripts/, docs/, hestia/
    │   │   └── deploy.ftp.*
    │   ├── aviator-log-in/
    │   └── powerballjackpot/
    │       └── plugins/lottery/
    ├── build/                        # gitignored — полные сайты для деплоя
    └── scripts/
        ├── build_site.sh
        └── deploy_site.sh            # воркер (вызывается из deploy.sh)
```

### Сборка перед деплоем

```bash
./ggcms/scripts/build_site.sh chickenroad
# → ggcms/build/chickenroad/site/  (полный document root)
```

На сервер уходит **только** содержимое `build/<brand>/site/` — изолированный сайт без ссылок на монорепо.

### Что в `core/` vs overlay `sites/<brand>/site/`

| core | overlay (per brand) |
|------|---------------------|
| admin, api, cron, jobs | `index.php`, `admin.php` |
| translation, telemetry | layouts (`index.php` hero, `_template.php`) |
| общие functions, modules | `assets/`, `robots.txt`, `config.example` |
| | `brand.php` → baked as `config/brand.profile.php` |

---

## 3. Сервер и хостинг

| Параметр | Значение |
|----------|----------|
| Хост | `38.133.213.49` |
| SSH/SFTP порт | `20203` |
| Пользователь | `dikodo` |
| Панель | HestiaCP |
| Auth деплоя | SSH-ключ `~/.ssh/webgkv` (в `deploy.ftp.local`) |

### Document roots на сервере

| Проект | `REMOTE_PATH` |
|--------|---------------|
| chickenroad | `/home/dikodo/web/chickenroad.run/public_html` |
| aviator-log-in | `/home/dikodo/web/aviator-log-in.com/public_html` |
| powerballjackpot | `/home/dikodo/web/powerballjackpot.run/public_html` |

DNS: домены за **Cloudflare** (прокси). Origin — тот же сервер.

---

## 4. Локальная настройка (первый запуск)

### 4.1 Deploy secrets

В каждом бренде:

```bash
cd ggcms/sites/chickenroad
cp deploy.ftp.example deploy.ftp.local
# HOST, USER, SSH_KEY, REMOTE_PATH
```

`LOCAL_PATH` по умолчанию: `ggcms/build/<brand>/site/` (deploy сам вызывает build).

### 4.2 Site config (БД, ключи)

Шаблон: `ggcms/sites/<brand>/site/config/config.php.example`  
Прод: `config/config.php` на сервере (gitignored).

На сервере: `site/config/config.php` (gitignored).  
Шаблон: `site/config/config.php.example` (есть у chickenroad и powerballjackpot).

Локально для отладки — копия с сервера или example; **не коммитить**.

### 4.3 Telemetry token

Шаблон: `ggcms/sites/<brand>/scripts/telemetry_token.example.txt`  
Рабочий: `telemetry_token.local.txt` (gitignored).

Токен = Admin → Telemetry на соответствующем прод-домене.

Прод-примеры snapshot:

```
https://chickenroad.run/api/telemetry_snapshot?token=…&limit=25&translation_limit=150
https://aviator-log-in.com/api/telemetry_snapshot?token=…&limit=25&translation_limit=150
https://powerballjackpot.run/api/telemetry_snapshot?token=…&limit=25&translation_limit=150
```

Подробно: `ggcms/docs/AGENT_TELEMETRY.md`, `ggcms/sites/aviator-log-in/docs/backlog.md`.

---

## 5. Деплой

```bash
./deploy.sh --all                        # все сайты, по очереди
./deploy.sh --chickenroad                # один сайт
./deploy.sh --powerballjackpot --reset   # с флагами деплоя
```

Единый вход — корневой `deploy.sh`: разбирает `--<brand>` / `--all`, прокидывает флаги деплоя, вызывает воркер `ggcms/scripts/deploy_site.sh` (который делает build + upload).

Порядок: **build** (`core` + overlay + plugins) → **rsync** `ggcms/build/<brand>/site/` → сервер.

Флаги (прокидываются как есть): `--reset`, `--reset --transfer-all`, `--delete-remote`, `--no-delete-remote`.

**Исключения rsync:** `*.md`, `*.log`, `.git`, `files/media/`.

---

## 6. Git: монорепо vs legacy

### Монорепо `websites`

```bash
cd /home/lenovo/bin/websites
git remote -v   # origin → webgkv/websites.git
```

Рекомендуемый workflow (после первого коммита):

```bash
git add -A
git commit -m "…"
git push origin main
./deploy.sh --all   # или выборочно: ./deploy.sh --chickenroad
```

### Legacy-репозитории (история до монорепо)

| Проект | Remote |
|--------|--------|
| chickenroad | `git@github.com:webgkv/chickenroad.git` |
| aviator-log-in | `git@github.com:webgkv/aviator-log-in.git` |
| powerballjackpot | `git@github.com:webgkv/powerballjackpot.git` |

На момент копирования в монорепо HEAD:

| Проект | Commit | Последнее известное сообщение |
|--------|--------|-------------------------------|
| chickenroad | `ec5b50e` | `<noads>` / `<noinc>` content exclusions |
| aviator-log-in | `f9a3ef8` | то же |
| powerballjackpot | `762ef51` | то же |

**Важно:** пока не решено окончательно, какой remote — source of truth. До миграции workflow мог дублироваться (commit в legacy + копия в monorepo). Целевое состояние — **только `websites`**.

---

## 7. Общая архитектура (для всех трёх)

### Роутинг

- URL: `/{lang}/{section}/…` (например `/en/authors/jose-adinerado/`).
- `index.php` находит строку в `pages` (или `content_i18n`), подключает `modules/<module>.php`.
- Breadcrumbs строятся в `index.php` из дерева `pages`; модули добавляют хвост (например имя автора).

### Модули контента

`authors`, `blog`, `casinos`, `games`, `guides`, `pages`, `news` — стандартный набор.

### i18n

- Языки в БД + `files/languages/{id}/dictionary/`.
- Переводы контента: `content_i18n`, translation clusters, autopilot.
- Правило для агентов: суммы в копирайте — **USD** (см. `.cursor/rules/localization-currency-usd.mdc`).

### SEO

- Sitemap: `/api/sitemap/index_hub.xml`
- Structured data, canonical — `functions/site_seo.php`, admin SEO Structured.
- SEO Monitor + telemetry control для meta-патчей.

### Админка

`https://<domain>/admin.php` — контент, переводы, медиа, telemetry, SEO.

---

## 8. Отличия проектов

### chickenroad.run

- Бренд: **Chicken Road** (`site/functions/site_brand.php`).
- `robots.txt`: `Allow: /` (полная индексация).
- `.cursor/rules/`: telemetry, localization, **seo-cluster-html-hygiene**.
- Документация: `docs/AGENT_TELEMETRY.md`, `docs/SEO_CLUSTER_LOCALIZATION.md`.

### aviator-log-in.com

- Бренд: **Aviator** (конфиг / шаблоны; отдельный `site_brand_name` в config).
- Самый большой репозиторий (~90M в монорепо): много guides, casino articles, backlog.
- `backlog.md` — сводный backlog + телеметрия (1290+ строк).
- Доп. гайды: `ggcms/sites/aviator-log-in/docs/` (`LOCALIZATION_GUIDE.md`, `CONTENT_QUALITY_GUIDE.md`, …)
- Hestia template: `hestia/templates/nginx/caching-aviator.tpl`.

### powerballjackpot.run

- Бренд: **PowerBall Jackpot**.
- **Лотерейный симулятор**: `site/functions/site_lottery_simulator.php`, `assets/js/lottery-sim-*.js`, demo app layout.
- **Ограниченная индексация**: `robots.txt` — `Disallow: /` + явные `Allow` только для языковых главных и `/en/demo/app/`.
- Админ-badge «Indexing limited» (`admin_indexing_guard.php`).
- `site_seo.php` — rebrand-хосты с aviator/chickenroad → powerballjackpot.
- Hestia: `caching-chickenroad` / powerball-шаблоны в `hestia/`.

---

## 9. Телеметрия и переводы (кратко)

**Не гадать состояние прода по коду** — снимок API или paste от пользователя.

1. `GET /api/telemetry_snapshot` — кластеры, jobs, queue_health.
2. `POST /api/telemetry_control` — enqueue/run (нужен control enabled).
3. `GET /api/telemetry_page_seo` — DB vs live HTML meta.

Типовой порядок для «застрявшего» кластера:

1. `cluster_snapshot` для `entity` + `entity_id`.
2. Проверить `scheduled_at` у pending jobs.
3. `translate_pipeline` с `process_jobs: 1` (избегать 504).
4. При `blocked` — смотреть `locales_compact[].blockers`.

Команда «добивай» в Cursor → см. `.cursor/rules/telemetry-dobivay.mdc`.

---

## 10. Cursor / правила агентов

Правила: `ggcms/sites/<brand>/.cursor/rules/` (telemetry, localization, telemetry-dobivay, seo-cluster-html-hygiene для chickenroad).

Workspace: `/home/lenovo/bin/websites` или `ggcms/sites/<brand>/`.

---

## 11. Недавние правки (контекст)

| Дата | Что |
|------|-----|
| 2026-06-30 | Исправлены хлебные крошки авторов: убран дубликат `Authors` в `site/modules/authors.php` → путь `Home / Authors / {Name}` |
| 2026-07-02 | Создан монорепо `websites`, скопированы 3 проекта, `deploy_all.sh`, обновлены `LOCAL_PATH` |
| 2026-07-02 | Pull в legacy: `<noads>` / `<noinc>` — `content_exclude_tags.php`, правки `advertising_api.php`, `cta_inject.php` |

---

## 12. Чеклист: новый разработчик / агент

- [ ] Клонировать `git@github.com:webgkv/websites.git` → `/home/lenovo/bin/websites`
- [ ] Для каждого нужного проекта: `deploy.ftp.local` из example + SSH-ключ
- [ ] При работе с прод-API: `scripts/telemetry_token.local.txt` (не коммитить)
- [ ] Деплой: `./deploy.sh --all` или `./deploy.sh --<brand>`
- [ ] Перед commit: не добавлять `config.php`, `deploy.ftp.local`, токены, media
- [ ] PHP lint на изменённых файлах
- [ ] Для SEO/переводов: читать `docs/AGENT_TELEMETRY.md` или snapshot с прода

---

## 13. Добавление нового сайта в GGCMS

1. `ggcms/sites/<slug>/` — `brand.php`, `site/`, `modules/`, `plugins/`
2. `deploy.ftp.example` → `deploy.ftp.local`; добавить `<slug>` в массив `BRANDS` в `deploy.sh` и `DOMAINS` в `ggcms/scripts/deploy_site.sh`
3. Hestia vhost + Cloudflare DNS
4. Обновить `HANDOFF.md`

---

## 14. Контакты и ссылки

| Ресурс | URL |
|--------|-----|
| GitHub monorepo | https://github.com/webgkv/websites |
| chickenroad (legacy) | https://github.com/webgkv/chickenroad |
| aviator (legacy) | https://github.com/webgkv/aviator-log-in |
| powerball (legacy) | https://github.com/webgkv/powerballjackpot |

---

*При изменении структуры, деплоя или workflow — обновляйте этот файл в том же PR/коммите.*
