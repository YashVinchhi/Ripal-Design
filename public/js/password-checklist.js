(function(){
  function $(s){return document.querySelector(s)}
  function $all(s){return Array.from(document.querySelectorAll(s))}
  var pwd = $('#password');
  var list = $('#passwordChecklist');
  if (!pwd || !list) return;

  var items = {
    length: /(?=.{8,})/,
    uppercase: /[A-Z]/,
    lowercase: /[a-z]/,
    number: /[0-9]/,
    special: /[^A-Za-z0-9]/
  };

  function check() {
    var v = pwd.value || '';
    Object.keys(items).forEach(function(key){
      var re = items[key];
      var ok = re.test(v);
      var li = list.querySelector('li[data-check="'+key+'"]');
      if (!li) return;
      if (ok) {
        li.classList.remove('unmet');
        li.classList.add('met');
        li.setAttribute('aria-checked','true');
      } else {
        li.classList.remove('met');
        li.classList.add('unmet');
        li.setAttribute('aria-checked','false');
      }
    });
  }

  // run on input & on page load (in case browser autofill)
  pwd.addEventListener('input', check, {passive:true});
  window.addEventListener('DOMContentLoaded', check);
  // also run shortly after to catch autofill
  setTimeout(check, 300);
})();
