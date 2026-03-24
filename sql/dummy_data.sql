-- Dummy seed data for TheFinal
-- Purpose: Provide at least 10 rows per table using
-- - Mahabharat names for people
-- - Ramayan places for project/location fields

USE `Ripal-Design`;

START TRANSACTION;

-- ============================================================
-- users (12)
-- ============================================================
INSERT IGNORE INTO users (
  username, full_name, first_name, last_name, email, phone,
  password_hash, role, status, address, city, state, zip, joined_date
) VALUES
('yudhishthira', 'Yudhishthira Pandava', 'Yudhishthira', 'Pandava', 'yudhishthira@thefinal.in', '9000000001', '$2y$10$dummyhashYUDH', 'admin', 'active', 'Rajmarg, Ayodhya', 'Ayodhya', 'Kosala', '110001', '2024-01-03'),
('bhima', 'Bhima Pandava', 'Bhima', 'Pandava', 'bhima@thefinal.in', '9000000002', '$2y$10$dummyhashBHIM', 'worker', 'active', 'Gada Chowk, Kishkindha', 'Kishkindha', 'Vanara Pradesh', '110002', '2024-01-05'),
('arjuna', 'Arjuna Pandava', 'Arjuna', 'Pandava', 'arjuna@thefinal.in', '9000000003', '$2y$10$dummyhashARJU', 'employee', 'active', 'Dhanush Vihar, Mithila', 'Mithila', 'Videha', '110003', '2024-01-09'),
('nakula', 'Nakula Pandava', 'Nakula', 'Pandava', 'nakula@thefinal.in', '9000000004', '$2y$10$dummyhashNAKU', 'worker', 'pending', 'Ashwa Path, Chitrakoot', 'Chitrakoot', 'Kosala', '110004', '2024-02-01'),
('sahadeva', 'Sahadeva Pandava', 'Sahadeva', 'Pandava', 'sahadeva@thefinal.in', '9000000005', '$2y$10$dummyhashSAHA', 'employee', 'active', 'Jyotish Marg, Panchavati', 'Panchavati', 'Dandaka', '110005', '2024-02-03'),
('draupadi', 'Draupadi Panchali', 'Draupadi', 'Panchali', 'draupadi@thefinal.in', '9000000006', '$2y$10$dummyhashDRAU', 'client', 'active', 'Pushp Vatika, Janakpur', 'Janakpur', 'Videha', '110006', '2024-02-07'),
('karna', 'Karna Radheya', 'Karna', 'Radheya', 'karna@thefinal.in', '9000000007', '$2y$10$dummyhashKARN', 'worker', 'active', 'Surya Dwar, Rameshwaram', 'Rameshwaram', 'Setu Pradesh', '110007', '2024-02-10'),
('duryodhana', 'Duryodhana Kaurava', 'Duryodhana', 'Kaurava', 'duryodhana@thefinal.in', '9000000008', '$2y$10$dummyhashDURY', 'employee', 'suspended', 'Rajsabha Road, Lanka', 'Lanka', 'Simhala', '110008', '2024-02-12'),
('kunti', 'Kunti Devi', 'Kunti', 'Devi', 'kunti@thefinal.in', '9000000009', '$2y$10$dummyhashKUNT', 'client', 'active', 'Mata Mandir, Nandigram', 'Nandigram', 'Kosala', '110009', '2024-02-15'),
('abhimanyu', 'Abhimanyu Arjuna', 'Abhimanyu', 'Arjuna', 'abhimanyu@thefinal.in', '9000000010', '$2y$10$dummyhashABHI', 'worker', 'active', 'Chakra Path, Dandakaranya', 'Dandakaranya', 'Dandaka', '110010', '2024-02-18'),
('vidura', 'Vidura Mahamantri', 'Vidura', 'Mahamantri', 'vidura@thefinal.in', '9000000011', '$2y$10$dummyhashVIDU', 'employee', 'active', 'Neeti Nagar, Shringaverpur', 'Shringaverpur', 'Kosala', '110011', '2024-02-20'),
('subhadra', 'Subhadra Yadavi', 'Subhadra', 'Yadavi', 'subhadra@thefinal.in', '9000000012', '$2y$10$dummyhashSUBH', 'client', 'pending', 'Yamuna Ghat, Prayagraj', 'Prayagraj', 'Madhyadesh', '110012', '2024-02-24');

-- ============================================================
-- signup (10)
-- ============================================================
INSERT IGNORE INTO signup (first_name, last_name, email, password, phone_number) VALUES
('Bhishma', 'Pitamah', 'bhishma.signup@thefinal.in', '$2y$10$dummyhash01', '9010000001'),
('Drona', 'Acharya', 'drona.signup@thefinal.in', '$2y$10$dummyhash02', '9010000002'),
('Kripa', 'Acharya', 'kripa.signup@thefinal.in', '$2y$10$dummyhash03', '9010000003'),
('Ashwatthama', 'Drona', 'ashwatthama.signup@thefinal.in', '$2y$10$dummyhash04', '9010000004'),
('Shakuni', 'Gandhara', 'shakuni.signup@thefinal.in', '$2y$10$dummyhash05', '9010000005'),
('Gandhari', 'Kuru', 'gandhari.signup@thefinal.in', '$2y$10$dummyhash06', '9010000006'),
('Balarama', 'Yadava', 'balarama.signup@thefinal.in', '$2y$10$dummyhash07', '9010000007'),
('Satyaki', 'Vrishni', 'satyaki.signup@thefinal.in', '$2y$10$dummyhash08', '9010000008'),
('Ulupi', 'Naga', 'ulupi.signup@thefinal.in', '$2y$10$dummyhash09', '9010000009'),
('Hidimba', 'Rakshasi', 'hidimba.signup@thefinal.in', '$2y$10$dummyhash10', '9010000010');

