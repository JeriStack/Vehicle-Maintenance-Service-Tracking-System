# Release Scope & Scope Reconciliation Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-RS-001  
**Phase:** 1 — Requirements Confirmation and Backlog Baseline  
**Status:** Complete / Pending Gate Approval  
**Last Updated:** 2026-11-05  

---

## 1. Executive Summary

This document establishes the official release scope boundary separating the **Minimum Viable Product (P0 / MVP)**, the **First Maintenance Update (P1 / Release 1.1)**, and **Future Enhancements (P2 / Optional)**. 

Furthermore, it formally records the **Scope Reconciliation** addressing variances between the original project roadmap, the 32-page *VMS Complete Plan — Administrator Edition*, and the existing codebase.

---

## 2. Release Classifications

* **P0 — Minimum Viable Product (MVP):**
  * *Definition:* Mandatory core features required for the first demonstrable, secure, and functional deployment of VMS. All essential business workflows, security controls, and primary acceptance criteria must pass.
* **P1 — Release 1.1 (Enhancement Release):**
  * *Definition:* Important functional enhancements and administrative tooling that enrich the user experience but whose absence does not prevent the core application from operating securely.
* **P2 — Optional / Future Enhancements:**
  * *Definition:* Non-essential advanced capabilities that require external infrastructure, third-party carrier subscriptions, or formal change-request approvals.

---

## 3. Scope Reconciliation Analysis

### 3.1 Module: Notifications & Reminders (`NTF`)

#### A. Documented Discrepancy
- The original system plan suggested granular email notifications for all reminder types, customizable multi-channel routing, and SMS integration.
- The development roadmap focused primarily on in-app notifications and scheduled background reminders.
- The existing codebase already features a fully functional native RFC 6455 WebSocket alerting daemon and an RFC 5321 native SMTP email client.

#### B. Scope Reconciliation Decision

| Notification Feature | Reconciled Release | Business Justification | Configuration Scope |
|---|---|---|---|
| **In-App Notification Feed** | **P0 (MVP)** | Fundamental mechanism for alerting owners of due maintenance and expiring registrations. | Owner-controlled |
| **Due & Overdue Service Reminders** | **P0 (MVP)** | Core value proposition preventing mechanical failures. | Owner-controlled |
| **Registration Expiration Reminders** | **P0 (MVP)** | Core value proposition preventing statutory penalties. | Owner-controlled |
| **Idempotent Deduplication** | **P0 (MVP)** | Essential to prevent alert fatigue and notification spam. | System-enforced |
| **Real-Time WebSocket Push** | **P0 (MVP)** | Already implemented and verified; delivers instant UX value without external cost. | System-enforced |
| **Notification Read/Unread State** | **P0 (MVP)** | Basic usability for notification inbox management. | Owner-controlled |
| **Basic Preference Toggles** | **P0 (MVP)** | Allows users to disable reminders or set global lead days (1–30 days). | Owner-controlled |
| **Automated SMTP Email Dispatch** | **P1 (Release 1.1)** | Mailer client is built, but cron daemon hook should be refined to prevent accidental rate-limiting during local development. | Global & Owner |
| **Per-Vehicle Reminder Intervals** | **P1 (Release 1.1)** | Overrides global lead days on a per-vehicle basis. | Owner-controlled |
| **SMS Gateway Integration** | **P2 (Excluded)** | Requires paid third-party telecommunications carrier (Twilio/Semaphore); out of scope. | Excluded |

---

### 3.2 Module: Reporting & Analytics (`RPT`)

#### A. Documented Discrepancy
- Early project outlines proposed basic table views for service history.
- The *VMS Complete Plan* specified monthly and yearly spending trends, maintenance breakdown summaries, and multi-format exports.
- The existing codebase already contains Chart.js visual charts, monthly/yearly breakdowns, and an RFC 4180 CSV export script.

#### B. Scope Reconciliation Decision

