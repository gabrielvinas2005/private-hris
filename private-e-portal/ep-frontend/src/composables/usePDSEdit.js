import { ref, reactive, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

export function usePDSEdit(employeeId) {
  const isEditMode = ref(false)
  const isSaving = ref(false)
  const lastSaveTime = ref(null)
  const editFormData = reactive({})
  const autosaveInterval = ref(null)
  const localStorageTimeout = ref(null)
  
  // Get storage key - handle both ref and value
  const getStorageKey = () => {
    const id = typeof employeeId === 'object' && employeeId.value !== undefined 
      ? employeeId.value 
      : employeeId
    return `pds_draft_${id || 'unknown'}`
  }

  // Initialize form data directly from database employee data
  const initializeFormData = (employeeData, addressData, workInfo, payrollInfo, familyInfo, children, educations, serviceRecords, workExperiences, eligibilities, trainings, voluntaryWorks, recognitions, skills, memberships, references) => {
    // Clear any previous draft from storage so database values always take precedence
    localStorage.removeItem(getStorageKey())

    // Initialize directly from database employee data
    Object.assign(editFormData, {
      // Basic info
      employee_no: employeeData.employee_no || '',
      email: employeeData.email || '',
      mobile_no: employeeData.mobile_no || '',
      telephone_no: employeeData.telephone_no || '',
      name_prefix_id: employeeData.name_prefix_id || 0,
      first_name: employeeData.first_name || '',
      middle_name: employeeData.middle_name || '',
      last_name: employeeData.last_name || '',
      name_suffix_id: employeeData.name_suffix_id || 0,
      birth_place: employeeData.birth_place || '',
      birthdate: employeeData.birthdate || employeeData.date_of_birth || '',
      age: employeeData.age || 0,
      height: employeeData.height || '',
      weight: employeeData.weight || '',
      gender_id: employeeData.gender_id || employeeData.sex_id || 0,
      civil_status_id: employeeData.civil_status_id || 0,
      citizenship_id: employeeData.citizenship_id || 0,
      religion_id: employeeData.religion_id || 0,
      blood_type_id: employeeData.blood_type_id || 0,
      // Address - Residential
      ra_house_no: employeeData.ra_house_no || '',
      ra_village: employeeData.ra_village || '',
      ra_street: employeeData.ra_street || '',
      ra_barangay: addressData.ra_brgy || addressData.ra_barangay || employeeData.ra_barangay || '',
      ra_city: addressData.ra_city || employeeData.ra_city || '',
      ra_province: addressData.ra_province || employeeData.ra_province || '',
      ra_region: addressData.ra_region || employeeData.ra_region || '',
      // Address - Permanent
      pa_house_no: employeeData.pa_house_no || '',
      pa_village: employeeData.pa_village || '',
      pa_street: employeeData.pa_street || '',
      pa_barangay: addressData.pa_brgy || addressData.pa_barangay || employeeData.pa_barangay || '',
      pa_city: addressData.pa_city || employeeData.pa_city || '',
      pa_province: addressData.pa_province || employeeData.pa_province || '',
      pa_region: addressData.pa_region || employeeData.pa_region || '',
        // Family
        father_name_prefix_id: employeeData.father_name_prefix_id || 0,
        father_first_name: employeeData.father_first_name || '',
        father_middle_name: employeeData.father_middle_name || '',
        father_last_name: employeeData.father_last_name || '',
        father_name_suffix_id: employeeData.father_name_suffix_id || 0,
        mother_name_prefix_id: employeeData.mother_name_prefix_id || 0,
        mother_first_name: employeeData.mother_first_name || '',
        mother_middle_name: employeeData.mother_middle_name || '',
        mother_last_name: employeeData.mother_last_name || '',
        mother_name_suffix_id: employeeData.mother_name_suffix_id || 0,
        spouse_name_prefix_id: employeeData.spouse_name_prefix_id || 0,
        spouse_first_name: employeeData.spouse_first_name || '',
        spouse_middle_name: employeeData.spouse_middle_name || '',
        spouse_last_name: employeeData.spouse_last_name || '',
        spouse_name_suffix_id: employeeData.spouse_name_suffix_id || 0,
        spouse_occupation: employeeData.spouse_occupation || '',
        spouse_employer: employeeData.spouse_employer || '',
        spouse_business_address: employeeData.spouse_business_address || '',
        // Work
        company_id: workInfo.company_id || 0,
        branch_id: workInfo.branch_id || 0,
        department_id: workInfo.department_id || 0,
        division_id: workInfo.division_id || 0,
        section_id: workInfo.section_id || 0,
        employment_type_id: workInfo.employment_type_id || 0,
        position_id: workInfo.position_id || 0,
        plantilla_id: workInfo.plantilla_id || 0,
        date_hired: workInfo.date_hired || '',
        // Payroll
        payroll_interval_id: payrollInfo.payroll_interval_id || 0,
        gsis_no: payrollInfo.gsis_no || '',
        sss_no: payrollInfo.sss_no || '',
        pagibig_no: payrollInfo.pagibig_no || '',
        philhealth_no: payrollInfo.philhealth_no || '',
        tin_no: payrollInfo.tin_no || '',
        salary: payrollInfo.salary || 0,
        tax_amount: payrollInfo.tax_amount || 0,
        gsis_amount: payrollInfo.gsis_amount || 0,
        sss_amount: payrollInfo.sss_amount || 0,
        pagibig_amount: payrollInfo.pagibig_amount || 0,
        philhealth_amount: payrollInfo.philhealth_amount || 0,
        // Array sections
        children: children || [],
        educations: educations || [],
        serviceRecords: serviceRecords || [],
        workExperiences: workExperiences || [],
        eligibilities: eligibilities || [],
        trainings: trainings || [],
        voluntaryWorks: voluntaryWorks || [],
        recognitions: recognitions || [],
        skills: skills || [],
        memberships: memberships || [],
        references: references || []
      })
    }

  // Load draft from localStorage
  const loadDraftFromStorage = () => {
    try {
      const saved = localStorage.getItem(getStorageKey())
      if (saved) {
        return JSON.parse(saved)
      }
    } catch (e) {
      console.error('Error loading draft from storage:', e)
    }
    return null
  }

  // Save draft to localStorage
  const saveDraftToStorage = () => {
    try {
      localStorage.setItem(getStorageKey(), JSON.stringify(editFormData))
    } catch (e) {
      console.error('Error saving draft to storage:', e)
    }
  }

  // Autosave to backend
  const autosave = async () => {
    if (isSaving.value || !isEditMode.value) return

    try {
      isSaving.value = true
      const ApiService = (await import('../services/api.js')).default
      
      // Get employee ID - handle both ref and value
      const id = typeof employeeId === 'object' && employeeId.value !== undefined 
        ? employeeId.value 
        : employeeId
      
      if (!id) {
        console.warn('Cannot autosave: employee ID not available')
        return
      }
      
      // Save to backend
      const response = await ApiService.autosavePDS(id, editFormData)
      
      if (response.success) {
        lastSaveTime.value = new Date()
        // Also save to localStorage as backup
        saveDraftToStorage()
      }
    } catch (error) {
      console.error('Autosave error:', error)
      // Still save to localStorage even if backend fails
      saveDraftToStorage()
    } finally {
      isSaving.value = false
    }
  }

  // Watch for changes and trigger autosave
  watch(editFormData, () => {
    if (isEditMode.value) {
      // Debounce autosave - save to backend after 2 seconds of no changes
      clearTimeout(autosaveInterval.value)
      autosaveInterval.value = setTimeout(() => {
        autosave()
      }, 2000)
    }
  }, { deep: true })

  // Enter edit mode
  const enterEditMode = (employeeData, addressData, workInfo, payrollInfo, familyInfo, children, educations, serviceRecords, workExperiences, eligibilities, trainings, voluntaryWorks, recognitions, skills, memberships, references) => {
    isEditMode.value = true
    initializeFormData(employeeData, addressData, workInfo, payrollInfo, familyInfo, children, educations, serviceRecords, workExperiences, eligibilities, trainings, voluntaryWorks, recognitions, skills, memberships, references)
  }

  // Exit edit mode
  const exitEditMode = async (save = false) => {
    if (save) {
      // Final save before exiting
      await autosave()
      localStorage.removeItem(getStorageKey())
      ElMessage.success('Changes saved successfully')
    } else {
      // Show modal confirmation before canceling
      try {
        await ElMessageBox.confirm(
          'Are you sure you want to cancel? Unsaved changes will be lost.',
          'Cancel Editing',
          {
            confirmButtonText: 'Yes, Cancel',
            cancelButtonText: 'No, Continue Editing',
            type: 'warning',
            distinguishCancelAndClose: true
          }
        )
        // User confirmed - clear draft and exit
        localStorage.removeItem(getStorageKey())
      } catch {
        // User cancelled the confirmation - don't exit
        return
      }
    }
    
    // Clear autosave interval
    if (autosaveInterval.value) {
      clearTimeout(autosaveInterval.value)
      autosaveInterval.value = null
    }
    
    isEditMode.value = false
  }

  // Helper function to set nested property
  const setNestedProperty = (obj, path, value) => {
    const keys = path.split('.')
    let current = obj
    
    for (let i = 0; i < keys.length - 1; i++) {
      const key = keys[i]
      // Handle array indices
      if (!isNaN(key) && !isNaN(parseFloat(key))) {
        const index = parseInt(key)
        if (!Array.isArray(current[key])) {
          // If it's a number but not an array, treat as object property
          if (!current[key]) current[key] = {}
          current = current[key]
        } else {
          // Ensure array exists and has enough elements
          while (current[key].length <= index) {
            current[key].push({})
          }
          current = current[key][index]
        }
      } else {
        if (!current[key]) {
          // Check if next key is a number (array index)
          const nextKey = keys[i + 1]
          if (!isNaN(nextKey) && !isNaN(parseFloat(nextKey))) {
            current[key] = []
          } else {
            current[key] = {}
          }
        }
        current = current[key]
      }
    }
    
    const lastKey = keys[keys.length - 1]
    if (!isNaN(lastKey) && !isNaN(parseFloat(lastKey))) {
      const index = parseInt(lastKey)
      if (Array.isArray(current)) {
        current[index] = value
      } else {
        current[lastKey] = value
      }
    } else {
      current[lastKey] = value
    }
  }

  // Update form data (called from child components)
  const updateFormData = (data) => {
    if (data && typeof data === 'object' && data.field !== undefined) {
      // Handle nested paths like "children.0.child_name" or "eligibilities.1.exam_rating"
      if (data.field.includes('.')) {
        setNestedProperty(editFormData, data.field, data.value)
      } else {
        // Handle simple field updates
        editFormData[data.field] = data.value
      }
    } else if (arguments.length === 2) {
      // Handle direct format: (field, value)
      const [field, value] = arguments
      if (field.includes('.')) {
        setNestedProperty(editFormData, field, value)
      } else {
        editFormData[field] = value
      }
    }
  }

  // Cleanup on unmount
  const cleanup = () => {
    if (autosaveInterval.value) {
      clearTimeout(autosaveInterval.value)
    }
    if (localStorageTimeout.value) {
      clearTimeout(localStorageTimeout.value)
    }
  }

  return {
    isEditMode,
    isSaving,
    lastSaveTime,
    editFormData,
    enterEditMode,
    exitEditMode,
    updateFormData,
    autosave,
    cleanup
  }
}

