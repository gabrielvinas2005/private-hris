# Monetization Setup Components

This folder contains the modular components for the Monetization Setup functionality in the Payroll Setup section.

## Components

### 1. MonetizationForm.vue
- **Purpose**: Form component for editing monetization setup configuration
- **Features**:
  - CF Rate input with decimal precision
  - Maximum Number Allowed input
  - Input validation for positive numbers
  - Help text and descriptions
  - Save/Cancel actions

### 2. MonetizationTable.vue
- **Purpose**: Display component for monetization setup data
- **Features**:
  - Single record display (setup is typically one configuration)
  - Sortable columns
  - Column visibility controls
  - Loading and error states
  - Proper number formatting

### 3. MonetizationModal.vue
- **Purpose**: Modal wrapper for the form component
- **Features**:
  - Modal dialog with form integration
  - Event handling for form actions
  - Loading states

### 4. MonetizationFilters.vue
- **Purpose**: Information and action controls
- **Features**:
  - Setup description and information
  - Edit button
  - Event emission for parent handling

## API Integration

The components use the `useMonetizationSetup` composable which provides:
- CRUD operations for monetization setup
- Form state management
- Error handling

## Backend Endpoints

- `GET /monetization-setups` - Get monetization setup configuration
- `POST /monetization-setups/{id}` - Save/update monetization setup

## Usage

The main view (`Monetization.vue`) integrates all components and provides:
- Complete CRUD functionality
- Export capabilities (CSV, PDF, Print)
- Column visibility controls
- Single configuration management

## Data Structure

The monetization setup contains:
- `id`: Unique identifier
- `cf_rate`: Conversion factor rate for monetization calculation (decimal)
- `maximum_number_allowed`: Maximum number of leave credits allowed for monetization

## Validation Rules

- CF Rate must be a positive number (typically between 0 and 1)
- Maximum Number Allowed must be a positive integer
- Both fields are required
- CF Rate cannot exceed 1.0
- Maximum Number Allowed cannot exceed 100

## Business Logic

The monetization setup is used to configure:
- **CF Rate**: Conversion factor rate used in monetization calculations
  - Formula: `amount = ((salary * total_days) * cf_rate)`
  - Default value: 0.0481927 (from controller)
- **Maximum Number Allowed**: Maximum number of leave credits that can be monetized
  - Used for validation in leave monetization applications
  - Default value: 0 (unlimited)

## Key Features

- **Single Configuration**: Unlike other setups, this manages a single configuration record
- **Decimal Precision**: CF Rate supports up to 7 decimal places for accuracy
- **Input Validation**: Comprehensive validation for data integrity
- **Default Values**: Provides sensible defaults when no setup exists
- **Export Capabilities**: CSV, PDF, and print options
- **Error Handling**: Graceful fallbacks and user notifications

## Differences from Other Setups

Unlike table-based setups (Cash Gift, Mid Year Bonus, Year End Bonus), the Monetization Setup:
- **Single Record**: Manages one configuration instead of multiple records
- **Configuration Focus**: Sets up parameters for other processes rather than managing data tables
- **No CRUD Table**: Uses a simple form instead of a data table
- **System Parameters**: Controls system behavior rather than storing business data

## Integration with Leave Monetization

This setup directly affects the Leave Monetization process:
- CF Rate is used in the monetization calculation formula
- Maximum Number Allowed is used for validation
- Changes here affect all future monetization applications
- Default CF Rate of 0.0481927 is used when no setup exists
