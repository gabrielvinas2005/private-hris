import { ref } from 'vue'
import ApiService from '../Services/api'

export function useDocumentNumbers() {
    const items = ref([])
    const loading = ref(false)
    const saving = ref(false)

    const isNonEmpty = (v) => (v ?? '').toString().trim().length > 0
    const areAllFieldsFilled = (row) => {
        return (
            isNonEmpty(row.name) &&
            isNonEmpty(row.rd_document_number) &&
            isNonEmpty(row.rd_revision) &&
            isNonEmpty(row.co_document_number) &&
            isNonEmpty(row.co_revision)
        )
    }

    const isRowCompletelyEmpty = (row) => {
        return (
            !isNonEmpty(row.name) &&
            !isNonEmpty(row.rd_document_number) &&
            !isNonEmpty(row.rd_revision) &&
            !isNonEmpty(row.co_document_number) &&
            !isNonEmpty(row.co_revision)
        )
    }

    async function fetchList() {
        loading.value = true
        try {
            const res = await ApiService.get('/document-numbers')
            const list = Array.isArray(res.data) ? res.data : []
            items.value = list.map(r => ({
                id: r.id ? parseInt(r.id) : null,
                name: r.name || '',
                rd_document_number: r.rd_document_number || '',
                rd_revision: r.rd_revision || '',
                co_document_number: r.co_document_number || '',
                co_revision: r.co_revision || ''
            }))
        } finally {
            loading.value = false
        }
    }

    function addRow() {
        items.value = [
            ...items.value,
            { id: null, name: '', rd_document_number: '', rd_revision: '', co_document_number: '', co_revision: '' }
        ]
    }

    async function remove(row) {
        if (!row.id) {
            items.value = items.value.filter(r => r !== row)
            return { success: true }
        }
        const res = await ApiService.delete(`/document-numbers/${row.id}`)
        await fetchList()
        return res
    }

    async function saveBulk() {
        saving.value = true
        try {
            const allItems = items.value || []
            const newRows = allItems.filter(r => !r.id)

            // Block saving if the user clicked "+ Add Document" but didn't fill the new row(s).
            // This prevents the screen from showing "saved" when nothing meaningful was added.
            if (newRows.length > 0) {
                const anyNewRowHasValues = newRows.some(r => !isRowCompletelyEmpty(r))
                const allNewRowsComplete = newRows.every(r => areAllFieldsFilled(r))

                if (!anyNewRowHasValues) {
                    return { success: false, message: 'Please enter document details before saving.' }
                }
                if (!allNewRowsComplete) {
                    return { success: false, message: 'Please fill in all fields for the new document.' }
                }
            }

            const validRows = allItems.filter(r => areAllFieldsFilled(r))
            const payload = {
                id: validRows.map(r => r.id ?? null),
                name: validRows.map(r => r.name ?? ''),
                rd_document_number: validRows.map(r => r.rd_document_number ?? ''),
                rd_revision: validRows.map(r => r.rd_revision ?? ''),
                co_document_number: validRows.map(r => r.co_document_number ?? ''),
                co_revision: validRows.map(r => r.co_revision ?? '')
            }
            if (validRows.length === 0) {
                return { success: false, message: 'No document rows to save.' }
            }
            return await ApiService.post('/document-numbers', payload)
        } finally {
            saving.value = false
        }
    }

    return { items, loading, saving, fetchList, addRow, remove, saveBulk }
}


