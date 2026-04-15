<?php
require_once __DIR__ . '/../includes/init.php';
require_login();

$requestPageUrl = BASE_PATH . PUBLIC_PATH_PREFIX . '/request_leave.php';
$formErrors = [];
$formData = [
    'leave_type' => '',
    'start_date' => '',
    'end_date' => '',
    'reason' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave_request'])) {
    require_csrf();

    $formData['leave_type'] = trim((string)($_POST['leave_type'] ?? ''));
    $formData['start_date'] = trim((string)($_POST['start_date'] ?? ''));
    $formData['end_date'] = trim((string)($_POST['end_date'] ?? ''));
    $formData['reason'] = trim((string)($_POST['reason'] ?? ''));

    $leaveType = $formData['leave_type'];
    $startDate = $formData['start_date'];
    $endDate = $formData['end_date'];
    $reason = $formData['reason'];
    $userId = current_user_id();

    if ($userId <= 0) {
        $formErrors[] = 'Please log in again to submit a leave request.';
    }
    if ($leaveType === '' || strlen($leaveType) > 100) {
        $formErrors[] = 'Please select a valid leave type.';
    }
    if ($startDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $formErrors[] = 'Please provide a valid start date.';
    }
    if ($endDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $formErrors[] = 'Please provide a valid end date.';
    }
    if ($startDate !== '' && $endDate !== '' && strtotime($startDate) > strtotime($endDate)) {
        $formErrors[] = 'End date cannot be earlier than start date.';
    }
    if ($reason === '' || strlen($reason) > 1000) {
        $formErrors[] = 'Please provide a reason up to 1000 characters.';
    }

    if (empty($formErrors) && (!db_connected() || !db_table_exists('leave_requests'))) {
        $formErrors[] = 'Leave request service is currently unavailable.';
    }

    if (empty($formErrors)) {
        db_query(
            'INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status, approved_by) VALUES (?, ?, ?, ?, ?, ?, NULL)',
            [$userId, $leaveType, $startDate, $endDate, $reason, 'pending']
        );
        header('Location: ' . $requestPageUrl . '?notice=created');
        exit;
    }
}

$myLeaves = [];
if (db_connected() && db_table_exists('leave_requests')) {
    $myLeaves = db_fetch_all(
        'SELECT id, leave_type, start_date, end_date, reason, status, requested_at FROM leave_requests WHERE user_id = ? ORDER BY requested_at DESC LIMIT 20',
        [current_user_id()]
    );
}

$leaveTypes = ['Sick Leave', 'Casual Leave', 'Earned Leave'];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Leave | Ripal Design</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'rajkot-rust': '#94180C',
              'canvas-white': '#F9FAFB',
              'foundation-grey': '#2D2D2D',
            },
            fontFamily: {
              sans: ['Inter', 'sans-serif'],
              serif: ['Cormorant Garamond', 'serif'],
            }
          }
        }
      }
    </script>
