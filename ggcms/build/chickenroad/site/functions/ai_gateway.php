<?php
/**
 * AI Gateway: test and call OpenRouter / Google Gemini / NVIDIA NIM.
 * Keys are stored in DB (ai_provider_keys) and managed via admin module.
 */

if (!defined('ROOT_DIR')) {
	define('ROOT_DIR', dirname(__DIR__) . '/');
}

function ai_gateway_test($provider, $api_key, $model = null) {
	$api_key = trim((string)$api_key);
	if ($api_key === '') {
		return array('ok' => false, 'message' => 'API key is empty', 'full_response' => array());
	}
	$provider = trim((string)$provider);
	if ($provider === 'openrouter') {
		return _ai_gateway_test_openrouter($api_key, $model);
	}
	if ($provider === 'google_gemini') {
		return _ai_gateway_test_gemini($api_key, $model);
	}
	if ($provider === 'nvidia') {
		return _ai_gateway_test_nvidia($api_key, $model);
	}
	return array('ok' => false, 'message' => 'Unknown provider: ' . $provider, 'full_response' => array());
}

function ai_gateway_chat($provider, $api_key, $model, $messages) {
	$api_key = trim((string)$api_key);
	if ($api_key === '' || !is_array($messages) || empty($messages)) {
		return array('ok' => false, 'reply_text' => '', 'message' => 'API key or messages empty', 'full_response' => array());
	}
	$provider = trim((string)$provider);
	if ($provider === 'openrouter') return _ai_gateway_chat_openrouter($api_key, $model, $messages);
	if ($provider === 'google_gemini') return _ai_gateway_chat_gemini($api_key, $model, $messages);
	if ($provider === 'nvidia') return _ai_gateway_chat_nvidia($api_key, $model, $messages);
	return array('ok' => false, 'reply_text' => '', 'message' => 'Unknown provider', 'full_response' => array());
}

function ai_gateway_default_model($provider) {
	$provider = trim((string)$provider);
	if ($provider === 'openrouter') return 'openai/gpt-4o-mini';
	if ($provider === 'google_gemini') return 'gemini-2.0-flash';
	if ($provider === 'nvidia') return 'meta/llama-3.1-8b-instruct';
	return '';
}

function _ai_gateway_test_openrouter($api_key, $model) {
	$url = 'https://openrouter.ai/api/v1/chat/completions';
	if (trim((string)$model) === '') $model = 'openai/gpt-4o-mini';
	$body = array(
		'model' => $model,
		'messages' => array(array('role' => 'user', 'content' => 'Reply with exactly: OK')),
		'max_tokens' => 10,
	);
	return _ai_gateway_curl_json($url, $api_key, $body, true);
}

function _ai_gateway_chat_openrouter($api_key, $model, $messages) {
	$url = 'https://openrouter.ai/api/v1/chat/completions';
	if (trim((string)$model) === '') $model = 'openai/gpt-4o-mini';
	$body = array('model' => $model, 'messages' => $messages, 'max_tokens' => 4096);
	$res = _ai_gateway_curl_json($url, $api_key, $body, true);
	if (!$res['ok']) return array('ok' => false, 'reply_text' => '', 'message' => $res['message'], 'full_response' => $res['full_response']);
	$decoded = $res['full_response']['decoded'];
	$text = isset($decoded['choices'][0]['message']['content']) ? trim((string)$decoded['choices'][0]['message']['content']) : '';
	return array('ok' => true, 'reply_text' => $text, 'message' => 'OK', 'full_response' => $res['full_response']);
}

function _ai_gateway_test_nvidia($api_key, $model) {
	$url = 'https://integrate.api.nvidia.com/v1/chat/completions';
	if (trim((string)$model) === '') $model = 'meta/llama-3.1-8b-instruct';
	$body = array(
		'model' => $model,
		'messages' => array(array('role' => 'user', 'content' => 'Reply with exactly: OK')),
		'max_tokens' => 10,
	);
	return _ai_gateway_curl_json($url, $api_key, $body, true);
}

function _ai_gateway_chat_nvidia($api_key, $model, $messages) {
	$url = 'https://integrate.api.nvidia.com/v1/chat/completions';
	if (trim((string)$model) === '') $model = 'meta/llama-3.1-8b-instruct';
	$body = array('model' => $model, 'messages' => $messages, 'max_tokens' => 4096);
	$res = _ai_gateway_curl_json($url, $api_key, $body, true);
	if (!$res['ok']) return array('ok' => false, 'reply_text' => '', 'message' => $res['message'], 'full_response' => $res['full_response']);
	$decoded = $res['full_response']['decoded'];
	$text = isset($decoded['choices'][0]['message']['content']) ? trim((string)$decoded['choices'][0]['message']['content']) : '';
	return array('ok' => true, 'reply_text' => $text, 'message' => 'OK', 'full_response' => $res['full_response']);
}

function _ai_gateway_gemini_model_id($model) {
	$model = trim((string)$model);
	if ($model === '') return 'gemini-2.0-flash';
	$deprecated = array('gemini-1.5-flash' => 'gemini-2.0-flash', 'gemini-1.5-pro' => 'gemini-2.0-flash');
	return isset($deprecated[$model]) ? $deprecated[$model] : $model;
}

function _ai_gateway_test_gemini($api_key, $model) {
	$model = _ai_gateway_gemini_model_id($model);
	$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($api_key);
	$body = array(
		'contents' => array(array('parts' => array(array('text' => 'Reply with exactly: OK')))),
		'generationConfig' => array('maxOutputTokens' => 10),
	);
	return _ai_gateway_curl_json($url, null, $body, false);
}

