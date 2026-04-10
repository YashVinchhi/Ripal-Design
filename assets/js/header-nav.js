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
        link.addEventListener('click', function(e) {
            // Allow anchor-less or same-page fragment links to behave normally
            var href = link.getAttribute('href');
            var target = link.getAttribute('target');

            if (!href || href.indexOf('#') === 0) {
                closeMenu();
                return;
            }

            // If link opens in a new tab/window, just close and let default happen
            if (target && target !== '_self') {
                closeMenu();
                return;
            }

            // Prevent immediate navigation so the close animation can run
            e.preventDefault();
            closeMenu();

            // Wait for overlay opacity transition to finish before navigating
            var navigated = false;
            function doNavigate() {
                if (navigated) return; navigated = true;
                window.location.href = href;
            }

            // Listen for transitionend on the overlay (opacity transition)
            function onTransition(e) {
                // ensure we react to the overlay opacity/transform completing
                if (e.target === overlay) {
                    overlay.removeEventListener('transitionend', onTransition);
                    doNavigate();
                }
            }

            overlay.addEventListener('transitionend', onTransition);

            // Fallback: navigate after 400ms if transitionend doesn't fire
            setTimeout(doNavigate, 400);
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

    // Sidebar notification popup (DB-backed + polling + deep links)
    var notifBtn = document.getElementById('sidebarNotifBtn');
    var notifPopup = document.getElementById('sidebarNotifPopup');
    var notifClear = document.getElementById('notifClearBtn');

    if (notifBtn && notifPopup) {
        var notifApiUrl = notifBtn.getAttribute('data-api-url') || '';
        var notifCsrfToken = notifBtn.getAttribute('data-csrf-token') || '';
        var notifItemsWrap = notifPopup.querySelector('.notif-items');
        var notifPollTimer = null;
        var notifIsLoading = false;
        var notifPollMs = 30000;

        function openNotif() {
            notifPopup.classList.add('open');
            notifBtn.setAttribute('aria-expanded', 'true');
            notifPopup.setAttribute('aria-hidden', 'false');
            fetchNotifications();
        }

        function closeNotif() {
            notifPopup.classList.remove('open');
            notifBtn.setAttribute('aria-expanded', 'false');
            notifPopup.setAttribute('aria-hidden', 'true');
        }

        function toggleNotif(e) {
            e.stopPropagation();
            e.preventDefault();
            if (notifPopup.classList.contains('open')) {
                closeNotif();
            } else {
                openNotif();
            }
        }

        function htmlEscape(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function getBasePathFromApi() {
            if (!notifApiUrl) return '';
            var marker = '/dashboard/api/notifications.php';
            var idx = notifApiUrl.indexOf(marker);
            if (idx === -1) return '';
            return notifApiUrl.substring(0, idx);
        }

        var notifBasePath = getBasePathFromApi();

        function normalizeLink(url) {
            var value = String(url || '').trim();
            if (!value) return '';
            if (/^(https?:)?\/\//i.test(value)) return value;
            if (value.charAt(0) === '/') return value;
            if (notifBasePath) {
                return notifBasePath + '/' + value.replace(/^\/+/, '');
            }
            return '/' + value.replace(/^\/+/, '');
        }

        function resolveNotifLink(item) {
            var deepLink = normalizeLink(item.deep_link || item.deepLink || '');
            if (deepLink) return deepLink;

            var action = String(item.action_key || item.actionKey || item.type || '').toLowerCase();
            var projectId = parseInt(item.project_id || item.projectId || '0', 10) || 0;

            switch (action) {
                case 'project.assigned':
                    return normalizeLink('/worker/assigned_projects.php');
                case 'project.assignment.updated':
                    return projectId > 0
                        ? normalizeLink('/client/client_files.php?project_id=' + projectId)
                        : normalizeLink('/client/client_files.php');
                case 'drawing.uploaded':
                    return projectId > 0
                        ? normalizeLink('/client/client_files.php?project_id=' + projectId)
                        : normalizeLink('/client/client_files.php');
                case 'file.uploaded':
                case 'drawing.changes_requested':
                case 'project.completed':
                case 'project.created':
                    return projectId > 0
                        ? normalizeLink('/dashboard/project_details.php?id=' + projectId)
                        : normalizeLink('/dashboard/project_management.php');
                case 'drawing.approved':
                    return projectId > 0
                        ? normalizeLink('/worker/project_details.php?id=' + projectId)
                        : normalizeLink('/worker/project_details.php');
                case 'review.submitted':
                case 'review.resolved':
                    return normalizeLink('/dashboard/review_requests.php');
                case 'payment.received':
                    return normalizeLink('/admin/payment_gateway.php');
                default:
                    return '';
            }
        }

        function formatNotifTime(createdAt) {
            var dateValue = String(createdAt || '').trim();
            if (!dateValue) return 'now';

            var ts = Date.parse(dateValue.replace(' ', 'T'));
            if (isNaN(ts)) return 'now';

            var diff = Math.floor((Date.now() - ts) / 1000);
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';

            var d = new Date(ts);
            return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        }

        function updateNotifCount(nextCount) {
            var countEl = notifBtn.querySelector('.notif-count');
            if (!countEl) return;

            var value = parseInt(nextCount || '0', 10);
            if (isNaN(value) || value < 0) value = 0;
            countEl.textContent = String(value);
        }

        function markNotifRead(id, done) {
            if (!notifApiUrl || !id) {
                if (typeof done === 'function') done(false, null);
                return;
            }

            fetch(notifApiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': notifCsrfToken
                },
                body: JSON.stringify({
                    action: 'mark_read',
                    id: id,
                    csrf_token: notifCsrfToken
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && typeof data.unread !== 'undefined') {
                        updateNotifCount(data.unread);
                    }
                    if (typeof done === 'function') done(true, data || null);
                })
                .catch(function () {
                    if (typeof done === 'function') done(false, null);
                });
        }

        function markAllNotifRead(done) {
            if (!notifApiUrl) {
                if (typeof done === 'function') done(false, null);
                return;
            }

            fetch(notifApiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': notifCsrfToken
                },
                body: JSON.stringify({
                    action: 'mark_all_read',
                    csrf_token: notifCsrfToken
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data && typeof data.unread !== 'undefined') {
                        updateNotifCount(data.unread);
                    }
                    if (typeof done === 'function') done(true, data || null);
                })
                .catch(function () {
                    if (typeof done === 'function') done(false, null);
                });
        }

        function collapseItem(item) {
            var detail = item.querySelector('.notif-detail');
            item.classList.remove('open');
            if (!detail) return;
            detail.style.maxHeight = '0';
            detail.style.paddingTop = '0';
            detail.style.paddingBottom = '0';
            detail.style.marginTop = '0';
        }

        function expandItem(item) {
            var detail = item.querySelector('.notif-detail');
            item.classList.add('open');
            if (!detail) return;
            detail.style.maxHeight = '200px';
            detail.style.paddingTop = '8px';
            detail.style.paddingBottom = '8px';
            detail.style.marginTop = '6px';
        }

        function renderNotifItems(items) {
            if (!notifItemsWrap) return;

            var list = Array.isArray(items) ? items : [];
            if (list.length === 0) {
                notifItemsWrap.innerHTML =
                    '<div class="notif-item" role="note" tabindex="0" data-id="0" style="padding:14px 16px; background:rgba(0,0,0,0.15); border-bottom:1px solid rgba(0,0,0,0.1);">' +
                    '<div class="notif-main" style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start;">' +
                    '<div class="notif-title" style="color:#ffffff !important; font-size:15px; font-weight:500; flex:1;">No notifications yet</div>' +
                    '<div class="notif-meta" style="color:rgba(255,255,255,0.7) !important; font-size:12px; white-space:nowrap; flex-shrink:0;">now</div>' +
                    '</div>' +
                    '<div class="notif-detail" style="color:rgba(255,255,255,0.95) !important; font-size:13px; line-height:1.5; max-height:200px; overflow:hidden; padding-top:8px; padding-bottom:8px; margin-top:6px;">You are all caught up.</div>' +
                    '</div>';
                return;
            }

            notifItemsWrap.innerHTML = list.map(function (item) {
                var id = parseInt(item.id || '0', 10) || 0;
                var title = htmlEscape(item.title || 'Notification');
                var body = htmlEscape(item.body || '');
                var unread = parseInt(item.is_read || '0', 10) !== 1;
                var actionKey = htmlEscape(item.action_key || item.type || '');
                var projectId = parseInt(item.project_id || '0', 10) || 0;
                var deepLink = htmlEscape(resolveNotifLink(item));
                var timeText = htmlEscape(formatNotifTime(item.created_at || ''));

                return '' +
                    '<div class="notif-item' + (unread ? ' unread' : '') + '" role="button" tabindex="0" data-id="' + id + '" data-action-key="' + actionKey + '" data-project-id="' + projectId + '" data-deep-link="' + deepLink + '" style="padding:14px 16px; background:rgba(0,0,0,0.15); border-bottom:1px solid rgba(0,0,0,0.1); cursor:pointer; transition:background 0.2s ease;">' +
                    '<div class="notif-main" style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start;">' +
                    '<div class="notif-title" style="color:#ffffff !important; font-size:15px; font-weight:500; flex:1;">' + title + '</div>' +
                    '<div class="notif-meta" style="color:rgba(255,255,255,0.7) !important; font-size:12px; white-space:nowrap; flex-shrink:0;">' + timeText + '</div>' +
                    '</div>' +
                    '<div class="notif-detail" style="color:rgba(255,255,255,0.95) !important; font-size:13px; line-height:1.5; max-height:0; overflow:hidden; transition:all 0.3s ease; padding-top:0; padding-bottom:0; margin-top:0;">' + body + '</div>' +
                    '</div>';
            }).join('');

            var notifItems = notifPopup.querySelectorAll('.notif-item');
            Array.prototype.forEach.call(notifItems, function (item) {
                function handleActivate(e) {
                    e.stopPropagation();

                    var notifId = parseInt(item.getAttribute('data-id') || '0', 10);
                    var link = item.getAttribute('data-deep-link') || '';

                    if (link) {
                        var navigate = function () { window.location.href = link; };
                        if (item.classList.contains('unread') && notifId > 0) {
                            markNotifRead(notifId, function () {
                                item.classList.remove('unread');
                                navigate();
                            });
                        } else {
                            navigate();
                        }
                        return;
                    }

                    var isOpen = item.classList.contains('open');
                    Array.prototype.forEach.call(notifItems, function (other) {
                        if (other !== item) collapseItem(other);
                    });

                    if (isOpen) {
                        collapseItem(item);
                    } else {
                        expandItem(item);
                        if (item.classList.contains('unread') && notifId > 0) {
                            markNotifRead(notifId, function (ok) {
                                if (ok) item.classList.remove('unread');
                            });
                        }
                    }
                }

                item.addEventListener('click', handleActivate);
                item.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        handleActivate(e);
                    }
                });
            });
        }

        function fetchNotifications() {
            if (!notifApiUrl || notifIsLoading) return;

            notifIsLoading = true;
            fetch(notifApiUrl + '?limit=20&t=' + Date.now(), {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || !data.success || !data.data) return;
                    updateNotifCount(data.data.unread || 0);
                    renderNotifItems(data.data.items || []);
                })
                .catch(function () {
                    // Silent by design, keeps UI non-blocking.
                })
                .finally(function () {
                    notifIsLoading = false;
                });
        }

        function startNotifPolling() {
            if (notifPollTimer || !notifApiUrl) return;
            notifPollTimer = setInterval(function () {
                fetchNotifications();
            }, notifPollMs);
        }

        notifBtn.addEventListener('click', toggleNotif);

        notifPopup.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        panel.addEventListener('click', function (e) {
            if (!notifPopup.contains(e.target) && !notifBtn.contains(e.target)) {
                closeNotif();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && notifPopup.classList.contains('open')) {
                closeNotif();
            }
        });

        if (notifClear) {
            notifClear.addEventListener('click', function (e) {
                e.stopPropagation();
                markAllNotifRead(function () {
                    fetchNotifications();
                });
            });
        }

        fetchNotifications();
        startNotifPolling();
    }
})();
