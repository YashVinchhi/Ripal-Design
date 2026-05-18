<?php

require_once dirname(__DIR__, 2) . '/app/Core/Bootstrap/init.php';

require_login();
gate('settings', 'panel.permissions', 'edit');

$pageTitle = 'UI Permission Manager';
$roles = ['admin', 'employee', 'worker', 'client'];
$roleLabels = [
    'admin' => 'Admin',
    'employee' => 'Employee',
    'worker' => 'Worker',
    'client' => 'Client',
];

$permissionsByCombo = [];
$combos = [];

if (db_connected() && db_table_exists('ui_permissions')) {
    $rows = db_fetch_all(
        'SELECT page_key, component_key, role, can_view, can_edit, can_delete
           FROM ui_permissions
       ORDER BY page_key ASC, component_key ASC, role ASC'
    );

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $pageKey = (string)($row['page_key'] ?? '');
        $componentKey = (string)($row['component_key'] ?? '');
        $role = (string)($row['role'] ?? '');
        if ($pageKey === '' || $componentKey === '' || $role === '') {
            continue;
        }

        $comboKey = $pageKey . '::' . $componentKey;
        $combos[$comboKey] = ['page_key' => $pageKey, 'component_key' => $componentKey];
        $permissionsByCombo[$comboKey][$role] = [
            'can_view' => (int)($row['can_view'] ?? 0),
            'can_edit' => (int)($row['can_edit'] ?? 0),
            'can_delete' => (int)($row['can_delete'] ?? 0),
        ];
    }
}

