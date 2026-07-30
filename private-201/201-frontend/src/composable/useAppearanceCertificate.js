import { ref } from 'vue'
import { appearanceCertificateApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useAppearanceCertificate() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])

    const fetchEmployees = async () => {
        loading.value = true
        try {
            const res = await appearanceCertificateApi.getEmployees()
            employees.value = res.data?.data ?? []
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load employees for appearance certificate')
            employees.value = []
        } finally {
            loading.value = false
        }
    }

    const generateAppearancePdf = async (form) => {
        generateLoading.value = true
        try {
            const res = await appearanceCertificateApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating appearance certificate')
                } else {
                    ElMessage.error('Failed to generate appearance certificate')
                }
            } catch {
                ElMessage.error('Failed to generate appearance certificate')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const downloadWord = async (form) => {
        generateLoading.value = true
        try {
            const res = await appearanceCertificateApi.generateWord(form)
            const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
            return blob
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating appearance certificate Word')
                } else {
                    ElMessage.error('Failed to generate appearance certificate Word')
                }
            } catch {
                ElMessage.error('Failed to generate appearance certificate Word')
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

    return { loading, generateLoading, employees, fetchEmployees, generateAppearancePdf, downloadWord, downloadPDFFromBlob }
}


