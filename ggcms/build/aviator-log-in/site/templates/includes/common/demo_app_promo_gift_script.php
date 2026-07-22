<script>
(function () {
	var gift = document.getElementById('demoAppPromoGift');
	if (!gift) return;
	var raw = gift.getAttribute('data-promo-items');
	if (!raw) return;
	var items;
	try { items = JSON.parse(raw); } catch (e) { return; }
	if (!Array.isArray(items) || !items.length) return;
	var storageKey = gift.getAttribute('data-promo-seen-key') || 'promo_seen_v1';
	function readSeen() {
		try {
			var o = JSON.parse(localStorage.getItem(storageKey) || '{}');
			return (o && typeof o === 'object') ? o : {};
		} catch (e) {
			return {};
		}
	}
	function hasUnseen() {
		var seen = readSeen();
		return items.some(function (it) {
			return it && it.id && !seen[String(it.id)];
		});
	}
	function refreshDot() {
		if (hasUnseen()) {
			gift.classList.add('demo-app-promo-gift--unseen');
		} else {
			gift.classList.remove('demo-app-promo-gift--unseen');
		}
	}
	refreshDot();
	window.addEventListener('storage', function (ev) {
		if (ev.key === storageKey) refreshDot();
	});
})();
</script>
