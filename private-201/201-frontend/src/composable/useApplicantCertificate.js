import { ref } from 'vue'
import { applicantCertificateApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useApplicantCertificate() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])

    const fetchEmployees = async () => {
        loading.value = true
        try {
            const res = await applicantCertificateApi.getEmployees()
            employees.value = res.data?.data ?? []
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load applicants')
            employees.value = []
        } finally { loading.value = false }
    }

    const generateApplicantPdf = async (form) => {
        generateLoading.value = true
        try {
            const res = await applicantCertificateApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating applicant certificate')
                } else { ElMessage.error('Failed to generate applicant certificate') }
            } catch { ElMessage.error('Failed to generate applicant certificate') }
            throw e
        } finally { generateLoading.value = false }
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

    const downloadWord = async (form) => {
        generateLoading.value = true
        try {
            const res = await applicantCertificateApi.generateWord(form)
            return res.data
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating applicant certificate Word')
                } else {
                    ElMessage.error('Failed to generate applicant certificate Word')
                }
            } catch {
                ElMessage.error('Failed to generate applicant certificate Word')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const sendEmail = async (form) => {
        generateLoading.value = true
        try {
            const res = await applicantCertificateApi.sendEmail(form)
            ElMessage.success(res.data?.message || 'Appointment certificate email sent successfully')
            return res.data?.data ?? null
        } catch (e) {
            ElMessage.error(e?.response?.data?.message || 'Failed to send appointment certificate email')
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    return { loading, generateLoading, employees, fetchEmployees, generateApplicantPdf, downloadPDFFromBlob, downloadWord, sendEmail }
}


