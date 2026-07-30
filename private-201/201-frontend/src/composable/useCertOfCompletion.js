import { ref } from 'vue'
import { certOfCompletionApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useCertOfCompletion() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const students = ref([])

    const fetchActiveStudents = async () => {
        loading.value = true
        try {
            const res = await certOfCompletionApi.getActiveStudents()
            students.value = res.data?.data || []
            return students.value
        } catch (e) {
            const errorMessage = e?.response?.data?.message || e?.response?.data?.error || 'Failed to fetch active students'
            ElMessage.error(errorMessage)
            throw e
        } finally {
            loading.value = false
        }
    }

    const generateCertificate = async (form) => {
        generateLoading.value = true
        try {
            const res = await certOfCompletionApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating certificate of completion')
                } else {
                    ElMessage.error('Failed to generate certificate of completion')
                }
            } catch {
                ElMessage.error('Failed to generate certificate of completion')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const downloadWord = async (form) => {
        generateLoading.value = true
        try {
            const res = await certOfCompletionApi.generateWord(form)
            const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
            return blob
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating certificate of completion Word')
                } else {
                    ElMessage.error('Failed to generate certificate of completion Word')
                }
            } catch {
                ElMessage.error('Failed to generate certificate of completion Word')
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

    return {
        loading,
        generateLoading,
        students,
        fetchActiveStudents,
        generateCertificate,
        downloadWord,
        downloadPDFFromBlob
    }
}

