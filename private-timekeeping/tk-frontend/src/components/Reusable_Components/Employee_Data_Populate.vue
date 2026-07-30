<template>
  <div class="employee-data-root">
    <!-- Render per-field to reuse in individual table columns -->
    <span v-if="field === 'empNo'" class="emp-no">{{ employeeNo }}</span>

    <div v-else-if="field === 'photo'" class="photo-container">
      <!-- Use el-avatar for both so real photos match placeholder size (same as Element Plus default) -->
      <el-avatar v-if="photo" :src="photo" :alt="name" size="large" />
      <el-avatar v-else :icon="UserFilled" size="large" />
    </div>

    <span v-else-if="field === 'name'" class="name">{{ name }}</span>

    <div v-else-if="field === 'namePosition'" class="name-pos">
      <span class="name">{{ name }}</span>
      <span class="position" v-if="position">{{ position }}</span>
    </div>

    <span v-else-if="field === 'department'" class="department">{{ department }}</span>

    <span v-else-if="field === 'employmentStatus'" class="employment">{{ employmentStatus }}</span>

    <span v-else-if="field === 'employmentType'" class="employment">{{ employmentType }}</span>

    <!-- Default combined rendering if used standalone -->
    <div v-else class="employee-data">
      <div class="line-top">
        <span class="emp-no">{{ employeeNo }}</span>
        <span class="name">{{ name }}</span>
      </div>
      <div class="line-bottom">
        <span class="position" v-if="position">{{ position }}</span>
        <span class="separator" v-if="position && department">•</span>
        <span class="department" v-if="department">{{ department }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { UserFilled } from '@element-plus/icons-vue'
import { formatEmployeeName } from '../../Composables/useNameFormatter'

const props = defineProps({
  employee: { type: Object, default: () => ({}) },
  // Which field to render: 'empNo' | 'photo' | 'name' | 'namePosition' | 'department' | 'employmentStatus'
  field: { type: String, default: 'namePosition' },
  // Optional direct props if not passing employee object
  name: { type: String, default: '' },
  position: { type: String, default: '' },
  department: { type: String, default: '' },
  employee_no: { type: [String, Number], default: '' },
  employment_type_name: { type: String, default: '' },
  photo: { type: String, default: '' },
})

const name = computed(() => {
  const fallback = props.name ?? ''
  return formatEmployeeName(props.employee, fallback)
})
const position = computed(() => props.employee?.position ?? props.position ?? '')
const department = computed(() => props.employee?.department ?? props.department ?? '')
const employeeNo = computed(() => props.employee?.employee_no ?? props.employee_no ?? '')
const employmentStatus = computed(() => props.employee?.employment_type_name ?? props.employment_type_name ?? '')
const employmentType = computed(() => props.employee?.employment_type ?? props.employment_type ?? '')
const photo = computed(() => {
  const photoData = props.employee?.photo ?? props.photo ?? ''
  if (photoData == null || photoData === '' || photoData === 'null') return ''
  // If photo data starts with 'data:', it's already a data URL
  if (photoData.startsWith('data:')) return photoData
  // If it's a full URL, use as-is
  if (photoData.startsWith('http://') || photoData.startsWith('https://')) return photoData
  // Otherwise, construct data URL from base64 data (assuming JPEG format)
  return `data:image/jpeg;base64,${photoData}`
})
const firstLetter = computed(() => {
  const fullName = name.value.trim()
  return fullName ? fullName.charAt(0).toUpperCase() : '?'
})
</script>

<style scoped>
.employee-data { display: flex; flex-direction: column; gap: 2px; }
.line-top { display: flex; align-items: baseline; gap: 8px; }
.emp-no { color: #6b7280; font-size: 12px; min-width: 64px; display: inline-block; }
.name { color: #111827; font-weight: 600; }
.line-bottom { color: #6b7280; font-size: 12px; display: flex; gap: 6px; }
.separator { color: #9ca3af; }
.name-pos { display: flex; flex-direction: column; }
.position { color: #6b7280; font-size: 12px; }
.department { color: #111827; }
.employment { color: #111827; }

.photo-container {
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
