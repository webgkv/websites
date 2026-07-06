<?php
/**
 * Fullscreen game demo shell for /demo/app/ (iframe fills viewport under app chrome).
 */
if (!function_exists('game_demo_spribe_currency_param')) {
	game_demo_ensure_spribe_provider();
}
global $abc, $config;

$currency = game_demo_spribe_currency_param($abc, $config);
$lang = strtolower(game_demo_lang_param($abc));
$mirrorApiUrl = game_demo_mirror_config_value($config, 'api_url', 'https://fwu21pcmpk1m14q.gmngdoor.link/');
$mirrorGameId = game_demo_mirror_config_value($config, 'game_id', 'aviator_spribe');
$mirrorBankGroupId = game_demo_mirror_config_value($config, 'bank_group_id', '');
if ($mirrorBankGroupId === '') {
	$mirrorBankGroupId = 'GoldenBet_' . $currency;
}
	
$spribeFallbackUrl = 'https://demo.spribe.io/launch/aviator?currency=' . rawurlencode($currency) . '&lang=' . rawurlencode($lang);

// Check if user is an authorized admin
if (!function_exists('access') && defined('ROOT_DIR') && is_file(ROOT_DIR . 'functions/auth_func.php')) {
	require_once ROOT_DIR . 'functions/auth_func.php';
}
$isAdmin = (function_exists('access') && access('user admin'));
$isDebugAllowed = $isAdmin && !empty($_GET['debug_ip_check']);

