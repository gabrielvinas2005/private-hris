<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ isEdit ? 'Edit Leave' : 'Add Leave' }}</h1>
        <p class="text-slate-600">{{ isEdit ? 'Update your leave application' : 'Submit a new leave application' }}</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Form -->
      <div v-else class="bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="p-6">
          <form @submit.prevent="handleSubmit" novalidate>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Left Column -->
              <div class="space-y-6">
                <!-- Leave Type -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Leave Type <span class="text-red-500">*</span>
                  </label>
                  <select 
                    v-model="form.leave_type_id"
                    class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :disabled="isView"
                    required
                  >
                    <option value="" disabled>Select Leave Type</option>
                    <option 
                      v-for="leaveType in leaveTypes" 
                      :key="leaveType.id"
                      :value="leaveType.id"
                    >
                      {{ leaveType.name }}
                    </option>
                  </select>
                </div>

                <!-- Force Leave Checkbox -->
                <div v-if="showForceLeave" class="flex items-center">
                  <input 
                    v-model="form.is_force_leave"
                    type="checkbox"
                    :disabled="isView"
                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                  >
                  <label class="ml-2 text-sm text-slate-700">Apply as Force Leave?</label>
                </div>

                <!-- Day Type -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Day Type <span class="text-red-500">*</span>
                  </label>
                  <select 
                    v-model="form.day_type_id"
                    class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :disabled="isView"
                    required
                  >
                    <option value="" disabled>Select Day Type</option>
                    <option value="1">Whole Day</option>
                    <option value="2">Half Day</option>
                  </select>
                </div>

                <!-- Date From -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Date From <span class="text-red-500">(Year-Month-Day) *</span>
                  </label>
                  <input 
                    v-model="form.date_from"
                    type="date"
                    class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :disabled="isView"
                    required
                  >
                </div>

                <!-- Date To -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Date To <span class="text-red-500">(Year-Month-Day) *</span>
                  </label>
                  <input 
                    v-model="form.date_to"
                    type="date"
                    class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    :disabled="isView"
                    required
                  >
                </div>

                <!-- Reason -->
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Reason for Leave <span class="text-red-500">*</span>
                  </label>
                  <textarea 
                    v-model="form.reason"
                    rows="5"
                    class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                    :disabled="isView"
                    placeholder="Please provide a reason for your leave"
                    required
                  ></textarea>
                </div>
              </div>

              <!-- Right Column -->
              <div class="space-y-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">
                  Where Leave will be spent:
                </label>
                
                <div class="bg-slate-50 rounded-lg p-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Vacation Leave -->
                    <div v-if="showVacationLeave" class="space-y-3">
                      <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                          In case of Vacation Leave
                        </label>
                        <select 
                          v-model="form.incase_vacation_leave_id"
                          class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          :disabled="isView"
                        >
                          <option value="0"></option>
                          <option value="1">Within the Philippines</option>
                          <option value="2">Abroad</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                          Please Specify
                        </label>
                        <input 
                          v-model="form.incase_vacation_leave_specify"
                          type="text"
                          class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          :disabled="isView"
                          placeholder="Specify location"
                        >
                      </div>
                    </div>

                    <!-- Sick Leave -->
                    <div v-if="showSickLeave" class="space-y-3">
                      <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                          In case of Sick Leave
                        </label>
                        <select 
                          v-model="form.incase_sick_leave_id"
                          class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          :disabled="isView"
                        >
                          <option value="0"></option>
                          <option value="1">Hospital</option>
                          <option value="2">Out Patient</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                          Specify Illness
                        </label>
                        <input 
                          v-model="form.incase_sick_leave_specify"
                          type="text"
                          class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          :disabled="isView"
                          placeholder="Specify illness"
                        >
                      </div>
                    </div>

                    <!-- Special Leave -->
                    <div v-if="showSpecialLeave" class="space-y-3">
                      <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                          In case of Special Leave benefits for women
                        </label>
                        <input 
                          v-model="form.incase_special_leave_specify"
                          type="text"
                          class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          :disabled="isView"
                          placeholder="Specify reason"
                        >
                      </div>
                    </div>

                    <!-- Study Leave -->
                    <div v-if="showStudyLeave" class="space-y-3">
                      <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                          In case of Study Leave
                        </label>
                        <select 
                          v-model="form.incase_study_leave_id"
                          class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          :disabled="isView"
                        >
                          <option value="0"></option>
                          <option value="1">Completion of Master's Degree</option>
                          <option value="2">BAR/Board Examination Review</option>
                        </select>
                      </div>
                    </div>

                    <!-- Other Purpose -->
                    <div class="space-y-3">
                      <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                          Other Purpose
                        </label>
                        <select 
                          v-model="form.other_purpose_id"
                          class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          :disabled="isView"
                        >
                          <option value="0"></option>
                          <option value="1">Monetization of Leave Credits</option>
                          <option value="2">Terminal Leave</option>
                        </select>
                      </div>
                    </div>

                    <!-- Commutation -->
                    <div class="space-y-3">
                      <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                          Commutation
                        </label>
                        <select 
                          v-model="form.commutation_id"
                          class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          :disabled="isView"
                        >
                          <option value="0"></option>
                          <option value="1">Request</option>
                          <option value="2">No Request</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Leave Details and Attachments -->
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Leave Details -->
              <div class="bg-slate-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Leave Details</h3>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-100">
                      <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-600 uppercase">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-600 uppercase">With Pay</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-600 uppercase">Without Pay</th>
                      </tr>
                    </thead>
                    <tbody class="bg-slate-50 divide-y divide-slate-200">
                      <tr v-for="detail in leaveDetails" :key="detail.leave_date" class="hover:bg-slate-100">
                        <td class="px-4 py-2 text-sm text-slate-900">{{ detail.leave_date }}</td>
                        <td class="px-4 py-2 text-sm text-slate-900">{{ detail.with_pay }}</td>
                        <td class="px-4 py-2 text-sm text-slate-900">{{ detail.without_pay }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Attachments -->
              <div class="bg-slate-50 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Attachments</h3>
                
                <div v-if="!isView" class="mb-4">
                  <label class="block text-sm font-medium text-slate-700 mb-2">
                    Upload Files: <span class="text-red-500">*</span>
                  </label>
                  <input 
                    ref="fileInput"
                    type="file"
                    @change="handleFileChange"
                    accept=".jpg,.jpeg,.png,.xls,.xlsx,.doc,.docx,.pdf"
                    multiple
                    class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                  <p class="text-xs text-slate-500 mt-1">
                    Accepted Files: Images (.jpg, .jpeg, .png), Excel (.xls, .xlsx), Word File (.doc, .docx) and PDF (.pdf) only. 
                    Maximum of 5 files to upload. File Maximum of 25MB.
                  </p>
                </div>

                <!-- File List -->
                <div v-if="attachments.length > 0" class="space-y-2">
                  <h4 class="text-sm font-medium text-slate-700">Uploaded Files:</h4>
                  <div class="space-y-2">
                    <div 
                      v-for="(file, index) in attachments" 
                      :key="index"
                      class="flex items-center justify-between p-2 bg-white rounded border"
                    >
                      <span class="text-sm text-slate-700">{{ file.name }}</span>
                      <button 
                        v-if="!isView"
                        @click="removeFile(index)"
                        type="button"
                        class="text-red-600 hover:text-red-800"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div v-if="!isView" class="mt-8 flex justify-end">
              <button 
                type="submit"
                :disabled="isSubmitting"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ isSubmitting ? 'Saving...' : (isEdit ? 'Update Leave' : 'Save Leave') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import ApiService from '../../services/api.js'
import { useToast } from 'vue-toastification'

export default {
  name: 'LeaveApplicationView',
  components: { MainLayout },
  data() {
    return {
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Leave Management', path: '/leave-management' },
        { name: 'Leave Application', path: '/leaves' },
        { name: 'Add Leave', path: '/leaves/add' }
      ],
      loading: true,
      isSubmitting: false,
      isView: false,
      isEdit: false,
      leaveId: null,
      employeeId: null,
      form: {
        employee_id: '',
        leave_type_id: '',
        day_type_id: '',
        date_from: '',
        date_to: '',
        reason: '',
        is_force_leave: false,
        incase_vacation_leave_id: '0',
        incase_vacation_leave_specify: '',
        incase_sick_leave_id: '0',
        incase_sick_leave_specify: '',
        incase_special_leave_specify: '',
        incase_study_leave_id: '0',
        other_purpose_id: '0',
        commutation_id: '0'
      },
      leaveTypes: [],
      leaveDetails: [],
      attachments: [],
      toast: null
    }
  },
  computed: {
    showForceLeave() {
      return this.form.leave_type_id && this.leaveTypes.find(lt => lt.id == this.form.leave_type_id)?.allows_force_leave
    },
    showVacationLeave() {
      return this.form.leave_type_id && this.leaveTypes.find(lt => lt.id == this.form.leave_type_id)?.name?.toLowerCase().includes('vacation')
    },
    showSickLeave() {
      return this.form.leave_type_id && this.leaveTypes.find(lt => lt.id == this.form.leave_type_id)?.name?.toLowerCase().includes('sick')
    },
    showSpecialLeave() {
      return this.form.leave_type_id && this.leaveTypes.find(lt => lt.id == this.form.leave_type_id)?.name?.toLowerCase().includes('special')
    },
    showStudyLeave() {
      return this.form.leave_type_id && this.leaveTypes.find(lt => lt.id == this.form.leave_type_id)?.name?.toLowerCase().includes('study')
    }
  },
  async mounted() {
    this.toast = useToast()
    await this.initializeForm()
  },
  methods: {
    // Count weekdays (Mon-Fri) between two yyyy-mm-dd strings, inclusive
    countWeekdays(from, to) {
      try {
        const start = new Date(from)
        const end = new Date(to)
        if (!(start instanceof Date) || !(end instanceof Date) || isNaN(start) || isNaN(end)) return 0
        if (start > end) return 0
        let days = 0
        const cur = new Date(start)
        while (cur <= end) {
          const d = cur.getDay() // 0 Sun .. 6 Sat
          if (d !== 0 && d !== 6) days += 1
          cur.setDate(cur.getDate() + 1)
        }
        return days
      } catch (_) {
        return 0
      }
    },
    async initializeForm() {
      try {
        this.loading = true
        const routeParams = this.$route.params
        const idParam = routeParams.id ? parseInt(routeParams.id, 10) : 0
        this.leaveId = isNaN(idParam) ? 0 : idParam
        this.isEdit = this.leaveId > 0
        this.isView = routeParams.view === '1'
        if (this.isEdit) {
          this.breadcrumbs[3] = { name: this.isView ? 'View Leave' : 'Edit Leave', path: this.isView ? '/leaves/view' : '/leaves/edit' }
        }

        const formRes = await ApiService.getLeaveForm(this.leaveId || 0, this.isView ? 1 : 0)
        if (!formRes.success) throw new Error(formRes.message || 'Failed to load leave form')
        const d = formRes.data

        this.leaveTypes = d.leave_types || []
        // Set employee id from backend helper payload
        const emp = (d.employee_data && d.employee_data[0]) || {}
        this.employeeId = emp.id || null
        if (this.employeeId) {
          this.form.employee_id = String(this.employeeId)
        }

        if (this.isEdit) {
          const leave = (d.leave_info && d.leave_info[0]) || {}
          this.form = {
            employee_id: String(leave.employee_id || this.employeeId || ''),
            leave_type_id: String(leave.leave_type_id || ''),
            day_type_id: String(leave.day_type_id || ''),
            date_from: leave.date_from ? leave.date_from.substring(0,10) : '',
            date_to: leave.date_to ? leave.date_to.substring(0,10) : '',
            reason: leave.reason || '',
            is_force_leave: !!leave.is_force_leave,
            incase_vacation_leave_id: String(leave.incase_vacation_leave_id || '0'),
            incase_vacation_leave_specify: leave.incase_vacation_leave_specify || '',
            incase_sick_leave_id: String(leave.incase_sick_leave_id || '0'),
            incase_sick_leave_specify: leave.incase_sick_leave_specify || '',
            incase_special_leave_specify: leave.incase_special_leave_specify || '',
            incase_study_leave_id: String(leave.incase_study_leave_id || '0'),
            other_purpose_id: String(leave.other_purpose_id || '0'),
            commutation_id: String(leave.commutation_id || '0')
          }
          this.leaveDetails = d.leave_details || []
        }
      } catch (error) {
        console.error('Error initializing form:', error)
        this.$toast.error('Failed to load form data')
      } finally {
        this.loading = false
      }
    },
    handleFileChange(event) {
      const files = Array.from(event.target.files)
      files.forEach(file => {
        const allowedTypes = ['image/jpeg','image/jpg','image/png','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/pdf']
        if (!allowedTypes.includes(file.type)) { this.toast.error(`Invalid file type: ${file.name}`); return }
        if (file.size > 25 * 1024 * 1024) { this.toast.error(`File too large: ${file.name}`); return }
        if (this.attachments.length >= 5) { this.toast.error('Maximum 5 files allowed'); return }
        this.attachments.push(file)
      })
      event.target.value = ''
    },
    removeFile(index) { this.attachments.splice(index, 1) },
    async handleSubmit() {
      if (!this.form.leave_type_id) return this.toast.error('Please select a leave type')
      if (!this.form.date_from || !this.form.date_to) return this.toast.error('Please select both start and end dates')
      if (!this.form.reason.trim()) return this.toast.error('Please provide a reason for leave')
      if (!this.isEdit && this.attachments.length === 0) return this.toast.error('Please upload at least one file')

      // Pre-check: ensure the selected date range contains at least one weekday (Mon-Fri)
      const weekdays = this.countWeekdays(this.form.date_from, this.form.date_to)
      if (weekdays <= 0) {
        return this.toast.error('Selected date range contains no weekdays. Please pick at least one working day.')
      }

      this.isSubmitting = true
      try {
        const formData = new FormData()
        Object.keys(this.form).forEach(key => formData.append(key, this.form[key]))
        // Ensure employee_id included
        if (this.employeeId && !formData.has('employee_id')) {
          formData.append('employee_id', String(this.employeeId))
        }
        this.attachments.forEach(file => formData.append('attachments[]', file))
        const id = this.isEdit ? this.leaveId : 0
        const res = await ApiService.submitLeave(id, formData)
        if (!res || res.success !== true) throw new Error((res && res.message) || 'Failed to submit leave')
        this.toast.success(this.isEdit ? 'Leave updated successfully' : 'Leave application submitted successfully')
        this.$router.push('/leaves')
      } catch (error) {
        console.error('Error submitting leave:', error)
        // Try to extract server-provided error JSON from error.message: "... - {\"error\":\"...\"}"
        let serverMessage = ''
        try {
          const raw = String(error && error.message ? error.message : '')
          const sepIndex = raw.indexOf(' - ')
          if (sepIndex !== -1) {
            const maybeJson = raw.substring(sepIndex + 3).trim()
            const parsed = JSON.parse(maybeJson)
            serverMessage = parsed?.message || parsed?.error || ''
            if (!serverMessage && parsed?.errors) {
              // Laravel style validation errors object
              const firstKey = Object.keys(parsed.errors)[0]
              const firstMsg = Array.isArray(parsed.errors[firstKey]) ? parsed.errors[firstKey][0] : String(parsed.errors[firstKey])
              serverMessage = firstMsg || ''
            }
          }
        } catch (_) {}
        const message = serverMessage || (error && error.message) || 'Failed to submit leave'
        this.toast.error(message)
      } finally {
        this.isSubmitting = false
      }
    }
  }
}
</script> 