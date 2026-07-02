(function () {
  'use strict';

  var homeI18n = {};

  function loadHomeI18n() {
    var el = document.getElementById('pbj-home-i18n');
    if (!el) {
      return;
    }
    try {
      var parsed = JSON.parse(el.textContent || '{}');
      if (parsed && typeof parsed === 'object') {
        homeI18n = parsed;
      }
    } catch (e) {
      /* ignore */
    }
  }

  function t(key) {
    return homeI18n[key] || '';
  }

  function fmt(template) {
    var args = Array.prototype.slice.call(arguments, 1);
    var n = 1;
    args.forEach(function (arg) {
      template = template.split('%' + n).join(String(arg));
      n++;
    });
    return template;
  }

  function parseConfig() {
    var el = document.getElementById('pbj-checker-config');
    if (!el) return null;
    try {
      return JSON.parse(el.textContent || '{}');
    } catch (e) {
      return null;
    }
  }

  function gameById(cfg, id) {
    if (!cfg || !cfg.games) return null;
    for (var i = 0; i < cfg.games.length; i++) {
      if (cfg.games[i].id === id) return cfg.games[i];
    }
    return null;
  }

  function bonusSlotCount(game) {
    var n = 0;
    (game.bonus || []).forEach(function (pool) {
      n += parseInt(pool.count, 10) || 0;
    });
    return n;
  }

  function formatDate(iso) {
    if (!iso) return '';
    var d = new Date(iso);
    if (isNaN(d.getTime())) return iso;
    var dd = String(d.getDate()).padStart(2, '0');
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    return dd + '/' + mm + '/' + d.getFullYear();
  }

  function splitWinning(game, nums) {
    var mainCount = parseInt(game.main.count, 10) || 0;
    return {
      main: nums.slice(0, mainCount),
      bonus: nums.slice(mainCount),
    };
  }

  function countMainMatches(picks, winningMain) {
    var set = {};
    winningMain.forEach(function (n) {
      set[n] = true;
    });
    var c = 0;
    picks.forEach(function (n) {
      if (set[n]) c++;
    });
    return c;
  }

  function countBonusMatches(picks, winningBonus) {
    var matched = 0;
    for (var i = 0; i < picks.length; i++) {
      if (winningBonus.indexOf(picks[i]) !== -1) matched++;
    }
    return matched;
  }

  function buildSlots(game, container) {
    container.innerHTML = '';
    var mainCount = parseInt(game.main.count, 10) || 5;
    var min = parseInt(game.main.min, 10) || 1;
    var max = parseInt(game.main.max, 10) || 69;
    var i;
    for (i = 0; i < mainCount; i++) {
      container.appendChild(slotInput('main', min, max));
    }
    (game.bonus || []).forEach(function (pool) {
      var bmin = parseInt(pool.min, 10) || 1;
      var bmax = parseInt(pool.max, 10) || 26;
      var bc = parseInt(pool.count, 10) || 1;
      var j;
      for (j = 0; j < bc; j++) {
        container.appendChild(slotInput('bonus', bmin, bmax));
      }
    });
  }

  function slotInput(kind, min, max) {
    var li = document.createElement('li');
    li.className = kind === 'bonus' ? 'pbj-checker-bonus-slot' : 'pbj-checker-main-slot';
    var input = document.createElement('input');
    input.type = 'number';
    input.min = String(min);
    input.max = String(max);
    input.className = 'pbj-checker-input';
    input.setAttribute('data-kind', kind);
    input.setAttribute(
      'aria-label',
      kind === 'bonus' ? t('home_checker_aria_bonus') : t('home_checker_aria_main')
    );
    li.appendChild(input);
    return li;
  }

  function focusInput(input) {
    if (!input || typeof input.focus !== 'function') {
      return;
    }
    try {
      input.focus({ preventScroll: true });
    } catch (e) {
      input.focus();
    }
  }

  function readPicks(game, container) {
    var inputs = container.querySelectorAll('.pbj-checker-input');
    var mainCount = parseInt(game.main.count, 10) || 0;
    var main = [];
    var bonus = [];
    var i;
    for (i = 0; i < inputs.length; i++) {
      var raw = inputs[i].value.trim();
      if (raw === '') {
        return { error: t('home_checker_err_all') };
      }
      var n = parseInt(raw, 10);
      if (isNaN(n)) {
        return { error: t('home_checker_err_invalid') };
      }
      var min = parseInt(inputs[i].min, 10);
      var max = parseInt(inputs[i].max, 10);
      if (n < min || n > max) {
        return { error: fmt(t('home_checker_err_range'), n, min, max) };
      }
      if (inputs[i].getAttribute('data-kind') === 'bonus') {
        bonus.push(n);
      } else {
        main.push(n);
      }
    }
    var seen = {};
    for (i = 0; i < main.length; i++) {
      if (seen[main[i]]) {
        return { error: t('home_checker_err_unique') };
      }
      seen[main[i]] = true;
    }
    return { main: main, bonus: bonus };
  }

  function updateDrawHint(game, drawEl) {
    if (!drawEl) return;
    var w = game.winning || {};
    if (!w.nums || !w.nums.length) {
      drawEl.textContent = t('home_checker_no_draw_loaded');
      return;
    }
    var parts = splitWinning(game, w.nums);
    var label = formatDate(w.draw_date);
    var numsPart = parts.main.join(', ') + (parts.bonus.length ? ' + ' + parts.bonus.join(', ') : '');
    if (label) {
      drawEl.textContent = fmt(t('home_checker_latest_draw'), label, numsPart);
    } else {
      drawEl.textContent = fmt(t('home_checker_latest_draw_no_date'), numsPart);
    }
  }

  function showResult(el, type, html) {
    if (!el) return;
    el.hidden = false;
    el.className = 'pbj-checker-result mt-3 alert alert-' + type;
    el.innerHTML = html;
  }

  function runCheck(game, container, resultEl) {
    var picks = readPicks(game, container);
    if (picks.error) {
      showResult(resultEl, 'warning', picks.error);
      var firstInput = container.querySelector('.pbj-checker-input');
      focusInput(firstInput);
      return;
    }
    var w = game.winning || {};
    if (!w.nums || !w.nums.length) {
      showResult(resultEl, 'warning', t('home_checker_no_draw_data'));
      return;
    }
    var win = splitWinning(game, w.nums);
    var mainHits = countMainMatches(picks.main, win.main);
    var bonusHits = countBonusMatches(picks.bonus, win.bonus);
    var bonusTotal = win.bonus.length;
    var dateLabel = formatDate(w.draw_date);
    var msg = '<strong>' + game.name + '</strong>';
    if (dateLabel) msg += fmt(t('home_checker_draw_on'), dateLabel);
    msg += '<br>';
    if (mainHits === 0 && bonusHits === 0) {
      msg += t('home_checker_no_matches');
    } else {
      msg += fmt(t('home_checker_matched_main'), mainHits);
      if (bonusTotal > 0) {
        msg += fmt(t('home_checker_matched_bonus'), bonusHits);
      }
      msg += t('home_checker_matched_end');
      if (mainHits === picks.main.length && bonusHits === bonusTotal && bonusTotal > 0) {
        msg += t('home_checker_jackpot_hint');
      } else if (mainHits === picks.main.length && bonusTotal === 0) {
        msg += t('home_checker_all_main_hint');
      }
    }
    var winStr = win.main.join(', ');
    if (win.bonus.length) winStr += ' + ' + win.bonus.join(', ');
    msg += '<br><span class="small text-muted">' + fmt(t('home_checker_winning_label'), winStr) + '</span>';
    var alertType = mainHits > 0 || bonusHits > 0 ? 'success' : 'secondary';
    showResult(resultEl, alertType, msg);
  }

  function init() {
    loadHomeI18n();
    var root = document.getElementById('pbj-ticket-checker');
    if (!root) return;
    var cfg = parseConfig();
    var select = document.getElementById('pbj-checker-lottery');
    var slots = document.getElementById('pbj-checker-slots');
    var drawEl = document.getElementById('pbj-checker-draw');
    var submit = document.getElementById('pbj-checker-submit');
    var resultEl = document.getElementById('pbj-checker-result');
    if (!cfg || !select || !slots) return;

    function loadGame() {
      var game = gameById(cfg, select.value);
      if (!game) return;
      buildSlots(game, slots);
      updateDrawHint(game, drawEl);
      if (resultEl) resultEl.hidden = true;
    }

    select.addEventListener('change', loadGame);
    if (submit) {
      submit.addEventListener('click', function () {
        var game = gameById(cfg, select.value);
        if (game) runCheck(game, slots, resultEl);
      });
    }
    loadGame();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
