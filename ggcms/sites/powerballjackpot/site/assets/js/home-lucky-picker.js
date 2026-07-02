(function () {
  'use strict';

  var STORAGE_PREFIX_HOME = 'pbj_lucky_';
  var STORAGE_PREFIX_SIM = 'pbj_sim_lucky_';
  var STORAGE_VERSION = 3;
  var homeI18n = {};

  function loadHomeI18n() {
    var el = document.getElementById('pbj-sim-i18n') || document.getElementById('pbj-home-i18n');
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

  function readConfig() {
    var el = document.getElementById('pbj-lucky-config');
    if (!el) {
      return null;
    }
    try {
      return JSON.parse(el.textContent || '{}');
    } catch (e) {
      return null;
    }
  }

  function intVal(v, fallback) {
    var n = parseInt(v, 10);
    return isNaN(n) ? fallback : n;
  }

  function pad2(n) {
    n = intVal(n, 0);
    return n < 10 ? '0' + n : String(n);
  }

  function randomUnique(min, max, count) {
    var pool = [];
    var i;
    for (i = min; i <= max; i++) {
      pool.push(i);
    }
    var picked = [];
    while (picked.length < count && pool.length) {
      var idx = Math.floor(Math.random() * pool.length);
      picked.push(pool.splice(idx, 1)[0]);
    }
    picked.sort(function (a, b) {
      return a - b;
    });
    return picked;
  }

  function emptyLine(game) {
    var bonus = {};
    (game.bonus || []).forEach(function (pool) {
      bonus[pool.id] = [];
    });
    return { main: [], bonus: bonus };
  }

  function emptyState(game, defaults) {
    var linesMax = intVal(game.lines_max, intVal(defaults && defaults.lines_max, 5));
    var lines = [];
    var i;
    for (i = 0; i < linesMax; i++) {
      lines.push(emptyLine(game));
    }
    return { v: STORAGE_VERSION, currentLine: 0, lines: lines };
  }

  function normalizeNums(arr, min, max) {
    var out = [];
    if (!Array.isArray(arr)) {
      return out;
    }
    arr.forEach(function (n) {
      n = intVal(n, -1);
      if (n >= min && n <= max && out.indexOf(n) < 0) {
        out.push(n);
      }
    });
    out.sort(function (a, b) {
      return a - b;
    });
    return out;
  }

  function normalizeLine(raw, game) {
    var line = emptyLine(game);
    if (!raw || typeof raw !== 'object') {
      return line;
    }
    line.main = normalizeNums(raw.main, game.main.min, game.main.max);
    if (line.main.length > game.main.count) {
      line.main = line.main.slice(0, game.main.count);
    }
    (game.bonus || []).forEach(function (pool) {
      var src = raw.bonus && raw.bonus[pool.id];
      if (!src && raw.bonus && typeof raw.bonus === 'object') {
        src = raw.bonus[pool.id];
      }
      line.bonus[pool.id] = normalizeNums(src, pool.min, pool.max);
      if (line.bonus[pool.id].length > pool.count) {
        line.bonus[pool.id] = line.bonus[pool.id].slice(0, pool.count);
      }
    });
    return line;
  }

  function normalizeState(raw, game, defaults) {
    var base = emptyState(game, defaults);
    if (!raw || !Array.isArray(raw.lines)) {
      return base;
    }
    var max = base.lines.length;
    var i;
    for (i = 0; i < max; i++) {
      base.lines[i] = normalizeLine(raw.lines[i], game);
    }
    base.currentLine = intVal(raw.currentLine, 0);
    if (base.currentLine < 0) {
      base.currentLine = 0;
    }
    if (base.currentLine >= max) {
      base.currentLine = max - 1;
    }
    base.v = STORAGE_VERSION;
    return base;
  }

  function lineIsComplete(line, game) {
    if (!line) {
      return false;
    }
    if (line.main.length !== intVal(game.main.count, 0)) {
      return false;
    }
    var pools = game.bonus || [];
    for (var i = 0; i < pools.length; i++) {
      var pool = pools[i];
      var picked = line.bonus[pool.id] || [];
      if (picked.length !== intVal(pool.count, 0)) {
        return false;
      }
    }
    return true;
  }

  function countCompleteLines(state, game) {
    var n = 0;
    state.lines.forEach(function (line) {
      if (lineIsComplete(line, game)) {
        n++;
      }
    });
    return n;
  }

  function firstIncompleteLine(state, game) {
    var i;
    for (i = 0; i < state.lines.length; i++) {
      if (!lineIsComplete(state.lines[i], game)) {
        return i;
      }
    }
    return state.lines.length - 1;
  }

  function formatMoney(symbol, amount) {
    return symbol + amount.toFixed(2);
  }

  function LuckyPicker(game, defaults, tabEl) {
    this.game = game;
    this.game.main.count = intVal(game.main.count, 5);
    this.game.main.min = intVal(game.main.min, 1);
    this.game.main.max = intVal(game.main.max, 69);
    (this.game.bonus || []).forEach(function (pool) {
      pool.count = intVal(pool.count, 1);
      pool.min = intVal(pool.min, 1);
      pool.max = intVal(pool.max, 26);
    });
    this.defaults = defaults || {};
    this.tabEl = tabEl;
    this.pickerEl = tabEl.querySelector('.pbj-lucky-picker');
    this.panelEl = tabEl.querySelector('.pbj-ticket-panel');
    this.lineHintEl = tabEl.querySelector('.pbj-line-hint-num');
    this.stageHintEl = tabEl.querySelector('.pbj-stage-hint');
    this.storageKey =
      (tabEl.closest('.pbj-lottery-sim') ? STORAGE_PREFIX_SIM : STORAGE_PREFIX_HOME) + game.id;
    this.state = this.loadState();
    this.bindEvents();
    this.syncAll();
  }

  LuckyPicker.prototype.loadState = function () {
    try {
      var raw = localStorage.getItem(this.storageKey);
      if (raw) {
        var parsed = JSON.parse(raw);
        if (parsed && Array.isArray(parsed.lines)) {
          return normalizeState(parsed, this.game, this.defaults);
        }
      }
    } catch (e) {
      /* ignore */
    }
    return emptyState(this.game, this.defaults);
  };

  LuckyPicker.prototype.saveState = function () {
    try {
      localStorage.setItem(this.storageKey, JSON.stringify(this.state));
    } catch (e) {
      /* ignore */
    }
  };

  LuckyPicker.prototype.currentLineData = function () {
    return this.state.lines[this.state.currentLine] || emptyLine(this.game);
  };

  LuckyPicker.prototype.canSelectLine = function (idx) {
    if (idx <= 0) {
      return true;
    }
    if (idx >= this.state.lines.length) {
      return false;
    }
    if (lineIsComplete(this.state.lines[idx - 1], this.game)) {
      return true;
    }
    return this.lineHasAnyPick(this.state.lines[idx]);
  };

  LuckyPicker.prototype.bindEvents = function () {
    var self = this;

    if (this.pickerEl) {
      this.pickerEl.addEventListener('click', function (ev) {
        var li = ev.target.closest('li[data-num]');
        if (!li || !self.pickerEl.contains(li)) {
          return;
        }
        var num = intVal(li.getAttribute('data-num'), -1);
        if (num < 0) {
          return;
        }
        if (li.closest('.pbj-bonus-numbers')) {
          var poolId = li.closest('.pbj-bonus-numbers').getAttribute('data-pbj-pool');
          self.toggleBonus(poolId, num);
        } else if (li.closest('.pbj-main-numbers')) {
          self.toggleMain(num, li);
        }
      });

      this.pickerEl.addEventListener('mousedown', function (ev) {
        var li = ev.target.closest('li[data-num]');
        if (li && self.pickerEl.contains(li)) {
          ev.preventDefault();
        }
      });

      var quickBtn = this.pickerEl.querySelector('.pbj-quick-pick');
      if (quickBtn) {
        quickBtn.addEventListener('click', function () {
          self.quickPickCurrentLine();
        });
      }

      var clearBtn = this.pickerEl.querySelector('.pbj-clear-all');
      if (clearBtn) {
        clearBtn.addEventListener('click', function () {
          self.clearAll();
        });
      }
    }

    if (this.panelEl) {
      this.panelEl.querySelectorAll('.pbj-ticket-line').forEach(function (lineEl) {
        lineEl.addEventListener('click', function () {
          var idx = intVal(lineEl.getAttribute('data-pbj-line'), -1);
          if (idx < 0) {
            return;
          }
          if (!self.canSelectLine(idx)) {
            return;
          }
          self.state.currentLine = idx;
          self.syncAll();
          self.saveState();
        });
      });
    }
  };

  LuckyPicker.prototype.mainIsFull = function (line) {
    return line.main.length >= this.game.main.count;
  };

  LuckyPicker.prototype.hasBonusPools = function () {
    return (this.game.bonus || []).length > 0;
  };

  LuckyPicker.prototype.focusBonusSection = function () {
    if (!this.pickerEl || !this.hasBonusPools()) {
      return;
    }
    var section = this.pickerEl.querySelector('.pbj-bonus-section');
    if (!section) {
      return;
    }
    section.classList.add('pbj-need-pick');
    if (typeof section.scrollIntoView === 'function') {
      section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    var self = this;
    window.setTimeout(function () {
      section.classList.remove('pbj-need-pick');
    }, 2500);
  };

  LuckyPicker.prototype.flashMainFullNotice = function (liEl) {
    if (!liEl) {
      return;
    }
    liEl.classList.add('pbj-main-rejected');
    window.setTimeout(function () {
      liEl.classList.remove('pbj-main-rejected');
    }, 600);
  };

  LuckyPicker.prototype.toggleMain = function (num, liEl) {
    var lineIdx = this.state.currentLine;
    var line = this.state.lines[lineIdx];
    if (!line) {
      return;
    }
    var max = this.game.main.count;
    var idx = line.main.indexOf(num);
    if (idx >= 0) {
      line.main.splice(idx, 1);
    } else if (line.main.length < max) {
      line.main.push(num);
      line.main.sort(function (a, b) {
        return a - b;
      });
      if (this.mainIsFull(line) && this.hasBonusPools()) {
        this.focusBonusSection();
      }
    } else {
      this.flashMainFullNotice(liEl);
      this.focusBonusSection();
      this.syncStageHint();
      return;
    }
    this.syncTicketLine(lineIdx);
    this.afterLineChange(false, lineIdx);
  };

  LuckyPicker.prototype.toggleBonus = function (poolId, num) {
    var lineIdx = this.state.currentLine;
    var line = this.state.lines[lineIdx];
    if (!line) {
      return;
    }
    var pool = (this.game.bonus || []).filter(function (p) {
      return p.id === poolId;
    })[0];
    if (!pool) {
      return;
    }
    if (!line.bonus[poolId]) {
      line.bonus[poolId] = [];
    }
    var picked = line.bonus[poolId];
    var idx = picked.indexOf(num);
    if (idx >= 0) {
      picked.splice(idx, 1);
    } else if (picked.length < pool.count) {
      picked.push(num);
      picked.sort(function (a, b) {
        return a - b;
      });
    }
    this.syncTicketLine(lineIdx);
    this.afterLineChange(false, lineIdx);
  };

  LuckyPicker.prototype.quickPickCurrentLine = function () {
    var lineIdx = this.state.currentLine;
    var line = this.state.lines[lineIdx];
    line.main = randomUnique(this.game.main.min, this.game.main.max, this.game.main.count);
    (this.game.bonus || []).forEach(function (pool) {
      line.bonus[pool.id] = randomUnique(pool.min, pool.max, pool.count);
    });
    this.syncTicketLine(lineIdx);
    this.afterLineChange(false, lineIdx);
  };

  LuckyPicker.prototype.clearAll = function () {
    this.state = emptyState(this.game, this.defaults);
    this.afterLineChange(true);
  };

  LuckyPicker.prototype.afterLineChange = function (skipAdvance, editedLineIdx) {
    var lineIdx = typeof editedLineIdx === 'number' ? editedLineIdx : this.state.currentLine;
    var line = this.state.lines[lineIdx];
    if (!skipAdvance && lineIsComplete(line, this.game)) {
      var next = lineIdx + 1;
      if (next < this.state.lines.length) {
        this.state.currentLine = next;
      }
    } else if (!lineIsComplete(line, this.game) && !this.lineHasAnyPick(line)) {
      this.state.currentLine = firstIncompleteLine(this.state, this.game);
    }
    this.syncAll();
    this.saveState();
  };

  LuckyPicker.prototype.syncAll = function () {
    this.syncLineHint();
    this.syncStageHint();
    this.syncPickerSelection();
    this.syncTicketPanel();
    this.syncLineHighlights();
    this.syncBuyLink();
  };

  LuckyPicker.prototype.syncLineHint = function () {
    if (!this.lineHintEl) {
      return;
    }
    this.lineHintEl.textContent = pad2(this.state.currentLine + 1);
  };

  LuckyPicker.prototype.syncStageHint = function () {
    if (!this.stageHintEl) {
      return;
    }
    var line = this.currentLineData();
    var mainCount = this.game.main.count;
    var picked = line.main.length;
    var bonusPools = this.game.bonus || [];

    if (lineIsComplete(line, this.game)) {
      this.stageHintEl.textContent = t('home_picker_stage_complete');
      this.pickerEl && this.pickerEl.classList.remove('pbj-main-complete');
      return;
    }

    if (this.mainIsFull(line) && bonusPools.length) {
      var bonusPicked = 0;
      bonusPools.forEach(function (pool) {
        bonusPicked += (line.bonus[pool.id] || []).length;
      });
      var bonusNeed = 0;
      bonusPools.forEach(function (pool) {
        bonusNeed += intVal(pool.count, 0);
      });
      var bonusLabelText = bonusPools.length && bonusPools[0].label
        ? bonusPools[0].label
        : t('home_picker_bonus_fallback');
      this.stageHintEl.textContent = fmt(
        t('home_picker_main_set'),
        picked,
        mainCount,
        bonusLabelText.toLowerCase(),
        bonusPicked,
        bonusNeed
      );
      if (this.pickerEl) {
        this.pickerEl.classList.add('pbj-main-complete');
      }
      return;
    }

    if (this.pickerEl) {
      this.pickerEl.classList.remove('pbj-main-complete');
    }
    this.stageHintEl.textContent = fmt(t('home_picker_selected_main'), picked, mainCount);
  };

  LuckyPicker.prototype.syncPickerSelection = function () {
    if (!this.pickerEl) {
      return;
    }
    var line = this.currentLineData();
    var pickerEl = this.pickerEl;
    var game = this.game;

    pickerEl.querySelectorAll('.pbj-main-numbers li[data-num]').forEach(function (li) {
      var num = intVal(li.getAttribute('data-num'), -1);
      li.classList.toggle('numActive', line.main.indexOf(num) >= 0);
    });

    (game.bonus || []).forEach(function (pool) {
      var ul = pickerEl.querySelector('.pbj-bonus-numbers[data-pbj-pool="' + pool.id + '"]');
      if (!ul) {
        return;
      }
      var picked = line.bonus[pool.id] || [];
      ul.querySelectorAll('li[data-num]').forEach(function (li) {
        var num = intVal(li.getAttribute('data-num'), -1);
        li.classList.toggle('ballActive', picked.indexOf(num) >= 0);
      });
    });
  };

  LuckyPicker.prototype.setSlotDisplay = function (slotEl, value, isBonus) {
    if (!slotEl) {
      return;
    }
    var span = slotEl.querySelector('span');
    if (!span) {
      return;
    }
    var hasValue = value != null && value !== '';
    span.textContent = hasValue ? pad2(value) : '--';
    slotEl.classList.toggle('pbj-has-value', hasValue);
    slotEl.classList.toggle('ballActive', isBonus);
    slotEl.classList.toggle('numActive', !isBonus);
  };

  LuckyPicker.prototype.syncTicketLine = function (lineIdx) {
    if (!this.panelEl) {
      return;
    }
    var line = this.state.lines[lineIdx];
    if (!line) {
      return;
    }
    var lineEl = this.panelEl.querySelector('.pbj-ticket-line[data-pbj-line="' + lineIdx + '"]');
    if (!lineEl) {
      return;
    }
    var mainCount = this.game.main.count;
    var m;
    for (m = 0; m < mainCount; m++) {
      this.setSlotDisplay(lineEl.querySelector('[data-pbj-slot="main-' + m + '"]'), line.main[m], false);
    }
    var bonusIdx = 0;
    (this.game.bonus || []).forEach(function (pool) {
      var picked = line.bonus[pool.id] || [];
      var c;
      for (c = 0; c < pool.count; c++) {
        this.setSlotDisplay(
          lineEl.querySelector('[data-pbj-slot="bonus-' + bonusIdx + '"]'),
          picked[c],
          true
        );
        bonusIdx++;
      }
    }, this);
  };

  LuckyPicker.prototype.syncTicketPanel = function () {
    if (!this.panelEl) {
      return;
    }
    var self = this;

    this.state.lines.forEach(function (line, lineIdx) {
      self.syncTicketLine(lineIdx);

      var lineEl = self.panelEl.querySelector('.pbj-ticket-line[data-pbj-line="' + lineIdx + '"]');
      if (!lineEl) {
        return;
      }

      var complete = lineIsComplete(line, self.game);
      var selectable = self.canSelectLine(lineIdx);
      lineEl.classList.toggle('pbj-line-complete', complete);
      lineEl.classList.toggle('disable-items', !selectable && !complete && lineIdx !== self.state.currentLine);
      lineEl.classList.toggle('pbj-line-locked', !selectable);
    });

    var completeCount = countCompleteLines(this.state, this.game);
    var price = parseFloat(this.panelEl.getAttribute('data-pbj-price') || '0');
    var symbol = this.panelEl.getAttribute('data-pbj-currency') || '$';
    var total = completeCount * price;

    var detail = this.panelEl.querySelector('.pbj-price-detail');
    if (detail) {
      var priceStr = formatMoney(symbol, price);
      if (completeCount === 0) {
        var pending = 0;
        this.state.lines.forEach(function (line) {
          if (self.mainIsFull(line) && !lineIsComplete(line, self.game)) {
            pending++;
          }
        });
        if (pending > 0) {
          var bonusWord = (self.game.bonus && self.game.bonus[0] && self.game.bonus[0].label) || t('home_picker_bonus_fallback');
          detail.textContent = fmt(t('home_ticket_price_pending'), pending, bonusWord, priceStr);
        } else {
          detail.textContent = fmt(t('home_ticket_price_lines_zero'), priceStr);
        }
      } else if (completeCount === 1) {
        detail.textContent = fmt(t('home_ticket_price_one'), priceStr);
      } else {
        detail.textContent = fmt(t('home_ticket_price_lines'), completeCount, priceStr);
      }
    }
    var totalEl = this.panelEl.querySelector('.pbj-price-total');
    if (totalEl) {
      totalEl.textContent = formatMoney(symbol, total);
    }
  };

  LuckyPicker.prototype.lineHasAnyPick = function (line) {
    if (!line) {
      return false;
    }
    if (line.main && line.main.length) {
      return true;
    }
    var pools = this.game.bonus || [];
    for (var i = 0; i < pools.length; i++) {
      if ((line.bonus[pools[i].id] || []).length) {
        return true;
      }
    }
    return false;
  };

  LuckyPicker.prototype.syncLineHighlights = function () {
    if (!this.panelEl) {
      return;
    }
    var self = this;
    this.panelEl.querySelectorAll('.pbj-ticket-line').forEach(function (lineEl) {
      var idx = intVal(lineEl.getAttribute('data-pbj-line'), -1);
      lineEl.classList.toggle('pbj-line-active', idx === self.state.currentLine);
    });
  };

  LuckyPicker.prototype.syncBuyLink = function () {
    if (!this.panelEl) {
      return;
    }
    var buy = this.panelEl.querySelector('.pbj-buy-ticket');
    if (!buy) {
      return;
    }
    var base = buy.getAttribute('data-pbj-base-url') || buy.getAttribute('href') || '#';
    var completeLines = this.state.lines.filter(
      function (line) {
        return lineIsComplete(line, this.game);
      }.bind(this)
    );
    if (!completeLines.length || base === '#') {
      buy.setAttribute('href', base);
      buy.setAttribute('aria-disabled', completeLines.length ? 'false' : 'true');
      return;
    }
    var parts = completeLines.map(function (line) {
      var nums = line.main.slice();
      (this.game.bonus || []).forEach(function (pool) {
        nums = nums.concat(line.bonus[pool.id] || []);
      });
      return nums.join('-');
    }, this);
    var sep = base.indexOf('?') >= 0 ? '&' : '?';
    buy.setAttribute('href', base + sep + 'game=' + encodeURIComponent(this.game.id) + '&lines=' + encodeURIComponent(parts.join('|')));
    buy.setAttribute('aria-disabled', 'false');
  };

  LuckyPicker.prototype.lineIsComplete = function (line) {
    return lineIsComplete(line, this.game);
  };

  function init() {
    loadHomeI18n();
    var cfg = readConfig();
    if (!cfg || !cfg.games || !cfg.games.length) {
      return;
    }
    var defaults = cfg.defaults || {};
    var map = {};
    cfg.games.forEach(function (game) {
      map[game.id] = game;
    });

    window.PbjLuckyPickerRegistry = { instances: {} };

    document
      .querySelectorAll('.pbj-home .pbj-lucky-tab, .pbj-lottery-sim .pbj-lucky-tab')
      .forEach(function (tabEl) {
        var gameId = tabEl.getAttribute('data-pbj-game');
        if (gameId && map[gameId]) {
          window.PbjLuckyPickerRegistry.instances[gameId] = new LuckyPicker(
            map[gameId],
            defaults,
            tabEl
          );
        }
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
