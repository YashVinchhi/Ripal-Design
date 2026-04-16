<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
require_role('admin');

$db = get_db();

// Handle POST actions (delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete' && !empty($_POST['id']) && $db instanceof PDO) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $db->prepare('DELETE FROM contact_messages WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            set_flash('Message deleted successfully.', 'success');
        } catch (Exception $e) {
            set_flash('Failed to delete message: ' . $e->getMessage(), 'danger');
        }
        // Redirect to avoid form resubmission
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $db instanceof PDO) {
    try {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="contact_messages_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['id', 'first_name', 'last_name', 'email', 'subject', 'message', 'created_at']);
        $stmt = $db->query('SELECT id, first_name, last_name, email, subject, message, created_at FROM contact_messages ORDER BY created_at DESC');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [$row['id'], $row['first_name'], $row['last_name'], $row['email'], $row['subject'], $row['message'], $row['created_at']]);
        }
        fclose($out);
        exit;
    } catch (Exception $e) {
        set_flash('Export failed: ' . $e->getMessage(), 'danger');
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

$messages = [];
if ($db instanceof PDO) {
    try {
        $stmt = $db->query('SELECT id, first_name, last_name, email, subject, message, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 1000');
        $messages = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Exception $e) {
        if (function_exists('app_log')) {
            app_log('warning', 'Failed to load contact messages', ['exception' => $e->getMessage()]);
        }
        set_flash('Unable to load contact messages at this time.', 'danger');
    }
}

$title = 'Contact Messages | Admin';
$DASHBOARD_HEADING = 'Contact Messages';
$DASHBOARD_VARIANT = 'admin';
$HEADER_MODE = 'dashboard';
?>
<!doctype html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo esc($title); ?></title>
    <?php require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="font-sans text-foundation-grey bg-canvas-white">
    <div class="min-h-screen flex flex-col">
        <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-3xl md:text-4xl font-serif font-bold"><?php echo esc($DASHBOARD_HEADING); ?></h1>
                    <p class="text-gray-400 mt-2 text-sm">Latest messages received via contact form</p>
                    <p class="text-[11px] mt-2 uppercase tracking-widest text-gray-300">Showing <?php echo count($messages); ?> messages</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="?export=csv" class="inline-flex items-center gap-2 bg-white text-foundation-grey px-3 py-2 rounded border hover:shadow-sm no-underline">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        Export CSV
                    </a>
                    <a href="<?php echo esc_attr(base_path('admin/content_management.php')); ?>" class="inline-flex items-center gap-2 bg-rajkot-rust text-white px-3 py-2 rounded no-underline">Admin Home</a>
                </div>
            </div>
        </header>

        <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
            <?php render_flash(); ?>

            <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex-1 pr-4">
                        <label for="searchInput" class="sr-only">Search messages</label>
                        <input id="searchInput" type="search" placeholder="Search by name, email, subject or message..." class="w-full border border-gray-200 rounded px-3 py-2" />
                    </div>
                    <div class="flex-shrink-0">
                        <span class="text-sm text-gray-500">Total: <strong class="text-foundation-grey"><?php echo count($messages); ?></strong></span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="messagesTable" class="w-full text-left text-sm table-auto border-collapse">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase border-b">
                                <th class="py-3 px-3">#</th>
                                <th class="py-3 px-3">Name</th>
                                <th class="py-3 px-3">Email</th>
                                <th class="py-3 px-3">Subject</th>
                                <th class="py-3 px-3">Message</th>
                                <th class="py-3 px-3">Received</th>
                                <th class="py-3 px-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $m):
                                $id = (int)($m['id'] ?? 0);
                                $name = trim((string)($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')) ?: '—';
                                $email = (string)($m['email'] ?? '');
                                $subject = (string)($m['subject'] ?? '');
                                $messageFull = (string)($m['message'] ?? '');
                                $excerpt = mb_strimwidth(strip_tags($messageFull), 0, 160, '...');
                            ?>
                                <tr class="border-b hover:bg-gray-50" data-id="<?php echo (int)$id; ?>">
                                    <td class="py-3 px-3 align-top"><?php echo esc($id); ?></td>
                                    <td class="py-3 px-3 align-top"><?php echo esc($name); ?></td>
                                    <td class="py-3 px-3 align-top"><a class="text-rajkot-rust no-underline" href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc($email); ?></a></td>
                                    <td class="py-3 px-3 align-top"><?php echo esc($subject); ?></td>
                                    <td class="py-3 px-3 align-top text-gray-600" style="max-width:50ch; white-space:pre-wrap"><?php echo esc($excerpt); ?></td>
                                    <td class="py-3 px-3 align-top"><?php echo esc($m['created_at']); ?></td>
                                    <td class="py-3 px-3 align-top">
                                        <div class="flex gap-2 items-center">
                                            <button type="button" class="view-btn inline-flex items-center justify-center h-10 w-10 rounded-none bg-foundation-grey text-white shadow-sm hover:shadow-premium transition-colors" data-id="<?php echo (int)$id; ?>" title="View message" aria-label="View message">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </button>

                                            <a class="inline-flex items-center justify-center h-10 w-10 rounded-none bg-approval-green text-white shadow-sm hover:shadow-premium transition-colors no-underline" href="mailto:<?php echo esc_attr($email); ?>?subject=Re:%20<?php echo rawurlencode($subject); ?>" title="Reply" aria-label="Reply">
                                                <i data-lucide="mail" class="w-4 h-4"></i>
                                            </a>

                                            <form method="post" onsubmit="return confirm('Delete this message permanently?');" style="display:inline">
                                                <?php echo csrf_token_field(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                                                <button type="submit" class="inline-flex items-center justify-center h-10 w-10 rounded-none bg-pending-amber text-white shadow-sm hover:shadow-premium transition-colors" title="Delete message" aria-label="Delete message">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <?php require_once __DIR__ . '/../Common/footer.php'; ?>
    </div>

    <!-- Message Modal -->
    <div id="msgModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded shadow-lg max-w-3xl w-full overflow-hidden">
            <div class="p-4 border-b flex items-start justify-between">
                <div>
                    <h3 id="modalSubject" class="text-lg font-bold"></h3>
                    <div id="modalMeta" class="text-sm text-gray-500"></div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="modalClose" class="text-gray-500 hover:text-gray-800">Close</button>
                </div>
            </div>
            <div class="p-4 max-h-[50vh] overflow-auto text-sm" id="modalBody"></div>
            <div class="p-4 border-t flex items-center justify-end gap-2">
                <a id="modalReply" class="inline-flex items-center justify-center h-10 w-10 rounded-none bg-rajkot-rust text-white no-underline" title="Reply" aria-label="Reply">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </a>
                <form id="modalDeleteForm" method="post" onsubmit="return confirm('Delete this message permanently?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="modalDeleteId" value="">
                    <button type="submit" class="inline-flex items-center justify-center h-10 w-10 rounded-none bg-pending-amber text-white" title="Delete message" aria-label="Delete message">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP messages to JS safely
        const MESSAGES = <?php echo json_encode(array_map(function($m){ return [
            'id' => (int)$m['id'],
            'first_name' => $m['first_name'] ?? '',
            'last_name' => $m['last_name'] ?? '',
            'email' => $m['email'] ?? '',
            'subject' => $m['subject'] ?? '',
            'message' => $m['message'] ?? '',
            'created_at' => $m['created_at'] ?? ''
        ]; }, $messages), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;

        function findMessageById(id) {
            return MESSAGES.find(m => m.id === Number(id));
        }

        document.addEventListener('DOMContentLoaded', function(){
            // Search filter
            const search = document.getElementById('searchInput');
            const table = document.getElementById('messagesTable');
            search.addEventListener('input', function(){
                const q = this.value.trim().toLowerCase();
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(r => {
                    const text = (r.textContent || '').toLowerCase();
                    r.style.display = q === '' || text.indexOf(q) !== -1 ? '' : 'none';
                });
            });

            // View modal
            const modal = document.getElementById('msgModal');
            const modalSubject = document.getElementById('modalSubject');
            const modalMeta = document.getElementById('modalMeta');
            const modalBody = document.getElementById('modalBody');
            const modalDeleteId = document.getElementById('modalDeleteId');
            const modalReply = document.getElementById('modalReply');
            const modalClose = document.getElementById('modalClose');

            function openModalFor(id) {
                const m = findMessageById(id);
                if (!m) return;
                modalSubject.textContent = m.subject || '(No subject)';
                modalMeta.textContent = (m.first_name + ' ' + m.last_name).trim() + ' · ' + m.email + ' · ' + m.created_at;
                modalBody.innerHTML = (m.message || '').replace(/\n/g, '<br>');
                modalDeleteId.value = m.id;
                modalReply.href = 'mailto:' + encodeURIComponent(m.email) + '?subject=Re:%20' + encodeURIComponent(m.subject || '');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }

            table.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', function(){
                    const id = this.getAttribute('data-id');
                    openModalFor(id);
                });
            });

            modalClose.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
        });

        if (window.lucide) window.lucide.createIcons();
    </script>
</body>
</html>
