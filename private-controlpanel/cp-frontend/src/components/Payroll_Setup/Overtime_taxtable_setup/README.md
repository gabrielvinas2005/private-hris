# Overtime Tax Table Setup Components

This folder contains the modular components for the Overtime Tax Table Setup functionality in the Payroll Setup section.

## Components

### 1. OvertimeTaxTableForm.vue
- **Purpose**: Form component for editing overtime tax table data
- **Features**:
  - Year selection dropdown
  - Dynamic table with amount ranges and percentages
  - Add/Remove rows functionality
  - Input validation for amount ranges
  - Save/Cancel actions

### 2. OvertimeTaxTableTable.vue
- **Purpose**: Display component for overtime tax table data
- **Features**:
  - Sortable columns
  - Search functionality
  - Column visibility controls
  - Delete actions with confirmation
  - Loading and error states

### 3. OvertimeTaxTableModal.vue
- **Purpose**: Modal wrapper for the form component
- **Features**:
  - Modal dialog with form integration
  - Event handling for form actions
  - Loading states

### 4. OvertimeTaxTableFilters.vue
- **Purpose**: Filter and action controls
- **Features**:
  - Search input
  - Year selection
  - Edit button
  - Event emission for parent handling

## API Integration

The components use the `useOvertimeTaxTable` composable which provides:
- CRUD operations for overtime tax tables
- Year-based data loading
- Form state management
- Error handling

## Backend Endpoints

- `GET /overtime-tax-table` - Get current year data
- `GET /overtime-tax-table/{year}/load` - Get data for specific year
- `POST /overtime-tax-table` - Save/update overtime tax table
- `GET /overtime-tax-table/{id}/delete` - Delete specific record

## Usage

The main view (`OvertimeTaxTable.vue`) integrates all components and provides:
- Complete CRUD functionality
- Export capabilities (CSV, PDF, Print)
- Column visibility controls
- Search and filtering
- Year-based data management

## Data Structure

Each overtime tax record contains:
- `id`: Unique identifier
- `amount_from`: Starting amount for the range
- `amount_to`: Ending amount for the range
- `percentage`: Tax percentage for the range
- `fiscal_year`: Year the tax table applies to

## Validation Rules

- Amount ranges cannot overlap
- Amount From must be less than Amount To
- Percentage must be between 0-100
- At least one valid row required for saving
