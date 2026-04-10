# Background Gradient

Source: Project details page background in [dashboard/project_details.php](dashboard/project_details.php).

```css
background:
    radial-gradient(circle at 8% 10%, #f5e3dc 0%, transparent 35%),
    radial-gradient(circle at 88% 20%, #f0eee8 0%, transparent 28%),
    linear-gradient(180deg, #fcfbf9 0%, var(--tone-bg) 45%, #f3f2ee 100%);
```

Optional solid fallback:

```css
background-color: #f6f5f2;
```
