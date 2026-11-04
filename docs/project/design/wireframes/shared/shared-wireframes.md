# Wireframe: Shared Layouts, Modals & Global Components
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-WF-SHR-001  
**Target Screens:** `SCR-SHR-01` (Header & Topbar), `SCR-SHR-02` (Sidebar & Shell)  
**Target Roles:** Shared across `VEHICLE_OWNER` and `SYSTEM_ADMIN`  
**Layout Shell:** Global Responsive Wrapper (`#wrapper`, `#sidebar-wrapper`, `#page-content-wrapper`)  

---

## 1. Global Header & Topbar Wireframe (`SCR-SHR-01`)

### Desktop Layout (>= 992px)
```text
+-------------------------------------------------------------------------------------------------------------------+
| [ VMS Tracker ] | [ 🔍 Search vehicles, records... (Ctrl+K) ] | [● WS Online] | [🔔 3] | [ John Doe (Owner) ▼ ]   |
+-------------------------------------------------------------------------------------------------------------------+
                                                                                     |
                                                                                     +--> [ User Profile Dropdown ]
                                                                                          | • View Profile (/settings)
                                                                                          | • Change Password
                                                                                          | • Notification Preferences
                                                                                          |---------------------------
                                                                                          | • 🚪 Log Out
```

### Notification Flyout Drawer (Off-Canvas Right)
```text
+-------------------------------------------------------------+
| Real-Time Notification Center                          [X]  |
| Tabs: [ Unread (3) ]  [ All Notifications ]  [Mark All Read]|
|-------------------------------------------------------------|
| ⚠️ OVERDUE MAINTENANCE                                      |
| 2021 Toyota Corolla - Brake Pad Inspection is overdue.      |
| 10 minutes ago | [ View Vehicle ]                           |
|-------------------------------------------------------------|
| ⏱️ UPCOMING SERVICE                                         |
| 2019 Honda Civic - Oil Change due in 4 days.                |
| 2 hours ago | [ Log Service Record ]                        |
|-------------------------------------------------------------|
| 📄 REGISTRATION RENEWAL REMINDER                            |
| 2023 Ford F-150 registration expires in 28 days.            |
| Yesterday | [ View Details ]                                |
+-------------------------------------------------------------+
```

---

## 2. Shared Feedback & Modal Wireframes

### Standard Confirmation Dialogue (`CMP-FDB-02`)
```text
+------------------------------------------------------------------------+
| {Dialog Title: e.g., Archive Vehicle "2021 Toyota Corolla"}        [X] |
|------------------------------------------------------------------------|
| {Icon: ⚠️} Are you sure you want to proceed with this action?          |
|                                                                        |
| {Detailed explanatory text explaining non-destructive impact:          |
|  "Archiving this vehicle will remove it from your active garage.       |
|   All historical service records, mileage logs, and receipts will be    |
|   permanently retained and remain accessible in reports."}             |
|                                                                        |
| [ ] I understand this action and confirm my request.                   |
|------------------------------------------------------------------------|
|                                            [ Cancel ]  [ Confirm Action]
+------------------------------------------------------------------------+
```

### Real-Time Floating Toasts (`CMP-FDB-01`)
```text
Desktop (Top-Right Fixed Anchor):
+----------------------------------------------------+
| [✓] Service Record Created Successfully       [X]  |
| 2021 Toyota Corolla odometer advanced to 45,210 km |
| Just now                                           |
+----------------------------------------------------+

+----------------------------------------------------+
| [!] Urgent Reminder: Overdue Maintenance      [X]  |
| Brake Pad Inspection exceeds target threshold.     |
| 1 minute ago                                       |
+----------------------------------------------------+
```

### Illustrated Empty State (`CMP-FDB-03`)
```text
+--------------------------------------------------------------------+
|                                                                    |
|                     [ 🚗 Automotive Graphic ]                      |
|                                                                    |
|                     No Vehicles in Your Garage                     |
|     You haven't added any vehicles yet. Begin tracking your        |
|     maintenance, odometer progression, and service records now.   |
|                                                                    |
|                      [ + Add Your First Vehicle ]                  |
|                                                                    |
+--------------------------------------------------------------------+
```

### Loading Skeleton Layout (`CMP-FDB-04`)
```text
+--------------------------------------------------------------------+
| [████████████████████████████]       [████████████]                |
|                                                                    |
| +-------------------+  +-------------------+  +-------------------+|
| | [██████████]      |  | [██████████]      |  | [██████████]      ||
| | [███████]         |  | [███████]         |  | [███████]         ||
| +-------------------+  +-------------------+  +-------------------+|
|                                                                    |
| [█████████████████████████████████████████████████████████████████]|
| [█████████████████████████████████████████████████████████████████]|
| [█████████████████████████████████████████████████████████████████]|
+--------------------------------------------------------------------+
```

