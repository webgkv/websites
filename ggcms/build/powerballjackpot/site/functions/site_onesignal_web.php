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
 * Shared client-side push flow (iOS standalone PWA).
 *
 * Two-step UX: OneSignal Slidedown only → native iOS permission → optIn.
 *
 * @return string HTML script block
 */
function site_onesignal_push_flow_helpers_script() {
	return <<<'HTML'
<script>
(function () {
  window.siteOneSignalPushFlow = {
    LS_SUBSCRIBED: 'os_ios_push_subscribed',
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
        if (OneSignal.User.PushSubscription.optedIn === true) {
          return true;
        }
        var id = OneSignal.User.PushSubscription.id;
        return !!id;
      } catch (e) {
        return false;
      }
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

    hasSlidedownApi: function (OneSignal) {
      return !!(OneSignal && OneSignal.Slidedown && typeof OneSignal.Slidedown.promptPush === 'function');
    },

    /** Step 1a: OneSignal Slidedown (dashboard / promptOptions). Returns true if native permission granted. */
    trySlidedown: async function (OneSignal, force) {
      if (!OneSignal.Slidedown || typeof OneSignal.Slidedown.promptPush !== 'function') {
        return false;
      }
      var permBefore = !!OneSignal.Notifications.permission;
      try {
        await OneSignal.Slidedown.promptPush({ force: !!force });
      } catch (e) { /* ignore */ }
      return !permBefore && !!OneSignal.Notifications.permission;
    },

    /** Step 2: native iOS permission via OneSignal. */
    requestNativePermission: async function (OneSignal) {
      OneSignal = OneSignal || this._oneSignal;
      if (!OneSignal || !OneSignal.Notifications
        || typeof OneSignal.Notifications.requestPermission !== 'function') {
        return false;
      }
      if (this.nativePermission() === 'denied' || OneSignal.Notifications.permission === false) {
        return false;
      }
      if (!OneSignal.Notifications.permission) {
        await OneSignal.Notifications.requestPermission();
      }
      return !!OneSignal.Notifications.permission;
    },

    completeOptIn: async function (OneSignal) {
      OneSignal = OneSignal || this._oneSignal;
      if (!OneSignal || !OneSignal.Notifications || !OneSignal.Notifications.permission) {
        return { ok: false, reason: 'no_permission' };
      }
      var subscribed = await this.isSubscribed(OneSignal);
      if (!subscribed && OneSignal.User && OneSignal.User.PushSubscription
        && typeof OneSignal.User.PushSubscription.optIn === 'function') {
        await OneSignal.User.PushSubscription.optIn();
        await new Promise(function (resolve) { setTimeout(resolve, 400); });
        subscribed = await this.isSubscribed(OneSignal);
      }
      if (subscribed) {
        this.markSubscribed();
      } else {
        this.clearSubscribedFlag();
      }
      return { ok: subscribed, reason: subscribed ? 'subscribed' : 'no_subscription' };
    },

    /**
     * Full subscribe flow: OneSignal Slidedown → native → optIn (no custom fallback modal).
     * @param {{force?:boolean, skipSlidedown?:boolean}} opts force=true on bell click
     */
    showSubscribeFlow: async function (OneSignal, opts) {
      opts = opts || {};
      OneSignal = OneSignal || this._oneSignal;
      await this.warmup(OneSignal);

      if (await this.isSubscribed(OneSignal)) {
        return { ok: true, reason: 'subscribed' };
      }
      if (this.nativePermission() === 'denied') {
        return { ok: false, reason: 'denied' };
      }

      var slidedownApi = this.hasSlidedownApi(OneSignal);

      if (!opts.skipSlidedown && slidedownApi) {
        await this.trySlidedown(OneSignal, !!opts.force);
      } else if (!slidedownApi && opts.force) {
        await this.requestNativePermission(OneSignal);
      }

      if (!OneSignal.Notifications.permission) {
        return { ok: false, reason: slidedownApi || opts.force ? 'dismissed' : 'no_prompt' };
      }

      return await this.completeOptIn(OneSignal);
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

    shouldOfferPush: async function (OneSignal) {
      if (!this.isIosDevice() || !this.isStandaloneShell()) return false;
      if (!(await this.isPushSupported(OneSignal))) return false;
      if (this.nativePermission() === 'denied') return false;
      if (await this.isSubscribed(OneSignal)) return false;
      return true;
    },

    scheduleAutoPrompt: function (OneSignal) {
      var self = this;
      if (this._autoTimer) return;
      this._autoTimer = setTimeout(async function () {
        self._autoTimer = null;
        try {
          if (!(await self.shouldOfferPush(OneSignal))) return;
          await self.ensureSubscribed(OneSignal, { force: false });
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
 * Auto-prompt on iOS standalone PWA: SW warmup + delayed Slidedown/soft prompt after init.
 *
 * @return string HTML script block or empty string in Median shell
 */
function site_onesignal_web_ios_prompt_script() {
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
