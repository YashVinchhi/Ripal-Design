# Ripal Design — Professional Architecture & Design Website

A modern, well-structured PHP web application for an architectural design firm, featuring public pages, client portal, worker management, and admin dashboard.

## 🎯 Project Status

**✅ Recently Refactored (February 2026)**
- Complete code cleanup and standardization
- Eliminated duplicate files and conflicting code
- Implemented industry-standard coding practices
- Comprehensive documentation added
- Security enhancements implemented

## 📚 Documentation

- **[REFACTORING_DOCUMENTATION.md](REFACTORING_DOCUMENTATION.md)** - Detailed changelog and improvements
- **[DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)** - Quick reference for developers
- Individual module documentation in respective folders

## 🗂️ Project Structure

```
ripal_design/
├── public/              # Public-facing pages (home, services, about, contact, login)
├── dashboard/           # Authenticated user dashboard
├── admin/              # Admin management tools
├── client/             # Client-specific features
├── worker/             # Worker portal and project tracking
├── includes/           # Core PHP functionality
│   ├── init.php        # Application bootstrap
│   ├── config.php      # Configuration and environment
│   ├── db.php          # Database connection
│   ├── auth.php        # Authentication & authorization
│   ├── util.php        # Utility functions
│   ├── forms.php       # Form helpers (NEW)
│   └── validation.php  # Validation helpers (NEW)
├── Common/             # Shared UI components (header, footer)
├── assets/             # Static assets (CSS, JS, images)
│   ├── css/
│   │   └── variables.css  # Design tokens (NEW)
│   └── js/
│       └── header-nav.js  # Navigation script (NEW)
└── sql/                # Database schema
```

## 🚀 Quick Start

### Prerequisites

- PHP 7.4+ or PHP 8.0+
- MySQL/MariaDB 5.7+
- Apache or Nginx (or PHP built-in server for development)
- Composer (optional, recommended for future enhancements)

### Installation

1. **Clone or download the repository**
   ```bash
   cd /path/to/your/webserver
   git clone <repository-url> ripal-design
   cd ripal-design
   ```

2. **Configure the database**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE ripal_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   
   # Import schema
   mysql -u root -p ripal_db < sql/database.sql
   ```

3. **Set environment variables** (recommended for production)
   ```bash
   # Create .env file or set in your web server config
   export DB_HOST=localhost
   export DB_NAME=ripal_db
   export DB_USER=your_db_user
   export DB_PASS=your_db_password
   export APP_ENV=development  # or 'production'
   ```

4. **Start development server**
   ```bash
   php -S localhost:8000 -t public
   ```

5. **Open in browser**
   ```
   http://localhost:8000
   ```

## 🔧 Configuration

### Database Configuration

Edit `includes/db.php` or use environment variables:

```bash
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ripal_db
DB_USER=dbuser
DB_PASS=dbpass
```

### Application Environment

Set `APP_ENV` to control error display:
- `development` - Show detailed errors
- `production` - Hide errors, log only

## 🛠️ Development

### Coding Standards

This project follows PHP-FIG standards and best practices:

- **PSR-12** code style
- **PHPDoc** documentation for all functions
- **Prepared statements** for all database queries
- **Output escaping** for all user-generated content
- **Validation** for all form inputs

### Creating a New Page

```php
<?php
require_once __DIR__ . '/../includes/init.php';
// Optional: protect page
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Page</title>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
</head>
<body>
    <?php render_flash(); ?>
    
    <main class="container">
        <h1>Welcome to My Page</h1>
        <!-- Your content -->
    </main>
    
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
```

See [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) for more examples.

## 🔐 Security Features

- **Session Management** - Secure, centralized session handling
- **Authentication** - Role-based access control (admin, client, worker)
- **Output Escaping** - XSS protection via `esc()` functions
- **SQL Injection Prevention** - Prepared statements throughout
- **CSRF Protection** - Framework ready (implement tokens as needed)
- **Password Security** - Strong password validation
- **Error Handling** - Environment-aware error display

## 📦 Key Features

### For Administrators
- User management (clients, workers) 
- Project management
- Payment gateway integration
- File management system
- Analytics and reporting

### For Clients
- Project dashboard
- File uploads and revisions
- Progress tracking
- Communication portal

### For Workers
- Assigned project views
- Task management
- Rating system
- Time tracking

### Public Features
- Portfolio showcase
- Service descriptions
- About us and team
- Contact forms
- Responsive design

## 🎨 Customization

### CSS Variables

All design tokens are defined in `assets/css/variables.css`:

```css
:root {
    --primary: #731209;        /* Brand color */
    --bg-dark: #0a0a0a;       /* Dark background */
    --text-primary: #ffffff;   /* Primary text */
    /* ... see variables.css for complete list */
}
```

### Theming

The application supports light/dark themes via CSS variables. Add `.light-theme` class to `<body>` for light theme.

## 🧪 Testing

### Manual Testing Checklist

- [ ] All pages load without errors
- [ ] Login/logout functionality works
- [ ] Form validation works (client & server-side)
- [ ] Database queries execute correctly
- [ ] Flash messages display properly
- [ ] Navigation menu works on all devices
- [ ] Role-based access control functions
- [ ] File uploads work correctly

### Automated Testing (Future)

```bash
# PHPUnit tests (to be implemented)
./vendor/bin/phpunit tests
```

## 📝 Helper Functions Reference

### Common Operations

```php
// Output escaping
esc($data)                    // HTML escape
esc_attr($data)               // Attribute escape

// URLs
base_url('path/to/file.php')  // Generate absolute URL
base_path('path')             // Generate relative path

// Flash messages
set_flash('Message', 'type')  // Set flash message
render_flash()                // Display flash message

// Database
db_query($sql, $params)       // Execute query
db_fetch($sql, $params)       // Fetch one row
db_fetch_all($sql, $params)   // Fetch all rows

// Authentication
require_login()               // Require authentication
current_user()                // Get current user
has_role('admin')             // Check role
```

See [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) for complete function reference.

## 🤝 Contributing

1. Follow the established coding standards
2. Document all functions with PHPDoc
3. Use the helper functions provided
4. Test thoroughly before committing
5. Update documentation as needed

## 📄 License

All rights reserved © 2026 Ripal Design

## 👥 Credits

**Ripal Design Team**
- Architecture & Design
- Project Management
- Web Development

## 📞 Support

For questions or issues:
- **Email:** projects@ripaldesign.in
- **Location:** Rajkot, Gujarat, India

## 🔄 Version History

### v2.0.0 (February 2026) - Major Refactoring
- Complete code cleanup and standardization
- Added reusable components (forms, validation)
- Improved security (session management, auth)
- Created comprehensive documentation
- Removed duplicate files
- Consolidated CSS variables
- Enhanced error handling

### v1.0.0 (2017-2025)
- Initial development
- Core functionality implementation

---

**Note:** This project has been recently refactored to follow modern PHP best practices. See [REFACTORING_DOCUMENTATION.md](REFACTORING_DOCUMENTATION.md) for detailed information about changes.
