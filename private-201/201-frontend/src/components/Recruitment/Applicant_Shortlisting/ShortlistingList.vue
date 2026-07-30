<template>
  <PageScaffold title="Applicant Shortlisting" subtitle="Review and update applicant status">
    <el-card shadow="never">
      <el-tabs v-model="activeTab" class="mb-4">
        <el-tab-pane label="List" name="list">
          <el-table :data="applicants" v-loading="loading" size="small" stripe>
        <el-table-column label="Photo" width="100" align="center">
          <template #default="{ row }">
            <el-avatar :size="48" :src="photoSrc(row.photo)" shape="square">
              <el-icon><User /></el-icon>
            </el-avatar>
          </template>
        </el-table-column>
        <el-table-column label="Name">
          <template #default="{ row }">
            {{ (row.first_name || '') + ' ' + (row.middle_name || '') + ' ' + (row.last_name || '') }}
          </template>
        </el-table-column>
        <el-table-column prop="position" label="Applied Position" />
        <el-table-column label="Status" width="240">
          <template #default="{ row }">
            <el-select v-model="statusSelection[rowListKey(row)]" placeholder="Reviewed" size="small" clearable>
              <el-option label="Reviewed" :value="'reviewed'" />
              <el-option label="Shortlisted" :value="'shortlisted'" />
              <el-option label="For Reference" :value="'reference'" />
            </el-select>
          </template>
        </el-table-column>
      </el-table>
      
          <div class="mt-4 flex justify-end">
            <el-button type="primary" :loading="processing" @click="saveChanges">Save Changes</el-button>
          </div>
        </el-tab-pane>
        
        <el-tab-pane label="Shortlisted" name="shortlisted">
          <el-table :data="shortlistedApplicants" v-loading="loading" size="small" stripe>
            <el-table-column label="Photo" width="100" align="center">
              <template #default="{ row }">
                <el-avatar :size="48" :src="photoSrc(row.photo)" shape="square">
                  <el-icon><User /></el-icon>
                </el-avatar>
              </template>
            </el-table-column>
            <el-table-column label="Name">
              <template #default="{ row }">
                {{ (row.first_name || '') + ' ' + (row.middle_name || '') + ' ' + (row.last_name || '') }}
              </template>
            </el-table-column>
            <el-table-column prop="position" label="Applied Position" />
            <el-table-column label="Status" width="240">
              <template #default="{ row }">
                <el-tag :type="row.application_status_id === 5 ? 'primary' : 'success'">
                  {{ row.application_status || 'Shortlisted' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="200">
              <template #default="{ row }">
                <el-button
                  size="small"
                  type="success"
                  :disabled="Number(row.application_status_id) === 6"
                  :loading="proceedingKey === rowListKey(row)"
                  @click="onProceed(row)"
                >
                  Proceed
                </el-button>
                <el-button size="small" type="danger" @click="removeFromShortlist(row)">Remove</el-button>
              </template>
            </el-table-column>
          </el-table>

        </el-tab-pane>
      </el-tabs>
    </el-card>
  </PageScaffold>
</template>

<script setup>
import { reactive, onMounted, ref, computed } from 'vue'
import { User } from '@element-plus/icons-vue'
import PageScaffold from '../../PageScaffold.vue'
import { useApplicantShortlisting } from '../../../composable/useApplicantShortlisting.js'

const { loading, processing, proceedingKey, applicants, shortlisted, fetchAll, add, remove, proceedNextStep } =
  useApplicantShortlisting()

// Photo src: backend may return base64 (with or without leading slash), data URL, or file path
const photoSrc = (photo) => {
  if (!photo) return ''
  if (typeof photo !== 'string') return ''
  if (photo.startsWith('data:')) return photo
  if (photo.startsWith('http')) return photo
  // Backend sometimes returns base64 with leading slash (e.g. "/9j/4AAQ..."); using that as URL causes 431
  if (photo.startsWith('/') && /^\/[A-Za-z0-9+/=]+$/.test(photo) && photo.length > 20) return `data:image/jpeg;base64,${photo}`
  if (photo.startsWith('/')) return photo // real path e.g. /storage/...
  return `data:image/jpeg;base64,${photo}`
}

const activeTab = ref('list')
const statusSelection = reactive({})

// Unique key per (applicant, position) so one applicant can be shortlisted for one job but not another
const rowListKey = (row) => `${row.id}_${row.position_applied_id ?? row.plantilla_id ?? ''}`

// Use the shortlisted data from the composable
const shortlistedApplicants = computed(() => shortlisted.value || [])

const saveChanges = async () => {
  const changes = []

  for (const [key, status] of Object.entries(statusSelection)) {
    if (!status) continue
    const parts = key.split('_')
    const applicantId = parseInt(parts[0], 10)
    const positionAppliedId = parts[1] ? parseInt(parts[1], 10) : null
    if (!applicantId) continue
    changes.push({ applicantId, positionAppliedId: positionAppliedId || null, status })
  }

  if (changes.length === 0) return

  for (const change of changes) {
    if (change.status === 'shortlisted') {
      await add(change.applicantId, 0, 1, change.positionAppliedId || null)
    }
  }
  Object.keys(statusSelection).forEach((k) => delete statusSelection[k])
  await fetchAll()
}

const removeFromShortlist = async (row) => {
  const shortlistedId = row.shortlisted_id ?? row.id
  await remove(shortlistedId)
  const key = rowListKey(row)
  if (statusSelection[key]) delete statusSelection[key]
  await fetchAll()
}

const onProceed = async (row) => {
  const positionAppliedId = row.position_applied_id ?? row.plantilla_id ?? null
  await proceedNextStep(row.id, positionAppliedId)
}

onMounted(fetchAll)
</script>

<style scoped>
.mt-4 { margin-top: 1rem; }
.mb-4 { margin-bottom: 1rem; }
.flex { display: flex; }
.justify-end { justify-content: flex-end; }
</style>
