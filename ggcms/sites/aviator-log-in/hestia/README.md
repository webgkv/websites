# Hestia templates for aviator-log-in.com

This folder contains custom HestiaCP **Nginx (proxy)** templates and an install script.

## What it does

- Adds a custom proxy caching template `caching-aviator` for **Nginx + Apache2** setups.
- Uses the **existing** global Nginx cache zone `cache` (as configured in `/etc/nginx/nginx.conf`).
- Ensures admin-like paths are **not cached**.
- Ensures **`/{lang}/demo/app/`** is **not cached** (dynamic mirror loader / iframe shell).

## Install on server

Copy this folder to the server and run:

```bash
sudo bash hestia/install_caching_aviator_templates.sh
```

Then apply the template to the domain (example):

```bash
sudo /usr/local/hestia/bin/v-change-web-domain-proxy-tpl dikodo aviator-log-in.com caching-aviator
sudo /usr/local/hestia/bin/v-rebuild-web-domains dikodo
sudo nginx -t && sudo systemctl reload nginx
```

If you prefer the UI: Web → Domain → Edit → Proxy template → `caching-aviator` → Save, then rebuild.

