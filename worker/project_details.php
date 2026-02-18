<?php
require_once __DIR__ . '/../includes/init.php';

// Mock data for demonstration - in production this would come from DB based on $_GET['id']
$project = [
    'id' => 101,
    'name' => 'Renovation — Oak Street Residence',
    'status' => 'ongoing',
    'address' => '123 Oak St, Rajkot, Gujarat',
    'lat' => 22.3039, 
    'lng' => 70.8022, // Rajkot coords
    'area' => '2,400 sq. ft.',
    'budget' => '₹ 45,00,000',
    'owner' => [
        'name' => 'Amitbhai Patel',
        'contact' => '+91 98765 43210'
    ],
    'workers' => [
        ['role' => 'Plumber', 'name' => 'Ramesh Kumar', 'contact' => '+91 98989 89898'],
        ['role' => 'Electrician', 'name' => 'Suresh Bhai', 'contact' => '+91 97979 79797'],
        ['role' => 'Carpenter', 'name' => 'Mahesh M.', 'contact' => '+91 96969 69696'],
    ],
    'goods' => [
        ['item' => 'Cement Bags (Ultratech)', 'qty' => '50 bags', 'status' => 'Delivered'],
        ['item' => 'Teak Wood Logs', 'qty' => '200 cft', 'status' => 'Pending'],
        ['item' => 'Ceramic Tiles (2x2)', 'qty' => '150 boxes', 'status' => 'Ordered'],
    ],
    'drawings' => [
        ['title' => 'Ground Floor Plan', 'type' => 'pdf', 'date' => '2025-01-15', 'status' => 'construction_issued'],
        ['title' => 'Electrical Layout', 'type' => 'pdf', 'date' => '2025-01-20', 'status' => 'construction_issued'],
        ['title' => 'Plumbing Diagram', 'type' => 'img', 'date' => '2025-01-22', 'status' => 'construction_issued'],
    ]
];
?>
<!doctype html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($project['name']); ?> | Ripal Design</title>
    <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; 
    
    // Handle review request submission
    $request_sent = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_details'])) {
        $request_sent = true;
    }
    ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <style>
        .error { color: #94180C; font-size: 10px; font-weight: bold; text-transform: uppercase; margin-top: 4px; display: block; }
    </style>
</head>
<body class="font-sans text-foundation-grey bg-canvas-white">

<?php if ($request_sent): ?>
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-6">
        <div class="bg-white p-12 max-w-lg w-full text-center border-b-4 border-rajkot-rust shadow-premium">
            <i data-lucide="check-circle" class="w-16 h-16 text-approval-green mx-auto mb-6"></i>
            <h2 class="text-3xl font-serif font-bold text-foundation-grey mb-4">Verification Submitted</h2>
            <p class="text-gray-500 mb-8">Your review request has been logged. An architect will inspect the site shortly.</p>
            <button onclick="window.location.href='dashboard.php'" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-8 py-3 text-[10px] font-bold uppercase tracking-widest transition-all">
                Continue
            </button>
        </div>
    </div>
<?php endif; ?>

<div class="min-h-screen flex flex-col">
    <!-- Unified Dark Portal Header -->
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg">
        <div class="max-w-4xl mx-auto flex flex-col">
            <div class="flex items-center gap-2 mb-3">
                <a href="dashboard.php" class="text-gray-400 hover:text-white transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <span class="px-2 py-0.5 bg-rajkot-rust text-[10px] font-bold uppercase tracking-widest">
                    Job Detail
                </span>
            </div>
            <h1 class="text-2xl font-serif font-bold"><?php echo htmlspecialchars($project['name']); ?></h1>
            <p class="text-gray-400 text-sm mt-1 flex items-center gap-1">
                <i data-lucide="map-pin" class="w-4 h-4 text-rajkot-rust"></i> 
                <?php echo htmlspecialchars($project['address']); ?>
            </p>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="bg-white border-b border-gray-200 sticky top-20 z-30 overflow-x-auto">
        <div class="max-w-4xl mx-auto flex text-sm font-bold">
            <button onclick="switchTab('overview')" id="tab-overview" class="flex-1 py-4 px-2 border-b-2 border-rajkot-rust text-rajkot-rust tab-btn transition-colors uppercase tracking-wider">
                Overview
            </button>
            <button onclick="switchTab('drawings')" id="tab-drawings" class="flex-1 py-4 px-2 border-b-2 border-transparent text-gray-400 tab-btn transition-colors uppercase tracking-wider">
                Drawings
            </button>
            <button onclick="switchTab('request')" id="tab-request" class="flex-1 py-4 px-2 border-b-2 border-transparent text-gray-400 tab-btn transition-colors uppercase tracking-wider">
                Requests
            </button>
        </div>
    </nav>

    <main class="flex-grow p-4 max-w-4xl mx-auto w-full">
        
        <!-- 1. OVERVIEW TAB -->
        <div id="content-overview" class="tab-content space-y-6">
            <!-- Project Stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white p-4 shadow-premium border border-gray-100">
                    <span class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Area</span>
                    <span class="block text-lg font-bold mt-1"><?php echo $project['area']; ?></span>
                </div>
                <div class="bg-white p-4 shadow-premium border border-gray-100">
                    <span class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Budget</span>
                    <span class="block text-lg font-bold mt-1 text-rajkot-rust"><?php echo $project['budget']; ?></span>
                </div>
            </div>

            <!-- Contacts Section -->
            <div class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-rajkot-rust text-opacity-50"></i>
                    Site Contacts
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Owner -->
                    <div class="bg-white p-4 shadow-premium border border-gray-100 flex justify-between items-center">
                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase tracking-widest block mb-1">Owner</span>
                            <span class="font-bold"><?php echo htmlspecialchars($project['owner']['name']); ?></span>
                        </div>
                        <a href="tel:<?php echo $project['owner']['contact']; ?>" class="w-10 h-10 bg-green-50 text-green-700 rounded-full flex items-center justify-center">
                            <i data-lucide="phone" class="w-5 h-5"></i>
                        </a>
                    </div>
                    <!-- Team -->
                    <?php foreach($project['workers'] as $w): ?>
                    <div class="bg-white p-4 shadow-premium border border-gray-100 flex justify-between items-center">
                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase tracking-widest block mb-1"><?php echo htmlspecialchars($w['role']); ?></span>
                            <span class="font-bold"><?php echo htmlspecialchars($w['name']); ?></span>
                        </div>
                        <a href="tel:<?php echo $w['contact']; ?>" class="w-10 h-10 bg-slate-accent/10 text-slate-accent rounded-full flex items-center justify-center">
                            <i data-lucide="phone-call" class="w-5 h-5"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Materials Summary -->
            <div class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 flex items-center gap-2">
                    <i data-lucide="package" class="w-4 h-4 text-rajkot-rust text-opacity-50"></i>
                    Material Status
                </h2>
                <div class="bg-white shadow-premium border border-gray-100 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-[10px] uppercase font-bold text-gray-400 tracking-widest">
                            <tr>
                                <th class="p-4">Item</th>
                                <th class="p-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($project['goods'] as $g): ?>
                            <tr>
                                <td class="p-4 font-medium"><?php echo htmlspecialchars($g['item']); ?></td>
                                <td class="p-4 text-right">
                                    <span class="text-[10px] font-bold px-2 py-0.5 border <?php echo $g['status'] == 'Delivered' ? 'text-approval-green border-approval-green/20 bg-approval-green/10' : 'text-pending-amber border-pending-amber/20 bg-pending-amber/10'; ?>">
                                        <?php echo strtoupper($g['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. DRAWINGS TAB -->
        <div id="content-drawings" class="tab-content hidden space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 flex items-center gap-2 px-1">
                <i data-lucide="file-text" class="w-4 h-4 text-rajkot-rust text-opacity-50"></i>
                Technical Drawings
            </h2>
            <div class="grid grid-cols-1 gap-3">
                <?php foreach($project['drawings'] as $d): ?>
                <div class="bg-white p-4 shadow-premium border border-gray-100 flex items-center gap-4 group hover:border-rajkot-rust transition-colors cursor-pointer" onclick="window.open('../admin/file_viewer.php?file=<?php echo urlencode($d['title']); ?>&project=<?php echo urlencode($project['name']); ?>', '_blank')">
                    <div class="w-12 h-12 bg-foundation-grey group-hover:bg-rajkot-rust transition-colors flex items-center justify-center text-white shrink-0">
                        <i data-lucide="<?php echo $d['type'] == 'pdf' ? 'file-text' : 'image'; ?>" class="w-6 h-6"></i>
                    </div>
                    <div class="flex-grow min-w-0">
                        <h4 class="font-bold text-foundation-grey truncate group-hover:text-rajkot-rust transition-colors"><?php echo htmlspecialchars($d['title']); ?></h4>
                        <p class="text-xs text-gray-400 uppercase tracking-widest mt-1">
                            Issued: <?php echo date('M d, Y', strtotime($d['date'])); ?> &bull; <span class="text-approval-green">Issued for Construction</span>
                        </p>
                    </div>
                    <div class="text-gray-300">
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="mt-4 p-8 border-2 border-dashed border-gray-200 rounded-lg flex flex-col items-center justify-center text-gray-400">
                    <i data-lucide="upload-cloud" class="w-10 h-10 mb-2 opacity-50"></i>
                    <span class="text-xs font-bold uppercase tracking-widest">Share On-site Photo</span>
                </div>
            </div>
        </div>

        <!-- 3. REQUEST TAB -->
        <div id="content-request" class="tab-content hidden space-y-6">
            <div class="bg-white p-6 shadow-premium border border-gray-100">
                <h3 class="text-lg font-bold font-serif mb-4">New Review Request</h3>
                <form class="space-y-4" method="POST" action="" id="requestForm">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2">Subject</label>
                        <input type="text" name="request_subject" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust transition-colors" placeholder="e.g. Beam Reinforcement Ready">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2">Urgency</label>
                            <select name="request_urgency" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust transition-colors appearance-none">
                                <option>Normal</option>
                                <option>High</option>
                                <option>Critical</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2">Trade</label>
                            <select name="request_trade" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust transition-colors appearance-none">
                                <option>Structural</option>
                                <option>Plumbing</option>
                                <option>Electrical</option>
                                <option>Finishing</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2">Details</label>
                        <textarea name="request_details" rows="4" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust transition-colors" placeholder="Explain what requires immediate inspection..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-rajkot-rust text-white py-4 font-bold uppercase tracking-widest shadow-lg active:scale-[0.98] transition-all">
                        Submit for Verification
                    </button>
                </form>
            </div>
        </div>

    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</div>

<script>
    function switchTab(tab) {
        // Update Buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-rajkot-rust', 'text-rajkot-rust');
            btn.classList.add('border-transparent', 'text-gray-400');
        });
        const activeBtn = document.getElementById('tab-' + tab);
        activeBtn.classList.add('border-rajkot-rust', 'text-rajkot-rust');
        activeBtn.classList.remove('border-transparent', 'text-gray-400');

        // Update Content
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
        document.getElementById('content-' + tab).classList.remove('hidden');

        // Update Hash
        window.location.hash = tab;
    }

    // Initialize from Hash
    window.addEventListener('load', () => {
        const hash = window.location.hash.replace('#', '') || 'overview';
        switchTab(hash);
    });

    $(document).ready(function() {
        $("#requestForm").validate({
            rules: {
                request_subject: {
                    required: true,
                    minlength: 5
                },
                request_details: {
                    required: true,
                    minlength: 10
                }
            },
            messages: {
                request_subject: {
                    required: "Subject is required for registry",
                    minlength: "Subject must be at least 5 characters"
                },
                request_details: {
                    required: "Detailed context is mandatory",
                    minlength: "Please provide more detail for the architect"
                }
            },
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            }
        });
    });
</script>

</body>
</html>
