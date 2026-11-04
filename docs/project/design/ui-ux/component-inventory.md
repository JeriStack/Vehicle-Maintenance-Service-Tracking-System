# Reusable Component Inventory
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-UI-CMP-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, Design Tokens (`VMS-DOC-UI-TOK-001`)  
**Status:** Approved Design Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Component Architecture & Principles

The VMS frontend utilizes a modular, reusable UI component architecture implemented with semantic HTML5, Bootstrap 5.3 utilities, and progressive vanilla JavaScript enhancements. 

### Core Design Rules:
1. **Single Source of Truth:** Shared components serve both the Vehicle Owner and System Administrator views, parameterized by props/configuration rather than duplicating code.
2. **Accessible by Default:** All interactive elements feature visible `:focus-visible` rings, ARIA roles, associated `<label>` elements, and non-color-only state cues.
3. **Graceful Mobile Degradation:** Components automatically adapt to small viewports without horizontal clipping or broken touch interactions.

---

## 2. Global Application Shell & Navigation Components

### 2.1 Top Application Header (`CMP-SHL-01`)
- **Purpose:** Persistent top navigation bar hosting brand identity, global search, WebSocket live status indicator, notification bell, and user profile menu.
- **Properties / Inputs:** `user_name`, `user_role`, `unread_notifications_count`, `ws_connected_status`.
- **States:** Default (Connected), WebSocket Disconnected (Amber pulse), Unread Alerts Active (Badge glowing).
- **Accessibility:** Header wrapped in `<header role="banner">`; profile menu toggles `aria-expanded="true/false"`; notification count announced via `aria-live="polite"`.
- **Responsive Behavior:** Height `64px` sticky; search input collapses into an icon button on screens `< 768px`.
- **Location:** `includes/header.php`.

### 2.2 Role-Aware Sidebar Navigation (`CMP-SHL-02`)
- **Purpose:** Primary vertical navigation displaying role-specific routes, active indicators, and category groupings.
- **Properties / Inputs:** `active_route`, `user_role` (`admin` | `user`), `badge_counts` (e.g., overdue alerts count).
- **States:** Fully Expanded (`260px`), Icon-Collapsed (`80px`), Off-Canvas Mobile Drawer (`width: 280px`).
- **Accessibility:** Nav container wrapped in `<nav aria-label="Main Navigation">`; active route indicated by `aria-current="page"`.
- **Location:** `includes/sidebar.php`.

### 2.3 Breadcrumb Bar (`CMP-SHL-03`)
- **Purpose:** Hierarchical wayfinding bar showing current position in application tree.
- **Properties / Inputs:** Array of `{ label: string, url: string|null }`.
- **States:** Default, Truncated on mobile (shows current page and back chevron).
- **Accessibility:** `<nav aria-label="breadcrumb">` containing `<ol class="breadcrumb">`.
- **Location:** `includes/breadcrumbs.php`.

---

## 3. Data Presentation & Analytics Components

### 3.1 Metric KPI Card (`CMP-DAT-01`)
- **Purpose:** High-impact metric display card for dashboard overviews (Total Vehicles, Due Services, YTD Spend, System Health).
- **Properties / Inputs:** `title`, `value`, `icon_class`, `badge_text`, `badge_color`, `subtext`, `href` (optional link).
- **States:** Default, Hover elevation, Loading skeleton shimmer.
- **Accessibility:** Contrast >= 4.5:1 between text and card surface; screen-reader text for status icons.
- **Responsive Behavior:** 4-column desktop grid (`col-lg-3`), 2-column tablet (`col-md-6`), full-width mobile (`col-12`).
- **Location:** `includes/components/metric_card.php`.

### 3.2 Responsive Data Table with Card Degradation (`CMP-DAT-02`)
- **Purpose:** Primary tabular data display for vehicles, service histories, users, and audit logs.
- **Properties / Inputs:** `headers[]`, `rows[]`, `sortable_columns[]`, `actions[]`, `empty_message`.
- **States:** Populated, Hover row, Loading skeleton rows, Empty state illustration, Sorted column header.
- **Accessibility:** Uses semantic `<thead>`, `<tbody>`, `<th scope="col">`; sortable headers indicate `aria-sort="ascending|descending"`.
- **Responsive Behavior:** Standard tabular rows on desktop; transforms into stacked `.table-mobile-cards` on mobile `< 768px`.
- **Location:** `includes/components/data_table.php`.

### 3.3 Semantic Status Badge (`CMP-DAT-03`)
- **Purpose:** Non-color-alone status indicator for maintenance targets, account states, and service flags.
- **Properties / Inputs:** `status_type` (`OVERDUE`, `DUE`, `UPCOMING`, `GOOD`, `ACTIVE`, `ARCHIVED`, `VOIDED`), `icon`, `label`.
- **States:** Standard pill badge, Compact dot badge.
- **Accessibility:** Always pairs text label with recognizable icon (e.g., warning triangle for Overdue); contrast >= 4.5:1.
- **Location:** `helpers/ViewHelper.php::renderStatusBadge()`.

### 3.4 Pagination Bar (`CMP-DAT-04`)
- **Purpose:** Navigating multi-page dataset results (users, services, audit logs).
- **Properties / Inputs:** `current_page`, `total_pages`, `base_url`, `query_params`.
- **States:** Active page, Disabled (First/Prev on page 1), Hover.
- **Accessibility:** Wrapped in `<nav aria-label="Table pagination">`; active page marked with `aria-current="page"`.
- **Responsive Behavior:** Condenses to "Page X of Y" with Prev/Next buttons on mobile screens.
- **Location:** `includes/components/pagination.php`.

