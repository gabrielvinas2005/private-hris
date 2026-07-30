import { ref, computed } from 'vue'

export function useTabAccess() {
    const isLoading = ref(false)
    const isLoaded = ref(false)

    const allowedMenuKeys = ref([])
    const allowedMenuNames = ref([])

    const toBool = (val) => {
        if (val === true || val === 1) return true
        if (val === false || val === 0 || val === null || typeof val === 'undefined') return false
        if (typeof val === 'string') {
            const v = val.trim().toLowerCase()
            return v === '1' || v === 'true' || v === 'yes' || v === 'y'
        }
        return !!val
    }

    const normalize = (s) => {
        if (s === null || typeof s === 'undefined') return ''
        return String(s).trim().toLowerCase().replace(/\s+/g, ' ')
    }

    const isAdmin = computed(() => {
        try {
            const raw = localStorage.getItem('user_data')
            if (!raw) return false
            const user = JSON.parse(raw)
            return toBool(user?.is_admin)
        } catch {
            return false
        }
    })

    const allowedKeySet = computed(() => new Set(allowedMenuKeys.value.map(normalize)))
    const allowedNameSet = computed(() => new Set(allowedMenuNames.value.map(normalize)))

    const canViewTab = (label, name) => {
        // Do not render until permissions are loaded to avoid unauthorized "flash".
        if (!isLoaded.value) return false

        // Allow calling with a single identifier (menu_key or menu name).
        if (typeof name === 'undefined') {
            const id = normalize(label)
            return allowedKeySet.value.has(id) || allowedNameSet.value.has(id)
        }

        const l = normalize(label)
        const n = normalize(name)

        // We support matching by either tab label/name or by menu_key.
        return allowedNameSet.value.has(l) || allowedKeySet.value.has(n) || allowedKeySet.value.has(l)
    }

    const load = async () => {
        if (isLoading.value || isLoaded.value) return
        try {
            isLoading.value = true
            const ApiService = (await import('../services/api.js')).default
            const res = await ApiService.getUserTabAccess()
            const menus = res?.data?.menus

            if (Array.isArray(menus)) {
                allowedMenuKeys.value = menus.map(m => m.menu_key).filter(Boolean)
                allowedMenuNames.value = menus.map(m => m.menu).filter(Boolean)
            } else {
                allowedMenuKeys.value = []
                allowedMenuNames.value = []
            }

            isLoaded.value = true
        } catch (e) {
            // If API fails, treat as no additional restrictions (so user isn't blocked).
            allowedMenuKeys.value = []
            allowedMenuNames.value = []
            isLoaded.value = true
        } finally {
            isLoading.value = false
        }
    }

    return {
        isLoading,
        isLoaded,
        canViewTab,
        load,
    }
}

