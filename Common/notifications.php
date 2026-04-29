<?php
$notifUserId = function_exists('current_user_id') ? current_user_id() : 0;
$notifItems = [];
$notifUnread = 0;

if ($notifUserId > 0 && function_exists('notifications_get_for_user')) {
    $notifItems = notifications_get_for_user($notifUserId, 20);
    $notifUnread = notifications_get_unread_count($notifUserId);
}

$notifApiUrl = rtrim((string)BASE_PATH, '/') . '/dashboard/api/notifications.php';
$notifCsrf = function_exists('csrf_token') ? csrf_token() : '';
?>

<div class="alt-panel-top">
    <button
        id="sidebarNotifBtn"
        class="notif-btn"
        aria-expanded="false"
        aria-label="Open notifications"
        data-api-url="<?php echo htmlspecialchars($notifApiUrl, ENT_QUOTES, 'UTF-8'); ?>"
        data-csrf-token="<?php echo htmlspecialchars($notifCsrf, ENT_QUOTES, 'UTF-8'); ?>"
    >
        <i class="fa-solid fa-bell" aria-hidden="true"></i>
        <span class="notif-count"><?php echo (int)$notifUnread; ?></span>
    </button>

    <div id="sidebarNotifPopup" class="notif-popup" aria-hidden="true">
        <div class="notif-popup-header">
            <strong>Notifications</strong>
            <button id="notifClearBtn" class="notif-clear" type="button">Mark all read</button>
        </div>
        <div class="notif-items">
            <?php if (empty($notifItems)): ?>
                <div class="notif-item" role="note" tabindex="0" data-id="0" style="padding:14px 16px; background:rgba(0,0,0,0.15); border-bottom:1px solid rgba(0,0,0,0.1);">
                    <div class="notif-main" style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start;">
                        <div class="notif-title" style="color:#ffffff !important; font-size:15px; font-weight:500; flex:1;">No notifications yet</div>
                        <div class="notif-meta" style="color:rgba(255,255,255,0.7) !important; font-size:12px; white-space:nowrap; flex-shrink:0;">now</div>
                    </div>
                    <div class="notif-detail" style="color:rgba(255,255,255,0.95) !important; font-size:13px; line-height:1.5; max-height:200px; overflow:hidden; padding-top:8px; padding-bottom:8px; margin-top:6px;">You are all caught up.</div>
                </div>
            <?php else: ?>
                <?php foreach ($notifItems as $item): ?>
                    <?php
                        $isRead = (int)($item['is_read'] ?? 0) === 1;
                        $id = (int)($item['id'] ?? 0);
                        $title = (string)($item['title'] ?? 'Notification');
                        $body = (string)($item['body'] ?? '');
                        $deepLink = (string)($item['deep_link'] ?? '');
                        $actionKey = (string)($item['action_key'] ?? $item['type'] ?? '');
                        $projectId = (int)($item['project_id'] ?? 0);
                        $createdAt = (string)($item['created_at'] ?? '');
                        $timeText = 'now';
                        if ($createdAt !== '') {
                            $ts = strtotime($createdAt);
                            if ($ts !== false) {
                                $diff = time() - $ts;
                                if ($diff < 60) {
                                    $timeText = 'just now';
                                } elseif ($diff < 3600) {
                                    $timeText = floor($diff / 60) . 'm ago';
                                } elseif ($diff < 86400) {
                                    $timeText = floor($diff / 3600) . 'h ago';
                                } else {
                                    $timeText = date('M d', $ts);
                                }
                            }
                        }
                    ?>
                    <div
                        class="notif-item<?php echo $isRead ? '' : ' unread'; ?>"
                        role="button"
                        tabindex="0"
                        data-id="<?php echo (int)$id; ?>"
                        data-deep-link="<?php echo htmlspecialchars($deepLink, ENT_QUOTES, 'UTF-8'); ?>"
                        data-action-key="<?php echo htmlspecialchars($actionKey, ENT_QUOTES, 'UTF-8'); ?>"
                        data-project-id="<?php echo (int)$projectId; ?>"
                        style="padding:14px 16px; background:rgba(0,0,0,0.15); border-bottom:1px solid rgba(0,0,0,0.1); cursor:pointer; transition:background 0.2s ease;"
                    >
                        <div class="notif-main" style="display:flex; justify-content:space-between; gap:8px; align-items:flex-start;">
                            <div class="notif-title" style="color:#ffffff !important; font-size:15px; font-weight:500; flex:1;"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="notif-meta" style="color:rgba(255,255,255,0.7) !important; font-size:12px; white-space:nowrap; flex-shrink:0;"><?php echo htmlspecialchars($timeText, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="notif-detail" style="color:rgba(255,255,255,0.95) !important; font-size:13px; line-height:1.5; max-height:0; overflow:hidden; transition:all 0.3s ease; padding-top:0; padding-bottom:0; margin-top:0;"><?php echo htmlspecialchars($body, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
