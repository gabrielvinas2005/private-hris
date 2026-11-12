## 📋 Pull Request Summary

### Jira / Linear / GitHub Issue Key
> Closes # <!-- e.g., HRIS-102 or #12 -->

### Type of Change
- [ ] 🚀 User Story (Feature implementation)
- [ ] 🐛 Bugfix (Defect resolution)
- [ ] ♻️ Refactoring / Tech Debt
- [ ] ⚡ Performance Optimization
- [ ] 📝 Documentation / Architecture update

---

## 🏢 Impacted Micro-Portals & Services
Check all modules affected by this change:
- [ ] `private-201` (201-frontend / 201-backend)
- [ ] `private-e-portal` (ep-frontend / ep-backend)
- [ ] `private-a-portal` (ap-frontend / ap-backend)
- [ ] `private-controlpanel` (cp-frontend / cp-backend)
- [ ] `private-timekeeping` (tk-frontend / tk-backend)
- [ ] `private-payroll` (pr-frontend / pr-backend)
- [ ] Database Schema / Migrations (`sqlsrv`, `sqlsrv_bio`, `attachments`)

---

## 🎯 Acceptance Criteria Verification
List the acceptance criteria from the User Story and verify completion:
- [ ] **AC 1:** 
- [ ] **AC 2:** 
- [ ] **AC 3:** 

---

## 🧪 Testing Checklist & Evidence
- [ ] Tested locally on Windows/Docker environment.
- [ ] Multi-portal sync tested (e.g. EP request appears in AP, TK DTR updates PR).
- [ ] API contract backward-compatible (does not break other frontends).
- [ ] No regression on existing database queries.

### Test Evidence (Screenshots / API Responses / Logs)
*(Attach screenshots or paste response snippets here)*

---

## ✅ Scrum Definition of Done (DoD) Sign-Off
- [ ] Code follows PSR-12 (Laravel) and ESLint/Prettier (Vue 3).
- [ ] DB Transactions (`DB::transaction`) wrap any multi-table or cross-database mutations.
- [ ] No hardcoded database credentials or environment variables committed.
- [ ] Self-reviewed code diff before requesting peer review.