require_once dirname(__DIR__, 2) . '/Common/layout/header.php';
?>
<style>
    .rd-permissions-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--surface-color, #fff);
    }

    .rd-permissions-table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: var(--surface-color, #fff);
        text-align: left;
        padding: 1rem;
        border-bottom: 1px solid var(--border-color, rgba(15, 23, 42, 0.12));
        color: var(--heading-color, #111827);
    }

    .rd-permissions-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color, rgba(15, 23, 42, 0.08));
        vertical-align: top;
    }

    .rd-permissions-combo {
        min-width: 16rem;
    }

    .rd-permission-role {
        min-width: 16rem;
    }

    .rd-permission-box {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.4rem;
    }

    .rd-permission-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.5rem;
        border: 1px solid var(--border-color, rgba(15, 23, 42, 0.12));
        border-radius: 0.45rem;
        background: var(--surface-muted, rgba(15, 23, 42, 0.02));
        font-size: 0.75rem;
        font-weight: 600;
    }

    .rd-permission-pill input {
        margin: 0;
    }

    .rd-permission-save {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.9rem 1.25rem;
        border: 0;
        background: var(--accent-color, #94180C);
        color: #fff;
        font: inherit;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        cursor: pointer;
    }

    .rd-permission-status {
        margin-top: 1rem;
        font-size: 0.95rem;
    }
</style>

<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8">
        <div class="flex items-start justify-between gap-6 flex-wrap mb-6">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400 mb-2">Security Settings</p>
                <h1 class="text-3xl md:text-4xl font-serif font-bold text-foundation-grey">UI Permission Manager</h1>
                <p class="text-sm text-gray-500 mt-2">Edit view, edit, and delete permissions for each page/component and role.</p>
            </div>
            <div class="text-xs uppercase tracking-[0.2em] text-gray-400 font-bold">
                Matrix driven by <span class="text-foundation-grey">ui_permissions</span>
            </div>
        </div>

        <form id="permissionsForm" method="post" action="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/api/permissions-update.php', ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo csrf_token_field(); ?>

            <?php if (empty($combos)): ?>
                <div class="border border-gray-100 bg-gray-50 p-6 text-gray-500">No permission rows are available yet.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="rd-permissions-table">
                        <thead>
                            <tr>
                                <th scope="col">Page / Component</th>
                                <?php foreach ($roles as $role): ?>
                                    <th scope="col"><?php echo htmlspecialchars($roleLabels[$role], ENT_QUOTES, 'UTF-8'); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($combos as $combo): ?>
                                <?php
                                    $comboKey = $combo['page_key'] . '::' . $combo['component_key'];
                                ?>
                                <tr>
                                    <td class="rd-permissions-combo">
                                        <div class="font-bold text-foundation-grey"><?php echo htmlspecialchars($combo['page_key'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-sm text-gray-500 font-mono"><?php echo htmlspecialchars($combo['component_key'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <?php foreach ($roles as $role): ?>
                                        <?php $perm = $permissionsByCombo[$comboKey][$role] ?? ['can_view' => 0, 'can_edit' => 0, 'can_delete' => 0]; ?>
                                        <td class="rd-permission-role">
                                            <div class="rd-permission-box">
                                                <label class="rd-permission-pill">
                                                    <input
                                                        type="checkbox"
                                                        data-permission-checkbox
                                                        data-page-key="<?php echo htmlspecialchars($combo['page_key'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-component-key="<?php echo htmlspecialchars($combo['component_key'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-role="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-action="view"
                                                        <?php echo !empty($perm['can_view']) ? 'checked' : ''; ?>
                                                    >
                                                    View
                                                </label>
                                                <label class="rd-permission-pill">
                                                    <input
                                                        type="checkbox"
                                                        data-permission-checkbox
                                                        data-page-key="<?php echo htmlspecialchars($combo['page_key'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-component-key="<?php echo htmlspecialchars($combo['component_key'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-role="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-action="edit"
                                                        <?php echo !empty($perm['can_edit']) ? 'checked' : ''; ?>
                                                    >
                                                    Edit
                                                </label>
                                                <label class="rd-permission-pill">
                                                    <input
                                                        type="checkbox"
                                                        data-permission-checkbox
                                                        data-page-key="<?php echo htmlspecialchars($combo['page_key'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-component-key="<?php echo htmlspecialchars($combo['component_key'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-role="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-action="delete"
                                                        <?php echo !empty($perm['can_delete']) ? 'checked' : ''; ?>
                                                    >
                                                    Delete
                                                </label>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="flex items-center justify-between gap-4 flex-wrap mt-6">
                <p id="permissionsStatus" class="rd-permission-status text-gray-500">Ready to save permission updates.</p>
                <button type="submit" class="rd-permission-save">Save Changes</button>
            </div>
        </form>
    </section>
</main>

<script>
(function () {
    const form = document.getElementById('permissionsForm');
    const status = document.getElementById('permissionsStatus');
    const csrfInput = form ? form.querySelector('input[name="csrf_token"]') : null;

    if (!form || !status || !csrfInput) {
        return;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const grouped = new Map();
        form.querySelectorAll('[data-permission-checkbox]').forEach(function (checkbox) {
            const pageKey = checkbox.getAttribute('data-page-key') || '';
            const componentKey = checkbox.getAttribute('data-component-key') || '';
            const role = checkbox.getAttribute('data-role') || '';
            const action = checkbox.getAttribute('data-action') || '';
            if (!pageKey || !componentKey || !role || !action) {
                return;
            }

            const mapKey = pageKey + '::' + componentKey + '::' + role;
            if (!grouped.has(mapKey)) {
                grouped.set(mapKey, {
                    page_key: pageKey,
                    component_key: componentKey,
                    role: role,
                    can_view: 0,
                    can_edit: 0,
                    can_delete: 0
                });
            }

            const entry = grouped.get(mapKey);
            entry['can_' + action] = checkbox.checked ? 1 : 0;
        });

        const permissions = Array.from(grouped.values());
        status.textContent = 'Saving...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfInput.value
                },
                body: JSON.stringify({ permissions: permissions })
            });

            const payload = await response.json().catch(function () {
                return null;
            });

            if (!response.ok || !payload || payload.error) {
                status.textContent = (payload && payload.error) ? payload.error : 'Unable to save permissions.';
                return;
            }

            status.textContent = 'Saved ' + (payload.updated || 0) + ' permission rows.';
        } catch (error) {
            status.textContent = 'Unable to save permissions.';
        }
    });
})();
</script>

<?php require_once dirname(__DIR__, 2) . '/Common/layout/footer.php'; ?>
