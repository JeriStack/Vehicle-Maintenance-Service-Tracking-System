# Wireframe: System Administrator Governance Screens
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-WF-ADM-001  
**Target Screens:** `SCR-ADM-01` through `SCR-ADM-06`  
**Target Role:** `SYSTEM_ADMIN` (`role = 'admin'`)  
**Layout Shell:** Authenticated Application Shell (Administrator Governance Navigation)  

---

## 1. Administrator Dashboard Wireframe (`SCR-ADM-01`)

### Desktop Layout Variant (Viewport: >= 992px)
```text
+-----------------------------------------------------------------------------------------------------------------------------------------+
| [VMS Logo] VMS Platform Governance   | [ 🔍 Search users, audit events... ]         | [● WS Daemon Active] | [ 🛡️ Super Admin ▼ ]       |
+-----------------------------------------------------------------------------------------------------------------------------------------+
| [ADMIN SIDEBAR (260px)]    | BREADCRUMBS: Administration > Overview                                                                     |
| • Dashboard (Active)       |                                                                                                            |
| • User Management          | +-------------------+  +-------------------+  +-------------------+  +-------------------+                 |
|   - All Accounts (142)     | | REGISTERED USERS  |  | ACTIVE GARAGES    |  | LOGGED SERVICES   |  | AUDIT EVENTS (24h)|                 |
|   - Suspended (3)          | |       142         |  |       218         |  |      1,840        |  |        48         |                 |
| • Role Governance          | | +8 this month     |  | 98% active status |  | Total platform ops|  | 0 security flags  |                 |
| • Maintenance Catalog      | +-------------------+  +-------------------+  +-------------------+  +-------------------+                 |
| • Security Audit Logs      |                                                                                                            |
| • System Health            | +-------------------------------------------------------+  +---------------------------------------------+ |
| • Account                  | | 👥 RECENT USER REGISTRATIONS                          |  | 💻 DAEMON & SERVICE TELEMETRY               | |
|   - Settings               | |-------------------------------------------------------|  |---------------------------------------------| |
|   - Logout                 | | User: Sarah Connor (sarah@example.com)                |  | • MariaDB 10.4:  [ OK - 14 ms latency ]     | |
|                            | | Registered: 2026-11-04 18:22 | Role: [ USER ]         |  | • PHP CLI Daemon:[ RUNNING - PID 4092 ]     | |
|                            | | Status: [ ACTIVE ]  [ Manage Account ]                |  | • WebSocket Port:[ LISTENING - Port 8080 ]  | |
|                            | |                                                       |  | • Memory Usage:  [ 18.4 MB / 256 MB ]       | |
|                            | | User: David Miller (david.m@example.com)              |  | • Cron Lockfile: [ UNLOCKED - Ready ]       | |
|                            | | Registered: 2026-11-04 14:05 | Role: [ USER ]         |  +---------------------------------------------+ |
|                            | | Status: [ ACTIVE ]  [ Manage Account ]                |                                                  |
|                            | +-------------------------------------------------------+                                                  |
+-----------------------------------------------------------------------------------------------------------------------------------------+
```

> [!NOTE]
> **Admin Privacy Boundary:**  
> The Administrator Dashboard displays platform aggregations only. It strictly excludes private owner vehicle repair details, mechanic remarks, or invoices.

---

## 2. User Management & Account Governance (`SCR-ADM-02`)

### User Listing Table Wireframe
```text
+----------------------------------------------------------------------------------------------------+
| USER ACCOUNT MANAGEMENT                                                                            |
| Status Filter: [ All Users (142) ▼ ]  Role Filter: [ All Roles ▼ ]  Search: [ Name, Email...      ]|
|----------------------------------------------------------------------------------------------------|
| ID  | Full Name          | Email Address            | Role    | Status    | Created    | Actions   |
|-----|--------------------|--------------------------|---------|-----------|------------|-----------|
| 1   | Super Admin        | admin@vms.local          | Admin   | Active    | 2026-09-01 | [Edit]    |
| 14  | John Doe           | john.doe@example.com     | User    | Active    | 2026-09-15 | [Manage]  |
| 22  | Jane Smith         | jane.smith@example.com   | User    | Suspended | 2026-10-02 | [Manage]  |
| 35  | Michael Brown      | mbrown@example.com       | Admin   | Active    | 2026-10-14 | [Edit]    |
+----------------------------------------------------------------------------------------------------+
```

