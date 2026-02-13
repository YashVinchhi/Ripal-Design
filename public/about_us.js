$(document).ready(function(){
    gsap.registerPlugin(ScrollTrigger);

    // 1. Navigation Logic
    const menuBtn = $('#menuBtn');
    const closeBtn = $('#closeBtn');
    const navOverlay = $('#navOverlay');

    menuBtn.on('click', function() {
        navOverlay.addClass('open');
        gsap.from('#navOverlay nav a', {
            y: 30,
            opacity: 0,
            stagger: 0.1,
            duration: 0.6,
            ease: "power2.out"
        });
    });

    closeBtn.on('click', () => navOverlay.removeClass('open'));

    // 2. Timeline Animation Logic
    // Reset states
    gsap.set('#tapeStrip', { width: '0%' });
    gsap.set('.milestone-marker', { opacity: 0, scale: 0.8, y: 30 });

    const timelineMaster = gsap.timeline({
        scrollTrigger: {
            trigger: ".timeline-section",
            start: "top 45%",
            end: "bottom 20%",
            toggleActions: "play none none reverse"
        }
    });

    // Tape extend
    timelineMaster.to('#tapeStrip', {
        width: '100%',
        duration: 2.5,
        ease: "expo.inOut"
    });

    // Markers pop in sequence
    timelineMaster.to('.milestone-marker', {
        opacity: 1,
        scale: 1,
        y: 0,
        duration: 0.8,
        stagger: 0.4,
        ease: "back.out(1.7)"
    }, "-=1.8");

    // Individual highlight logic based on progress
    const markers = document.querySelectorAll('.milestone-marker');
    markers.forEach((marker, index) => {
        timelineMaster.to(marker, {
            onStart: () => $(marker).addClass('active'),
            duration: 0.1
        }, `-=${2 - (index * 0.7)}`);
    });

    // 3. Stats Section Parallax/Reveal
    gsap.from('.stat-number-bg', {
        scrollTrigger: {
            trigger: '.stat-item',
            start: "top 90%",
            scrub: 1
        },
        y: 100,
        opacity: 0
    });
});
