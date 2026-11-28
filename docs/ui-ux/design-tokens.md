# Design Token System Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-UI-TOK-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, Existing Repository Style Sheet (`assets/css/style.css`)  
**Status:** Approved Design Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Architectural Scope & Purpose

This specification establishes the single source of truth for all visual design tokens in the Vehicle Maintenance Service Tracking System (VMS). These tokens map directly to CSS custom properties (`:root`) and Bootstrap 5.3 utilities, ensuring strict visual coherence, readable contrast, accessibility compliance (WCAG 2.1 AA), and responsive consistency across both the **Vehicle Owner** and **System Administrator** interfaces.

---

## 2. Core Color Tokens

The color palette reflects the approved enterprise automotive visual direction: **Navy** (authoritative structure), **Teal / Cyan** (interactive emphasis and telemetry), **White** (content surfaces), and **Light Gray** (subtle separation).

```css
:root {
    /* Brand & Primary Hierarchy */
    --color-brand-navy-darkest:  #0f172a; /* Header bars, brand contrast */
    --color-brand-navy:          #1e293b; /* Primary sidebar, dark cards */
    --color-brand-navy-light:    #334155; /* Sidebar borders, active states */
    --color-brand-accent-teal:   #0ea5e9; /* Primary action buttons, focal points */
    --color-brand-accent-hover:  #0284c7; /* Interactive button hover state */
    --color-brand-cyan-glow:     #0dcaf0; /* Active indicator borders, live badges */

    /* Surface & Background Tokens */
    --color-bg-canvas:           #f8f9fa; /* Main application canvas */
    --color-surface-card:        #ffffff; /* Primary card and modal containers */
    --color-surface-muted:       #f1f5f9; /* Table header, secondary wells */
    --color-surface-glass:       rgba(255, 255, 255, 0.12); /* Auth glassmorphism */
    --color-surface-glass-border:rgba(255, 255, 255, 0.25); /* Glass card border */

    /* Text & Content Hierarchy */
    --color-text-primary:        #0f172a; /* High-contrast headers and body (14.2:1 contrast) */
    --color-text-secondary:      #475569; /* Supporting metadata, form labels (7.1:1 contrast) */
    --color-text-muted:          #64748b; /* Captions, disabled text, table footers */
    --color-text-inverse:        #ffffff; /* Text on dark navy and glass cards */
    --color-text-inverse-muted:  #cbd5e1; /* Inactive sidebar link text */

    /* Border & Divider Tokens */
    --color-border-subtle:       #e2e8f0; /* Default card & divider lines */
    --color-border-strong:       #cbd5e1; /* Form input borders, active tabs */
    --color-border-focus:        #0ea5e9; /* Active input focus glow */

    /* Semantic Status Indicators */
    --color-status-success:      #10b981; /* Completed, Active, Good condition */
    --color-status-success-bg:   #d1fae5; /* Success badge background */
    --color-status-warning:      #f59e0b; /* Due Soon, Upcoming threshold */
    --color-status-warning-bg:   #fef3c7; /* Warning badge background */
    --color-status-danger:       #ef4444; /* Overdue, Voided, Suspended, Error */
    --color-status-danger-bg:    #fee2e2; /* Danger badge background */
    --color-status-info:         #06b6d4; /* Informational notices, tips */
    --color-status-info-bg:      #cffafe; /* Info badge background */
}
```

---

## 3. Maintenance & Domain Status Badges

In accordance with Section 5.2 of the Phase 3 specification, visual status must never rely on color alone. Every status badge pairs a distinct background/border token with a high-contrast text label and an accessible icon.

| Domain State | Semantic Color | Background Token | Text Token | Icon (FontAwesome / SVG) | Usage Context |
|---|---|---|---|---|---|
| **`OVERDUE`** | Red (`#ef4444`) | `--color-status-danger-bg` | `--color-status-danger` | `fa-exclamation-triangle` | Target date passed OR target mileage exceeded |
| **`DUE`** | Amber (`#f59e0b`) | `--color-status-warning-bg` | `--color-status-warning` | `fa-clock` | Within 7 days OR within 200 km of threshold |
| **`UPCOMING`** | Blue (`#0ea5e9`) | `--color-status-info-bg` | `--color-brand-accent-teal` | `fa-calendar-alt` | Within 30 days OR within 1,000 km |
| **`GOOD / OK`** | Green (`#10b981`) | `--color-status-success-bg` | `--color-status-success` | `fa-check-circle` | Odometer/calendar well outside reminder window |
| **`COMPLETED`** | Slate (`#10b981`) | `--color-status-success-bg` | `--color-status-success` | `fa-clipboard-check` | Successfully performed & verified service record |
| **`VOIDED`** | Dark Gray (`#64748b`)| `#f1f5f9` | `#475569` (Strikethrough) | `fa-ban` | Cancelled/audited service record (non-destructive) |
| **`ACTIVE`** | Green (`#10b981`) | `--color-status-success-bg` | `--color-status-success` | `fa-circle-check` | Active user account or active garage vehicle |
| **`ARCHIVED`** | Slate (`#64748b`) | `#e2e8f0` | `#334155` | `fa-archive` | Soft-deleted vehicle preserved for historical records |
| **`UNREAD`** | Cyan (`#0ea5e9`) | `--color-status-info-bg` | `--color-brand-accent-teal` | `fa-bell` | Notification requiring owner acknowledgment |