-- ============================================================
-- auth_tokens (10)
-- ============================================================
INSERT IGNORE INTO auth_tokens (user_id, token, token_type, expires_at)
SELECT id, CONCAT('tok_yudhishthira_', id), 'session', DATE_ADD(NOW(), INTERVAL 30 DAY) FROM users WHERE username='yudhishthira'
UNION ALL SELECT id, CONCAT('tok_bhima_', id), 'session', DATE_ADD(NOW(), INTERVAL 20 DAY) FROM users WHERE username='bhima'
UNION ALL SELECT id, CONCAT('tok_arjuna_', id), 'session', DATE_ADD(NOW(), INTERVAL 25 DAY) FROM users WHERE username='arjuna'
UNION ALL SELECT id, CONCAT('tok_nakula_', id), 'password_reset', DATE_ADD(NOW(), INTERVAL 2 DAY) FROM users WHERE username='nakula'
UNION ALL SELECT id, CONCAT('tok_sahadeva_', id), 'session', DATE_ADD(NOW(), INTERVAL 18 DAY) FROM users WHERE username='sahadeva'
UNION ALL SELECT id, CONCAT('tok_draupadi_', id), 'session', DATE_ADD(NOW(), INTERVAL 15 DAY) FROM users WHERE username='draupadi'
UNION ALL SELECT id, CONCAT('tok_karna_', id), 'session', DATE_ADD(NOW(), INTERVAL 40 DAY) FROM users WHERE username='karna'
UNION ALL SELECT id, CONCAT('tok_duryodhana_', id), 'api', DATE_ADD(NOW(), INTERVAL 60 DAY) FROM users WHERE username='duryodhana'
UNION ALL SELECT id, CONCAT('tok_kunti_', id), 'session', DATE_ADD(NOW(), INTERVAL 10 DAY) FROM users WHERE username='kunti'
UNION ALL SELECT id, CONCAT('tok_abhimanyu_', id), 'session', DATE_ADD(NOW(), INTERVAL 12 DAY) FROM users WHERE username='abhimanyu';

-- ============================================================
-- projects (10)
-- Ramayan place names used in location fields
-- ============================================================
INSERT INTO projects (
  name, status, budget, progress, due, location, site_location, address,
  owner_name, owner_contact, owner_email, worker_name, project_type,
  client_id, created_by, latitude, longitude
) VALUES
('Ayodhya Rajmarg Villa', 'ongoing', 2500000.00, 35, '2026-09-30', 'Ayodhya Sector A', 'Ayodhya', 'Sarayu Ghat Road, Ayodhya', 'Draupadi Panchali', '9200000001', 'draupadi@thefinal.in', 'Bhima Pandava', 'Residential', (SELECT id FROM users WHERE username='draupadi'), (SELECT id FROM users WHERE username='yudhishthira'), 26.7990000, 82.2040000),
('Mithila Heritage Home', 'planning', 1800000.00, 10, '2026-11-20', 'Janakpur East Block', 'Mithila', 'Sita Vatika Lane, Mithila', 'Kunti Devi', '9200000002', 'kunti@thefinal.in', 'Karna Radheya', 'Residential', (SELECT id FROM users WHERE username='kunti'), (SELECT id FROM users WHERE username='arjuna'), 26.7280000, 85.9250000),
('Kishkindha Office Hub', 'ongoing', 3200000.00, 50, '2026-08-10', 'Kishkindha Hill Zone', 'Kishkindha', 'Sugriva Fort Street, Kishkindha', 'Subhadra Yadavi', '9200000003', 'subhadra@thefinal.in', 'Nakula Pandava', 'Commercial', (SELECT id FROM users WHERE username='subhadra'), (SELECT id FROM users WHERE username='vidura'), 15.3350000, 76.4600000),
('Chitrakoot Riverside Residency', 'paused', 1400000.00, 42, '2026-12-15', 'Chitrakoot Riverside', 'Chitrakoot', 'Mandakini Path, Chitrakoot', 'Draupadi Panchali', '9200000004', 'draupadi@thefinal.in', 'Abhimanyu Arjuna', 'Residential', (SELECT id FROM users WHERE username='draupadi'), (SELECT id FROM users WHERE username='yudhishthira'), 25.1700000, 80.8600000),
('Panchavati Studio Complex', 'ongoing', 2100000.00, 65, '2026-07-25', 'Panchavati Arts District', 'Panchavati', 'Godavari View, Panchavati', 'Kunti Devi', '9200000005', 'kunti@thefinal.in', 'Bhima Pandava', 'Studio', (SELECT id FROM users WHERE username='kunti'), (SELECT id FROM users WHERE username='arjuna'), 20.0000000, 73.7800000),
('Dandakaranya Green Township', 'planning', 4500000.00, 8, '2027-02-28', 'Dandakaranya North', 'Dandakaranya', 'Vananchal Ring Road, Dandakaranya', 'Subhadra Yadavi', '9200000006', 'subhadra@thefinal.in', 'Karna Radheya', 'Township', (SELECT id FROM users WHERE username='subhadra'), (SELECT id FROM users WHERE username='vidura'), 19.0700000, 82.0300000),
('Rameshwaram Sea View Retreat', 'on-hold', 2750000.00, 22, '2026-10-19', 'Rameshwaram Coastline', 'Rameshwaram', 'Setu Beach Front, Rameshwaram', 'Draupadi Panchali', '9200000007', 'draupadi@thefinal.in', 'Abhimanyu Arjuna', 'Resort', (SELECT id FROM users WHERE username='draupadi'), (SELECT id FROM users WHERE username='yudhishthira'), 9.2880000, 79.3170000),
('Lanka Skyline Towers', 'ongoing', 6800000.00, 58, '2027-01-11', 'Lanka Central', 'Lanka', 'Ashoka Vatika Avenue, Lanka', 'Kunti Devi', '9200000008', 'kunti@thefinal.in', 'Nakula Pandava', 'High-rise', (SELECT id FROM users WHERE username='kunti'), (SELECT id FROM users WHERE username='arjuna'), 6.9270000, 79.8610000),
('Nandigram Civic Center', 'completed', 1600000.00, 100, '2026-05-12', 'Nandigram Ward 4', 'Nandigram', 'Bharat Path, Nandigram', 'Subhadra Yadavi', '9200000009', 'subhadra@thefinal.in', 'Bhima Pandava', 'Civic', (SELECT id FROM users WHERE username='subhadra'), (SELECT id FROM users WHERE username='vidura'), 26.9300000, 82.1300000),
('Shringaverpur Logistics Park', 'cancelled', 3900000.00, 15, '2026-12-31', 'Shringaverpur Transport Belt', 'Shringaverpur', 'Ganga Link Road, Shringaverpur', 'Draupadi Panchali', '9200000010', 'draupadi@thefinal.in', 'Karna Radheya', 'Industrial', (SELECT id FROM users WHERE username='draupadi'), (SELECT id FROM users WHERE username='yudhishthira'), 25.5500000, 81.9000000);

