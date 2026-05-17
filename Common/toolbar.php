<?php
/**
 * Page-aware toolbar loader
 * Renders admin toolbar for specific pages (e.g. admin/project_management.php)
 */

// Determine current script name
$_scriptName = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');

if (!function_exists('is_logged_in') || !is_logged_in()) {
    return;
}

// Pages must explicitly opt-in to show the common toolbar. To enable,
// set `$ENABLE_COMMON_TOOLBAR = true` in the page before including the header.
// This prevents the toolbar from appearing on every page unintentionally.
if (empty($ENABLE_COMMON_TOOLBAR)) {
    return;
}

$cuRole = '';
if (function_exists('current_user')) {
    $cu = current_user();
    $cuRole = is_array($cu) ? strtolower(trim((string)($cu['role'] ?? ''))) : '';
}

// Only render toolbar for admin on project_management.php
if ($cuRole !== 'admin') {
    return;
}

$headerModeLocal = $headerMode ?? ($HEADER_MODE ?? '');
if ($headerModeLocal !== 'dashboard') {
    return;
}

// Render toolbar for admin dashboard pages. Previously this file only
// rendered for a small whitelist of pages; expand to provide a centralized
// toolbar for all admin dashboard pages while preserving page-specific
// variants below.

?>
<?php if ($_scriptName === 'project_management.php'): ?>
<!-- Admin toolbar (moved to Common/toolbar.php) -->
<div class="alt-toolbar" role="region" aria-label="Admin tools">
    <div class="toolbar-group toolbar-status ml-4">
        <label class="toolbar-label">Status</label>
        <select id="projectStatusFilterHeader" class="styled-select" aria-label="Project status (header)">
            <option value="all">All Statuses</option>
            <option value="planning">Conceptual Design</option>
            <option value="paused">Approval Pending</option>
            <option value="ongoing">Construction Ongoing</option>
            <option value="completed">Project Handover</option>
        </select>
    </div>

    <div class="toolbar-group toolbar-search ml-4 flex-1">
        <i class="fa-solid fa-magnifying-glass absolute-search-icon" aria-hidden="true"></i>
        <input id="projectSearchHeader" type="search" placeholder="Search Master Registry..." class="styled-input" aria-label="Search projects (header)">
    </div>

    <div class="toolbar-actions ml-4" role="group" aria-label="Project actions">
        <button id="addProjectBtn" type="button" class="toolbar-btn" title="Add New Project"><i class="fa-solid fa-plus" aria-hidden="true"></i>&nbsp;New</button>
        <button id="editProjectBtn" type="button" class="toolbar-btn" title="Edit Project"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>&nbsp;Edit</button>
        <button id="deleteProjectBtn" type="button" class="toolbar-btn text-danger" title="Delete Project"><i class="fa-solid fa-trash" aria-hidden="true"></i>&nbsp;Delete</button>

        <div class="toolbar-region-select" style="margin-left:8px;">
            <label for="regionSelect" class="toolbar-label" style="margin-right:6px;">Region</label>
            <select id="regionSelect" class="styled-select" aria-label="Select region">
                <option value="global">Global</option>
                <option value="rajkot">Rajkot</option>
                <option value="kham" selected>Jam Khambhalia</option>
            </select>
        </div>

        <button id="selectAllBtn" type="button" class="toolbar-btn" title="Select All"><i class="fa-solid fa-check-square" aria-hidden="true"></i>&nbsp;Select All</button>

        <div class="filter-sort" style="position:relative;">
            <button id="filterSortBtn" type="button" class="toolbar-btn" aria-expanded="false" aria-controls="filterSortPanel" title="Filter & Sort"><i class="fa-solid fa-filter" aria-hidden="true"></i>&nbsp;Filter / Sort</button>
            <div id="filterSortPanel" class="filter-panel" role="dialog" aria-hidden="true" style="position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid rgba(15,23,42,0.06);box-shadow:0 6px 18px rgba(16,24,40,0.06);padding:12px;border-radius:6px;min-width:240px;display:none;z-index:10000;">
                <div style="margin-bottom:8px;font-weight:600">Filter</div>
                <label style="display:block;margin-bottom:6px">Progress
                    <select id="filterProgress" class="styled-select" style="width:100%;margin-top:6px">
                        <option value="any">Any</option>
                        <option value="0-25">0 - 25%</option>
                        <option value="25-50">25 - 50%</option>
                        <option value="50-75">50 - 75%</option>
                        <option value="75-100">75 - 100%</option>
                    </select>
                </label>
                <label style="display:block;margin-bottom:6px">Worker
                    <input id="filterWorker" type="text" class="styled-input" placeholder="Worker name" style="width:100%;margin-top:6px">
                </label>
                <label style="display:block;margin-bottom:6px">Location
                    <input id="filterLocation" type="text" class="styled-input" placeholder="Location" style="width:100%;margin-top:6px">
                </label>
                <div style="margin-top:10px; border-top:1px solid rgba(15,23,42,0.03); padding-top:10px">
                    <div style="font-weight:600;margin-bottom:6px">Sort by</div>
                    <select id="sortBy" class="styled-select" style="width:100%">
                        <option value="progress_desc">Progress (High → Low)</option>
                        <option value="progress_asc">Progress (Low → High)</option>
                        <option value="location_asc">Location (A → Z)</option>
                        <option value="worker_asc">Worker (A → Z)</option>
                    </select>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px">
                    <button type="button" id="applyFiltersBtn" class="toolbar-btn">Apply</button>
                    <button type="button" id="clearFiltersBtn" class="toolbar-btn">Clear</button>
                </div>
            </div>
        </div>
    </div>
    </div>
