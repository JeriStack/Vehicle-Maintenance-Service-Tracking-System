<h1 align="center">Vehicle Maintenance Service Tracking System</h1>

<p align="justify">
A full-stack, production-ready native PHP and MySQL/MariaDB web application designed to help vehicle owners and system administrators track vehicles, record service histories, monitor upcoming maintenance schedules, receive timely reminders, and analyze maintenance costs in one centralized platform.
</p>

## Features

<div align="justify">
<ul>
  <li><strong>Authentication, RBAC &amp; Security:</strong> Role-based access control separating Vehicle Owners and System Administrators, bcrypt password hashing, failed-login throttling, hardened sessions with automatic invalidation, and server-side defenses against Session Fixation, CSRF, XSS, and IDOR.</li>
  <li><strong>Interactive Dashboard:</strong> Real-time overview displaying fleet metrics, upcoming and overdue maintenance alerts, unread notifications, and quick access to recent service activity.</li>
  <li><strong>Vehicle Management:</strong> Full owner-scoped garage management for adding, updating, viewing, and archiving vehicles with normalized license plates, VINs, odometer tracking, registration dates, and vehicle preferences.</li>
  <li><strong>Service Records:</strong> Detailed ledger of completed and scheduled services, capturing costs, mileage milestones, service providers, replaced parts, and remarks.</li>
  <li><strong>Smart Maintenance Tracking:</strong> Automated calculation engine that classifies scheduled maintenance as Upcoming, Due, or Overdue using both calendar dates and odometer mileage thresholds.</li>
  <li><strong>Real-Time Notifications &amp; Reminders:</strong> Configurable reminder scheduler paired with native RFC 6455 WebSocket push notifications and database fallback polling for expiring registrations and due services.</li>
  <li><strong>Reporting &amp; Cost Analytics:</strong> Monthly and yearly cost summaries, interactive charts, complete service history reports, and one-click CSV exports.</li>
  <li><strong>Responsive User Interface:</strong> Built with Bootstrap 5, Bootstrap Icons, and Chart.js for a clean, accessible, mobile-friendly experience across desktop and mobile devices.</li>
</ul>
</div>
