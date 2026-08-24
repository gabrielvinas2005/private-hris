<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="w-full h-full max-w-[1400px] mx-auto px-2 md:px-4">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">Leave Management</h1>
        <p class="text-slate-600 text-sm md:text-base">Manage your leave applications and requests</p>
      </div>

      <!-- Leave Balance Cards (Top) -->
      <div v-if="displayedLeaveBalances.length > 0" class="mb-4">
        <h3 class="text-lg font-semibold text-slate-900 mb-3">Leave Balance</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
          <div
            v-for="balance in displayedLeaveBalances"
            :key="balance.type"
            class="rounded-lg border border-slate-200 bg-white shadow-sm p-4 text-center"
          >
            <div class="text-2xl font-semibold text-slate-900">{{ formatCredits(balance.balance) }}</div>
            <div class="text-slate-500 text-xs mt-1 uppercase tracking-wide">{{ balance.type }}</div>
          </div>
        </div>
      </div>

      <!-- Filters and Actions -->
      <el-card class="mb-4" shadow="never" body-style="padding: 16px;">
        <div class="flex items-center gap-3 flex-wrap">
          <el-input v-model="state.search" size="small" placeholder="Search leave type, reason" clearable :style="{ width: '260px' }" />
          <el-select v-model="state.statusFilter" size="small" placeholder="Pending" clearable :style="{ width: '180px' }">
            <el-option label="Pending" value="pending" />
            <el-option label="Approved" value="approved" />
            <el-option label="Disapproved" value="disapproved" />
            <el-option label="Cancelled" value="cancelled" />
            <el-option label="Expired" value="expired" />
          </el-select>
          <div class="ml-auto flex gap-2">
            <el-button v-if="state.allowed" size="small" type="primary" @click="openAddLeave">+ Add Leave</el-button>
          </div>
        </div>
      </el-card>

      <!-- Warning Message -->
      <div v-if="!state.allowed" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-red-800">
          <span class="font-semibold">WARNING:</span> Approver is not setup, please contact HRD.
        </p>
      </div>

      <!-- Add Leave Modal -->
      <el-dialog
        :model-value="uiState.showAddModal"
        title="Add Leave"
        width="720px"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        @close="closeAddLeave"
      >
        <el-form label-position="top" @submit.prevent>
          <el-row :gutter="12">
            <el-col :xs="24" :md="12">
              <el-form-item label="Leave Type" required>
                <el-select
                  v-model="formData.addForm.leave_type_id"
                  placeholder="Select Leave Type"
                  filterable
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
              <el-form-item
                v-if="!['1', '2'].includes(String(formData.addForm.other_purpose_id || ''))"
                label="Day Type"
                required
              >
                <el-select v-model="formData.addForm.day_type_id" placeholder="Select Day Type">
                  <el-option label="Whole Day" value="1" />
                  <el-option label="Half Day" value="2" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <el-row v-if="!['1', '2'].includes(String(formData.addForm.other_purpose_id || ''))" :gutter="12">
            <el-col v-if="requiresVacationAdvanceNotice(formData.addForm.leave_type_id) && !formData.addForm.emergency_leave" :span="24">
              <p class="mb-2 text-xs text-slate-500">
                Leave must start at least 5 working days from today.
              </p>
            </el-col>
            <el-col v-if="isSickLeave(formData.addForm.leave_type_id) && formData.addForm.is_advance_filing" :span="24">
              <p class="mb-2 text-xs text-slate-500">
                Advance sick leave: select today or a future date for your scheduled consultation, operation, or medical appointment.
              </p>
            </el-col>
            <el-col :xs="24" :md="12">
              <el-form-item label="Date From" required>
                <el-date-picker 
                  :key="`leave-date-from-${formData.addForm.leave_type_id}-${formData.addForm.emergency_leave}-${formData.addForm.is_advance_filing}`"
                  v-model="formData.addForm.date_from" 
                  type="date" 
                  value-format="YYYY-MM-DD" 
                  placeholder="Select date" 
                  class="w-full"
                  :disabled-date="addLeaveDisabledDate"
                />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :md="12">
              <el-form-item label="Date To" required>
                <el-date-picker 
                  :key="`leave-date-to-${formData.addForm.leave_type_id}-${formData.addForm.emergency_leave}-${formData.addForm.is_advance_filing}`"
                  v-model="formData.addForm.date_to" 
                  type="date" 
                  value-format="YYYY-MM-DD" 
                  placeholder="Select date" 
                  class="w-full"
                  :disabled-date="addLeaveDisabledDate"
                />
              </el-form-item>
            </el-col>
          </el-row>

          <div v-if="!['1', '2'].includes(String(formData.addForm.other_purpose_id || '')) && formData.addForm.leave_type_id && !isSickLeave(formData.addForm.leave_type_id)" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item
              v-if="showEmergencyLeaveCheckbox"
              label="Emergency Leave"
              class="mb-3"
            >
              <el-checkbox
                :model-value="formData.addForm.emergency_leave"
                @change="toggleEmergencyLeave"
              >
                File as emergency leave (allows immediate filing without 5 working-day advance notice)
              </el-checkbox>
              <p v-if="!formData.addForm.emergency_leave" class="mt-1 text-xs text-slate-500">
                Leave must start at least 5 working days from today.
              </p>
            </el-form-item>

            <el-form-item label="In case of Vacation Leave / Special Privilege Leave:" :required="String(formData.addForm.leave_type_id || '') !== '13'">
              <div class="space-y-2">
                <el-checkbox
                  :model-value="formData.addForm.incase_vacation_leave_id === '1'"
                  @change="(checked) => toggleVacationLeaveIncase(1, checked)"
                >
                  Within the Philippines
                </el-checkbox>
                <el-checkbox
                  :model-value="formData.addForm.incase_vacation_leave_id === '2'"
                  @change="(checked) => toggleVacationLeaveIncase(2, checked)"
                >
                  Abroad (Specify)
                </el-checkbox>
              </div>
            </el-form-item>

            <el-form-item label="Specify (Optional)" class="mb-0">
              <el-input
                v-model="formData.addForm.incase_vacation_leave_specify"
                type="textarea"
                :rows="3"
                placeholder="Enter location/details"
              />
            </el-form-item>
          </div>

          <div v-if="!['1', '2'].includes(String(formData.addForm.other_purpose_id || '')) && (uiState.showSickPromptModal || String(formData.addForm.leave_type_id || '') === '13')" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item v-if="isSickLeave(formData.addForm.leave_type_id)" label="Advance Filing" class="mb-3">
              <el-checkbox
                :model-value="formData.addForm.is_advance_filing"
                @change="toggleAdvanceSickLeave"
              >
                Advance Sick Leave
              </el-checkbox>
              <p class="mt-1 text-xs text-slate-500">
                For sick leave with a scheduled doctor appointment, operation, or other future medical consultation.
              </p>
            </el-form-item>

            <el-form-item label="In case of Sick Leave:" :required="String(formData.addForm.leave_type_id || '') !== '13'">
              <div class="space-y-2">
                <el-checkbox
                  :model-value="formData.addForm.incase_sick_leave_id === '1'"
                  @change="(checked) => toggleSickLeaveIncase(1, checked)"
                >
                  In Hospital (Specify Illness)
                </el-checkbox>
                <el-checkbox
                  :model-value="formData.addForm.incase_sick_leave_id === '2'"
                  @change="(checked) => toggleSickLeaveIncase(2, checked)"
                >
                  Out Patient (Specify Illness)
                </el-checkbox>
              </div>
            </el-form-item>

            <el-form-item
              label="Specify Illness"
              :required="isSickLeave(formData.addForm.leave_type_id) || !!formData.addForm.incase_sick_leave_id"
              class="mb-0"
            >
              <el-input
                v-model="formData.addForm.incase_sick_leave_specify"
                type="textarea"
                :rows="3"
                placeholder="Enter illness/details"
              />
            </el-form-item>
          </div>

          <div v-if="!['1', '2'].includes(String(formData.addForm.other_purpose_id || '')) && (uiState.showSpecialLeaveForWomenPromptModal || String(formData.addForm.leave_type_id || '') === '13')" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item label="In case of Special Leave Benefits for Women:" class="mb-0">
              <el-input
                v-model="formData.addForm.incase_special_leave_specify"
                type="textarea"
                :rows="3"
                placeholder="Specify details"
              />
            </el-form-item>
          </div>

          <div v-if="!['1', '2'].includes(String(formData.addForm.other_purpose_id || '')) && (uiState.showStudyLeavePromptModal || String(formData.addForm.leave_type_id || '') === '13')" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item label="In case of Study Leave:" :required="String(formData.addForm.leave_type_id || '') !== '13'">
              <div class="space-y-2">
                <el-checkbox
                  :model-value="formData.addForm.incase_study_leave_id === '1'"
                  @change="(checked) => toggleStudyLeaveIncase(1, checked)"
                >
                  Completion of Master's Degree
                </el-checkbox>
                <el-checkbox
                  :model-value="formData.addForm.incase_study_leave_id === '2'"
                  @change="(checked) => toggleStudyLeaveIncase(2, checked)"
                >
                  BAR/Board Examination Review
                </el-checkbox>
              </div>
            </el-form-item>
          </div>

          <div v-if="formData.addForm.leave_type_id && !['13', '28'].includes(String(formData.addForm.leave_type_id || ''))" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item label="Other Purpose:" class="mb-0">
              <div class="space-y-2">
                <el-checkbox
                  :model-value="formData.addForm.other_purpose_id === '1'"
                  @change="(checked) => toggleOtherPurpose(1, checked)"
                >
                  Monetization of Leave Credits
                </el-checkbox>
                <el-checkbox
                  :model-value="formData.addForm.other_purpose_id === '2'"
                  @change="(checked) => toggleOtherPurpose(2, checked)"
                >
                  Terminal Leave
                </el-checkbox>
              </div>
            </el-form-item>
            <el-form-item v-if="formData.addForm.other_purpose_id === '1'" label="Leave Credits to be Monetized" required class="mt-3 mb-0">
              <el-input
                v-model="formData.addForm.monetization_amount"
                type="number"
                min="0"
                step="0.01"
                placeholder="Enter leave credits"
              />
            </el-form-item>
            <el-form-item v-if="formData.addForm.other_purpose_id === '2'" label="Commutation" required class="mt-3 mb-0">
              <div class="space-y-2">
                <el-checkbox
                  :model-value="formData.addForm.commutation_id === '2'"
                  @change="(checked) => toggleCommutation(2, checked)"
                >
                  Not Requested
                </el-checkbox>
                <el-checkbox
                  :model-value="formData.addForm.commutation_id === '1'"
                  @change="(checked) => toggleCommutation(1, checked)"
                >
                  Requested
                </el-checkbox>
              </div>
            </el-form-item>
          </div>

          <div v-if="String(formData.addForm.leave_type_id || '') === '28'" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item label="Remarks" class="mb-0">
              <el-input
                v-model="formData.addForm.reason"
                type="textarea"
                :rows="3"
                placeholder="Enter remarks"
              />
            </el-form-item>
          </div>

          <el-form-item v-if="formData.addForm.leave_type_id" label="Attachments (max 5, up to 25MB each)" required>
            <input type="file" multiple @change="onAddFilesChange" accept=".jpg,.jpeg,.png,.xls,.xlsx,.doc,.docx,.pdf" />
            <div v-if="formData.addAttachments.length" class="mt-2 space-y-1">
              <div v-for="(f, i) in formData.addAttachments" :key="i" class="text-xs text-slate-600 flex items-center justify-between">
                <span>{{ f.name }}</span>
                <el-button text type="danger" size="small" @click="removeAddFile(i)">Remove</el-button>
              </div>
            </div>
          </el-form-item>
        </el-form>

        <template #footer>
          <el-button @click="closeAddLeave">Cancel</el-button>
          <el-button type="primary" :loading="uiState.addSubmitting" @click="submitAddLeave">Save Leave</el-button>
        </template>
      </el-dialog>

      <!-- Leave List (Filter-driven) -->
      <el-card shadow="never" body-style="padding: 0;">
        <div class="relative">
          <LeaveTable
            v-if="filteredLeaves.length > 0"
            :leaves="filteredLeaves"
            :is-approver="state.isApprover"
            :status-filter="state.statusFilter"
            @edit="openEditModal"
            @delete="deleteLeave"
            @approve="openApproveModal"
            @disapprove="openDisapproveModal"
            @cancel="cancelLeave"
            @download="downloadAttachment"
            @print="showInlinePrint"
          />
          <div v-else class="py-10">
            <el-empty description="No leave records found" />
          </div>
          <TableLoadingOverlay
            :loading="uiState.isTableLoading"
            text="Retrieving leave details..."
          />
        </div>
      </el-card>

      <!-- Inline Print Preview -->
      <div v-if="uiState.printBlobUrl" class="mt-4 border rounded-lg overflow-hidden">
        <div class="flex justify-between items-center px-3 py-2 bg-white border-b">
          <div class="text-sm text-slate-600">Print Preview</div>
          <div class="space-x-2">
            <el-button size="small" @click="closeInlinePrint">Close</el-button>
            <el-button size="small" type="primary" @click="downloadInlinePrint">Download</el-button>
          </div>
        </div>
        <iframe :src="uiState.printBlobUrl" class="w-full h-[70vh] border-0"></iframe>
      </div>

      <!-- Edit Leave Modal (same layout as Add) -->
      <el-dialog :model-value="uiState.showEdit" title="Edit Leave" width="720px" @close="closeEditModal">
        <el-form label-position="top" @submit.prevent>
          <el-row :gutter="12">
            <el-col :xs="24" :md="12">
              <el-form-item label="Leave Type" required>
                <el-select v-model="formData.editForm.leave_type_id" placeholder="Select Leave Type" filterable>
                  <el-option v-for="lt in state.leaveTypes" :key="lt.id" :label="lt.name" :value="String(lt.id)" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :md="12">
              <el-form-item
                v-if="!['1', '2'].includes(String(formData.editForm.other_purpose_id || ''))"
                label="Day Type"
                required
              >
                <el-select v-model="formData.editForm.day_type_id" placeholder="Select Day Type">
                  <el-option label="Whole Day" value="1" />
                  <el-option label="Half Day" value="2" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <el-row v-if="!['1', '2'].includes(String(formData.editForm.other_purpose_id || ''))" :gutter="12">
            <el-col :xs="24" :md="12">
              <el-form-item label="Date From" required>
                <el-date-picker v-model="formData.editForm.date_from" type="date" value-format="YYYY-MM-DD" placeholder="Select date" class="w-full" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :md="12">
              <el-form-item label="Date To" required>
                <el-date-picker v-model="formData.editForm.date_to" type="date" value-format="YYYY-MM-DD" placeholder="Select date" class="w-full" />
              </el-form-item>
            </el-col>
          </el-row>

          <div v-if="!['1', '2'].includes(String(formData.editForm.other_purpose_id || '')) && (['1', '3'].includes(String(formData.editForm.leave_type_id || '')) || String(formData.editForm.leave_type_id || '') === '13')" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item label="In case of Vacation Leave / Special Privilege Leave:" :required="String(formData.editForm.leave_type_id || '') !== '13'">
              <div class="space-y-2">
                <el-checkbox
                  :model-value="formData.editForm.incase_vacation_leave_id === '1'"
                  @change="(checked) => toggleEditVacationLeaveIncase(1, checked)"
                >
                  Within the Philippines
                </el-checkbox>
                <el-checkbox
                  :model-value="formData.editForm.incase_vacation_leave_id === '2'"
                  @change="(checked) => toggleEditVacationLeaveIncase(2, checked)"
                >
                  Abroad (Specify)
                </el-checkbox>
              </div>
            </el-form-item>

            <el-form-item label="Specify (Optional)" class="mb-0">
              <el-input
                v-model="formData.editForm.incase_vacation_leave_specify"
                type="textarea"
                :rows="3"
                placeholder="Enter location/details"
              />
            </el-form-item>
          </div>

          <div v-if="!['1', '2'].includes(String(formData.editForm.other_purpose_id || '')) && (String(formData.editForm.leave_type_id || '') === '2' || String(formData.editForm.leave_type_id || '') === '13')" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item v-if="isSickLeave(formData.editForm.leave_type_id)" label="Advance Filing" class="mb-3">
              <el-checkbox
                :model-value="formData.editForm.is_advance_filing"
                @change="(checked) => { formData.editForm.is_advance_filing = !!checked }"
              >
                Advance Sick Leave
              </el-checkbox>
              <p class="mt-1 text-xs text-slate-500">
                For sick leave with a scheduled doctor appointment, operation, or other future medical consultation.
              </p>
            </el-form-item>

            <el-form-item label="In case of Sick Leave:" :required="String(formData.editForm.leave_type_id || '') !== '13'">
              <div class="space-y-2">
                <el-checkbox
                  :model-value="formData.editForm.incase_sick_leave_id === '1'"
                  @change="(checked) => toggleEditSickLeaveIncase(1, checked)"
                >
                  In Hospital (Specify Illness)
                </el-checkbox>
                <el-checkbox
                  :model-value="formData.editForm.incase_sick_leave_id === '2'"
                  @change="(checked) => toggleEditSickLeaveIncase(2, checked)"
                >
                  Out Patient (Specify Illness)
                </el-checkbox>
              </div>
            </el-form-item>

            <el-form-item
              label="Specify Illness"
              :required="isSickLeave(formData.editForm.leave_type_id) || !!formData.editForm.incase_sick_leave_id"
              class="mb-0"
            >
              <el-input
                v-model="formData.editForm.incase_sick_leave_specify"
                type="textarea"
                :rows="3"
                placeholder="Enter illness/details"
              />
            </el-form-item>
          </div>

          <div v-if="!['1', '2'].includes(String(formData.editForm.other_purpose_id || '')) && (String(formData.editForm.leave_type_id || '') === '25' || String(formData.editForm.leave_type_id || '') === '13')" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item label="In case of Special Leave Benefits for Women:" class="mb-0">
              <el-input
                v-model="formData.editForm.incase_special_leave_specify"
                type="textarea"
                :rows="3"
                placeholder="Specify details"
              />
            </el-form-item>
          </div>

          <div v-if="!['1', '2'].includes(String(formData.editForm.other_purpose_id || '')) && (String(formData.editForm.leave_type_id || '') === '5' || String(formData.editForm.leave_type_id || '') === '13')" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item label="In case of Study Leave:" :required="String(formData.editForm.leave_type_id || '') !== '13'">
              <div class="space-y-2">
                <el-checkbox
                  :model-value="formData.editForm.incase_study_leave_id === '1'"
                  @change="(checked) => toggleEditStudyLeaveIncase(1, checked)"
                >
                  Completion of Master's Degree
                </el-checkbox>
                <el-checkbox
                  :model-value="formData.editForm.incase_study_leave_id === '2'"
                  @change="(checked) => toggleEditStudyLeaveIncase(2, checked)"
                >
                  BAR/Board Examination Review
                </el-checkbox>
              </div>
            </el-form-item>
          </div>

          <div
            v-if="!['13', '28'].includes(String(formData.editForm.leave_type_id || '')) && (['1', '2'].includes(String(formData.editForm.other_purpose_id || '')) || formData.editForm.monetization || formData.editForm.terminal_leave)"
            class="mb-4 rounded-lg border border-slate-200 p-3"
          >
            <el-form-item label="Other Purpose:" class="mb-0">
              <div class="space-y-2">
                <el-checkbox
                  :model-value="formData.editForm.other_purpose_id === '1'"
                  @change="(checked) => toggleEditOtherPurpose(1, checked)"
                >
                  Monetization of Leave Credits
                </el-checkbox>
                <el-checkbox
                  :model-value="formData.editForm.other_purpose_id === '2'"
                  @change="(checked) => toggleEditOtherPurpose(2, checked)"
                >
                  Terminal Leave
                </el-checkbox>
              </div>
            </el-form-item>
            <el-form-item v-if="formData.editForm.other_purpose_id === '1'" label="Leave Credits to be Monetized" required class="mt-3 mb-0">
              <el-input
                v-model="formData.editForm.monetization_amount"
                type="number"
                min="0"
                step="0.01"
                placeholder="Enter leave credits"
              />
            </el-form-item>
            <el-form-item v-if="formData.editForm.other_purpose_id === '2'" label="Commutation" required class="mt-3 mb-0">
              <div class="space-y-2">
                <el-checkbox
                  :model-value="formData.editForm.commutation_id === '2'"
                  @change="(checked) => toggleEditCommutation(2, checked)"
                >
                  Not Requested
                </el-checkbox>
                <el-checkbox
                  :model-value="formData.editForm.commutation_id === '1'"
                  @change="(checked) => toggleEditCommutation(1, checked)"
                >
                  Requested
                </el-checkbox>
              </div>
            </el-form-item>
          </div>

          <div v-if="String(formData.editForm.leave_type_id || '') === '28'" class="mb-4 rounded-lg border border-slate-200 p-3">
            <el-form-item label="Remarks" class="mb-0">
              <el-input
                v-model="formData.editForm.reason"
                type="textarea"
                :rows="3"
                placeholder="Enter remarks"
              />
            </el-form-item>
          </div>
        </el-form>

        <template #footer>
          <el-button @click="closeEditModal">Cancel</el-button>
          <el-button type="primary" :loading="uiState.editSubmitting" @click="submitEdit">Save Changes</el-button>
        </template>
      </el-dialog>

      <!-- For Approvals Section (if approver) -->
      <div v-if="state.isApprover" class="mt-8 bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="p-6">
          <h3 class="text-lg font-semibold text-slate-900 mb-4">Leaves for Approval</h3>
          
          <div class="border-b border-slate-200 mb-4">
            <nav class="flex space-x-8">
              <button
                v-for="tab in approverTabs"
                :key="tab.id"
                @click="state.activeApproverTab = tab.id"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  state.activeApproverTab === tab.id
                    ? 'border-red-500 text-red-600'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                ]"
              >
                {{ tab.name }}
              </button>
            </nav>
          </div>

          <div>
            <!-- Pending Approvals -->
            <div v-if="state.activeApproverTab === 'pending'" class="space-y-4">
              <LeaveTable 
                v-if="state.pendingApprovals.length > 0"
                :leaves="state.pendingApprovals"
                :is-approver="true"
                :is-approval-section="true"
                @approve="openApproveModal"
                @disapprove="openDisapproveModal"
                @print="showInlinePrint"
              />
              <el-empty v-else description="No pending approvals" />
            </div>

            <!-- Approved by Approver -->
            <div v-if="state.activeApproverTab === 'approved'" class="space-y-4">
              <LeaveTable 
                v-if="state.approvedByApprover.length > 0"
                :leaves="state.approvedByApprover"
                :is-approver="true"
                :is-approval-section="true"
                @print="showInlinePrint"
              />
              <el-empty v-else description="No approved records" />
            </div>

            <!-- Disapproved by Approver -->
            <div v-if="state.activeApproverTab === 'disapproved'" class="space-y-4">
              <LeaveTable 
                v-if="state.disapprovedByApprover.length > 0"
                :leaves="state.disapprovedByApprover"
                :is-approver="true"
                :is-approval-section="true"
                @print="showInlinePrint"
              />
              <el-empty v-else description="No disapproved records" />
            </div>

            <!-- Cancelled by Approver -->
            <div v-if="state.activeApproverTab === 'cancelled'" class="space-y-4">
              <LeaveTable 
                v-if="state.cancelledByApprover.length > 0"
                :leaves="state.cancelledByApprover"
                :is-approver="true"
                :is-approval-section="true"
                @print="showInlinePrint"
              />
              <el-empty v-else description="No cancelled records" />
            </div>

            <!-- Expired by System (no further actions) -->
            <div v-if="state.activeApproverTab === 'expired'" class="space-y-4">
              <LeaveTable
                v-if="state.expiredByApprover.length > 0"
                :leaves="state.expiredByApprover"
                :is-approver="true"
                :is-approval-section="true"
                @print="showInlinePrint"
              />
              <el-empty v-else description="No expired records" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Leave Modal -->
    <CancelLeaveModal 
      :show="uiState.showCancelModal"
      :leave="uiState.selectedLeave"
      @close="closeCancelModal"
      @submit="handleCancelLeave"
    />

    <!-- Approve Leave Modal -->
    <ApproveLeaveModal
      :show="uiState.showApproveModal"
      :leave="uiState.selectedLeave"
      :is-submitting="uiState.approveSubmitting"
      @close="closeApproveModal"
      @approve="approveLeave"
    />

    <!-- Disapprove Leave Modal -->
    <DisapproveLeaveModal
      :show="uiState.showDisapproveModal"
      :leave="uiState.selectedLeave"
      :is-submitting="uiState.disapproveSubmitting"
      @close="closeDisapproveModal"
      @disapprove="disapproveLeave"
    />
  </MainLayout>
