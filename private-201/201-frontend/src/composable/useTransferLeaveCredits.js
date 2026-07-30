import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { transferLeaveCreditsApi } from '../services/api'

const DEFAULT_REASON_1 = 'Has not requested for the commutation of the aforesaid leave credits;'
const DEFAULT_REASON_2 = 'Has availed of the following leaves during the current year:'
const DEFAULT_REASON_3 = 'Incurred zero (0) vacation leave w/o pay (VLWOP) days from January this year.'
const DEFAULT_REASON_4 = 'Incurred zero (0) days absent without official leave (AWOL) from January this year.'

const DEFAULT_LEAVE_ROWS = [
  { type: 'Forced/Mandatory Leave', days: 'N/A', dates: 'N/A' },
  { type: 'Special Privilege Leave', days: 'N/A', dates: 'N/A' }
]

export const buildDefaultPurposeText = (employeeName, genderId = null) => {
  let pronoun = 'their'
  if (genderId === 1) pronoun = 'his'
  else if (genderId === 2) pronoun = 'her'

  const name = employeeName || 'the employee'
  return `This certification is being issued upon the request of ${name} in connection with ${pronoun} transfer to the CITEM (Center for International Trade Expositions and Missions).`
}

export const createDefaultPreviewForm = (row = null) => ({
  purpose_text: buildDefaultPurposeText(row?.employee_name, row?.gender_id),
  reason_1: DEFAULT_REASON_1,
  reason_2: DEFAULT_REASON_2,
  reason_3: DEFAULT_REASON_3,
  reason_4: DEFAULT_REASON_4,
  leave_rows: DEFAULT_LEAVE_ROWS.map((item) => ({ ...item }))
})

