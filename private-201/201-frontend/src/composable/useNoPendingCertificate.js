import { ref } from 'vue'
import { noPendingCertificateApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useNoPendingCertificate() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])

    const fetchEmployees = async () => {
        loading.value = true
        try {
            const res = await noPendingCertificateApi.getEmployees()
            // may be paginated
            employees.value = res.data?.data?.data ?? res.data?.data ?? []
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load employees for no pending certificate')
            employees.value = []
        } finally {
            loading.value = false
        }
    }

    const generateNoPendingPdf = async (form, { manageLoading = true } = {}) => {
        if (manageLoading) {
            generateLoading.value = true
        }
        try {
            const res = await noPendingCertificateApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating no pending certificate')
                } else {
                    ElMessage.error('Failed to generate no pending certificate')
                }
            } catch {
                ElMessage.error('Failed to generate no pending certificate')
            }
            throw e
        } finally {
            if (manageLoading) {
                generateLoading.value = false
            }
        }
    }

    const downloadWord = async (form) => {
        generateLoading.value = true
        try {
            const res = await noPendingCertificateApi.generateWord(form)
            const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
            return blob
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating no pending certificate Word')
                } else {
                    ElMessage.error('Failed to generate no pending certificate Word')
                }
            } catch {
                ElMessage.error('Failed to generate no pending certificate Word')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const downloadExcel = async (form) => {
        generateLoading.value = true
        try {
            const res = await noPendingCertificateApi.generateExcel(form)
            const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
            return blob
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating no pending certificate Excel')
                } else {
                    ElMessage.error('Failed to generate no pending certificate Excel')
                }
            } catch {
                ElMessage.error('Failed to generate no pending certificate Excel')
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

    return { loading, generateLoading, employees, fetchEmployees, generateNoPendingPdf, downloadWord, downloadExcel, downloadPDFFromBlob }
}