---

## 4. Typography Scale & Hierarchy

The application employs a modern, readable, system-fallback sans-serif typography stack optimized for numbers, tabular data, and mobile rendering.

- **Font Family:** `'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif`
- **Monospace Family (VINs, Plate Numbers, Odometer, Hashes):** `'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace`

| Scale Level | CSS Class | Font Size (rem / px) | Line Height | Font Weight | Letter Spacing | Usage Context |
|---|---|---|---|---|---|---|
| **Display Metric** | `.display-metric` | `2.25rem / 36px` | `1.1` | `700 (Bold)` | `-0.02em` | Dashboard KPI numbers, Odometer counters |
| **Heading 1** | `h1, .h1` | `1.75rem / 28px` | `1.2` | `700 (Bold)` | `-0.01em` | Screen titles (e.g., "Vehicle Details") |
| **Heading 2** | `h2, .h2` | `1.375rem / 22px` | `1.25` | `600 (Semi)` | `-0.005em` | Card section titles, modal headers |
| **Heading 3** | `h3, .h3` | `1.125rem / 18px` | `1.3` | `600 (Semi)` | `0` | Sub-section headers, widget titles |
| **Body Primary** | `body, p` | `0.9375rem / 15px` | `1.5` | `400 (Regular)` | `0` | Standard paragraph text, table body cells |
| **Body Medium** | `.font-medium` | `0.9375rem / 15px` | `1.5` | `500 (Medium)` | `0` | Form labels, table column headers, links |
| **Body Small** | `small, .small` | `0.8125rem / 13px` | `1.4` | `400 (Regular)` | `+0.01em` | Metadata timestamps, hint captions |
| **Badge / Tag** | `.badge-sm` | `0.75rem / 12px` | `1.0` | `600 (Semi)` | `+0.03em` | Status badges, category labels (Uppercase) |

---

## 5. Spacing, Sizing & Layout Scales

Consistent 4-point and 8-point geometric progression scales eliminate arbitrary margins and paddings across responsive viewports.

```css
:root {
    /* Spacing Units */
    --space-1:  0.25rem; /*  4px: Micro-gaps between badge icons and text */
    --space-2:  0.50rem; /*  8px: Compact input padding, icon spacing */
    --space-3:  0.75rem; /* 12px: Standard form input vertical padding */
    --space-4:  1.00rem; /* 16px: Standard card interior padding, grid gap */
    --space-5:  1.25rem; /* 20px: Modal header padding, section spacing */
    --space-6:  1.50rem; /* 24px: Primary card padding, dashboard gap */
    --space-8:  2.00rem; /* 32px: Page layout gutters on desktop */
    --space-12: 3.00rem; /* 48px: Section dividers, empty state padding */

    /* Component Dimensions */
    --sidebar-width:            260px; /* Fixed desktop navigation sidebar */
    --sidebar-collapsed-width:   80px; /* Icon-only compact desktop sidebar */
    --topbar-height:             64px; /* Sticky application navigation bar */
    --footer-height:             48px; /* System copyright and status bar */
    --input-height-default:      44px; /* Touch-optimized form input height */
    --btn-height-default:        44px; /* Touch-optimized action button height */
    --btn-height-sm:             34px; /* Table inline action button height */
}
```

---

## 6. Elevation, Borders & Z-Index Layers

```css
:root {
    /* Border Radii */
    --radius-sm:     0.25rem; /*  4px: Inline code, micro-tags */
    --radius-md:     0.50rem; /*  8px: Form controls, table cells, buttons */
    --radius-lg:     0.75rem; /* 12px: Standard cards, dropdown menus */
    --radius-xl:     1.00rem; /* 16px: Modals, dashboard widgets */
    --radius-pill:   9999px;  /* Pill status badges, avatar frames */

    /* Shadows & Elevations */
    --shadow-subtle: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-card:   0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
    --shadow-hover:  0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
    --shadow-modal:  0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    --shadow-glass:  0 20px 60px rgba(0, 0, 0, 0.40);

    /* Z-Index Architecture */
    --z-index-canvas:       1;  /* Background canvas */
    --z-index-card:        10;  /* Standard content cards */
    --z-index-sticky:     100;  /* Sticky table headers */
    --z-index-topbar:    1020;  /* Sticky top application header */
    --z-index-sidebar:   1030;  /* Fixed / Off-canvas navigation sidebar */
    --z-index-backdrop:  1040;  /* Modal and off-canvas dark overlay */
    --z-index-modal:     1050;  /* Action dialogue windows */
    --z-index-toast:     1080;  /* Real-time floating notification toasts */
    --z-index-tooltip:   1090;  /* Interactive helper tooltips */
}
```