### User Detail & Action Panel (`SCR-ADM-03`)
```text
+----------------------------------------------------------------------------------------------------+
| USER PROFILE: John Doe (ID: 14)                                             [ Back to All Users ]  |
|----------------------------------------------------------------------------------------------------|
| Account Overview:                                                                                  |
| • Email: john.doe@example.com        • Phone: +1 (555) 019-2834                                    |
| • Role: [ USER ]                     • Current Status: [ ACTIVE ]                                  |
| • Registered: 2026-09-15 10:14:00    • Last Login: 2026-11-04 20:12:15                            |
|                                                                                                    |
| Governance Actions:                                                                                |
| [ Deactivate Account ]  [ Suspend Account ]  [ Promote to System Administrator ]                   |
+----------------------------------------------------------------------------------------------------+
```

---

## 3. Administrator Privilege Management & Last-Admin Safeguard

### Role Promotion / Demotion Confirmation Modal
```text
+------------------------------------------------------------------------+
| Confirm Administrative Role Change                                 [X] |
|------------------------------------------------------------------------|
| Target User: Michael Brown (mbrown@example.com)                        |
| Current Role: [ SYSTEM_ADMIN ]                                         |
| Proposed Role: [ VEHICLE_OWNER ] (Demote)                              |
|                                                                        |
| 🛡️ SYSTEM INTEGRITY VALIDATION:                                       |
| Total Active Administrators: 2                                         |
| Last-Admin Protection Check: PASSED (1 other active admin exists)      |
|                                                                        |
| Mandatory Reason for Privilege Change (Recorded in Audit Logs):        |
| [ Reassigning to standard vehicle owner account upon role rotation. ] |
|                                                                        |
| Enter Your Current Password to Confirm:                                |
| [ ••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••• ] |
|------------------------------------------------------------------------|
|                                            [ Cancel ]  [ Confirm Change]
+------------------------------------------------------------------------+
```

### Last-Active-Admin Locked State (Safeguard Invariant)
```text
+------------------------------------------------------------------------+
| 🚫 ACTION FORBIDDEN: SOLE SYSTEM ADMINISTRATOR                     [X] |
|------------------------------------------------------------------------|
| This user is the LAST REMAINING active System Administrator.           |
| Demoting or deactivating this account is permanently prohibited to     |
| prevent total administrative lockout of the platform.                  |
|                                                                        |
| To demote this account, first promote another active user to the       |
| Administrator role.                                                    |
|------------------------------------------------------------------------|
|                                                          [ Close ]     |
+------------------------------------------------------------------------+
```

---

## 4. Global Service Catalog (`SCR-ADM-04`)

```text
+----------------------------------------------------------------------------------------------------+
| GLOBAL MAINTENANCE CATALOG                                                  [ + Add Service Type ] |
| Search: [ Filter maintenance type name...              ]                                           |
|----------------------------------------------------------------------------------------------------|
| ID  | Service Name            | Default Months | Default Mileage | Active Schedules | Status       |
|-----|-------------------------|----------------|-----------------|------------------|--------------|
| 1   | Engine Oil & Filter     | 6 Months       | 8,000 km        | 184 vehicles     | Active       |
| 2   | Brake Fluid Replacement | 24 Months      | 40,000 km       | 92 vehicles      | Active       |
| 3   | Tire Rotation & Balance | 6 Months       | 10,000 km       | 165 vehicles     | Active       |
| 4   | Transmission Fluid Flush| 36 Months      | 60,000 km       | 45 vehicles      | Active       |
| 5   | Spark Plugs Replacement | 48 Months      | 80,000 km       | 78 vehicles      | Active       |
+----------------------------------------------------------------------------------------------------+
```

---

## 5. Security Audit Log Inspector (`SCR-ADM-05`)

```text
+----------------------------------------------------------------------------------------------------+
| IMMUTABLE SYSTEM AUDIT LOGS (Read-Only)                                     [ Export Audit CSV ]   |
| Date Range: [ Last 7 Days ▼ ]  Action: [ All Security Events ▼ ]  Search: [ Actor, Target, IP... ] |
|----------------------------------------------------------------------------------------------------|
| Timestamp (UTC)     | Actor Email        | Action              | Target Entity   | Client IP       |
|---------------------|--------------------|---------------------|-----------------|-----------------|
| 2026-11-04 22:15:02 | admin@vms.local    | USER_ROLE_DEMOTE    | User #35        | 192.168.1.100   |
| 2026-11-04 21:04:12 | CLI_SCHEDULER      | REMINDER_SWEEP_RUN  | Schedule Batch  | 127.0.0.1       |
| 2026-11-04 19:45:30 | john.doe@vms.local | VEHICLE_ARCHIVE     | Vehicle #12     | 172.56.21.9     |
| 2026-11-04 18:22:10 | SYSTEM_AUTH        | FAILED_LOGIN_ATTEMPT| User #14        | 45.33.12.8      |
+----------------------------------------------------------------------------------------------------+
```

