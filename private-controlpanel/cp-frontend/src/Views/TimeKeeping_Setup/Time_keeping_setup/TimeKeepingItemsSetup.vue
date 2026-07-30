<template>
  <MainLayout>
    <template #header>
      <div class="page-header">
        <div>
          <div class="title">Time Keeping Setup</div>
          <p class="subtitle">Assign standard work days, work hours, and holiday pay preference per employment type.</p>
        </div>
        <div class="header-actions">
          <el-button :icon="Refresh" text type="default" @click="handleRefresh" :loading="loading">Refresh</el-button>
          <el-button type="success" :disabled="!hasSelection" :loading="saving" @click="handleSave">Save Changes</el-button>
        </div>
      </div>
    </template>

    <el-row :gutter="20" class="layout-grid">
      <el-col :xs="24" :md="9" :lg="8">
        <el-card shadow="never" class="panel-card employment-panel" v-loading="loading">
          <div class="panel-header">
            <div>
              <h3>Employment Types</h3>
              <p>Select an employment type to configure its default schedule.</p>
            </div>
            <el-tag size="small" type="info">{{ filteredEmploymentTypes.length }} Types</el-tag>
          </div>
          <el-input
            v-model="searchTerm"
            placeholder="Search employment type..."
            clearable
            class="search-input"
            :prefix-icon="Search"
          />
          <el-scrollbar class="types-scroll" height="460px">
            <div
              v-for="type in filteredEmploymentTypes"
              :key="type.id"
              class="type-row"
              :class="{ active: type.id === selectedEmploymentTypeId }"
              @click="handleSelectType(type.id)"
            >
              <div class="type-avatar">{{ getInitial(type.name) }}</div>
              <div class="type-content">
                <div class="type-info">
                  <span class="name">{{ type.name }}</span>
                  <small class="description">{{ type.description || 'No description provided' }}</small>
                </div>
                <div class="type-metrics">
                  <template v-if="scheduleDisplay[type.id]">
                    <el-tag size="small" type="info">{{ scheduleDisplay[type.id].work_days ?? 0 }} days/wk</el-tag>
                    <el-tag size="small" type="warning">{{ scheduleDisplay[type.id].work_hours ?? 0 }} hrs/day</el-tag>
                  </template>
                  <span v-else class="no-config">No schedule set</span>
                </div>
              </div>
              <el-tag v-if="type.id === selectedEmploymentTypeId" type="success" size="small">Selected</el-tag>
            </div>
            <div v-if="!filteredEmploymentTypes.length" class="empty-hint">
              <el-empty description="No employment types matched your search" :image-size="80" />
            </div>
          </el-scrollbar>
        </el-card>
      </el-col>

      <el-col :xs="24" :md="15" :lg="16">
        <el-card shadow="hover" class="panel-card schedule-panel" v-loading="loading">
          <div class="panel-header">
            <div>
              <h3>{{ activeEmploymentType?.name || 'Select an employment type' }}</h3>
              <p v-if="activeEmploymentType">{{ activeEmploymentType.description || 'Configure default work schedule below.' }}</p>
              <p v-else>Please choose an employment type from the list to start configuring.</p>
            </div>
            <el-tag v-if="activeEmploymentType" type="info" size="small">{{ activeEmploymentType.category || 'Employment Type' }}</el-tag>
          </div>

          <el-empty v-if="!hasSelection" description="Choose an employment type on the left to configure its schedule." />

          <div v-else class="schedule-content">
            <div class="summary-grid">
              <div class="summary-card">
                <span>Default Work Days</span>
                <strong>{{ form.work_days || 0 }}</strong>
              </div>
              <div class="summary-card">
                <span>Work Hours / Day</span>
                <strong>{{ form.work_hours || 0 }}</strong>
              </div>
              <div class="summary-card">
                <span>Holiday Pay</span>
                <strong>{{ form.with_holiday_pay ? 'With Pay' : 'No Pay' }}</strong>
              </div>
            </div>

            <el-form label-position="top" class="schedule-form">
              <el-row :gutter="20">
                <el-col :xs="24" :sm="12">
                  <el-form-item label="Work Days per Week">
                    <el-input-number v-model="form.work_days" :min="0" :max="7" controls-position="right" />
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="12">
                  <el-form-item label="Work Hours per Day">
                    <el-input-number v-model="form.work_hours" :min="0" :max="24" :step="0.5" controls-position="right" />
                  </el-form-item>
                </el-col>
              </el-row>
              <el-row :gutter="20">
                <el-col :xs="24" :sm="12">
                  <el-form-item label="Holiday Pay Eligibility">
                    <div class="toggle-row">
                      <el-switch v-model="form.with_holiday_pay" active-text="With Holiday Pay" inactive-text="No Holiday Pay" />
                    </div>
                  </el-form-item>
                </el-col>
                <el-col :xs="24" :sm="12">
                  <el-form-item label="Notes">
                    <el-alert
                      title="Work days and hours will be applied to newly created schedules for this employment type."
                      type="info"
                      :closable="false"
                    />
                  </el-form-item>
                </el-col>
              </el-row>
            </el-form>
          </div>
        </el-card>
      </el-col>
    </el-row>
  </MainLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useTimekeepingSetup } from '../../../composables/useTimekeepingSetup.js'