-- ============================================================
-- project_assignments (10)
-- ============================================================
INSERT INTO project_assignments (project_id, worker_id, assigned_at)
SELECT p.id, u.id, NOW() FROM projects p JOIN users u ON p.name='Ayodhya Rajmarg Villa' AND u.username='bhima'
UNION ALL SELECT p.id, u.id, NOW() FROM projects p JOIN users u ON p.name='Mithila Heritage Home' AND u.username='karna'
UNION ALL SELECT p.id, u.id, NOW() FROM projects p JOIN users u ON p.name='Kishkindha Office Hub' AND u.username='nakula'
UNION ALL SELECT p.id, u.id, NOW() FROM projects p JOIN users u ON p.name='Chitrakoot Riverside Residency' AND u.username='abhimanyu'
UNION ALL SELECT p.id, u.id, NOW() FROM projects p JOIN users u ON p.name='Panchavati Studio Complex' AND u.username='bhima'
UNION ALL SELECT p.id, u.id, NOW() FROM projects p JOIN users u ON p.name='Dandakaranya Green Township' AND u.username='karna'
UNION ALL SELECT p.id, u.id, NOW() FROM projects p JOIN users u ON p.name='Rameshwaram Sea View Retreat' AND u.username='abhimanyu'
UNION ALL SELECT p.id, u.id, NOW() FROM projects p JOIN users u ON p.name='Lanka Skyline Towers' AND u.username='nakula'
UNION ALL SELECT p.id, u.id, NOW() FROM projects p JOIN users u ON p.name='Nandigram Civic Center' AND u.username='bhima'
UNION ALL SELECT p.id, u.id, NOW() FROM projects p JOIN users u ON p.name='Shringaverpur Logistics Park' AND u.username='karna';

-- ============================================================
-- project_workers (10)
-- ============================================================
INSERT INTO project_workers (project_id, worker_name, worker_role, worker_contact)
SELECT id, 'Bhima Pandava', 'Masonry Lead', '9300000001' FROM projects WHERE name='Ayodhya Rajmarg Villa'
UNION ALL SELECT id, 'Karna Radheya', 'Electrical Lead', '9300000002' FROM projects WHERE name='Mithila Heritage Home'
UNION ALL SELECT id, 'Nakula Pandava', 'Site Supervisor', '9300000003' FROM projects WHERE name='Kishkindha Office Hub'
UNION ALL SELECT id, 'Abhimanyu Arjuna', 'Finishing Lead', '9300000004' FROM projects WHERE name='Chitrakoot Riverside Residency'
UNION ALL SELECT id, 'Bhima Pandava', 'Fabrication Lead', '9300000005' FROM projects WHERE name='Panchavati Studio Complex'
UNION ALL SELECT id, 'Karna Radheya', 'Procurement Lead', '9300000006' FROM projects WHERE name='Dandakaranya Green Township'
UNION ALL SELECT id, 'Abhimanyu Arjuna', 'Civil Engineer', '9300000007' FROM projects WHERE name='Rameshwaram Sea View Retreat'
UNION ALL SELECT id, 'Nakula Pandava', 'Facade Specialist', '9300000008' FROM projects WHERE name='Lanka Skyline Towers'
UNION ALL SELECT id, 'Bhima Pandava', 'Execution Lead', '9300000009' FROM projects WHERE name='Nandigram Civic Center'
UNION ALL SELECT id, 'Karna Radheya', 'Infrastructure Lead', '9300000010' FROM projects WHERE name='Shringaverpur Logistics Park';

-- ============================================================
-- project_milestones (10)
-- ============================================================
INSERT INTO project_milestones (project_id, title, target_date, status)
SELECT id, 'Foundation Completion', '2026-04-15', 'active' FROM projects WHERE name='Ayodhya Rajmarg Villa'
UNION ALL SELECT id, 'Structural Design Freeze', '2026-05-01', 'pending' FROM projects WHERE name='Mithila Heritage Home'
UNION ALL SELECT id, 'Core Construction 50%', '2026-06-20', 'active' FROM projects WHERE name='Kishkindha Office Hub'
UNION ALL SELECT id, 'Material Approval', '2026-05-18', 'completed' FROM projects WHERE name='Chitrakoot Riverside Residency'
UNION ALL SELECT id, 'Facade Finalization', '2026-04-28', 'active' FROM projects WHERE name='Panchavati Studio Complex'
UNION ALL SELECT id, 'Land Survey Closure', '2026-07-07', 'pending' FROM projects WHERE name='Dandakaranya Green Township'
UNION ALL SELECT id, 'Coastal Compliance Review', '2026-06-11', 'active' FROM projects WHERE name='Rameshwaram Sea View Retreat'
UNION ALL SELECT id, 'Tower Crane Setup', '2026-05-30', 'completed' FROM projects WHERE name='Lanka Skyline Towers'
UNION ALL SELECT id, 'Interior Handover', '2026-04-01', 'completed' FROM projects WHERE name='Nandigram Civic Center'
UNION ALL SELECT id, 'Tender Re-evaluation', '2026-08-20', 'pending' FROM projects WHERE name='Shringaverpur Logistics Park';

-- ============================================================
-- project_files (10)
-- ============================================================
INSERT INTO project_files (
  project_id, name, filename, type, size, file_path, storage_path,
  version, uploaded_by, status
)
SELECT id, 'Master Plan', 'ayodhya_master_plan.pdf', 'pdf', '2.4MB', '/uploads/ayodhya/master_plan.pdf', 'projects/ayodhya/master_plan_v1.pdf', 1, 'Arjuna Pandava', 'active' FROM projects WHERE name='Ayodhya Rajmarg Villa'
UNION ALL SELECT id, 'Estimate Sheet', 'mithila_estimate.xlsx', 'xlsx', '1.1MB', '/uploads/mithila/estimate.xlsx', 'projects/mithila/estimate_v1.xlsx', 1, 'Sahadeva Pandava', 'active' FROM projects WHERE name='Mithila Heritage Home'
UNION ALL SELECT id, 'Site Layout', 'kishkindha_layout.dwg', 'dwg', '3.8MB', '/uploads/kishkindha/layout.dwg', 'projects/kishkindha/layout_v2.dwg', 2, 'Vidura Mahamantri', 'active' FROM projects WHERE name='Kishkindha Office Hub'
UNION ALL SELECT id, 'Revision Notes', 'chitrakoot_revision.docx', 'docx', '720KB', '/uploads/chitrakoot/revision.docx', 'projects/chitrakoot/revision_v1.docx', 1, 'Arjuna Pandava', 'active' FROM projects WHERE name='Chitrakoot Riverside Residency'
UNION ALL SELECT id, 'MEP Plan', 'panchavati_mep.pdf', 'pdf', '2.0MB', '/uploads/panchavati/mep.pdf', 'projects/panchavati/mep_v1.pdf', 1, 'Sahadeva Pandava', 'active' FROM projects WHERE name='Panchavati Studio Complex'
UNION ALL SELECT id, 'Topography Report', 'dandakaranya_topo.pdf', 'pdf', '1.7MB', '/uploads/dandakaranya/topo.pdf', 'projects/dandakaranya/topo_v1.pdf', 1, 'Vidura Mahamantri', 'active' FROM projects WHERE name='Dandakaranya Green Township'
UNION ALL SELECT id, 'Coastal NOC', 'rameshwaram_noc.pdf', 'pdf', '900KB', '/uploads/rameshwaram/noc.pdf', 'projects/rameshwaram/noc_v1.pdf', 1, 'Yudhishthira Pandava', 'active' FROM projects WHERE name='Rameshwaram Sea View Retreat'
UNION ALL SELECT id, 'Tower Elevation', 'lanka_elevation.dwg', 'dwg', '4.2MB', '/uploads/lanka/elevation.dwg', 'projects/lanka/elevation_v3.dwg', 3, 'Arjuna Pandava', 'active' FROM projects WHERE name='Lanka Skyline Towers'
UNION ALL SELECT id, 'Completion Certificate', 'nandigram_completion.pdf', 'pdf', '650KB', '/uploads/nandigram/completion.pdf', 'projects/nandigram/completion_v1.pdf', 1, 'Vidura Mahamantri', 'archived' FROM projects WHERE name='Nandigram Civic Center'
UNION ALL SELECT id, 'Bid Comparison', 'shringaverpur_bid.xlsx', 'xlsx', '860KB', '/uploads/shringaverpur/bid.xlsx', 'projects/shringaverpur/bid_v1.xlsx', 1, 'Sahadeva Pandava', 'active' FROM projects WHERE name='Shringaverpur Logistics Park';