<?php endif; ?>

<?php
// If we're on user_management.php render a user toolbar variant
if ($_scriptName === 'user_management.php'):
?>
<!-- Admin toolbar for User Management (page-styled, not fixed) -->
<div class="user-management-toolbar bg-white shadow-premium border border-gray-100 p-4 md:p-6 mb-8 flex flex-col lg:flex-row justify-between items-center gap-4 md:gap-6" role="region" aria-label="User management tools">
    <div class="flex items-center gap-3">
        <button id="addUserBtn" type="button" class="bg-rajkot-rust text-white px-4 py-2 rounded font-bold text-sm" title="Add New User" onclick="location.href='add_user.php'">
            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>&nbsp;Add User
        </button>
        <button id="addTempUserBtn" type="button" class="bg-foundation-grey text-white px-4 py-2 rounded font-bold text-sm" title="Add Temp User" onclick="location.href='provision_temp_user.php'">
            <i class="fa-solid fa-user-clock" aria-hidden="true"></i>&nbsp;Temp User
        </button>
    </div>
    <div class="relative flex-1">
        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
        <input id="userSearchHeader" type="search" placeholder="Search users..." class="w-full pl-12 pr-6 py-3 md:py-4 bg-gray-50 border border-gray-50 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium" value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    </div>

    <div class="flex items-center gap-3 w-auto">
        <select id="filterRoleHeader" class="py-3 md:py-4 px-4 bg-gray-50 border border-gray-50 text-[12px] font-bold uppercase tracking-widest outline-none focus:bg-white focus:border-rajkot-rust transition-all cursor-pointer">
            <option value="all" <?php echo (isset($role) && $role === 'all') ? 'selected' : ''; ?>>All Roles</option>
            <option value="admin" <?php echo (isset($role) && $role === 'admin') ? 'selected' : ''; ?>>Administrators</option>
            <option value="employee" <?php echo (isset($role) && $role === 'employee') ? 'selected' : ''; ?>>Employees</option>
            <option value="worker" <?php echo (isset($role) && $role === 'worker') ? 'selected' : ''; ?>>Field Tech</option>
            <option value="client" <?php echo (isset($role) && $role === 'client') ? 'selected' : ''; ?>>Clients</option>
        </select>
        <button id="applyFiltersBtn" type="button" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-3 text-[10px] font-bold uppercase tracking-[0.2em] transition-all flex items-center justify-center shadow-lg">Apply</button>
        <button id="clearFiltersBtn" type="button" class="bg-gray-50 text-gray-600 px-4 py-3 text-[10px] font-bold uppercase tracking-[0.2em] transition-all">Clear</button>
    </div>
</div>

<?php
endif; // user_management.php block

