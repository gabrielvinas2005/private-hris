# Employee 201 File System

## Overview

The Employee 201 File system is a comprehensive Vue.js application that displays and manages employee profile information. It provides a modular, component-based architecture for viewing and updating employee records.

## Structure

### Main View
- `src/views/201-file/index.vue` - Main container component that orchestrates all 201 file functionality

### Components
Located in `src/components/201/`:

1. **EmployeeProfileCard.vue** - Displays employee basic information and photo
2. **WorkInformation.vue** - Shows work details, payroll information, income and loans
3. **FamilyInformation.vue** - Displays family details and children information
4. **EducationInformation.vue** - Shows educational background
5. **ServiceRecord.vue** - Displays service history
6. **WorkExperience.vue** - Shows employment history
7. **EligibilityInformation.vue** - Displays eligibility records
8. **TrainingInformation.vue** - Shows training and seminar records
9. **VoluntaryWork.vue** - Displays voluntary work details
10. **IPCRResults.vue** - Shows IPCR results with view functionality
11. **OtherInformation.vue** - Displays recognitions, skills, memberships, and references

## Features

### Tabbed Interface
The main view uses a tabbed interface to organize different sections of employee information:
- Work Information
- Family
- Education
- Service Record
- Work Experience
- Eligibility
- Training
- Voluntary Work
- IPCR Result
- Other

### Responsive Design
All components are built with Tailwind CSS for responsive design and modern UI/UX.

### Data Management
- Uses Vue.js reactive data management
- Integrates with REST API endpoints
- Handles loading states and error scenarios
- Supports real-time updates

### Update Functionality
- Modal-based update requests
- Approval workflow integration
- Form validation and error handling

## API Integration

The system expects the following API endpoints:

### GET /api/employee/201-file
Returns complete employee 201 file data including:
- Employee basic information
- Work and payroll information
- Family details
- Education records
- Service records
- Work experience
- Eligibility information
- Training records
- Voluntary work
- IPCR results
- Other information (recognitions, skills, memberships, references)

### POST /api/employee/201-file/update-request
Creates a new update request for 201 file changes.

## Routing

The 201 file system is accessible at `/201-file` and integrates with the main application routing.

## Dependencies

- Vue.js 3.x
- Vue Router 4.x
- Axios for HTTP requests
- Vue Toastification for notifications
- Tailwind CSS for styling

## Usage

1. Navigate to the 201 file page via the profile records section
2. View different sections using the tabbed interface
3. Use the "Update 201 File" button to submit update requests
4. Download PDS using the "Download PDS" button

## Component Communication

Components communicate through props and events:
- Parent components pass data via props
- Child components emit events for user interactions
- Modal components handle update workflows

## Error Handling

- Network errors are handled gracefully
- Loading states are managed appropriately
- User feedback is provided through toast notifications
- Authentication errors redirect to login

## Future Enhancements

- Real-time data synchronization
- Advanced filtering and search
- Export functionality
- Bulk update capabilities
- Audit trail integration 