(function ($) {
  'use strict';

  function initCarousels() {
    var $root = $('.pbj-home');
    if (!$root.length || typeof $.fn.slick !== 'function') {
      return;
    }

    var $lottery = $root.find('.lottery-carousel');
    if ($lottery.length && !$lottery.hasClass('slick-initialized')) {
      $lottery.slick({
        slidesToShow: 4,
        slidesToScroll: 1,
        arrows: true,
        dots: false,
        infinite: true,
        prevArrow: '<button type="button" class="slick-prev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>',
        nextArrow: '<button type="button" class="slick-next" aria-label="Next"><i class="fas fa-chevron-right"></i></button>',
        responsive: [
          { breakpoint: 1200, settings: { slidesToShow: 3 } },
          { breakpoint: 992, settings: { slidesToShow: 2 } },
          { breakpoint: 576, settings: { slidesToShow: 1 } },
        ],
      });
    }

    var $testimonials = $root.find('.testimonial-carousel');
    if ($testimonials.length && !$testimonials.hasClass('slick-initialized')) {
      $testimonials.slick({
        slidesToShow: 2,
        slidesToScroll: 1,
        arrows: true,
        dots: false,
        infinite: true,
        prevArrow: '<button type="button" class="slick-prev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>',
        nextArrow: '<button type="button" class="slick-next" aria-label="Next"><i class="fas fa-chevron-right"></i></button>',
        responsive: [{ breakpoint: 768, settings: { slidesToShow: 1 } }],
      });
    }
  }

  function initLuckyTabs() {
    var $root = $('.pbj-home');
    var $select = $root.find('.tab-to-select');
    if (!$select.length) {
      return;
    }
    $select.on('change', function () {
      var target = $(this).val();
      if (!target) {
        return;
      }
      var tabBtn = document.querySelector('.pbj-home button[data-bs-target="#' + target + '"]');
      if (tabBtn && window.bootstrap && bootstrap.Tab) {
        bootstrap.Tab.getOrCreateInstance(tabBtn).show();
      }
    });
  }

  $(function () {
    initCarousels();
    initLuckyTabs();
  });
})(jQuery);
