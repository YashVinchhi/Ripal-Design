/*
 * persist-tabs.js
 * Persist and restore active tabs across page reloads using URL hash or ?tab=...
 * Works with .tab-link[data-tab], Bootstrap tab toggles ([data-bs-toggle="tab"]) and simple custom tab buttons that target content ids.
 */
(function () {
  'use strict';

  function getTabName(el) {
    if (!el) return '';
    if (el.dataset && el.dataset.tab) return String(el.dataset.tab).trim();

    var target = el.getAttribute('data-bs-target') || el.getAttribute('href') || el.getAttribute('data-target');
    if (!target) return '';
    var id = String(target).replace(/^#/, '').trim();
    if (id.endsWith('-tab')) return id.slice(0, -4);
    return id;
  }

  function init() {
    var selector = '.tab-link[data-tab], [data-bs-toggle="tab"], [data-bs-toggle="pill"], [data-bs-toggle="list"], .tab-btn';
    var links = Array.prototype.slice.call(document.querySelectorAll(selector));
    if (!links.length) return;

    links.forEach(function (link) {
      link.addEventListener('click', function () {
        var name = getTabName(link);
        if (!name) return;
        try {
          if (history.replaceState) history.replaceState(null, '', '#' + name);
          else location.hash = name;
        } catch (e) {
          // ignore
        }
      });
    });

    function restore() {
      var desired = (location.hash || '').replace('#', '').trim();
      if (!desired) {
        try {
          var params = new URLSearchParams(window.location.search);
          desired = String(params.get('tab') || '').trim();
        } catch (e) {
          desired = '';
        }
      }
      if (!desired) return;

      var found = links.find(function (l) {
        return getTabName(l) === desired;
      });
      if (!found) return;

      // If Bootstrap Tab API is available, prefer to use it
      try {
        if (window.bootstrap && typeof window.bootstrap.Tab === 'function' && found.getAttribute('data-bs-toggle')) {
          window.bootstrap.Tab.getOrCreateInstance(found).show();
          return;
        }
      } catch (e) {
        // ignore
      }

      // otherwise trigger a click to activate page-specific handlers
      if (typeof found.click === 'function') {
        found.click();
      } else {
        found.dispatchEvent(new Event('click', { bubbles: true, cancelable: true }));
      }
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function () { setTimeout(restore, 30); });
    } else {
      setTimeout(restore, 30);
    }
  }

  try { init(); } catch (err) { console.error('persist-tabs initialization error', err); }
})();
