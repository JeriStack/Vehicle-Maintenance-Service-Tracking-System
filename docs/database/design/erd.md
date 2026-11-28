# Entity-Relationship Diagram (ERD) Specification
## Vehicle Maintenance Service Tracking System (VMS)

**Document ID:** VMS-DOC-DES-ERD-001  
**Phase:** 2 — Process Analysis and Technical Design  
**Authority:** VMS System Plan, Database Schema, Phase 1 Requirements Baseline  
**Status:** Approved Technical Design  
**Last Updated:** 2026-11-05  

---

## 1. Relational Architecture Overview

The VMS relational database model is designed around **ten core entities** optimized for high referential integrity, auditable data retention, owner-scoped isolation, and fast query performance under MySQL 8.0 and MariaDB 10.4 (InnoDB engine).

```mermaid
erDiagram
    users ||--o{ vehicles : "owns"
    users ||--|| notification_settings : "configures"
    users ||--|| vehicle_preferences : "specifies"
    users ||--o{ notifications : "receives"
    users ||--o{ audit_logs : "records"

    vehicles ||--o{ service_records : "has history"
    vehicles ||--o{ maintenance_schedules : "has schedules"
    vehicles ||--o{ vehicle_mileage_logs : "tracks odometer"
    vehicles ||--o{ notifications : "context for"

    maintenance_types ||--o{ service_records : "categorizes"
    maintenance_types ||--o{ maintenance_schedules : "templates"

    service_records ||--o| maintenance_schedules : "triggers next"

    users {
        int id PK
        string full_name
        string email UK
        string password
        string phone
        enum role
        enum status
        timestamp created_at
    }

    vehicles {
        int id PK
        int user_id FK
        string plate_number
        string normalized_plate
        string make
        string model
        int year
        int current_mileage
        date registration_expiry
        string status
    }

    maintenance_types {
        int id PK
        string name
        string slug UK
        int default_days_interval
        int default_km_interval
        bool is_active
    }

    service_records {
        int id PK
        int vehicle_id FK
        int maintenance_type_id FK
        string service_type
        date service_date
        int mileage
        decimal cost
        string service_provider
        string status
        string void_reason
    }

    maintenance_schedules {
        int id PK
        int vehicle_id FK
        int maintenance_type_id FK
        int source_service_id FK
        date next_service_date
        int next_service_mileage
        string status
    }

    vehicle_mileage_logs {
        int id PK
        int vehicle_id FK
        int recorded_mileage
        string source
        timestamp created_at
    }

    notifications {
        int id PK
        int user_id FK
        int vehicle_id FK
        string type
        string title
        string dedupe_key
        bool is_read
        timestamp created_at
    }

    notification_settings {
        int id PK
        int user_id FK,UK
        bool maintenance_reminder
        bool registration_reminder
        int reminder_days
    }

    vehicle_preferences {
        int id PK
        int user_id FK,UK
        int default_service_interval
        int default_mileage_interval
    }

    audit_logs {
        int id PK
        int user_id FK
        string action
        string table_name
        int record_id
        timestamp created_at
    }
```

---

## 2. Ownership & Data Isolation Hierarchy

```text
USER (Parent Root Identity)
 │
 ├── notification_settings (1:1 Child)
 ├── vehicle_preferences   (1:1 Child)
 ├── notifications         (1:N Child)
 ├── audit_logs            (1:N Child)
 │
 └── VEHICLES (1:N Primary Domain Asset)
      │
      ├── vehicle_mileage_logs    (1:N Audit History)
      ├── service_records         (1:N Completed Invoices & Records)
      └── maintenance_schedules   (1:N Future Target Projections)
```

### Invariant:
Every query accessing vehicle, service, schedule, or mileage data must traverse the ownership path:
$$\text{Data Resource} \longrightarrow \text{Vehicle} \longrightarrow \text{user\_id} = \$_SESSION['user\_id']$$
Direct access to any child record whose parent vehicle does not belong to the current authenticated user results in immediate authorization rejection (`HTTP 403 Forbidden`).

