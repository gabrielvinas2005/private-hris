<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between mb-4 border-b border-slate-200 pb-3">
      <div>
        <h3 class="text-lg font-semibold text-slate-900">Disciplinary Records</h3>
        <p class="text-xs text-slate-500">
          Notices, incident reports, and corrective actions compliant with DOLE due process (Art. 297 Twin-Notice Rule).
        </p>
      </div>
      <div class="flex items-center gap-2">
        <el-button v-if="isEditable" type="primary" size="small" @click="addRecord">
          <el-icon class="mr-1"><Plus /></el-icon> Add Disciplinary Entry
        </el-button>
        <el-tag v-if="isEditable" type="warning" size="small">Edit Mode (HR Approval)</el-tag>
      </div>
    </div>

    <!-- Edit Mode Form -->
    <el-form v-if="isEditable" :model="localData">
      <el-card 
        v-for="(record, index) in localData" 
        :key="record.id || index" 
        shadow="hover" 
        class="mb-4 border-l-4 border-l-amber-500"
      >
        <div class="flex justify-between items-center mb-3 pb-2 border-b border-slate-100">
          <span class="font-semibold text-slate-700 text-sm">Disciplinary Record #{{ index + 1 }}</span>
          <el-button type="danger" size="small" text @click="removeRecord(index)">
            <el-icon class="mr-1"><Delete /></el-icon> Remove
          </el-button>
        </div>

        <el-row :gutter="20">
          <el-col :xs="24" :sm="12" :md="6">
            <el-form-item label="Incident / Action Date">
              <el-date-picker
                v-model="record.date"
                type="date"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                @change="syncData"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="10">
            <el-form-item label="Violation / Incident">
              <el-input 
                v-model="record.violation" 
                placeholder="e.g. Habitual Tardiness, AWOL, Insubordination"
                @input="syncData" 
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Action Taken">
              <el-select v-model="record.action_taken" placeholder="Select Action" style="width: 100%" @change="syncData">
                <el-option label="Notice to Explain (NTE)" value="Notice to Explain (NTE)" />
                <el-option label="Verbal Warning" value="Verbal Warning" />
                <el-option label="Written Warning / Reprimand" value="Written Warning / Reprimand" />
                <el-option label="Suspension (1–3 Days)" value="Suspension (1–3 Days)" />
                <el-option label="Suspension (4–15 Days)" value="Suspension (4–15 Days)" />
                <el-option label="Dismissal / Just Cause (Art. 297)" value="Dismissal / Just Cause (Art. 297)" />
                <el-option label="Exonerated / Case Closed" value="Exonerated / Case Closed" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item label="Issued By">
              <el-input v-model="record.issued_by" placeholder="e.g. HR Manager / Dept Head" @input="syncData" />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="24" :md="16">
            <el-form-item label="Status / Remarks">
              <el-input v-model="record.remarks" placeholder="Case status, explanation submitted, memo reference" @input="syncData" />
            </el-form-item>
          </el-col>
        </el-row>
      </el-card>

      <div v-if="localData.length === 0" class="text-center py-8 text-slate-400 bg-slate-50 rounded-lg border border-dashed border-slate-300">
        No disciplinary records on file.
      </div>
    </el-form>

    <!-- View Mode Table -->
    <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Date</th>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Violation / Incident</th>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Action Taken</th>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Issued By</th>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Status / Remarks</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="(item, idx) in localData" :key="idx" class="hover:bg-slate-50/80">
              <td class="px-6 py-4 whitespace-nowrap text-slate-900 font-medium">{{ formatDate(item.date) }}</td>
              <td class="px-6 py-4 font-semibold text-slate-800">{{ item.violation || '-' }}</td>
              <td class="px-6 py-4">
                <el-tag :type="getActionTag(item.action_taken)" size="small">
                  {{ item.action_taken || 'Notice' }}
                </el-tag>
              </td>
              <td class="px-6 py-4 text-slate-600">{{ item.issued_by || '-' }}</td>
              <td class="px-6 py-4 text-xs text-slate-500">{{ item.remarks || '-' }}</td>
            </tr>
            <tr v-if="localData.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                No disciplinary actions recorded. Employee has a clear record.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { Plus, Delete } from '@element-plus/icons-vue'

export default {
  name: 'DisciplinaryRecords',
  components: { Plus, Delete },
  props: {
    employee: { type: Object, default: () => ({}) },
    isEditMode: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    formData: { type: Object, default: () => ({}) }
  },
  emits: ['update:form-data'],
  setup(props, { emit }) {
    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const localData = ref(
      props.formData?.disciplinaryRecords && Array.isArray(props.formData.disciplinaryRecords)
        ? [...props.formData.disciplinaryRecords]
        : (props.employee?.disciplinary_records || [])
    )

    watch(() => props.formData?.disciplinaryRecords, (newVal) => {
      if (newVal && Array.isArray(newVal)) {
        localData.value = [...newVal]
      }
    }, { deep: true })

    const addRecord = () => {
      localData.value.push({
        date: '',
        violation: '',
        action_taken: 'Notice to Explain (NTE)',
        issued_by: '',
        remarks: ''
      })
      syncData()
    }

    const removeRecord = (index) => {
      localData.value.splice(index, 1)
      syncData()
    }

    const syncData = () => {
      emit('update:form-data', { field: 'disciplinaryRecords', value: [...localData.value] })
    }

    const formatDate = (dateStr) => {
      if (!dateStr) return '-'
      return new Date(dateStr).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
    }

    const getActionTag = (action) => {
      if (!action) return 'info'
      const a = action.toLowerCase()
      if (a.includes('dismissal')) return 'danger'
      if (a.includes('suspension') || a.includes('warning')) return 'warning'
      if (a.includes('exonerated') || a.includes('closed')) return 'success'
      return 'info'
    }

    return {
      isEditable,
      localData,
      addRecord,
      removeRecord,
      syncData,
      formatDate,
      getActionTag
    }
  }
}
</script>
