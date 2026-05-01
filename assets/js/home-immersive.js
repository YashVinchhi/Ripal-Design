(function (window, document) {
  'use strict';

  if (!window || !document) return;
  if (!document.body || !document.body.classList.contains('home-immersive')) return;
  if (typeof window.gsap === 'undefined' || typeof window.ScrollTrigger === 'undefined') return;

  var reduce = !!(window.RDAnimations && window.RDAnimations.state && window.RDAnimations.state.reduceMotion);
  if (reduce) return;

  document.documentElement.style.scrollBehavior = 'auto';

  var sections = window.gsap.utils.toArray('.snap-section');
  if (!sections.length) return;

  var snapPoints = [];

  function updateSnapPoints() {
    snapPoints = sections.map(function (section) {
      return section.offsetTop;
    });
  }

  updateSnapPoints();
  window.ScrollTrigger.addEventListener('refreshInit', updateSnapPoints);
  window.ScrollTrigger.addEventListener('refresh', updateSnapPoints);

  window.ScrollTrigger.create({
    start: 0,
    end: function () {
      return window.ScrollTrigger.maxScroll(window);
    },
    snap: {
      snapTo: function (value) {
        var maxScroll = window.ScrollTrigger.maxScroll(window);
        if (!maxScroll) return value;
        var scroll = value * maxScroll;
        var snapped = window.gsap.utils.snap(snapPoints, scroll);
        return snapped / maxScroll;
      },
      duration: { min: 0.2, max: 0.7 },
      ease: 'power2.out'
    }
  });

  window.gsap.from('.hero-logo', {
    y: -10,
    autoAlpha: 0,
    duration: 0.6,
    ease: 'power2.out',
    delay: 0.1
  });

  window.gsap.from('.hero-content > *', {
    y: 24,
    autoAlpha: 0,
    duration: 0.8,
    stagger: 0.08,
    ease: 'power2.out',
    delay: 0.2
  });

  window.gsap.from('.carousel-caption', {
    y: 20,
    autoAlpha: 0,
    duration: 0.6,
    ease: 'power2.out',
    scrollTrigger: {
      trigger: '.media-carousel',
      start: 'top 70%'
    }
  });

  window.gsap.from('.project-row', {
    y: 20,
    autoAlpha: 0,
    duration: 0.6,
    stagger: 0.12,
    ease: 'power2.out',
    scrollTrigger: {
      trigger: '.projects-section',
      start: 'top 70%'
    }
  });

  window.gsap.from('.logo-track span', {
    y: 10,
    autoAlpha: 0,
    duration: 0.5,
    stagger: 0.05,
    ease: 'power2.out',
    scrollTrigger: {
      trigger: '.companies-section',
      start: 'top 75%'
    }
  });

  window.gsap.from('.testimonial-card', {
    y: 10,
    autoAlpha: 0,
    duration: 0.6,
    stagger: 0.1,
    ease: 'power2.out',
    scrollTrigger: {
      trigger: '.testimonials-section',
      start: 'top 75%'
    }
  });
})(window, document);