-- ============================================================
-- project_activity (10)
-- ============================================================
INSERT INTO project_activity (project_id, user, action, item)
SELECT id, 'Yudhishthira', 'created', 'Project initialized' FROM projects WHERE name='Ayodhya Rajmarg Villa'
UNION ALL SELECT id, 'Arjuna', 'updated', 'Design brief revised' FROM projects WHERE name='Mithila Heritage Home'
UNION ALL SELECT id, 'Vidura', 'assigned_worker', 'Nakula assigned to site' FROM projects WHERE name='Kishkindha Office Hub'
UNION ALL SELECT id, 'Sahadeva', 'uploaded', 'Revision notes uploaded' FROM projects WHERE name='Chitrakoot Riverside Residency'
UNION ALL SELECT id, 'Arjuna', 'updated', 'Facade milestone adjusted' FROM projects WHERE name='Panchavati Studio Complex'
UNION ALL SELECT id, 'Vidura', 'reviewed', 'Survey documents reviewed' FROM projects WHERE name='Dandakaranya Green Township'
UNION ALL SELECT id, 'Yudhishthira', 'approved', 'Compliance stage approved' FROM projects WHERE name='Rameshwaram Sea View Retreat'
UNION ALL SELECT id, 'Arjuna', 'updated', 'Elevation file replaced' FROM projects WHERE name='Lanka Skyline Towers'
UNION ALL SELECT id, 'Vidura', 'closed', 'Project closed and archived' FROM projects WHERE name='Nandigram Civic Center'
UNION ALL SELECT id, 'Sahadeva', 'reopened', 'Tender review re-opened' FROM projects WHERE name='Shringaverpur Logistics Park';

-- ============================================================
-- project_drawings (10)
-- ============================================================
INSERT INTO project_drawings (project_id, name, version, status, file_path)
SELECT id, 'Ayodhya Ground Floor Plan', 'v1.0', 'Under Review', '/drawings/ayodhya/ground_floor_v1.dwg' FROM projects WHERE name='Ayodhya Rajmarg Villa'
UNION ALL SELECT id, 'Mithila Elevation East', 'v1.2', 'Revision Needed', '/drawings/mithila/elevation_east_v1_2.dwg' FROM projects WHERE name='Mithila Heritage Home'
UNION ALL SELECT id, 'Kishkindha Office Core', 'v2.0', 'Approved', '/drawings/kishkindha/core_v2.dwg' FROM projects WHERE name='Kishkindha Office Hub'
UNION ALL SELECT id, 'Chitrakoot Stair Layout', 'v1.1', 'Under Review', '/drawings/chitrakoot/stair_v1_1.dwg' FROM projects WHERE name='Chitrakoot Riverside Residency'
UNION ALL SELECT id, 'Panchavati Interior Sheet', 'v3.0', 'Approved', '/drawings/panchavati/interior_v3.dwg' FROM projects WHERE name='Panchavati Studio Complex'
UNION ALL SELECT id, 'Dandakaranya Plot Grid', 'v1.0', 'Under Review', '/drawings/dandakaranya/grid_v1.dwg' FROM projects WHERE name='Dandakaranya Green Township'
UNION ALL SELECT id, 'Rameshwaram Coastal Section', 'v1.4', 'Revision Needed', '/drawings/rameshwaram/section_v1_4.dwg' FROM projects WHERE name='Rameshwaram Sea View Retreat'
UNION ALL SELECT id, 'Lanka Tower Podium', 'v2.2', 'Approved', '/drawings/lanka/podium_v2_2.dwg' FROM projects WHERE name='Lanka Skyline Towers'
UNION ALL SELECT id, 'Nandigram Civic Hall', 'v1.0', 'Approved', '/drawings/nandigram/hall_v1.dwg' FROM projects WHERE name='Nandigram Civic Center'
UNION ALL SELECT id, 'Shringaverpur Loading Bay', 'v0.9', 'Under Review', '/drawings/shringaverpur/loading_bay_v0_9.dwg' FROM projects WHERE name='Shringaverpur Logistics Park';

-- ============================================================
-- project_goods (10)
-- ============================================================
INSERT INTO project_goods (project_id, sku, name, description, unit, quantity, unit_price, total_price)
SELECT id, 'AYO-CEM-001', 'OPC Cement', '53 grade cement bags', 'bag', 400, 380.00, 152000.00 FROM projects WHERE name='Ayodhya Rajmarg Villa'
UNION ALL SELECT id, 'MIT-STE-002', 'TMT Steel 12mm', 'High tensile steel bars', 'kg', 5000, 62.00, 310000.00 FROM projects WHERE name='Mithila Heritage Home'
UNION ALL SELECT id, 'KIS-BRK-003', 'Fly Ash Bricks', 'Standard size bricks', 'pcs', 25000, 8.00, 200000.00 FROM projects WHERE name='Kishkindha Office Hub'
UNION ALL SELECT id, 'CHI-SAN-004', 'River Sand', 'Fine river sand', 'ton', 120, 2100.00, 252000.00 FROM projects WHERE name='Chitrakoot Riverside Residency'
UNION ALL SELECT id, 'PAN-TIL-005', 'Vitrified Tiles', '600x600 premium tiles', 'box', 300, 1200.00, 360000.00 FROM projects WHERE name='Panchavati Studio Complex'
UNION ALL SELECT id, 'DAN-AGR-006', 'Aggregate 20mm', 'Concrete aggregate', 'ton', 200, 1500.00, 300000.00 FROM projects WHERE name='Dandakaranya Green Township'
UNION ALL SELECT id, 'RAM-WAT-007', 'Waterproof Membrane', 'Roof waterproofing material', 'roll', 80, 3500.00, 280000.00 FROM projects WHERE name='Rameshwaram Sea View Retreat'
UNION ALL SELECT id, 'LAN-GLA-008', 'Facade Glass Panels', 'Tempered glass panels', 'sqm', 500, 2400.00, 1200000.00 FROM projects WHERE name='Lanka Skyline Towers'
UNION ALL SELECT id, 'NAN-PNT-009', 'Acrylic Paint', 'Exterior weather coat', 'bucket', 150, 1800.00, 270000.00 FROM projects WHERE name='Nandigram Civic Center'
UNION ALL SELECT id, 'SHR-RCK-010', 'Paver Blocks', 'Heavy duty paver blocks', 'pcs', 7000, 48.00, 336000.00 FROM projects WHERE name='Shringaverpur Logistics Park';

