<?php

/**
 * Custom localized push soft-prompt (replaces OneSignal Slidedown UI).
 */

if (!function_exists('site_push_soft_prompt_ui_strings')) {
	/**
	 * @return array{title:string,body:string,allow:string,cancel:string}
	 */
	function site_push_soft_prompt_ui_strings() {
		$brand = function_exists('site_brand_name') ? site_brand_name() : 'this site';
		$title = trim((string) i18n('common|demo_app_push_soft_title'));
		if ($title === '' || strpos($title, 'common|') === 0) {
			$title = 'Stay in the loop';
		}
		$body = trim((string) i18n('common|demo_app_push_soft_body'));
		if ($body === '' || strpos($body, 'common|') === 0) {
			$body = 'Get updates and alerts from ' . $brand . '. You can turn this off anytime in Settings.';
		} else {
			$body = str_replace('{brand}', $brand, $body);
		}
		$allow = trim((string) i18n('common|demo_app_push_soft_allow'));
		if ($allow === '' || strpos($allow, 'common|') === 0) {
			$allow = 'Allow notifications';
		}
		$cancel = trim((string) i18n('common|demo_app_push_soft_cancel'));
		if ($cancel === '' || strpos($cancel, 'common|') === 0) {
			$cancel = 'Not now';
		}

		return array(
			'title' => $title,
			'body' => $body,
			'allow' => $allow,
			'cancel' => $cancel,
		);
	}
}

if (!function_exists('site_push_soft_prompt_markup')) {
	function site_push_soft_prompt_markup() {
		$ui = site_push_soft_prompt_ui_strings();
		$title = htmlspecialchars((string) $ui['title'], ENT_QUOTES, 'UTF-8');
		$body = htmlspecialchars((string) $ui['body'], ENT_QUOTES, 'UTF-8');
		$allow = htmlspecialchars((string) $ui['allow'], ENT_QUOTES, 'UTF-8');
		$cancel = htmlspecialchars((string) $ui['cancel'], ENT_QUOTES, 'UTF-8');

		return '<div class="site-push-soft" id="sitePushSoftPrompt" hidden role="dialog" aria-modal="true" aria-labelledby="sitePushSoftPromptTitle">'
			. '<div class="site-push-soft__backdrop" data-push-soft-dismiss aria-hidden="true"></div>'
			. '<div class="site-push-soft__panel">'
			. '<p class="site-push-soft__title" id="sitePushSoftPromptTitle">' . $title . '</p>'
			. '<p class="site-push-soft__body">' . $body . '</p>'
			. '<div class="site-push-soft__actions">'
			. '<button type="button" class="site-push-soft__allow" data-push-soft-allow>' . $allow . '</button>'
			. '<button type="button" class="site-push-soft__cancel" data-push-soft-dismiss>' . $cancel . '</button>'
			. '</div>'
			. '</div>'
			. '</div>';
	}
}

