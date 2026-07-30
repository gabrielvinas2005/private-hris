# Specialization Setup Module

This module provides comprehensive functionality for managing specializations (learnings) in the HR system.

## Components

### SpecializationForm.vue
- **Purpose**: Form component for adding and editing specialization information
- **Features**:
  - Specialization name input with validation (required, min 3 chars, max 255 chars)
  - Active/Inactive status toggle with help text
  - Character limit display
  - Form validation with proper error messages

### SpecializationTable.vue
- **Purpose**: Displays specializations in a paginated table format
- **Features**:
  - Sortable columns (ID, Name, Status)
  - Search and filter functionality
  - Pagination support
  - Edit and Delete actions
  - Loading states
  - Export functionality (Print, Excel, PDF)
  - Column visibility toggle
  - Responsive design

### SpecializationModal.vue
- **Purpose**: Modal wrapper for the SpecializationForm component
- **Features**:
  - Dynamic title (Add/Edit)
  - Form validation
  - Loading states
  - Proper modal behavior

### SpecializationFilters.vue
- **Purpose**: Search and filter controls for specializations
- **Features**:
  - Search by specialization name
  - Filter by status (Active/Inactive)
  - Add Specialization button
  - Responsive design

## Data Structure

### Specialization Object
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

- `GET /learnings` - Get all specializations
- `GET /learnings/create` - Get form data (simple response)
- `POST /learnings` - Create new specialization
- `GET /learnings/{id}/edit` - Get specialization for editing
- `PATCH /learnings/{id}` - Update specialization
- `DELETE /learnings/{id}` - Delete specialization
- `GET /learnings/{id}` - Get specialization details

## Features

### CRUD Operations
- ✅ Create new specializations
- ✅ Read/List all specializations
- ✅ Update existing specializations
- ✅ Delete specializations (with confirmation)

### Search & Filter
- ✅ Search by specialization name
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
- ✅ Simple data structure
- ✅ Employee integration (work_specialization_id in employment records)

## Usage

### In SpecializationSetup.vue
```vue
<template>
  <SpecializationFilters @add="handleAddSpecialization" />
  <SpecializationTable @edit="handleEditSpecialization" @delete="handleDeleteSpecialization" />
  <SpecializationModal v-model="showModal" @submit="handleSaveSpecialization" />
</template>
```

### With useSpecialization Composable
```javascript
import { useSpecialization } from '@/composables/useSpecialization.js'

const {
  specializations,
  loading,
  fetchSpecializations,
  saveSpecialization,
  deleteSpecialization
} = useSpecialization()
```

## Dependencies

- Vue 3 Composition API
- Element Plus UI Framework
- Custom API Service
- useSpecialization Composable

## Notes

- Specializations are stored in the `learnings` table in the backend
- Character limit of 255 characters for specialization names
- All operations include proper error handling and user feedback
- The module follows the same patterns as other setup modules
- Export functionality included with simple, clean design
- Delete functionality is included
- Statistics show 3 different metrics for better insights
- Help text provided for form fields to improve user experience
- Backend controller is `LearningsController.php` but serves as specialization management
