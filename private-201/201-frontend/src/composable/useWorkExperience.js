import { ref } from 'vue'
import { workExperienceApi } from '../services/api'

export function useWorkExperience() {
    const loading = ref(false)
    const error = ref(null)

    // Generate work experience sheet preview
    const generateWorkExperienceSheet = async (formData) => {
        try {
            console.log('🚀 Starting work experience sheet generation...')
            console.log('📋 Form data:', formData)

            loading.value = true
            error.value = null

            console.log('📡 Calling API...')
            const response = await workExperienceApi.generatePreview(formData)

            console.log('📥 API Response:', response)
            console.log('📥 Response status:', response.status)
            console.log('📥 Response data:', response.data)

            // Check if response is a PDF blob (successful response)
            if (response.status === 200 && response.data instanceof Blob && response.data.type === 'application/pdf') {
                console.log('✅ PDF blob received successfully')
                // Create blob URL for preview
                const url = URL.createObjectURL(response.data)

                console.log('🔗 Blob URL created:', url)

                return {
                    success: true,
                    preview_url: url,
                    data: response.data
                }
            } else {
                console.error('❌ API returned error:', response.data)
                throw new Error('Failed to generate work experience sheet - invalid response format')
            }
        } catch (err) {
            console.error('💥 Error in generateWorkExperienceSheet:', err)
            console.error('💥 Error message:', err.message)
            console.error('💥 Error response:', err.response)
            console.error('💥 Error status:', err.response?.status)
            console.error('💥 Error data:', err.response?.data)

            error.value = err.message
            return {
                success: false,
                message: err.message
            }
        } finally {
            loading.value = false
            console.log('🏁 Generation process completed')
        }
    }

    // Download work experience PDF
    const downloadWorkExperiencePDF = async (formData) => {
        try {
            loading.value = true
            error.value = null

            const response = await workExperienceApi.downloadPDF(formData)

            // Check if response is a PDF blob (successful response)
            if (response.status === 200 && response.data instanceof Blob && response.data.type === 'application/pdf') {
                // Create download link
                const url = URL.createObjectURL(response.data)
                const link = document.createElement('a')
                link.href = url
                link.download = `Work_Experience_Sheet_${formData.work_experience_id}.pdf`
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                URL.revokeObjectURL(url)

                return {
                    success: true,
                    message: 'PDF downloaded successfully'
                }
            } else {
                throw new Error('Failed to download work experience sheet - invalid response format')
            }
        } catch (err) {
            error.value = err.message
            return {
                success: false,
                message: err.message
            }
        } finally {
            loading.value = false
        }
    }

    // Download work experience Word (.doc via HTML)
    const downloadWorkExperienceWORD = async (formData) => {
        try {
            loading.value = true
            error.value = null

            const response = await workExperienceApi.downloadWord(formData)

            if (response.status === 200 && response.data instanceof Blob) {
                const url = URL.createObjectURL(response.data)
                const link = document.createElement('a')
                link.href = url
                link.download = `Work_Experience_Sheet_${formData.work_experience_id}.doc`
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                URL.revokeObjectURL(url)
                return { success: true }
            } else {
                throw new Error('Failed to download work experience sheet (Word)')
            }
        } catch (err) {
            error.value = err.message
            return { success: false, message: err.message }
        } finally {
            loading.value = false
        }
    }

    // Download work experience DOCX
    const downloadWorkExperienceDOCX = async (formData) => {
        try {
            loading.value = true
            error.value = null

            const response = await workExperienceApi.downloadDocx(formData)
            if (response.status === 200 && response.data instanceof Blob) {
                const url = URL.createObjectURL(response.data)
                const link = document.createElement('a')
                link.href = url
                link.download = `Work_Experience_Sheet_${formData.work_experience_id}.docx`
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                URL.revokeObjectURL(url)
                return { success: true }
            } else {
                throw new Error('Failed to download work experience sheet (DOCX)')
            }
        } catch (err) {
            error.value = err.message
            return { success: false, message: err.message }
        } finally {
            loading.value = false
        }
    }

    // Get work experience data
    const getWorkExperienceData = async (employeeId) => {
        try {
            loading.value = true
            error.value = null

            const response = await workExperienceApi.getData(employeeId)

            if (response.data.success) {
                return {
                    success: true,
                    data: response.data.data
                }
            } else {
                throw new Error(response.data.message || 'Failed to get work experience data')
            }
        } catch (err) {
            error.value = err.message
            return {
                success: false,
                message: err.message
            }
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        error,
        generateWorkExperienceSheet,
        downloadWorkExperiencePDF,
        getWorkExperienceData,
        downloadWorkExperienceWORD,
        downloadWorkExperienceDOCX
    }
}
