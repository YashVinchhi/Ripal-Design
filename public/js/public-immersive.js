(function (window, document) {
  'use strict';

  if (!window || !document) return;
  if (typeof window.gsap === 'undefined') return;

  var body = document.body;
  if (!body) return;

  var isPublicImmersive = body.classList.contains('public-immersive');
  var isHomeImmersive = body.classList.contains('home-immersive');
  if (!isPublicImmersive && !isHomeImmersive) return;

  function addRevealAttributes() {
    var heroCopy = document.querySelectorAll('.hero-copy');
    heroCopy.forEach(function (node) {
      if (!node.hasAttribute('data-anim')) {
        node.setAttribute('data-anim', 'reveal');
      }
    });

    var revealSections = document.querySelectorAll('.section-head, .feature-panel, .service-layout, .contact-layout, .auth-layout, .legal-card');
    revealSections.forEach(function (node) {
      if (!node.hasAttribute('data-anim')) {
        node.setAttribute('data-anim', 'reveal');
      }
    });

    var groups = document.querySelectorAll('.card-grid, .project-grid, .service-list, .split-grid');
    groups.forEach(function (group) {
      if (!group.hasAttribute('data-anim-group')) {
        group.setAttribute('data-anim-group', '');
      }

      var children = group.querySelectorAll('article, a, .service-card, .project-card, .team-card, .process-step');
      children.forEach(function (child) {
        if (!child.hasAttribute('data-anim-item')) {
          child.setAttribute('data-anim-item', '');
        }
      });
    });
  }

  function runMotion() {
    var presets = window.RDMotionPresets;
    if (!presets) return;

    addRevealAttributes();
    presets.runAutoReveals(document);
    presets.attachHoverLift('.project-card, .service-card, .process-step, .team-card, .contact-panel, .auth-card', { y: -6 });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runMotion, { once: true });
  } else {
    runMotion();
  }
})(window, document);
