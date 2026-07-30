import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import apiService from '../Services/api.js'

const companies = ref([])
const currentCompany = ref(null)
const loading = ref(false)
const saving = ref(false)

const hasCompany = computed(() => companies.value.length > 0)
const primaryCompany = computed(() => companies.value[0] || null)

export function useCompany() {
    async function fetchCompanies() {
        loading.value = true
        try {
            const data = await apiService.get('/companies')

            if (data.success) {
                companies.value = data.data || []
                currentCompany.value = companies.value[0] || null
                return { success: true, data: data.data }
            } else {
                ElMessage.error('Failed to fetch company data')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error fetching companies:', error)
            ElMessage.error('Error fetching company data')
            return { success: false, message: error.message }
        } finally {
            loading.value = false
        }
    }

    async function saveCompany(companyData, companyId = null) {
        saving.value = true
        try {
            // Create FormData for file upload
            const formData = new FormData()

            // Add company ID if updating
            if (companyId) {
                formData.append('id', companyId)
            }

            // Add all form fields
            Object.keys(companyData).forEach(key => {
                // Skip id, logo, and empty values
                if (key === 'id') {
                    // ID is handled separately above
                    return
                }
                
                if (key === 'logo') {
                    // Only append logo if it's a File object (new upload)
                    if (companyData[key] instanceof File) {
                        formData.append(key, companyData[key])
                    }
                    // If logo is not a File, don't include it (backend will keep existing logo)
                } else {
                    // Append other fields if they have values
                    const value = companyData[key]
                    if (value !== null && value !== undefined && value !== '') {
                        formData.append(key, value)
                    }
                }
            })

            // API service will handle FormData correctly (no need to set Content-Type manually)
            const data = await apiService.post('/companies', formData)

            if (data.success) {
                ElMessage.success('Company information updated successfully!')
                await fetchCompanies() // Refresh the data
                return { success: true, data: data.data }
            } else {
                ElMessage.error(data.message || 'Failed to update company information')
                return { success: false, message: data.message }
            }
        } catch (error) {
            console.error('Error saving company:', error)
            ElMessage.error('Error updating company information')
            return { success: false, message: error.message }
        } finally {
            saving.value = false
        }
    }

    // Form validation
    function validateCompanyForm(formData) {
        const errors = {}

        if (!formData.name || formData.name.trim() === '') {
            errors.name = 'Company name is required'
        }

        if (!formData.address || formData.address.trim() === '') {
            errors.address = 'Company address is required'
        }

        if (!formData.email || formData.email.trim() === '') {
            errors.email = 'Company email is required'
        } else if (!isValidEmail(formData.email)) {
            errors.email = 'Please enter a valid email address'
        }

        if (formData.logo && formData.logo.size > 2 * 1024 * 1024) {
            errors.logo = 'Logo file size must be less than 2MB'
        }

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        }
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        return emailRegex.test(email)
    }

    function getLogoUrl(company) {
        const logo = company?.logo
        if (!logo || typeof logo !== 'string') {
            return null
        }

        const trimmed = logo.trim()
        if (!trimmed) {
            return null
        }

        if (trimmed.startsWith('data:image')) {
            return trimmed
        }

        let mime = 'image/jpeg'
        if (trimmed.startsWith('iVBOR')) {
            mime = 'image/png'
        } else if (trimmed.startsWith('R0lGOD')) {
            mime = 'image/gif'
        } else if (trimmed.startsWith('UklGR')) {
            mime = 'image/webp'
        }

        return `data:${mime};base64,${trimmed}`
    }

    function setFavicon(href) {
        let link = document.querySelector('link[rel="icon"]')
        if (!link) {
            link = document.createElement('link')
            link.rel = 'icon'
            document.head.appendChild(link)
        }
        link.href = href
    }

    async function applyCompanyBranding() {
        const result = await fetchCompanies()
        const company = result?.data?.[0] || primaryCompany.value
        const name = company?.name?.trim()

        document.title = name ? `${name} - Control Panel` : 'Control Panel'

        const logo = getLogoUrl(company)
        if (logo) {
            setFavicon(logo)
        }

        return company
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes'
        const k = 1024
        const sizes = ['Bytes', 'KB', 'MB', 'GB']
        const i = Math.floor(Math.log(bytes) / Math.log(k))
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }

    return {
        // State
        companies,
        currentCompany,
        loading,
        saving,

        // Computed
        hasCompany,
        primaryCompany,

        // Methods
        fetchCompanies,
        saveCompany,
        validateCompanyForm,
        getLogoUrl,
        applyCompanyBranding,
        formatFileSize
    }
}
