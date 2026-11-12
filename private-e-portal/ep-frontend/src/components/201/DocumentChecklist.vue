<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between mb-4 border-b border-slate-200 pb-3">
      <div>
        <h3 class="text-lg font-semibold text-slate-900">Documentary Requirements Checklist</h3>
        <p class="text-xs text-slate-500">
          Standard 201 file pre-employment, statutory, legal, and company compliance documents.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <div class="text-xs font-semibold px-3 py-1 bg-slate-100 text-slate-700 rounded-full">
          Progress: {{ completedCount }} / {{ items.length }} ({{ Math.round((completedCount / items.length) * 100) }}%)
        </div>
        <el-tag v-if="isEditable" type="warning" size="small">Edit Mode (HR Approval)</el-tag>
      </div>
    </div>

    <!-- Edit Mode Table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-5 py-3 text-left font-semibold text-slate-600">Document Name</th>
              <th class="px-5 py-3 text-center font-semibold text-slate-600 w-36">Submitted?</th>
              <th class="px-5 py-3 text-left font-semibold text-slate-600 w-48">Date Submitted</th>
              <th class="px-5 py-3 text-left font-semibold text-slate-600">Remarks / Reference</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="(item, idx) in items" :key="item.name" class="hover:bg-slate-50/80">
              <td class="px-5 py-3.5 font-medium text-slate-900">
                {{ idx + 1 }}. {{ item.name }}
              </td>
              <td class="px-5 py-3.5 text-center">
                <el-switch 
                  v-if="isEditable" 
                  v-model="item.submitted" 
                  size="small"
                  @change="syncChecklist" 
                />
                <el-tag 
                  v-else 
                  :type="item.submitted ? 'success' : 'info'" 
                  size="small"
                >
                  {{ item.submitted ? 'Submitted' : 'Pending' }}
                </el-tag>
              </td>
              <td class="px-5 py-3.5">
                <el-date-picker
                  v-if="isEditable"
                  v-model="item.date_submitted"
                  type="date"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  size="small"
                  style="width: 100%"
                  @change="syncChecklist"
                />
                <span v-else class="text-slate-600 text-xs">{{ formatDate(item.date_submitted) }}</span>
              </td>
              <td class="px-5 py-3.5">
                <el-input
                  v-if="isEditable"
                  v-model="item.remarks"
                  placeholder="Notes, copy type"
                  size="small"
                  @input="syncChecklist"
                />
                <span v-else class="text-slate-500 text-xs">{{ item.remarks || '-' }}</span>
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

export default {
  name: 'DocumentChecklist',
  props: {
    employee: { type: Object, default: () => ({}) },
    isEditMode: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    formData: { type: Object, default: () => ({}) }
  },
  emits: ['update:form-data'],
  setup(props, { emit }) {
    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const defaultChecklist = [
      { name: 'Resume / Curriculum Vitae', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Application Form', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Employment Contract / Job Offer', submitted: false, date_submitted: '', remarks: '' },
      { name: 'PSA Birth Certificate', submitted: false, date_submitted: '', remarks: '' },
      { name: 'PSA Marriage Certificate (if married)', submitted: false, date_submitted: '', remarks: '' },
      { name: "Dependents' PSA Birth Certificates", submitted: false, date_submitted: '', remarks: '' },
      { name: 'Diploma / Transcript of Records', submitted: false, date_submitted: '', remarks: '' },
      { name: 'NBI Clearance', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Police Clearance', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Barangay Clearance', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Pre-employment Medical Exam', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Drug Test Result', submitted: false, date_submitted: '', remarks: '' },
      { name: '2×2 ID Photos', submitted: false, date_submitted: '', remarks: '' },
      { name: 'SSS E-1 / SS Number Proof', submitted: false, date_submitted: '', remarks: '' },
      { name: 'PhilHealth MDR / Proof of PIN', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Pag-IBIG MDF / Proof of MID', submitted: false, date_submitted: '', remarks: '' },
      { name: 'TIN / BIR Form 1902 or 2305', submitted: false, date_submitted: '', remarks: '' },
      { name: 'BIR Form 2316 from Previous Employer', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Certificate of Employment (Previous)', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Valid Government ID (photocopy)', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Data Privacy Consent Form', submitted: false, date_submitted: '', remarks: '' },
      { name: 'Company Handbook Acknowledgment', submitted: false, date_submitted: '', remarks: '' }
    ]

    const items = ref(
      props.formData?.checklist && Array.isArray(props.formData.checklist)
        ? [...props.formData.checklist]
        : defaultChecklist
    )

    watch(() => props.formData?.checklist, (newVal) => {
      if (newVal && Array.isArray(newVal)) {
        items.value = [...newVal]
      }
    }, { deep: true })

    const completedCount = computed(() => {
      return items.value.filter(i => i.submitted).length
    })

    const syncChecklist = () => {
      emit('update:form-data', { field: 'checklist', value: [...items.value] })
    }

    const formatDate = (date) => {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
    }

    return {
      isEditable,
      items,
      completedCount,
      syncChecklist,
      formatDate
    }
  }
}
</script>
