import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { positionDescriptionApi } from '../services/api'

export function usePositionDescription() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const error = ref(null)

    const positions = ref([])
    const pdfRecords = ref([])
    const competencyLevels = ref([])
    const employees = ref([])
    const salaryGrades = ref([])
    const selectedPdfRecord = ref(null)

    const formData = ref({
        position: '',
        position_code: '',
        salary_grade: '',
        department: '',
        division: '',
        description: '',
        unit_description: '',
        education: '',
        experience: '',
        training: '',
        eligibility: '',
        competencies: '',
        signatory_name: '',
        signatory_position: '',
        report_date: '',
        report_type: 'standard'
    })

    // PDF Record form data
    const pdfFormData = ref({
        Employee_no: '',
        position_title: '',
        position_id: null,
        item_number: '',
        salarygrade_id: null,
        supervised_position_title: '',
        supervised_positionTitle_ID: null,
        supervised_item_number: '',
        equiptment: '',
        stakeholders: '',
        working_Condition: '',
        unit_description: '',
        position_description: '',
        education: '',
        experience: '',
        training: '',
        eigibility: '',
        supervisor: '',
        employee_date: '',
        supervisor_date: '',
        immediate_supervisor_position_id: null,
        next_higher_supervisor_position_id: null
    })

    // SODAR form data
    const sodarFormData = ref({
        PDF_id: null,
        Percetage: '',
        Responsibilities: '',
        Competencylevel_id: null
    })

    // Core Competency form data
    const coreCompetencyFormData = ref({
        PDF_id: null,
        Competency: '',
        CompetencyLevel_id: null
    })

    // Leadership Competency form data
    const leadershipCompetencyFormData = ref({
        PDF_id: null,
        COmpetency: '',
        CompetencyLevel_id: null
    })

    // Work Experience form data
    const workExperienceFormData = ref({
        Reference_id: '',
        Position: '',
        Work_start_date: '',
        Work_end_date: '',
        Duration: '',
        Office_name: '',
        Office_Address: '',
        Immediate_supervisor: '',
        List_Of_Accomplishment: '',
        Summary_of_Duties: ''
    })

    const rules = {
        position: [{ required: true, message: 'Please select position', trigger: 'change' }],
        description: [{ required: true, message: 'Position description is required', trigger: 'blur' }],
        signatory_name: [{ required: true, message: 'Signatory name is required', trigger: 'blur' }],
        signatory_position: [{ required: true, message: 'Signatory position is required', trigger: 'blur' }]
    }

    const fetchPositions = async () => {
        try {
            loading.value = true
            error.value = null
            console.log('Fetching positions...')
            const response = await positionDescriptionApi.getPositions()
            console.log('Positions response:', response)
            positions.value = response?.data?.data || response?.data || []
            console.log('Positions loaded:', positions.value.length)
        } catch (e) {
            error.value = e
            console.error('Error fetching positions:', e)
            ElMessage.error('Failed to load positions: ' + e.message)
        } finally {
            loading.value = false
        }
    }

    const generatePositionDescriptionPdf = async (formData) => {
        try {
            generateLoading.value = true
            error.value = null

            // Always request blob from API
            const response = await positionDescriptionApi.generatePDF(formData)
            return response?.data instanceof Blob ? response.data : new Blob([response?.data], { type: 'application/pdf' })
        } catch (e) {
            error.value = e
            console.error('Error generating PDF:', e)
            ElMessage.error('Failed to generate PDF')
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const downloadPDFFromBlob = (blob, filename) => {
        try {
            const url = URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = filename || `position_description_${new Date().toISOString().split('T')[0]}.pdf`
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            URL.revokeObjectURL(url)
        } catch (e) {
            console.error('Error downloading PDF:', e)
            ElMessage.error('Failed to download PDF')
        }
    }

    const downloadDocx = async (formData) => {
        try {
            generateLoading.value = true
            error.value = null

            const response = await positionDescriptionApi.downloadDocxV2(formData)
            // Return the response (axios with responseType: 'blob' returns blob in response.data)
            return response
        } catch (e) {
            error.value = e
            console.error('Error downloading DOCX:', e)
            ElMessage.error('Failed to download DOCX')
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const resetForm = () => {
        formData.value = {
            position: '',
            position_code: '',
            salary_grade: '',
            department: '',
            division: '',
            description: '',
            unit_description: '',
            education: '',
            experience: '',
            training: '',
            eligibility: '',
            competencies: '',
            signatory_name: '',
            signatory_position: '',
            report_date: '',
            report_type: 'standard'
        }
    }

    // PDF Records Management
    const fetchPdfRecords = async () => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.getPdfRecords()
            pdfRecords.value = response?.data?.data || response?.data || []
        } catch (e) {
            error.value = e
            console.error('Error fetching PDF records:', e)
            ElMessage.error('Failed to load PDF records: ' + e.message)
        } finally {
            loading.value = false
        }
    }

    const getPdfRecord = async (id) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.getPdfRecord(id)
            selectedPdfRecord.value = response?.data?.data || response?.data
            return selectedPdfRecord.value
        } catch (e) {
            error.value = e
            console.error('Error fetching PDF record:', e)
            ElMessage.error('Failed to load PDF record: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    const createPdfRecord = async (data) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.createPdfRecord(data)
            ElMessage.success('PDF record created successfully')
            await fetchPdfRecords() // Refresh the list
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error creating PDF record:', e)
            ElMessage.error('Failed to create PDF record: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    const updatePdfRecord = async (id, data) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.updatePdfRecord(id, data)
            ElMessage.success('PDF record updated successfully')
            await fetchPdfRecords() // Refresh the list
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error updating PDF record:', e)
            ElMessage.error('Failed to update PDF record: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    const deletePdfRecord = async (id) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.deletePdfRecord(id)
            ElMessage.success('PDF record deleted successfully')
            await fetchPdfRecords() // Refresh the list
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error deleting PDF record:', e)
            ElMessage.error('Failed to delete PDF record: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    // SODAR Management
    const addSodarRecord = async (data) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.addSodarRecord(data)
            ElMessage.success('SODAR record added successfully')
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error adding SODAR record:', e)
            ElMessage.error('Failed to add SODAR record: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteSodarRecord = async (id) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.deleteSodarRecord(id)
            ElMessage.success('SODAR record deleted successfully')
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error deleting SODAR record:', e)
            ElMessage.error('Failed to delete SODAR record: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    // Competencies Management
    const addCoreCompetency = async (data) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.addCoreCompetency(data)
            ElMessage.success('Core competency added successfully')
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error adding core competency:', e)
            ElMessage.error('Failed to add core competency: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteCoreCompetency = async (id) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.deleteCoreCompetency(id)
            ElMessage.success('Core competency deleted successfully')
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error deleting core competency:', e)
            ElMessage.error('Failed to delete core competency: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    const addLeadershipCompetency = async (data) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.addLeadershipCompetency(data)
            ElMessage.success('Leadership competency added successfully')
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error adding leadership competency:', e)
            ElMessage.error('Failed to add leadership competency: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteLeadershipCompetency = async (id) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.deleteLeadershipCompetency(id)
            ElMessage.success('Leadership competency deleted successfully')
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error deleting leadership competency:', e)
            ElMessage.error('Failed to delete leadership competency: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchCompetencyLevels = async () => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.getCompetencyLevels()
            competencyLevels.value = response?.data?.data || response?.data || []
        } catch (e) {
            error.value = e
            console.error('Error fetching competency levels:', e)
            ElMessage.error('Failed to load competency levels: ' + e.message)
        } finally {
            loading.value = false
        }
    }

    // Fetch Employees and Applicants
    const fetchEmployees = async () => {
        try {
            loading.value = true
            error.value = null
            const [employeeResponse, applicantResponse] = await Promise.all([
                positionDescriptionApi.getEmployees(),
                positionDescriptionApi.getApplicants()
            ])
            const employeeData = employeeResponse?.data?.data || employeeResponse?.data || []
            const applicantData = applicantResponse?.data?.data || applicantResponse?.data || []
            // Combine employees and applicants
            employees.value = [...employeeData, ...applicantData]
        } catch (e) {
            error.value = e
            console.error('Error fetching employees and applicants:', e)
            ElMessage.error('Failed to load employees and applicants: ' + e.message)
        } finally {
            loading.value = false
        }
    }

    // Fetch Salary Grades
    const fetchSalaryGrades = async () => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.getSalaryGrades()
            salaryGrades.value = response?.data?.data || response?.data || []
        } catch (e) {
            error.value = e
            console.error('Error fetching salary grades:', e)
            ElMessage.error('Failed to load salary grades: ' + e.message)
        } finally {
            loading.value = false
        }
    }

    // Work Experience Management
    const fetchWorkExperience = async (referenceId = null) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.getWorkExperience(referenceId)
            workExperience.value = response?.data?.data || response?.data || []
        } catch (e) {
            error.value = e
            console.error('Error fetching work experience:', e)
            ElMessage.error('Failed to load work experience: ' + e.message)
        } finally {
            loading.value = false
        }
    }

    const addWorkExperience = async (data) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.addWorkExperience(data)
            ElMessage.success('Work experience added successfully')
            await fetchWorkExperience() // Refresh the list
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error adding work experience:', e)
            ElMessage.error('Failed to add work experience: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    // Supervised Positions Management
    const addSupervisedPosition = async (data) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.addSupervisedPosition(data)
            ElMessage.success('Supervised position added successfully')
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error adding supervised position:', e)
            ElMessage.error('Failed to add supervised position: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteSupervisedPosition = async (id) => {
        try {
            loading.value = true
            error.value = null
            const response = await positionDescriptionApi.deleteSupervisedPosition(id)
            ElMessage.success('Supervised position deleted successfully')
            return response?.data
        } catch (e) {
            error.value = e
            console.error('Error deleting supervised position:', e)
            ElMessage.error('Failed to delete supervised position: ' + e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    // Reset functions for all forms
    const resetPdfForm = () => {
        pdfFormData.value = {
            Employee_no: '',
            position_title: '',
            position_id: null,
            item_number: '',
            salarygrade_id: null,
            supervised_position_title: '',
            supervised_positionTitle_ID: null,
            supervised_item_number: '',
            equiptment: '',
            stakeholders: '',
            working_Condition: '',
            unit_description: '',
            position_description: '',
            education: '',
            experience: '',
            training: '',
            eigibility: '',
            supervisor: '',
            employee_date: '',
            supervisor_date: '',
            immediate_supervisor_position_id: null,
            next_higher_supervisor_position_id: null
        }
    }

    const resetSodarForm = () => {
        sodarFormData.value = {
            PDF_id: null,
            Percetage: '',
            Responsibilities: '',
            Competencylevel_id: null
        }
    }

    const resetCoreCompetencyForm = () => {
        coreCompetencyFormData.value = {
            PDF_id: null,
            Competency: '',
            CompetencyLevel_id: null
        }
    }

    const resetLeadershipCompetencyForm = () => {
        leadershipCompetencyFormData.value = {
            PDF_id: null,
            COmpetency: '',
            CompetencyLevel_id: null
        }
    }

    const resetWorkExperienceForm = () => {
        workExperienceFormData.value = {
            Reference_id: '',
            Position: '',
            Work_start_date: '',
            Work_end_date: '',
            Duration: '',
            Office_name: '',
            Office_Address: '',
            Immediate_supervisor: '',
            List_Of_Accomplishment: '',
            Summary_of_Duties: ''
        }
    }

    return {
        loading,
        generateLoading,
        error,
        positions,
        pdfRecords,
        competencyLevels,
        employees,
        salaryGrades,
        selectedPdfRecord,
        formData,
        pdfFormData,
        sodarFormData,
        coreCompetencyFormData,
        leadershipCompetencyFormData,
        rules,
        fetchPositions,
        generatePositionDescriptionPdf,
        downloadPDFFromBlob,
        downloadDocx,
        resetForm,
        // PDF Records Management
        fetchPdfRecords,
        getPdfRecord,
        createPdfRecord,
        updatePdfRecord,
        deletePdfRecord,
        // SODAR Management
        addSodarRecord,
        deleteSodarRecord,
        // Competencies Management
        addCoreCompetency,
        deleteCoreCompetency,
        addLeadershipCompetency,
        deleteLeadershipCompetency,
        fetchCompetencyLevels,
        // Data fetching
        fetchEmployees,
        fetchSalaryGrades,
        // Reset functions
        resetPdfForm,
        resetSodarForm,
        resetCoreCompetencyForm,
        resetLeadershipCompetencyForm,
        // Supervised positions
        addSupervisedPosition,
        deleteSupervisedPosition
    }
}
