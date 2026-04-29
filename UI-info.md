# UI reference — design tokens & rules

Summary of fonts, colors, spacing and responsive rules used across this project. Canonical sources scanned: Tailwind config, central CSS variables, Bootstrap, and template files.

## Canonical token sources
- assets/css/variables.css — central CSS custom properties (brand, backgrounds, text, borders, layout tokens).
- tailwind.config.js — Tailwind `theme.container`, `extend.colors`, `extend.spacing`, `fontFamily` (compiled utilities depend on this).
- bootstrap-5.3.8-dist/css/bootstrap-reboot.css — shipped Bootstrap variables (project overrides applied via `--bs-*` vars).
- Common/header.php (font loader) and templates — where Google Fonts are imported and font choices applied.

## Fonts

- Primary UI (sans): **Inter**
  - Loaded via Google Fonts in header templates (see Common/header.php).
  - Tailwind default `fontFamily.sans` is `Inter, sans-serif` ([tailwind.config.js](tailwind.config.js#L1-L40)).

- Heading / display (serif): **Cormorant Garamond** (used site-wide for headings) and **Playfair Display** (used in some templates).
  - Both imported in header templates (see [Common/header.php](Common/header.php#L95-L100), [Common/header_alt.php](Common/header_alt.php#L23-L26)).

- Additional fonts:
  - **Manrope** — used on login/reset pages ([public/css/login.css](public/css/login.css#L1-L40)).
  - **DejaVu Sans** — used for PDF generation (dompdf) and invoice templates ([app/Domains/Dashboard/Controllers/invoice_pdf_template.php](app/Domains/Dashboard/Controllers/invoice_pdf_template.php#L1-L20)).
  - Fallbacks: Arial / Helvetica / system stacks used in email templates and some server-rendered pages.
  - Monospace stacks used for code blocks: `Consolas`, `SFMono-Regular`, `Menlo`, `Monaco`, `Monospace`.

## Colors

### Central CSS variables (single source)
Found in [assets/css/variables.css](assets/css/variables.css#L1-L120). Key tokens:

```
--primary: #731209        # Brand primary (Ripal maroon)
--primary-light: #94180C  # Lighter brand accent
--primary-dark: #5a0e07
--bs-primary: #731209     # Bootstrap primary overridden to brand

--bg-dark: #0a0a0a
--bg-darker: #050505
--bg-panel: #121212
--bg-card: #1a1a1a

--text-primary: #ffffff
--text-secondary: rgba(255,255,255,0.7)
--text-muted: rgba(255,255,255,0.6)

--surface-light: #f8f9fa
--surface-dark: #1a1a1a

--border-light: rgba(255,255,255,0.1)
--border-medium: rgba(255,255,255,0.2)
```

Use `assets/css/variables.css` to change brand and global surface/text tokens. Bootstrap uses `--bs-*` variables and the project sets `--bs-primary` to the brand value.

### Tailwind custom colors (tailwind.config.js)
Defined under `theme.extend.colors` in [tailwind.config.js](tailwind.config.js#L1-L64):

```
'rajkot-rust': '#94180C'
'canvas-white': '#F9FAFB'
'foundation-grey': '#2D2D2D'
'slate-accent': '#334155'
'approval-green': '#15803D'
'pending-amber': '#B45309'
'background-light': '#F9FAFB'
'background-dark': '#121212'
```

These names are available as Tailwind utility classes (e.g. `bg-rajkot-rust`, `text-approval-green`).

### Bootstrap palette & overrides
- Bootstrap ships its default palette in its CSS (`--bs-primary`, `--bs-secondary`, etc.). This project sets `--bs-primary` to the brand value in `assets/css/variables.css`, so Bootstrap components use the brand color automatically. See [bootstrap-5.3.8-dist/css/bootstrap-reboot.css](bootstrap-5.3.8-dist/css/bootstrap-reboot.css#L8-L36) for defaults.

### Notable one-off hexes found in templates
- `#7f140a` used as a darker hover in a few places (see Common/dashboard_unified.php). 
- Footer color examples in [Common/footer.php](Common/footer.php#L36-L62): `#ffffff`, `#9ca3af`, `#94180C`, `#6b7280` (used for text/links).

## Spacing & layout scales

### Tailwind spacing (project extend)
Defined in [tailwind.config.js](tailwind.config.js#L1-L64) under `extend.spacing`:

```
xs: 0.25rem
sm: 0.5rem
md: 0.75rem
lg: 1rem
xl: 1.5rem
2xl: 2rem
3xl: 3rem
```

These supplement Tailwind's default scale and are used across components (utilities like `p-lg`, `m-xs`, etc.).

### Container settings
Tailwind container config (in `tailwind.config.js`):
- `container.center: true` and padding per screen: DEFAULT/sm = `1rem`; lg/xl/2xl = `2rem`.

### Project-specific layout tokens
- `--tape-height: 48px` and `--year-box-gap: 10px` (used by the measuring-tape/timeline component). See [assets/css/variables.css](assets/css/variables.css#L60-L90).

## Breakpoints (responsive)

- Tailwind (project): `sm: 640px`, `md: 768px`, `lg: 1024px`, `xl: 1280px`, `2xl: 1536px` — defined in `tailwind.config.js` container screens.
- Bootstrap defaults (shipped): `sm: 576px`, `md: 768px`, `lg: 992px`, `xl: 1200px`, `xxl: 1400px` (see Bootstrap CSS).

When implementing responsive spacing or typography, prefer Tailwind utility classes (matching the Tailwind breakpoints above) or component CSS that references the canonical variables.

## Where to change things (single source guidance)

- Brand colors & surfaces: update `assets/css/variables.css` (this file is read by templates and also used to set Bootstrap `--bs-*` variables).
- Tailwind utility names and spacing: update `tailwind.config.js` then rebuild the compiled Tailwind CSS (the project includes a compiled `assets/css/tailwind.css` in the repo).
- Fonts: modify the Google Fonts link in `Common/header.php` (and similar header templates) and keep `tailwind.config.js` `fontFamily` in sync.

## Quick file references
- Central variables: [assets/css/variables.css](assets/css/variables.css#L1-L120)
- Tailwind theme: [tailwind.config.js](tailwind.config.js#L1-L80)
- Font loader: [Common/header.php](Common/header.php#L90-L100)
- Footer examples: [Common/footer.php](Common/footer.php#L36-L62)
- Bootstrap variables/defaults: [bootstrap-5.3.8-dist/css/bootstrap-reboot.css](bootstrap-5.3.8-dist/css/bootstrap-reboot.css#L8-L36)

---
Generated: 2026-04-29 — scanned Tailwind, CSS variables, templates, and Bootstrap files. Update this file when adding new tokens or changing the design system.
