/*
 * Global Page Transitions
 *
 * Implements a branded curtain transition for internal navigation.
 * Honors reduced-motion preferences automatically.
 */
(function (window, document) {
  'use strict';

  if (!window || !document) return;

  var curtain = document.getElementById('transition-curtain');
  var curtainGhost = document.getElementById('transition-curtain-ghost');
  var orb = document.getElementById('transition-orb');
  var hasGSAP = typeof window.gsap !== 'undefined';
  var isNavigating = false;
  var ORB_BASE_SIZE = 24;
  var NAV_FLAG_KEY = 'rd_transition_pending';

  if (!curtain) return;

  function reduceMotionEnabled() {
    var state = window.RDAnimations && window.RDAnimations.state;
    if (state && typeof state.reduceMotion === 'boolean') {
      return state.reduceMotion;
    }

    try {
      var mql = window.matchMedia('(prefers-reduced-motion: reduce)');
      return !!(mql && mql.matches);
    } catch (err) {
      return false;
    }
  }

  function isModifiedClick(event) {
    return !!(event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0);
  }

  function markTransitionPending() {
    try {
      window.sessionStorage.setItem(NAV_FLAG_KEY, '1');
    } catch (err) {
      // Ignore storage failures.
    }
  }

  function consumeTransitionPending() {
    try {
      var pending = window.sessionStorage.getItem(NAV_FLAG_KEY) === '1';
      window.sessionStorage.removeItem(NAV_FLAG_KEY);
      return pending;
    } catch (err) {
      return false;
    }
  }

  function isHistoryTraversal() {
    try {
      var entries = window.performance && window.performance.getEntriesByType
        ? window.performance.getEntriesByType('navigation')
        : [];
      if (entries && entries.length && entries[0] && entries[0].type === 'back_forward') {
        return true;
      }
    } catch (err) {
      // Ignore perf API errors.
    }
    return false;
  }

  function isDashboardPath(pathname) {
    return /\/(dashboard|admin|client|worker)(\/|\.php|$)/i.test(pathname || '');
  }

  function resolveNavigationElement(event) {
    if (!event || !event.target || !event.target.closest) return null;
    return event.target.closest('a[href], button[data-href], button[data-transition-href], [data-transition-href]');
  }

  function resolveNavigationHref(node) {
    if (!node) return '';
    if (node.tagName === 'A') return node.getAttribute('href') || '';
    return node.getAttribute('data-transition-href') || node.getAttribute('data-href') || '';
  }

  function isButtonLike(node) {
    if (!node) return false;
    if (node.tagName === 'BUTTON') return true;
    if (node.matches('[role="button"], [data-transition-button="true"], [data-magnetic]')) return true;
    if (node.classList.contains('btn') || node.classList.contains('btn-alt')) return true;
    if (node.tagName === 'A') {
      return true;
    }
    return false;
  }

  function isInternalNavigableHref(href, node) {
    if (!href || href === '#') return false;
    if (href.indexOf('javascript:') === 0) return false;
    if (href.indexOf('mailto:') === 0) return false;
    if (href.indexOf('tel:') === 0) return false;
    if (node && node.hasAttribute && node.hasAttribute('download')) return false;
    if (node && node.getAttribute && node.getAttribute('target') && node.getAttribute('target') !== '_self') return false;
    if (node && node.getAttribute && node.getAttribute('data-no-transition') === 'true') return false;

    var url;
    try {
      url = new URL(href, window.location.href);
    } catch (err) {
      return false;
    }

    if (url.origin !== window.location.origin) return false;

    var samePath = url.pathname === window.location.pathname;
    var sameSearch = url.search === window.location.search;
    if (samePath && sameSearch && url.hash) return false;

    return true;
  }

  function resolveOriginPoint(event, node) {
    if (event && typeof event.clientX === 'number' && typeof event.clientY === 'number' && (event.clientX !== 0 || event.clientY !== 0)) {
      return { x: event.clientX, y: event.clientY };
    }

    if (node && typeof node.getBoundingClientRect === 'function') {
      var rect = node.getBoundingClientRect();
      return {
        x: rect.left + rect.width / 2,
        y: rect.top + rect.height / 2
      };
    }

    return {
      x: window.innerWidth / 2,
      y: window.innerHeight / 2
    };
  }

  function computeOrbScale(x, y) {
    var corners = [
      { x: 0, y: 0 },
      { x: window.innerWidth, y: 0 },
      { x: 0, y: window.innerHeight },
      { x: window.innerWidth, y: window.innerHeight }
    ];

    var maxDistance = 0;
    for (var i = 0; i < corners.length; i += 1) {
      var dx = corners[i].x - x;
      var dy = corners[i].y - y;
      var distance = Math.sqrt((dx * dx) + (dy * dy));
      if (distance > maxDistance) {
        maxDistance = distance;
      }
    }

    return (maxDistance / (ORB_BASE_SIZE / 2)) + 0.25;
  }

  function resetOrbStyles() {
    if (!orb) return;
    if (!hasGSAP) {
      orb.style.opacity = '0';
      orb.style.transform = 'translate(-50%, -50%) scale(0.001)';
      return;
    }

    window.gsap.set(orb, {
      autoAlpha: 0,
      scale: 0.001,
      xPercent: -50,
      yPercent: -50,
      pointerEvents: 'none'
    });
  }

  function resetGhostStyles() {
    if (!curtainGhost) return;

    if (!hasGSAP) {
      curtainGhost.style.opacity = '0';
      curtainGhost.style.transform = 'scale(1)';
      curtainGhost.style.pointerEvents = 'none';
      return;
    }

    window.gsap.set(curtainGhost, {
      autoAlpha: 0,
      scale: 1,
      pointerEvents: 'none'
    });
  }

  function playDashboardOutTransition(origin, onDone) {
    if (reduceMotionEnabled() || !hasGSAP || !orb) {
      onDone();
      return;
    }

    var point = origin || { x: window.innerWidth / 2, y: window.innerHeight / 2 };
    var targetScale = computeOrbScale(point.x, point.y);

    window.gsap.killTweensOf(orb);
    window.gsap.set(orb, {
      left: point.x,
      top: point.y,
      xPercent: -50,
      yPercent: -50,
      scale: 0.001,
      autoAlpha: 1,
      pointerEvents: 'none'
    });

    window.gsap.to(orb, {
      scale: targetScale,
      duration: 0.56,
      ease: 'power3.inOut',
      onComplete: onDone
    });
  }

  function playOutTransition(onDone) {
    if (reduceMotionEnabled() || !hasGSAP) {
      onDone();
      return;
    }

    window.gsap.killTweensOf(curtain);
    window.gsap.set(curtain, {
      yPercent: -100,
      autoAlpha: 1,
      pointerEvents: 'auto'
    });

    window.gsap.to(curtain, {
      yPercent: 0,
      duration: 0.46,
      ease: 'power2.inOut',
      onComplete: onDone
    });
  }

  function playInTransition() {
    if (reduceMotionEnabled() || !hasGSAP) {
      curtain.style.transform = 'translateY(-100%)';
      curtain.style.opacity = '1';
      curtain.style.pointerEvents = 'none';
      resetGhostStyles();
      resetOrbStyles();
      return;
    }

    window.gsap.killTweensOf(curtain);
    resetOrbStyles();
    if (curtainGhost) {
      window.gsap.killTweensOf(curtainGhost);
    }
    window.gsap.set(curtain, {
      yPercent: 0,
      autoAlpha: 1,
      pointerEvents: 'none'
    });

    if (curtainGhost) {
      window.gsap.set(curtainGhost, {
        autoAlpha: 0,
        scale: 0.985,
        pointerEvents: 'none'
      });

      window.gsap.to(curtainGhost, {
        autoAlpha: 1,
        scale: 1,
        duration: 0.22,
        ease: 'power2.out'
      });
    }

    window.gsap.to(curtain, {
      yPercent: -100,
      duration: 0.6,
      ease: 'power3.out',
      delay: 0.08,
      onComplete: function () {
        resetGhostStyles();
      }
    });
  }

  function resetTransitionVisualState() {
    if (hasGSAP) {
      window.gsap.killTweensOf(curtain);
      window.gsap.set(curtain, {
        yPercent: -100,
        autoAlpha: 1,
        pointerEvents: 'none'
      });
      resetGhostStyles();
      resetOrbStyles();
      return;
    }

    curtain.style.transform = 'translateY(-100%)';
    curtain.style.opacity = '1';
    curtain.style.pointerEvents = 'none';
    if (curtainGhost) {
      curtainGhost.style.opacity = '0';
      curtainGhost.style.transform = 'scale(1)';
      curtainGhost.style.pointerEvents = 'none';
    }
    if (orb) {
      orb.style.opacity = '0';
      orb.style.transform = 'translate(-50%, -50%) scale(0.001)';
      orb.style.pointerEvents = 'none';
    }
  }

  function onLinkClick(event) {
    var node = resolveNavigationElement(event);
    if (!node) return;
    if (isNavigating) return;
    if (isModifiedClick(event)) return;

    var href = resolveNavigationHref(node);
    if (!isInternalNavigableHref(href, node)) return;

    event.preventDefault();
    isNavigating = true;
    markTransitionPending();

    var shouldUseDashboardRipple = isDashboardPath(window.location.pathname) && isButtonLike(node);

    if (shouldUseDashboardRipple) {
      var origin = resolveOriginPoint(event, node);
      playDashboardOutTransition(origin, function () {
        window.location.href = href;
      });
      return;
    }

    playOutTransition(function () {
      window.location.href = href;
    });
  }

  document.addEventListener('click', onLinkClick, true);

  var shouldAnimateIn = consumeTransitionPending() && !isHistoryTraversal();

  function runInitialTransitionWhenReady() {
    if (!shouldAnimateIn) {
      resetTransitionVisualState();
      return;
    }

    var startIn = function () {
      playInTransition();
    };

    if (document.readyState === 'complete') {
      startIn();
      return;
    }

    window.addEventListener('load', startIn, { once: true });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runInitialTransitionWhenReady, { once: true });
  } else {
    runInitialTransitionWhenReady();
  }

  window.addEventListener('pageshow', function (evt) {
    if (evt.persisted) {
      isNavigating = false;
      resetTransitionVisualState();
    }
  });
})(window, document);
