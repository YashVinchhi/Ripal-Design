<?php

require_once dirname(__DIR__, 2) . '/app/Core/Permissions/gate.php';

if (!isset($columns) || !is_array($columns)) {
    $columns = [];
}

if (!isset($rows) || !is_array($rows)) {
    $rows = [];
}

if (!isset($options) || !is_array($options)) {
    $options = [];
}

$page = (string)($options['page'] ?? '');
$actions = array_values(array_filter((array)($options['actions'] ?? []), static function ($action) {
    return is_string($action) && $action !== '';
}));
$tableId = 'rd-table-' . substr(md5($page . '|' . implode(',', array_keys($columns))), 0, 8);

?>
<style>
    .rd-data-table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        color: var(--text-color, var(--rd-text, #1f2937));
        background: var(--surface-color, var(--rd-surface, #ffffff));
    }

    .rd-data-table caption {
        text-align: left;
        padding: 0 0 0.75rem;
        color: var(--muted-color, var(--rd-muted, #6b7280));
        font-weight: 600;
    }

    .rd-data-table thead th {
        text-align: left;
        font-weight: 700;
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--border-color, var(--rd-border, rgba(15, 23, 42, 0.12)));
        color: var(--heading-color, var(--rd-heading, #111827));
        background: var(--table-head-bg, transparent);
    }

    .rd-data-table tbody td {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--border-color, var(--rd-border, rgba(15, 23, 42, 0.08)));
        vertical-align: top;
    }

    .rd-data-table tbody tr:hover {
        background: var(--table-row-hover, rgba(148, 24, 12, 0.03));
    }

    .rd-table-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .rd-table-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border-color, var(--rd-border, rgba(15, 23, 42, 0.12)));
        background: var(--button-bg, var(--rd-button-bg, transparent));
        color: var(--button-text, var(--rd-button-text, #111827));
        padding: 0.4rem 0.75rem;
        border-radius: 0.35rem;
        font: inherit;
        cursor: pointer;
        text-decoration: none;
    }

    .rd-table-action[data-action="delete"] {
        color: var(--danger-color, var(--rd-danger, #991b1b));
        border-color: var(--danger-color, var(--rd-danger, #991b1b));
    }
</style>

<table id="<?php echo htmlspecialchars($tableId, ENT_QUOTES, 'UTF-8'); ?>" class="rd-data-table">
    <caption><?php echo htmlspecialchars((string)($options['caption'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></caption>
    <thead>
        <tr>
            <?php foreach ($columns as $label): ?>
                <th scope="col"><?php echo htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8'); ?></th>
            <?php endforeach; ?>
            <?php if ($actions !== []): ?>
                <th scope="col"><?php echo htmlspecialchars((string)($options['actions_label'] ?? 'Actions'), ENT_QUOTES, 'UTF-8'); ?></th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
            <?php if (!is_array($row)) { continue; } ?>
            <tr>
                <?php foreach (array_keys($columns) as $key): ?>
                    <td data-label="<?php echo htmlspecialchars((string)$columns[$key], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars((string)($row[$key] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                <?php endforeach; ?>

                <?php if ($actions !== []): ?>
                    <td data-label="<?php echo htmlspecialchars((string)($options['actions_label'] ?? 'Actions'), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="rd-table-actions">
                            <?php foreach ($actions as $action): ?>
                                <?php render_if($page, 'btn.' . $action, static function () use ($action): void { ?>
                                    <button type="button" class="rd-table-action" data-action="<?php echo htmlspecialchars($action, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars(ucfirst($action), ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                <?php }); ?>
                            <?php endforeach; ?>
                        </div>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