function _ai_gateway_chat_gemini($api_key, $model, $messages) {
	$model = _ai_gateway_gemini_model_id($model);
	$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($api_key);
	$parts = array();
	foreach ($messages as $m) {
		if (!is_array($m) || empty($m['content'])) continue;
		$parts[] = array('text' => (string)$m['content']);
	}
	if (empty($parts)) $parts[] = array('text' => 'OK');
	$body = array(
		'contents' => array(array('parts' => $parts)),
		'generationConfig' => array('maxOutputTokens' => 4096),
	);
	$res = _ai_gateway_curl_json($url, null, $body, false);
	if (!$res['ok']) return array('ok' => false, 'reply_text' => '', 'message' => $res['message'], 'full_response' => $res['full_response']);
	$decoded = $res['full_response']['decoded'];
	$text = '';
	if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
		$text = trim((string)$decoded['candidates'][0]['content']['parts'][0]['text']);
	}
	return array('ok' => true, 'reply_text' => $text, 'message' => 'OK', 'full_response' => $res['full_response']);
}

function _ai_gateway_curl_json($url, $api_key, $body, $bearer) {
	$ts0 = microtime(true);
	$ch = curl_init($url);
	$headers = array('Content-Type: application/json');
	if ($api_key !== null) {
		$headers[] = ($bearer ? 'Authorization: Bearer ' : '') . $api_key;
		if (!$bearer) {
			// Gemini uses key in URL, no auth header
			$headers = array('Content-Type: application/json');
		}
	}
	// Some hosts ignore CURLOPT_TIMEOUT under certain signal/transport conditions.
	// Add progress callback-based abort as a last resort hard timeout.
	$hard_timeout_ms = 60000; // slightly above CURLOPT_TIMEOUT_MS for safety
	$progress_fn = function() use ($ts0, $hard_timeout_ms) {
		$elapsed_ms = (int)round((microtime(true) - $ts0) * 1000);
		// Returning non-zero aborts transfer (CURLE_ABORTED_BY_CALLBACK).
		return ($elapsed_ms > $hard_timeout_ms) ? 1 : 0;
	};

	$opts = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => $headers,
		CURLOPT_POSTFIELDS => json_encode($body),
		// Keep hard time limits so background jobs don't get stuck in `running`.
		// NOTE: CURLOPT_TIMEOUT can be ignored in some environments without NOSIGNAL / ms variants.
		// Use multiple safeguards so the worker never hangs forever on a stuck socket.
		CURLOPT_NOSIGNAL => 1,
		CURLOPT_CONNECTTIMEOUT => 15,
		CURLOPT_TIMEOUT => 55,
		CURLOPT_CONNECTTIMEOUT_MS => 15000,
		CURLOPT_TIMEOUT_MS => 55000,
		// Abort if connection is "stalled" (no bytes) for too long.
		CURLOPT_LOW_SPEED_LIMIT => 100,   // bytes/sec
		CURLOPT_LOW_SPEED_TIME => 20,     // seconds
	);
	// NVIDIA NIM endpoint sometimes negotiates HTTP/3 (QUIC) and can "hang" with no callbacks.
	// Force a stable version (HTTP/1.1) for reliability.
	if (stripos((string)$url, 'integrate.api.nvidia.com') !== false && defined('CURL_HTTP_VERSION_1_1')) {
		$opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
	}
	// Enable progress callback abort (works even when normal timeouts are flaky).
	$opts[CURLOPT_NOPROGRESS] = false;
	if (defined('CURLOPT_XFERINFOFUNCTION')) {
		$opts[CURLOPT_XFERINFOFUNCTION] = $progress_fn;
	} else if (defined('CURLOPT_PROGRESSFUNCTION')) {
		$opts[CURLOPT_PROGRESSFUNCTION] = $progress_fn;
	}
	curl_setopt_array($ch, $opts);
	$raw = curl_exec($ch);
	$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$err = curl_error($ch);
	$errno = function_exists('curl_errno') ? (int)curl_errno($ch) : 0;
	$info = curl_getinfo($ch);
	curl_close($ch);
	$elapsed_ms = (int)round((microtime(true) - $ts0) * 1000);
	$decoded = @json_decode($raw, true);
	$full_response = array(
		'http_code' => $code,
		'raw_body' => $raw,
		'raw_len' => is_string($raw) ? strlen($raw) : null,
		'decoded' => $decoded,
		'curl_errno' => $errno ?: null,
		'curl_error' => $err !== '' ? $err : null,
		'elapsed_ms' => $elapsed_ms,
		'curl_info' => is_array($info) ? $info : null,
		'debug' => array(
			'file' => __FILE__,
			'file_mtime' => @filemtime(__FILE__) ?: null,
			'pid' => function_exists('getmypid') ? (int)getmypid() : null,
			'sapi' => php_sapi_name(),
		),
	);
	if (!function_exists('site_telemetry_ai_gateway_record')) {
		require_once ROOT_DIR . 'functions/site_telemetry.php';
	}
	if ($err !== '') {
		// Common: timeout / low speed / SSL read errors
		$msg = 'cURL error: ' . $err;
		if ($errno === 28) $msg = 'cURL timeout: ' . $err;
		site_telemetry_ai_gateway_record($url, $full_response, false);
		return array('ok' => false, 'message' => $msg, 'full_response' => $full_response);
	}
	if ($code < 200 || $code >= 300) {
		$msg = (is_array($decoded) && isset($decoded['error']['message'])) ? $decoded['error']['message'] : ('HTTP ' . $code);
		site_telemetry_ai_gateway_record($url, $full_response, false);
		return array('ok' => false, 'message' => $msg, 'full_response' => $full_response);
	}
	site_telemetry_ai_gateway_record($url, $full_response, true);
	return array('ok' => true, 'message' => 'OK', 'full_response' => $full_response);
}

