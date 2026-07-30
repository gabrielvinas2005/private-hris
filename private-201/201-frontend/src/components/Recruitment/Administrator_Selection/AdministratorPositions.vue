<template>
  <el-card shadow="never">
    <template #header>
      <div class="flex items-center justify-between">
        <span class="font-bold">Administrator Selection - Positions</span>
        <div class="flex items-center gap-2">
          <el-button size="small">Print</el-button>
          <el-button size="small">Excel</el-button>
          <el-button size="small">PDF</el-button>
          <el-dropdown trigger="click">
            <el-button size="small">Column visibility</el-button>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-for="col in columns" :key="col.prop">
                  <el-checkbox v-model="col.show">{{ col.label }}</el-checkbox>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
          <el-input v-model="search" placeholder="Search" size="small" style="width: 260px" />
        </div>
      </div>
    </template>

    <el-table :data="filtered" size="small" stripe v-loading="loading">
      <el-table-column prop="position" label="Job Title" />
      <el-table-column label="Salary" width="180" align="center">
        <template #default="{ row }">
          <el-tag v-if="isNonPlantillaRow(row)" type="success" effect="dark">
            {{ formatNonPlantillaSalary(row.salary) }}
          </el-tag>
          <el-tag v-else type="primary" effect="dark">
            {{ displayGrade(row.grade) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="code" label="Item Code" width="140" />
      <el-table-column label="Publication Period" width="260">
        <template #default="{ row }">
          <div>From: {{ row.publication_from }}</div>
          <div>To: {{ row.publication_to }}</div>
        </template>
      </el-table-column>
      <el-table-column prop="department" label="Area of Assignment" />
      <el-table-column prop="total" label="Total Applicant" width="140" align="center" />
      <el-table-column label="View Applicant" width="140">
        <template #default="{ row }">
          <el-button size="small" type="primary" @click="$emit('select', row)">View</el-button>
        </template>
      </el-table-column>
    </el-table>
  </el-card>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})
defineEmits(['select'])

const search = ref('')
const filtered = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return props.items
  return props.items.filter((r) => 
    [r.code, r.position, r.department, r.eligibility].some(x => 
      String(x).toLowerCase().includes(q)
    )
  )
})

// Column visibility
const columns = ref([
  { prop: 'code', label: 'Item Code', show: true },
  { prop: 'position', label: 'Position', show: true },
  { prop: 'step', label: 'Step', show: true },
  { prop: 'grade', label: 'Grade', show: true },
  { prop: 'department', label: 'Department', show: true },
  { prop: 'eligibility', label: 'Eligibility', show: true },
  { prop: 'experience', label: 'Experience', show: true },
  { prop: 'training', label: 'Training', show: true },
  { prop: 'education', label: 'Education', show: true },
  { prop: 'publication', label: 'Publication Period', show: true },
  { prop: 'total', label: 'Total Applicants', show: true }
])
const columnsMap = computed(() => Object.fromEntries(columns.value.map(c => [c.prop, c])))

const isNonPlantillaRow = (row) => row?.is_plantilla === false || row?.is_plantilla === 0

const formatNonPlantillaSalary = (salary) => {
  if (salary === null || salary === undefined || salary === '') return 'N/A'
  const n = Number(salary)
  if (Number.isNaN(n)) return String(salary)
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(n)
}

const displayGrade = (grade) => {
  if (!grade && grade !== 0) return 'N/A'
  const text = String(grade).trim()
  // If backend already returns formatted text like "Salary Grade 1", use it as-is
  if (/^salary\s*grade/i.test(text)) return text
  // Otherwise, prefix consistently
  return `Salary Grade ${text}`
}
</script>

<style scoped>
.flex { display: flex; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.gap-2 { gap: 8px; }
.font-bold { font-weight: 700; }
</style>
