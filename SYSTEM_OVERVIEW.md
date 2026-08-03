# Comprehensive System Architecture & Module Overview
## Private HRIS Platform

---

### Executive Summary

The **Private HRIS Platform** is an enterprise-grade Human Resource Information System specifically designed for government agencies and large institutions in the Philippines. It automates personnel management, self-service applications, multi-tier approvals, biometric timekeeping, performance evaluations, and complex payroll processing.

The system is architected as a **modular micro-portal suite** consisting of 6 decoupled frontend-backend pairs sharing centralized database structures, authentication models, and standardized data models.

---

## 1. System Architecture & Tech Stack

```
+---------------------------------------------------------------------------------------------------+
|                                       USER INTERFACE LAYER                                        |
|  [ 201 Portal ]    [ E-Portal ]    [ A-Portal ]    [ Control Panel ]   [ Timekeeping ]   [ Payroll ]  |
|   (Vue 3/Vite)     (Vue 3/Vite)   (Vue 3/Vite)     (Vue 3/Vite)      (Vue 3/Vite)    (Vue 3/Vite) |
+-------+----------------+----------------+----------------+----------------+----------------+------+
        |                |                |                |                |                |
        +----------------+----------------+-------+--------+----------------+----------------+
                                                  | REST APIs / JSON (Sanctum Tokens)
+-------------------------------------------------+--------------------------------------------------+
|                                        BACKEND SERVICES LAYER                                     |
|  [ 201-Backend ]  [ EP-Backend ]   [ AP-Backend ]   [ CP-Backend ]    [ TK-Backend ]   [ PR-Backend ] |
|   (Laravel 8+)     (Laravel 8+)    (Laravel 8+)     (Laravel 8+)      (Laravel 8+)     (Laravel 8+)  |
+-------+----------------+----------------+----------------+----------------+----------------+------+
        |                |                |                |                |                |
        +----------------+----------------+-------+--------+----------------+----------------+
                                                  | SQL Server Driver (sqlsrv)
+-------------------------------------------------+--------------------------------------------------+
|                                         DATABASE / STORAGE LAYER                                  |
|   +-----------------------+     +------------------------+     +-------------------------------+   |
|   | Primary HRIS Database |     |  Biometrics Database   |     |     Attachments Database      |   |
|   |  (Core HR & Payroll)  |     |      (sqlsrv_bio)      |     | (WTIHRIS_PTTC_ATTACHMENTS)    |   |
|   +-----------------------+     +------------------------+     +-------------------------------+   |
+----------------------------------------------------------------------------------------------------+
```

### Core Technologies
- **Frontend Stack**: Vue 3, Vite, Vue Router, Axios, Tailwind CSS / Vanilla CSS.
- **Backend Stack**: Laravel Framework (PHP 8+), Laravel Sanctum (Token-Based Authentication).
- **Database Engine**: Microsoft SQL Server (`sqlsrv` driver).
- **Storage Strategy**: Dedicated SQL Server Database (`WTIHRIS_PTTC_ATTACHMENTS`) for file uploads (PDS attachments, leave certificates, OB documents, IPCR forms).

---

## 2. Core Modules Breakdown

The HRIS suite consists of 6 primary modules, each divided into a **Frontend (`*-frontend`)** and **Backend (`*-backend`)**:

### 1. 201 File Management (`private-201`)
- **Purpose**: Master repository for employee personnel records based on Philippine Civil Service Commission (CSC Form 212 - Personal Data Sheet).
- **Key Functions**:
  - Personal Information, Contact Details, Family Background & Dependents.
  - Educational Attainment, Civil Service Eligibility, Work Experience, Voluntary Work.
  - Seminars & Training Records, Recognitions, Special Skills, References.
  - Official Service Record generation and PDS Excel/PDF export.

