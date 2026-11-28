# Project Charter
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-PC-001  
**Phase:** 0 — Project Initiation and Feasibility  
**Status:** Complete / Pending Formal Academic Gate Approval  
**Last Updated:** 2026-11-05  

---

## 1. Project Overview

The **Vehicle Maintenance Service Tracking System (VMS)** is a secure, responsive, full-stack web application developed in native PHP and MySQL/MariaDB. It serves as a centralized platform designed to assist vehicle owners in tracking vehicle health, recording completed maintenance transactions, projecting upcoming servicing requirements, and receiving proactive date- and mileage-based reminders.

The system simultaneously empowers system administrators to govern platform security, oversee user account lifecycles, configure the global maintenance service catalog, and monitor system audit trails while upholding owner data privacy boundaries.

---

## 2. Problem Statement

Vehicle owners frequently struggle with managing recurring automotive maintenance, vehicle registration renewals, and service expense tracking:

1. **Fragmented & Forgotten Maintenance Schedules:** Vehicle owners rely on physical window stickers, scattered paper invoices, or personal memory. This leads to missed oil changes, deferred brake inspections, and unexpected mechanical breakdowns.
2. **Registration Lapses:** Missing statutory registration renewal deadlines results in financial penalties, impound risks, and invalidated vehicular insurance.
3. **Financial Opacity:** Owners lack visibility into total cost of ownership (TCO), monthly/yearly maintenance expenditures, and part replacement histories across multiple personal vehicles.
4. **Resale Value Erosion:** Without an auditable, verifiable service history log, owners struggle to prove diligent maintenance when selling their vehicles.

**VMS Solution:** A centralized, accessible web portal delivering automated calculations, multi-channel reminders (in-app notifications and real-time alerts), cost reports, and auditable service logging.

---

## 3. Target Users & Two-Role RBAC Model

The application architecture enforces **exactly two official application roles**:

```text
┌─────────────────────────────────────────────────────────────┐
│                 VMS ACCESS CONTROL BOUNDARY                 │
├──────────────────────────────┬──────────────────────────────┤
│       SYSTEM_ADMIN           │       VEHICLE_OWNER          │
├──────────────────────────────┼──────────────────────────────┤
│ • Platform security & health │ • Account registration       │
│ • Owner account management   │ • Vehicle garage & records   │
│ • Account activate/deactivate│ • Maintenance logs & costs   │
│ • Global service type catalog│ • Next service calculations  │
│ • System audit logs review   │ • Personal reminders         │
│ • System configuration       │ • Maintenance & cost reports │
└──────────────────────────────┴──────────────────────────────┘
```

### 3.1 Role 1: SYSTEM_ADMIN (Administrator)
Responsible for overseeing system availability, account management, and security governance.
- **Responsibilities:**
  - Access administrative dashboard with platform-level health indicators.
  - Manage vehicle-owner accounts (search, view metadata, activate, deactivate).
  - Manage administrator accounts with safeguards against self-demotion and removal of the last administrator.
  - Manage the global maintenance service catalog (create, edit, and toggle global service types like "Oil Change", "Brake Pad Replacement").
  - Review administrative, security, and authentication audit logs.
  - Oversee platform-level configuration settings.
- **Security & Privacy Boundary:**
  - Administrator access **does not** grant permission to browse, alter, or inspect private vehicle service details or personal repair histories.
  - All administrative actions are permanently recorded in the `audit_logs` table.
  - Administrative accounts cannot impersonate vehicle owners.

### 3.2 Role 2: VEHICLE_OWNER (Primary Operational User)
The primary end-user who registers personal vehicles and tracks maintenance.
- **Responsibilities:**
  - Register, authenticate, manage personal profile, and reset passwords securely.
  - Add, edit, view, and archive personal vehicles (plate number, make, model, year, VIN, odometer).
  - Record completed maintenance services (service type, date, odometer reading, cost, parts, mechanic name, invoice remarks).
  - Monitor upcoming, due, and overdue maintenance schedules evaluated by date and mileage thresholds.
  - Receive automated in-app notifications and real-time WebSocket alerts.
  - Track official vehicle registration expiration dates.
  - Generate and export maintenance summaries, cost breakdowns, and CSV reports.
  - Customize personal notification preferences and vehicle servicing intervals.
- **Security Boundary:**
  - Vehicle owners can **only** query and modify records where `user_id` matches their authenticated session (`$_SESSION['user_id']`). Cross-user record access (IDOR) is strictly forbidden.

### 3.3 Explicit Boundary Regarding Mechanics and Service Providers
> [!IMPORTANT]
> **Mechanics, auto-repair shops, and service centers are NOT application roles.**  
> They are recorded strictly as descriptive text fields (`service_provider`, `parts_replaced`) within individual service records. The system does not support mechanic logins, mechanic dashboards, or third-party shop access.

---

## 4. Project Goals

1. **Establish a Secure Two-Role Foundation:** Implement strict role-based access control (RBAC), password hashing (bcrypt), session fixation protection, CSRF mitigation, and IDOR prevention.
2. **Reliable Vehicle & Service Tracking:** Provide intuitive CRUD operations allowing vehicle owners to catalog vehicles and record comprehensive service histories.
3. **Accurate Status Calculations:** Implement dual-criteria ("whichever comes first" date and mileage) logic to dynamically identify `OVERDUE`, `DUE`, `UPCOMING`, and `NOT_DUE` statuses.
4. **Automated Reminders & Real-Time Alerts:** Deliver timely notifications for servicing and registration renewals via in-app feeds and native WebSockets.
5. **Administrative Governance:** Equip system administrators with account controls, global catalog management, and audit log inspection.
6. **Insightful Reporting:** Offer visual dashboards, monthly/yearly expenditure breakdowns, service history logs, and CSV data export capabilities.
7. **Clean Native PHP Architecture:** Maintain a zero-dependency, maintainable, modular native PHP 8+ codebase with 100% automated test coverage for critical business logic.