export function useTransferLeaveCredits() {
  const formRef = ref(null)
  const employees = ref([])
  const transferList = ref([])
  const loadingEmployees = ref(false)
  const loadingCredits = ref(false)
  const loadingTransfers = ref(false)
  const submitting = ref(false)
  const previewLoading = ref(false)
  const pdfUrl = ref('')
  const previewKey = ref(0)
  const selectedRecord = ref(null)

  const previewFormData = ref(createDefaultPreviewForm())

  const formData = ref({
    employee_id: null,
    vacation_leave_credits: '',
    sick_leave_credits: ''
  })

  const leaveCredits = ref({
    vacation_leave: {
      leave_type_id: null,
      leave_type_name: 'Vacation Leave',
      credits: 0
    },
    sick_leave: {
      leave_type_id: null,
      leave_type_name: 'Sick Leave',
      credits: 0
    }
  })

  const rules = {
    employee_id: [{ required: true, message: 'Employee is required', trigger: 'change' }]
  }

  const formatEmployeeLabel = (emp) => {
    const parts = [emp.name]
    if (emp.position_name) parts.push(`- ${emp.position_name}`)
    return parts.join(' ')
  }

  const buildPrintParams = () => ({
    purpose_text: previewFormData.value.purpose_text,
    reason_1: previewFormData.value.reason_1,
    reason_2: previewFormData.value.reason_2,
    reason_3: previewFormData.value.reason_3,
    reason_4: previewFormData.value.reason_4,
    leave_rows: JSON.stringify(previewFormData.value.leave_rows)
  })

  const loadEmployees = async () => {
    loadingEmployees.value = true
    try {
      const { data } = await transferLeaveCreditsApi.getEmployees()
      employees.value = data.data || []
    } catch (error) {
      console.error('Failed to load employees for transfer leave credits', error)
      ElMessage.error('Failed to load employees')
    } finally {
      loadingEmployees.value = false
    }
  }

  const loadLeaveCredits = async (employeeId) => {
    if (!employeeId) {
      leaveCredits.value = {
        vacation_leave: { leave_type_id: null, leave_type_name: 'Vacation Leave', credits: 0 },
        sick_leave: { leave_type_id: null, leave_type_name: 'Sick Leave', credits: 0 }
      }
      formData.value.vacation_leave_credits = ''
      formData.value.sick_leave_credits = ''
      return
    }

    loadingCredits.value = true
    try {
      const { data } = await transferLeaveCreditsApi.getLeaveCredits(employeeId)
      if (data.data) {
        leaveCredits.value = data.data
        formData.value.vacation_leave_credits = data.data.vacation_leave.credits || 0
        formData.value.sick_leave_credits = data.data.sick_leave.credits || 0
      }
    } catch (error) {
      console.error('Failed to load leave credits', error)
      ElMessage.error('Failed to load leave credits')
      leaveCredits.value = {
        vacation_leave: { leave_type_id: null, leave_type_name: 'Vacation Leave', credits: 0 },
        sick_leave: { leave_type_id: null, leave_type_name: 'Sick Leave', credits: 0 }
      }
    } finally {
      loadingCredits.value = false
    }
  }

  const loadTransfers = async () => {
    loadingTransfers.value = true
    try {
      const { data } = await transferLeaveCreditsApi.list()
      transferList.value = data.data || []
    } catch (error) {
      console.error('Failed to load transfer leave credits', error)
      ElMessage.error('Failed to load transfer records')
    } finally {
      loadingTransfers.value = false
    }
  }

  const onSubmit = () => {
    if (!formRef.value) return

    formRef.value.validate(async (valid) => {
      if (!valid) return

      submitting.value = true
      try {
        await transferLeaveCreditsApi.create(formData.value)
        ElMessage.success('Transfer leave credits saved successfully')
        onReset()
        loadTransfers()
      } catch (error) {
        console.error('Failed to save transfer leave credits', error)
        ElMessage.error('Failed to save transfer leave credits')
      } finally {
        submitting.value = false
      }
    })
  }

  const onReset = () => {
    if (formRef.value) {
      formRef.value.resetFields()
    }
    formData.value.employee_id = null
    formData.value.vacation_leave_credits = ''
    formData.value.sick_leave_credits = ''
    leaveCredits.value = {
      vacation_leave: { leave_type_id: null, leave_type_name: 'Vacation Leave', credits: 0 },
      sick_leave: { leave_type_id: null, leave_type_name: 'Sick Leave', credits: 0 }
    }
  }

  const handleEmployeeChange = (employeeId) => {
    loadLeaveCredits(employeeId)
  }

  const generatePdfPreview = async ({ manageLoading = true } = {}) => {
    if (!selectedRecord.value?.id) return

    if (manageLoading) {
      previewLoading.value = true
    }
    try {
      const { data } = await transferLeaveCreditsApi.print(selectedRecord.value.id, buildPrintParams())
      const blob = new Blob([data], { type: 'application/pdf' })
      if (pdfUrl.value) {
        window.URL.revokeObjectURL(pdfUrl.value)
      }
      pdfUrl.value = window.URL.createObjectURL(blob)
      previewKey.value++
    } catch (error) {
      console.error('Failed to print transfer leave credits', error)
      ElMessage.error('Failed to generate report')
      throw error
    } finally {
      if (manageLoading) {
        previewLoading.value = false
      }
    }
  }

  const handlePrint = async (row) => {
    if (!row?.id) return
    selectedRecord.value = row
    previewFormData.value = createDefaultPreviewForm(row)
    await generatePdfPreview()
  }

  const updatePreview = async () => {
    if (!selectedRecord.value?.id) return
    previewLoading.value = true
    try {
      await generatePdfPreview({ manageLoading: false })
      ElMessage.success('Preview updated')
    } catch (error) {
      // error already surfaced in generatePdfPreview
    } finally {
      previewLoading.value = false
    }
  }

  const downloadPdf = () => {
    if (!pdfUrl.value) return
    const link = document.createElement('a')
    link.href = pdfUrl.value
    link.download = `transfer_leave_credits_${selectedRecord.value?.id || 'report'}.pdf`
    link.click()
  }

  const downloadWord = async () => {
    const id = selectedRecord.value?.id
    if (!id) return
    try {
      const { data } = await transferLeaveCreditsApi.generateWord(id, buildPrintParams())
      const blob = new Blob([data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
      const link = document.createElement('a')
      link.href = URL.createObjectURL(blob)
      link.download = `transfer_leave_credits_${id}_${new Date().toISOString().split('T')[0]}.docx`
      link.click()
      URL.revokeObjectURL(link.href)
    } catch (error) {
      console.error('Failed to download Word document', error)
      ElMessage.error('Failed to download Word document')
    }
  }

  onMounted(() => {
    loadEmployees()
    loadTransfers()
  })

  return {
    formRef,
    employees,
    transferList,
    pdfUrl,
    previewKey,
    previewFormData,
    previewLoading,
    selectedRecord,
    loadingEmployees,
    loadingCredits,
    loadingTransfers,
    submitting,
    formData,
    leaveCredits,
    rules,
    formatEmployeeLabel,
    loadEmployees,
    loadLeaveCredits,
    loadTransfers,
    onSubmit,
    onReset,
    handleEmployeeChange,
    handlePrint,
    updatePreview,
    downloadPdf,
    downloadWord
  }
}
