# Project Guidelines & Context

## Domain Context: Private HRIS
- This system is a **Private HRIS** (Human Resource Information System) designed for private sector enterprise management (transitioned from public/government HRIS framework).
- All new features, pages, terminology, and workflows should reflect **private company HR standards** (e.g., SSS, PhilHealth, Pag-IBIG, private employee 201 files, performance reviews, company policies, private payroll, and benefits) rather than public/civil service government requirements (e.g., SALN, GSIS, CS Form 212, CSC plantilla).

## Project overview
- Define a complete, modular HRIS covering the full private-sector employee lifecycle — from recruitment through separation
- Provide a browser-accessible, role-based platform eliminating workstation-bound limitations.
- Establish a self-service model for employees and managers to reduce manual HR processing.
-  Ensure automatic compliance with Philippine private-sector labor law and statutory-contribution requirements (SSS, PhilHealth, Pag-IBIG, BIR, Labor Code leave benefits).
- Provide comprehensive, exportable reporting and analytics across all HR functions.
- Define clear process flows and predecessor relationships between modules to guide system design without prescribing database structure.

- The HRIS shall be delivered as a single, web-based platform with the following high-level architectural characteristics. This section describes the platform posture only; internal data structures and data-flow diagrams are excluded by design.
    
    - Browser-based front end accessible from desktop, tablet, and mobile devices without additional client software.
    - Centralized application layer serving all sixteen modules from a single employee master data source (Core HR).
    - Role-based access layer governing what each user type (employee, manager, HR admin, payroll officer, system administrator) can view or transact.
    - A configurable workflow/approval engine shared across modules (leave, overtime, requisitions, expense-type approvals) rather than hard-coded per module.
    - A notification engine (email/SMS/in-app) triggered by transaction events across all modules.
    - Deployable on-premise or cloud, per client infrastructure preference.