-- ============================================================
-- review_requests (10)
-- ============================================================
INSERT INTO review_requests (project_id, submitted_by, subject, description, urgency, status)
SELECT p.id, u.id, 'Foundation Recheck', 'Need structural recheck before slab casting', 'high', 'pending' FROM projects p JOIN users u ON p.name='Ayodhya Rajmarg Villa' AND u.username='arjuna'
UNION ALL SELECT p.id, u.id, 'Budget Adjustment', 'Client requested alternate materials', 'normal', 'approved' FROM projects p JOIN users u ON p.name='Mithila Heritage Home' AND u.username='sahadeva'
UNION ALL SELECT p.id, u.id, 'Site Safety Audit', 'Electrical and scaffold audit required', 'critical', 'changes_requested' FROM projects p JOIN users u ON p.name='Kishkindha Office Hub' AND u.username='vidura'
UNION ALL SELECT p.id, u.id, 'RCC Mix Clarification', 'Grade mismatch observed in report', 'high', 'rejected' FROM projects p JOIN users u ON p.name='Chitrakoot Riverside Residency' AND u.username='arjuna'
UNION ALL SELECT p.id, u.id, 'Interior Layout Freeze', 'Final sign-off needed for interior zones', 'normal', 'approved' FROM projects p JOIN users u ON p.name='Panchavati Studio Complex' AND u.username='sahadeva'
UNION ALL SELECT p.id, u.id, 'Topography Re-Survey', 'Contours differ from initial records', 'high', 'pending' FROM projects p JOIN users u ON p.name='Dandakaranya Green Township' AND u.username='vidura'
UNION ALL SELECT p.id, u.id, 'Coastal Permit Update', 'Re-upload revised coastal documents', 'critical', 'changes_requested' FROM projects p JOIN users u ON p.name='Rameshwaram Sea View Retreat' AND u.username='yudhishthira'
UNION ALL SELECT p.id, u.id, 'Facade Vendor Approval', 'Need management approval for vendor L-3', 'normal', 'pending' FROM projects p JOIN users u ON p.name='Lanka Skyline Towers' AND u.username='arjuna'
UNION ALL SELECT p.id, u.id, 'Completion Audit', 'Post-completion quality review', 'low', 'approved' FROM projects p JOIN users u ON p.name='Nandigram Civic Center' AND u.username='vidura'
UNION ALL SELECT p.id, u.id, 'Tender Revalidation', 'Costs changed after market movement', 'high', 'pending' FROM projects p JOIN users u ON p.name='Shringaverpur Logistics Park' AND u.username='sahadeva';

-- ============================================================
-- worker_ratings (10)
-- ============================================================
INSERT INTO worker_ratings (worker_id, rated_by, rating, comment)
SELECT id, 'Arjuna Pandava', 5, 'Excellent speed and quality' FROM users WHERE username='bhima'
UNION ALL SELECT id, 'Sahadeva Pandava', 4, 'Good coordination and execution' FROM users WHERE username='karna'
UNION ALL SELECT id, 'Vidura Mahamantri', 4, 'Reliable on critical deadlines' FROM users WHERE username='nakula'
UNION ALL SELECT id, 'Yudhishthira Pandava', 5, 'Outstanding site commitment' FROM users WHERE username='abhimanyu'
UNION ALL SELECT id, 'Arjuna Pandava', 4, 'Strong workmanship' FROM users WHERE username='bhima'
UNION ALL SELECT id, 'Sahadeva Pandava', 5, 'Very good handling of materials' FROM users WHERE username='karna'
UNION ALL SELECT id, 'Vidura Mahamantri', 3, 'Needs improvement in reporting' FROM users WHERE username='nakula'
UNION ALL SELECT id, 'Yudhishthira Pandava', 4, 'Consistent on quality checks' FROM users WHERE username='abhimanyu'
UNION ALL SELECT id, 'Arjuna Pandava', 5, 'Handled difficult tasks smoothly' FROM users WHERE username='bhima'
UNION ALL SELECT id, 'Sahadeva Pandava', 4, 'Good team behavior' FROM users WHERE username='karna';

-- ============================================================
-- leave_requests (10)
-- ============================================================
INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status, approved_by)
SELECT u.id, 'Sick Leave', '2026-04-02', '2026-04-04', 'Seasonal fever', 'approved', a.id FROM users u JOIN users a ON u.username='bhima' AND a.username='yudhishthira'
UNION ALL SELECT u.id, 'Casual Leave', '2026-04-10', '2026-04-12', 'Family ceremony in Mithila', 'pending', a.id FROM users u JOIN users a ON u.username='karna' AND a.username='yudhishthira'
UNION ALL SELECT u.id, 'Earned Leave', '2026-05-01', '2026-05-05', 'Personal travel to Chitrakoot', 'approved', a.id FROM users u JOIN users a ON u.username='nakula' AND a.username='yudhishthira'
UNION ALL SELECT u.id, 'Casual Leave', '2026-05-08', '2026-05-09', 'Academic exam', 'rejected', a.id FROM users u JOIN users a ON u.username='abhimanyu' AND a.username='yudhishthira'
UNION ALL SELECT u.id, 'Sick Leave', '2026-05-14', '2026-05-16', 'Back pain', 'on_leave', a.id FROM users u JOIN users a ON u.username='arjuna' AND a.username='yudhishthira'
UNION ALL SELECT u.id, 'Casual Leave', '2026-05-20', '2026-05-21', 'Religious visit to Rameshwaram', 'approved', a.id FROM users u JOIN users a ON u.username='sahadeva' AND a.username='yudhishthira'
UNION ALL SELECT u.id, 'Earned Leave', '2026-06-01', '2026-06-03', 'Home renovation', 'pending', a.id FROM users u JOIN users a ON u.username='vidura' AND a.username='yudhishthira'
UNION ALL SELECT u.id, 'Casual Leave', '2026-06-07', '2026-06-08', 'Community event', 'approved', a.id FROM users u JOIN users a ON u.username='duryodhana' AND a.username='yudhishthira'
UNION ALL SELECT u.id, 'Sick Leave', '2026-06-12', '2026-06-14', 'Viral infection', 'approved', a.id FROM users u JOIN users a ON u.username='kunti' AND a.username='yudhishthira'
UNION ALL SELECT u.id, 'Earned Leave', '2026-06-20', '2026-06-24', 'Pilgrimage to Panchavati', 'pending', a.id FROM users u JOIN users a ON u.username='subhadra' AND a.username='yudhishthira';

