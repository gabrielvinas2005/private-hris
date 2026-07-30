import { ref } from 'vue'
import { acceptanceOfResignationApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useAcceptanceOfResignation() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])

    const fetchEmployees = async () => {
        loading.value = true
        try {
            const res = await acceptanceOfResignationApi.getEmployees()
            // API is paginated; extract data if present
            employees.value = res.data?.data?.data ?? res.data?.data ?? []
        } catch (e) {
            console.error(e)
            ElMessage.error('Theres no employees for acceptance of resignation')
            employees.value = []
        } finally {
            loading.value = false
        }
    }

    const generateAcceptancePdf = async (form) => {
        generateLoading.value = true
        try {
            const res = await acceptanceOfResignationApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating acceptance of resignation')
                } else {
                    ElMessage.error('Failed to generate acceptance of resignation')
                }
            } catch {
                ElMessage.error('Failed to generate acceptance of resignation')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const generateAcceptanceWord = async (form) => {
        generateLoading.value = true
        try {
            const res = await acceptanceOfResignationApi.generateWord(form)
            const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
            return blob
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating acceptance of resignation Word document')
                } else {
                    ElMessage.error('Failed to generate acceptance of resignation Word document')
                }
            } catch {
                ElMessage.error('Failed to generate acceptance of resignation Word document')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const previewAcceptanceWord = async (form) => {
        generateLoading.value = true
        try {
            const res = await acceptanceOfResignationApi.previewWord(form)
            const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating acceptance of resignation Word preview')
                } else {
                    ElMessage.error('Failed to generate acceptance of resignation Word preview')
                }
            } catch {
                ElMessage.error('Failed to generate acceptance of resignation Word preview')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const downloadPDFFromBlob = (blob, filename) => {
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = filename
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        URL.revokeObjectURL(url)
    }

    const downloadWordFromBlob = (blob, filename) => {
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = filename
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        URL.revokeObjectURL(url)
    }

    return {
        loading,
        generateLoading,
        employees,
        fetchEmployees,
        generateAcceptancePdf,
        generateAcceptanceWord,
        previewAcceptanceWord,
        downloadPDFFromBlob,
        downloadWordFromBlob
    }
}