import { Search, Refresh } from '@element-plus/icons-vue'

const {
  employmentTypes,
  employmentTypeSchedules,
  selectedEmploymentTypeId,
  form,
  loading,
  saving,
  hasSelection,
  fetchEmploymentTypes,
  save
} = useTimekeepingSetup()

const searchTerm = ref('')

const filteredEmploymentTypes = computed(() => {
  if (!searchTerm.value) return employmentTypes.value
  const needle = searchTerm.value.toLowerCase()
  return employmentTypes.value.filter(et => (et.name || '').toLowerCase().includes(needle))
})

const activeEmploymentType = computed(() =>
  employmentTypes.value.find(et => et.id === selectedEmploymentTypeId.value) || null
)

const scheduleDisplay = computed(() => {
  const map = {}
  employmentTypes.value.forEach(type => {
    if (type.id === selectedEmploymentTypeId.value) {
      map[type.id] = {
        work_days: Number(form.value.work_days ?? 0),
        work_hours: Number(form.value.work_hours ?? 0),
        with_holiday_pay: !!form.value.with_holiday_pay
      }
    } else if (employmentTypeSchedules.value[type.id]) {
      map[type.id] = employmentTypeSchedules.value[type.id]
    }
  })
  return map
})

function handleSelectType(id) {
  if (selectedEmploymentTypeId.value !== id) {
    selectedEmploymentTypeId.value = id
  }
}

function getInitial(name = '') {
  return name.trim().charAt(0).toUpperCase() || '?'
}

function handleRefresh() {
  fetchEmploymentTypes()
}

async function handleSave() {
  await save()
}

onMounted(fetchEmploymentTypes)
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}
.title {
  font-weight: 600;
  font-size: 20px;
  margin-bottom: 4px;
}
.subtitle {
  margin: 0;
  color: #6b7280;
}
.header-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}
.layout-grid {
  margin-top: 10px;
}
.panel-card {
  border-radius: 12px;
  min-height: 520px;
}
.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 18px;
}
.panel-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}
.panel-header p {
  margin: 4px 0 0 0;
  color: #6b7280;
  font-size: 13px;
}
.search-input {
  margin-bottom: 12px;
}
.types-scroll {
  flex: 1;
  min-height: 0;
}
.type-row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 12px;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid transparent;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-bottom: 8px;
  background: #f8fafc;
}
.type-row:hover {
  border-color: #cbd5f5;
  background: #f0f4ff;
}
.type-row.active {
  border-color: #2563eb;
  background: #dbeafe;
}
.type-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #2563eb;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}
.type-info .name {
  display: block;
  font-weight: 600;
  color: #1f2937;
}
.type-info .description {
  color: #6b7280;
}
.type-content {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.type-metrics {
  display: flex;
  gap: 6px;
  align-items: center;
  flex-wrap: wrap;
  justify-content: flex-start;
}
.type-metrics .no-config {
  font-size: 12px;
  color: #9ca3af;
}
.empty-hint {
  margin-top: 30px;
}
.schedule-panel .schedule-content {
  margin-top: 10px;
}
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
.summary-card {
  background: #f3f4f6;
  border-radius: 10px;
  padding: 12px 16px;
  display: flex;
  flex-direction: column;
}
.summary-card span {
  font-size: 12px;
  color: #6b7280;
}
.summary-card strong {
  font-size: 22px;
  margin-top: 4px;
  color: #111827;
}
.schedule-form {
  margin-top: 10px;
}
.toggle-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.employment-panel :deep(.el-card__body) {
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 520px;
}
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }
  .header-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
