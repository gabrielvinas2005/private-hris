<template>
  <div class="space-y-5">
    <!-- Header with Balance Summary Cards -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-6">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">Leave Applications & Balances</h3>
            <p class="text-xs font-medium text-slate-500">Monitor remaining leave credits, track request status, and submit leave applications</p>
          </div>
        </div>
        
        <el-button 
          type="primary" 
          class="!rounded-xl font-semibold !px-5 !py-2.5 shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/35 hover:-translate-y-0.5 transition-all duration-200" 
          @click="openAddLeave"
        >
          <span class="text-sm">+ File Leave Application</span>
        </el-button>
      </div>

      <!-- Leave Balances Grid -->
      <div v-if="displayedLeaveBalances.length > 0" class="pt-2">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Available Leave Credits</h4>
          <span class="text-[11px] font-semibold text-indigo-700 bg-indigo-50 px-3 py-1 rounded-full border border-indigo-100">
            {{ displayedLeaveBalances.length }} Active Credit Categories
          </span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5">
          <div
            v-for="b in displayedLeaveBalances"
            :key="b.type"
            class="p-4 bg-gradient-to-br from-slate-50 via-white to-indigo-50/20 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-indigo-300 hover:-translate-y-0.5 transition-all duration-200 text-center group"
          >
            <div class="text-2xl font-black text-indigo-600   tracking-tight group-hover:scale-105 transition-transform duration-200">{{ formatCredits(b.balance) }}</div>
            <div class="text-[11px] font-bold text-slate-700 truncate mt-1 group-hover:text-indigo-900 transition-colors">{{ b.type }}</div>
            <div class="mt-2.5 w-full bg-slate-200/70 h-1.5 rounded-full overflow-hidden">
              <div class="bg-indigo-500 h-full rounded-full transition-all duration-500" :style="{ width: Math.min(100, Math.max(12, (b.balance / 15) * 100)) + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Status Filters Bar -->
      <div class="flex items-center gap-3 flex-wrap pt-4 border-t border-slate-100">
        <el-input 
          v-model="state.search" 
          size="default" 
          placeholder="Search leave type or reason..." 
          clearable 
          style="width: 280px;"
          class="!rounded-xl"
        >
          <template #prefix>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
          </template>
        </el-input>

        <div class="flex items-center bg-slate-100 p-1 rounded-xl gap-1 border border-slate-200/60">
          <button 
            v-for="st in [
              { label: 'All', val: '' },
              { label: 'Pending', val: 'pending' },
              { label: 'Approved', val: 'approved' },
              { label: 'Disapproved', val: 'disapproved' },
              { label: 'Cancelled', val: 'cancelled' }
            ]" 
            :key="st.val"
            @click="state.statusFilter = st.val"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200"
            :class="state.statusFilter === st.val ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'"
          >
            {{ st.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Leave Application Table -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80">
      <LeaveTable
        v-if="filteredLeaves.length > 0"
        :leaves="filteredLeaves"
        :is-approver="state.isApprover"
        :status-filter="state.statusFilter"
        @edit="openEditModal"
        @delete="deleteLeave"
        @cancel="cancelLeave"
        @download="downloadAttachment"
        @print="showInlinePrint"
      />
      <el-empty v-else description="No leave applications found matching your criteria" class="my-6">
        <template #image>
          <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400 border border-slate-200">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
        </template>
      </el-empty>
    </div>

    <!-- Add Leave Form Modal -->
    <el-dialog
      v-model="uiState.showAddModal"
      width="640px"
      class="!rounded-3xl overflow-hidden shadow-2xl"
      :close-on-click-modal="false"
      @close="closeAddLeave"
      :show-close="true"
    >
      <template #header>
        <div class="flex items-center gap-3.5 pb-3 border-b border-slate-100">
          <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white shadow-md shadow-indigo-500/25">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900 leading-tight">File Leave Application</h3>
            <p class="text-xs font-medium text-slate-500">Submit a leave request to deduct from your active credits</p>
          </div>
        </div>
      </template>

      <el-form label-position="top" size="default" class="pt-2 space-y-2">
        <el-row :gutter="16">
          <el-col :xs="24" :md="12">
            <el-form-item required>
              <template #label>
                <span class="text-xs font-bold text-slate-700">Leave Type <span class="text-rose-500">*</span></span>
              </template>
              <el-select
                v-model="formData.addForm.leave_type_id"
                placeholder="Select Leave Type"
                filterable
                class="w-full !rounded-xl"
                @change="handleAddLeaveTypeChange"
              >
                <el-option
                  v-for="lt in state.leaveTypes"
                  :key="lt.id"
                  :label="lt.name + (hasZeroCredits(lt) ? ' (No Credits)' : '')"
                  :value="String(lt.id)"
                  :disabled="hasZeroCredits(lt)"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :xs="24" :md="12">
            <el-form-item required>
              <template #label>
                <span class="text-xs font-bold text-slate-700">Day Type <span class="text-rose-500">*</span></span>
              </template>
              <el-select v-model="formData.addForm.day_type_id" placeholder="Select Day Type" class="w-full !rounded-xl">
                <el-option label="Whole Day" value="1" />
                <el-option label="Half Day" value="2" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <el-row :gutter="16">
          <el-col :xs="24" :md="12">
            <el-form-item required>
              <template #label>
                <span class="text-xs font-bold text-slate-700">Date From <span class="text-rose-500">*</span></span>
              </template>
              <el-date-picker
                v-model="formData.addForm.date_from"
                type="date"
                value-format="YYYY-MM-DD"
                placeholder="Select start date"
                class="w-full !rounded-xl"
                :disabled-date="addLeaveDisabledDate"
              />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :md="12">
            <el-form-item required>
              <template #label>
                <span class="text-xs font-bold text-slate-700">Date To <span class="text-rose-500">*</span></span>
              </template>
              <el-date-picker
                v-model="formData.addForm.date_to"
                type="date"
                value-format="YYYY-MM-DD"
                placeholder="Select end date"
                class="w-full !rounded-xl"
                :disabled-date="addLeaveDisabledDate"
              />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item required>
          <template #label>
            <span class="text-xs font-bold text-slate-700">Reason / Justification <span class="text-rose-500">*</span></span>
          </template>
          <el-input
            v-model="formData.addForm.reason"
            type="textarea"
            :rows="3"
            placeholder="Detailed reason for leave..."
            class="!rounded-xl"
          />
        </el-form-item>

        <el-form-item>
          <template #label>
            <span class="text-xs font-bold text-slate-700">Attachments <span class="text-slate-400 font-normal">(Optional)</span></span>
          </template>
          <div class="w-full border-2 border-dashed border-slate-200 hover:border-indigo-400 bg-slate-50/50 hover:bg-indigo-50/20 transition-all rounded-2xl p-4 text-center cursor-pointer group relative">
            <input 
              type="file" 
              multiple 
              @change="onAddFilesChange" 
              accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" 
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
            />
            <div class="flex flex-col items-center justify-center gap-1.5">
              <div class="w-9 h-9 rounded-xl bg-indigo-50 group-hover:bg-indigo-100 text-indigo-600 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
              </div>
              <span class="text-xs font-semibold text-slate-700 group-hover:text-indigo-600 transition-colors">Click or drag documents to upload</span>
              <span class="text-[11px] text-slate-400">Supports PDF, DOC, DOCX, PNG, JPG (Max 5MB)</span>
            </div>
          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <el-button class="!rounded-xl !px-5 font-semibold" @click="closeAddLeave">Cancel</el-button>
          <el-button 
            type="primary" 
            class="!rounded-xl font-bold !px-6 !py-2.5 !bg-gradient-to-r !from-indigo-600 !to-indigo-700 hover:!from-indigo-700 hover:!to-indigo-800 !border-indigo-600 shadow-md shadow-indigo-500/25 hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all duration-200" 
            :loading="uiState.addSubmitting" 
            @click="submitAddLeave"
          >
            Submit Leave Application
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { computed, onMounted } from 'vue'
import LeaveTable from '../Leaves/LeaveTable.vue'
import { useLeave } from '../../composables/useLeave.js'

export default {
  name: 'LeaveSection',
  components: { LeaveTable },
  setup() {
    const {
      state,
      uiState,
      formData,
      filteredLeaves,
      loadLeaveData,
      loadLeaveTypes,
      submitAddLeave,
      deleteLeave,
      cancelLeave,
      downloadAttachment,
      showInlinePrint,
      openAddLeave,
      closeAddLeave,
      handleAddLeaveTypeChange,
      openEditModal,
      onAddFilesChange,
      formatCredits,
      getLeaveBalance,
      hasZeroCredits,
      addLeaveDisabledDate
    } = useLeave()

    onMounted(async () => {
      await loadLeaveTypes()
      await loadLeaveData()
    })

    const displayedLeaveBalances = computed(() => {
      return (state.leaveTypes || [])
        .filter(lt => Number(lt?.active) === 1)
        .map(lt => ({
          type: lt.name,
          balance: Number(getLeaveBalance(lt.name)) || 0
        }))
        .filter(b => b.balance !== 0)
        .sort((a, b) => b.balance - a.balance)
    })

    return {
      state,
      uiState,
      formData,
      filteredLeaves,
      displayedLeaveBalances,
      openAddLeave,
      closeAddLeave,
      handleAddLeaveTypeChange,
      submitAddLeave,
      openEditModal,
      deleteLeave,
      cancelLeave,
      downloadAttachment,
      showInlinePrint,
      onAddFilesChange,
      formatCredits,
      hasZeroCredits,
      addLeaveDisabledDate
    }
  }
}
</script>