-- ============================================================
-- contact_messages (10)
-- ============================================================
INSERT INTO contact_messages (first_name, last_name, email, subject, message) VALUES
('Bhishma', 'Pitamah', 'bhishma.msg@thefinal.in', 'Site Query', 'Need update on Ayodhya material dispatch.'),
('Drona', 'Acharya', 'drona.msg@thefinal.in', 'Consultation', 'Requesting structural consultation for Mithila project.'),
('Kripa', 'Acharya', 'kripa.msg@thefinal.in', 'Support', 'Please share revised invoice for Kishkindha office hub.'),
('Ashwatthama', 'Drona', 'ashwatthama.msg@thefinal.in', 'Timeline', 'What is the expected completion for Panchavati studio?'),
('Shakuni', 'Gandhara', 'shakuni.msg@thefinal.in', 'Pricing', 'Need commercial package quote for Lanka site.'),
('Gandhari', 'Kuru', 'gandhari.msg@thefinal.in', 'Meeting', 'Schedule a meeting for Dandakaranya township.'),
('Balarama', 'Yadava', 'balarama.msg@thefinal.in', 'Vendor', 'Please approve listed vendor for steel supply.'),
('Satyaki', 'Vrishni', 'satyaki.msg@thefinal.in', 'Inspection', 'Inspection report upload appears missing.'),
('Ulupi', 'Naga', 'ulupi.msg@thefinal.in', 'Design', 'Need fresh elevation concept for riverside property.'),
('Hidimba', 'Rakshasi', 'hidimba.msg@thefinal.in', 'Account', 'Unable to view notification details in dashboard.');

-- ============================================================
-- services (10)
-- ============================================================
INSERT INTO services (title, description, price, active) VALUES
('Residential Planning', 'End-to-end residential planning and drawings', 45000.00, 1),
('Commercial Architecture', 'Commercial project architecture and execution planning', 85000.00, 1),
('Interior Design Package', 'Interior layout, material board, and execution drawings', 60000.00, 1),
('3D Visualization', '3D renders and walkthrough support', 30000.00, 1),
('Structural Consultation', 'Structural system review and optimization', 55000.00, 1),
('MEP Coordination', 'MEP planning and clash-resolution support', 50000.00, 1),
('Site Supervision', 'Weekly site supervision and compliance checks', 40000.00, 1),
('Cost Estimation', 'Detailed BOQ and cost estimation package', 25000.00, 1),
('Renovation Advisory', 'Renovation strategy and phased execution advisory', 35000.00, 1),
('Landscape Design', 'Landscape and external development planning', 20000.00, 1);

-- ============================================================
-- notifications (10)
-- ============================================================
INSERT INTO notifications (user_id, type, title, body, is_read)
SELECT id, 'project', 'Project Assigned', 'You were assigned to Ayodhya Rajmarg Villa.', 0 FROM users WHERE username='bhima'
UNION ALL SELECT id, 'review', 'Review Request Raised', 'Your request for Mithila Heritage Home is under review.', 0 FROM users WHERE username='sahadeva'
UNION ALL SELECT id, 'leave', 'Leave Approved', 'Your leave request has been approved.', 1 FROM users WHERE username='arjuna'
UNION ALL SELECT id, 'finance', 'Invoice Updated', 'Goods invoice was updated for Kishkindha Office Hub.', 0 FROM users WHERE username='vidura'
UNION ALL SELECT id, 'project', 'Milestone Completed', 'Nandigram Civic Center reached 100% completion.', 1 FROM users WHERE username='yudhishthira'
UNION ALL SELECT id, 'security', 'Token Expiry Alert', 'Your session token expires in 2 days.', 0 FROM users WHERE username='nakula'
UNION ALL SELECT id, 'review', 'Changes Requested', 'Please update coastal documents for Rameshwaram project.', 0 FROM users WHERE username='arjuna'
UNION ALL SELECT id, 'account', 'Profile Updated', 'Your profile details were updated successfully.', 1 FROM users WHERE username='kunti'
UNION ALL SELECT id, 'project', 'Tender Reopened', 'Shringaverpur project tender has been reopened.', 0 FROM users WHERE username='subhadra'
UNION ALL SELECT id, 'general', 'Welcome', 'Welcome to TheFinal platform.', 1 FROM users WHERE username='draupadi';

-- ============================================================
-- role_groups (at least 10 total)
-- Existing: employee, worker
-- Added: 8 more
-- ============================================================
INSERT IGNORE INTO role_groups (code, name) VALUES
('client_group', 'Client Group'),
('management', 'Management'),
('design', 'Design Team'),
('engineering', 'Engineering Team'),
('site_ops', 'Site Operations'),
('finance', 'Finance Team'),
('support', 'Support Team'),
('qa', 'Quality Assurance');

-- ============================================================
-- roles (already > 10; add extra role samples)
-- ============================================================
INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT id, 'cli_premium_client', 'Premium Client', 'Premium client with enhanced visibility' FROM role_groups WHERE code='client_group';
INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT id, 'mgr_operations_head', 'Operations Head', 'Leads operations across projects' FROM role_groups WHERE code='management';
INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT id, 'des_ui_planner', 'UI Planner', 'Design planning specialist' FROM role_groups WHERE code='design';
INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT id, 'eng_structural_specialist', 'Structural Specialist', 'Advanced structural role' FROM role_groups WHERE code='engineering';
INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT id, 'site_hse_officer', 'HSE Officer', 'Health safety environment officer' FROM role_groups WHERE code='site_ops';
INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT id, 'fin_auditor', 'Finance Auditor', 'Audits invoices and spending' FROM role_groups WHERE code='finance';
INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT id, 'sup_helpdesk_agent', 'Helpdesk Agent', 'Support ticket operations' FROM role_groups WHERE code='support';
INSERT IGNORE INTO roles (role_group_id, code, name, description)
SELECT id, 'qa_site_inspector', 'Site Inspector', 'Project quality inspections' FROM role_groups WHERE code='qa';