</template>

<script>
import { computed } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import LeaveTable from '../../components/Leaves/LeaveTable.vue'
import TableLoadingOverlay from '../../components/TableLoadingOverlay.vue'
import CancelLeaveModal from '../../components/Leaves/CancelLeaveModal.vue'
import ApproveLeaveModal from '../../components/Leaves/ApproveLeaveModal.vue'
import DisapproveLeaveModal from '../../components/Leaves/DisapproveLeaveModal.vue'
import { useLeave } from '../../composables/useLeave.js'

export default {
  name: 'LeaveManagementView',
  components: { 
    MainLayout, 
    LeaveTable, 
    TableLoadingOverlay,
    CancelLeaveModal,
    ApproveLeaveModal,
    DisapproveLeaveModal
  },
  setup() {
    const {
      state,
      uiState,
      formData,
      tabs,
      approverTabs,
      filteredLeaves,
      loadLeaveData,
      loadLeaveTypes,
      submitAddLeave,
      submitEdit,
      deleteLeave,
      approveLeave,
      disapproveLeave,
      openApproveModal,
      closeApproveModal,
      openDisapproveModal,
      closeDisapproveModal,
      handleCancelLeave,
      downloadAttachment,
      showInlinePrint,
      closeInlinePrint,
      downloadInlinePrint,
      openAddLeave,
      closeAddLeave,
      handleAddLeaveTypeChange,
      toggleVacationLeaveIncase,
      toggleSickLeaveIncase,
      toggleStudyLeaveIncase,
      toggleOtherPurpose,
      toggleCommutation,
      toggleEditVacationLeaveIncase,
      toggleEditSickLeaveIncase,
      toggleEditStudyLeaveIncase,
      toggleEditOtherPurpose,
      toggleEditCommutation,
      openEditModal,
      closeEditModal,
      cancelLeave,
      closeCancelModal,
      onAddFilesChange,
      removeAddFile,
      switchTab,
      editLeave,
      viewLeaveDetails,
      formatCredits,
      getLeaveBalance,
      hasZeroCredits,
      addLeaveDisabledDate,
      isSickLeave,
      showEmergencyLeaveCheckbox,
      requiresVacationAdvanceNotice,
      toggleEmergencyLeave,
      toggleAdvanceSickLeave
    } = useLeave()

    const displayedLeaveBalances = computed(() => {
      return (state.leaveTypes || [])
        .filter(lt => Number(lt?.active) === 1)
        .map(lt => ({
          type: lt.name,
          balance: Number(getLeaveBalance(lt.name)) || 0
        }))
        .filter(balance => balance.balance !== 0)
        .sort((a, b) => b.balance - a.balance || String(a.type).localeCompare(String(b.type)))
    })

    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Leave Management', path: '/leave-management' },
        { name: 'Leave Application', path: '/leaves' }
      ],
      state,
      uiState,
      formData,
      tabs,
      approverTabs,
      filteredLeaves,
      loadLeaveData,
      loadLeaveTypes,
      submitAddLeave,
      submitEdit,
      deleteLeave,
      approveLeave,
      disapproveLeave,
      openApproveModal,
      closeApproveModal,
      openDisapproveModal,
      closeDisapproveModal,
      handleCancelLeave,
      downloadAttachment,
      showInlinePrint,
      closeInlinePrint,
      downloadInlinePrint,
      openAddLeave,
      closeAddLeave,
      handleAddLeaveTypeChange,
      toggleVacationLeaveIncase,
      toggleSickLeaveIncase,
      toggleStudyLeaveIncase,
      toggleOtherPurpose,
      toggleCommutation,
      toggleEditVacationLeaveIncase,
      toggleEditSickLeaveIncase,
      toggleEditStudyLeaveIncase,
      toggleEditOtherPurpose,
      toggleEditCommutation,
      openEditModal,
      closeEditModal,
      cancelLeave,
      closeCancelModal,
      onAddFilesChange,
      removeAddFile,
      switchTab,
      editLeave,
      viewLeaveDetails,
      formatCredits,
      getLeaveBalance,
      displayedLeaveBalances,
      hasZeroCredits,
      addLeaveDisabledDate,
      isSickLeave,
      showEmergencyLeaveCheckbox,
      requiresVacationAdvanceNotice,
      toggleEmergencyLeave,
      toggleAdvanceSickLeave
    }
  },
  async mounted() {
    await Promise.all([this.loadLeaveData(), this.loadLeaveTypes()])
  }
}
</script> 