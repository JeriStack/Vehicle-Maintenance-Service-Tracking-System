# Wireframe: Vehicle Owner Portal Screens
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-WF-OWN-001  
**Target Screens:** `SCR-OWN-01` through `SCR-OWN-09`  
**Target Role:** `VEHICLE_OWNER` (`role = 'user'`)  
**Layout Shell:** Authenticated Application Shell (Sidebar + 12-Column Responsive Canvas)  

---

## 1. Owner Dashboard Wireframe (`SCR-OWN-01`)

### Desktop Layout Variant (Viewport: >= 992px)
```text
+-----------------------------------------------------------------------------------------------------------------------------------------+
| [VMS Logo] VMS Tracker      | [ 🔍 Quick search vehicles, services... (Ctrl+K) ] | [● WS Live] | [🔔 3] | [ John Doe (Owner) ▼ ]        |
+-----------------------------------------------------------------------------------------------------------------------------------------+
| [SIDEBAR (260px)]          | BREADCRUMBS: Home > Dashboard                                                         [ + Log Service ]    |
| • Dashboard (Active)       |                                                                                                            |
| • My Garage                | +-------------------+  +-------------------+  +-------------------+  +-------------------+                 |
|   - All Vehicles           | | ACTIVE VEHICLES   |  | DUE SERVICES      |  | OVERDUE SERVICES  |  | YTD SPEND (2026)  |                 |
|   - Add Vehicle            | |        3          |  |        2          |  |        1          |  |    $1,420.50       |                 |
| • Services                 | | Garage inventory  |  | Due in <= 7 days  |  | Immediate action  |  | 6 total records   |                 |
|   - Records                | +-------------------+  +-------------------+  +-------------------+  +-------------------+                 |
|   - Log Service            |                                                                                                            |
| • Maintenance              | +-------------------------------------------------------+  +---------------------------------------------+ |
|   - Status Board           | | ⚠️ ACTION REQUIRED: MAINTENANCE ALERTS                |  | 🚗 MY GARAGE SUMMARY                        | |
| • Notifications [3]        | |-------------------------------------------------------|  |---------------------------------------------| |
| • Reports & Spend          | | [!] 2021 Toyota Corolla (ABC-1234)                    |  | • 2021 Toyota Corolla (ABC-1234)            | |
| • Settings                 | |     Brake Pad Inspection — OVERDUE by 140 km          |  |   Odometer: 45,210 km | Status: [ OVERDUE ] | |
|                            | |     [ Log Service ] [ Snooze ]                        |  |                                             | |
|                            | |                                                       |  | • 2019 Honda Civic (XYZ-9876)               | |
|                            | | [!] 2019 Honda Civic (XYZ-9876)                       |  |   Odometer: 62,100 km | Status: [ DUE SOON] | |
|                            | |     Engine Oil & Filter Change — DUE in 4 days        |  |                                             | |
|                            | |     [ Log Service ]                                   |  | • 2023 Ford F-150 (TRK-5544)                | |
|                            | +-------------------------------------------------------+  |   Odometer: 14,800 km | Status: [ GOOD ]    | |
|                            |                                                            +---------------------------------------------+ |
+-----------------------------------------------------------------------------------------------------------------------------------------+
```

### Mobile Layout Variant (Viewport: < 576px)
```text
+------------------------------------------+
| [☰] VMS Tracker          [●] [🔔 3] [👤]  |
+------------------------------------------+
| Dashboard Overview                       |
|                                          |
| +--------------------------------------+ |
| | ACTIVE VEHICLES: 3                   | |
| +--------------------------------------+ |
| | OVERDUE MAINTENANCE: 1  [!]          | |
| +--------------------------------------+ |
| | DUE THIS WEEK: 2                     | |
| +--------------------------------------+ |
| | YTD SPEND: $1,420.50                 | |
| +--------------------------------------+ |
|                                          |
| [ + Log Service Record ]                 |
|                                          |
| URGENT ALERTS (1)                        |
| +--------------------------------------+ |
| | 2021 Toyota Corolla (ABC-1234)       | |
| | Brake Pad Inspection                 | |
| | Status: [ OVERDUE by 140 km ]        | |
| | [ Log Service Now ]                  | |
| +--------------------------------------+ |
|                                          |
+------------------------------------------+
| [🏠 Home]  [🚗 Garage]  [🔧 Log]  [🔔 (3)]|  <-- Mobile Bottom Nav Bar
+------------------------------------------+
```

---

## 2. Vehicle Garage Screens (`SCR-OWN-02`, `SCR-OWN-03`)

