/* OneSignal web push: load first; shares root scope with PWA (see OneSignal “combining service workers”). */
importScripts('https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js');

/* Aviator Log In — minimal PWA service worker (offline shell + asset cache). */
const STATIC_CACHE = 'aviator-static-v4';
const PRECACHE_URLS = ['/offline.html'];

self.addEventListener('install', function (event) {
	event.waitUntil(
		caches
			.open(STATIC_CACHE)
			.then(function (cache) {
				return cache.addAll(PRECACHE_URLS);
			})
			.then(function () {
				return self.skipWaiting();
			})
	);
});

self.addEventListener('activate', function (event) {
	event.waitUntil(
		caches
			.keys()
			.then(function (keys) {
				return Promise.all(
					keys
						.filter(function (key) {
							/* Only prune our own static caches — never delete OneSignal / third-party caches. */
							return key.indexOf('aviator-static-') === 0 && key !== STATIC_CACHE;
						})
						.map(function (key) {
							return caches.delete(key);
						})
				);
			})
			.then(function () {
				return self.clients.claim();
			})
	);
});

self.addEventListener('fetch', function (event) {
	var req = event.request;
	if (req.mode === 'navigate') {
		event.respondWith(
			fetch(req).catch(function () {
				return caches.match('/offline.html').then(function (r) {
					return r || new Response('Offline', { status: 503, statusText: 'Offline' });
				});
			})
		);
		return;
	}
	var url = req.url;
	if (url.indexOf(self.location.origin + '/assets/') === 0 && req.method === 'GET') {
		event.respondWith(
			caches.match(req).then(function (cached) {
				var net = fetch(req)
					.then(function (res) {
						if (res && res.ok) {
							var copy = res.clone();
							caches.open(STATIC_CACHE).then(function (cache) {
								cache.put(req, copy);
							});
						}
						return res;
					})
					.catch(function () {
						return cached;
					});
				return cached || net;
			})
		);
	}
});
