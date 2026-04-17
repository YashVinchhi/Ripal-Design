// Legacy fallback copy of public/js/index.js
// NOTE: This is a simple fallback for browsers that don't support modules.
// For full legacy compatibility (IE11) consider transpiling to ES5 with your build tool.
// NAV: hamburger open//close
var menuBtn = document.getElementById('menuBtn');
var closeBtn = document.getElementById('closeBtn');
var navOverlay = document.getElementById('navOverlay');

function showNav() {
    if (!navOverlay) return;
    navOverlay.classList.add('open');
    navOverlay.setAttribute('aria-hidden', 'false');
    if (menuBtn) menuBtn.classList.add('is-active');
}

function hideNav() {
    if (!navOverlay) return;
    navOverlay.classList.remove('open');
    navOverlay.setAttribute('aria-hidden', 'true');
    if (menuBtn) menuBtn.classList.remove('is-active');
}

if (menuBtn) menuBtn.addEventListener('click', showNav);
if (closeBtn) closeBtn.addEventListener('click', hideNav);

if (navOverlay) {
    Array.prototype.forEach.call(navOverlay.querySelectorAll('nav a'), function(link){ link.addEventListener('click', hideNav); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') hideNav(); });
}
(function () {
    var track = document.getElementById('projectsTrack');
    var carousel = document.getElementById('projectsCarousel');
    var slides = track && track.querySelectorAll('.carousel-slide');
    var prev = document.getElementById('projectsPrev');
    var next = document.getElementById('projectsNext');
    if (!track || !slides || slides.length === 0) return;

    var idx = 0;
    var autoplayId = null;
    var animating = false;

    function update(animate) {
        animate = (typeof animate === 'undefined') ? true : animate;
        if (!track) return;
        track.style.transition = animate ? 'transform 0.8s ease' : 'none';
        track.style.transform = 'translateX(' + (-idx * 100) + '%)';
    }

    function nextSlide() {
        idx = (idx + 1) % slides.length;
        update(true);
    }

    function prevSlide() {
        idx = (idx - 1 + slides.length) % slides.length;
        update(true);
    }

    if (prev) prev.addEventListener('click', function () { prevSlide(); restart(); });
    if (next) next.addEventListener('click', function () { nextSlide(); restart(); });

    // autoplay controls
    function start() {
        stop();
        autoplayId = setInterval(function () { nextSlide(); }, 4000);
    }

    function stop() {
        if (autoplayId) { clearInterval(autoplayId); autoplayId = null; }
    }

    // pause on hover
    if (carousel) {
        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);
    }

    // basic swipe support (touch)
    (function () {
        var startX = 0, dist = 0, dragging = false;
        track.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; dragging = true; stop(); }, { passive: true });
        track.addEventListener('touchmove', function (e) { if (!dragging) return; dist = e.touches[0].clientX - startX; track.style.transform = 'translateX(' + ((-idx * 100) + (dist / carousel.clientWidth * 100)) + '%)'; }, { passive: true });
        track.addEventListener('touchend', function () { dragging = false; if (dist > 50) { prevSlide(); } else if (dist < -50) { nextSlide(); } else { update(true); } dist = 0; start(); }, { passive: true });
    })();

    // start autoplay
    start();
})();

// Generic carousel helper
function createCarousel(trackId, opts) {
    opts = opts || {};
    var prevId = opts.prevId;
    var nextId = opts.nextId;
    var interval = typeof opts.interval !== 'undefined' ? opts.interval : 4000;
    var handleMedia = !!opts.handleMedia;

    var track = document.getElementById(trackId);
    if (!track) return null;
    var slides = Array.prototype.slice.call(track.children);
    var index = 0;
    var total = slides.length;

    function show(i) {
        index = (i + total) % total;
        track.style.transform = 'translateX(-' + (index * 100) + '%)';
        if (handleMedia) {
            for (var si = 0; si < slides.length; si++) {
                var s = slides[si];
                var v = s.querySelector && s.querySelector('video');
                if (v) { if (si === index) { try { v.play(); } catch(e){} } else { try { v.pause(); v.currentTime = 0; } catch(e){} } }
            }
        }
    }

    var prev = prevId ? document.getElementById(prevId) : null;
    var next = nextId ? document.getElementById(nextId) : null;
    if (prev) prev.addEventListener('click', function(){ show(index - 1); });
    if (next) next.addEventListener('click', function(){ show(index + 1); });

    var timer = setInterval(function(){ show(index + 1); }, interval);
    track.addEventListener('mouseenter', function(){ clearInterval(timer); });
    track.addEventListener('mouseleave', function(){ timer = setInterval(function(){ show(index + 1); }, interval); });

    // start
    show(0);
    return { show: show, destroy: function(){ clearInterval(timer); } };
}

// instantiate media carousel (handles videos)
createCarousel('mediaTrack', { interval: 5000, handleMedia: true });

// instantiate photo carousel with controls
createCarousel('photoTrack', { prevId: 'photoPrev', nextId: 'photoNext', interval: 4000 });

// instantiate projects carousel (below hero) with controls and autoplay
createCarousel('projectsTrack', { prevId: 'projectsPrev', nextId: 'projectsNext', interval: 4000 });

// Testimonial auto-scroll showing 3 at a time
(function () {
    var list = document.querySelector('.testimonials__list');
    if (!list) return;
    var items = Array.prototype.slice.call(list.children);
    var tIndex = 0;
    var total = items.length;
    function showTestimonials(i) {
        tIndex = i % total;
        var rect = items[0].getBoundingClientRect();
        var gap = parseFloat(window.getComputedStyle(list).gap || 0) || 0;
        var shift = (tIndex) * (rect.width + gap);
        list.style.transform = 'translateX(-' + shift + 'px)';
    }
    var tTimer = setInterval(function(){ showTestimonials(tIndex + 1); }, 3500);
    list.addEventListener('mouseenter', function(){ clearInterval(tTimer); });
    list.addEventListener('mouseleave', function(){ tTimer = setInterval(function(){ showTestimonials(tIndex + 1); }, 3500); });
})();
