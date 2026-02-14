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
        ['title' => 'Ground Floor Plan', 'type' => 'pdf', 'date' => '2025-01-15'],
        ['title' => 'Electrical Layout', 'type' => 'dwg', 'date' => '2025-01-20'],
        ['title' => 'Plumbing Diagram', 'type' => 'img', 'date' => '2025-01-22'],
    ]
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($project['name']); ?> - Details</title>
    
    <!-- Typography & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom Styles -->
    <?php asset_enqueue_css('/worker/worker_dashboard.css'); ?>
    <style>
        /* Fix for fixed header visibility on light background */
        .fixed-top {
            background-color: var(--color-primary);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Ensure content starts below header */
        .worker-dashboard {
            padding-top: 140px !important;
            padding-bottom: 60px; /* Space above footer */
            min-height: calc(100vh - 300px); /* Ensure footer pushes down */
        }
        
        /* Page-specific overrides */
        .details-header {
            background: #fff;
            border-bottom: 1px solid var(--color-border);
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .nav-tabs .nav-link {
            color: var(--color-text-muted);
            border: none;
            border-bottom: 3px solid transparent;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            padding: 10px 20px;
        }
        .nav-tabs .nav-link.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
            background: transparent;
            font-weight: 700;
        }
        .nav-tabs {
            border-bottom: 1px solid var(--color-border);
            margin-bottom: 30px;
        }
        .info-card {
            background: #fff;
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius-card);
            padding: 20px;
            height: 100%;
            box-shadow: var(--shadow-sm);
        }
        .info-card h3 {
            font-family: 'Cormorant Garamond', serif;
            color: var(--color-primary);
            font-size: 1.25rem;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .data-list dt {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--color-text-muted);
            margin-top: 10px;
        }
        .data-list dd {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 5px;
        }
        .map-container {
            height: 350px;
            border-radius: var(--border-radius-card);
            overflow: hidden;
            background: #eee;
            margin-bottom: 15px;
            position: relative;
        }
        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
        .drawing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .drawing-card {
            background: #fff;
            border: 1px solid var(--color-border);
            border-radius: var(--border-radius-btn);
            padding: 15px;
            text-align: center;
            transition: var(--transition-speed);
            text-decoration: none;
            color: var(--color-text-main);
            display: block;
        }
        .drawing-card:hover {
            border-color: var(--color-primary);
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
            color: var(--color-primary);
        }
        .drawing-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--color-text-muted);
        }
        .drawing-card:hover .drawing-icon {
            color: var(--color-primary);
        }
        /* Fix for contact buttons stretching due to flex:1 on .btn */
        .btn-contact {
            flex: none !important;
            width: 36px !important;
            height: 36px !important;
            padding: 0 !important;
        }
    </style>
</head>
<body>
<?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../includes/header.php'; ?>

