# Changelog

All notable changes to the Ripal Design project refactoring.

## [2.0.0] - 2026-02-14

### 🎯 Major Refactoring Release

This release represents a comprehensive refactoring of the entire codebase to implement industry-standard coding practices, eliminate conflicts, and improve maintainability.

---

### ✨ Added

#### New Core Components
- **`includes/forms.php`** - Complete form helper library for generating Bootstrap-compatible forms
- **`includes/validation.php`** - Server-side validation helpers with 15+ validation rules
- **`assets/css/variables.css`** - Centralized CSS design tokens and variables
- **`assets/js/header-nav.js`** - Extracted navigation menu JavaScript into separate file

#### New Helper Functions (forms.php)
- `form_start()` - Generate form opening tags
- `form_end()` - Generate form closing tags
- `form_input()` - Generate text inputs with proper escaping
- `form_textarea()` - Generate textarea fields
- `form_select()` - Generate select dropdowns
- `form_checkbox()` - Generate checkboxes with labels
- `form_radio()` - Generate radio buttons with labels
- `form_button()` - Generate buttons
- `form_error()` - Display validation errors
- `old()` - Retrieve old form values after validation error

... (trimmed for archive)