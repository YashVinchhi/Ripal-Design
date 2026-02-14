/**
 * Header Navigation Menu Script
 * 
 * Handles the mobile/overlay navigation menu functionality.
 * Features:
 * - Toggle menu open/close
 * - Close on outside click
 * - Close on Escape key
 * - Spotlight pointer effect on menu panel
 * 
 * @package RipalDesign
 * @subpackage Assets
 */

(function() {
    'use strict';
    
    // Get DOM elements
    var btn = document.getElementById('altMenuBtn');
    var overlay = document.getElementById('altOverlay');
    var panel = overlay ? overlay.querySelector('.alt-panel') : null;
    
    // Return early if elements don't exist
    if (!btn || !overlay) return;
    
    /**
     * Open the navigation overlay
     */
    function openMenu() {
        overlay.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        
        var hamburger = btn.querySelector('.alt-hamburger');
        if (hamburger) {
            hamburger.classList.add('active');
        }
        
        // Prevent body scroll when menu is open
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * Close the navigation overlay
     */
    function closeMenu() {
        overlay.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
        
        var hamburger = btn.querySelector('.alt-hamburger');
        if (hamburger) {
            hamburger.classList.remove('active');
        }
        
        // Restore body scroll
        document.body.style.overflow = '';
    }
    
    /**
     * Toggle menu open/closed
     */
    function toggleMenu() {
        if (overlay.classList.contains('open')) {
            closeMenu();
        } else {
            openMenu();
        }
    }
    
    // Set initial ARIA state
    btn.setAttribute('aria-expanded', 'false');
    
    // Toggle menu on button click
    btn.addEventListener('click', toggleMenu);
    
    // Close when clicking outside the panel
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeMenu();
        }
    });
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.classList.contains('open')) {
            closeMenu();
        }
    });
    
    // Spotlight pointer interaction (throttled via requestAnimationFrame)
    if (panel) {
        var rect = null;
        var mouseX = 0;
        var mouseY = 0;
        var rafId = null;
        
        /**
         * Update spotlight position
         */
        function updateSpotlight() {
            if (!rect) {
                rect = panel.getBoundingClientRect();
            }
            
            panel.style.setProperty('--spot-x', (mouseX - rect.left) + 'px');
            panel.style.setProperty('--spot-y', (mouseY - rect.top) + 'px');
            rafId = null;
        }
        
        /**
         * Handle mouse movement
         */
        panel.addEventListener('mousemove', function(e) {
            mouseX = e.clientX;
            mouseY = e.clientY;
            
            if (!rafId) {
                rafId = requestAnimationFrame(updateSpotlight);
            }
        });
        
        /**
         * Handle touch movement
         */
        panel.addEventListener('touchmove', function(e) {
            if (e.touches && e.touches[0]) {
                mouseX = e.touches[0].clientX;
                mouseY = e.touches[0].clientY;
                
                if (!rafId) {
                    rafId = requestAnimationFrame(updateSpotlight);
                }
            }
        }, { passive: true });
        
        /**
         * Reset spotlight on mouse leave
         */
        panel.addEventListener('mouseleave', function() {
            rect = null;
            panel.style.setProperty('--spot-x', '50%');
            panel.style.setProperty('--spot-y', '50%');
        });
    }
})();
