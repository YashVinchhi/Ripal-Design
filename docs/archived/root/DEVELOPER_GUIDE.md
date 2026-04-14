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

... (truncated for brevity) ...
