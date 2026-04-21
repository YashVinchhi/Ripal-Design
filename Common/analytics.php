<?php
/**
 * Analytics stub: GA4 + Microsoft Clarity placeholders
 * Include this file from header.php
 */
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XDFRVXT9JJ"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);} 
    gtag('js', new Date());

    gtag('config', 'G-XDFRVXT9JJ');

  // Form submission tracking and file download tracking
  document.addEventListener('DOMContentLoaded', function(){
    // Track form submissions
    document.querySelectorAll('form').forEach(function(f){
      f.addEventListener('submit', function(e){
        try{ var fname = f.getAttribute('name') || f.id || 'unknown_form'; gtag('event','form_submission',{ 'form_name': fname }); }catch(e){}
      }, {capture:true});
    });

    // Track file downloads for common extensions
    document.body.addEventListener('click', function(e){
      var a = e.target.closest && e.target.closest('a');
      if(!a || !a.href) return;
      try{
        var href = a.getAttribute('href');
        if(!href) return;
        var m = href.match(/\.([a-z0-9]+)(?:[?#].*)?$/i);
        if(m){ var ext = m[1].toLowerCase(); if(['pdf','dwg','zip'].indexOf(ext)!==-1){
            var name = href.split('/').pop().split('?')[0];
            gtag('event','file_download',{'file_name': name});
        }}
      }catch(e){}
    }, false);
  });
</script>

<!-- Microsoft Clarity -->
<script type="text/javascript">
  (function(c,l,a,r,i,t,y){
    c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
    t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
    y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
  })(window, document, "clarity", "script", "wf6mhmil74");
</script>
