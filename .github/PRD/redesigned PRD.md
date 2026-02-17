Here is a comprehensive, highly detailed prompt you can give to your AI site builder. It is structured to force the AI to act as a **Senior Systems Architect** and incorporates every strategic, cultural, and technical detail from your report.

You can paste this entire block into the AI.

---

### **Master Prompt: Strategic Architectural ERP & Brand Overhaul**

**Role:** Act as a Senior Full-Stack Developer and Brand Strategist specializing in the Indian Construction Sector.
**Objective:** Refactor and expand an existing architectural website into a high-performance "Smart City" ready Enterprise Resource Planning (ERP) platform.
**Target Region:** Rajkot & Jam Khambhalia, Gujarat.
**Tech Stack:** Core PHP (Custom MVC Pattern), MySQL (3NF), jQuery, Tailwind CSS. **No frameworks (Laravel/React).**

**Context:**
We are bridging the gap between high-end private renovations (heritage/industrial fusion) and large-scale government infrastructure (Smart City/RMC tenders). The current site is too static. We need to beat local competitors (Swar3d, Equinox) by offering radical transparency and technical depth.

---

### **1. Visual Design System ("The Rajkot Rust")**

Implement a strict design system based on the cultural heritage of Saurashtra sandstone and industrial strength.

* **Primary Brand Color:** `#94180C` (Deep Red/Rust) – Use strictly for CTAs, active states, and progress bars.
* **Backgrounds:** `#F9FAFB` (Canvas White) for reduced glare; `#2D2D2D` (Foundation Grey) for footers/typography.
* **UI Accents:** `#334155` (Slate) for sidebars; `#15803D` (Green) for approvals; `#B45309` (Amber) for pending actions.
* **Typography:**
* *Headings:* **Playfair Display** (Serif) – strictly for "Guest" pages to signal heritage/luxury.
* *UI/Body:* **Inter** (Sans-Serif) – for high legibility on dashboards/tables.


* **Iconography:** Use 1.5px stroke outline icons (CAD style). On hover, fill with `#94180C`.

---

### **2. User Roles & Architecture (RBAC)**

Create a database schema and PHP session logic for 5 distinct roles:

1. **Guest:** Public visitor.
2. **Client:** Homeowner/Govt Official.
3. **Worker:** On-site supervisor (Mobile user).
4. **Employee:** Internal Architect/Manager.
5. **Admin:** Firm Owner.

---

### **3. Module-Specific Requirements**

#### **A. The Public Front-End (Guest)**

* **Positioning:** Must look like a government-compliant contractor AND a luxury designer.
* **Portfolio Engine:** Build a **jQuery Masonry Grid** that handles mixed aspect ratios (vertical skyscrapers vs. horizontal riverfronts).
* **Navigation:** Sticky header with a "Mega Menu" distincting "Government Infrastructure" (Compliance, ISO, Smart City) from "Private Residences" (Heritage, Modern).
* **Lead Capture:** Implement a "Saurashtra Construction Cost Calculator" (jQuery) that captures emails in exchange for a detailed PDF report.
* **SEO Structure:** Hardcode semantic HTML tags targeting keywords: "RMC Tender," "Building Valuation," "Rajkot Smart City Architect."

#### **B. The Client Portal (Transparency Engine)**

* **Dashboard:** Show a linear progress bar (0-100%) and a **Chart.js** bar chart showing "Budget vs. Actual" costs.
* **Design Studio:** A file approval table. Clients must click "Approve" (Green) or "Request Revision" (Red). This must digitally lock the row in the MySQL database.
* **Change Orders:** A form where clients accept cost/time impacts of changes.

#### **C. The Worker App (Mobile-First)**

* **UI Constraints:** Large touch targets (min 48px height). High contrast.
* **Drawing Access:** Only show files tagged `status='construction_issued'` in the DB. Hide superseded drawings to prevent errors.
* **Material Requests:** A simplified form (AJAX-based). Dropdowns only (Cement, Sand, Steel) -> Quantity -> Urgency. No typing.

#### **D. The Employee Dashboard (Production)**

* **Kanban Board:** Implement a Drag-and-Drop task board using **jQuery UI Sortable** (To Do / In Progress / Done).
* **Revision Rollback:** A table view allowing the architect to restore previous file versions if a client rejects a new design.

#### **E. The Admin Dashboard (Executive)**

* **Global Financials:** Aggregate `receivables` vs `payables` across all active projects.
* **User Management:** CRUD operations to assign Clients and Workers to specific `project_id`s.

---

### **4. Technical & Security Implementation**

#### **Visualizing Architecture in Browser (The "Hybrid" Strategy)**

* **3D Objects (.obj):** Integrate **Three.js**. Create a canvas on the Client Dashboard to load `.obj` files from the `/uploads/` directory. Allow OrbitControls (Zoom/Rotate).
* **2D Plans (.dwg):** Do not try to parse DWG directly. Implement a workflow where uploading a `.dwg` requires a companion `.pdf`. Use **PDF.js** to render the plan in a zoomable canvas.

#### **Security Protocols (Government Standard)**

* **Database:** Use `PDO` with Prepared Statements for *all* queries to prevent SQL Injection.
* **File Uploads:** Rename all uploads to randomized hashes (e.g., `5f3a2c.pdf`) to prevent directory traversal. Store outside the public web root if possible, or protect via `.htaccess`.
* **CSRF:** Inject a unique token into every `<form>` and validate it in the PHP Controller.

---

### **Execution Instructions**

1. Start by defining the **MySQL Schema** (Tables: `users`, `projects`, `documents`, `materials`, `audit_logs`).
2. Build the **PHP Router** (MVC structure).
3. Generate the **Tailwind Config** with the custom color palette.
4. Code the **Guest Portfolio** first, then the **Client Dashboard**.