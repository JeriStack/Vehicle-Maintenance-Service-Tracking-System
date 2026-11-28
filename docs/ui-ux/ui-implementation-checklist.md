# UI Implementation & Quality Audit Checklist
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-UI-CHK-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, WCAG 2.1 AA Standards  
**Status:** Approved Design Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Overview & Quality Gate

This checklist serves as the mandatory pre-commit and code-review audit gate for every frontend view, template, modal, and component implemented across Sprints 1 through 8. No screen may be merged into `development` without 100% compliance across all five audit categories.

---

## 2. Comprehensive Screen Quality Checklist

### 2.1 Layout & Design Token Adherence
- [ ] **Role-Specific Layout Shell:** View extends the correct base layout (`layout-app.php` with appropriate sidebar for `user` or `admin`, or `layout-public.php` for guest auth).
- [ ] **Consistent Page Header:** Displays semantic `h1` page title, descriptive subtitle, and contextual primary action buttons aligned to the right.
- [ ] **Design Tokens Applied:** Uses CSS variables (`--color-brand-navy`, `--color-brand-accent-teal`, etc.) rather than ad-hoc inline hex colors or hardcoded font sizes.
- [ ] **Grid Architecture:** All card containers and widgets reside within a responsive Bootstrap `.container-fluid` or `.container` using standard 12-column row layouts.
- [ ] **Elevation & Borders:** Cards, tables, and dropdowns conform to established `--radius-lg` (`12px`) and `--shadow-card` tokens.

### 2.2 Operational States Coverage
- [ ] **Default State:** Clean, populated view displaying correctly formatted dates (`YYYY-MM-DD`), currency (`$X,XXX.XX`), and uppercase plate/VIN strings.
- [ ] **Loading State:** Skeleton shimmer loaders (`CMP-FDB-04`) occupy exact geometry of cards or table rows while awaiting AJAX/data loads (no layout shift).
- [ ] **Empty State:** When 0 records exist, displays illustrated empty state (`CMP-FDB-03`) with clear explanatory copy and a prominent call-to-action button.
- [ ] **Validation State:** Invalid input fields receive `.is-invalid` class with inline `.invalid-feedback` text explaining the specific constraint failure.
- [ ] **Error State:** Server or query exceptions trigger a dismissible red alert banner (`.alert .alert-danger`) with friendly, non-leaking user guidance.
- [ ] **Success State:** Action completions (save, update, archive) emit an auto-dismissing floating toast (`CMP-FDB-01`) or persistent green alert banner.
- [ ] **Unauthorized / Forbidden State:** Unauthenticated users redirect to login with return intent; unauthorized roles receive structured `HTTP 403` error screen.

### 2.3 Interactive Functionality & Behavioral Integrity
- [ ] **Clear Primary Call-to-Action:** Exactly one high-visibility primary button (`.btn-primary` with teal accent) per page or modal dialogue.
- [ ] **Accessible Secondary Actions:** Secondary buttons use `.btn-outline-secondary` or `.btn-light` to preserve visual hierarchy.
- [ ] **State-Changing Method Enforcement:** All mutations (Create, Update, Archive, Void) use authenticated `POST` forms with hidden `csrf_token` inputs (no state-changing `GET` links).
- [ ] **Destructive Action Safeguards:** Destructive actions (Void Service, Archive Vehicle, Ban User) require explicit confirmation via modal (`CMP-FDB-02`).
- [ ] **Double-Submission Prevention:** Submit buttons disable and show a loading spinner immediately upon form submission (`btn.disabled = true;`).

### 2.4 Accessibility & Semantic HTML (WCAG 2.1 AA)
- [ ] **Semantic Structure:** Proper landmarks (`<header role="banner">`, `<nav aria-label="...">`, `<main role="main">`, `<footer>`).
- [ ] **Explicit Form Labeling:** Every form input has an associated `<label for="{id}">` element.
- [ ] **Full Keyboard Navigation:** All buttons, links, tabs, and form controls are reachable and operable via `Tab`, `Enter`, and `Spacebar`.
- [ ] **Visible Focus Rings:** Focused interactive elements render a prominent cyan outline (`outline: 2px solid #0dcaf0; outline-offset: 2px;`).
- [ ] **Color Independence:** Status badges combine distinct text labels, icons, and background tokens (never relying on color alone).
- [ ] **High Contrast:** All text content achieves a minimum contrast ratio of **4.5:1** against its background (and **3:1** for large text/icons).
- [ ] **Screen Reader Attributes:** Modal dialogues implement `role="dialog"`, `aria-modal="true"`, and `aria-labelledby="{modal-title-id}"`.

### 2.5 Responsive Viewport Adaptations
- [ ] **Zero Horizontal Clipping:** No unwanted horizontal scrollbars across any viewport from `320px` to `2560px`.
- [ ] **Mobile Table Card Transformation:** Data tables convert to `.table-mobile-cards` on viewports `< 768px`.
- [ ] **Form Input Stacking:** Multi-column inputs stack vertically into single columns on viewports `< 576px`.
- [ ] **Touch Target Sizing:** All touch targets maintain a minimum size of **48px x 48px** with at least **8px** separation.
- [ ] **Thumb Reachability:** On mobile, critical primary actions and modal footers are anchored within comfortable thumb reach zones.

