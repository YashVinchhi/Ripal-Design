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

    // Close menu when any panel link is clicked while allowing normal navigation.
    var panelLinks = overlay.querySelectorAll('.alt-panel nav a, .alt-panel .panel-footer a');
    Array.prototype.forEach.call(panelLinks, function(link) {
        link.addEventListener('click', function() {
            closeMenu();
        });
    });
    
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

    // Sidebar notification popup (static UI)
    var notifBtn = document.getElementById('sidebarNotifBtn');
    var notifPopup = document.getElementById('sidebarNotifPopup');
    var notifClear = document.getElementById('notifClearBtn');

    if (notifBtn && notifPopup) {
        function openNotif() {
            notifPopup.classList.add('open');
            notifBtn.setAttribute('aria-expanded', 'true');
            notifPopup.setAttribute('aria-hidden', 'false');
        }
        function closeNotif() {
            notifPopup.classList.remove('open');
            notifBtn.setAttribute('aria-expanded', 'false');
            notifPopup.setAttribute('aria-hidden', 'true');
        }
        function toggleNotif(e) {
            e.stopPropagation();
            e.preventDefault();
            if (notifPopup.classList.contains('open')) closeNotif(); else openNotif();
        }

        notifBtn.addEventListener('click', toggleNotif);

        // Prevent clicks inside popup from bubbling
        notifPopup.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Close popup when clicking inside panel but outside popup and button
        panel.addEventListener('click', function(e) {
            if (!notifPopup.contains(e.target) && !notifBtn.contains(e.target)) {
                closeNotif();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && notifPopup.classList.contains('open')) {
                closeNotif();
            }
        });

        // Clear button (client-side static behavior)
        if (notifClear) {
            notifClear.addEventListener('click', function(e) {
                e.stopPropagation();
                var items = notifPopup.querySelectorAll('.notif-item');
                items.forEach(function(it){ it.remove(); });
                var count = notifBtn.querySelector('.notif-count');
                if (count) count.textContent = '0';
            });
        }

        // Make each notification item expandable / clickable
        var notifItems = notifPopup.querySelectorAll('.notif-item');
        notifItems.forEach(function(item){
            var detail = item.querySelector('.notif-detail');
            
            function openItem() {
                // close other items
                notifItems.forEach(function(i){ 
                    if (i !== item) {
                        i.classList.remove('open');
                        var otherDetail = i.querySelector('.notif-detail');
                        if (otherDetail) {
                            otherDetail.style.maxHeight = '0';
                            otherDetail.style.paddingTop = '0';
                            otherDetail.style.paddingBottom = '0';
                            otherDetail.style.marginTop = '0';
                        }
                    }
                });
                
                // toggle current item
                var isOpen = item.classList.contains('open');
                item.classList.toggle('open');
                
                if (detail) {
                    if (isOpen) {
                        // collapse
                        detail.style.maxHeight = '0';
                        detail.style.paddingTop = '0';
                        detail.style.paddingBottom = '0';
                        detail.style.marginTop = '0';
                    } else {
                        // expand
                        detail.style.maxHeight = '200px';
                        detail.style.paddingTop = '8px';
                        detail.style.paddingBottom = '8px';
                        detail.style.marginTop = '6px';
                    }
                }
                
                // mark as read by decrementing count if >0
                var countEl = notifBtn.querySelector('.notif-count');
                if (countEl && !isOpen) {
                    var v = parseInt(countEl.textContent || '0', 10);
                    if (v > 0) {
                        countEl.textContent = String(Math.max(0, v-1));
                    }
                }
            }

            item.addEventListener('click', function(e){
                e.stopPropagation();
                openItem();
            });
            item.addEventListener('keydown', function(e){
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openItem(); }
            });
        });
    }
})();
