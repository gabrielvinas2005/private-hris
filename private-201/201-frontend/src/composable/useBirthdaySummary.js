import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { employeeApi, birthdaySummaryApi } from '@/services/api'

export function useBirthdaySummary() {
    const loading = ref(false)
    const employees = ref([])
    const generateLoading = ref(false)

    // Fetch all employees for birthday summary
    const fetchEmployees = async () => {
        try {
            loading.value = true
            const res = await employeeApi.getEmployees()
            employees.value = res.data?.data || res.data || []
            
            // Process employees to add computed fields
            employees.value = employees.value.map(emp => {
                const fullName = `${emp.first_name || ''} ${emp.middle_name || ''} ${emp.last_name || ''}`.trim()
                return {
                    ...emp,
                    name: fullName,
                    birthdate: emp.birthdate || null,
                    // Calculate age if birthdate exists
                    age: emp.birthdate ? calculateAge(emp.birthdate) : null,
                    // Get month and day for sorting/filtering
                    birthMonth: emp.birthdate ? new Date(emp.birthdate).getMonth() + 1 : null,
                    birthDay: emp.birthdate ? new Date(emp.birthdate).getDate() : null,
                    // Get next birthday date
                    nextBirthday: emp.birthdate ? getNextBirthday(emp.birthdate) : null,
                    // Days until next birthday
                    daysUntilBirthday: emp.birthdate ? getDaysUntilBirthday(emp.birthdate) : null
                }
            })
            
            return employees.value
        } catch (e) {
            ElMessage.error('Failed to load employees: ' + (e.response?.data?.message || e.message))
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    // Calculate age from birthdate
    const calculateAge = (birthdate) => {
        if (!birthdate) return null
        const today = new Date()
        const birth = new Date(birthdate)
        let age = today.getFullYear() - birth.getFullYear()
        const monthDiff = today.getMonth() - birth.getMonth()
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--
        }
        return age
    }

    // Get next birthday date
    const getNextBirthday = (birthdate) => {
        if (!birthdate) return null
        const today = new Date()
        const birth = new Date(birthdate)
        const currentYear = today.getFullYear()
        
        // Create birthday for this year
        const thisYearBirthday = new Date(currentYear, birth.getMonth(), birth.getDate())
        
        // If birthday already passed this year, use next year
        if (thisYearBirthday < today) {
            return new Date(currentYear + 1, birth.getMonth(), birth.getDate())
        }
        
        return thisYearBirthday
    }

    // Get days until next birthday
    const getDaysUntilBirthday = (birthdate) => {
        if (!birthdate) return null
        const nextBirthday = getNextBirthday(birthdate)
        if (!nextBirthday) return null
        
        const today = new Date()
        today.setHours(0, 0, 0, 0)
        nextBirthday.setHours(0, 0, 0, 0)
        
        const diffTime = nextBirthday - today
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
        
        return diffDays
    }

    // Generate PDF report for preview
    const generatePDFPreview = async (month = null) => {
        try {
            generateLoading.value = true
            const response = await birthdaySummaryApi.generatePDF(month)

            // Create blob URL for preview
            const blob = new Blob([response.data], { type: 'application/pdf' })
            const pdfUrl = window.URL.createObjectURL(blob)

            const filename = month 
                ? `birthday_summary_${new Date(2000, month - 1, 1).toLocaleString('default', { month: 'long' }).toLowerCase()}_${new Date().toISOString().split('T')[0]}.pdf`
                : `birthday_summary_${new Date().toISOString().split('T')[0]}.pdf`

            return {
                pdfUrl,
                blob,
                filename
            }
        } catch (e) {
            ElMessage.error('Failed to generate PDF: ' + (e.response?.data?.message || e.message))
            console.error('Generate PDF error:', e)
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    // Generate PDF report and download
    const generatePDF = async (month = null) => {
        try {
            generateLoading.value = true
            const response = await birthdaySummaryApi.generatePDF(month)

            // Create blob and download
            const blob = new Blob([response.data], { type: 'application/pdf' })
            const url = window.URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            
            const filename = month 
                ? `birthday_summary_${new Date(2000, month - 1, 1).toLocaleString('default', { month: 'long' }).toLowerCase()}_${new Date().toISOString().split('T')[0]}.pdf`
                : `birthday_summary_${new Date().toISOString().split('T')[0]}.pdf`
            
            link.download = filename
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(url)

            ElMessage.success('Birthday summary PDF generated successfully')
        } catch (e) {
            ElMessage.error('Failed to generate PDF: ' + (e.response?.data?.message || e.message))
            console.error('Generate PDF error:', e)
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    // Download PDF from blob
    const downloadPDFFromBlob = (blob, filename) => {
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = filename
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
        ElMessage.success('Birthday summary PDF downloaded successfully')
    }

    return {
        loading,
        employees,
        generateLoading,
        fetchEmployees,
        generatePDFPreview,
        generatePDF,
        downloadPDFFromBlob
    }
}
