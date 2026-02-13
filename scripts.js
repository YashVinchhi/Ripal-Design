const menuBtn = document.getElementById('menuBtn');
const closeBtn = document.getElementById('closeBtn');
const navOverlay = document.getElementById('navOverlay');

// Ensure nav overlay starts hidden for accessibility
if (navOverlay) navOverlay.setAttribute('aria-hidden', 'true');

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

// Testimonial auto-scroll showing 3 at a time
(function () {
    const list = document.querySelector('.testimonials__list');
    if (!list) return;
    const items = Array.from(list.children);
    if (!items.length) return;
    let tIndex = 0;
    const total = items.length;
    const gap = parseFloat(getComputedStyle(list).gap) || 0;

    function showTestimonials(i) {
        tIndex = ((i % total) + total) % total;
        const cardWidth = items[0].getBoundingClientRect().width;
        const shift = tIndex * (cardWidth + gap);
        list.style.transform = `translateX(-${shift}px)`;
    }

    let tTimer = null;
    function startTestimonials() {
        if (tTimer) return;
        tTimer = setInterval(() => showTestimonials(tIndex + 1), 3500);
    }
    function stopTestimonials() {
        if (tTimer) { clearInterval(tTimer); tTimer = null; }
    }

    // Pause on hover, resume on leave
    list.addEventListener('mouseenter', stopTestimonials);
    list.addEventListener('mouseleave', startTestimonials);

    // start
    showTestimonials(0);
    startTestimonials();
})();

// Measuring Tape Animation (GSAP) — vanilla JS (no jQuery required)
document.addEventListener('DOMContentLoaded', function () {
    if (typeof gsap === 'undefined') return;
    if (typeof gsap.registerPlugin === 'function' && typeof ScrollTrigger !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);
    }

    // Initial State
    gsap.set('.tape-strip', { width: '0%' });
    gsap.set('.tape-hook', { left: '0%' });

    // Timeline Animation (Auto-Extend)
    let tl = gsap.timeline({
        scrollTrigger: {
            trigger: ".timeline-section",
            start: "top 60%", // Start when section is visible
            toggleActions: "play none none reverse"
        }
    });

    // 1. Extend the tape over 3 seconds
    tl.to('.tape-strip', {
        width: '100%',
        ease: "power2.out",
        duration: 3
    }, 0)
        .to('.tape-hook', {
            left: '100%',
            ease: "power2.out",
            duration: 3
        }, 0);

    // 2. Trigger milestones as the tape passes them
    function activateMilestone(selector, positionPercent) {
        let time = (positionPercent / 100) * 3;
        tl.call(() => {
            document.querySelectorAll(selector).forEach(el => el.classList.add('active'));
        }, null, time);
    }

    activateMilestone('.milestone-marker[data-pos="0"]', 30); // Start 30%
    activateMilestone('.milestone-marker[data-pos="33"]', 52);
    activateMilestone('.milestone-marker[data-pos="66"]', 73);
    activateMilestone('.milestone-marker[data-pos="100"]', 95); // End 95%
});