### 2. Employee Self-Service / E-Portal (`private-e-portal`)
- **Purpose**: Employee-facing web portal for individual requests, filings, and personal record tracking.
- **Key Functions**:
  - Filing of Leave Applications, Overtime (OT) Requests, and Official Business (OB) / Travel Orders.
  - Performance Evaluation Form Submissions (IPCR - Individual Performance Commitment & Review, OPCR, DPCR).
  - Annual SALN (Statement of Assets, Liabilities, and Net Worth) submission.
  - Certificate & HR Document Requests (Certificate of Employment, Service Records).
  - Online Payslip viewing and printing.
  - 201 File self-update requests.

### 3. Approval & Administration Portal / A-Portal (`private-a-portal`)
- **Purpose**: Managerial and supervisory portal for reviewing and processing employee applications.
- **Key Functions**:
  - Multi-tier approval workflows (Immediate Supervisor -> Division Head -> HR / Agency Head).
  - Actioning Leave applications, OB/Travel orders, OT authorizations, and Document Requests.
  - Review and rating of IPCR/DPCR performance target forms.
  - Verification and approval of 201 File update requests submitted by employees.

### 4. Timekeeping & Attendance System (`private-timekeeping`)
- **Purpose**: Tracks employee daily attendance, biometric logs, work schedules, and computes rendered hours.
- **Key Functions**:
  - Ingestion of raw biometric punch logs from `sqlsrv_bio`.
  - Daily Time Record (DTR) calculation, incorporating tardiness, undertime, and overtime.
  - Integration of approved Leaves, OBs, and Official Travel into the DTR calculation engine.
  - Work shift schedule assignment and holiday management.
  - Generation of monthly DTR reports for payroll submission.

### 5. Payroll Management System (`private-payroll`)
- **Purpose**: Financial computation and disbursement engine for employee compensation.
- **Key Functions**:
  - Base salary calculation (Salary Grade & Step increment scaling).
  - Mandatory government deductions: GSIS, Pag-IBIG, PhilHealth, BIR Tax withholding.
  - Allowances computation: PERA (Personal Economic Relief Allowance), RATA (Representation and Transportation Allowance), Hazard Pay, Night Differential.
  - Loan deductions and customized employee deductions.
  - Payroll Register generation, General Payroll Vouchers, and official Bank Payroll Files.
  - Government remittance file exports (GSIS, Pag-IBIG, PhilHealth reports).

### 6. System Control Panel (`private-controlpanel`)
- **Purpose**: Central administrative hub for system setup, master reference tables, and security.
- **Key Functions**:
  - Organizational Structure setup: Companies, Branches, Offices, Divisions, Sections.
  - Position & Plantilla Item setup, Employment Types (Permanent, Contractual, Casual, Job Order).
  - Payroll reference tables: Tax tables, Mid-Year Bonus, Year-End Bonus, Cash Gift, Monetization rates.
  - User Management, Role-Based Access Control (RBAC), and Tab/Feature permissions.
  - Biometric device connection configurations and system audit logging.

---

## 3. Inter-Module Interactions & Data Flow

```
                           +------------------------+
                           |  Control Panel (CP)    |
                           | Setup & System Reference|
                           +-----------+------------+
                                       |
                   +-------------------+-------------------+
                   |                                       |
                   v                                       v
         +------------------+                    +------------------+
         |  201 File (201)  |                    | Timekeeping (TK) |
         | Employee Master  |                    | Biometrics & DTR |
         +---------+--------+                    +---------+--------+
                   |                                       |
     +-------------+-------------+                         |
     |                           |                         |
     v                           v                         v
+----+---------------+   +-------+------------+    +-------+------------+
| E-Portal (EP)      |   | Approval (AP)      |    | Payroll System(PR) |
| Employee Requests  +-->+ Supervisor Actions +--->+ Final Computation  |
+--------------------+   +--------------------+    +--------------------+
```

### Key Workflows

#### A. Employee Leave & Travel Application Workflow
1. **Employee Application**: An employee files a Leave or OB request via **E-Portal**.
2. **Approval Workflow**: The request routes to **A-Portal**, where Division Heads and HR review and approve the request.
3. **Attendance Adjustment**: Once approved, **Timekeeping** automatically updates the employee's DTR, reflecting the approved leave/OB status for the date range.
4. **Payroll Processing**: If the leave is leave-without-pay (LWOP), **Payroll** reads the DTR summary from Timekeeping and calculates salary deductions.

