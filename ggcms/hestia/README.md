# Hestia templates (shared)

Общие шаблоны HestiaCP **Nginx (proxy)** для всех брендов GGCMS.

## Шаблон `caching-ggcms`

Один proxy-cache template на все домены. Домены не мешают друг другу: Hestia подставляет `%domain%`, `%docroot%`, `%user%` и т.д. при генерации конфига.

- Глобальная зона `cache` (как в `/etc/nginx/nginx.conf`)
- Admin/API/dynamic paths — **не кешируются**
- `/{lang}/demo/app/` — **отдельный location без кеша** (UA-dependent install shell)

Legacy-имена на сервере (`caching-chickenroad`, `caching-aviator`, `caching-ice-fish`) — дубликаты того же содержимого; новые домены вешаем на `caching-ggcms`.

## Install on server

```bash
sudo bash ggcms/hestia/install_caching_ggcms_templates.sh
```

Применить ко всем доменам (пример):

```bash
for d in chickenroad.run aviator-log-in.com ice-fish.run powerballjackpot.run; do
  sudo /usr/local/hestia/bin/v-change-web-domain-proxy-tpl dikodo "$d" caching-ggcms
done
sudo /usr/local/hestia/bin/v-rebuild-web-domains dikodo
sudo nginx -t && sudo systemctl reload nginx
```

Или через UI: Web → Domain → Edit → Proxy template → `caching-ggcms` → Save, затем rebuild.