// If we're on payment_gateway.php render a billing-specific toolbar variant
if ($_scriptName === 'payment_gateway.php'):
?>
<!-- Admin toolbar for Payment/Gateway (billing tools) -->
<div class="payment-toolbar user-management-toolbar bg-white shadow-premium border border-gray-100 p-4 md:p-6 mb-8 flex flex-col lg:flex-row justify-between items-center gap-4 md:gap-6" role="region" aria-label="Billing tools">
    <div class="flex items-center gap-3">
        <button id="createInvoiceBtn" type="button" class="bg-rajkot-rust text-white px-4 py-2 rounded font-bold text-sm" title="Create Invoice">
            <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>&nbsp;Create invoice
        </button>
        <button id="recordPaymentBtn" type="button" class="bg-foundation-grey text-white px-4 py-2 rounded font-bold text-sm" title="Record Payment">
            <i class="fa-solid fa-receipt" aria-hidden="true"></i>&nbsp;Record payment
        </button>
        <button id="sendInvoiceEmailBtn" type="button" class="bg-foundation-grey text-white px-4 py-2 rounded font-bold text-sm" title="Send Invoice">
            <i class="fa-solid fa-envelope" aria-hidden="true"></i>&nbsp;Send invoice
        </button>
    </div>
    <div class="relative flex-1">
        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
        <input id="projectSearchHeader" type="search" placeholder="Search project..." class="w-full pl-12 pr-6 py-3 md:py-4 bg-gray-50 border border-gray-50 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium" aria-label="Search projects (header)">
    </div>
    <div class="flex items-center gap-3 w-auto">
        <button id="exportCsvBtn" type="button" class="bg-gray-50 text-gray-600 px-4 py-3 text-[10px] font-bold uppercase tracking-[0.2em] transition-all">Export CSV</button>
    </div>
</div>

<?php
endif; // payment_gateway.php block
// Generic fallback toolbar for other admin dashboard pages
// Do not render the generic fallback for pages that have specific toolbar variants
if (!in_array($_scriptName, ['project_management.php','user_management.php','payment_gateway.php'], true)):
?>
<div class="alt-toolbar" role="region" aria-label="Admin tools">
    <div class="toolbar-group toolbar-search ml-4 flex-1">
        <i class="fa-solid fa-magnifying-glass absolute-search-icon" aria-hidden="true"></i>
        <input id="projectSearchHeader" type="search" placeholder="Search..." class="styled-input" aria-label="Search (header)">
    </div>

    <div class="toolbar-actions ml-4" role="group" aria-label="Admin actions">
        <button id="addProjectBtn" type="button" class="toolbar-btn" title="Add"><i class="fa-solid fa-plus" aria-hidden="true"></i>&nbsp;New</button>
        <button id="editProjectBtn" type="button" class="toolbar-btn" title="Edit"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>&nbsp;Edit</button>
        <button id="deleteProjectBtn" type="button" class="toolbar-btn text-danger" title="Delete"><i class="fa-solid fa-trash" aria-hidden="true"></i>&nbsp;Delete</button>
        <div class="toolbar-region-select" style="margin-left:8px;">
            <label for="regionSelect" class="toolbar-label" style="margin-right:6px;">Region</label>
            <select id="regionSelect" class="styled-select" aria-label="Select region">
                <option value="global">Global</option>
                <option value="rajkot">Rajkot</option>
                <option value="kham">Jam Khambhalia</option>
            </select>
        </div>
    </div>
</div>
<?php
endif;
// End toolbar.php
?>

<script>
    (function(){
        function initToolbarHandlers(){
            try {
                var createBtn = document.getElementById('createInvoiceBtn');
                if (createBtn) {
                    createBtn.addEventListener('click', function(){
                        var sel = document.querySelector('select[name="project_id"]');
                        if (sel) { sel.focus(); sel.scrollIntoView({behavior:'smooth', block:'center'}); }
                    });
                }

                var recordBtn = document.getElementById('recordPaymentBtn');
                if (recordBtn) {
                    recordBtn.addEventListener('click', function(){
                        var paymentsTable = document.querySelector('table.payments-table');
                        if (paymentsTable) { paymentsTable.scrollIntoView({behavior:'smooth', block:'center'}); }
                    });
                }

                var exportBtn = document.getElementById('exportCsvBtn');
                if (exportBtn) {
                    exportBtn.addEventListener('click', function(){
                        var u = new URL(window.location.href);
                        u.searchParams.set('export', 'csv');
                        window.location.href = u.toString();
                    });
                }

                var searchInput = document.getElementById('projectSearchHeader');
                if (searchInput) {
                    searchInput.addEventListener('keydown', function(e){
                        if (e.key === 'Enter') {
                            var q = encodeURIComponent(searchInput.value || '');
                            var u = new URL(window.location.href);
                            u.searchParams.set('project_search', q);
                            window.location.href = u.toString();
                        }
                    });
                }
            } catch (e) {
                // non-fatal; toolbar handlers are best-effort
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initToolbarHandlers);
        } else {
            initToolbarHandlers();
        }
    })();
</script>
