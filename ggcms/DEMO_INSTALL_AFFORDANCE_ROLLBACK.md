# DEMO_INSTALL_AFFORDANCE — quick rollback (all brands)

Install icon on `/demo/app/` + CTA nudge + iOS in-app Safari hint.

## Files touched

| Path | Role |
|------|------|
| `ggcms/core/functions/demo_app_install_affordance.php` | **core** PHP helpers |
| `ggcms/sites/*/site/index.php` | +1 `require_once` |
| `ggcms/sites/*/site/templates/includes/layouts/demo_app.php` | button, modal, JS |
| `ggcms/sites/*/site/templates/includes/layouts/_template_demo_app.php` | CSS |
| `ggcms/sites/*/site/files/languages/1/dictionary/common.php` | 3 i18n keys (EN) |
| `ggcms/sites/*/site/admin/modules/languages_json.php` | admin labels |

Search marker: `DEMO_INSTALL_AFFORDANCE`

## Rollback

```bash
cd /home/lenovo/bin/websites
git checkout HEAD -- \
  ggcms/core/functions/demo_app_install_affordance.php \
  ggcms/sites/chickenroad/site/index.php \
  ggcms/sites/chickenroad/site/templates/includes/layouts/demo_app.php \
  ggcms/sites/chickenroad/site/templates/includes/layouts/_template_demo_app.php \
  ggcms/sites/chickenroad/site/files/languages/1/dictionary/common.php \
  ggcms/sites/chickenroad/site/admin/modules/languages_json.php \
  ggcms/sites/aviator-log-in/site/index.php \
  ggcms/sites/aviator-log-in/site/templates/includes/layouts/demo_app.php \
  ggcms/sites/aviator-log-in/site/templates/includes/layouts/_template_demo_app.php \
  ggcms/sites/aviator-log-in/site/files/languages/1/dictionary/common.php \
  ggcms/sites/aviator-log-in/site/admin/modules/languages_json.php \
  ggcms/sites/ice-fish/site/index.php \
  ggcms/sites/ice-fish/site/templates/includes/layouts/demo_app.php \
  ggcms/sites/ice-fish/site/templates/includes/layouts/_template_demo_app.php \
  ggcms/sites/ice-fish/site/files/languages/1/dictionary/common.php \
  ggcms/sites/ice-fish/site/admin/modules/languages_json.php \
  ggcms/sites/powerballjackpot/site/index.php \
  ggcms/sites/powerballjackpot/site/templates/includes/layouts/demo_app.php \
  ggcms/sites/powerballjackpot/site/templates/includes/layouts/_template_demo_app.php \
  ggcms/sites/powerballjackpot/site/files/languages/1/dictionary/common.php \
  ggcms/sites/powerballjackpot/site/admin/modules/languages_json.php
rm -f ggcms/core/functions/demo_app_install_affordance.php ggcms/DEMO_INSTALL_AFFORDANCE_ROLLBACK.md \
      ggcms/sites/chickenroad/DEMO_INSTALL_AFFORDANCE_ROLLBACK.md
./deploy.sh --all --extras_plus
```
