// NAV: hamburger open//close
const menuBtn = document.getElementById('menuBtn');
const closeBtn = document.getElementById('closeBtn');
const navOverlay = document.getElementById('navOverlay');

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
    navOverlay.querySelectorAll('nav a').forEach(link => link.addEventListener('click', hideNav));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideNav(); });
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
function createCarousel(trackId, { prevId, nextId, interval = 4000, handleMedia = false } = {}) {
    const track = document.getElementById(trackId);
    if (!track) return null;
    const slides = Array.from(track.children);
    let index = 0;
    const total = slides.length;

    function show(i) {
        index = (i + total) % total;
        track.style.transform = `translateX(-${index * 100}%)`;
        if (handleMedia) {
            slides.forEach((s, si) => {
                const v = s.querySelector('video');
                if (v) { if (si === index) { v.play().catch(() => { }); } else { v.pause(); v.currentTime = 0; } }
            });
        }
    }

    const prev = prevId ? document.getElementById(prevId) : null;
    const next = nextId ? document.getElementById(nextId) : null;
    if (prev) prev.addEventListener('click', () => show(index - 1));
    if (next) next.addEventListener('click', () => show(index + 1));

    let timer = setInterval(() => show(index + 1), interval);
    track.addEventListener('mouseenter', () => clearInterval(timer));
    track.addEventListener('mouseleave', () => timer = setInterval(() => show(index + 1), interval));

    // start
    show(0);
    return { show, destroy: () => clearInterval(timer) };
}

// instantiate media carousel (handles videos)
createCarousel('mediaTrack', { interval: 5000, handleMedia: true });

// instantiate photo carousel with controls
createCarousel('photoTrack', { prevId: 'photoPrev', nextId: 'photoNext', interval: 4000 });

// instantiate projects carousel (below hero) with controls and autoplay
createCarousel('projectsTrack', { prevId: 'projectsPrev', nextId: 'projectsNext', interval: 4000 });

// Testimonial auto-scroll showing 3 at a time
(function () {
    const list = document.querySelector('.testimonials__list');
    if (!list) return;
    const items = Array.from(list.children);
    let tIndex = 0;
    const total = items.length;
    function showTestimonials(i) {
        tIndex = i % total;
        const shift = (tIndex) * (items[0].getBoundingClientRect().width + parseFloat(getComputedStyle(list).gap || 0));
        list.style.transform = `translateX(-${shift}px)`;
    }
    let tTimer = setInterval(() => showTestimonials(tIndex + 1), 3500);
    list.addEventListener('mouseenter', () => clearInterval(tTimer));
    list.addEventListener('mouseleave', () => tTimer = setInterval(() => showTestimonials(tIndex + 1), 3500));
})();
