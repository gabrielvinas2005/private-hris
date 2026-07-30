# Mid Year Bonus Table Setup Components

This folder contains the modular components for the Mid Year Bonus Table Setup functionality in the Payroll Setup section.

## Components

### 1. MidYearBonusForm.vue
- **Purpose**: Form component for editing mid year bonus table data
- **Features**:
  - Dynamic table with months and percentages
  - Add/Remove rows functionality
  - Input validation for months and percentages
  - Duplicate month prevention
  - Save/Cancel actions

### 2. MidYearBonusTable.vue
- **Purpose**: Display component for mid year bonus table data
- **Features**:
  - Sortable columns
  - Search functionality
  - Column visibility controls
  - Delete actions with confirmation
  - Loading and error states

### 3. MidYearBonusModal.vue
- **Purpose**: Modal wrapper for the form component
- **Features**:
  - Modal dialog with form integration
  - Event handling for form actions
  - Loading states

### 4. MidYearBonusFilters.vue
- **Purpose**: Filter and action controls
- **Features**:
  - Search input
  - Edit button
  - Event emission for parent handling

## API Integration

The components use the `useMidYearBonusTable` composable which provides:
- CRUD operations for mid year bonus tables
- Form state management
- Error handling

## Backend Endpoints

- `GET /midyear-tables` - Get all mid year bonus data
- `POST /midyear-tables` - Save/update mid year bonus table
- `DELETE /midyear-tables/{id}` - Delete specific record
- `GET /midyear-tables/{id}/delete` - Get record for deletion confirmation

## Usage

The main view (`MidYearBonus.vue`) integrates all components and provides:
- Complete CRUD functionality
- Export capabilities (CSV, PDF, Print)
- Column visibility controls
- Search and filtering

## Data Structure

Each mid year bonus record contains:
- `id`: Unique identifier
- `months`: No. of Aggregate Months of Service
- `percentage`: Percentage of Basic Monthly Salary

## Validation Rules

- Months must be positive integers
- Each month should be unique (no duplicates)
- Percentage must be between 0-1 (decimal format, e.g., 0.15 for 15%)
- At least one valid row required for saving
- Months field is required
- Percentage field is required

## Business Logic

The mid year bonus table is used to determine the percentage of basic monthly salary that employees receive as their mid-year bonus based on their months of service. The table maps:
- No. of Aggregate Months of Service → Percentage of Basic Monthly Salary (decimal format)
- Example: 6 months = 0.50 (50% of basic monthly salary)