### Garage Index Screen (`SCR-OWN-02`)
```text
+----------------------------------------------------------------------------------------------------+
| MY GARAGE                                                                       [ + Add Vehicle ]  |
| Tabs: [ Active Vehicles (3) ]  [ Archived Vehicles (1) ]                                           |
| Filter: [ Search by make, model, plate...            ]  Sort: [ Current Mileage ▼ ]                |
|----------------------------------------------------------------------------------------------------|
| +----------------------------+  +----------------------------+  +----------------------------+     |
| | [Photo: Toyota Corolla]    |  | [Photo: Honda Civic]       |  | [Photo: Ford F-150]        |     |
| | 2021 Toyota Corolla        |  | 2019 Honda Civic           |  | 2023 Ford F-150            |     |
| | Nickname: "Daily Commuter" |  | Nickname: "City Hatch"     |  | Nickname: "Work Truck"     |     |
| | Plate: [ ABC-1234 ]        |  | Plate: [ XYZ-9876 ]        |  | Plate: [ TRK-5544 ]        |     |
| | Odometer: 45,210 km        |  | Odometer: 62,100 km        |  | Odometer: 14,800 km        |     |
| | Status: [ OVERDUE ]        |  | Status: [ DUE SOON ]       |  | Status: [ GOOD ]           |     |
| |----------------------------|  |----------------------------|  |----------------------------|     |
| | [View Details] [Log Serv]  |  | [View Details] [Log Serv]  |  | [View Details] [Log Serv]  |     |
| +----------------------------+  +----------------------------+  +----------------------------+     |
+----------------------------------------------------------------------------------------------------+
```

### Add Vehicle Modal Dialog
```text
+------------------------------------------------------------------------+
| Add New Vehicle to Garage                                          [X] |
|------------------------------------------------------------------------|
| Make: [ Toyota                     ▼ ]  Model: [ Corolla             ] |
| Year: [ 2021                       ▼ ]  Color: [ Silver Metallic     ] |
| License Plate: [ ABC-1234            ]  VIN:   [ 1HGCR2F83HA000000   ] |
| Initial Odometer (km): [ 45000       ]  Fuel:  [ Gasoline          ▼ ] |
| Registration Expiry:   [ 2026-12-31  ]                                 |
| Vehicle Nickname (Opt):[ Daily Commuter                              ] |
|------------------------------------------------------------------------|
|                                            [ Cancel ]  [ Save Vehicle ]|
+------------------------------------------------------------------------+
```

---

## 3. Service Record Logging & Invoicing (`SCR-OWN-04`, `SCR-OWN-05`)

### Service History View (`SCR-OWN-04`)
```text
+----------------------------------------------------------------------------------------------------+
| SERVICE RECORDS HISTORY                                                      [ + Record Service ]  |
| Vehicle: [ All Vehicles ▼ ]  Category: [ All Types ▼ ]  Date: [ Last 12 Months ▼ ]                 |
|----------------------------------------------------------------------------------------------------|
| Date       | Vehicle          | Maintenance Type   | Odometer  | Cost     | Provider  | Status     |
|------------|------------------|--------------------|-----------|----------|-----------|------------|
| 2026-10-15 | Toyota Corolla   | Engine Oil Change  | 45,000 km | $65.00   | Valvoline | Completed  |
| 2026-08-20 | Honda Civic      | Tire Rotation      | 60,000 km | $45.00   | DiscountT | Completed  |
| 2026-05-12 | Toyota Corolla   | Transmission Flush | 40,000 km | $210.00  | Toyota Dlr| Completed  |
| 2026-02-10 | Honda Civic      | Spark Plug Replace | 55,000 km | $120.00  | DIY Garage| Voided [X] |
+----------------------------------------------------------------------------------------------------+
```

### Void Service Record Confirmation Modal (Audit Requirement)
```text
+------------------------------------------------------------------------+
| Void Service Record #1042                                          [X] |
|------------------------------------------------------------------------|
| ⚠️ Are you sure you want to void this record?                          |
| This transaction will be marked VOIDED and excluded from cost reports. |
| The record is permanently retained for accounting audit compliance.    |
|                                                                        |
| Reason for Voiding (Required):                                         |
| [ Duplicate entry created by mistake. Service logged on wrong vehicle] |
|                                                                        |
| [ ] I understand this action cannot be undone.                         |
|------------------------------------------------------------------------|
|                                             [ Cancel ]  [ Void Record ]|
+------------------------------------------------------------------------+
```

---

## 4. Maintenance Schedule Board (`SCR-OWN-06`)

### Dual-Criteria Schedule Matrix
```text
+----------------------------------------------------------------------------------------------------+
| MAINTENANCE SCHEDULE BOARD (Dual-Criteria Engine)                       [ + Add Custom Schedule ]  |
| Vehicle: [ 2021 Toyota Corolla (ABC-1234) | Current Odometer: 45,210 km ]                          |
|----------------------------------------------------------------------------------------------------|
| Service Item            | Interval Rules       | Due Criteria       | Progress Bar     | Status    |
|-------------------------|----------------------|--------------------|------------------|-----------|
| Engine Oil & Filter     | 6 Mos / 8,000 km     | In 14 days / 790 km| [========--] 85% | DUE SOON  |
| Brake Pad Inspection    | 12 Mos / 15,000 km   | -140 km (Exceeded) | [==========]101% | OVERDUE ! |
| Tire Rotation           | 6 Mos / 10,000 km    | In 68 days / 3,200 | [====------] 45% | UPCOMING  |
| Cabin Air Filter        | 12 Mos / 20,000 km   | In 180 days / 12k  | [==--------] 20% | GOOD (OK) |
+----------------------------------------------------------------------------------------------------+
```

