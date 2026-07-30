# Branch Setup Module

This module provides comprehensive functionality for managing company branches in the HR Setup section.

## Components

### BranchTable.vue
A data table component for displaying branches with:
- Branch code, name, and head information
- Main branch indicators
- Action buttons for edit/delete
- Pagination support
- Responsive design

### BranchForm.vue
A form component for adding/editing branches including:
- Branch code and name fields
- Branch head selection from employees
- Main branch checkbox
- Form validation and error handling

### BranchModal.vue
A modal wrapper for the branch form with:
- Dynamic title based on add/edit mode
- Proper modal styling and behavior
- Form integration

### BranchFilters.vue
A filtering component with:
- Search functionality (name, code, head)
- Type filtering (main/regular branches)
- Active filter display with removal
- Reset functionality

## Usage

### In Views
```vue
<template>
  <BranchSetup />
</template>

<script setup>
import BranchSetup from '@/Views/HR_Setup/Branch_Setup/BranchSetup.vue'
</script>
```

### Using the Composable
```vue
<script setup>
import { useBranch } from '@/composables/useBranch.js'

const { 
  branches, 
  employees, 
  loading, 
  saving, 
  deleting,
  fetchBranches,
  fetchEmployees,
  saveBranches,
  deleteBranch,
  confirmDeleteBranch,
  formatBranchData,
  filterBranches
} = useBranch()

// Fetch data
await fetchBranches()
await fetchEmployees()

// Save branches (array format for backend)
const formattedData = formatBranchData(branchesData)
await saveBranches(formattedData)

// Delete branch with confirmation
const confirmed = await confirmDeleteBranch(branch)
if (confirmed) {
  await deleteBranch(branch.id)
}
</script>
```

## API Integration

The module integrates with the backend BranchController:
- `GET /branches` - Fetch branches and employees
- `POST /branches` - Save/update branches (array format)
- `DELETE /branches/{id}` - Delete branch
- `GET /branches/{id}/delete` - Get branch for deletion confirmation
- `GET /branches/{id}/edit` - Get branch for editing

## Features

- ✅ Branch CRUD operations
- ✅ Employee assignment as branch heads
- ✅ Main branch designation
- ✅ Search and filtering
- ✅ Pagination
- ✅ Form validation
- ✅ Confirmation dialogs
- ✅ Loading states
- ✅ Error handling
- ✅ Success notifications
- ✅ Responsive design
- ✅ Modular component architecture

## Data Format

### Branch Object
```javascript
{
  id: number,
  code: string,
  name: string,
  branch_head_id: number,
  is_main_branch: boolean
}
```

### Backend Format (for saving)
```javascript
{
  id: [1, 2, 3],
  code: ['HQ', 'BR1', 'BR2'],
  name: ['Headquarters', 'Branch 1', 'Branch 2'],
  branch_head_id: [1, 2, null],
  is_main_branch: [1] // Array of IDs that are main branches
}
```

## File Structure

```
src/
├── components/HR_Setup/Branch_Setup/
│   ├── BranchTable.vue      # Data table component
│   ├── BranchForm.vue       # Form component
│   ├── BranchModal.vue      # Modal wrapper
│   ├── BranchFilters.vue    # Filter component
│   └── README.md           # This file
├── Views/HR_Setup/Branch_Setup/
│   └── BranchSetup.vue     # Main view
└── composables/
    └── useBranch.js        # Branch composable
```