---

## 4. Form Controls & Validation Components

### 4.1 Floating / Standard Form Input Group (`CMP-FRM-01`)
- **Purpose:** Accessible form input with leading icon, field label, placeholder, and validation feedback.
- **Properties / Inputs:** `name`, `label`, `type` (`text`, `email`, `password`, `number`), `value`, `required`, `hint`, `error`.
- **States:** Default, Focused (`--color-border-focus`), Validated (`.is-valid`), Invalid (`.is-invalid`), Disabled.
- **Accessibility:** `<label for="{id}">` explicitly linked to `<input id="{id}">`; errors linked via `aria-describedby="{id}-error"`.
- **Location:** `includes/components/form_input.php`.

### 4.2 Show / Hide Password Input Group (`CMP-FRM-02`)
- **Purpose:** Secure password entry with interactive toggle button to reveal plaintext.
- **Properties / Inputs:** `name`, `label`, `minlength`, `show_strength_meter` (boolean).
- **States:** Hidden (dots), Visible (plaintext), Focused, Strength indicator levels (Weak, Fair, Strong).
- **Accessibility:** Eye toggle button features `aria-label="Show password as plain text"`, changes to "Hide password".
- **Location:** `includes/components/password_input.php`.

### 4.3 Monotonic Mileage Input (`CMP-FRM-03`)
- **Purpose:** Odometer input field enforcing monotonic advancement rules against current vehicle mileage.
- **Properties / Inputs:** `current_mileage`, `name="odometer"`, `unit` (`km` | `mi`).
- **States:** Valid (greater than current), Warning (same as current), Invalid (lower than current).
- **Accessibility:** Dynamic validation hint announced via `aria-live="polite"`.
- **Location:** `includes/components/mileage_input.php`.

---

## 5. Feedback, Dialogues & Modal Components

### 5.1 Real-Time Floating Notification Toast (`CMP-FDB-01`)
- **Purpose:** Non-blocking floating alert pushed via WebSocket or generated after successful action completion.
- **Properties / Inputs:** `title`, `message`, `severity` (`success`, `warning`, `danger`, `info`), `timestamp`, `auto_dismiss_ms`.
- **States:** Slide-in from top-right, Hover (pauses auto-dismiss), Dismissed.
- **Accessibility:** Rendered in `<div aria-live="assertive" aria-atomic="true">` container with accessible close button.
- **Location:** `assets/js/notifications.js`, `includes/toast_container.php`.

### 5.2 Confirmation Modal Dialogue (`CMP-FDB-02`)
- **Purpose:** Blocking dialog requiring explicit user affirmation for sensitive or destructive actions (Archive Vehicle, Void Service, Deactivate User, Promote Role).
- **Properties / Inputs:** `modal_id`, `title`, `body_html`, `confirm_button_text`, `confirm_button_class` (`btn-danger`, `btn-warning`), `action_url`, `csrf_token`.
- **States:** Closed, Opening, Visible with dark backdrop, Submitting (button spinner).
- **Accessibility:** Traps keyboard focus within modal; `role="dialog"`; `aria-modal="true"`; closes on `Escape` key.
- **Responsive Behavior:** Centered dialog on desktop; converts to bottom-sheet drawer on mobile `< 576px`.
- **Location:** `includes/components/confirm_modal.php`.

### 5.3 Empty State Container (`CMP-FDB-03`)
- **Purpose:** Illustrated informative state when a query, table, or garage has zero records.
- **Properties / Inputs:** `icon_class` (or SVG), `title`, `description`, `action_label`, `action_url`.
- **States:** Standard, Compact (for widgets).
- **Accessibility:** Descriptive heading (`h3`); primary action button clearly focused.
- **Location:** `includes/components/empty_state.php`.

### 5.4 Loading Skeleton Shimmer (`CMP-FDB-04`)
- **Purpose:** Placeholder visual feedback while AJAX queries or reports load, preventing content layout shift (CLS).
- **Properties / Inputs:** `variant` (`card`, `table_rows`, `chart`).
- **States:** Animated shimmer pulse (`--color-surface-muted`).
- **Accessibility:** Container tagged with `aria-busy="true"` and `aria-label="Loading content..."`.
- **Location:** `includes/components/skeleton.php`.

---

## 6. Domain-Specific Automotive Components

### 6.1 Vehicle Garage Card (`CMP-AUT-01`)
- **Purpose:** Visual garage card presenting vehicle specs, photo placeholder, license badge, and current odometer.
- **Properties / Inputs:** `vehicle_id`, `make`, `model`, `year`, `nickname`, `plate_number`, `current_mileage`, `status`, `health_badge`.
- **Location:** `vehicles/index.php`.

### 6.2 Dual-Criteria Maintenance Progress Bar (`CMP-AUT-02`)
- **Purpose:** Visual gauge illustrating "whichever comes first" maintenance progress across both Date and Distance thresholds.
- **Properties / Inputs:** `days_remaining`, `days_total`, `km_remaining`, `km_total`, `status` (`OVERDUE`, `DUE`, `UPCOMING`, `GOOD`).
- **Location:** `maintenance/index.php`, `dashboard/index.php`.

### 6.3 Audit Trail Detail Modal (`CMP-AUT-03`)
- **Purpose:** Administrator tool to inspect complete cryptographic/system metadata for a specific audit log entry.
- **Properties / Inputs:** `log_id`, `actor_email`, `action`, `ip_address`, `user_agent`, `details_json`, `created_at`.
- **Location:** `admin/audit_logs.php`.

