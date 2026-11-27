# Role-Specific Navigation & Information Architecture
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-UI-NAV-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, Phase 1 Requirements Baseline, Phase 2 Authorization Matrix  
**Status:** Approved Design Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Information Architecture & Security Boundary

The Vehicle Maintenance Service Tracking System enforces two distinct navigation architectures corresponding to the two official application roles:
- **Vehicle Owner Navigation:** Focused on garage asset tracking, service transaction logging, maintenance interval monitoring, cost reporting, and personal preferences.
- **System Administrator Navigation:** Focused on platform governance, user lifecycle management, role delegation, global service catalog curation, system health monitoring, and audit log inspection.

> [!IMPORTANT]
> **Navigation Visibility vs. Server-Side Authorization:**  
> Hiding menu items from the UI is merely a user experience pattern and does **not** constitute a security control. Every destination route, action endpoint, and AJAX handler independently verifies the session role and resource ownership server-side in accordance with [`VMS-DOC-DES-AM-001`](file:///c:/xampp/htdocs/GitHub_Clone_File_Projects/Vehicle-Maintenance-Service-Tracking-System/docs/project/design/routing/authorization-matrix.md).

---

## 2. Vehicle Owner Navigation Structure (`role = 'user'`)

```text
VEHICLE MAINTENANCE (Owner Portal)
│
├── 📊 Dashboard                         (/dashboard/index.php)
│
├── 🚗 Vehicles
│   ├── All Vehicles (Garage)           (/vehicles/index.php)
│   └── ➕ Add New Vehicle               (/vehicles/add.php [or Modal])
│
├── 🔧 Services
│   ├── Service Records                 (/services/index.php)
│   ├── ➕ Log Service Record            (/services/add.php [or Modal])
│   └── Service History & Invoices      (/services/history.php)
│
├── ⏱️ Maintenance Schedules
│   ├── Overview Board                  (/maintenance/index.php)
│   ├── Upcoming (Window Active)        (/maintenance/index.php?status=upcoming)
│   ├── Due Now                         (/maintenance/index.php?status=due)
│   └── Overdue (Alert)                 (/maintenance/index.php?status=overdue)
│
├── 🔔 Notifications                     (/notifications/index.php)
│
├── 📈 Reports & Analytics
│   ├── Maintenance Spend Summary       (/reports/index.php?tab=spend)
│   ├── Service History Report          (/reports/index.php?tab=history)
│   └── Mileage Progression Trends      (/reports/index.php?tab=mileage)
│
└── ⚙️ Settings
    ├── Profile Information             (/settings/index.php?section=profile)
    ├── Security & Password             (/settings/index.php?section=password)
    ├── Notification Channels           (/settings/index.php?section=notifications)
    └── Vehicle Interval Defaults       (/settings/index.php?section=preferences)
```

---

## 3. System Administrator Navigation Structure (`role = 'admin'`)

```text
VMS ADMINISTRATION (Platform Governance)
│
├── 🏛️ Admin Dashboard                  (/admin/index.php)
│
├── 👥 User Management
│   ├── All Accounts                    (/admin/users.php)
│   ├── Active Accounts                 (/admin/users.php?status=active)
│   └── Suspended / Inactive Accounts   (/admin/users.php?status=inactive)
│
├── 🛡️ Administrator Privilege Control  (/admin/roles.php)
│
├── 📋 Global Service Catalog           (/admin/catalog.php)
│
├── 📜 Security Audit Logs              (/admin/audit_logs.php)
│
├── 💻 System Health & Telemetry        (/admin/system.php)
│
└── 👤 Administrator Account
    ├── Profile                         (/settings/index.php?section=profile)
    ├── Change Password                 (/settings/index.php?section=password)
    └── 🚪 Logout                       (/auth/logout.php)
```

---

## 4. Navigation States & Active Route Mapping

1. **Active Route Indication:**
   - Active navigation item receives class `.active`, background `--color-brand-navy-light` (`#334155`), text `--color-text-inverse` (`#ffffff`), and a prominent left cyan accent border (`4px solid #0dcaf0`).
   - Active item emits accessibility attribute `aria-current="page"`.
2. **Badge Counters:**
   - *Owner Sidebar:* Overdue maintenance items render a red pill badge (`.badge bg-danger`) next to the "Maintenance Schedules" menu item.
   - *Top Navigation Bell:* Dynamic counter showing unread notification count; auto-updates via WebSocket listener without page refresh.
3. **Collapsible Sub-Menus:**
   - Uses standard Bootstrap 5 collapse controls (`data-bs-toggle="collapse"`).
   - Chevron icon rotates 90 degrees smoothly (`transition: transform 0.2s ease-in-out`) when sub-menu expands.

---

## 5. Responsive Mobile Navigation Adaptations

1. **Off-Canvas Slide Drawer (`< 992px`):**
   - Left sidebar unpins from desktop layout and converts to an off-canvas slide-out drawer (`.offcanvas-start`).
   - Triggered by header hamburger icon (`#sidebarToggle`); closes automatically when clicking outside the drawer, clicking a link, or pressing the `Escape` key.
2. **Mobile Bottom Quick-Action Bar (`< 576px`):**
   - Persistent sticky bottom bar on smartphone viewports hosting four primary tap targets:
     1. **Home:** `/dashboard/index.php` (or `/admin/index.php`)
     2. **Garage:** `/vehicles/index.php` (or Users for Admin)
     3. **Services:** `/services/index.php` (or Catalog for Admin)
     4. **Alerts:** `/notifications/index.php` (or Audit Logs for Admin)

