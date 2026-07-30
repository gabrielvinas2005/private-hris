import { ref, reactive, computed } from 'vue'
import ApiService from '../Services/api'

export function useMonetizationSetup() {
    const monetizationSetup = ref({})
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref({
        cf_rate: 0,
        maximum_number_allowed: 0
    })

    async function fetchMonetizationSetup() {
        loading.value = true
        apiError.value = false
        try {
            const res = await ApiService.getMonetizationSetups()
            console.log('API response:', res)
            const data = res?.data || []
            console.log('Monetization setup data:', data)

            if (data.length > 0) {
                monetizationSetup.value = {
                    id: data[0].id,
                    cf_rate: parseFloat(data[0].cf_rate) || 0,
                    maximum_number_allowed: parseInt(data[0].maximum_number_allowed) || 0
                }
            } else {
                // Default values when no setup exists
                monetizationSetup.value = {
                    id: 0,
                    cf_rate: 0.0481927, // Default CF rate from controller
                    maximum_number_allowed: 0
                }
            }

            console.log('Processed monetization setup:', monetizationSetup.value)
        } catch (error) {
            console.error('Error fetching monetization setup:', error)
            console.error('Error details:', {
                status: error.response?.status,
                statusText: error.response?.statusText,
                data: error.response?.data,
                message: error.message
            })

            // Log the full response for debugging
            if (error.response?.data) {
                console.error('Server response:', JSON.stringify(error.response.data, null, 2))
            }
            apiError.value = true

            // Show default data for demonstration when API fails
            monetizationSetup.value = {
                id: 0,
                cf_rate: 0.0481927,
                maximum_number_allowed: 0
            }

            // Show user-friendly error message
            if (error.response?.status === 500) {
                console.warn('Server error - showing default monetization setup. Please check database connection.')
                console.warn('Possible causes: 1) monetization_setups table does not exist, 2) database connection issue, 3) missing migrations')
            }
        } finally {
            loading.value = false
        }
    }

    function openForm() {
        formVisible.value = true
        formLoading.value = true

        // Initialize form data with current setup
        formData.value = {
            cf_rate: monetizationSetup.value.cf_rate || 0,
            maximum_number_allowed: monetizationSetup.value.maximum_number_allowed || 0
        }

        formLoading.value = false
    }

    async function saveMonetizationSetup() {
        saving.value = true
        try {
            // Validate data before saving
            if (formData.value.cf_rate < 0) {
                throw new Error('CF Rate must be a positive number')
            }

            if (formData.value.maximum_number_allowed < 0) {
                throw new Error('Maximum Number Allowed must be a positive number')
            }

            const payload = {
                cf_rate: parseFloat(formData.value.cf_rate) || 0,
                maximum_number_allowed: parseInt(formData.value.maximum_number_allowed) || 0
            }

            console.log('Saving monetization setup with payload:', payload)
            const response = await ApiService.saveMonetizationSetup(monetizationSetup.value.id, payload)
            console.log('Save response:', response)

            if (response.success) {
                await fetchMonetizationSetup()
                formVisible.value = false
                console.log('Monetization setup saved successfully')
            } else {
                throw new Error(response.message || 'Failed to save monetization setup')
            }
        } catch (error) {
            console.error('Error saving monetization setup:', error)
            // You can add user notification here if needed
            throw error
        } finally {
            saving.value = false
        }
    }

    const tableColumns = [
        { key: 'cf_rate', label: 'CF Rate', minWidth: 200 },
        { key: 'maximum_number_allowed', label: 'Maximum Number Allowed', minWidth: 250 }
    ]

    return {
        monetizationSetup,
        loading,
        saving,
        apiError,
        fetchMonetizationSetup,
        // form
        formVisible,
        formLoading,
        formData,
        openForm,
        saveMonetizationSetup,
        tableColumns
    }
}
