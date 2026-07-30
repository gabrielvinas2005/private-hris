# Cash Gift Table Setup Components

This folder contains the modular components for the Cash Gift Table Setup functionality in the Payroll Setup section.

## Components

### 1. CashGiftForm.vue
- **Purpose**: Form component for editing cash gift table data
- **Features**:
  - Dynamic table with months and percentages
  - Add/Remove rows functionality
  - Input validation for months and percentages
  - Duplicate month prevention
  - Save/Cancel actions
  - Scrollable table after 10 rows

### 2. CashGiftTable.vue
- **Purpose**: Display component for cash gift table data
- **Features**:
  - Sortable columns
  - Search functionality
  - Column visibility controls
  - Delete actions with confirmation
  - Loading and error states
  - Scrollable table after 10 rows

### 3. CashGiftModal.vue
- **Purpose**: Modal wrapper for the form component
- **Features**:
  - Modal dialog with form integration
  - Event handling for form actions
  - Loading states

### 4. CashGiftFilters.vue
- **Purpose**: Filter and action controls
- **Features**:
  - Search input
  - Edit button
  - Event emission for parent handling

## API Integration

The components use the `useCashGiftTable` composable which provides:
- CRUD operations for cash gift tables
- Form state management
- Error handling

## Backend Endpoints

- `GET /cashgift-tables` - Get all cash gift data
- `POST /cashgift-tables` - Save/update cash gift table
- `DELETE /cashgift-tables/{id}` - Delete specific record
- `GET /cashgift-tables/{id}/delete` - Get record for deletion confirmation

## Usage

The main view (`CashGift.vue`) integrates all components and provides:
- Complete CRUD functionality
- Export capabilities (CSV, PDF, Print)
- Column visibility controls
- Search and filtering

## Data Structure

Each cash gift record contains:
- `id`: Unique identifier
- `months`: No. of Aggregate Months of Service
- `percentage`: Percentage of Basic Monthly Salary (decimal format)

## Validation Rules

- Months must be positive integers
- Each month should be unique (no duplicates) - enforced by backend
- Percentage must be between 0-1 (decimal format, e.g., 0.15 for 15%)
- At least one valid row required for saving
- Months field is required
- Percentage field is required

## Business Logic

The cash gift table is used to determine the percentage of basic monthly salary that employees receive as their cash gift based on their months of service. The table maps:
- No. of Aggregate Months of Service → Percentage of Basic Monthly Salary (decimal format)
- Example: 6 months = 0.50 (50% of basic monthly salary)

## Key Features

- **Decimal Percentage Format**: Uses decimal values (0.15 for 15%) as requested
- **Proper Column Names**: Uses "No. of Aggregate Months of Service" and "Percentage of Basic Monthly Salary"
- **Unique Month Validation**: Backend enforces unique months constraint
- **Scrollable Tables**: Tables become scrollable after 10 rows for better UX
- **Full CRUD Operations**: Create, read, update, and delete functionality
- **Export Capabilities**: CSV, PDF, and print options
- **Input Validation**: Comprehensive validation for data integrity

## Differences from Other Bonus Tables

While similar to Mid Year and Year End Bonus Tables, the Cash Gift Table serves a different purpose:
- **Cash Gift**: Given as a cash benefit based on service months
- **Mid Year Bonus**: Given at the middle of the year
- **Year End Bonus**: Given at the end of the year
- All use the same data structure and validation rules
- All use decimal format for percentages (0.15 for 15%)
