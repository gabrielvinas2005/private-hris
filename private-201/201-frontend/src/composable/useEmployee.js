import { ref, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { employeeApi } from '@/services/api'

export function useEmployee() {
    const loading = ref(false)
    const employees = ref([])
    const employee = ref(null)
    const formData = ref(null)
    const relatedData = ref(null)

    // Employee list management
    const fetchEmployees = async () => {
        try {
            loading.value = true
            const response = await employeeApi.getEmployees()
            employees.value = response.data.data || []
            return response.data
        } catch (error) {
            console.error('Error fetching employees:', error)
            ElMessage.error('Failed to fetch employees')
            throw error
        } finally {
            loading.value = false
        }
    }

    // Get single employee with form data
    const fetchEmployee = async (id) => {
        try {
            loading.value = true
            const response = await employeeApi.getEmployee(id)
            const data = response.data.data

            employee.value = data.employee_info[0] || null
            formData.value = data.form_data || null
            relatedData.value = data.related_data || null

            return data
        } catch (error) {
            console.error('Error fetching employee:', error)
            ElMessage.error('Failed to fetch employee data')
            throw error
        } finally {
            loading.value = false
        }
    }

    // Create new employee
    const createEmployee = async (employeeData) => {
        try {
            loading.value = true
            const response = await employeeApi.createEmployee(employeeData)
            ElMessage.success(response.data.message || 'Employee created successfully')
            return response.data
        } catch (error) {
            console.error('Error creating employee:', error)
            const errorMessage = error.response?.data?.message || 'Failed to create employee'
            ElMessage.error(errorMessage)
            throw error
        } finally {
            loading.value = false
        }
    }

    // Update employee
    const updateEmployee = async (id, employeeData) => {
        try {
            loading.value = true
            const response = await employeeApi.updateEmployee(id, employeeData)
            ElMessage.success(response.data.message || 'Employee updated successfully')
            return response.data
        } catch (error) {
            console.error('Error updating employee:', error)
            const errorMessage = error.response?.data?.message || 'Failed to update employee'
            ElMessage.error(errorMessage)
            throw error
        } finally {
            loading.value = false
        }
    }

    // Delete employee data (specific type)
    const deleteEmployeeData = async (typeId, id) => {
        try {
            await ElMessageBox.confirm(
                'This action cannot be undone. Are you sure you want to delete this data?',
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            )

            loading.value = true
            const response = await employeeApi.deleteEmployeeData(typeId, id)
            ElMessage.success('Data deleted successfully')
            return response.data
        } catch (error) {
            if (error !== 'cancel') {
                console.error('Error deleting employee data:', error)
                ElMessage.error('Failed to delete data')
                throw error
            }
        } finally {
            loading.value = false
        }
    }

    // Download document
    const downloadDocument = async (id) => {
        try {
            loading.value = true
            const response = await employeeApi.downloadDocument(id)
            const { file_content, filename, content_type } = response.data.data

            // Convert base64 to blob and download
            const byteCharacters = atob(file_content)
            const byteNumbers = new Array(byteCharacters.length)
            for (let i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i)
            }
            const byteArray = new Uint8Array(byteNumbers)
            const blob = new Blob([byteArray], { type: content_type })

            const url = window.URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = filename
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(url)

            ElMessage.success('Document downloaded successfully')
        } catch (error) {
            console.error('Error downloading document:', error)
            ElMessage.error('Failed to download document')
            throw error
        } finally {
            loading.value = false
        }
    }

    // Search and filter employees
    const searchEmployees = (query, employeesList) => {
        if (!query) return employeesList

        const searchTerm = query.toLowerCase()
        return employeesList.filter(emp =>
            emp.name?.toLowerCase().includes(searchTerm) ||
            emp.employee_no?.toLowerCase().includes(searchTerm) ||
            emp.position?.toLowerCase().includes(searchTerm) ||
            emp.department?.toLowerCase().includes(searchTerm) ||
            emp.email?.toLowerCase().includes(searchTerm)
        )
    }

    // Form validation helpers
    const validateEmployeeForm = (formData) => {
        const errors = []

        if (!formData.employee_no) errors.push('Employee number is required')
        if (!formData.email) errors.push('Email is required')
        if (!formData.first_name) errors.push('First name is required')
        if (!formData.last_name) errors.push('Last name is required')
        if (!formData.birthdate) errors.push('Birthdate is required')
        if (!formData.gender_id) errors.push('Gender is required')
        if (!formData.department_id) errors.push('Department is required')
        if (!formData.position_id) errors.push('Position is required')
        if (!formData.employment_type_id) errors.push('Employment type is required')

        return errors
    }

    // Reset form data
    const resetEmployeeData = () => {
        employee.value = null
        formData.value = null
        relatedData.value = null
    }

    return {
        // State
        loading,
        employees,
        employee,
        formData,
        relatedData,

        // Methods
        fetchEmployees,
        fetchEmployee,
        createEmployee,
        updateEmployee,
        deleteEmployeeData,
        downloadDocument,
        searchEmployees,
        validateEmployeeForm,
        resetEmployeeData
    }
}
