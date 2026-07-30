import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { requestForPublicationApi } from '../services/api'

export function useRequestforpublication() {
  const formRef = ref(null)
  const plantillas = ref([])
  const requestList = ref([])
  const loadingPlantillas = ref(false)
  const loadingRequests = ref(false)
  const submitting = ref(false)
  const showPlantillas = ref(false)
  const pdfUrl = ref('')
  const loadingFormData = ref(false)
  const hrmoEmployees = ref([])

  const formData = ref({
    plantilla_id: null,
    date_requested: '',
    remarks: '',
    status: 'Pending'
  })

  const reportFormData = ref({
    hrmo_employee_id: null,
    hrmo_email: ''
  })

  const savingHrmoEmail = ref(false)
  const generateLoading = ref(false)

  const rules = {
    plantilla_id: [{ required: true, message: 'Plantilla position is required', trigger: 'change' }],
    date_requested: [{ required: true, message: 'Date requested is required', trigger: 'change' }]
  }

  const formatPlantillaLabel = (plantilla) => {
    const grade = plantilla.salary_grade_name ? `Grade: ${plantilla.salary_grade_name}` : ''
    const step = plantilla.salary_step_name ? `Step: ${plantilla.salary_step_name}` : ''
    const dept = plantilla.department_name ? ` - ${plantilla.department_name}` : ''
    const gradeStep = [grade, step].filter(Boolean).join(', ')

    return `${plantilla.code} - ${plantilla.position_name}${gradeStep ? ` (${gradeStep})` : ''}${dept}`
  }

  const getStatusTagType = (status) => {
    switch (status) {
      case 'Approved':
        return 'success'
      case 'Rejected':
        return 'danger'
      case 'Pending':
        return 'info'
      default:
        return ''
    }
  }

  const loadPlantillas = async () => {
    loadingPlantillas.value = true
    try {
      const { data } = await requestForPublicationApi.getPlantillas()
      plantillas.value = data.data || []
    } catch (error) {
      console.error('Failed to load plantillas for publication', error)
      ElMessage.error('Failed to load plantillas')
    } finally {
      loadingPlantillas.value = false
    }
  }

  const loadRequests = async () => {
    loadingRequests.value = true
    try {
      const { data } = await requestForPublicationApi.list()
      requestList.value = data.data || []
    } catch (error) {
      console.error('Failed to load requests for publication', error)
      ElMessage.error('Failed to load requests')
    } finally {
      loadingRequests.value = false
    }
  }

  const onSubmit = () => {
    if (!formRef.value) return

    formRef.value.validate(async (valid) => {
      if (!valid) return

      submitting.value = true
      try {
        await requestForPublicationApi.create(formData.value)
        ElMessage.success('Request for publication submitted successfully')
        onReset()
        loadRequests()
      } catch (error) {
        console.error('Failed to submit request for publication', error)
        ElMessage.error('Failed to submit request for publication')
      } finally {
        submitting.value = false
      }
    })
  }

  const onReset = () => {
    if (formRef.value) {
      formRef.value.resetFields()
    }
    formData.value.status = 'Pending'
  }

  const loadFormData = async () => {
    loadingFormData.value = true
    try {
      const { data } = await requestForPublicationApi.getFormData()
      hrmoEmployees.value = data.data?.hrmo_employees || []
      reportFormData.value.hrmo_email = data.data?.company?.publication_hrmo_email || ''
    } catch (error) {
      console.error('Failed to load form data', error)
      ElMessage.error('Failed to load form data')
    } finally {
      loadingFormData.value = false
    }
  }

  const openPlantillas = async () => {
    showPlantillas.value = true
    await loadPlantillas()
    await loadFormData()
  }

  const handleGenerateReport = async () => {
    if (!plantillas.value.length) {
      ElMessage.warning('No plantillas available for publication report')
      return
    }

    const selectedHrmo = hrmoEmployees.value.find(
      (emp) => emp.id === reportFormData.value.hrmo_employee_id
    )

    if (pdfUrl.value) {
      window.URL.revokeObjectURL(pdfUrl.value)
      pdfUrl.value = ''
    }

    generateLoading.value = true
    try {
      const payload = {
        plantilla_ids: plantillas.value.map(p => p.id),
        hrmo_employee_id: reportFormData.value.hrmo_employee_id || null,
        hrmo_name: selectedHrmo?.name || null,
        hrmo_position: selectedHrmo?.position_title || null,
        hrmo_email: reportFormData.value.hrmo_email?.trim() || null,
      }
      const { data } = await requestForPublicationApi.print(payload)
      const blob = new Blob([data], { type: 'application/pdf' })
      pdfUrl.value = window.URL.createObjectURL(blob)
    } catch (error) {
      console.error('Failed to generate publication report', error)
      ElMessage.error('Failed to generate report')
    } finally {
      generateLoading.value = false
    }
  }

  const saveHrmoEmail = async () => {
    const email = reportFormData.value.hrmo_email?.trim()
    if (!email) {
      ElMessage.warning('Please enter an email address')
      return
    }

    savingHrmoEmail.value = true
    try {
      await requestForPublicationApi.saveHrmoEmail({ email })
      ElMessage.success('Publication contact email saved')
    } catch (error) {
      console.error('Failed to save publication contact email', error)
      ElMessage.error('Failed to save email')
    } finally {
      savingHrmoEmail.value = false
    }
  }

  const downloadPdf = () => {
    if (!pdfUrl.value) return
    const link = document.createElement('a')
    link.href = pdfUrl.value
    link.download = 'request_for_publication.pdf'
    link.click()
  }

  return {
    // state
    formRef,
    plantillas,
    requestList,
    loadingPlantillas,
    loadingRequests,
    submitting,
    showPlantillas,
    formData,
    rules,
    pdfUrl,
    loadingFormData,
    hrmoEmployees,
    reportFormData,
    savingHrmoEmail,
    generateLoading,
    // helpers
    formatPlantillaLabel,
    getStatusTagType,
    // actions
    loadPlantillas,
    loadRequests,
    loadFormData,
    openPlantillas,
    handleGenerateReport,
    saveHrmoEmail,
    onSubmit,
    onReset,
    downloadPdf
  }
}

