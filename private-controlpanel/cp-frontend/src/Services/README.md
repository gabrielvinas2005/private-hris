# API Services Documentation

This directory contains the API services for the PTTC Control Panel frontend application.

## Files

### `api.js`
Main API service class that handles all HTTP requests to the backend.

### `auth.js`
Authentication service (currently disabled - will be added later).

## Usage

### Basic API Service

```javascript
import apiService from '@/Services/api.js'

// Get users
const users = await apiService.getUsers()

// Add new users
const result = await apiService.addUsers({
  employee_no: ['EMP001', 'EMP002'],
  select: ['EMP001', 'EMP002']
})

// Note: Update and delete methods are not available yet
// const updated = await apiService.updateUser(userId, userData)
// const deleted = await apiService.deleteUser(userId)
```

### Authentication Service (Disabled for now)

```javascript
// Authentication will be added later
// import authService from '@/Services/auth.js'
```

### Using Composables

```javascript
import { useUsers } from '@/composables/useUsers.js'

export default {
  setup() {
    const {
      users,
      loading,
      fetchUsers,
      addUsers,
      deleteUser
    } = useUsers()

    return {
      users,
      loading,
      fetchUsers,
      addUsers,
      deleteUser
    }
  }
}
```

## Configuration

### Environment Variables

Create a `.env` file in the root directory:

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_APP_NAME=PTTC Control Panel
VITE_APP_VERSION=1.0.0
VITE_DEBUG=true
```

### API Endpoints

The service is configured to work with the following backend endpoints:

- **Authentication**: (disabled for now)
- **Users**: `/users` (GET, POST) - Update/delete routes not available yet
- **HR Setup**: `/hr-setup/*`
- **Time Keeping**: `/timekeeping-setup/*`
- **Payroll**: `/payroll-setup/*`

## Error Handling

All API methods include proper error handling and will show user-friendly error messages using Element Plus notifications.

## Authentication

Authentication is currently disabled. Will be added later when needed.

## Examples

### User Management

```javascript
// Fetch all users
const { users, loading } = useUsers()
await fetchUsers()

// Add new users
const formData = {
  employee_no: ['EMP001', 'EMP002'],
  select: ['EMP001', 'EMP002']
}
await addUsers(formData)

// Note: Delete functionality not available yet
// await deleteUser(userId)
```

### Authentication Flow

```javascript
// Login
const result = await authService.login(credentials)
if (result.success) {
  if (result.requiresOtp) {
    // Redirect to OTP verification
    router.push('/verify-otp')
  } else {
    // Redirect to dashboard
    router.push(result.next)
  }
}

// Check authentication status
if (authService.isAuthenticated()) {
  // User is logged in
  const user = authService.getUser()
}
```
