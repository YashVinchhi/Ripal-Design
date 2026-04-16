/*
 * GSAP Motion Presets
 *
 * Shared reveal and interaction helpers used across public and dashboard pages.
 */
(function (window, document) {
  'use strict';

  if (!window || !document) return;

  var hasGSAP = typeof window.gsap !== 'undefined';
  if (!hasGSAP) return;

  var Presets = window.RDMotionPresets || {};

  function getRuntimeDefaults() {
    var state = window.RDAnimations && window.RDAnimations.state;
    return (state && state.defaults) || {
      duration: { fast: 0.24, normal: 0.48, slow: 0.8 },
      ease: { standard: 'power2.out', emphasis: 'power3.out' },
      stagger: { base: 0.08 },
      distance: { small: 14, medium: 22 }
    };
  }

  function reduceMotionEnabled() {
    var state = window.RDAnimations && window.RDAnimations.state;
    return !!(state && state.reduceMotion);
  }

  function queryAll(root, selector) {
    if (!root || !selector) return [];
    return Array.prototype.slice.call(root.querySelectorAll(selector));
  }

  function markInitialized(el) {
    if (!el) return;
    el.setAttribute('data-anim-init', '1');
  }

  function shouldSkip(el) {
    return !el || el.getAttribute('data-anim-init') === '1';
  }

  function revealUp(targets, options) {
    if (!targets || (Array.isArray(targets) && targets.length === 0)) return null;

    var d = getRuntimeDefaults();
    var opts = options || {};
    var reduce = reduceMotionEnabled();

    return window.gsap.from(targets, {
      y: reduce ? 0 : (opts.distance || d.distance.medium),
      autoAlpha: 0,
      duration: reduce ? 0 : (opts.duration || d.duration.normal),
      ease: opts.ease || d.ease.standard,
      stagger: reduce ? 0 : (opts.stagger || d.stagger.base),
      clearProps: 'opacity,visibility,transform'
    });
  }

  function revealIn(targets, options) {
    if (!targets || (Array.isArray(targets) && targets.length === 0)) return null;

    var d = getRuntimeDefaults();
    var opts = options || {};
    var reduce = reduceMotionEnabled();

    return window.gsap.from(targets, {
      autoAlpha: 0,
      duration: reduce ? 0 : (opts.duration || d.duration.fast),
      ease: opts.ease || d.ease.standard,
      stagger: reduce ? 0 : (opts.stagger || d.stagger.tight || 0.04),
      clearProps: 'opacity,visibility'
    });
  }

  function attachHoverLift(selector, options) {
    var nodes = queryAll(document, selector);
    if (!nodes.length) return;

    var d = getRuntimeDefaults();
    var opts = options || {};
    var liftY = typeof opts.y === 'number' ? opts.y : -6;

    nodes.forEach(function (node) {
      if (node.getAttribute('data-anim-hover') === '1') return;
      node.setAttribute('data-anim-hover', '1');

      node.addEventListener('mouseenter', function () {
        if (reduceMotionEnabled()) return;
        window.gsap.to(node, {
          y: liftY,
          duration: opts.enterDuration || d.duration.fast,
          ease: opts.ease || d.ease.emphasis,
          overwrite: 'auto'
        });
      });

      node.addEventListener('mouseleave', function () {
        window.gsap.to(node, {
          y: 0,
          duration: opts.leaveDuration || d.duration.fast,
          ease: opts.ease || d.ease.standard,
          overwrite: 'auto'
        });
      });
    });
  }

  function toNodeList(targets) {
    if (!targets) return [];
    if (typeof targets === 'string') return queryAll(document, targets);
    if (targets instanceof Element) return [targets];
    if (Array.isArray(targets)) return targets;
    if (typeof targets.length === 'number') return Array.prototype.slice.call(targets);
    return [];
  }

  function staggeredEntry(targets, options) {
    var nodes = toNodeList(targets);
    if (!nodes.length) return null;

    var d = getRuntimeDefaults();
    var opts = options || {};
    var reduce = reduceMotionEnabled();

    return window.gsap.from(nodes, {
      y: reduce ? 0 : (opts.distance || d.distance.small),
      autoAlpha: 0,
      duration: reduce ? 0 : (opts.duration || d.duration.fast),
      stagger: reduce ? 0 : (opts.stagger || d.stagger.base),
      ease: opts.ease || d.ease.standard,
      clearProps: 'opacity,visibility,transform'
    });
  }

  function magneticButtons(selector, options) {
    var nodes = queryAll(document, selector || '[data-magnetic]');
    if (!nodes.length) return;

    var opts = options || {};
    var strength = typeof opts.strength === 'number' ? opts.strength : 0.22;
    var maxShift = typeof opts.maxShift === 'number' ? opts.maxShift : 12;
    var d = getRuntimeDefaults();

    nodes.forEach(function (node) {
      if (node.getAttribute('data-anim-magnetic') === '1') return;
      node.setAttribute('data-anim-magnetic', '1');

      var xTo = window.gsap.quickTo(node, 'x', {
        duration: d.duration.fast,
        ease: d.ease.emphasis,
        overwrite: 'auto'
      });
      var yTo = window.gsap.quickTo(node, 'y', {
        duration: d.duration.fast,
        ease: d.ease.emphasis,
        overwrite: 'auto'
      });

      node.addEventListener('mousemove', function (event) {
        if (reduceMotionEnabled()) return;

        var rect = node.getBoundingClientRect();
        var relX = event.clientX - (rect.left + rect.width / 2);
        var relY = event.clientY - (rect.top + rect.height / 2);

        var nextX = Math.max(Math.min(relX * strength, maxShift), -maxShift);
        var nextY = Math.max(Math.min(relY * strength, maxShift), -maxShift);

        xTo(nextX);
        yTo(nextY);
      });

      node.addEventListener('mouseleave', function () {
        xTo(0);
        yTo(0);
      });
    });
  }

  function countUp(targets, options) {
    var nodes = toNodeList(targets);
    if (!nodes.length) return;

    var d = getRuntimeDefaults();
    var opts = options || {};
    var duration = reduceMotionEnabled() ? 0 : (opts.duration || d.duration.slow);

    nodes.forEach(function (node) {
      if (!node || node.getAttribute('data-anim-counted') === '1') return;

      var rawText = String(node.getAttribute('data-countup-target') || node.textContent || '').trim();
      var normalized = rawText.replace(/[^0-9.-]/g, '');
      var targetValue = parseFloat(normalized);
      if (!isFinite(targetValue)) return;

      var decimals = 0;
      if (normalized.indexOf('.') !== -1) {
        decimals = normalized.split('.')[1].length;
      }

      var hasGrouping = /,/.test(rawText);
      var counter = { value: 0 };
      node.setAttribute('data-anim-counted', '1');

      window.gsap.to(counter, {
        value: targetValue,
        duration: duration,
        ease: opts.ease || d.ease.emphasis,
        onUpdate: function () {
          if (decimals > 0) {
            node.textContent = counter.value.toFixed(decimals);
            return;
          }

          var rounded = Math.round(counter.value);
          node.textContent = hasGrouping ? rounded.toLocaleString('en-IN') : String(rounded);
        },
        onComplete: function () {
          node.textContent = rawText;
        }
      });
    });
  }

  function setupScrollReveals(root) {
    if (typeof window.ScrollTrigger === 'undefined') return;
    var d = getRuntimeDefaults();
    var reduce = reduceMotionEnabled();

    var revealNodes = queryAll(root, '[data-anim="reveal"]:not([data-anim-init="1"])');
    revealNodes.forEach(function (node) {
      markInitialized(node);

      window.gsap.from(node, {
        y: reduce ? 0 : d.distance.medium,
        autoAlpha: 0,
        duration: reduce ? 0 : d.duration.normal,
        ease: d.ease.standard,
        scrollTrigger: {
          trigger: node,
          start: 'top 88%',
          once: true
        },
        clearProps: 'opacity,visibility,transform'
      });
    });

    var staggerGroups = queryAll(root, '[data-anim-group]:not([data-anim-init="1"])');
    staggerGroups.forEach(function (group) {
      markInitialized(group);
      var children = queryAll(group, '[data-anim-item]');
      if (!children.length) return;

      window.gsap.from(children, {
        y: reduce ? 0 : d.distance.small,
        autoAlpha: 0,
        duration: reduce ? 0 : d.duration.fast,
        stagger: reduce ? 0 : d.stagger.base,
        ease: d.ease.standard,
        scrollTrigger: {
          trigger: group,
          start: 'top 88%',
          once: true
        },
        clearProps: 'opacity,visibility,transform'
      });
    });
  }

  function runAutoReveals(root) {
    var scope = root || document;
    setupScrollReveals(scope);

    if (window.RDAnimations && typeof window.RDAnimations.refreshScrollTriggers === 'function') {
      window.RDAnimations.refreshScrollTriggers();
    }
  }

  Presets.revealUp = revealUp;
  Presets.revealIn = revealIn;
  Presets.attachHoverLift = attachHoverLift;
  Presets.staggeredEntry = staggeredEntry;
  Presets.magneticButtons = magneticButtons;
  Presets.countUp = countUp;
  Presets.runAutoReveals = runAutoReveals;

  window.RDMotionPresets = Presets;

  function boot() {
    runAutoReveals(document);
    attachHoverLift('.btn-alt, .testimonial-card, .stat-item', { y: -4 });
    magneticButtons('[data-magnetic]');

    var autogroup = queryAll(document, '[data-stagger-entry]');
    if (autogroup.length) {
      staggeredEntry(autogroup, { distance: 16, duration: 0.38 });
    }

    var counters = queryAll(document, '[data-countup]');
    if (counters.length) {
      countUp(counters);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})(window, document);
