# Eligibility Setup Module

This module provides comprehensive functionality for managing eligibilities in the HR system.

## Components

### EligibilityForm.vue
- **Purpose**: Form component for adding and editing eligibility information
- **Features**:
  - Eligibility name input with validation (required, min 3 chars, max 255 chars)
  - Active/Inactive status toggle
  - Character limit display
  - Form validation with proper error messages

### EligibilityTable.vue
- **Purpose**: Displays eligibilities in a paginated table format
- **Features**:
  - Sortable columns (ID, Name, Status)
  - Search and filter functionality
  - Pagination support
  - Edit and Delete actions
  - Loading states
  - Export functionality (Print, Excel, PDF)
  - Column visibility toggle
  - Responsive design

### EligibilityModal.vue
- **Purpose**: Modal wrapper for the EligibilityForm component
- **Features**:
  - Dynamic title (Add/Edit)
  - Form validation
  - Loading states
  - Proper modal behavior

### EligibilityFilters.vue
- **Purpose**: Search and filter controls for eligibilities
- **Features**:
  - Search by eligibility name
  - Filter by status (Active/Inactive)
  - Add Eligibility button
  - Responsive design

## Data Structure

### Eligibility Object
```javascript
{
  id: number,
  name: string,           // Required, min 3 characters, max 255 characters
  active: boolean,        // Active status
  created_at: string,     // Creation timestamp
  updated_at: string      // Last update timestamp
}
```

## API Endpoints

- `GET /eligibilities` - Get all eligibilities
- `GET /eligibilities/create` - Get form data (simple response)
- `POST /eligibilities` - Create new eligibility
- `GET /eligibilities/{id}/edit` - Get eligibility for editing
- `PATCH /eligibilities/{id}` - Update eligibility
- `DELETE /eligibilities/{id}` - Delete eligibility
- `GET /eligibilities/{id}` - Get eligibility details

## Features

### CRUD Operations
- ✅ Create new eligibilities
- ✅ Read/List all eligibilities
- ✅ Update existing eligibilities
- ✅ Delete eligibilities (with confirmation)

### Search & Filter
- ✅ Search by eligibility name
- ✅ Filter by status (Active/Inactive)
- ✅ Combined filtering support

### Validation
- ✅ Required field validation
- ✅ Minimum length validation (3 characters)
- ✅ Maximum length validation (255 characters)
- ✅ Unique name validation

### UI/UX
- ✅ Responsive design
- ✅ Loading states
- ✅ Error handling
- ✅ Success messages
- ✅ Confirmation dialogs
- ✅ Statistics cards
- ✅ Pagination
- ✅ Export functionality

### Integration
- ✅ Audit trail (handled by backend)
- ✅ Simple data structure (no complex relationships)

## Usage

### In EligibilitySetup.vue
```vue
<template>
  <EligibilityFilters @add="handleAddEligibility" />
  <EligibilityTable @edit="handleEditEligibility" @delete="handleDeleteEligibility" />
  <EligibilityModal v-model="showModal" @submit="handleSaveEligibility" />
</template>
```

### With useEligibility Composable
```javascript
import { useEligibility } from '@/composables/useEligibility.js'

const {
  eligibilities,
  loading,
  fetchEligibilities,
  saveEligibility,
  deleteEligibility
} = useEligibility()
```

## Dependencies

- Vue 3 Composition API
- Element Plus UI Framework
- Custom API Service
- useEligibility Composable

## Notes

- Eligibility is a simple entity with just name and active status
- No complex relationships with other entities
- Character limit of 255 characters for eligibility names
- All operations include proper error handling and user feedback
- The module follows the same patterns as other setup modules
- Export functionality included with simple, clean design
- Delete functionality is included (unlike some other modules)