$uniqueId = 'app_shell';
?>
<div class="main__frame main__frame--app-shell" style="background: #151b24; overflow: hidden;">
	<div class="main__frame-app-inner" style="position: relative; width: 100%; height: 100%;">
		<!-- Premium Loader -->
		<div id="loader_<?= $uniqueId ?>" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; background: radial-gradient(circle at center, #1b2430 0%, #0f141c 100%); z-index: 10; transition: opacity 0.6s ease; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;">
			<div style="position: relative; width: 80px; height: 80px; margin-bottom: 24px; animation: floatPlane_<?= $uniqueId ?> 3s ease-in-out infinite;">
				<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%; filter: drop-shadow(0 0 12px #e50539);">
					<path d="M12 28L48 10L36 34L12 28Z" fill="#e50539"/>
					<path d="M48 10L32 44L28 52L24 38L48 10Z" fill="#b30229"/>
					<path d="M12 28L24 38L48 10L12 28Z" fill="#ff2e5f"/>
				</svg>
				<div style="position: absolute; inset: -10px; border: 2px solid rgba(229,5,57,0.3); border-radius: 50%; animation: pulseRing_<?= $uniqueId ?> 1.8s cubic-bezier(0.215, 0.610, 0.355, 1) infinite;"></div>
			</div>
			<h3 style="color: #ffffff; font-size: 1.1rem; font-weight: 600; margin: 0 0 8px 0; letter-spacing: 0.5px; text-transform: uppercase;">Initializing Demo</h3>
			<p id="status_<?= $uniqueId ?>" style="color: #8a99ad; font-size: 0.85rem; margin: 0 0 20px 0;">Connecting to secure mirror network...</p>
			<div style="width: 200px; height: 4px; background: rgba(255,255,255,0.08); border-radius: 2px; overflow: hidden; position: relative;">
				<div id="bar_<?= $uniqueId ?>" style="position: absolute; left: 0; top: 0; height: 100%; width: 10%; background: linear-gradient(90deg, #ff2e5f, #e50539); border-radius: 2px; box-shadow: 0 0 8px #ff2e5f; transition: width 0.4s cubic-bezier(0.1, 0.8, 0.1, 1);"></div>
			</div>
		</div>

		<!-- Game Iframe -->
		<iframe id="iframe_<?= $uniqueId ?>" class="app-demo-iframe" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; opacity: 0; transition: opacity 0.8s ease;" title="<?= htmlspecialchars((function_exists('site_brand_name') ? site_brand_name() : 'Game') . ' Demo', ENT_QUOTES, 'UTF-8') ?>" allowfullscreen>Browser not compatible.</iframe>
	</div>
</div>

<style>
@keyframes floatPlane_<?= $uniqueId ?> {
	0%, 100% { transform: translateY(0) rotate(0deg); }
	50% { transform: translateY(-8px) rotate(-3deg); }
}
@keyframes pulseRing_<?= $uniqueId ?> {
	0% { transform: scale(0.7); opacity: 1; }
	80%, 100% { transform: scale(1.3); opacity: 0; }
}
</style>

<script>
(function() {
	var iframe = document.getElementById("iframe_<?= $uniqueId ?>");
	var loader = document.getElementById("loader_<?= $uniqueId ?>");
	var bar = document.getElementById("bar_<?= $uniqueId ?>");
	var status = document.getElementById("status_<?= $uniqueId ?>");
	
	var currency = <?= json_encode($currency) ?>;
	var lang = <?= json_encode($lang) ?>;
	var mirrorApiUrl = <?= json_encode($mirrorApiUrl) ?>;
	var mirrorGameId = <?= json_encode($mirrorGameId) ?>;
	var mirrorBankGroupId = <?= json_encode($mirrorBankGroupId) ?>;
	var spribeFallbackUrl = <?= json_encode($spribeFallbackUrl) ?>;
	var isDebugAllowed = <?= json_encode($isDebugAllowed) ?>;
	
	var isLoaded = false;
	
	function setProgress(pct, statusText) {
		if (bar) bar.style.width = pct + "%";
		if (status && statusText) status.textContent = statusText;
	}
	
	function loadGame(url, source) {
		if (isLoaded) return;
		isLoaded = true;
		setProgress(100, "Launching game...");
		iframe.src = url;
		
		if (isDebugAllowed) {
			iframe.setAttribute("data-game-source", source);
			if (source === "mirror") {
				console.log("%c🟢 [Aviator Demo] Successfully loaded from MIRROR aggregator!", "color: #00ff00; font-size: 14px; font-weight: bold; padding: 4px;");
			} else {
				console.log("%c🔴 [Aviator Demo] Aggregator failed or timed out. Loaded from OFFICIAL SPRIBE fallback!", "color: #ff3333; font-size: 14px; font-weight: bold; padding: 4px;");
			}
		}
		
		iframe.onload = function() {
			setTimeout(function() {
				loader.style.opacity = "0";
				iframe.style.opacity = "1";
				setTimeout(function() {
					loader.style.display = "none";
				}, 600);
			}, 500);
		};
	}
	
	setProgress(30, "Connecting to game server...");
	
	var controller = new AbortController();
	var timeoutId = setTimeout(function() {
		controller.abort();
	}, 2800);
	
	fetch(mirrorApiUrl, {
		method: "POST",
		headers: { "Content-Type": "application/json" },
		signal: controller.signal,
		body: JSON.stringify({
			jsonrpc: "2.0",
			method: "Session.CreateDemo",
			id: Date.now(),
			params: {
				BankGroupId: mirrorBankGroupId,
				GameId: mirrorGameId,
				StartBalance: 10000,
				Params: { language: lang },
				PlayerIp: ""
			}
		})
	})
	.then(function(res) {
		clearTimeout(timeoutId);
		setProgress(70, "Securing dynamic session...");
		return res.json();
	})
	.then(function(data) {
		if (data.result && data.result.Url) {
			loadGame(data.result.Url, "mirror");
		} else {
			if (isDebugAllowed) {
				console.warn("Mirror aggregator API error, using fallback", data.error || data);
			}
			setProgress(85, "Switching to backup game server...");
			loadGame(spribeFallbackUrl, "spribe");
		}
	})
	.catch(function(err) {
		clearTimeout(timeoutId);
		if (isDebugAllowed) {
			console.warn("Mirror aggregator connection failed, using fallback", err);
		}
		setProgress(85, "Switching to backup game server...");
		loadGame(spribeFallbackUrl, "spribe");
	});
})();
</script>
