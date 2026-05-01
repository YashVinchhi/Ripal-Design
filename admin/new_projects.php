<?php
$projectRoot = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 1);
require_once $projectRoot . '/app/Core/Bootstrap/init.php';
require_login();
require_role('admin');

$action = '../dashboard/project_details.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>New Project — Admin</title>
  <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="project-management-sharp bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-serif font-bold">Initialize Venture</h1>
        <p class="text-gray-300 mt-1 text-sm">Create a new project record for the portfolio</p>
      </div>
      <div>
        <a href="<?php echo esc_attr(base_path('admin/project_management.php')); ?>" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 px-4 py-2 text-sm font-bold no-underline">Back to Portfolio</a>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
    <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8">
      <h2 class="text-xl md:text-2xl font-serif font-bold mb-4">Create New Project</h2>

      <form method="post" action="<?php echo htmlspecialchars($action); ?>" enctype="multipart/form-data" class="space-y-6">
      <?php echo csrf_token_field(); ?>

      <div>
        <label class="block text-sm font-bold mb-2">Project Name <span style="color:#c00">*</span></label>
        <input name="name" required class="styled-input w-full" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold mb-2">Status</label>
          <select name="status" class="styled-select w-full">
            <option value="planning">Conceptual / Planning</option>
            <option value="ongoing" selected>Construction Ongoing</option>
            <option value="paused">Approval Pending</option>
            <option value="completed">Project Handover</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-bold mb-2">Progress (%)</label>
          <input name="progress" type="number" min="0" max="100" value="0" class="styled-input w-full" />
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-bold mb-2">Budget (INR)</label>
          <input name="budget" type="number" step="0.01" class="styled-input w-full" />
        </div>
        <div>
          <label class="block text-sm font-bold mb-2">Due Date</label>
          <input name="due" type="date" class="styled-input w-full" />
        </div>
        <div>
          <label class="block text-sm font-bold mb-2">Area (sqft)</label>
          <input name="area_sqft" type="text" class="styled-input w-full" />
        </div>
      </div>

      <div>
        <label class="block text-sm font-bold mb-2">Location / City</label>
        <input name="location" class="styled-input w-full" />
      </div>

      <div>
        <label class="block text-sm font-bold mb-2">Address</label>
        <textarea name="address" rows="2" class="styled-input w-full"></textarea>
      </div>

      <div>
        <label class="block text-sm font-bold mb-2">Map link / Place</label>
        <input name="map_link" placeholder="Google Maps URL or address" class="styled-input w-full" />
      </div>

      <h2 class="text-lg font-semibold mt-4">Owner / Client</h2>
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-bold mb-2">Owner Name</label>
          <input name="owner_name" class="styled-input w-full" />
        </div>
        <div>
          <label class="block text-sm font-bold mb-2">Owner Contact</label>
          <input name="owner_contact" class="styled-input w-full" />
        </div>
        <div>
          <label class="block text-sm font-bold mb-2">Owner Email</label>
          <input name="owner_email" type="email" class="styled-input w-full" />
        </div>
      </div>

      <h2 class="text-lg font-semibold mt-4">Additional Fields</h2>
      <div>
        <label class="block text-sm font-bold mb-2">Project Type</label>
        <input name="project_type" class="styled-input w-full" placeholder="residential, commercial, interior, urban" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-bold">SEO Title</label>
          <input name="seo_title" class="w-full border p-2" />
        </div>
        <div>
          <label class="block font-bold">Meta Description</label>
          <input name="meta_description" class="w-full border p-2" />
        </div>
      </div>

      <div>
        <label class="block text-sm font-bold mb-2">Project Cover Images</label>
        <input type="file" name="project_photo[]" accept="image/*" multiple class="block" />
      </div>

      <div class="flex items-center gap-4">
        <label class="inline-flex items-center">
          <input type="checkbox" name="is_published" value="1" />
          <span class="ml-2">Publish immediately</span>
        </label>

        <div class="ml-auto">
          <button type="submit" class="px-4 py-2 bg-rajkot-rust text-white font-bold">Create Project</button>
          <a href="project_management.php" class="ml-3 text-sm text-gray-600">Cancel</a>
        </div>
      </div>
    </form>
    </section>
  </main>

  <?php if (!defined('HIDE_FOOTER_CTA')) define('HIDE_FOOTER_CTA', true); require_once PROJECT_ROOT . '/Common/footer.php'; ?>
</body>
</html>
