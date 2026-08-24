import { ref, reactive, computed } from 'vue'
import ApiService from '../Services/api'
import { ElMessage } from 'element-plus'

export function useApprovers() {
    const rows = ref([])
    const loading = ref(false)
    const saving = ref(false)

    // form state
    const formVisible = ref(false)
    const formLoading = ref(false)
    const isDuplicateMode = ref(false)
    const form = reactive({
        id: 0,
        branch_id: null,
        department_id: null,
        division_id: null,
        section_id: null,
        type_id: null,
        type_ids: [], // Multi-type selection support
        branch_approver_1: null,
        approver_1: null,
        approver_2: null,
        approver_3: null,
        approver_4: null,
        division_approver_1: null,
        section_approver_1: null,
    })
    const options = reactive({
        branches: [],
        departments: [],
        divisions: [],
        sections: [],
        approvers: [],
        approverTypes: [],
        departmentEmployees: [], // Employees from selected department for subordinate selection
    })

    // subordinate lists by type
    const subordinates = reactive({
        branch: [],
        department: [],
        division: [],
        section: [],
    })

    // UI helpers for managing Office subordinates
    const newOfficeSubordinates = ref([])
    const pendingOfficeSubordinates = ref([])

    function toInt(v) {
        if (v === null || v === undefined || v === '') return 0
        const n = parseInt(v)
        return Number.isNaN(n) ? 0 : n
    }

    function toId(v) {
        const n = toInt(v)
        return n > 0 ? n : null
    }

    /** Build "First M. Last"; omit middle initial when middle is null/empty (same rule as backend). */
    function formatNameFromParts(first, middle, last) {
        const fi = first != null ? String(first).trim() : ''
        const mi = middle != null ? String(middle).trim() : ''
        const la = last != null ? String(last).trim() : ''
        if (!fi && !la) return ''
        if (!mi) return `${fi} ${la}`.trim()
        const initial = mi.charAt(0).toUpperCase()
        return `${fi} ${initial}. ${la}`.trim()
    }

    function resolveEmployeeDisplayName(row) {
        if (!row || typeof row !== 'object') return ''
        const direct = row.name ?? row.label ?? row.title
        if (direct != null && String(direct).trim() !== '') return String(direct).trim()
        return formatNameFromParts(row.first_name, row.middle_name, row.last_name)
    }

    function normalizeIdNameList(list = []) {
        return (Array.isArray(list) ? list : []).map(x => ({
            ...x,
            id: toInt(x?.id),
            name: resolveEmployeeDisplayName(x)
        })).filter(x => !!x.id)
    }

    function normalizeApproverTypes(list = []) {
        return (Array.isArray(list) ? list : []).map(t => {
            const id = toInt(t?.id ?? t?.type_id ?? t?.TypeId ?? t?.approver_type_id)
            const name =
                t?.name ??
                t?.approver_type ??
                t?.type ??
                t?.description ??
                t?.title ??
                (id ? `Type ${id}` : '')
            return { ...t, id, name }
        }).filter(t => !!t.id)
    }

    async function fetchList() {
        loading.value = true
        try {
            const res = await ApiService.getLeaveApprovers()

            if (!res || !res.data) {
                rows.value = []
                return
            }

            const response = res.data
            const headers = response.data || []
            const subordinatesData = response.subordinates || []

            // Group subordinates by approver_id and type
            const subordinatesByApprover = {}
            subordinatesData.forEach(sub => {
                if (!subordinatesByApprover[sub.id]) {
                    subordinatesByApprover[sub.id] = { office: [], section: [], division: [], branch: [] }
                }
                const subName = resolveEmployeeDisplayName(sub)
                if (sub.is_department) subordinatesByApprover[sub.id].office.push({ id: sub.employee_id, name: subName })
                if (sub.is_section) subordinatesByApprover[sub.id].section.push({ id: sub.employee_id, name: subName })
                if (sub.is_division) subordinatesByApprover[sub.id].division.push({ id: sub.employee_id, name: subName })
                if (sub.is_branch) subordinatesByApprover[sub.id].branch.push({ id: sub.employee_id, name: subName })
            })

            // Map headers with subordinates
            rows.value = headers.map(header => ({
                ...header,
                office_subordinates: subordinatesByApprover[header.id]?.office || [],
                section_subordinates: subordinatesByApprover[header.id]?.section || [],
                division_subordinates: subordinatesByApprover[header.id]?.division || [],
                branch_subordinates: subordinatesByApprover[header.id]?.branch || []
            }))
        } catch (error) {
            rows.value = []
        } finally {
            loading.value = false
        }
    }

    async function openForm(id = 0) {
        formVisible.value = true
        formLoading.value = true
        isDuplicateMode.value = false
        try {
            const res = await ApiService.getLeaveApproverForm(id)
            const payload = res?.data || {}
            const header = payload.approver_headers || { id: 0 }

            form.id = toInt(header.id) || 0
            form.branch_id = toId(header.branch_id)
            form.department_id = toId(header.department_id)
            form.division_id = toId(header.division_id)
            form.section_id = toId(header.section_id)
            form.branch_approver_1 = toId(header.branch_approver_id_1)
            form.approver_1 = toId(header.approver_id_1)
            form.approver_2 = toId(header.approver_id_2)
            form.approver_3 = toId(header.approver_id_3)
            form.approver_4 = toId(header.approver_id_4)
            form.division_approver_1 = toId(header.division_approver_id_1)
            form.section_approver_1 = toId(header.section_approver_id_1)
            form.type_id = toId(header.type_id)
            form.type_ids = header.type_id ? [toId(header.type_id)] : []

            options.branches = normalizeIdNameList(payload.branches || [])
            options.departments = normalizeIdNameList(payload.departments || [])
            options.sections = normalizeIdNameList(payload.sections || [])
            options.approvers = normalizeIdNameList(payload.approvers || [])
            options.approverTypes = normalizeApproverTypes(payload.approver_types || [])
            options.divisions = []

            if (form.branch_id) await loadDepartments(form.branch_id, { preserveSelection: true })
            if (form.department_id) await loadDivisions(form.department_id, { preserveSelection: true })
            if (form.division_id) await loadSections(form.division_id, { preserveSelection: true })

            if (form.department_id && !options.departments.some(d => d.id === form.department_id)) {
                options.departments.unshift({ id: form.department_id, name: 'Selected Office' })
            }
            if (form.division_id && !options.divisions.some(dv => dv.id === form.division_id)) {
                options.divisions.unshift({ id: form.division_id, name: 'Selected Division' })
            }
            if (form.section_id && !options.sections.some(s => s.id === form.section_id)) {
                options.sections.unshift({ id: form.section_id, name: 'Selected Section' })
            }
            if (form.type_id && !options.approverTypes.some(t => t.id === form.type_id)) {
                options.approverTypes.unshift({ id: form.type_id, name: 'Selected Type' })
            }

            const mapDetail = (x) => ({
                id: x.employee_id,
                name: resolveEmployeeDisplayName(x),
                position: x.position
            })
            subordinates.branch = (payload.approver_details_branch || []).map(mapDetail)
            subordinates.department = (payload.approver_details || []).map(mapDetail)
            subordinates.division = (payload.approver_details_division || []).map(mapDetail)
            subordinates.section = (payload.approver_details_section || []).map(mapDetail)
            pendingOfficeSubordinates.value = []
        } catch (error) {
            try {
                const branchesRes = await ApiService.get('/branches')
                if (branchesRes?.data) options.branches = branchesRes.data

                const employeesRes = await ApiService.get('/employees')
                if (employeesRes?.data) {
                    options.approvers = normalizeIdNameList(
                        employeesRes.data.filter(emp => emp.active && emp.is_employee)
                    )
                }
            } catch (fallbackError) {}
            if (!options.approverTypes || options.approverTypes.length === 0) {
                options.approverTypes = []
            }
        } finally {
            formLoading.value = false
        }
    }

    /** 1-Click Rule Duplication Helper */
    async function duplicateApprover(row) {
        if (!row || !row.id) return
        await openForm(row.id)
        isDuplicateMode.value = true
        form.id = 0 // Reset ID so saving creates a brand-new rule
        // Stage existing department subordinates for the new rule
        if (subordinates.department && subordinates.department.length > 0) {
            pendingOfficeSubordinates.value = subordinates.department.map(s => s.id)
        }
        ElMessage.info(`Cloning setup from ${row.department || 'selected office'}. Adjust parameters and save to create a new route.`)
    }

    async function loadDepartments(branchId, { preserveSelection = false } = {}) {
        try {
            if (!branchId) {
                options.departments = []
                return
            }
            const prevDepartmentId = form.department_id
            const prevDivisionId = form.division_id
            const prevSectionId = form.section_id
            const res = await ApiService.getApproverDepartments(branchId)
            const data = normalizeIdNameList(res?.data || [])
            options.departments = data

            if (!preserveSelection) {
                form.department_id = null
                form.division_id = null
                form.section_id = null
                options.divisions = []
                options.sections = []
            } else {
                form.department_id = prevDepartmentId
                form.division_id = prevDivisionId
                form.section_id = prevSectionId
            }
        } catch (error) {}
    }
    async function loadDivisions(departmentId, { preserveSelection = false } = {}) {
        try {
            if (!departmentId) {
                options.divisions = []
                return
            }
            const prevDivisionId = form.division_id
            const prevSectionId = form.section_id
            const res = await ApiService.getApproverDivisions(departmentId)
            const data = normalizeIdNameList(res?.data || [])
            options.divisions = data

            if (!preserveSelection) {
                form.division_id = null
                form.section_id = null
                options.sections = []
            } else {
                form.division_id = prevDivisionId
                form.section_id = prevSectionId
            }
        } catch (error) {}
    }
    async function loadSections(divisionId, { preserveSelection = false } = {}) {
        try {
            if (!divisionId) {
                options.sections = []
                return
            }
            const prevSectionId = form.section_id
            const res = await ApiService.getApproverSections(divisionId)
            const data = normalizeIdNameList(res?.data || [])
            options.sections = data

            if (!preserveSelection) {
                form.section_id = null
            } else {
                form.section_id = prevSectionId
            }
        } catch (error) {}
    }

    async function saveForm() {
        saving.value = true
        try {
            const isEdit = form.id !== 0
            const typeList = (form.type_ids && form.type_ids.length > 0) 
                ? form.type_ids 
                : (form.type_id ? [form.type_id] : [null])

            let lastResult = null
            let savedCount = 0

            for (const tId of typeList) {
                const payload = {
                    branch_id: toInt(form.branch_id),
                    department_id: toInt(form.department_id),
                    division_id: toInt(form.division_id),
                    section_id: toInt(form.section_id),
                    type_id: tId ? toInt(tId) : null,
                    branch_approver_1: toInt(form.branch_approver_1),
                    division_approver_1: toInt(form.division_approver_1),
                    section_approver_1: toInt(form.section_approver_1),
                    approver_1: toInt(form.approver_1),
                    approver_2: toInt(form.approver_2),
                    approver_3: toInt(form.approver_3),
                    approver_4: toInt(form.approver_4),
                }

                // If editing, use existing form.id for first type, 0 for additional types
                const currentId = (savedCount === 0 && isEdit) ? form.id : 0
                const res = await ApiService.saveLeaveApprover(currentId, payload)

                if (!res?.success) {
                    if (typeList.length === 1) {
                        ElMessage.error(res?.message || 'Failed to save approver setup')
                        return { success: false, errors: res?.errors }
                    }
                    continue
                }

                savedCount++
                lastResult = res
                const newId = (res && res.data && (res.data.id || res.data.data?.id)) || currentId

                // Attach pending subordinates to each created header
                if (newId && pendingOfficeSubordinates.value.length > 0) {
                    await ApiService.addApproverSubordinates(newId, 2, { employee_id: pendingOfficeSubordinates.value })
                }
            }

            pendingOfficeSubordinates.value = []
            await fetchList()
            formVisible.value = false

            const successMsg = savedCount > 1 
                ? `Created ${savedCount} approval rules across selected request types!`
                : (isEdit ? 'Approver setup updated successfully!' : 'Approver setup saved successfully!')
            
            ElMessage.success(successMsg)
            return { success: true }
        } finally {
            saving.value = false
        }
    }

    async function deleteApprover(id) {
        if (!id) return
        await ApiService.deleteLeaveApprover(id)
        await fetchList()
    }

    async function reloadOfficeSubordinates() {
        try {
            if (!form.id) return
            const res = await ApiService.getLeaveApproverForm(form.id)
            const payload = res?.data || {}
            subordinates.department = (payload.approver_details || []).map(x => ({
                id: x.employee_id,
                name: resolveEmployeeDisplayName(x),
                position: x.position
            }))
        } catch {}
    }

    async function loadAvailableSubordinates() {
        try {
            if (!form.department_id) {
                subordinates.department = []
                return
            }
            
            const leaveTypeId = form.type_id ? toInt(form.type_id) : 0
            const res = await ApiService.getApproverSubordinates(
                form.department_id,
                form.id || 0,
                2,
                leaveTypeId > 0 ? leaveTypeId : null
            )
            const availableSubordinates = Array.isArray(res?.data) ? res.data : []
            
            options.departmentEmployees = availableSubordinates.map(sub => ({
                id: sub.id,
                name: resolveEmployeeDisplayName(sub),
                position: sub.position,
                department: sub.department
            }))
            newOfficeSubordinates.value = []
        } catch (error) {
            options.departmentEmployees = []
        }
    }

    /** 1-Click Auto-Add All Department Employees as Subordinates */
    async function autoAddAllDepartmentSubordinates() {
        if (!options.departmentEmployees || options.departmentEmployees.length === 0) {
            ElMessage.warning('No available department employees found to auto-add.')
            return
        }

        const existingIds = new Set(subordinates.department.map(s => s.id))
        const newIdsToStage = []

        options.departmentEmployees.forEach(emp => {
            if (!existingIds.has(emp.id)) {
                subordinates.department.push({ id: emp.id, name: emp.name, position: emp.position })
                newIdsToStage.push(emp.id)
            }
        })

        if (newIdsToStage.length === 0) {
            ElMessage.info('All department employees are already included as subordinates.')
            return
        }

        if (!form.id) {
            const staged = new Set(pendingOfficeSubordinates.value)
            newIdsToStage.forEach(id => staged.add(id))
            pendingOfficeSubordinates.value = Array.from(staged)
        } else {
            await ApiService.addApproverSubordinates(form.id, 2, { employee_id: newIdsToStage })
        }

        ElMessage.success(`Added ${newIdsToStage.length} department employees as subordinates!`)
    }

    async function addOfficeSubordinates() {
        if (newOfficeSubordinates.value.length === 0) return
        if (!form.id) {
            const idSet = new Set(subordinates.department.map(s => s.id))
            newOfficeSubordinates.value.forEach(empId => {
                if (!idSet.has(empId)) {
                    const found = options.departmentEmployees.find(a => a.id === empId)
                    subordinates.department.push({ id: empId, name: found ? found.name : String(empId) })
                }
            })
            const staged = new Set(pendingOfficeSubordinates.value)
            newOfficeSubordinates.value.forEach(empId => staged.add(empId))
            pendingOfficeSubordinates.value = Array.from(staged)
            newOfficeSubordinates.value = []
            return
        }
        const payload = { employee_id: newOfficeSubordinates.value }
        await ApiService.addApproverSubordinates(form.id, 2, payload)
        const idSet2 = new Set(subordinates.department.map(s => s.id))
        newOfficeSubordinates.value.forEach(empId => {
            if (!idSet2.has(empId)) {
                const found = options.departmentEmployees.find(a => a.id === empId)
                subordinates.department.push({ id: empId, name: found ? found.name : String(empId) })
            }
        })
        newOfficeSubordinates.value = []
    }

    async function removeOfficeSubordinate(employeeId) {
        if (!employeeId) return
        await ApiService.deleteApproverSubordinate(employeeId)
        subordinates.department = subordinates.department.filter(s => s.id !== employeeId)
    }

    /** Compute Department Coverage & Gap Matrix */
    const coverageMatrix = computed(() => {
        const deptMap = {}
        
        rows.value.forEach(r => {
            const dName = r.department || 'Unassigned Department'
            if (!deptMap[dName]) {
                deptMap[dName] = {
                    department: dName,
                    branch: r.branch || 'Main Agency',
                    rules_count: 0,
                    types: new Set(),
                    approver_1_set: new Set(),
                    approver_2_set: new Set(),
                    approver_3_set: new Set(),
                    subordinates_count: 0
                }
            }

            deptMap[dName].rules_count++
            if (r.approver_type) deptMap[dName].types.add(r.approver_type)
            else deptMap[dName].types.add('Common / All Types')

            if (r.approver_1) deptMap[dName].approver_1_set.add(r.approver_1)
            if (r.approver_2) deptMap[dName].approver_2_set.add(r.approver_2)
            if (r.approver_3) deptMap[dName].approver_3_set.add(r.approver_3)

            deptMap[dName].subordinates_count += (r.office_subordinates?.length || 0)
        })

        return Object.values(deptMap).map(item => ({
            ...item,
            types_list: Array.from(item.types),
            approver_1_list: Array.from(item.approver_1_set),
            approver_2_list: Array.from(item.approver_2_set),
            approver_3_list: Array.from(item.approver_3_set),
            is_covered: item.rules_count > 0
        }))
    })

    const coveredOfficesCount = computed(() => coverageMatrix.value.length)
    const totalRoutesCount = computed(() => rows.value.length)

    const tableColumns = [
        { key: 'branch', label: 'Branch', minWidth: 160 },
        { key: 'department', label: 'Office', minWidth: 160 },
        { key: 'division', label: 'Division', minWidth: 160 },
        { key: 'section', label: 'Section', minWidth: 160 },
        { key: 'approver_1', label: 'Approver 1', minWidth: 180 },
        { key: 'approver_2', label: 'Approver 2', minWidth: 180 },
        { key: 'approver_3', label: 'Approver 3', minWidth: 180 },
        { key: 'approver_4', label: 'Approver 4', minWidth: 180 },
        { key: 'branch_approver', label: 'Branch Approver', minWidth: 180 },
        { key: 'division_approver', label: 'Division Approver', minWidth: 180 },
        { key: 'section_approver', label: 'Section Approver', minWidth: 180 },
        { key: 'actions', label: 'Actions', width: 140, fixed: 'right' }
    ]

    return {
        rows,
        loading,
        saving,
        fetchList,
        // form
        formVisible,
        formLoading,
        isDuplicateMode,
        form,
        options,
        openForm,
        duplicateApprover,
        loadDepartments,
        loadDivisions,
        loadSections,
        saveForm,
        deleteApprover,
        subordinates,
        tableColumns,
        // office subordinates management
        newOfficeSubordinates,
        pendingOfficeSubordinates,
        reloadOfficeSubordinates,
        loadAvailableSubordinates,
        autoAddAllDepartmentSubordinates,
        addOfficeSubordinates,
        removeOfficeSubordinate,
        // analytics
        coverageMatrix,
        coveredOfficesCount,
        totalRoutesCount,
    }
}