<main class="worker-dashboard">
    <div class="container-fluid px-0">
        <!-- Breadcrumb / Back -->
        <div class="mb-4">
            <a href="dashboard.php" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <!-- Project Header -->
        <header class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4">
            <div>
                <span class="status-badge <?php echo $project['status']; ?> mb-2 d-inline-block">
                    <?php echo ucfirst($project['status']); ?>
                </span>
                <h1 class="font-serif display-6 fw-bold" style="color: var(--color-primary);"><?php echo htmlspecialchars($project['name']); ?></h1>
                <p class="text-muted mb-0"><i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($project['address']); ?></p>
            </div>
            <div class="mt-3 mt-md-0">
                <div class="text-md-end">
                    <small class="text-uppercase text-muted d-block" style="font-size: 0.75rem;">Budget</small>
                    <span class="fs-4 fw-bold"><?php echo $project['budget']; ?></span>
                </div>
            </div>
        </header>

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="projectTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">Overview & Team</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="drawings-tab" data-bs-toggle="tab" data-bs-target="#drawings" type="button" role="tab">Drawings & Files</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="request-tab" data-bs-toggle="tab" data-bs-target="#request" type="button" role="tab">Request Review</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="projectTabsContent">
            
            <!-- 1. DETAILS TAB -->
            <div class="tab-pane fade show active" id="details" role="tabpanel">
                <div class="row g-4">
                    <!-- Left Col: Site Info & Map -->
                    <div class="col-lg-7">
                        <section class="info-card mb-4">
                            <h3>Site Details</h3>
                            <div class="map-container">
                                <iframe 
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d287.47857098438425!2d70.76867685826322!3d22.30597063170977!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3959c983c4b8aeaf%3A0xf7c6e2439ee00a3f!2sNanavati%20Chowk!5e1!3m2!1sen!2sin!4v1771055842937!5m2!1sen!2sin" 
                                    allowfullscreen="" 
                                    loading="lazy" 
                                    referrerpolicy="no-referrer-when-downgrade"
                                    title="Project Location Map">
                                </iframe>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted text-uppercase">Site Area</small>
                                    <div class="fw-bold fs-5"><?php echo $project['area']; ?></div>
                                </div>
                                <a href="https://maps.google.com/?q=<?php echo urlencode($project['address']); ?>" target="_blank" class="btn outline btn-sm" style="flex: initial; width: auto;">
                                    <i class="bi bi-cursor me-2"></i>Get Directions
                                </a>
                            </div>
                        </section>

                        <section class="info-card">
                            <h3>Goods & Materials</h3>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small text-uppercase">
                                        <tr>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($project['goods'] as $g): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($g['item']); ?></td>
                                            <td><?php echo htmlspecialchars($g['qty']); ?></td>
                                            <td><span class="badge bg-secondary opacity-75"><?php echo htmlspecialchars($g['status']); ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <!-- Right Col: People -->
                    <div class="col-lg-5">
                        <section class="info-card mb-4">
                            <h3>Owner info</h3>
                            <dl class="data-list mb-0">
                                <dt>Name</dt>
                                <dd><?php echo htmlspecialchars($project['owner']['name']); ?></dd>
                                <dt>Contact</dt>
                                <dd><a href="tel:<?php echo $project['owner']['contact']; ?>" class="text-decoration-none" style="color: var(--color-primary);"><?php echo $project['owner']['contact']; ?></a></dd>
                            </dl>
                        </section>

                        <section class="info-card">
                            <h3>Assigned Team</h3>
                            <div class="list-group list-group-flush">
                                <?php foreach($project['workers'] as $w): ?>
                                <div class="list-group-item px-0 py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($w['name']); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($w['role']); ?></small>
                                        </div>
                                        <a href="tel:<?php echo $w['contact']; ?>" class="btn btn-sm btn-light rounded-circle btn-contact" style="display:inline-flex;align-items:center;justify-content:center;">
                                            <i class="bi bi-telephone-fill text-dark"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            <!-- 2. DRAWINGS TAB -->
            <div class="tab-pane fade" id="drawings" role="tabpanel">
                <section class="info-card">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <h3 class="mb-0 border-0 p-0">Project Drawings</h3>
                        <span class="badge bg-custom text-dark border">Total: <?php echo count($project['drawings']); ?></span>
                    </div>
                    
                    <div class="drawing-grid">
                        <?php foreach($project['drawings'] as $d): 
                            $icon = 'bi-file-earmark';
                            if($d['type'] == 'pdf') $icon = 'bi-file-earmark-pdf';
                            if($d['type'] == 'dwg') $icon = 'bi-file-earmark-code';
                            if($d['type'] == 'img') $icon = 'bi-file-earmark-image';
                        ?>
                        <a href="#" class="drawing-card">
                            <i class="bi <?php echo $icon; ?> drawing-icon"></i>
                            <div class="fw-bold mb-1 text-truncate"><?php echo htmlspecialchars($d['title']); ?></div>
                            <small class="text-muted d-block text-uppercase"><?php echo $d['type']; ?> &bull; <?php echo $d['date']; ?></small>
                        </a>
                        <?php endforeach; ?>
                        
                        <!-- Upload New Placeholder -->
                        <a href="#" class="drawing-card d-flex flex-column align-items-center justify-content-center" style="border-style: dashed; background: #fafafa;">
                            <i class="bi bi-cloud-upload drawing-icon opacity-50"></i>
                            <div class="text-muted">Upload New</div>
                        </a>
                    </div>
                </section>
            </div>

            <!-- 3. REQUEST REVIEW TAB -->
            <div class="tab-pane fade" id="request" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <section class="info-card">
                            <h3>Submit Review Request</h3>
                            <p class="text-muted mb-4">Need approval on a specific phase or material? Submit a formal request here.</p>
                            
                            <form>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-uppercase">Subject</label>
                                    <input type="text" class="form-control" placeholder="e.g. Foundation Layer Inspection">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-uppercase">Urgency</label>
                                        <select class="form-select">
                                            <option>Normal</option>
                                            <option>High</option>
                                            <option>Critical</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-uppercase">Related Phase</label>
                                        <select class="form-select">
                                            <option>General</option>
                                            <option>Plumbing</option>
                                            <option>Electrical</option>
                                            <option>Civil Work</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-uppercase">Message / Description</label>
                                    <textarea class="form-control" rows="5" placeholder="Describe what needs reviewing..."></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-uppercase">Attachments</label>
                                    <input type="file" class="form-control">
                                    <div class="form-text">Upload photos of work done (Max 10MB)</div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn outline px-4">Cancel</button>
                                    <button type="submit" class="btn primary px-4">Submit Request</button>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</main>

<!-- <div class="position-relative" style="z-index: 10;">
<?php require_once __DIR__ . '/../includes/footer.php'; ?> -->
</div>
<?php asset_enqueue_js('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js'); ?>

<!-- Script to handle hash navigation (opening correct tab from dashboard links) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash;
        if (hash) {
            const triggerEl = document.querySelector(`.nav-link[data-bs-target="${hash}"]`);
            if (triggerEl) {
                const tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }
    });
</script>
</body>
</html>
