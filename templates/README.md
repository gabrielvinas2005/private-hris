# Templates & Component Blueprints Repository

Central repository for standard layout shells, component blueprints, and page templates across the **Private HRIS Platform**.

---

## Directory Structure

```
templates/
├── layouts/
│   ├── MainLayoutSideMenu.vue     # Reusable layout shell with sidebar + header + notifications
│   └── navigation.example.js      # Sample navigation configurations for micro-portals
├── components/                    # Component blueprints (modals, cards, forms, tables)
├── pages/                         # Standard page blueprints
└── README.md
```

---

## Layout Templates

### 1. `MainLayoutSideMenu.vue`
- **Purpose**: Modular portal shell containing collapsible sidebar, top bar header, notifications popover, and slot container.
- **Props**:
  - `companyName`: Brand text (default: `'Company'`)
  - `companyLogo`: Image path or URL
  - `systemLabel`: Portal subtitle (e.g. `'Employee Portal'`)
  - `dark`: Boolean for dark mode styling
  - `pageTitle` / `pageDescription`: Header page labels
  - `navItems`: Data-driven array of routes (`[{ to, label, icon, visible }]`)
  - `adminItems`: Cross-portal administrative links (`[{ key, label, icon, onClick, visible }]`)
  - `userData`: Object containing `{ name, email }`
- **Slots**:
  - `logo`: Custom logo slot
  - `default`: Main page content container

---

## Adding New Templates
When adding new component or page templates to this repository:
1. Place layouts in `templates/layouts/`
2. Place reusable UI components in `templates/components/`
3. Place full page blueprints in `templates/pages/`
4. Document props, emits, and usage instructions at the top of each `.vue` file.
