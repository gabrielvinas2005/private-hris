# Year End Bonus Table Setup Components

This folder contains the modular components for the Year End Bonus Table Setup functionality in the Payroll Setup section.

## Components

### 1. YearEndBonusForm.vue
- **Purpose**: Form component for editing year end bonus table data
- **Features**:
  - Dynamic table with months and percentages
  - Add/Remove rows functionality
  - Input validation for months and percentages
  - Duplicate month prevention
  - Save/Cancel actions

### 2. YearEndBonusTable.vue
- **Purpose**: Display component for year end bonus table data
- **Features**:
  - Sortable columns
  - Search functionality
  - Column visibility controls
  - Delete actions with confirmation
  - Loading and error states

### 3. YearEndBonusModal.vue
- **Purpose**: Modal wrapper for the form component
- **Features**:
  - Modal dialog with form integration
  - Event handling for form actions
  - Loading states

### 4. YearEndBonusFilters.vue
- **Purpose**: Filter and action controls
- **Features**:
  - Search input
  - Edit button
  - Event emission for parent handling

## API Integration

The components use the `useYearEndBonusTable` composable which provides:
- CRUD operations for year end bonus tables
- Form state management
- Error handling

## Backend Endpoints

- `GET /yearend-tables` - Get all year end bonus data
- `POST /yearend-tables` - Save/update year end bonus table
- `DELETE /yearend-tables/{id}` - Delete specific record
- `GET /yearend-tables/{id}/delete` - Get record for deletion confirmation

## Usage

The main view (`YearEndBonus.vue`) integrates all components and provides:
- Complete CRUD functionality
- Export capabilities (CSV, PDF, Print)
- Column visibility controls
- Search and filtering

## Data Structure

Each year end bonus record contains:
- `id`: Unique identifier
- `months`: No. of Aggregate Months of Service
- `percentage`: Percentage of Basic Monthly Salary (decimal format)

## Validation Rules

- Months must be positive integers
- Each month should be unique (no duplicates)
- Percentage must be between 0-1 (decimal format, e.g., 0.15 for 15%)
- At least one valid row required for saving
- Months field is required
- Percentage field is required

## Business Logic

The year end bonus table is used to determine the percentage of basic monthly salary that employees receive as their year-end bonus based on their months of service. The table maps:
- No. of Aggregate Months of Service → Percentage of Basic Monthly Salary (decimal format)
- Example: 6 months = 0.50 (50% of basic monthly salary)

## Differences from Mid Year Bonus

While similar to the Mid Year Bonus Table, the Year End Bonus Table serves a different purpose:
- **Mid Year Bonus**: Given at the middle of the year
- **Year End Bonus**: Given at the end of the year
- Both use the same data structure and validation rules
- Both use decimal format for percentages (0.15 for 15%)
