<?php
/**
 * OneSignal Web Push helpers (browser / PWA). Native Median shell uses the app plugin, not web SDK.
 */

/**
 * Explicit Median / GoNative WebView markers only (not generic "mobile").
 *
 * @see https://docs.median.co/docs/detecting-app-usage
 */
function site_median_native_webview_ua_regex() {
	return '/MedianAndroid|MedianIOS|Median\\/|gonative\\.io|GoNativeAndroid|GoNativeIOS/i';
}

/**
 * Register /sw.js as early as possible on iOS standalone PWA (before OneSignal.init).
 *
 * @return string HTML script block
 */
function site_onesignal_early_sw_script() {
	return <<<'HTML'
<script>
(function () {
  if (!('serviceWorker' in navigator)) return;
  var ua = navigator.userAgent || '';
  var ios = /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  if (!ios) return;
  var standalone = window.navigator.standalone === true
    || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
    || (window.matchMedia && window.matchMedia('(display-mode: fullscreen)').matches);
  if (!standalone) return;
  navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
})();
</script>
HTML;
}

/**
 * Shared client-side push flow (custom soft prompt → same subscribe path as OneSignal Slidedown Allow).
 *
 * @return string HTML script block
 */
function site_onesignal_push_flow_helpers_script() {
	return <<<'HTML'
<script>
(function () {
  window.siteOneSignalPushFlow = {
    LS_SUBSCRIBED: 'os_ios_push_subscribed',
    LS_AUTO_OFFERED: 'os_push_soft_offered',
    AUTO_PROMPT_DELAY_MS: 10000,

    isStandaloneShell: function () {
      if (window.navigator.standalone === true) return true;
      try {
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) return true;
        if (window.matchMedia && window.matchMedia('(display-mode: fullscreen)').matches) return true;
      } catch (e) { /* ignore */ }
      return false;
    },

    isIosDevice: function () {
      var ua = navigator.userAgent || '';
      if (/iPhone|iPad|iPod/i.test(ua)) return true;
      return navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
    },

    isMobileUa: function () {
      var ua = navigator.userAgent || '';
      return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    },

    isDesktopBrowser: function () {
      return !this.isMobileUa();
    },

    wasAutoOffered: function () {
      try { return localStorage.getItem(this.LS_AUTO_OFFERED) === '1'; } catch (e) { return false; }
    },

    markAutoOffered: function () {
      try { localStorage.setItem(this.LS_AUTO_OFFERED, '1'); } catch (e) { /* ignore */ }
    },

    nativePermission: function () {
      try {
        if (window.Notification && Notification.permission) {
          return Notification.permission;
        }
      } catch (e) { /* ignore */ }
      return 'default';
    },

    isPushSupported: async function (OneSignal) {
      try {
        return !!(OneSignal && OneSignal.Notifications && await OneSignal.Notifications.isPushSupported());
      } catch (e) {
        return false;
      }
    },

    isSubscribed: async function (OneSignal) {
      try {
        if (!OneSignal || !OneSignal.User || !OneSignal.User.PushSubscription) {
          return false;
        }
        var ps = OneSignal.User.PushSubscription;
        if (ps.optedIn === true) {
          return true;
        }
        if (ps.id) {
          return true;
        }
        if (ps.token) {
          return true;
        }
      } catch (e) {
        return false;
      }
      return false;
    },

    markSubscribed: function () {
      try { localStorage.setItem(this.LS_SUBSCRIBED, '1'); } catch (e) { /* ignore */ }
    },

    clearSubscribedFlag: function () {
      try { localStorage.removeItem(this.LS_SUBSCRIBED); } catch (e) { /* ignore */ }
    },

    _oneSignal: null,
    _warmupPromise: null,
    _inFlight: null,
    _autoTimer: null,
    _pendingSubscribeRequest: null,
    _pendingNativePermission: null,

    setReady: function (OneSignal) {
      this._oneSignal = OneSignal;
    },

    waitReady: function () {
      if (this._oneSignal) {
        return Promise.resolve(this._oneSignal);
      }
      var self = this;
      return new Promise(function (resolve) {
        self.schedulePostInit(function (OneSignal) {
          self._oneSignal = OneSignal;
          resolve(OneSignal);
        });
      });
    },

    warmup: function (OneSignal) {
      if (this._warmupPromise) {
        return this._warmupPromise;
      }
      this._oneSignal = OneSignal;
      this._warmupPromise = (async function () {
        if (!('serviceWorker' in navigator)) return;
        try {
          var reg = await navigator.serviceWorker.getRegistration('/');
          if (!reg || !reg.active) {
            reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
          }
          await navigator.serviceWorker.ready;
        } catch (e) { /* ignore */ }
      })();
      return this._warmupPromise;
    },

    /** Step 1: our localized soft prompt (replaces OneSignal Slidedown). */
    promptSoftSubscribe: async function () {
      if (typeof this.showCustomSoftPrompt === 'function') {
        return await this.showCustomSoftPrompt();
      }
      return false;
    },

    /**
     * Start browser permission in click handler (user gesture). Does not require OneSignal.
     * OneSignal registration runs afterward in runSubscribe().
     */
    beginSubscribeFromGesture: function () {
      if (this.nativePermission() === 'denied') {
        return;
      }
      if (this.nativePermission() === 'granted') {
        var OneSignal = this._oneSignal;
        if (OneSignal && OneSignal.User && OneSignal.User.PushSubscription
          && typeof OneSignal.User.PushSubscription.optIn === 'function') {
          try {
            this._pendingSubscribeRequest = OneSignal.User.PushSubscription.optIn();
          } catch (e) { /* ignore */ }
        }
        return;
      }
      try {
        if (typeof Notification !== 'undefined'
          && typeof Notification.requestPermission === 'function') {
          this._pendingNativePermission = Notification.requestPermission();
        }
      } catch (e) { /* ignore */ }
    },

    runSubscribe: async function (OneSignal) {
      OneSignal = OneSignal || this._oneSignal;
      if (!OneSignal) {
        OneSignal = await this.waitReady();
        this.setReady(OneSignal);
      }
      if (!OneSignal || !OneSignal.Notifications) {
        return false;
      }
      await this.warmup(OneSignal);

      if (this._pendingNativePermission) {
        try {
          await this._pendingNativePermission;
        } catch (e) { /* ignore */ }
        this._pendingNativePermission = null;
      }

      if (this._pendingSubscribeRequest) {
        try {
          await this._pendingSubscribeRequest;
        } catch (e) { /* ignore */ }
        this._pendingSubscribeRequest = null;
      }

      var granted = this.nativePermission() === 'granted'
        || OneSignal.Notifications.permission === true;

      if (!granted) {
        try {
          await OneSignal.Notifications.requestPermission();
        } catch (e) {
          return false;
        }
        granted = this.nativePermission() === 'granted'
          || OneSignal.Notifications.permission === true;
      }

      if (!granted) {
        return false;
      }

      // Permission granted — run OneSignal register chain (SW + token + backend), no second prompt.
      try {
        await OneSignal.Notifications.requestPermission();
      } catch (e) { /* ignore */ }

      if (!await this.isSubscribed(OneSignal)
        && OneSignal.User && OneSignal.User.PushSubscription
        && typeof OneSignal.User.PushSubscription.optIn === 'function') {
        try {
          await OneSignal.User.PushSubscription.optIn();
        } catch (e) { /* ignore */ }
      }

      return true;
    },

    waitForSubscription: async function (OneSignal) {
      OneSignal = OneSignal || this._oneSignal;
      var subscribed = false;
      for (var i = 0; i < 20; i++) {
        subscribed = await this.isSubscribed(OneSignal);
        if (subscribed) {
          break;
        }
        if (this.nativePermission() === 'denied') {
          break;
        }
        await new Promise(function (resolve) { setTimeout(resolve, 500); });
      }
      if (subscribed) {
        this.markSubscribed();
      } else {
        this.clearSubscribedFlag();
      }
      var reason = subscribed ? 'subscribed' : (this.nativePermission() === 'denied' ? 'denied' : 'no_subscription');
      return { ok: subscribed, reason: reason };
    },

    /**
     * Full subscribe flow: custom soft prompt → native Slidedown subscribe path.
     * @param {{force?:boolean, auto?:boolean}} opts force=true on bell click; auto=true marks LS after attempt
     */
    showSubscribeFlow: async function (OneSignal, opts) {
      opts = opts || {};
      OneSignal = OneSignal || this._oneSignal;
      if (!OneSignal) {
        OneSignal = await this.waitReady();
        this.setReady(OneSignal);
      }
      await this.warmup(OneSignal);

      if (await this.isSubscribed(OneSignal)) {
        return { ok: true, reason: 'subscribed' };
      }
      if (this.nativePermission() === 'denied') {
        return { ok: false, reason: 'denied' };
      }

      if (!OneSignal || !OneSignal.Notifications) {
        return { ok: false, reason: 'not_ready' };
      }

      var needsSoft = !OneSignal.Notifications.permission && this.nativePermission() !== 'granted';
      if (needsSoft) {
        var accepted = await this.promptSoftSubscribe();
        if (opts.auto) {
          this.markAutoOffered();
        }
        if (!accepted) {
          return { ok: false, reason: 'dismissed' };
        }
      } else if (opts.auto) {
        this.markAutoOffered();
      }

      await this.runSubscribe(OneSignal);
      return await this.waitForSubscription(OneSignal);
    },

    ensureSubscribed: async function (OneSignal, opts) {
      if (this._inFlight) {
        return this._inFlight;
      }
      var self = this;
      this._inFlight = this.showSubscribeFlow(OneSignal, opts);
      try {
        return await this._inFlight;
      } finally {
        this._inFlight = null;
      }
    },

    isAutoPromptContext: function () {
      if (this.isIosDevice() && this.isStandaloneShell()) return true;
      if (this.isDesktopBrowser()) return true;
      return false;
    },

    shouldOfferPush: async function (OneSignal) {
      if (!this.isAutoPromptContext()) return false;
      if (!(await this.isPushSupported(OneSignal))) return false;
      if (this.nativePermission() === 'denied') return false;
      if (await this.isSubscribed(OneSignal)) return false;
      if (this.wasAutoOffered()) return false;
      return true;
    },

    scheduleAutoPrompt: function (OneSignal) {
      var self = this;
      if (this._autoTimer) return;
      this._autoTimer = setTimeout(async function () {
        self._autoTimer = null;
        try {
          if (!(await self.shouldOfferPush(OneSignal))) return;
          await self.ensureSubscribed(OneSignal, { auto: true });
          if (typeof window.__demoAppPushSyncUI === 'function') {
            window.__demoAppPushSyncUI();
          }
        } catch (e) { /* ignore */ }
      }, this.AUTO_PROMPT_DELAY_MS);
    },

    schedulePostInit: function (fn) {
      window.OneSignalDeferred = window.OneSignalDeferred || [];
      OneSignalDeferred.push(fn);
    },

    scheduleAfterCountersLoad: function (fn) {
      var run = function () {
        setTimeout(function () {
          window.siteOneSignalPushFlow.schedulePostInit(fn);
        }, 50);
      };
      if (document.readyState === 'complete') {
        run();
      } else {
        window.addEventListener('load', run, { once: true });
      }
    }
  };
})();
</script>
HTML;
}