if (!function_exists('site_push_soft_prompt_styles')) {
	function site_push_soft_prompt_styles() {
		return <<<'CSS'
<style>
.site-push-soft[hidden] { display: none !important; }
.site-push-soft {
	position: fixed;
	inset: 0;
	z-index: 10060;
	display: flex;
	align-items: flex-end;
	justify-content: center;
	padding: 16px;
	padding-bottom: max(16px, env(safe-area-inset-bottom));
	box-sizing: border-box;
}
.site-push-soft__backdrop {
	position: absolute;
	inset: 0;
	background: rgba(0, 0, 0, 0.55);
}
.site-push-soft__panel {
	position: relative;
	width: 100%;
	max-width: 420px;
	background: var(--push-soft-panel-bg, #ffffff);
	border: 1px solid var(--push-soft-panel-border, rgba(0, 0, 0, 0.08));
	border-radius: 16px 16px 12px 12px;
	padding: 20px 18px 16px;
	box-shadow: 0 16px 48px rgba(0, 0, 0, 0.35);
}
.site-push-soft__title {
	margin: 0 0 8px;
	font-size: 17px;
	font-weight: 700;
	line-height: 1.25;
	color: var(--push-soft-title-color, #111111);
}
.site-push-soft__body {
	margin: 0;
	font-size: 14px;
	line-height: 1.45;
	color: var(--push-soft-body-color, #444444);
}
.site-push-soft__actions {
	display: flex;
	flex-direction: column;
	gap: 8px;
	margin-top: 16px;
}
.site-push-soft__allow {
	display: block;
	width: 100%;
	padding: 12px 14px;
	border: 0;
	border-radius: 10px;
	background: var(--push-soft-accent, #ffae00);
	color: var(--push-soft-accent-text, #141a39);
	font-weight: 700;
	font-size: 15px;
	cursor: pointer;
}
.site-push-soft__allow:hover { filter: brightness(1.05); }
.site-push-soft__cancel {
	background: transparent;
	border: 0;
	color: var(--push-soft-cancel-color, #666666);
	font-size: 14px;
	padding: 6px;
	cursor: pointer;
}
@media (min-width: 768px) {
	.site-push-soft {
		align-items: center;
		padding: 24px;
	}
	.site-push-soft__panel {
		border-radius: 14px;
	}
}
</style>
CSS;
	}
}

if (!function_exists('site_push_soft_prompt_bind_script')) {
	function site_push_soft_prompt_bind_script() {
		return <<<'HTML'
<script>
(function () {
  if (!window.siteOneSignalPushFlow) return;

  window.siteOneSignalPushFlow.initCustomSoftPrompt = function () {
    var el = document.getElementById('sitePushSoftPrompt');
    if (!el || this._softPromptBound) return;
    this._softPromptBound = true;
    this._softPromptEl = el;

    var self = this;
    el.querySelectorAll('[data-push-soft-dismiss]').forEach(function (node) {
      node.addEventListener('click', function () {
        if (self._softPromptResolve) {
          var resolve = self._softPromptResolve;
          self._softPromptResolve = null;
          self._hideCustomSoftPrompt();
          resolve(false);
        } else {
          self._hideCustomSoftPrompt();
        }
      });
    });

    var allowBtn = el.querySelector('[data-push-soft-allow]');
    if (allowBtn) {
      allowBtn.addEventListener('click', function () {
        if (!self._softPromptResolve) return;
        var resolve = self._softPromptResolve;
        self._softPromptResolve = null;
        self.beginSubscribeFromGesture();
        self._hideCustomSoftPrompt();
        resolve(true);
      });
    }
  };

  window.siteOneSignalPushFlow._hideCustomSoftPrompt = function () {
    if (this._softPromptEl) {
      this._softPromptEl.hidden = true;
    }
  };

  window.siteOneSignalPushFlow.showCustomSoftPrompt = function () {
    var self = this;
    this.initCustomSoftPrompt();
    if (!this._softPromptEl) {
      return Promise.resolve(false);
    }
    if (this._softPromptResolve) {
      return Promise.resolve(false);
    }
    this._softPromptEl.hidden = false;
    return new Promise(function (resolve) {
      self._softPromptResolve = resolve;
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      window.siteOneSignalPushFlow.initCustomSoftPrompt();
    });
  } else {
    window.siteOneSignalPushFlow.initCustomSoftPrompt();
  }
})();
</script>
HTML;
	}
}

if (!function_exists('site_push_soft_prompt_render')) {
	/**
	 * Styles + markup + bind script (non-Median web only).
	 *
	 * @return string
	 */
	function site_push_soft_prompt_render() {
		if (function_exists('site_is_median_native_webview') && site_is_median_native_webview()) {
			return '';
		}
		return site_push_soft_prompt_styles()
			. site_push_soft_prompt_markup()
			. site_push_soft_prompt_bind_script();
	}
}
