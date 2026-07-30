# Plantilla Setup Module

This module provides comprehensive functionality for managing plantillas (position templates) in the HR system. Plantillas define the requirements and specifications for specific positions within the organization.

## Components

### PlantillaForm.vue
- **Purpose**: Complex form component for adding and editing plantilla information
- **Features**:
  - **Basic Information Section**: Code, position, salary step/grade, department, unit, status, publication dates
  - **Education Requirements Section**: Dynamic list of academic levels and programs
  - **Work Experience Requirements Section**: Dynamic list of positions and years of experience
  - **Eligibility Requirements Section**: Dynamic list of required eligibilities
  - **Training Requirements Section**: Dynamic list of training programs and hours
  - **Remarks Section**: Dynamic list of additional remarks/requirements
  - Form validation with proper error messages
  - Code uniqueness checking
  - Dynamic add/remove functionality for all requirement sections

### PlantillaTable.vue
- **Purpose**: Displays plantillas in a paginated table format
- **Features**:
  - Sortable columns (ID, Code, Position, Step, Grade, Department, Status, Active)
  - Search and filter functionality
  - Pagination support
  - Edit and Delete actions
  - Loading states
  - Export functionality (Print, Excel, PDF)
  - Column visibility toggle
  - Responsive design

### PlantillaModal.vue
- **Purpose**: Large modal wrapper for the PlantillaForm component
- **Features**:
  - Dynamic title (Add/Edit)
  - Form validation
  - Loading states
  - Proper modal behavior
  - Large width (90%) to accommodate complex form
  - Scrollable content area

### PlantillaFilters.vue
- **Purpose**: Search and filter controls for plantillas
- **Features**:
  - Search by code, position, or department
  - Filter by status (Active/Inactive/Vacant/Occupied)
  - Filter by department (dynamic list from data)
  - Add Plantilla button
  - Responsive design

## Data Structure

### Plantilla Object
```javascript
{
  id: number,
  code: string,                        // Required, min 3 characters, max 50 characters
  position_id: number,                 // Required, references positions table
  salary_step_id: number,              // Required, references salary_steps table
  salary_grade_id: number,             // Required, references salary_grades table
  department_id: number,               // Required, references departments table
  unit: string,                        // Optional unit information
  publication_from: string,            // Publication start date
  publication_to: string,              // Publication end date
  status: string,                      // Active/Inactive status
  active: boolean,                     // Active status
  employee_id: number,                 // 0 for vacant, employee ID for occupied
  // Related data arrays
  educations: Array,                   // Education requirements
  experiences: Array,                  // Work experience requirements
  eligibilities: Array,                // Eligibility requirements
  trainings: Array,                    // Training requirements
  remarks: Array                       // Additional remarks
}
```

## API Endpoints

- `GET /plantillas` - Get all plantillas with related data
- `GET /plantillas/create` - Get form data (positions, steps, grades, departments, eligibilities)
- `POST /plantillas` - Create new plantilla
- `GET /plantillas/{id}/edit` - Get plantilla for editing (includes all related data)
- `PATCH /plantillas/{id}` - Update plantilla
- `DELETE /plantillas/{id}` - Delete plantilla
- `GET /plantillas/check-code` - Check code availability

## Features

### CRUD Operations
- ✅ Create new plantillas with complex requirements
- ✅ Read/List all plantillas with related data
- ✅ Update existing plantillas
- ✅ Delete plantillas (with confirmation)

### Search & Filter
- ✅ Search by code, position, or department
- ✅ Filter by status (Active/Inactive/Vacant/Occupied)
- ✅ Filter by department (dynamic list)
- ✅ Combined filtering support

### Validation
- ✅ Required field validation
- ✅ Minimum length validation (3 characters for code)
- ✅ Maximum length validation (50 characters for code)
- ✅ Unique code validation with real-time checking
- ✅ Foreign key validation (position, step, grade, department)

### UI/UX
- ✅ Responsive design for all screen sizes
- ✅ Loading states and skeleton screens
- ✅ **4 Statistics Cards**: Total, Active, Vacant, Occupied
- ✅ Error handling with user-friendly messages
- ✅ Success notifications
- ✅ Confirmation dialogs for deletions
- ✅ Large modal (90% width) for complex form
- ✅ Scrollable form content
- ✅ Export functionality (Print, Excel, PDF)
- ✅ Column visibility toggle

### Complex Form Features
- ✅ **Multi-section Form**: Basic info, education, experience, eligibility, training, remarks
- ✅ **Dynamic Lists**: Add/remove requirements for each section
- ✅ **Code Uniqueness**: Real-time code availability checking
- ✅ **Date Pickers**: Publication date range selection
- ✅ **Dropdown Selections**: Position, salary step/grade, department, eligibility
- ✅ **Form Validation**: Comprehensive validation for all fields
- ✅ **Data Transformation**: Proper data formatting for backend submission

### Integration
- ✅ Audit trail (handled by backend)
- ✅ Complex relational data management
- ✅ Employee integration (vacant vs occupied positions)
- ✅ Position, salary, and department integration
- ✅ Education, experience, eligibility, and training requirements

## Usage

### In PlantillaSetup.vue
```vue
<template>
  <PlantillaFilters @add="handleAddPlantilla" />
  <PlantillaTable @edit="handleEditPlantilla" @delete="handleDeletePlantilla" />
  <PlantillaModal v-model="showModal" @submit="handleSavePlantilla" @code-check="handleCodeCheck" />
</template>
```

### With usePlantilla Composable
```javascript
import { usePlantilla } from '@/composables/usePlantilla.js'

const {
  plantillas,
  loading,
  fetchPlantillas,
  savePlantilla,
  deletePlantilla,
  checkCode
} = usePlantilla()
```

## Dependencies

- Vue 3 Composition API
- Element Plus UI Framework
- Custom API Service
- usePlantilla Composable

## Notes

- Plantillas are stored in the `plantillas` table in the backend
- Character limit of 50 characters for plantilla codes
- All operations include proper error handling and user feedback
- The module follows the same patterns as other setup modules
- Export functionality included with simple, clean design
- Delete functionality is included
- Statistics show 4 different metrics for comprehensive insights
- Large modal design accommodates complex form requirements
- Dynamic form sections allow flexible requirement management
- Real-time code checking prevents duplicate codes
- Complex data relationships are properly managed
- Backend controller is `PlantillasController.php`
- Supports both vacant and occupied position tracking
- Comprehensive requirement management (education, experience, eligibility, training)
- Publication date range for position announcements
- Unit and status information for detailed position management
- Responsive design optimized for large forms
- Scrollable modal content for better user experience
- Dynamic department filtering based on actual data
- Proper data type conversion and validation
- Complex form submission with multiple related data arrays
- Audit trail integration for all operations
