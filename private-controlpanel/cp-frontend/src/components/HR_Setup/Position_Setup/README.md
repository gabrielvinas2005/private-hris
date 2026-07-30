# Position Setup Module

This module provides comprehensive functionality for managing positions in the HR system.

## Components

### PositionForm.vue
- **Purpose**: Form component for adding and editing position information
- **Features**:
  - Position code input (optional, max 50 characters)
  - Position name input with validation (required, min 3 chars, max 255 chars)
  - Administrative position checkbox with help text
  - Active/Inactive status toggle with help text
  - Character limit display
  - Form validation with proper error messages

### PositionTable.vue
- **Purpose**: Displays positions in a paginated table format
- **Features**:
  - Sortable columns (ID, Code, Name, Type, Status)
  - Search and filter functionality
  - Pagination support
  - Edit and Delete actions
  - Loading states
  - Export functionality (Print, Excel, PDF)
  - Column visibility toggle
  - Responsive design

### PositionModal.vue
- **Purpose**: Modal wrapper for the PositionForm component
- **Features**:
  - Dynamic title (Add/Edit)
  - Form validation
  - Loading states
  - Proper modal behavior

### PositionFilters.vue
- **Purpose**: Search and filter controls for positions
- **Features**:
  - Search by position name or code
  - Filter by status (Active/Inactive)
  - Filter by type (Administrative/Non-Administrative)
  - Add Position button
  - Responsive design

## Data Structure

### Position Object
```javascript
{
  id: number,
  code: string,                        // Optional position code
  name: string,                        // Required, min 3 characters, max 255 characters
  is_administrative_position: boolean, // Administrative position flag
  active: boolean,                     // Active status
  created_at: string,                  // Creation timestamp
  updated_at: string                   // Last update timestamp
}
```

## API Endpoints

- `GET /positions` - Get all positions
- `GET /positions/create` - Get form data (field definitions)
- `POST /positions` - Create new position
- `GET /positions/{id}/edit` - Get position for editing (includes competency data)
- `PATCH /positions/{id}` - Update position
- `DELETE /positions/{id}` - Delete position
- `GET /positions/{id}` - Get position details

## Features

### CRUD Operations
- ✅ Create new positions
- ✅ Read/List all positions
- ✅ Update existing positions
- ✅ Delete positions (with confirmation)

### Search & Filter
- ✅ Search by position name or code
- ✅ Filter by status (Active/Inactive)
- ✅ Filter by type (Administrative/Non-Administrative)
- ✅ Combined filtering support

### Validation
- ✅ Required field validation (position name)
- ✅ Minimum length validation (3 characters)
- ✅ Maximum length validation (255 characters for name, 50 for code)
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
- ✅ Competency system integration (backend includes competency data)
- ✅ Employee integration (positions linked to employees)

## Usage

### In PositionSetup.vue
```vue
<template>
  <PositionFilters @add="handleAddPosition" />
  <PositionTable @edit="handleEditPosition" @delete="handleDeletePosition" />
  <PositionModal v-model="showModal" @submit="handleSavePosition" />
</template>
```

### With usePosition Composable
```javascript
import { usePosition } from '@/composables/usePosition.js'

const {
  positions,
  loading,
  fetchPositions,
  savePosition,
  deletePosition
} = usePosition()
```

## Dependencies

- Vue 3 Composition API
- Element Plus UI Framework
- Custom API Service
- usePosition Composable

## Notes

- Positions are stored in the `positions` table in the backend
- Character limit of 255 characters for position names, 50 for codes
- All operations include proper error handling and user feedback
- The module follows the same patterns as other setup modules
- Export functionality included with simple, clean design
- Delete functionality is included
- Statistics show 4 different metrics for comprehensive insights
- Help text provided for form fields to improve user experience
- Backend controller is `PositionsController.php`
- Competency system integration available (backend provides competency data)
- Administrative vs Non-Administrative position classification
- Form includes both required and optional fields
- Proper data type conversion for boolean fields
- Comprehensive filtering by multiple criteria
- Responsive design for all screen sizes
- Column visibility control for better user experience
- Print, Excel, and PDF export capabilities
- Pagination for large datasets
- Loading states and skeleton screens
- Error handling with user-friendly messages
- Success notifications for all operations
- Confirmation dialogs for destructive actions
