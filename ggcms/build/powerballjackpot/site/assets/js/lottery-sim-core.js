(function (global) {
  'use strict';

  function intVal(v, fallback) {
    var n = parseInt(v, 10);
    return isNaN(n) ? fallback : n;
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

  function bonusSlotCount(game) {
    var n = 0;
    (game.bonus || []).forEach(function (pool) {
      n += intVal(pool.count, 0);
    });
    return n;
  }

  function generateDraw(game) {
    var main = randomUnique(
      intVal(game.main.min, 1),
      intVal(game.main.max, 69),
      intVal(game.main.count, 5)
    );
    var bonus = [];
    (game.bonus || []).forEach(function (pool) {
      var picked = randomUnique(intVal(pool.min, 1), intVal(pool.max, 26), intVal(pool.count, 1));
      bonus = bonus.concat(picked);
    });
    return { main: main, bonus: bonus };
  }

  function countMainMatches(picks, winningMain) {
    var set = {};
    winningMain.forEach(function (n) {
      set[n] = true;
    });
    var c = 0;
    picks.forEach(function (n) {
      if (set[n]) {
        c++;
      }
    });
    return c;
  }

  function countBonusMatches(picks, winningBonus) {
    var matched = 0;
    var used = {};
    picks.forEach(function (n) {
      var idx = winningBonus.indexOf(n);
      if (idx !== -1 && !used[idx]) {
        used[idx] = true;
        matched++;
      }
    });
    return matched;
  }

  function lineToNums(line, game) {
    var nums = (line.main || []).slice();
    (game.bonus || []).forEach(function (pool) {
      nums = nums.concat((line.bonus && line.bonus[pool.id]) || []);
    });
    return nums;
  }

  function findPayoutTier(game, mainHits, bonusHits) {
    var payouts = game.payouts || [];
    var i;
    for (i = 0; i < payouts.length; i++) {
      var tier = payouts[i];
      if (intVal(tier.main, -1) === mainHits && intVal(tier.bonus, -1) === bonusHits) {
        return tier;
      }
    }
    return null;
  }

  function tierPrizeValue(game, tier) {
    if (!tier) {
      return 0;
    }
    if (tier.jackpot) {
      return intVal(game.jackpot_sim, 10000000);
    }
    return intVal(tier.val, 0);
  }

  function evaluateLine(line, draw, game) {
    var mainHits = countMainMatches(line.main || [], draw.main);
    var bonusHits = countBonusMatches(lineToNums(line, game).slice(intVal(game.main.count, 0)), draw.bonus);
    var tier = findPayoutTier(game, mainHits, bonusHits);
    return {
      mainHits: mainHits,
      bonusHits: bonusHits,
      tier: tier,
      prize: tierPrizeValue(game, tier),
      won: tier !== null && tierPrizeValue(game, tier) > 0,
    };
  }

  function formatMoney(symbol, amount) {
    var n = Number(amount) || 0;
    if (n >= 1000000) {
      return symbol + n.toLocaleString(undefined, { maximumFractionDigits: 0 });
    }
    return symbol + n.toFixed(2);
  }

  function formatJackpotDisplay(symbol, amount) {
    var n = Number(amount) || 0;
    if (n >= 1000000000) {
      return symbol + Math.round(n / 1000000000) + 'B';
    }
    if (n >= 1000000) {
      return symbol + Math.round(n / 1000000) + 'M';
    }
    return formatMoney(symbol, n);
  }

  global.PbjLotterySimCore = {
    intVal: intVal,
    randomUnique: randomUnique,
    bonusSlotCount: bonusSlotCount,
    generateDraw: generateDraw,
    countMainMatches: countMainMatches,
    countBonusMatches: countBonusMatches,
    lineToNums: lineToNums,
    findPayoutTier: findPayoutTier,
    tierPrizeValue: tierPrizeValue,
    evaluateLine: evaluateLine,
    formatMoney: formatMoney,
    formatJackpotDisplay: formatJackpotDisplay,
  };
})(typeof window !== 'undefined' ? window : this);
