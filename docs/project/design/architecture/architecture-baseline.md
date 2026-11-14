# Technical Architecture Baseline & Quality Attributes
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-ARC-BSL-001  
**Phase:** 3 — UI/UX Planning and Architecture Baseline  
**Authority:** VMS Complete Plan, ADR-001 through ADR-008  
**Status:** Approved Architecture Baseline  
**Last Updated:** 2026-11-05  

---

## 1. Architectural Quality Attributes & Non-Functional Requirements

This baseline establishes measurable technical performance, reliability, and security quality attributes governing the Vehicle Maintenance Service Tracking System.

---

## 2. Measurable Performance Service Level Agreements (SLAs)

| Quality Metric | Performance SLA Target | Measurement Context | Enforcement Mechanism |
|---|---|---|---|
| **Server Response Time (TTFB)** | `< 150 ms` (95th percentile) | Dynamic HTML page generation under standard load | Fast cold-start native PHP; minimal autoloading overhead |
| **Database Query Latency** | `< 25 ms` per indexed query | Single-record lookups, vehicle garage index queries | B-Tree composite indexes on `(user_id, status)`, `(vehicle_id, service_date)` |
| **Complex Report Aggregation** | `< 250 ms` | Year-to-date maintenance spend and mileage calculations | Aggregate SQL queries with `SUM()`, `COUNT()` indexed by vehicle |
| **Real-Time WebSocket Push** | `< 80 ms` latency | Time from service record creation to client toast render | Event queue buffer (`realtime_events`) with socket frame push |
| **Memory Consumption** | `< 16 MB` per HTTP request | Standard PHP execution lifecycle | Lean memory footprint; avoiding heavyweight framework engines |
| **Background Scheduler Sweep**| `< 3.0 s` per 1,000 schedules | Daily CLI maintenance status evaluation | Bulk batching; indexed status queries; non-blocking file lock |

---

## 3. Scalability & Operational Capacity Limits

1. **Target Operational Scale (Phase 1–8 Scope):**
   - **Active Registered Users:** Designed to support up to **5,000** concurrent registered user accounts.
   - **Garage Fleet Assets:** Up to **20,000** active vehicle records.
   - **Historical Service Ledger:** Up to **250,000** itemized service transactions.
   - **Concurrent WebSocket Clients:** Up to **500** simultaneous active WebSocket socket connections on port 8080.
2. **Horizontal & Vertical Scaling Strategy:**
   - **Database Tier:** MariaDB connection pooling, table partitioning on `audit_logs` by calendar year when table exceeds 500,000 rows.
   - **Real-Time Tier:** Dedicated standalone Ratchet process running as a Windows Service or systemd daemon.

---

## 4. Fundamental Security & Integrity Invariants

1. **Zero-Trust Server-Side Enforcement:** Every HTTP endpoint verifies authorization, ownership, and input validity server-side before executing queries or rendering views. Client-side hiding of buttons is never considered security.
2. **IDOR Immunity via Owner Scoping:** All SQL queries accessing vehicles, service records, schedules, or reports must bind `$_SESSION['user_id']` directly.
3. **Monotonic Odometer Advancement:** Service records and mileage logs must satisfy `service_mileage >= vehicles.current_mileage`. Odometer rollback attempts trigger a validation rejection.
4. **Non-Destructive Archival:** Primary entities (`vehicles`, `service_records`, `audit_logs`) are never physically deleted via `DELETE` in normal business flows; status is updated to `archived` or `Voided`.
5. **Admin Privacy Barrier:** System Administrators are barred from inspecting private vehicle repair records, personal notes, or receipts.

