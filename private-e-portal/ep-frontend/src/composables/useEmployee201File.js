import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import regionData from '../assets/refregion.json'
import provinceData from '../assets/refprovince.json'
import cityData from '../assets/refcitymun.json'
import barangayData from '../assets/refbrgy.json'

export function useEmployee201File() {
    const router = useRouter()

    // Reactive state
    const loading = ref(false)
    const error = ref(null)
    const isAuthenticated = ref(false)
    const activeTab = ref('personal')

    // User data
    const userData = reactive({
        id: null,
        name: '',
        email: ''
    })

    // Employee data
    const employeeData = reactive({})
    const addressData = reactive({})
    const workInfo = reactive({})
    const payrollInfo = reactive({})
    const familyInfo = reactive({})
    const genderOptions = ref([])
    const bloodTypeOptions = ref([])
    const civilStatusOptions = ref([])
    const religionOptions = ref([])
    const employmentTypeOptions = ref([])
    // Initialize address options from frontend assets immediately
    const regionOptions = ref(regionData.RECORDS || [])
    const provinceOptions = ref(provinceData.RECORDS || [])
    const cityOptions = ref(cityData.RECORDS || [])
    const barangayOptions = ref(barangayData.RECORDS || [])

    // Arrays for various data
    const incomes = ref([])
    const loans = ref([])
    const children = ref([])
    const educations = ref([])
    const serviceRecords = ref([])
    const workExperiences = ref([])
    const eligibilities = ref([])
    const trainings = ref([])
    const voluntaryWorks = ref([])
    const ipcrResults = ref([])
    const recognitions = ref([])
    const skills = ref([])
    const memberships = ref([])
    const references = ref([])

    // Update permissions
    const canUpdate = ref(false)
    const daysRemaining = ref(0)
    const lastUpdateDate = ref('Original Date Created')

    // Computed properties
    const breadcrumbs = computed(() => [
        { name: 'Dashboard', path: '/dashboard' },
        { name: '201 File', path: '/201-file' }
    ])

    const tabs = computed(() => [
        { id: 'personal', name: 'Personal & Contact' },
        { id: 'work', name: 'Work Information' },
        { id: 'family', name: 'Family' },
        { id: 'education', name: 'Education' },
        { id: 'service', name: 'Service Record' },
        { id: 'experience', name: 'Work Experience' },
        { id: 'eligibility', name: 'Eligibility' },
        { id: 'training', name: 'Training' },
        { id: 'voluntary', name: 'Voluntary Work' },
        { id: 'ipcr', name: 'IPCR Result' },
        { id: 'other', name: 'Other' }
    ])

    // Methods
    const checkAuthentication = () => {
        const storedUserData = localStorage.getItem('user_data')
        if (storedUserData) {
            const user = JSON.parse(storedUserData)
            userData.id = user.id
            userData.name = user.name
            userData.email = user.email
            isAuthenticated.value = true
            return true
        }
        return false
    }

    const redirectToLogin = () => {
        router.push('/login')
    }

    const retry = () => {
        error.value = null
        loadEmployeeData()
    }

    const loadEmployeeData = async () => {
        try {
            loading.value = true
            error.value = null

            // Use the user ID from route params or localStorage
            const routeParamId = router.currentRoute.value?.params?.id ? parseInt(router.currentRoute.value.params.id, 10) : null
            const userId = routeParamId || userData.id

            if (!userId) {
                error.value = 'User ID not available'
                return
            }

            const ApiService = (await import('../services/api.js')).default
            const response = await ApiService.getEmployee201File(userId)

            if (response.success) {
                const data = response.data
                // Defensive mapping
                const employeeInfo = data.info && data.info.length > 0 ? data.info[0] : {}
                const cleanName = employeeInfo.name ? employeeInfo.name.replace(/\\\.\\.?\\s\*\\.?\\s\*\\./g, '.').trim() : ''

                // Spread ALL fields from employeeInfo first, then override/set specific ones
                Object.assign(employeeData, employeeInfo, {
                    name: cleanName || employeeInfo.name || 'Name not available'
                })

                Object.assign(addressData, data.address || {})

                // Reference dropdowns from backend (genders, blood types, etc.)
                genderOptions.value = data.genders || []
                bloodTypeOptions.value = data.blood_types || []
                civilStatusOptions.value = data.civil_statuses || []
                religionOptions.value = data.religions || []
                employmentTypeOptions.value = data.employment_types || []
                console.log('Employment types from API:', data.employment_types, 'Stored:', employmentTypeOptions.value)

                // Address dropdowns are already initialized from frontend assets
                // No need to reload them from backend

                Object.assign(workInfo, {
                    company: employeeInfo.company || '',
                    branch: employeeInfo.branch || '',
                    department: employeeInfo.department || '',
                    division: employeeInfo.division || '',
                    employment_type: employeeInfo.employment_type || '',
                    position: employeeInfo.position || '',
                    plantilla: employeeInfo.plantilla || '',
                    grade: employeeInfo.grade || '',
                    step: employeeInfo.step || '',
                    section: employeeInfo.section || ''
                })

                Object.assign(payrollInfo, {
                    interval: employeeInfo.payroll_interval || '',
                    gsis_no: employeeInfo.gsis_no || '',
                    sss_no: employeeInfo.sss_no || '',
                    pagibig_no: employeeInfo.pagibig_no || '',
                    philhealth_no: employeeInfo.philhealth_no || '',
                    tin_no: employeeInfo.tin_no || '',
                    salary: Number(employeeInfo.salary) || 0,
                    tax_amount: Number(employeeInfo.tax_amount) || 0,
                    gsis_amount: Number(employeeInfo.gsis_amount) || 0,
                    sss_amount: Number(employeeInfo.sss_amount) || 0,
                    pagibig_amount: Number(employeeInfo.pagibig_amount) || 0,
                    philhealth_amount: Number(employeeInfo.philhealth_amount) || 0
                })

                Object.assign(familyInfo, {
                    spouse_name: employeeInfo.spouse_name || '',
                    spouse_occupation: employeeInfo.spouse_occupation || '',
                    spouse_employer: employeeInfo.spouse_employer || '',
                    spouse_business_address: employeeInfo.spouse_business_address || '',
                    father_name: employeeInfo.father_name || '',
                    father_occupation: employeeInfo.father_occupation || '',
                    mother_name: employeeInfo.mother_name || '',
                    mother_occupation: employeeInfo.mother_occupation || ''
                })

                // Set arrays
                incomes.value = data.incomes || []
                loans.value = data.loans || []
                children.value = data.children || []
                educations.value = data.educations || []
                serviceRecords.value = data.service_records || []
                workExperiences.value = data.work_experiences || []
                eligibilities.value = data.eligibilities || []
                trainings.value = data.trainings || []
                voluntaryWorks.value = data.voluntary_works || []
                ipcrResults.value = data.ipcr_results || []
                recognitions.value = data.recognitions || []
                skills.value = data.skills || []
                memberships.value = data.memberships || []
                references.value = data.references || []

                // Set update permissions
                canUpdate.value = data.can_update || false
                daysRemaining.value = data.days_remaining || 0
                lastUpdateDate.value = data.last_update_date || 'Original Date Created'
            } else {
                error.value = response.message || 'Failed to load employee data'
            }
        } catch (err) {
            console.error('Error loading employee data:', err)
            error.value = 'An error occurred while loading employee data'
        } finally {
            loading.value = false
        }
    }

    const downloadPDS = async () => {
        try {
            const ApiService = (await import('../services/api.js')).default
            // Prefer employeeId from loaded employee data; fallback to user id
            const employeeId = employeeData.id || userData.id
            if (!employeeId) {
                console.error('Cannot download PDS: missing employee identifier')
                return
            }
            await ApiService.downloadPDS(employeeId)
        } catch (err) {
            console.error('Error downloading PDS:', err)
        }
    }

    const setActiveTab = (tabId) => {
        activeTab.value = tabId
    }

    return {
        // State
        loading,
        error,
        isAuthenticated,
        activeTab,
        userData,
        employeeData,
        addressData,
        workInfo,
        payrollInfo,
        familyInfo,
        incomes,
        loans,
        children,
        educations,
        serviceRecords,
        workExperiences,
        eligibilities,
        trainings,
        voluntaryWorks,
        ipcrResults,
        recognitions,
        skills,
        memberships,
        references,
        canUpdate,
        daysRemaining,
        lastUpdateDate,
        genderOptions,
        bloodTypeOptions,
        civilStatusOptions,
        religionOptions,
        employmentTypeOptions,
        regionOptions,
        provinceOptions,
        cityOptions,
        barangayOptions,

        // Computed
        breadcrumbs,
        tabs,

        // Methods
        checkAuthentication,
        redirectToLogin,
        retry,
        loadEmployeeData,
        downloadPDS,
        setActiveTab
    }
}
