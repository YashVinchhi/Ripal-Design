/*
 * Auto-hide Alerts
 *
 * Globally hides elements with the `alert` class after a short timeout.
 * Respects `data-autohide="false"` to keep some alerts persistent.
 */
(function(){
  'use strict';

  var DEFAULT_DELAY = 5000; // milliseconds before auto-hide
  var FADE_DURATION = 400; // animation duration in ms (matches CSS transition below)

  function hideAlert(el){
    if(!el || el.dataset && el.dataset.autohide === 'false') return;
    try{
      // Ensure measurable start height for smooth collapse
      el.style.overflow = 'hidden';
      el.style.maxHeight = el.scrollHeight + 'px';
      // trigger transition on next frame
      requestAnimationFrame(function(){
        el.style.transition = 'opacity ' + (FADE_DURATION/1000) + 's ease, max-height ' + (FADE_DURATION/1000) + 's ease, margin ' + (FADE_DURATION/1000) + 's ease, padding ' + (FADE_DURATION/1000) + 's ease';
        el.style.opacity = '0';
        el.style.maxHeight = '0';
        el.style.marginTop = '0';
        el.style.marginBottom = '0';
        el.style.paddingTop = '0';
        el.style.paddingBottom = '0';
      });
      setTimeout(function(){
        try{ el.remove(); }catch(e){}
      }, FADE_DURATION + 50);
    }catch(e){}
  }

  function scheduleAutoHideFor(el){
    if(!el) return;
    if (el.dataset && el.dataset.autohide === 'false') return;
    // Avoid scheduling twice
    if (el.__auto_hide_scheduled) return;
    el.__auto_hide_scheduled = true;
    var delay = parseInt(el.dataset.autohideDelay || DEFAULT_DELAY, 10);
    setTimeout(function(){ hideAlert(el); }, isNaN(delay) ? DEFAULT_DELAY : delay);
  }

  function scanAndSchedule(root){
    root = root || document;
    var alerts = root.querySelectorAll && root.querySelectorAll('.alert');
    if (!alerts) return;
    alerts.forEach(function(a){ scheduleAutoHideFor(a); });
  }

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ scanAndSchedule(document); });
  } else {
    scanAndSchedule(document);
  }

  // Observe future added alerts (e.g. dynamic inserts)
  try{
    var mo = new MutationObserver(function(muts){
      muts.forEach(function(m){
        m.addedNodes.forEach(function(node){
          if (!node) return;
          if (node.nodeType !== 1) return;
          if (node.classList && node.classList.contains('alert')) {
            scheduleAutoHideFor(node);
          } else if (node.querySelectorAll) {
            var found = node.querySelectorAll('.alert');
            found.forEach(function(a){ scheduleAutoHideFor(a); });
          }
        });
      });
    });
    mo.observe(document.documentElement || document.body, { childList: true, subtree: true });
  }catch(e){}

})();
