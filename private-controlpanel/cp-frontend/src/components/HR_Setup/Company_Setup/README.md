# Company Setup Module

This module provides functionality for managing company information in the HR Setup section.

## Components

### CompanyForm.vue
A comprehensive form component for adding/editing company information including:
- Company name, address, email
- Telephone and mobile numbers
- Company logo upload with preview
- Form validation and error handling

### CompanyInfo.vue
A display component for viewing company information with:
- Company logo display
- Contact information with clickable links
- Edit/Add action buttons
- Responsive design

## Usage

### In Views
```vue
<template>
  <CompanySetup />
</template>

<script setup>
import CompanySetup from '@/Views/HR_Setup/Company_Setup/CompanySetup.vue'
</script>
```

### Using the Composable
```vue
<script setup>
import { useCompany } from '@/composables/useCompany.js'

const { 
  companies, 
  primaryCompany, 
  hasCompany, 
  loading, 
  saving,
  fetchCompanies,
  saveCompany,
  validateCompanyForm,
  getLogoUrl 
} = useCompany()

// Fetch company data
await fetchCompanies()

// Save company data
const result = await saveCompany(formData)
</script>
```

## API Integration

The module integrates with the backend CompanyController:
- `GET /companies` - Fetch company information
- `POST /companies` - Save/update company information

## Features

- ✅ Company information management
- ✅ Logo upload with preview
- ✅ Form validation
- ✅ Responsive design
- ✅ Loading states
- ✅ Error handling
- ✅ Success notifications
- ✅ Modular component architecture

## File Structure

```
src/
├── components/HR_Setup/Company_Setup/
│   ├── CompanyForm.vue      # Form component
│   ├── CompanyInfo.vue      # Display component
│   └── README.md           # This file
├── Views/HR_Setup/Company_Setup/
│   └── CompanySetup.vue    # Main view
└── composables/
    └── useCompany.js       # Company composable
```
