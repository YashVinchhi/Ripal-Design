# SQL Folder

Contains the database schema and seed statements used by the project.

Files
- `database.sql`: SQL dump with tables, indexes and sample data. Use this to create the initial schema locally.

Usage
- Import into MySQL/MariaDB with:

```bash
mysql -u root -p ripal_db < sql/database.sql
```

Notes
- Review credentials and adapt to your environment. The application `includes/db.php` expects a database named `ripal_db` by default.
