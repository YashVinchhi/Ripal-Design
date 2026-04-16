/*
 * Global GSAP Runtime
 *
 * Provides one shared animation runtime for all pages.
 * - Registers plugins once
 * - Applies global defaults
 * - Handles reduced-motion and responsive conditions
 * - Exposes safe lifecycle methods for dynamic DOM updates
 */
(function (window, document) {
  'use strict';

  if (!window || !document) return;

  var RD = window.RDAnimations || {};
  var hasGSAP = typeof window.gsap !== 'undefined';
  var hasScrollTrigger = typeof window.ScrollTrigger !== 'undefined';

  RD.state = RD.state || {
    initialized: false,
    contexts: {},
    reduceMotion: false,
    mm: null,
    defaults: null
  };

  function safeRegisterPlugins() {
    if (!hasGSAP) return;
    if (hasScrollTrigger) {
      try {
        window.gsap.registerPlugin(window.ScrollTrigger);
      } catch (err) {
        // No-op if already registered.
      }
    }
  }

  function buildDefaults() {
    return {
      duration: {
        instant: 0,
        fast: 0.24,
        normal: 0.48,
        slow: 0.8,
        cinematic: 1.2
      },
      ease: {
        standard: 'power2.out',
        emphasis: 'power3.out',
        inOut: 'power2.inOut',
        expressive: 'back.out(1.35)',
        linear: 'none'
      },
      stagger: {
        tight: 0.04,
        base: 0.08,
        loose: 0.14
      },
      distance: {
        xSmall: 8,
        small: 14,
        medium: 22,
        large: 36
      }
    };
  }

  function detectReducedMotion() {
    try {
      var mql = window.matchMedia('(prefers-reduced-motion: reduce)');
      return !!(mql && mql.matches);
    } catch (err) {
      return false;
    }
  }

  function applyGsapDefaults() {
    if (!hasGSAP) return;
    var d = RD.state.defaults;
    window.gsap.defaults({
      duration: d.duration.normal,
      ease: d.ease.standard,
      overwrite: 'auto'
    });
  }

  function refreshScrollTriggers() {
    if (!hasScrollTrigger) return;
    try {
      window.ScrollTrigger.refresh();
    } catch (err) {
      // Ignore refresh failures from detached nodes.
    }
  }

  function killContext(key) {
    if (!key || !RD.state.contexts[key]) return;
    try {
      RD.state.contexts[key].revert();
    } catch (err) {
      // Safe fallback.
    }
    delete RD.state.contexts[key];
  }

  function cleanupAll() {
    var keys = Object.keys(RD.state.contexts);
    for (var i = 0; i < keys.length; i += 1) {
      killContext(keys[i]);
    }

    if (RD.state.mm && typeof RD.state.mm.revert === 'function') {
      try {
        RD.state.mm.revert();
      } catch (err) {
        // Ignore.
      }
      RD.state.mm = null;
    }

    if (hasScrollTrigger) {
      try {
        window.ScrollTrigger.getAll().forEach(function (trigger) {
          trigger.kill();
        });
      } catch (err) {
        // Ignore.
      }
    }
  }

  function registerContext(key, root, setup) {
    if (!hasGSAP || typeof setup !== 'function' || !key) return null;
    killContext(key);

    var ctx = window.gsap.context(function () {
      setup(RD);
    }, root || document);

    RD.state.contexts[key] = ctx;
    return ctx;
  }

  function initPageAnimations(pageKey, root, setup) {
    if (!hasGSAP) return null;
    var key = pageKey || 'page-default';
    return registerContext(key, root, setup);
  }

  function refreshAfterDomSwap(container) {
    if (!hasGSAP) return;

    if (window.RDMotionPresets && typeof window.RDMotionPresets.runAutoReveals === 'function') {
      window.RDMotionPresets.runAutoReveals(container || document);
    }

    refreshScrollTriggers();
  }

  function setupReducedMotionListener() {
    try {
      var mql = window.matchMedia('(prefers-reduced-motion: reduce)');
      if (!mql) return;

      var update = function () {
        RD.state.reduceMotion = !!mql.matches;
      };

      update();

      if (typeof mql.addEventListener === 'function') {
        mql.addEventListener('change', update);
      } else if (typeof mql.addListener === 'function') {
        mql.addListener(update);
      }
    } catch (err) {
      RD.state.reduceMotion = false;
    }
  }

  function setupMatchMedia() {
    if (!hasGSAP || typeof window.gsap.matchMedia !== 'function') return;

    RD.state.mm = window.gsap.matchMedia();
    RD.state.mm.add(
      {
        reduce: '(prefers-reduced-motion: reduce)',
        desktop: '(min-width: 1024px)',
        tabletDown: '(max-width: 1023px)'
      },
      function (context) {
        var conditions = context.conditions || {};
        RD.state.reduceMotion = !!conditions.reduce;
      }
    );
  }

  function initialize() {
    if (RD.state.initialized) return RD;

    RD.state.defaults = buildDefaults();
    RD.state.reduceMotion = detectReducedMotion();

    if (hasGSAP) {
      safeRegisterPlugins();
      applyGsapDefaults();
      setupMatchMedia();
    }

    setupReducedMotionListener();

    RD.state.initialized = true;
    return RD;
  }

  RD.initialize = initialize;
  RD.initPageAnimations = initPageAnimations;
  RD.refreshAfterDomSwap = refreshAfterDomSwap;
  RD.refreshScrollTriggers = refreshScrollTriggers;
  RD.killContext = killContext;
  RD.cleanupPageAnimations = cleanupAll;

  window.RDAnimations = RD;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
  } else {
    initialize();
  }

  window.addEventListener('beforeunload', function () {
    cleanupAll();
  });
})(window, document);