/**
 * Hide OneSignal SDK/dashboard slidedown — we use site_push_soft_prompt only.
 *
 * @return string HTML script + style block or empty in Median shell
 */
function site_onesignal_suppress_slidedown_script() {
	if (function_exists('site_is_median_native_webview') && site_is_median_native_webview()) {
		return '';
	}
	return <<<'HTML'
<style>
#onesignal-slidedown-container,
.onesignal-slidedown-container,
.onesignal-slidedown-dialog,
[class*="onesignal-slidedown"],
[id*="onesignal-slidedown"],
[id*="OneSignal-slidedown"] {
	display: none !important;
	visibility: hidden !important;
	pointer-events: none !important;
	opacity: 0 !important;
}
</style>
<script>
(function () {
  function hideOneSignalSlidedown() {
    var selectors = [
      '#onesignal-slidedown-container',
      '.onesignal-slidedown-container',
      '.onesignal-slidedown-dialog',
      '[class*="onesignal-slidedown"]',
      '[id*="onesignal-slidedown"]',
      '[id*="OneSignal-slidedown"]'
    ];
    selectors.forEach(function (sel) {
      try {
        document.querySelectorAll(sel).forEach(function (el) {
          el.style.setProperty('display', 'none', 'important');
          el.style.setProperty('visibility', 'hidden', 'important');
          el.style.setProperty('pointer-events', 'none', 'important');
        });
      } catch (e) { /* ignore */ }
    });
  }

  hideOneSignalSlidedown();
  try {
    new MutationObserver(hideOneSignalSlidedown).observe(document.documentElement, {
      childList: true,
      subtree: true
    });
  } catch (e) { /* ignore */ }

  window.OneSignalDeferred = window.OneSignalDeferred || [];
  OneSignalDeferred.push(function (OneSignal) {
    hideOneSignalSlidedown();
    try {
      if (OneSignal.Slidedown && typeof OneSignal.Slidedown.addEventListener === 'function') {
        OneSignal.Slidedown.addEventListener('slidedownShown', hideOneSignalSlidedown);
      }
    } catch (e) { /* ignore */ }
  });
})();
</script>
HTML;
}

/**
 * Auto-prompt after init. Language is auto-detected by OneSignal from device/browser on subscribe.
 *
 * @param array $abc Unused; kept for template call signature.
 * @return string HTML script block or empty string in Median shell
 */
function site_onesignal_web_ios_prompt_script($abc = array()) {
	if (function_exists('site_is_median_native_webview') && site_is_median_native_webview()) {
		return '';
	}
	return <<<'HTML'
<script>
(function () {
  if (!window.siteOneSignalPushFlow) return;
  window.siteOneSignalPushFlow.scheduleAfterCountersLoad(async function (OneSignal) {
    var flow = window.siteOneSignalPushFlow;
    flow.setReady(OneSignal);
    await flow.warmup(OneSignal);
    flow.scheduleAutoPrompt(OneSignal);
  });
})();
</script>
HTML;
}