-- ============================================================
-- permissions (already > 10; add extra permission samples)
-- ============================================================
INSERT IGNORE INTO permissions (code, resource, action, description) VALUES
('project.export', 'project', 'export', 'Export project data'),
('reports.view', 'reports', 'view', 'View operational reports'),
('reports.generate', 'reports', 'generate', 'Generate operational reports'),
('notifications.manage', 'notifications', 'manage', 'Manage user notifications'),
('services.manage', 'services', 'manage', 'Manage service catalog');

-- ============================================================
-- role_permissions (10+)
-- ============================================================
INSERT IGNORE INTO role_permissions (role_id, permission_id, is_allowed)
SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='emp_admin_manager' AND p.code='users.manage'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='emp_admin_manager' AND p.code='dashboard.admin'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='emp_accountant' AND p.code='finance.goods.manage'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='emp_architect' AND p.code='project.update'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='emp_cad_operator' AND p.code='project.files.manage'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='wrk_contractor' AND p.code='project.view'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='wrk_electrician' AND p.code='project.view'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='emp_hr' AND p.code='leave.manage'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='mgr_operations_head' AND p.code='reports.view'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='mgr_operations_head' AND p.code='reports.generate'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='fin_auditor' AND p.code='finance.goods.manage'
UNION ALL SELECT r.id, p.id, 1 FROM roles r JOIN permissions p ON r.code='sup_helpdesk_agent' AND p.code='notifications.manage';

-- ============================================================
-- user_roles (10+)
-- ============================================================
INSERT IGNORE INTO user_roles (user_id, role_id, is_primary, assigned_by)
SELECT u.id, r.id, 1, a.id FROM users u JOIN roles r JOIN users a
ON u.username='yudhishthira' AND r.code='emp_admin_manager' AND a.username='yudhishthira'
UNION ALL SELECT u.id, r.id, 1, a.id FROM users u JOIN roles r JOIN users a ON u.username='bhima' AND r.code='wrk_contractor' AND a.username='yudhishthira'
UNION ALL SELECT u.id, r.id, 1, a.id FROM users u JOIN roles r JOIN users a ON u.username='arjuna' AND r.code='emp_architect' AND a.username='yudhishthira'
UNION ALL SELECT u.id, r.id, 1, a.id FROM users u JOIN roles r JOIN users a ON u.username='nakula' AND r.code='wrk_carpenter' AND a.username='yudhishthira'
UNION ALL SELECT u.id, r.id, 1, a.id FROM users u JOIN roles r JOIN users a ON u.username='sahadeva' AND r.code='emp_estimate' AND a.username='yudhishthira'
UNION ALL SELECT u.id, r.id, 1, a.id FROM users u JOIN roles r JOIN users a ON u.username='draupadi' AND r.code='cli_premium_client' AND a.username='yudhishthira'
UNION ALL SELECT u.id, r.id, 1, a.id FROM users u JOIN roles r JOIN users a ON u.username='karna' AND r.code='wrk_electrician' AND a.username='yudhishthira'
UNION ALL SELECT u.id, r.id, 1, a.id FROM users u JOIN roles r JOIN users a ON u.username='duryodhana' AND r.code='emp_team_coordinator' AND a.username='yudhishthira'
UNION ALL SELECT u.id, r.id, 1, a.id FROM users u JOIN roles r JOIN users a ON u.username='vidura' AND r.code='mgr_operations_head' AND a.username='yudhishthira'
UNION ALL SELECT u.id, r.id, 1, a.id FROM users u JOIN roles r JOIN users a ON u.username='subhadra' AND r.code='cli_premium_client' AND a.username='yudhishthira';

-- ============================================================
-- dashboard_modules (already has 10; add 2 optional)
-- ============================================================
INSERT IGNORE INTO dashboard_modules (code, name, route) VALUES
('reports_dashboard', 'Reports Dashboard', '/admin/reports_dashboard.php'),
('notifications_center', 'Notifications Center', '/Common/notifications.php');

-- ============================================================
-- role_dashboard_access (10+)
-- ============================================================
INSERT IGNORE INTO role_dashboard_access (role_id, module_id, can_view, can_create, can_update, can_delete)
SELECT r.id, m.id, 1, 1, 1, 1 FROM roles r JOIN dashboard_modules m ON r.code='emp_admin_manager' AND m.code='admin_dashboard'
UNION ALL SELECT r.id, m.id, 1, 1, 1, 1 FROM roles r JOIN dashboard_modules m ON r.code='emp_admin_manager' AND m.code='project_management'
UNION ALL SELECT r.id, m.id, 1, 1, 1, 1 FROM roles r JOIN dashboard_modules m ON r.code='emp_admin_manager' AND m.code='user_management'
UNION ALL SELECT r.id, m.id, 1, 1, 1, 0 FROM roles r JOIN dashboard_modules m ON r.code='emp_hr' AND m.code='leave_management'
UNION ALL SELECT r.id, m.id, 1, 1, 1, 0 FROM roles r JOIN dashboard_modules m ON r.code='emp_accountant' AND m.code='goods_invoice'
UNION ALL SELECT r.id, m.id, 1, 1, 1, 0 FROM roles r JOIN dashboard_modules m ON r.code='emp_accountant' AND m.code='goods_manage'
UNION ALL SELECT r.id, m.id, 1, 0, 1, 0 FROM roles r JOIN dashboard_modules m ON r.code='wrk_contractor' AND m.code='worker_dashboard'
UNION ALL SELECT r.id, m.id, 1, 0, 1, 0 FROM roles r JOIN dashboard_modules m ON r.code='wrk_contractor' AND m.code='worker_project_details'
UNION ALL SELECT r.id, m.id, 1, 0, 0, 0 FROM roles r JOIN dashboard_modules m ON r.code='cli_premium_client' AND m.code='project_details'
UNION ALL SELECT r.id, m.id, 1, 0, 0, 0 FROM roles r JOIN dashboard_modules m ON r.code='mgr_operations_head' AND m.code='reports_dashboard';

-- ============================================================
-- project_access_levels (10 total)
-- Existing 5 + added 5
-- ============================================================
INSERT IGNORE INTO project_access_levels (code, name, rank_value, description) VALUES
('readonly', 'Read Only', 5, 'Read-only visibility for selected projects'),
('reviewer', 'Reviewer', 6, 'Can review and annotate project artifacts'),
('approver', 'Approver', 7, 'Can approve key project actions'),
('auditor', 'Auditor', 8, 'Audit visibility with logs and reports'),
('super_admin', 'Super Admin', 9, 'Full unrestricted project access');

