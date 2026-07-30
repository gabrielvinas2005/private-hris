<template>
  <div class="mb-3">
    <el-form :inline="true">
      <el-form-item v-if="showEmployee" label="Employee">
        <el-autocomplete
          v-model="searchText"
          :fetch-suggestions="getSuggestions"
          placeholder="Search employee (name or no.)"
          clearable
          :trigger-on-focus="true"
          style="width: 28ch;"
          @select="onEmployeeSelect"
          @change="emitFilter"
        />
      </el-form-item>
      <el-form-item label="Department">
        <el-select v-model="selectedDepartment" placeholder="All Departments" filterable clearable @change="emitFilter">
          <el-option
            v-for="d in departmentOptions"
            :key="d.id"
            :label="d.name"
            :value="d.id"
          />
        </el-select>
      </el-form-item>
      <el-form-item v-if="showEmploymentType" label="Employment Status">
        <el-select v-model="selectedEmploymentType" placeholder="ALL TYPES" filterable clearable @change="emitFilter">
          <el-option
            v-for="et in employmentTypeOptions"
            :key="et.id"
            :label="et.name"
            :value="et.id"
          />
        </el-select>
      </el-form-item>
      
    </el-form>
  </div>
</template>

<script setup>
import { ref, watch, toRefs, onMounted, computed } from 'vue'
import api from '@/services/api'

const props = defineProps({
  employmentTypes: { type: Array, default: () => [] },
  modelValue: { type: Object, default: () => ({ search: '', departmentId: null, employmentTypeId: null }) },
  employees: { type: Array, default: () => [] },
  showEmploymentType: { type: Boolean, default: true },
  showEmployee: { type: Boolean, default: true }
})
const emit = defineEmits(['update:modelValue', 'change'])

const { employmentTypes } = toRefs(props)
const searchText = ref(props.modelValue.search || '')
const selectedDepartment = ref(props.modelValue.departmentId || null)
const selectedEmploymentType = ref(props.modelValue.employmentTypeId || null)
const departments = ref([])

watch(() => props.modelValue, (val) => {
  searchText.value = val?.search ?? ''
  selectedDepartment.value = val?.departmentId ?? null
  selectedEmploymentType.value = val?.employmentTypeId ?? null
})

const emitFilter = () => {
  const payload = { 
    search: searchText.value, 
    departmentId: selectedDepartment.value,
    employmentTypeId: selectedEmploymentType.value
  }
  emit('update:modelValue', payload)
  emit('change', payload)
}

const clearFilters = () => {
  searchText.value = ''
  selectedDepartment.value = null
  selectedEmploymentType.value = null
  emitFilter()
}

const getSuggestions = (query, cb) => {
  const q = (query || '').toLowerCase().trim()
  if (!q) return cb([])
  const list = (props.employees || []).filter(e =>
    `${e.name || ''} ${e.employee_no || ''}`.toLowerCase().includes(q)
  ).slice(0, 10).map(e => ({ value: `${e.name} (${e.employee_no || 'N/A'})`, id: e.id }))
  cb(list)
}

const onEmployeeSelect = (item) => {
  searchText.value = item?.value || ''
  emitFilter()
}

// Build department options from employees prop
const departmentOptions = computed(() => {
  const list = (departments.value || []).map(d => ({ id: d.id, name: d.name }))
  const unique = []
  const seen = new Set()
  for (const d of list) {
    if (!seen.has(d.id)) { seen.add(d.id); unique.push(d) }
  }
  unique.sort((a,b) => a.name.localeCompare(b.name))
  return [{ id: '', name: 'ALL DEPARTMENTS' }, ...unique]
})

const employmentTypeOptions = computed(() => {
  const list = (employmentTypes.value || []).map(et => ({ id: et.id ?? et.value ?? '', name: et.name ?? et.label ?? '' }))
  const unique = []
  const seen = new Set()
  for (const e of list) { if (!seen.has(e.id)) { seen.add(e.id); unique.push(e) } }
  unique.sort((a,b) => a.name.localeCompare(b.name))
  return [{ id: '', name: 'ALL TYPES' }, ...unique]
})

onMounted(async () => {
  try {
    const res = await api.get('/departments')
    const payload = res?.data ?? res
    const items = Array.isArray(payload) ? payload : (payload?.data || payload?.departments || [])
    departments.value = items.filter(d => d.active === 1 || d.active === true || d.active === '1')
  } catch (e) {
    // Fallback to deriving from employees list if API fails
    const map = new Map()
    for (const e of (props.employees || [])) {
      const id = e.department_id ?? e.departmentId
      const name = e.department_name ?? e.department ?? ''
      if (id != null && !map.has(id)) map.set(id, { id, name: String(name) })
    }
    departments.value = Array.from(map.values())
  }
})
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
</style>
