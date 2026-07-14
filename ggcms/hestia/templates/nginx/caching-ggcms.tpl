#=========================================================================#
# Shared Hestia proxy cache template for all GGCMS brands                 #
# Based on Hestia default caching.tpl                                     #
#                                                                         #
# IMPORTANT: This is for Nginx (Proxy) mode (Nginx + Apache2).            #
# It relies on a global Nginx cache zone named `cache`.                   #
# Per-domain isolation comes from Hestia placeholders (%domain%, etc.).   #
#=========================================================================#

server {
	listen      %ip%:%proxy_port%;
	server_name %domain_idn% %alias_idn%;
	error_log   /var/log/%web_system%/domains/%domain%.error.log error;

	include %home%/%user%/conf/web/%domain%/nginx.forcessl.conf*;

	location ~ /\.(?!well-known\/|file) {
		deny all;
		return 404;
	}

	location ~* "^/[a-z]{2}(-[a-z]{2})?/demo/app/?$" {
		proxy_pass http://%ip%:%web_port%;

		proxy_cache off;
		proxy_no_cache 1;
		proxy_cache_bypass 1;

		add_header Cache-Control "private, no-store, no-cache, must-revalidate, max-age=0" always;
		add_header CDN-Cache-Control "no-store" always;
		add_header Cloudflare-CDN-Cache-Control "no-store" always;
		add_header X-Proxy-Cache "BYPASS" always;
	}

	location / {
		proxy_pass http://%ip%:%web_port%;

		# Do not let upstream sessions disable caching for guests
		proxy_ignore_headers "Set-Cookie" "Cache-Control" "Expires";
		proxy_hide_header "Set-Cookie";

		proxy_cache cache;
		proxy_cache_valid 200 5m;
		proxy_cache_valid 301 302 10m;
		proxy_cache_valid 404 10m;
		proxy_cache_bypass $no_cache $cookie_session $http_x_update;
		proxy_no_cache $no_cache;

		add_header X-Proxy-Cache $upstream_cache_status always;

		set $no_cache 0;

		# Never cache admin / dynamic endpoints
		if ($request_uri ~* "/admin\.php|/admin/|/administrator/|/manager/|/user/|/login|/logout|/demo/app|/api/telemetry_|/api/") {
			set $no_cache 1;
		}

		# Never cache if session/auth cookies present
		if ($http_cookie ~* "PHPSESSID|SESS|wordpress_logged_in|wordpress_no_cache") {
			set $no_cache 1;
		}

		location ~* ^.+\.(%proxy_extensions%)$ {
			try_files  $uri @fallback;

			root       %docroot%;
			access_log /var/log/%web_system%/domains/%domain%.log combined;
			access_log /var/log/%web_system%/domains/%domain%.bytes bytes;

			expires    max;

			proxy_cache off;
		}
	}

	location @fallback {
		proxy_pass http://%ip%:%web_port%;
	}

	location /error/ {
		alias %home%/%user%/web/%domain%/document_errors/;
	}

	include %home%/%user%/conf/web/%domain%/nginx.conf_*;
}
