# Legacy cleanup — выполнено 2026-07-02

Миграция на `ggcms/` завершена. Удалено:

- `chickenroad/`, `aviator-log-in/`, `powerballjackpot/` (корневые legacy-папки с полными копиями site/)
- `shared/` (промежуточный assemble-слой)
- `bootstrap_core.php`, `core-overrides.manifest`, `assemble_site.sh`, `gen_site_gitignore.py`

Текущая модель: **один `ggcms/core/` + overlay per brand + build → deploy**.
