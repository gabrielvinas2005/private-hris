import { ref, computed } from 'vue'

export function useGsisTable() {
    const gsisTables = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const apiError = ref(false)
    const formVisible = ref(false)
    const formLoading = ref(false)
    const formData = ref([])
    const search = ref('')
    const filteredGsisTables = computed(() => [])
    const columnVisibility = ref({})

    function fetchGsisTables() {}
    function openForm() {}
    function addRow() {}
    function removeRow() {}
    function saveGsisTables() {}
    function deleteGsis() {}

    return {
        gsisTables, loading, saving, apiError,
        fetchGsisTables, formVisible, formLoading, formData,
        openForm, addRow, removeRow, saveGsisTables, deleteGsis,
        search, filteredGsisTables, columnVisibility
    }
}
