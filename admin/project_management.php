<?php
// Project Management (Redesigned UI)
require_once __DIR__ . '/../includes/init.php';
require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_name'])) {
    require_csrf();

    $name = trim((string)($_POST['project_name'] ?? ''));
    $projectType = trim((string)($_POST['project_type'] ?? 'Residential'));
    $budget = (float)preg_replace('/[^0-9.]/', '', (string)($_POST['project_budget'] ?? '0'));
    $location = trim((string)($_POST['project_location'] ?? ''));
    $ownerName = trim((string)($_POST['client_name'] ?? ''));
    $ownerContact = trim((string)($_POST['client_contact'] ?? ''));
    $ownerEmail = trim((string)($_POST['client_email'] ?? ''));

    if ($name !== '' && db_connected()) {
        // Insert project and capture new ID so we can attach an uploaded photo
        $stmt = db_query('INSERT INTO projects (name, status, budget, progress, location, address, owner_name, owner_contact, owner_email, project_type, created_by) VALUES (?, "planning", ?, 0, ?, ?, ?, ?, ?, ?, ?)', [
            $name, $budget, $location, $location, $ownerName, $ownerContact, $ownerEmail, $projectType, $_SESSION['user']['id'] ?? null,
        ]);

        $projectId = 0;
        $pdo = get_db();
        if ($pdo instanceof PDO) {
            $projectId = (int)$pdo->lastInsertId();
        }

        // Handle optional cover photo upload
        if ($projectId > 0 && isset($_FILES['project_photo']) && is_array($_FILES['project_photo']) && (int)($_FILES['project_photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $uploaded = $_FILES['project_photo'];
            $originalName = (string)($uploaded['name'] ?? 'photo');
            $tmpPath = (string)($uploaded['tmp_name'] ?? '');
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if ($ext !== '' && in_array($ext, $allowed, true)) {
                $safeBaseName = preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                $safeBaseName = $safeBaseName !== '' ? $safeBaseName : 'photo';

                $relativeDir = 'uploads/projects/' . $projectId . '/files';
                $absoluteDir = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

                if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
                    // Directory creation failed; skip saving photo
                } else {
                    $storedName = $safeBaseName . '_' . time() . '_' . bin2hex(random_bytes(4));
                    if ($ext !== '') $storedName .= '.' . $ext;

                    $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $storedName;
                    if (move_uploaded_file($tmpPath, $absolutePath)) {
                        $publicPath = rtrim((string)BASE_PATH, '/') . '/' . $relativeDir . '/' . $storedName;
                        $sizeBytes = (int)($uploaded['size'] ?? 0);
                        if ($sizeBytes < 1024) {
                            $sizeLabel = $sizeBytes . ' B';
                        } elseif ($sizeBytes < 1024 * 1024) {
                            $sizeLabel = round($sizeBytes / 1024, 1) . ' KB';
                        } else {
                            $sizeLabel = round($sizeBytes / (1024 * 1024), 1) . ' MB';
                        }

                        db_query('INSERT INTO project_files (project_id, name, type, size, file_path, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())', [$projectId, $originalName, strtoupper($ext), $sizeLabel, $publicPath, $_SESSION['user']['username'] ?? ($_SESSION['user']['name'] ?? 'System')]);
                    }
                }
            }
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$projects = get_projects_basic(200);
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Project Management | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
  <!-- jQuery and Validation Plugin -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
  <style>
      .error { color: #94180C; font-size: 10px; font-weight: bold; text-transform: uppercase; margin-top: 4px; display: block; }
      input.error, select.error, textarea.error { border-color: #94180C !important; background-color: #FFF5F5 !important; }
  </style>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  
  <div class="min-h-screen flex flex-col">
    <!-- Unified Dark Portal Header -->
    <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-serif font-bold">Project Portfolio</h1>
                <p class="text-gray-400 mt-2 text-sm md:text-base">Executive oversight for architectural and infrastructure ventures.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <button id="exportProjectsBtn" type="button" class="w-full sm:w-auto bg-white/10 hover:bg-white/20 text-white border border-white/20 px-6 py-3 text-[10px] md:text-sm font-bold uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                    <i data-lucide="download" class="w-4 h-4 text-rajkot-rust"></i> Export Report
                </button>
                <button id="newProjectBtn" type="button" class="w-full sm:w-auto bg-rajkot-rust hover:bg-red-700 text-white px-6 py-3 text-[10px] md:text-sm font-bold uppercase tracking-widest shadow-lg transition-all flex items-center justify-center gap-2 active:scale-95">
                    <i data-lucide="plus" class="w-4 h-4"></i> New Project
                </button>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Filter Bar -->
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-6 mb-10">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Region Select:</span>
                <div class="flex gap-2 overflow-x-auto pb-2 sm:pb-0 w-full sm:w-auto no-scrollbar" id="region-filters">
                    <button onclick="filterRegion('Global')" class="px-4 py-1.5 bg-rajkot-rust text-white text-[10px] font-bold uppercase tracking-widest shadow-sm filter-btn active-filter whitespace-nowrap">Global</button>
                    <button onclick="filterRegion('Rajkot')" class="px-4 py-1.5 bg-white border border-gray-100 text-gray-500 text-[10px] font-bold uppercase tracking-widest hover:border-rajkot-rust transition-colors filter-btn whitespace-nowrap">Rajkot</button>
                    <button onclick="filterRegion('Jam Khambhalia')" class="px-4 py-1.5 bg-white border border-gray-100 text-gray-500 text-[10px] font-bold uppercase tracking-widest hover:border-rajkot-rust transition-colors filter-btn whitespace-nowrap">Jam Khambhalia</button>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto">
                <div class="relative flex-grow lg:w-64">
                    <i data-lucide="filter" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 w-4 h-4"></i>
                    <select class="w-full pl-10 pr-4 py-2.5 border border-gray-100 bg-gray-50 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-[10px] font-bold uppercase tracking-widest appearance-none cursor-pointer">
                        <option>All Statuses</option>
                        <option>Conceptual Design</option>
                        <option>Approval Pending</option>
                        <option>Construction Ongoing</option>
                        <option>Project Handover</option>
                    </select>
                </div>
                <div class="relative flex-grow lg:w-80">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 w-4 h-4"></i>
                    <input type="search" placeholder="Search Master Registry..." class="w-full pl-10 pr-4 py-2.5 border border-gray-100 bg-gray-50 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm">
                </div>
            </div>
        </div>

        <!-- Project Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($projects as $p): ?>
            <?php $pStatus = strtolower((string)($p['status'] ?? 'planning')); ?>
            <div class="project-card group bg-white border border-gray-100 shadow-premium hover:shadow-premium-hover transition-all duration-500 overflow-hidden flex flex-col" data-region="Global" data-status="<?php echo htmlspecialchars($pStatus); ?>">
                <div class="h-56 bg-foundation-grey relative overflow-hidden">
                    <?php if (!empty($p['cover_image'])): ?>
                        <img src="<?php echo htmlspecialchars((string)$p['cover_image']); ?>" alt="<?php echo htmlspecialchars((string)$p['name']); ?>" class="absolute inset-0 w-full h-full object-cover">
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-6">
                       <span class="px-3 py-1 bg-approval-green text-white text-[10px] font-bold uppercase tracking-widest mb-2 w-max shadow-lg"><?php echo htmlspecialchars(strtoupper($pStatus)); ?></span>
                       <h3 class="text-xl font-serif font-bold text-white group-hover:text-rajkot-rust transition-colors"><?php echo htmlspecialchars((string)$p['name']); ?></h3>
                    </div>
                </div>
                <div class="p-6 flex-grow">
                    <div class="flex items-center text-sm text-gray-500 mb-6">
                        <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-rajkot-rust"></i> <?php echo htmlspecialchars((string)($p['location'] ?: 'Location not set')); ?>
                    </div>
                    <div class="mb-8">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Progress</span>
                            <span class="text-sm font-bold text-rajkot-rust font-sans"><?php echo (int)($p['progress'] ?? 0); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-rajkot-rust h-full" style="width: <?php echo (int)($p['progress'] ?? 0); ?>%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between py-5 md:py-4 border-t border-gray-50">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Budget: ₹ <?php echo number_format((float)($p['budget'] ?? 0), 0, '.', ','); ?></span>
                        <a href="../dashboard/project_details.php?id=<?php echo (int)$p['id']; ?>" class="h-10 px-4 bg-gray-50 md:bg-transparent text-[10px] font-bold uppercase tracking-widest text-foundation-grey hover:text-rajkot-rust flex items-center justify-center border border-gray-100 md:border-0 rounded transition-all">Open Record</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Add Project Card -->
            <div class="border-2 border-dashed border-gray-200 p-8 flex flex-col items-center justify-center text-center group hover:border-rajkot-rust transition-colors cursor-pointer min-h-[400px]" onclick="openVentureModal()">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6 group-hover:bg-rajkot-rust transition-colors">
                    <i data-lucide="plus" class="w-10 h-10 text-gray-300 group-hover:text-white transition-colors"></i>
                </div>
                <h4 class="font-serif font-bold text-xl text-gray-400 group-hover:text-foundation-grey">Initialize Venture</h4>
                <p class="text-sm text-gray-400 mt-2 max-w-[200px]">Start a new architectural record for standard or government infrastructure.</p>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>

    <!-- Initialize Venture Modal -->
    <div id="ventureModal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[100] hidden items-center justify-center p-4">
        <div class="bg-white max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border-b-4 border-rajkot-rust">
            <div class="px-10 py-8 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
                <div>
                    <h2 class="text-2xl font-serif font-bold text-foundation-grey">Initialize New Venture</h2>
                    <p class="text-xs text-gray-400 uppercase tracking-widest mt-1">Registry Entry • Part II-B (Standard)</p>
                </div>
                <button onclick="closeVentureModal()" class="text-gray-400 hover:text-rajkot-rust transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <form method="post" enctype="multipart/form-data" class="p-10 space-y-8" id="ventureForm" onsubmit="handleVentureSubmit(event)">
                <?php echo csrf_token_field(); ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Client Details -->
                    <div class="space-y-4">
                        <h3 class="text-[10px] font-bold uppercase tracking-widest text-rajkot-rust border-b border-rajkot-rust/20 pb-2">I. Client Identity</h3>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Legal Full Name</label>
                            <input type="text" name="client_name" required class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Contact Primary (Mobile / Official)</label>
                            <input type="tel" name="client_contact" required class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Email Address</label>
                            <input type="email" name="client_email" required class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                        </div>
                    </div>

                    <!-- Project Details -->
                    <div class="space-y-4">
                        <h3 class="text-[10px] font-bold uppercase tracking-widest text-rajkot-rust border-b border-rajkot-rust/20 pb-2">II. Project Scope</h3>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Project Designation (Name)</label>
                            <input type="text" name="project_name" required placeholder="e.g. Skyline Apartments — Tower A" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Type</label>
                                <select name="project_type" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                                    <option>Residential</option>
                                    <option>Commercial</option>
                                    <option>Industrial</option>
                                    <option>Infrastructure</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Estimated Budget</label>
                                <input type="text" name="project_budget" placeholder="₹" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Site Location (Rajkot/Gujarat)</label>
                            <textarea name="project_location" rows="2" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Project Cover Photo (optional)</label>
                            <input type="file" name="project_photo" accept="image/*" class="w-full bg-gray-50 border border-gray-200 p-2 outline-none focus:border-rajkot-rust text-sm">
                        </div>
                    </div>
                </div>

                <div class="pt-6 flex flex-col md:flex-row justify-end gap-4 border-t border-gray-100">
                    <button type="button" onclick="closeVentureModal()" class="w-full md:w-auto px-8 py-4 md:py-3 text-gray-400 font-bold uppercase tracking-widest text-[10px] hover:text-foundation-grey transition-colors border border-gray-100 md:border-0">Abort Initialization</button>
                    <button type="submit" class="w-full md:w-auto bg-rajkot-rust text-white px-10 py-4 md:py-3 font-bold uppercase tracking-widest text-[10px] shadow-premium hover:bg-foundation-grey transition-all">Initialize Construction Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // jQuery Validation for the Venture Form
            $("#ventureForm").validate({
                rules: {
                    client_name: "required",
                    client_contact: {
                        required: true,
                        minlength: 10
                    },
                    client_email: {
                        required: true,
                        email: true
                    },
                    project_name: "required",
                    project_budget: "required",
                    project_location: "required"
                },
                messages: {
                    client_name: "Please enter the legal name",
                    client_contact: "Valid contact number required",
                    client_email: "Valid registry email required",
                    project_name: "Project designation is mandatory",
                    project_budget: "Estimated budget required",
                    project_location: "Site location must be specified"
                },
                submitHandler: function(form) {
                    form.submit();
                }
            });
        });

        let dashboardState = {
            region: 'Global',
            status: 'All Statuses'
        };

        function filterRegion(region) {
            dashboardState.region = region;
            
            // UI updates for buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('bg-rajkot-rust', 'text-white', 'active-filter');
                btn.classList.add('bg-white', 'text-gray-500', 'border-gray-100');
            });
            
            const activeBtn = event.currentTarget;
            activeBtn.classList.add('bg-rajkot-rust', 'text-white', 'active-filter');
            activeBtn.classList.remove('bg-white', 'text-gray-500', 'border-gray-100');
            
            applyAllFilters();
        }

        function filterStatus(status) {
            dashboardState.status = status;
            applyAllFilters();
        }

        function applyAllFilters() {
            const cards = document.querySelectorAll('.project-card');
            cards.forEach(card => {
                const cardRegion = card.getAttribute('data-region');
                const cardStatus = card.getAttribute('data-status');
                
                let matchesRegion = (dashboardState.region === 'Global' || cardRegion === dashboardState.region);
                let matchesStatus = (dashboardState.status === 'All Statuses' || cardStatus === dashboardState.status);
                
                if (matchesRegion && matchesStatus) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        // Attach event listener to status dropdown
        document.querySelector('select').addEventListener('change', function(e) {
            filterStatus(e.target.value);
        });

        function openVentureModal() {
            const modal = document.getElementById('ventureModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeVentureModal() {
            const modal = document.getElementById('ventureModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function handleVentureSubmit(e) {
            // Handled by jQuery Validation's submitHandler
            return false;
        }

        document.getElementById('newProjectBtn').addEventListener('click', function () {
            openVentureModal();
        });

        document.getElementById('exportProjectsBtn').addEventListener('click', function () {
            const rows = [['Project', 'Status', 'Location', 'Progress', 'Budget']];
            <?php foreach ($projects as $p): ?>
            rows.push([
                <?php echo json_encode((string)($p['name'] ?? '')); ?>,
                <?php echo json_encode((string)($p['status'] ?? '')); ?>,
                <?php echo json_encode((string)($p['location'] ?? '')); ?>,
                <?php echo json_encode((string)($p['progress'] ?? 0)); ?>,
                <?php echo json_encode((string)($p['budget'] ?? 0)); ?>
            ]);
            <?php endforeach; ?>

            const csv = rows.map(r => r.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'projects_report.csv';
            a.click();
            URL.revokeObjectURL(url);
        });

        // Close on backdrop click
        document.getElementById('ventureModal').addEventListener('click', function(e) {
            if (e.target === this) closeVentureModal();
        });
    </script>
  </div>

</body>
</html>