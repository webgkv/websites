(function () {
  'use strict';

  var Core = window.PbjLotterySimCore;
  if (!Core) {
    return;
  }

  var i18n = {};
  var cfg = null;
  var gameMap = {};
  var currentGameId = '';
  var pickers = {};
  var playing = false;
  var turbo = false;
  var drawTimer = null;
  var drawCount = 0;
  var played = 0;
  var won = 0;
  var lost = 0;
  var moneySpent = 0;
  var moneyWon = 0;
  var tierHits = {};
  var history = [];
  var NORMAL_MS = 900;
  var TURBO_BATCH = 800;

  function t(key) {
    return i18n[key] || '';
  }

  function fmt(template) {
    var args = Array.prototype.slice.call(arguments, 1);
    var n = 1;
    args.forEach(function (arg) {
      template = String(template).split('%' + n).join(String(arg));
      n++;
    });
    return template;
  }

  function loadJson(id) {
    var el = document.getElementById(id);
    if (!el) {
      return null;
    }
    try {
      return JSON.parse(el.textContent || '{}');
    } catch (e) {
      return null;
    }
  }

  function currentGame() {
    return gameMap[currentGameId] || null;
  }

  function symbolFor(game) {
    return (game && game.currency_symbol) || '$';
  }

  function padBall(n) {
    n = Core.intVal(n, 0);
    return n < 10 ? '0' + n : String(n);
  }

  function formatDate(iso) {
    if (!iso) {
      return '';
    }
    var d = new Date(iso);
    if (isNaN(d.getTime())) {
      return iso;
    }
    var dd = String(d.getDate()).padStart(2, '0');
    var mm = String(d.getMonth() + 1).padStart(2, '0');
    return dd + '/' + mm + '/' + d.getFullYear();
  }

  function tierLabel(tier, game) {
    if (!tier) {
      return '';
    }
    if (tier.jackpot) {
      return t('sim_tier_jackpot') || 'Jackpot!';
    }
    var m = Core.intVal(tier.main, 0);
    var b = Core.intVal(tier.bonus, 0);
    if (m > 0 && b > 0) {
      return fmt(t('sim_tier_main_bonus'), m, b);
    }
    if (m > 0) {
      return fmt(t('sim_tier_main_only'), m);
    }
    if (b > 0) {
      return fmt(t('sim_tier_bonus_only'), b);
    }
    return '';
  }

  function tierPrizeDisplay(game, tier) {
    if (!tier) {
      return '—';
    }
    if (tier.jackpot) {
      return Core.formatJackpotDisplay(symbolFor(game), Core.intVal(game.jackpot_sim, 0));
    }
    return Core.formatMoney(symbolFor(game), Core.intVal(tier.val, 0));
  }

  function getPicker(gameId) {
    return pickers[gameId] || null;
  }

  function lineIsComplete(line, game) {
    if (!line || !game) {
      return false;
    }
    if ((line.main || []).length !== Core.intVal(game.main.count, 0)) {
      return false;
    }
    var pools = game.bonus || [];
    var i;
    for (i = 0; i < pools.length; i++) {
      var pool = pools[i];
      var picked = (line.bonus && line.bonus[pool.id]) || [];
      if (picked.length !== Core.intVal(pool.count, 0)) {
        return false;
      }
    }
    return true;
  }

  function activeTickets(gameId) {
    var picker = getPicker(gameId);
    if (!picker || !picker.state) {
      return [];
    }
    var game = gameMap[gameId];
    return picker.state.lines.filter(function (line) {
      return lineIsComplete(line, game);
    });
  }

  function activeTicketCount() {
    return activeTickets(currentGameId).length;
  }

  function initTierHits(gameId) {
    tierHits[gameId] = {};
    (gameMap[gameId].payouts || []).forEach(function (tier, idx) {
      tierHits[gameId][idx] = 0;
    });
  }

  function switchGame(gameId) {
    if (!gameMap[gameId]) {
      return;
    }
    currentGameId = gameId;
    document.querySelectorAll('.pbj-lottery-sim .pbj-lucky-tab').forEach(function (tab) {
      var on = tab.getAttribute('data-pbj-game') === gameId;
      tab.classList.toggle('show', on);
      tab.classList.toggle('active', on);
      if (on) {
        tab.removeAttribute('hidden');
      } else {
        tab.setAttribute('hidden', 'hidden');
      }
    });
    renderPayoutTable();
    renderDrawBalls(null);
    updateRealDrawPanel();
    updateTimeTracker();
  }

  function renderPayoutTable() {
    var game = currentGame();
    var body = document.getElementById('pbj-sim-payout-body');
    if (!game || !body) {
      return;
    }
    if (!tierHits[currentGameId]) {
      initTierHits(currentGameId);
    }
    body.innerHTML = '';
    (game.payouts || []).forEach(function (tier, idx) {
      var tr = document.createElement('tr');
      var tdMatch = document.createElement('td');
      var tdPrize = document.createElement('td');
      var tdHits = document.createElement('td');
      tdMatch.textContent = tierLabel(tier, game);
      tdPrize.textContent = tierPrizeDisplay(game, tier);
      tdHits.textContent = String(tierHits[currentGameId][idx] || 0);
      tr.appendChild(tdMatch);
      tr.appendChild(tdPrize);
      tr.appendChild(tdHits);
      body.appendChild(tr);
    });
  }

  function renderHistory() {
    var body = document.getElementById('pbj-sim-history-body');
    if (!body) {
      return;
    }
    body.innerHTML = '';
    history.slice(0, 7).forEach(function (row) {
      var tr = document.createElement('tr');
      tr.innerHTML =
        '<td>' + row.gameNum + '</td><td>' + row.drawing + '</td><td>' + row.result + '</td>';
      body.appendChild(tr);
    });
  }

  function renderDrawBalls(draw, matchLine) {
    var ul = document.getElementById('pbj-sim-draw-balls');
    if (!ul) {
      return;
    }
    ul.innerHTML = '';
    if (!draw) {
      var game = currentGame();
      var mc = game ? Core.intVal(game.main.count, 5) : 5;
      var bc = game ? Core.bonusSlotCount(game) : 1;
      var i;
      for (i = 0; i < mc + bc; i++) {
        var li = document.createElement('li');
        li.innerHTML = '<span>-</span>';
        ul.appendChild(li);
      }
      return;
    }
    var matchMain = {};
    var matchBonus = {};
    if (matchLine) {
      (matchLine.main || []).forEach(function (n) {
        matchMain[n] = true;
      });
      var game = currentGame();
      var bonusNums = [];
      (game.bonus || []).forEach(function (pool) {
        bonusNums = bonusNums.concat((matchLine.bonus && matchLine.bonus[pool.id]) || []);
      });
      bonusNums.forEach(function (n) {
        matchBonus[n] = true;
      });
    }
    draw.main.forEach(function (n) {
      var li = document.createElement('li');
      if (matchMain[n]) {
        li.className = 'numActive';
      }
      li.innerHTML = '<span>' + padBall(n) + '</span>';
      ul.appendChild(li);
    });
    draw.bonus.forEach(function (n) {
      var li = document.createElement('li');
      li.className = 'ballActive' + (matchBonus[n] ? ' pbj-sim-match' : '');
      li.innerHTML = '<span>' + padBall(n) + '</span>';
      ul.appendChild(li);
    });
  }

  function updateStatsUI() {
    var game = currentGame();
    var sym = symbolFor(game);
    var el;
    el = document.getElementById('pbj-sim-played');
    if (el) {
      el.textContent = String(played);
    }
    el = document.getElementById('pbj-sim-won');
    if (el) {
      el.textContent = String(won);
    }
    el = document.getElementById('pbj-sim-lost');
    if (el) {
      el.textContent = String(lost);
    }
    el = document.getElementById('pbj-sim-spent');
    if (el) {
      el.textContent = Core.formatMoney(sym, moneySpent);
    }
    el = document.getElementById('pbj-sim-won-money');
    if (el) {
      el.textContent = Core.formatMoney(sym, moneyWon);
    }
    el = document.getElementById('pbj-sim-lost-money');
    if (el) {
      el.textContent = Core.formatMoney(sym, Math.max(0, moneySpent - moneyWon));
    }
    el = document.getElementById('pbj-sim-draw-id');
    if (el) {
      el.textContent = '#' + drawCount;
    }
  }

  function updateTimeTracker() {
    var el = document.getElementById('pbj-sim-time');
    var game = currentGame();
    if (!el || !game || played <= 0) {
      if (el) {
        el.textContent = '';
      }
      return;
    }
    var tickets = Math.max(1, activeTicketCount());
    var dpw = Core.intVal(game.draws_per_week, 2);
    var totalWeeks = played / dpw;
    var years = Math.floor(totalWeeks / 52);
    var remWeeks = totalWeeks % 52;
    var months = Math.floor(remWeeks / 4);
    var weeks = Math.floor(remWeeks % 4);
    var parts = [];
    if (years > 0) {
      parts.push(years + ' ' + (t('sim_time_years') || 'years'));
    }
    if (months > 0) {
      parts.push(months + ' ' + (t('sim_time_months') || 'months'));
    }
    if (weeks > 0 || !parts.length) {
      parts.push(weeks + ' ' + (t('sim_time_weeks') || 'weeks'));
    }
    var sym = symbolFor(game);
    el.textContent = fmt(
      t('sim_time_tracker'),
      tickets * dpw,
      parts.join(', '),
      sym + Math.round(moneySpent).toLocaleString()
    );
  }

  function updateRealDrawPanel() {
    var el = document.getElementById('pbj-sim-real-draw');
    var game = currentGame();
    if (!el || !game) {
      return;
    }
    var latest = game.latest || {};
    if (!latest.nums || !latest.nums.length) {
      el.textContent =
        (t('sim_real_draw_title') || 'Published draw:') + ' ' + (t('sim_real_draw_none') || '—');
      return;
    }
    var mainCount = Core.intVal(game.main.count, 5);
    var main = latest.nums.slice(0, mainCount);
    var bonus = latest.nums.slice(mainCount);
    var numsPart = main.join(', ') + (bonus.length ? ' + ' + bonus.join(', ') : '');
    var label = formatDate(latest.draw_date);
    if (label) {
      el.textContent = fmt(t('home_checker_latest_draw'), label, numsPart);
    } else {
      el.textContent = fmt(t('home_checker_latest_draw_no_date'), numsPart);
    }
  }

  function setPlayButtonState() {
    var btn = document.getElementById('pbj-sim-play');
    var turboBtn = document.getElementById('pbj-sim-turbo');
    if (btn) {
      btn.textContent = playing ? t('sim_stop') || 'Stop' : t('sim_play') || 'Play';
      btn.classList.toggle('active', playing && !turbo);
    }
    if (turboBtn) {
      turboBtn.classList.toggle('active', turbo);
    }
  }

  function lockPickers(lock) {
    document.querySelectorAll('.pbj-lottery-sim .pbj-lucky-picker button, .pbj-lottery-sim .pbj-lucky-picker li[data-num]').forEach(function (el) {
      if (el.tagName === 'BUTTON') {
        el.disabled = lock;
      }
    });
    document.querySelectorAll('.pbj-lottery-sim .pbj-sim-quick-all').forEach(function (btn) {
      btn.disabled = lock;
    });
  }

  function runSingleDraw() {
    var game = currentGame();
    if (!game) {
      return false;
    }
    var tickets = activeTickets(currentGameId);
    if (!tickets.length) {
      return false;
    }
    var draw = Core.generateDraw(game);
    drawCount += 1;
    played += 1;
    var cost = tickets.length * (parseFloat(game.price) || 0);
    moneySpent += cost;

    var bestLine = null;
    var bestPrize = 0;
    var resultText = t('sim_result_none') || 'No win';
    var anyWin = false;
    var jackpotHit = false;

    tickets.forEach(function (line) {
      var ev = Core.evaluateLine(line, draw, game);
      if (ev.tier) {
        var idx = (game.payouts || []).indexOf(ev.tier);
        if (idx >= 0) {
          if (!tierHits[currentGameId]) {
            initTierHits(currentGameId);
          }
          tierHits[currentGameId][idx] = (tierHits[currentGameId][idx] || 0) + 1;
        }
        var prize = Core.tierPrizeValue(game, ev.tier);
        if (prize > bestPrize) {
          bestPrize = prize;
          bestLine = line;
          if (ev.tier.jackpot) {
            resultText = t('sim_tier_jackpot') || 'Jackpot!';
            jackpotHit = true;
          } else if (prize > 0) {
            resultText = fmt(t('sim_result_win'), Core.formatMoney(symbolFor(game), prize));
          }
        }
        if (ev.tier.jackpot || prize > 0) {
          anyWin = true;
          moneyWon += prize;
        }
      }
    });

    if (anyWin) {
      won += 1;
    } else {
      lost += 1;
    }

    var drawing =
      draw.main.map(padBall).join(' ') + (draw.bonus.length ? ' + ' + draw.bonus.map(padBall).join(' ') : '');
    history.unshift({
      gameNum: '#' + drawCount,
      drawing: drawing,
      result: resultText,
    });
    if (history.length > 7) {
      history.length = 7;
    }

    renderDrawBalls(draw, bestLine);
    var resultEl = document.getElementById('pbj-sim-draw-result');
    if (resultEl) {
      resultEl.textContent = resultText;
      resultEl.classList.toggle('pbj-sim-win', anyWin);
    }

    if (jackpotHit) {
      showJackpotBanner(game, tickets.length);
    }

    updateStatsUI();
    renderPayoutTable();
    renderHistory();
    updateTimeTracker();
    return true;
  }

  function showJackpotBanner(game, ticketCount) {
    var box = document.getElementById('pbj-sim-jackpot');
    var text = document.getElementById('pbj-sim-jackpot-text');
    var stats = document.getElementById('pbj-sim-jackpot-stats');
    if (!box || !text) {
      return;
    }
    text.textContent = t('sim_jackpot_banner') || 'Jackpot!';
    if (stats) {
      var dpw = Core.intVal(game.draws_per_week, 2);
      var totalWeeks = played / dpw;
      var years = Math.floor(totalWeeks / 52);
      var rem = totalWeeks % 52;
      var months = Math.floor(rem / 4);
      var weeks = Math.floor(rem % 4);
      var parts = [];
      if (years) {
        parts.push(years + ' ' + (t('sim_time_years') || 'years'));
      }
      if (months) {
        parts.push(months + ' ' + (t('sim_time_months') || 'months'));
      }
      parts.push(weeks + ' ' + (t('sim_time_weeks') || 'weeks'));
      stats.textContent = fmt(
        t('sim_jackpot_after'),
        ticketCount * dpw,
        parts.join(', '),
        Core.formatMoney(symbolFor(game), moneySpent)
      );
    }
    box.hidden = false;
  }

  function stopGame() {
    playing = false;
    turbo = false;
    if (drawTimer) {
      clearInterval(drawTimer);
      drawTimer = null;
    }
    lockPickers(false);
    setPlayButtonState();
  }

  function startNormal() {
    if (!runSingleDraw()) {
      alert(t('sim_no_tickets') || 'Add at least one complete ticket.');
      return;
    }
    playing = true;
    turbo = false;
    lockPickers(true);
    setPlayButtonState();
    drawTimer = setInterval(function () {
      if (!runSingleDraw()) {
        stopGame();
      }
    }, NORMAL_MS);
  }

  function runTurboBatch() {
    var i;
    for (i = 0; i < TURBO_BATCH; i++) {
      if (!runSingleDraw()) {
        break;
      }
    }
  }

  function startTurbo() {
    if (!activeTickets(currentGameId).length) {
      alert(t('sim_no_tickets') || 'Add at least one complete ticket.');
      return;
    }
    playing = true;
    turbo = true;
    lockPickers(true);
    setPlayButtonState();
    function loop() {
      if (!playing || !turbo) {
        return;
      }
      runTurboBatch();
      requestAnimationFrame(loop);
    }
    requestAnimationFrame(loop);
  }

  function resetAll() {
    stopGame();
    drawCount = 0;
    played = won = lost = 0;
    moneySpent = moneyWon = 0;
    history = [];
    initTierHits(currentGameId);
    var resultEl = document.getElementById('pbj-sim-draw-result');
    if (resultEl) {
      resultEl.textContent = t('sim_draw_waiting') || 'Waiting...';
      resultEl.classList.remove('pbj-sim-win');
    }
    var jackpot = document.getElementById('pbj-sim-jackpot');
    if (jackpot) {
      jackpot.hidden = true;
    }
    renderDrawBalls(null);
    updateStatsUI();
    renderPayoutTable();
    renderHistory();
    updateTimeTracker();
  }

  function quickPickAll(gameId) {
    var picker = getPicker(gameId);
    if (!picker) {
      return;
    }
    var max = picker.state.lines.length;
    var i;
    for (i = 0; i < max; i++) {
      picker.state.currentLine = i;
      picker.quickPickCurrentLine();
    }
    picker.syncAll();
    picker.saveState();
  }

  function bindPickerHooks() {
    document.querySelectorAll('.pbj-lottery-sim .pbj-sim-quick-all').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var tab = btn.closest('.pbj-lucky-tab');
        var gid = tab ? tab.getAttribute('data-pbj-game') : currentGameId;
        quickPickAll(gid || currentGameId);
      });
    });
  }

  function registerPickers() {
    if (window.PbjLuckyPickerRegistry && window.PbjLuckyPickerRegistry.instances) {
      pickers = window.PbjLuckyPickerRegistry.instances;
    }
  }

  function init() {
    i18n = loadJson('pbj-sim-i18n') || loadJson('pbj-home-i18n') || {};
    cfg = loadJson('pbj-sim-config');
    if (!cfg || !cfg.games || !cfg.games.length) {
      return;
    }
    cfg.games.forEach(function (game) {
      gameMap[game.id] = game;
      initTierHits(game.id);
    });
    currentGameId = cfg.games[0].id;

    var select = document.getElementById('pbj-sim-game-select');
    if (select) {
      select.addEventListener('change', function () {
        stopGame();
        switchGame(select.value);
      });
    }

    var playBtn = document.getElementById('pbj-sim-play');
    if (playBtn) {
      playBtn.addEventListener('click', function () {
        if (playing && !turbo) {
          stopGame();
        } else if (!playing) {
          startNormal();
        }
      });
    }

    var turboBtn = document.getElementById('pbj-sim-turbo');
    if (turboBtn) {
      turboBtn.addEventListener('click', function () {
        if (turbo) {
          stopGame();
        } else {
          stopGame();
          startTurbo();
        }
      });
    }

    var resetBtn = document.getElementById('pbj-sim-reset');
    if (resetBtn) {
      resetBtn.addEventListener('click', resetAll);
    }

    window.addEventListener('keydown', function (e) {
      if (e.code === 'Space' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'SELECT') {
        e.preventDefault();
        if (playBtn) {
          playBtn.click();
        }
      }
    });

    bindPickerHooks();
    registerPickers();
    switchGame(currentGameId);
    renderPayoutTable();
    renderHistory();
    updateStatsUI();
    setPlayButtonState();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