---

## 5. Success Measures & Acceptance Targets

| Measure / Metric | Proposed Target | Formally Approved Target | Verification Method |
|---|---|---|---|
| **Authentication & RBAC** | 100% enforcement; zero unauthorized role crossover | Pending Academic Gate | Automated Unit Tests & Role Guard Route Testing |
| **Data Isolation (IDOR)** | 0 cross-user record leaks | Pending Academic Gate | Route inspection with mismatched session IDs |
| **Calculation Accuracy** | 100% compliance with "whichever comes first" maintenance algorithm | Approved | `MaintenanceCalculatorTest` (8/8 test vectors pass) |
| **Duplicate Prevention** | 0 duplicate notifications generated within 7 days | Approved | Deduplication key & SQL constraint tests |
| **Database Integrity** | All schema tables enforce foreign keys and index coverage | Approved | MySQL schema audit and migration scripts |
| **Automated Testing** | >= 40 automated unit tests passing in CI | Approved | `composer test` (44 tests, 156 assertions passing) |
| **Environment Startup** | Documented setup reproduces functional instance in <= 15 minutes | Pending User Gate | Fresh deployment rehearsal using `SETUP_NOTES.txt` |
| **Disaster Recovery** | Database backup and restore verified without data corruption | Pending Phase 8 | SQL dump export and restoration drill |

---

## 6. Project Constraints

- **Architecture:** Must adhere to Native PHP 8+, MySQL/MariaDB PDO, Bootstrap 5, and vanilla JavaScript without external full-stack frameworks (e.g., Laravel, Symfony).
- **Zero Heavy Dependencies:** Core application runtime must function without mandatory third-party Composer packages; Composer is reserved for testing and CI tools.
- **Operating Environment:** Primary local development executes on Windows under XAMPP (`C:\xampp\htdocs\...`).
- **Academic Context:** Developed within academic software engineering course milestones; dates and sprint schedules are relative to milestone deliverables.
  - *Project Sponsor / Instructor:* `[To be provided / Course Faculty]`
  - *Academic Term / Milestone:* `[To be provided]`
  - *Approved Project Budget:* N/A (Academic educational context)

---

## 7. Demonstration Outcomes

### 7.1 Administrator Demonstration Flow
1. **Login:** Administrator navigates to `/auth/login.php` and signs in with administrative credentials.
2. **Dashboard:** Redirects to `/admin/dashboard.php`, displaying platform totals (total users, active vehicles, registered services, system events).
3. **User Search & Management:** Administrator accesses `/admin/users.php`, searches for an owner account by name/email, and reviews account details at `/admin/user_view.php`.
4. **Authorized Account Action:** Administrator safely updates a user role or toggles account status while self-demotion and last-admin demotion are blocked.
5. **Global Maintenance Catalog:** Administrator accesses the global maintenance type catalog to define or edit standard service types.
6. **Audit Trail Review:** Administrator navigates to `/admin/audit_logs.php` to verify that administrative actions and logins are logged with timestamps.
7. **Secure Logout:** Administrator clicks logout, destroying session tokens and clearing cookies.

### 7.2 Vehicle Owner Demonstration Flow
1. **Registration & Login:** Vehicle owner signs up at `/auth/register.php` and logs in at `/auth/login.php`.
2. **Owner Dashboard:** Redirects to `/dashboard/index.php`, displaying personal vehicle counts, upcoming maintenance badges, and unread notifications.
3. **Vehicle Registration:** Owner accesses `/vehicles/create.php` and adds a vehicle (e.g., Toyota Vios, plate `ABC-1234`, odometer `45,000 km`).
4. **Service Recording:** Owner navigates to `/services/create.php` and records a completed service (e.g., Oil Change at 45,000 km, next service scheduled at 50,000 km or 6 months).
5. **Odometer & History Update:** The system updates vehicle mileage and appends the entry to the vehicle's permanent service history.
6. **Status Calculation:** Maintenance calculator evaluates current date and mileage, classifying next service as `UPCOMING`.
7. **Notification Delivery:** Triggering the reminder processor generates an in-app notification and real-time WebSocket alert.
8. **Reporting & Export:** Owner navigates to `/reports/costs.php` to view expenditure graphs and exports a service history CSV from `/reports/export_csv.php`.
9. **Cross-User Protection:** Attempting to directly request another user's vehicle ID (`/vehicles/view.php?id=999`) results in immediate `HTTP 403 / Redirect: Permission Denied`.
10. **Secure Logout:** Owner logs out, ending the session cleanly.

---

## 8. Charter Approval Status

| Role | Name | Status | Date |
|---|---|---|---|
| Project Lead / Architect | Technical Lead | Approved (Phase 0 Baseline) | 2026-11-05 |
| Project Sponsor / Instructor | `[Course Faculty / Sponsor]` | Pending Formal Review | Pending |
| Quality Assurance Reviewer | `[Peer / Reviewer]` | Pending Formal Review | Pending |

