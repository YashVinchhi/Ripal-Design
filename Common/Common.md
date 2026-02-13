# Common Folder

Shared UI fragments used across multiple pages (header, footer variations).

Files
- `header.php`: Emits common CSS/JS includes, fonts and a site navigation header. It inspects `PROJECT_ROOT` and `BASE_PATH` to include the correct stylesheet fallback. Also starts a session if needed.
- `header_alt.php` and `header_alt.css`: Alternative header and styles for different designs.
- `footer.php`: Shared footer markup included by pages.

Usage
- Include these in pages with:

```php
require_once __DIR__ . '/../includes/header.php';
// page content
require_once __DIR__ . '/../includes/footer.php';
```
