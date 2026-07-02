try {
  if (document.querySelector('.swiper-slider')) {
    new Swiper('.swiper-slider', {
      loop: true,
      slidesPerView: 1,
      spaceBetween: 24,
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      breakpoints: {
        768: { slidesPerView: 3, spaceBetween: 30 },
        1024: { slidesPerView: 4, spaceBetween: 40 },
        1280: { slidesPerView: 4, spaceBetween: 50 }
      }
    });
  }
} catch (e) { /* Swiper not loaded or element missing */ }

  // Mobile Responsive start
// Burger menu: inline onclick/ontouchend in HTML + JS handlers; tap always runs
(function () {
  if (!window._burgerDebug) window._burgerDebug = { inited: false, toggleClicks: 0, toggleTouches: 0 };
  var nav = null;
  var toggle = null;

  function getNav() {
    if (!nav) nav = document.getElementById('navbarNav') || document.querySelector('.navbarNav');
    return nav;
  }
  function getToggle() {
    if (!toggle) toggle = document.querySelector('.menu-toggle');
    return toggle;
  }

  function loadDeferredFlags(root) {
    if (!root) return;
    root.querySelectorAll('img.aviator-lang-flag--deferred[data-src]').forEach(function (img) {
      if (!img.getAttribute('src')) {
        img.setAttribute('src', img.getAttribute('data-src'));
      }
    });
  }

  function openClose(e) {
    // Avoid double-handling (inline + addEventListener)
    if (e && e._aviatorHandled) return;
    if (e) e._aviatorHandled = true;
    var n = getNav();
    if (!n) return;
    if (e && e.type === 'touchend') {
      if (window._burgerDebug) window._burgerDebug.toggleTouches++;
    } else {
      if (window._burgerDebug) window._burgerDebug.toggleClicks++;
    }
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    n.classList.toggle('active');
    var isOpen = n.classList.contains('active');
    if (isOpen) loadDeferredFlags(n);
    var tgl = getToggle();
    if (tgl) tgl.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    document.body.classList.toggle('menu-open', isOpen);
  }

  // Global hook for inline HTML handlers
  window.aviatorBurgerTap = function (e) {
    openClose(e || window.event);
  };

  function onNavClick(e) {
    var link = e.target && e.target.closest ? e.target.closest('a') : null;
    if (!link) return;
    if (link.classList.contains('dropdown-toggle') || link.getAttribute('data-bs-toggle') === 'dropdown') return;
    var n = getNav();
    if (n) {
      n.classList.remove('active');
      var tgl = getToggle();
      if (tgl) tgl.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('menu-open');
    }
  }

  function init() {
    var n = getNav();
    var t = getToggle();
    if (n && t) window._burgerDebug.inited = true;
    // Always attach JS handlers; openClose guards against double-call
    if (t) {
      t.addEventListener('click', function (e) { openClose(e || window.event); }, false);
      t.addEventListener('touchend', function (e) { openClose(e || window.event); }, { passive: false });
    }
    if (n) n.addEventListener('click', onNavClick, false);
  }

  document.addEventListener('DOMContentLoaded', init);
  if (document.readyState !== 'loading') init();
})();

var mybutton = document.getElementById("myBtn");
if (mybutton) {
  window.onscroll = function() {
    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
      mybutton.style.display = "flex";
    } else {
      mybutton.style.display = "none";
    }
  };
}
function topFunction() {
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
}

  // Mobile Responsive end


  // Add active class to the current button (highlight it)
var header = document.getElementById("header");
if (header) {
  var btns = header.getElementsByClassName("nav-link");
  for (var i = 0; i < btns.length; i++) {
    btns[i].addEventListener("click", function() {
      var current = document.getElementsByClassName("active");
      if (current.length) current[0].className = current[0].className.replace(" active", "");
      this.className += " active";
    });
  }
}
