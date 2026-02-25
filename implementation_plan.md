# Implementation Plan - MWAKASEGE HOUSE TENANT MANAGEMENT (MHTM)

## Overview
A comprehensive tenant management system built with Core PHP, MySQL, and a modern UI.

## Phase 1: Database & Config
- Database: `mhtm_db`
- Tables: `admins`, `houses`, `tenants`, `payments`, `expenses`, `sms_logs`, `email_logs`, `whatsapp_logs`, `settings`.
- Files:
  - `config/db.php`: PDO connection.
  - `config/functions.php`: Global utility functions (security, formatting).
  - `schema.sql`: Database creation script.

## Phase 2: Core Assets & Layout
- `assets/css/style.css`: Glassmorphism/Modern UI based on CSS variables.
- `includes/header.php`: Global header with nav/sidebar.
- `includes/footer.php`: Global footer with scripts.
- `includes/sidebar.php`: Navigation menu.

## Phase 3: Authentication
- `login.php`: Admin login with `password_hash`.
- `logout.php`: Clean sessions and redirect.

## Phase 4: Dashboard
- `dashboard.php`: Stats overview (Tenants, Houses, Income, Debt, Profit).
- Chart.js implementation for monthly income.

## Phase 5: House Management (CRUD)
- `admin/houses.php`: Manage property units.
- Auto-status update (Occupied/Vacant) based on tenant assignment.

## Phase 6: Tenant Management (CRUD + Profiles)
- `admin/tenants.php`: Full tenant lifecycle.
- `admin/tenant_profile.php`: Detailed view with photo, history, and communication shortcuts.
- File upload validation (image only).

## Phase 7: Payment & Finance
- `admin/payments.php`: Recording payments, auto-balance calculation, receipt generation.
- `admin/expenses.php`: Recording expenses.
- Receipt PDF generation (using TCPDF or DOMPDF).

## Phase 8: Communication Center
- Integration with PHPMailer for SSL/TLS emails.
- SMS API integration guide (Africa's Talking / Twilio).
- WhatsApp Cloud API integration.
- Logging for all messages.

## Phase 9: Reports & Export
- Filtering by date/month/house.
- Export functionality (PDF/Excel/CSV).
- Auto-debt reminders (Cron job example).

## Phase 10: Security & Settings
- PDO Prepared Statements.
- CSRF & XSS protection.
- Settings page for API keys and SMTP.

## Timeline
1. Database & Layout: Day 1
2. House & Tenant Management: Day 1
3. Payments & Dashboard: Day 2
4. Communication & Reports: Day 2
5. Final Polish & Testing: Day 2
