# Hestia templates for ice-fish.run

This folder contains custom HestiaCP **Nginx (proxy)** templates and an install script.

## What it does

- Adds a custom proxy caching template `caching-ice-fish` for **Nginx + Apache2** setups.
- Uses the **existing** global Nginx cache zone `cache` (as configured in `/etc/nginx/nginx.conf`).
- Ensures admin-like paths are **not cached**.
- Ensures **`/{lang}/demo/app/`** is **not cached** (dynamic mirror loader / iframe shell).

## Install on server

Copy this folder to the server and run:

```bash
sudo bash hestia/install_caching_icefish_templates.sh
```

Then apply the template to the domain (example):

```bash
sudo /usr/local/hestia/bin/v-change-web-domain-proxy-tpl dikodo ice-fish.run caching-ice-fish
sudo /usr/local/hestia/bin/v-rebuild-web-domains dikodo
sudo nginx -t && sudo systemctl reload nginx
```

If you prefer the UI: Web → Domain → Edit → Proxy template → `caching-ice-fish` → Save, then rebuild.
