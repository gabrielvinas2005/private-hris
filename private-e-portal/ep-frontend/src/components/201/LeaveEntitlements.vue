<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between mb-4 border-b border-slate-200 pb-3">
      <div>
        <h3 class="text-lg font-semibold text-slate-900">Leave Entitlements & Balances</h3>
        <p class="text-xs text-slate-500">
          Philippine statutory leaves (Labor Code & Special Laws) and company leave allocations.
        </p>
      </div>
      <el-tag v-if="isEditable" type="warning" size="small">Edit Mode (Subject to HR Approval)</el-tag>
    </div>

    <!-- Edit Mode Form -->
    <el-form v-if="isEditable" :model="localLeaves">
      <el-card shadow="never" class="mb-4 border-slate-200">
        <el-table :data="localLeaves" stripe style="width: 100%">
          <el-table-column prop="leave_type" label="Leave Type" min-width="180">
            <template #default="{ row }">
              <span class="font-medium text-slate-800">{{ row.leave_type }}</span>
            </template>
          </el-table-column>
          <el-table-column label="Annual Entitlement (days)" width="180">
            <template #default="{ row, $index }">
              <el-input-number 
                v-model="row.entitled_days" 
                :min="0" 
                :step="0.5"
                size="small"
                @change="updateField(`leaves.${$index}.entitled_days`, $event)"
              />
            </template>
          </el-table-column>
          <el-table-column label="Carry-Over (days)" width="160">
            <template #default="{ row, $index }">
              <el-input-number 
                v-model="row.carry_over" 
                :min="0" 
                :step="0.5"
                size="small"
                @change="updateField(`leaves.${$index}.carry_over`, $event)"
              />
            </template>
          </el-table-column>
          <el-table-column label="Used YTD (days)" width="150">
            <template #default="{ row, $index }">
              <el-input-number 
                v-model="row.used_days" 
                :min="0" 
                :step="0.5"
                size="small"
                @change="updateField(`leaves.${$index}.used_days`, $event)"
              />
            </template>
          </el-table-column>
          <el-table-column label="Remarks" min-width="180">
            <template #default="{ row, $index }">
              <el-input 
                v-model="row.remarks" 
                size="small" 
                placeholder="Optional notes"
                @input="updateField(`leaves.${$index}.remarks`, $event)"
              />
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </el-form>

    <!-- View Mode Table -->
    <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Leave Type</th>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Annual Entitlement</th>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Carry-Over</th>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Used YTD</th>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Balance</th>
              <th class="px-6 py-3.5 text-left font-semibold text-slate-600">Remarks</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="leave in displayLeaves" :key="leave.leave_type" class="hover:bg-slate-50/80">
              <td class="px-6 py-4 font-semibold text-slate-900">
                {{ leave.leave_type }}
              </td>
              <td class="px-6 py-4 text-left text-slate-700 font-medium">{{ leave.entitled_days }}</td>
              <td class="px-6 py-4 text-left text-slate-500">{{ leave.carry_over || 0 }}</td>
              <td class="px-6 py-4 text-left text-slate-500">{{ leave.used_days || 0 }}</td>
              <td class="px-6 py-4 text-left font-bold" :class="getBalanceClass(leave)">
                {{ computeBalance(leave) }}
              </td>
              <td class="px-6 py-4 text-xs text-slate-500">{{ leave.remarks || '-' }}</td>
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
  name: 'LeaveEntitlements',
  props: {
    employee: { type: Object, default: () => ({}) },
    isEditMode: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    formData: { type: Object, default: () => ({}) }
  },
  emits: ['update:form-data'],
  setup(props, { emit }) {
    const isEditable = computed(() => props.isEditMode && props.canUpdate)

    const defaultLeaves = [
      { leave_type: 'Vacation Leave (VL)', basis: 'Company Policy / SIL Art. 95', entitled_days: 12, carry_over: 0, used_days: 0, remarks: 'Cumulative / convertible' },
      { leave_type: 'Sick Leave (SL)', basis: 'Company Policy / SIL Art. 95', entitled_days: 12, carry_over: 0, used_days: 0, remarks: 'Medical certificate required' },
      { leave_type: 'Emergency Leave', basis: 'Company Policy', entitled_days: 3, carry_over: 0, used_days: 0, remarks: 'Immediate family emergencies' },
      { leave_type: 'Maternity Leave', basis: 'RA 11210 (105 Days)', entitled_days: 105, carry_over: 0, used_days: 0, remarks: 'Female employees' },
      { leave_type: 'Paternity Leave', basis: 'RA 8187 (7 Days)', entitled_days: 7, carry_over: 0, used_days: 0, remarks: 'Married male employees' },
      { leave_type: 'Solo Parent Leave', basis: 'RA 8972 / RA 11861', entitled_days: 7, carry_over: 0, used_days: 0, remarks: 'With valid Solo Parent ID' },
      { leave_type: 'VAWC Leave', basis: 'RA 9262 (10 Days)', entitled_days: 10, carry_over: 0, used_days: 0, remarks: 'Victims of violence' },
      { leave_type: 'Special Leave for Women', basis: 'RA 9710 (2 Months)', entitled_days: 60, carry_over: 0, used_days: 0, remarks: 'Gynecological surgery' },
      { leave_type: 'Bereavement Leave', basis: 'Company Policy', entitled_days: 3, carry_over: 0, used_days: 0, remarks: 'Immediate family' }
    ]

    const localLeaves = ref(
      props.formData?.leaves && Array.isArray(props.formData.leaves)
        ? [...props.formData.leaves]
        : defaultLeaves
    )

    watch(() => props.formData?.leaves, (newVal) => {
      if (newVal && Array.isArray(newVal)) {
        localLeaves.value = [...newVal]
      }
    }, { deep: true })

    const displayLeaves = computed(() => {
      return props.isEditMode ? localLeaves.value : defaultLeaves
    })

    const computeBalance = (item) => {
      const total = Number(item.entitled_days || 0) + Number(item.carry_over || 0)
      return (total - Number(item.used_days || 0)).toFixed(1)
    }

    const getBalanceClass = (item) => {
      const bal = Number(computeBalance(item))
      if (bal <= 0) return 'text-red-600'
      if (bal < 3) return 'text-amber-600'
      return 'text-emerald-600'
    }

    const updateField = (field, val) => {
      emit('update:form-data', { field: 'leaves', value: [...localLeaves.value] })
    }

    return {
      isEditable,
      localLeaves,
      displayLeaves,
      computeBalance,
      getBalanceClass,
      updateField
    }
  }
}
</script>
