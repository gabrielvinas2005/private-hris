# Employment Type Setup Module

This module provides comprehensive functionality for managing employment types in the HR system.

## Components

### EmploymentTypeForm.vue
- **Purpose**: Form component for adding and editing employment type information
- **Features**:
  - Employment type name input with validation (required, min 3 chars, max 255 chars)
  - With End Contract checkbox with help text
  - Active/Inactive status toggle with help text
  - Character limit display
  - Form validation with proper error messages

### EmploymentTypeTable.vue
- **Purpose**: Displays employment types in a paginated table format
- **Features**:
  - Sortable columns (ID, Name, End Contract, Status)
  - Search and filter functionality
  - Pagination support
  - Edit and Delete actions
  - Loading states
  - Export functionality (Print, Excel, PDF)
  - Column visibility toggle
  - Responsive design

### EmploymentTypeModal.vue
- **Purpose**: Modal wrapper for the EmploymentTypeForm component
- **Features**:
  - Dynamic title (Add/Edit)
  - Form validation
  - Loading states
  - Proper modal behavior

### EmploymentTypeFilters.vue
- **Purpose**: Search and filter controls for employment types
- **Features**:
  - Search by employment type name
  - Filter by status (Active/Inactive, With/Without End Contract)
  - Add Employment Type button
  - Responsive design

## Data Structure

### Employment Type Object
```javascript
{
  id: number,
  name: string,                    // Required, min 3 characters, max 255 characters
  with_end_contract: boolean,      // Whether this employment type has an end contract
  active: boolean,                 // Active status
  created_at: string,              // Creation timestamp
  updated_at: string               // Last update timestamp
}
```

## API Endpoints

- `GET /employment-types` - Get all employment types
- `GET /employment-types/create` - Get form data (simple response)
- `POST /employment-types` - Create new employment type
- `GET /employment-types/{id}/edit` - Get employment type for editing
- `PATCH /employment-types/{id}` - Update employment type
- `DELETE /employment-types/{id}` - Delete employment type
- `GET /employment-types/{id}` - Get employment type details

## Features

### CRUD Operations
- ✅ Create new employment types
- ✅ Read/List all employment types
- ✅ Update existing employment types
- ✅ Delete employment types (with confirmation)

### Search & Filter
- ✅ Search by employment type name
- ✅ Filter by status (Active/Inactive)
- ✅ Filter by contract type (With/Without End Contract)
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
- ✅ Statistics cards (4 different metrics)
- ✅ Pagination
- ✅ Export functionality

### Integration
- ✅ Audit trail (handled by backend)
- ✅ Simple data structure with contract management

## Usage

### In EmploymentTypeSetup.vue
```vue
<template>
  <EmploymentTypeFilters @add="handleAddEmploymentType" />
  <EmploymentTypeTable @edit="handleEditEmploymentType" @delete="handleDeleteEmploymentType" />
  <EmploymentTypeModal v-model="showModal" @submit="handleSaveEmploymentType" />
</template>
```

### With useEmploymentType Composable
```javascript
import { useEmploymentType } from '@/composables/useEmploymentType.js'

const {
  employmentTypes,
  loading,
  fetchEmploymentTypes,
  saveEmploymentType,
  deleteEmploymentType
} = useEmploymentType()
```

## Dependencies

- Vue 3 Composition API
- Element Plus UI Framework
- Custom API Service
- useEmploymentType Composable

## Notes

- Employment types include contract management (with/without end contract)
- Character limit of 255 characters for employment type names
- All operations include proper error handling and user feedback
- The module follows the same patterns as other setup modules
- Export functionality included with simple, clean design
- Delete functionality is included
- Statistics show 4 different metrics for better insights
- Help text provided for form fields to improve user experience
