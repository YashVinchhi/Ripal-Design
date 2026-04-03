/*
 * AJAX Forms (site-wide)
 *
 * Intercepts form submissions for forms marked with `data-ajax="true"` or `class="ajax-form"`.
 * Submits via fetch using FormData so file uploads work, then replaces alerts/forms/avatar parts
 * from the returned HTML response to avoid a full page reload.
 */
(function(){
  'use strict';

  function createAlertElement(message, type){
    var div = document.createElement('div');
    div.className = 'alert alert-' + (type || 'info') + ' alert-dismissible fade show';
    div.setAttribute('role','alert');
    div.innerHTML = (message || '') + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    return div;
  }

  function showAlert(message, type){
    var existing = document.querySelector('.alert');
    var el = createAlertElement(message, type);
    if (existing) existing.replaceWith(el); else {
      var main = document.querySelector('main') || document.body;
      main.insertBefore(el, main.firstChild);
    }
    // auto-hide script (if present) will pick this up
  }

  function replaceIfPresent(doc, id){
    try{
      if (!id) return false;
      var newEl = doc.getElementById(id);
      if (newEl){
        var cur = document.getElementById(id);
        if (cur) cur.replaceWith(newEl);
        else {
          // if not present insert near top of main
          var main = document.querySelector('main') || document.body;
          main.insertBefore(newEl, main.firstChild);
        }
        return true;
      }
    }catch(e){}
    return false;
  }

  function handleResponseText(form, text){
    if (!text) return;
    var parser = new DOMParser();
    var doc = parser.parseFromString(text, 'text/html');

    // Replace alert if server rendered one
    var newAlert = doc.querySelector('.alert');
    if (newAlert){
      var existing = document.querySelector('.alert');
      if (existing) existing.replaceWith(newAlert);
      else {
        var main = document.querySelector('main') || document.body;
        main.insertBefore(newAlert, main.firstChild);
      }
    }

    // Replace common profile elements if present
    ['profileAvatarDisplay','profileAvatarInitials','avatarFormSidebar','profileMainForm','profilePasswordForm'].forEach(function(id){
      var replaced = replaceIfPresent(doc, id);
      if (replaced){
        // If we replaced a form, attach handler to it
        var newEl = document.getElementById(id);
        if (newEl && newEl.tagName === 'FORM') attachAjaxToForm(newEl);
      }
    });

    // Recreate icons if necessary
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      try { window.lucide.createIcons(); } catch(e){}
    }
  }

  function attachAjaxToForm(form){
    if (!form || form.__ajax_attached) return;
    form.__ajax_attached = true;

    form.addEventListener('submit', function(e){
      e.preventDefault();
      // Disable submit buttons while request in-flight
      var submitButtons = Array.prototype.slice.call(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
      submitButtons.forEach(function(b){ b.disabled = true; });

      var action = form.getAttribute('action') || window.location.href;
      var method = (form.getAttribute('method') || 'POST').toUpperCase();
      var formData = new FormData(form);

      fetch(action, {
        method: method,
        body: formData,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html, application/json'
        }
      }).then(function(resp){
        var ct = resp.headers.get('Content-Type') || '';
        if (ct.indexOf('application/json') !== -1) return resp.json().then(function(j){ return {json: j}; });
        return resp.text().then(function(t){ return {text: t}; });
      }).then(function(result){
        if (result.json){
          var j = result.json;
          if (j.message){ showAlert(j.message, j.type || 'info'); }
          if (j.html){ handleResponseText(form, j.html); }
        } else if (result.text){
          handleResponseText(form, result.text);
        }
      }).catch(function(err){
        console.error('AJAX form error', err);
        showAlert('Unable to save changes. Please try again.', 'danger');
      }).finally(function(){
        submitButtons.forEach(function(b){ b.disabled = false; });
      });

    }, false);
  }

  function scanAndAttach(root){
    root = root || document;
    var forms = Array.prototype.slice.call(root.querySelectorAll('form[data-ajax="true"], form.ajax-form'));
    forms.forEach(attachAjaxToForm);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ scanAndAttach(document); }); else scanAndAttach(document);

  // Observe for dynamically inserted forms/alerts
  try{
    var mo = new MutationObserver(function(muts){
      muts.forEach(function(m){
        Array.prototype.forEach.call(m.addedNodes, function(node){
          if (!node || node.nodeType !== 1) return;
          if (node.matches && node.matches('form[data-ajax="true"], form.ajax-form')) attachAjaxToForm(node);
          else if (node.querySelectorAll){
            var hits = node.querySelectorAll('form[data-ajax="true"], form.ajax-form');
            if (hits && hits.length) Array.prototype.forEach.call(hits, attachAjaxToForm);
          }
        });
      });
    });
    mo.observe(document.documentElement || document.body, { childList: true, subtree: true });
  }catch(e){}

})();