-- ============================================================
-- role_project_access (already >10; add more explicit mappings)
-- ============================================================
INSERT IGNORE INTO role_project_access (role_id, project_access_level_id)
SELECT r.id, p.id FROM roles r JOIN project_access_levels p ON r.code='cli_premium_client' AND p.code='readonly'
UNION ALL SELECT r.id, p.id FROM roles r JOIN project_access_levels p ON r.code='mgr_operations_head' AND p.code='approver'
UNION ALL SELECT r.id, p.id FROM roles r JOIN project_access_levels p ON r.code='qa_site_inspector' AND p.code='reviewer'
UNION ALL SELECT r.id, p.id FROM roles r JOIN project_access_levels p ON r.code='fin_auditor' AND p.code='auditor'
UNION ALL SELECT r.id, p.id FROM roles r JOIN project_access_levels p ON r.code='emp_admin_manager' AND p.code='super_admin'
UNION ALL SELECT r.id, p.id FROM roles r JOIN project_access_levels p ON r.code='emp_architect' AND p.code='reviewer'
UNION ALL SELECT r.id, p.id FROM roles r JOIN project_access_levels p ON r.code='emp_estimate' AND p.code='reviewer'
UNION ALL SELECT r.id, p.id FROM roles r JOIN project_access_levels p ON r.code='wrk_contractor' AND p.code='assigned'
UNION ALL SELECT r.id, p.id FROM roles r JOIN project_access_levels p ON r.code='wrk_electrician' AND p.code='assigned'
UNION ALL SELECT r.id, p.id FROM roles r JOIN project_access_levels p ON r.code='sup_helpdesk_agent' AND p.code='readonly';

-- ============================================================
-- project_user_access (10)
-- ============================================================
INSERT IGNORE INTO project_user_access (project_id, user_id, project_access_level_id, granted_by)
SELECT p.id, u.id, a.id, g.id
FROM projects p
JOIN users u ON u.username='draupadi'
JOIN project_access_levels a ON a.code='readonly'
JOIN users g ON g.username='yudhishthira'
WHERE p.name='Ayodhya Rajmarg Villa'
UNION ALL
SELECT p.id, u.id, a.id, g.id FROM projects p JOIN users u ON u.username='kunti' JOIN project_access_levels a ON a.code='readonly' JOIN users g ON g.username='yudhishthira' WHERE p.name='Mithila Heritage Home'
UNION ALL
SELECT p.id, u.id, a.id, g.id FROM projects p JOIN users u ON u.username='subhadra' JOIN project_access_levels a ON a.code='readonly' JOIN users g ON g.username='yudhishthira' WHERE p.name='Kishkindha Office Hub'
UNION ALL
SELECT p.id, u.id, a.id, g.id FROM projects p JOIN users u ON u.username='arjuna' JOIN project_access_levels a ON a.code='reviewer' JOIN users g ON g.username='yudhishthira' WHERE p.name='Chitrakoot Riverside Residency'
UNION ALL
SELECT p.id, u.id, a.id, g.id FROM projects p JOIN users u ON u.username='vidura' JOIN project_access_levels a ON a.code='approver' JOIN users g ON g.username='yudhishthira' WHERE p.name='Panchavati Studio Complex'
UNION ALL
SELECT p.id, u.id, a.id, g.id FROM projects p JOIN users u ON u.username='sahadeva' JOIN project_access_levels a ON a.code='reviewer' JOIN users g ON g.username='yudhishthira' WHERE p.name='Dandakaranya Green Township'
UNION ALL
SELECT p.id, u.id, a.id, g.id FROM projects p JOIN users u ON u.username='karna' JOIN project_access_levels a ON a.code='assigned' JOIN users g ON g.username='yudhishthira' WHERE p.name='Rameshwaram Sea View Retreat'
UNION ALL
SELECT p.id, u.id, a.id, g.id FROM projects p JOIN users u ON u.username='nakula' JOIN project_access_levels a ON a.code='assigned' JOIN users g ON g.username='yudhishthira' WHERE p.name='Lanka Skyline Towers'
UNION ALL
SELECT p.id, u.id, a.id, g.id FROM projects p JOIN users u ON u.username='bhima' JOIN project_access_levels a ON a.code='assigned' JOIN users g ON g.username='yudhishthira' WHERE p.name='Nandigram Civic Center'
UNION ALL
SELECT p.id, u.id, a.id, g.id FROM projects p JOIN users u ON u.username='abhimanyu' JOIN project_access_levels a ON a.code='assigned' JOIN users g ON g.username='yudhishthira' WHERE p.name='Shringaverpur Logistics Park';

COMMIT;

-- Verification helper (optional):
-- SELECT 'users' AS table_name, COUNT(*) AS total FROM users
-- UNION ALL SELECT 'signup', COUNT(*) FROM signup
-- UNION ALL SELECT 'auth_tokens', COUNT(*) FROM auth_tokens
-- UNION ALL SELECT 'projects', COUNT(*) FROM projects
-- UNION ALL SELECT 'project_assignments', COUNT(*) FROM project_assignments
-- UNION ALL SELECT 'project_workers', COUNT(*) FROM project_workers
-- UNION ALL SELECT 'project_milestones', COUNT(*) FROM project_milestones
-- UNION ALL SELECT 'project_files', COUNT(*) FROM project_files
-- UNION ALL SELECT 'project_activity', COUNT(*) FROM project_activity
-- UNION ALL SELECT 'project_drawings', COUNT(*) FROM project_drawings
-- UNION ALL SELECT 'project_goods', COUNT(*) FROM project_goods
-- UNION ALL SELECT 'review_requests', COUNT(*) FROM review_requests
-- UNION ALL SELECT 'worker_ratings', COUNT(*) FROM worker_ratings
-- UNION ALL SELECT 'leave_requests', COUNT(*) FROM leave_requests
-- UNION ALL SELECT 'contact_messages', COUNT(*) FROM contact_messages
-- UNION ALL SELECT 'services', COUNT(*) FROM services
-- UNION ALL SELECT 'notifications', COUNT(*) FROM notifications
-- UNION ALL SELECT 'role_groups', COUNT(*) FROM role_groups
-- UNION ALL SELECT 'roles', COUNT(*) FROM roles
-- UNION ALL SELECT 'permissions', COUNT(*) FROM permissions
-- UNION ALL SELECT 'role_permissions', COUNT(*) FROM role_permissions
-- UNION ALL SELECT 'user_roles', COUNT(*) FROM user_roles
-- UNION ALL SELECT 'dashboard_modules', COUNT(*) FROM dashboard_modules
-- UNION ALL SELECT 'role_dashboard_access', COUNT(*) FROM role_dashboard_access
-- UNION ALL SELECT 'project_access_levels', COUNT(*) FROM project_access_levels
-- UNION ALL SELECT 'role_project_access', COUNT(*) FROM role_project_access
-- UNION ALL SELECT 'project_user_access', COUNT(*) FROM project_user_access;
