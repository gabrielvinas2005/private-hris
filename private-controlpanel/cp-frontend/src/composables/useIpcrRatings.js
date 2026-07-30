import { ref } from 'vue'
import ApiService from '../Services/api'

export function useIpcrRatings() {
    const items = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const formOptions = ref({ departments: [], divisions: [], semesters: [], months: [] })

    async function fetchList() {
        loading.value = true
        try {
            const res = await ApiService.get('/ipcr')
            items.value = Array.isArray(res.data) ? res.data.map(r => ({
                id: parseInt(r.id),
                department: r.department,
                division: r.division,
                semester: r.semester,
                month_from: r.month_from,
                month_to: r.month_to,
                month_from_id: r.month_from_id ?? r.month_from,
                month_to_id: r.month_to_id ?? r.month_to,
            })) : []
        } finally {
            loading.value = false
        }
    }

    async function fetchFormData(id = 0) {
        const res = await ApiService.get(`/ipcr/add/${id}`)
        const d = res.data || {}
        // backend returns arrays keyed as departments, divisions, semesters
        formOptions.value = {
            departments: (d.departments || []).map(x => ({ id: parseInt(x.id), name: x.name })),
            divisions: (d.divisions || []).map(x => ({ id: parseInt(x.id), name: x.name })),
            semesters: (d.semesters || []).map(x => ({ id: parseInt(x.id), name: x.name })),
            months: [
                { id: 1, name: 'January' }, { id: 2, name: 'February' }, { id: 3, name: 'March' },
                { id: 4, name: 'April' }, { id: 5, name: 'May' }, { id: 6, name: 'June' },
                { id: 7, name: 'July' }, { id: 8, name: 'August' }, { id: 9, name: 'September' },
                { id: 10, name: 'October' }, { id: 11, name: 'November' }, { id: 12, name: 'December' }
            ]
        }
        return d.ipcr_ratings?.[0] || { id: 0, department_id: 0, division_id: 0, semester_id: 0, month_from: 0, month_to: 0, year: new Date().getFullYear() }
    }

    async function save(payload) {
        saving.value = true
        try {
            const id = payload.id || 0
            const body = {
                id,
                department: parseInt(payload.department_id),
                division: parseInt(payload.division_id),
                semester: parseInt(payload.semester_id),
                month_from: parseInt(payload.month_from),
                month_to: parseInt(payload.month_to),
                year: parseInt(payload.year)
            }
            return await ApiService.post(`/ipcr/${id}`, body)
        } finally {
            saving.value = false
        }
    }

    return { items, loading, saving, formOptions, fetchList, fetchFormData, save }
}


