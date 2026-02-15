# Ripal Design - Developer Quick Reference

## Table of Contents
1. [Getting Started](#getting-started)
2. [File Includes](#file-includes)
3. [Common Functions](#common-functions)
4. [Forms](#forms)
5. [Validation](#validation)
6. [Database](#database)
7. [Authentication](#authentication)
8. [Asset Management](#asset-management)
9. [CSS Variables](#css-variables)

---

## Getting Started

### Every PHP File Should Start With:
```php
<?php require_once __DIR__ . '/../includes/init.php'; ?>
```

### Basic Page Structure:
```php
<?php require_once __DIR__ . '/../includes/init.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Title</title>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
</head>
<body>
    <?php render_flash(); ?>
    
    <!-- Your content here -->
    
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
```

---

## File Includes

| What You Need | Include This |
|---------------|-------------|
| Everything (config, DB, auth, utils) | `includes/init.php` |
| Header HTML | `includes/header.php` |
| Footer HTML | `includes/footer.php` |
| Form helpers only | `includes/forms.php` |
| Validation helpers only | `includes/validation.php` |

---

## Common Functions

### Output Escaping (ALWAYS USE!)
```php
echo esc($user_input);                    // HTML escape
<input value="<?php echo esc_attr($val); ?>">  // Attribute escape
<script>var data = <?php echo esc_js($data); ?>;</script>  // JS escape
```

### URLs
```php
base_url()                    // http://example.com/app
base_url('public/login.php')  // http://example.com/app/public/login.php
base_path()                   // /app
base_path('assets/logo.png')  // /app/assets/logo.png
asset_url('images/logo.png')  // http://example.com/app/assets/images/logo.png
```

### Redirects
```php
redirect('/dashboard');
redirect(base_url('public/index.php'));
```

### Flash Messages
```php
// Set message
set_flash('Profile updated!', 'success'); // Types: success, error, warning, info
redirect('/profile');

// Display message (in view)
render_flash();
```

### Dates
```php
format_date($dateString);                  // 2026-02-14
format_date($dateString, 'F j, Y');       // February 14, 2026
```

---

## Forms

### Simple Form
```php
<?php
echo form_start('/submit', 'POST');
echo '<div class="mb-3">';
echo '<label>Email</label>';
echo form_input('email', old('email'), ['class' => 'form-control']);
echo form_error('email');
echo '</div>';
echo form_button('Submit');
echo form_end();
?>
```

### All Form Helpers

```php
// Text input
form_input('name', 'John', ['placeholder' => 'Name', 'required' => true]);

// Password input
form_input('password', '', ['type' => 'password', 'required' => true]);

// Textarea
form_textarea('message', '', ['rows' => 5, 'placeholder' => 'Message']);

// Select dropdown
$options = ['1' => 'Option 1', '2' => 'Option 2'];
form_select('choice', $options, '1');

// Checkbox
form_checkbox('agree', '1', true, 'I agree to terms');

// Radio button
form_radio('gender', 'male', true, 'Male');

// Button
form_button('Submit', 'submit', ['class' => 'btn btn-primary']);

// Get old value (after validation error)
old('email', 'default@example.com');

// Show error
form_error('email');
```

---

## Validation

### Basic Validation
```php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    
    $rules = [
        'email' => 'required|email',
        'password' => 'required|min:8|strong_password',
        'name' => 'required|min:2|max:100',
        'age' => 'required|numeric',
        'phone' => 'required|phone'
    ];
    
    $errors = validate_data($data, $rules);
    
    if (empty($errors)) {
        // Process valid data
        set_flash('Success!', 'success');
        redirect('/success');
    } else {
        store_validation_errors($errors);
        store_old_input($data);
        redirect($_SERVER['REQUEST_URI']);
    }
}
?>
```

### Available Validation Rules

| Rule | Example | Description |
|------|---------|-------------|
| required | `'required'` | Field must not be empty |
| email | `'email'` | Must be valid email |
| min | `'min:8'` | Minimum length |
| max | `'max:255'` | Maximum length |
| numeric | `'numeric'` | Must be a number |
| alpha | `'alpha'` | Letters only |
| alphanumeric | `'alphanumeric'` | Letters and numbers only |
| phone | `'phone'` | Valid phone number |
| url | `'url'` | Valid URL |
| date | `'date'` | Valid date (Y-m-d) |
| match | `'match:field_name'` | Must match another field |
| in | `'in:admin,user,guest'` | Must be one of allowed values |
| strong_password | `'strong_password'` | 8+ chars, uppercase, lowercase, number |

### Individual Validators
```php
validate_required($value);
validate_email($email);
validate_min_length($str, 8);
validate_max_length($str, 255);
validate_numeric($value);
validate_alpha($str);
validate_alphanumeric($str);
validate_phone($phone);
validate_url($url);
validate_date($date);
validate_match($val1, $val2);
validate_in($value, ['allowed', 'values']);
validate_password_strength($password);
```

---

## Database

### Simple Query
```php
global $pdo;

// Check if connected
if (!db_connected()) {
    die('Database not available');
}

// Execute query
$stmt = db_query("SELECT * FROM users WHERE id = ?", [$userId]);
$user = $stmt->fetch();

// Fetch one row
$user = db_fetch("SELECT * FROM users WHERE id = ?", [$userId]);

// Fetch all rows
$users = db_fetch_all("SELECT * FROM users WHERE active = ?", [1]);

// Insert
$stmt = db_query(
    "INSERT INTO users (name, email) VALUES (?, ?)",
    [$name, $email]
);
$newId = $pdo->lastInsertId();

// Update
db_query("UPDATE users SET name = ? WHERE id = ?", [$name, $userId]);

// Delete
db_query("DELETE FROM users WHERE id = ?", [$userId]);
```

---

## Authentication

### Check Login Status
```php
// Redirect to login if not logged in
require_login();

// Check if logged in (returns true/false)
if (is_logged_in()) {
    echo "Welcome, " . esc(current_user()['name']);
}

// Get current user
$user = current_user(); // Returns array or null
if ($user) {
    echo $user['name'];
    echo $user['email'];
}
```

### Role-Based Access
```php
// Check if user has role
if (has_role('admin')) {
    // Show admin content
}

// Require specific role (redirects if not met)
require_role('admin');

// Redirect on fail
require_role('admin', base_url('public/index.php'));
```

---

## Asset Management

### Enqueue Assets
```php
<?php
// In your page (before header)
asset_enqueue_css('public/css/custom.css');
asset_enqueue_css('https://example.com/external.css');

asset_enqueue_js('public/js/custom.js', ['defer' => true]);
asset_enqueue_js('https://example.com/external.js', ['async' => true]);
?>

<!-- In header -->
<?php render_head_assets(); ?>

<!-- In footer -->
<?php render_footer_scripts(); ?>
```

---

## CSS Variables

### Using Variables
```css
.my-element {
    background-color: var(--primary);
    color: var(--text-primary);
    border: 1px solid var(--border-light);
    padding: var(--spacing-md);
}
```

### Available Variables

#### Colors
```css
--primary             /* #731209 - Brand maroon */
--primary-light       /* #94180C */
--primary-dark        /* #5a0e07 */

--bg-dark             /* #0a0a0a */
--bg-darker           /* #050505 */
--bg-panel            /* #121212 */
--bg-card             /* #1a1a1a */

--text-primary        /* #ffffff */
--text-secondary      /* rgba(255,255,255,0.7) */
--text-muted          /* rgba(255,255,255,0.6) */

--border-light        /* rgba(255,255,255,0.1) */
--border-medium       /* rgba(255,255,255,0.2) */
```

#### Transitions
```css
--transition-fast     /* 0.15s */
--transition-normal   /* 0.3s */
--transition-slow     /* 0.5s */

--easing-standard     /* cubic-bezier(0.4,0,0.2,1) */
```

---

## Common Patterns

### Protected Page
```php
<?php
require_once __DIR__ . '/../includes/init.php';
require_login(); // Redirect to login if not logged in

$user = current_user();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Protected Page</title>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
</head>
<body>
    <h1>Welcome, <?php echo esc($user['name']); ?></h1>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
```

### Admin-Only Page
```php
<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin'); // Only allow admins
?>
```

### Form with Validation
```php
<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validate_data($_POST, [
        'name' => 'required|min:2',
        'email' => 'required|email'
    ]);
    
    if (empty($errors)) {
        // Save to database
        db_query(
            "INSERT INTO contacts (name, email) VALUES (?, ?)",
            [$_POST['name'], $_POST['email']]
        );
        
        set_flash('Thank you! We will contact you soon.', 'success');
        redirect($_SERVER['REQUEST_URI']);
    } else {
        store_validation_errors($errors);
        store_old_input($_POST);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Contact Form</title>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
</head>
<body>
    <?php render_flash(); ?>
    
    <?php echo form_start($_SERVER['PHP_SELF']); ?>
        <div class="mb-3">
            <label>Name</label>
            <?php echo form_input('name', old('name'), ['class' => 'form-control']); ?>
            <?php echo form_error('name'); ?>
        </div>
        
        <div class="mb-3">
            <label>Email</label>
            <?php echo form_input('email', old('email'), ['type' => 'email', 'class' => 'form-control']); ?>
            <?php echo form_error('email'); ?>
        </div>
        
        <?php echo form_button('Submit'); ?>
    <?php echo form_end(); ?>
    
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
```

---

## Security Checklist

- [ ] Use `esc()` for all user-generated output
- [ ] Use `esc_attr()` for all HTML attributes
- [ ] Use prepared statements for all database queries
- [ ] Validate all form input server-side
- [ ] Use `require_login()` for protected pages
- [ ] Use `require_role()` for role-based access
- [ ] Never trust `$_GET`, `$_POST`, or `$_REQUEST` directly
- [ ] Use `store_old_input()` carefully (it filters passwords)
- [ ] Set proper environment variables for production

---

## Need Help?

1. Check `REFACTORING_DOCUMENTATION.md` for detailed information
2. Look at example pages in `public/` directory
3. Review existing code that follows these patterns
4. All helper functions are documented with PHPDoc comments