</head>
<body class="bg-[#050505] text-white overflow-x-hidden font-sans">
    <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="min-h-screen pt-28 pb-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-xs uppercase tracking-[0.2em] text-rajkot-rust">Employee Portal</p>
                <h1 class="text-4xl md:text-5xl font-serif mt-3">Request Leave</h1>
                <p class="text-gray-400 mt-2">Submit your leave request here. Admin can approve or reject it from Leave Management.</p>
            </div>

            <?php if (isset($_GET['notice']) && $_GET['notice'] === 'created'): ?>
                <div class="mb-6 rounded border border-emerald-300 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                    Your leave request has been submitted successfully.
                </div>
            <?php endif; ?>

            <?php if (!empty($formErrors)): ?>
                <div class="mb-6 rounded border border-red-300 bg-red-50 px-4 py-3 text-red-800 text-sm">
                    <p class="font-semibold mb-1">Unable to submit leave request:</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <?php foreach ($formErrors as $formError): ?>
                            <li><?php echo htmlspecialchars($formError); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-1 rounded-lg border border-gray-200 bg-white p-6 text-foundation-grey shadow-sm">
                    <h2 class="text-xl font-semibold text-foundation-grey">New Request</h2>
                    <form method="post" action="<?php echo htmlspecialchars($requestPageUrl, ENT_QUOTES, 'UTF-8'); ?>" class="mt-5 space-y-4">
                        <?php echo csrf_token_field(); ?>
                        <input type="hidden" name="submit_leave_request" value="1">

                        <div>
                            <label for="leave_type" class="block text-xs uppercase tracking-wider text-gray-500 mb-2">Leave Type</label>
                            <select id="leave_type" name="leave_type" required class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-foundation-grey focus:outline-none focus:ring-2 focus:ring-rajkot-rust/40">
                                <option value="">Select leave type</option>
                                <?php foreach ($leaveTypes as $leaveTypeOption): ?>
                                    <option value="<?php echo htmlspecialchars($leaveTypeOption); ?>" <?php echo $formData['leave_type'] === $leaveTypeOption ? 'selected' : ''; ?>><?php echo htmlspecialchars($leaveTypeOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="start_date" class="block text-xs uppercase tracking-wider text-gray-500 mb-2">Start Date</label>
                            <input id="start_date" name="start_date" type="date" required value="<?php echo htmlspecialchars($formData['start_date']); ?>" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-foundation-grey focus:outline-none focus:ring-2 focus:ring-rajkot-rust/40">
                        </div>

                        <div>
                            <label for="end_date" class="block text-xs uppercase tracking-wider text-gray-500 mb-2">End Date</label>
                            <input id="end_date" name="end_date" type="date" required value="<?php echo htmlspecialchars($formData['end_date']); ?>" class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-foundation-grey focus:outline-none focus:ring-2 focus:ring-rajkot-rust/40">
                        </div>

                        <div>
                            <label for="reason" class="block text-xs uppercase tracking-wider text-gray-500 mb-2">Reason</label>
                            <textarea id="reason" name="reason" rows="4" maxlength="1000" required class="w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm text-foundation-grey placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-rajkot-rust/40" placeholder="Explain your leave request."><?php echo htmlspecialchars($formData['reason']); ?></textarea>
                        </div>

                        <button type="submit" class="w-full h-11 rounded bg-rajkot-rust text-white text-sm font-semibold uppercase tracking-wider hover:bg-[#7e130a] transition">
                            Submit Request
                        </button>
                    </form>
                </div>

                <div class="lg:col-span-2 rounded-lg border border-gray-200 bg-white p-6 text-foundation-grey shadow-sm">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h2 class="text-xl font-semibold text-foundation-grey">My Recent Requests</h2>
                        <a href="<?php echo BASE_PATH; ?>/admin/leave_management.php" class="text-sm text-rajkot-rust hover:text-[#c54233] transition">Go to Leave Management</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="text-gray-500 border-b border-gray-200">
                                    <th class="py-3 pr-4">Type</th>
                                    <th class="py-3 pr-4">Dates</th>
                                    <th class="py-3 pr-4">Reason</th>
                                    <th class="py-3 pr-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($myLeaves)): ?>
                                    <tr>
                                        <td colspan="4" class="py-6 text-gray-500">No leave requests yet.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($myLeaves as $leave): ?>
                                    <?php
                                        $status = strtolower((string)($leave['status'] ?? 'pending'));
                                        $statusClass = 'bg-amber-500/15 text-amber-300 border border-amber-400/30';
                                        if ($status === 'approved') {
                                            $statusClass = 'bg-emerald-500/15 text-emerald-300 border border-emerald-400/30';
                                        } elseif ($status === 'rejected') {
                                            $statusClass = 'bg-red-500/15 text-red-300 border border-red-400/30';
                                        } elseif ($status === 'on_leave') {
                                            $statusClass = 'bg-sky-500/15 text-sky-300 border border-sky-400/30';
                                        }
                                    ?>
                                    <tr class="border-b border-gray-100 align-top">
                                        <td class="py-3 pr-4"><?php echo htmlspecialchars((string)($leave['leave_type'] ?? '')); ?></td>
                                        <td class="py-3 pr-4"><?php echo htmlspecialchars((string)($leave['start_date'] ?? '')); ?> to <?php echo htmlspecialchars((string)($leave['end_date'] ?? '')); ?></td>
                                        <td class="py-3 pr-4 text-gray-700 max-w-md"><?php echo htmlspecialchars((string)($leave['reason'] ?? '')); ?></td>
                                        <td class="py-3 pr-4">
                                            <span class="inline-flex px-2.5 py-1 rounded text-[11px] uppercase tracking-wide <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>