| Reporting Feature | Reconciled Release | Business Justification | Status |
|---|---|---|---|
| **Chronological Service History Report** | **P0 (MVP)** | Essential for owners to track repairs and verify historical maintenance. | **IMPLEMENTED** |
| **Maintenance Status Summary** | **P0 (MVP)** | High-level overview of completed, upcoming, due, and overdue counts. | **IMPLEMENTED** |
| **Cost Totals & Aggregations** | **P0 (MVP)** | Displays total expenditure per vehicle and across the garage. | **IMPLEMENTED** |
| **Vehicle-Specific Filtering** | **P0 (MVP)** | Necessary to isolate records for a single car. | **IMPLEMENTED** |
| **Monthly & Yearly Spending Trends** | **P0 (MVP)** | Visual Chart.js charts are already implemented and working reliably. | **IMPLEMENTED** |
| **RFC 4180 CSV Export Engine** | **P1 (Release 1.1)** | Export engine is built (`reports/export_csv.php`); assigned to P1 for formal QA edge-case testing. | **IMPLEMENTED (Assigned P1)** |
| **Print-Optimized Stylesheet (`@media print`)** | **P1 (Release 1.1)** | Enables owners to print clean paper records for vehicle sale inspections. | **P1 Backlog** |
| **Server-Side PDF Generation** | **P2 (Optional)** | Requires external PDF rendering libraries (e.g. FPDF/Dompdf); reserved for future expansion. | **P2 Backlog** |

---

### 3.3 Module: Administration & Privacy Boundary (`ADM`)

#### A. Documented Discrepancy
- The existing codebase provides an administrative dashboard, user management, and audit logs. However, the current admin views (`admin/vehicles.php`, `admin/services.php`) allow administrators to view all private owner records.
- Section 3.3 and Section 4.2 of the *VMS Complete Plan* explicitly mandate an **Owner Privacy Boundary**: *Administrators manage platform security, user accounts, and the global catalog, but do NOT browse private vehicle/service repair records without explicit owner consent.*

#### B. Scope Reconciliation Decision

| Administrative Feature | Reconciled Release | Scope Resolution |
|---|---|---|
| **Administrator Dashboard** | **P0 (MVP)** | Displays platform aggregate counts (total users, active vehicles, services). |
| **User Directory & Role Management** | **P0 (MVP)** | User search, role promotion, role demotion with last-admin protection. |
| **Account Activation / Deactivation** | **P0 (MVP)** | Administrative toggle to deactivate malicious or inactive accounts. |
| **Security Audit Log Review** | **P0 (MVP)** | Auditable tracking of logins, role switches, and administrative actions. |
| **Owner Privacy Enforcement** | **P0 (MVP)** | Administrative views refactored to show platform statistics; direct browsing of private owner service notes restricted. |
| **Global Maintenance Catalog (`maintenance_types`)** | **P1 (Release 1.1)** | Curate global standard service types (schema migration created; UI placed in P1). |
| **System-Wide Configuration Management** | **P1 (Release 1.1)** | Platform parameters (app name, maintenance mode toggle). |

---

## 4. Formal Release Boundaries Summary

```text
┌────────────────────────────────────────────────────────────────────────┐
│                        VMS RELEASE BOUNDARIES                          │
├───────────────────────────────────┬────────────────────────────────────┤
│          P0 — MVP SCOPE           │      P1 — RELEASE 1.1 SCOPE        │
├───────────────────────────────────┼────────────────────────────────────┤
│ • Complete Authentication & RBAC  │ • Global Maintenance Catalog UI    │
│ • "Remember Me" & "Forgot Pwd"    │ • RFC 4180 CSV Export Verification │
│ • Vehicle Garage CRUD             │ • Dedicated Maintenance Schedules  │
│ • Vehicle Soft-Archival (VEH-02)  │ • Odometer History Log Viewer      │
│ • Service Logging & Monotonic Odo │ • Automated SMTP Reminder Cron     │
│ • Status Calculator (whichever 1st)│ • Service Record Voiding Workflow │
│ • In-App Notification Center      │ • Print-Optimized Stylesheets      │
│ • Real-Time WebSockets Engine     │ • System Configuration Portal      │
│ • Owner Dashboard & Visual Charts │                                    │
│ • Service History & Cost Reports  │                                    │
│ • Admin User Mgmt & Last-Admin    │                                    │
│ • Admin Privacy Boundary          │                                    │
│ • Security Audit Log Viewer       │                                    │
└───────────────────────────────────┴────────────────────────────────────┘
```

---

## 5. Explicitly Excluded Features (Out of Scope)

The following items are officially classified as **OUT OF SCOPE** and must not be implemented:
1. **Mechanic / Service-Provider Logins & Portals** (Mechanics remain descriptive fields).
2. **Payment Gateways (Stripe, PayPal, GCash)** (VMS is an informational tracking platform).
3. **Hardware GPS & OBD-II Telematics**.
4. **Native Mobile Applications (iOS / Android)** (Addressed via responsive Bootstrap 5).
5. **SMS Telecommunications Integration**.
6. **Vehicle Classified Marketplace / Commercial Advertising**.
7. **Insurance Claims Processing & Carrier Portals**.

