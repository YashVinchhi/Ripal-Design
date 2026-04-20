document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.gsap === 'undefined') {
        return;
    }

    if (typeof window.ScrollTrigger !== 'undefined') {
        window.gsap.registerPlugin(window.ScrollTrigger);
    }

    // 1. Legacy nav fallback: only run if those elements exist
    var menuBtn = document.getElementById('menuBtn');
    var closeBtn = document.getElementById('closeBtn');
    var navOverlay = document.getElementById('navOverlay');

    if (menuBtn && navOverlay) {
        menuBtn.addEventListener('click', function () {
            navOverlay.classList.add('open');
            window.gsap.from('#navOverlay nav a', {
                y: 30,
                opacity: 0,
                stagger: 0.1,
                duration: 0.6,
                ease: 'power2.out'
            });
        });
    }

    if (closeBtn && navOverlay) {
        closeBtn.addEventListener('click', function () {
            navOverlay.classList.remove('open');
        });
    }

    // 2. Timeline animation logic
    var tapeStrip = document.getElementById('tapeStrip');
    var markers = document.querySelectorAll('.milestone-marker');
    var timelineSection = document.querySelector('.timeline-section');

    if (!timelineSection || !tapeStrip || !markers.length) {
        return;
    }

    window.gsap.set('#tapeStrip', { width: '0%' });
    window.gsap.set('.milestone-marker', { opacity: 0, scale: 0.8, y: 30 });

    var timelineMaster = window.gsap.timeline({
        scrollTrigger: {
            trigger: '.timeline-section',
            start: 'top 45%',
            end: 'bottom 20%',
            toggleActions: 'play none none reverse'
        }
    });

    timelineMaster.to('#tapeStrip', {
        width: '100%',
        duration: 2.5,
        ease: 'expo.inOut'
    });

    timelineMaster.to('.milestone-marker', {
        opacity: 1,
        scale: 1,
        y: 0,
        duration: 0.8,
        stagger: 0.4,
        ease: 'back.out(1.7)'
    }, '-=1.8');

    markers.forEach(function (marker, index) {
        timelineMaster.to(marker, {
            onStart: function () {
                marker.classList.add('active');
            },
            duration: 0.1
        }, '-=' + (2 - (index * 0.7)));
    });

    // 3. Stats section parallax/reveal
    window.gsap.from('.stat-number-bg', {
        scrollTrigger: {
            trigger: '.stat-item',
            start: 'top 90%',
            scrub: 1
        },
        y: 100,
        opacity: 0
    });
});
