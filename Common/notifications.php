<?php
// notifications.php
// Static notifications include for the sidebar UI. Matches site theme and uses
// the same classes used inside `Common/header.css` so styles are consistent.
// This file is intended to be included from the sidebar panel in `header.php`.
?>

<!-- Panel top: notification control (static UI only) -->
<div class="alt-panel-top">
    <button id="sidebarNotifBtn" class="notif-btn" aria-expanded="false" aria-label="Open notifications">
        <i class="bi bi-bell-fill" aria-hidden="true"></i>
        <span class="notif-count">3</span>
    </button>

    <div id="sidebarNotifPopup" class="notif-popup" aria-hidden="true">
        <div class="notif-popup-header">
            <strong>Notifications</strong>
            <button id="notifClearBtn" class="notif-clear">Clear</button>
        </div>
        <div class="notif-items">
            <div class="notif-item" role="button" tabindex="0" data-detail="A user left a comment: 'Please review the latest drawing.'" style="padding:14px 16px; background:rgba(0,0,0,0.15); border-bottom:1px solid rgba(0,0,0,0.1); cursor:pointer; transition:background 0.2s ease;">
                <div class="notif-main" style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start;">
                    <div class="notif-title" style="color:#ffffff !important; font-size:15px; font-weight:500; flex:1;">New comment on Project A</div>
                    <div class="notif-meta" style="color:rgba(255,255,255,0.7) !important; font-size:12px; white-space:nowrap; flex-shrink:0;">2h ago</div>
                </div>
                <div class="notif-detail" style="color:rgba(255,255,255,0.95) !important; font-size:13px; line-height:1.5; max-height:0; overflow:hidden; transition:all 0.3s ease; padding-top:0; padding-bottom:0; margin-top:0;">A user left a comment: "Please review the latest drawing."</div>
            </div>

            <div class="notif-item" role="button" tabindex="0" data-detail="Worker John Doe was assigned to Task 12. Contact: +91-XXXXXXXXXX" style="padding:14px 16px; background:rgba(0,0,0,0.15); border-bottom:1px solid rgba(0,0,0,0.1); cursor:pointer; transition:background 0.2s ease;">
                <div class="notif-main" style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start;">
                    <div class="notif-title" style="color:#ffffff !important; font-size:15px; font-weight:500; flex:1;">Worker assigned to Task 12</div>
                    <div class="notif-meta" style="color:rgba(255,255,255,0.7) !important; font-size:12px; white-space:nowrap; flex-shrink:0;">Yesterday</div>
                </div>
                <div class="notif-detail" style="color:rgba(255,255,255,0.95) !important; font-size:13px; line-height:1.5; max-height:0; overflow:hidden; transition:all 0.3s ease; padding-top:0; padding-bottom:0; margin-top:0;">Worker John Doe was assigned to Task 12. Contact: +91-XXXXXXXXXX</div>
            </div>

            <div class="notif-item" role="button" tabindex="0" data-detail="Payment gateway credentials were refreshed successfully." style="padding:14px 16px; background:rgba(0,0,0,0.15); border-bottom:1px solid rgba(0,0,0,0.1); cursor:pointer; transition:background 0.2s ease;">
                <div class="notif-main" style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start;">
                    <div class="notif-title" style="color:#ffffff !important; font-size:15px; font-weight:500; flex:1;">Payment gateway updated</div>
                    <div class="notif-meta" style="color:rgba(255,255,255,0.7) !important; font-size:12px; white-space:nowrap; flex-shrink:0;">3 days ago</div>
                </div>
                <div class="notif-detail" style="color:rgba(255,255,255,0.95) !important; font-size:13px; line-height:1.5; max-height:0; overflow:hidden; transition:all 0.3s ease; padding-top:0; padding-bottom:0; margin-top:0;">Payment gateway credentials were refreshed successfully.</div>
            </div>
        </div>
    </div>
</div>