#### B. Attendance & Payroll Calculation Workflow
1. **Raw Log Capture**: Biometric hardware pushes punch logs into `sqlsrv_bio`.
2. **DTR Processing**: **Timekeeping** parses raw punches against shift schedules, approved leaves, and OB applications to compute tardiness, undertime, and regular hours worked.
3. **Payroll Lock**: At the end of the payroll period, **Timekeeping** locks and exports DTR summaries to **Payroll**.
4. **Disbursement**: **Payroll** computes base salaries, adds allowances (PERA/RATA), deducts statutory contributions and LWOP undertime, and produces final Payslips (viewable by employees in **E-Portal**).

#### C. Employee Onboarding & Master Record Workflow
1. **Profile Creation**: HR creates the employee record in **Control Panel** (assigning Office, Division, Position, Salary Grade, and user roles).
2. **201 File Completion**: HR updates the detailed 201 File (PDS) in **201 File Management**.
3. **Self-Service Access**: The employee logs in via **E-Portal** using the credentials generated by Control Panel to view their profile, submit 201 update requests, and access self-service tools.

---

## 4. Multi-Database Architecture

The system utilizes an isolated multi-database architecture hosted on MS SQL Server for reliability, scalability, and security:

1. **`sqlsrv` (Primary Database)**:
   - Contains all core relational tables: `users`, `employees`, `leaves`, `overtime_applications`, `payrolls`, `positions`, `offices`, `saln_records`, `ipcr_ratings`.
2. **`sqlsrv_bio` (Biometrics Raw Database)**:
   - Dedicated database storing high-volume raw punch logs directly ingested from hardware biometric time clocks.
3. **`WTIHRIS_PTTC_ATTACHMENTS` (Attachments Database)**:
   - Isolated database optimized for storing blob/binary attachment records (scanned PDFs, Medical Certificates, COE attachments, signed performance forms) without inflating the main transactional database.
4. **`sqlsrv_mig` (Legacy / Migration Database)**:
   - Auxiliary database used during system migration to ingest and transform historical HRIS data.

---

## 5. Security & Access Control

- **Authentication**: RESTful JSON API authentication using **Laravel Sanctum**.
- **Role-Based Access Control (RBAC)**: Centralized permission matrix managed in Control Panel, governing route access and tab visibilities across all 6 frontends.
- **Audit Logging**: Comprehensive logging of data modifications (create, update, delete, approval) to comply with civil service audit trail requirements.

---

## Summary Table of System Modules

| Module Name | Codebase Directories | Primary Users | Key Responsibility |
|---|---|---|---|
| **201 File Management** | `private-201/201-frontend`<br>`private-201/201-backend` | HR Officers | Personnel Master Data & PDS (CS Form 212) |
| **Employee Portal (E-Portal)** | `private-e-portal/ep-frontend`<br>`private-e-portal/ep-backend` | General Employees | Leave, OT, OB, IPCR, SALN filings, Payslip viewing |
| **Approval Portal (A-Portal)** | `private-a-portal/ap-frontend`<br>`private-a-portal/ap-backend` | Supervisors, Division Heads | Multi-tier approval of requests & performance reviews |
| **Timekeeping System** | `private-timekeeping/tk-frontend`<br>`private-timekeeping/tk-backend` | HR Timekeepers | Biometric parsing, Shifts, DTR processing, Tardiness |
| **Payroll System** | `private-payroll/pr-frontend`<br>`private-payroll/pr-backend` | Payroll Officers, Accountants | Compensation, Taxes, Statutory Remittances, Payslips |
| **Control Panel** | `private-controlpanel/cp-frontend`<br>`private-controlpanel/cp-backend` | System Administrators | Master Tables, RBAC, User Accounts, Configurations |
