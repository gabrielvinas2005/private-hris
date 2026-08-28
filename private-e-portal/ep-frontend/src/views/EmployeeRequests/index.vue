<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- Hero Header Banner -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-slate-800">
        <div class="absolute -right-12 -top-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/3 -bottom-16 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-semibold tracking-wide">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              Supervisor & Approver Portal
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">Employee Requests</h1>
            <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
              Centralized approvals management. Review, approve, or disapprove requests submitted by your team members across all administrative modules.
            </p>
          </div>

          <!-- Quick Metrics Grid -->
          <div class="grid grid-cols-3 gap-3 flex-shrink-0">
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3.5 border border-white/10 text-center min-w-[100px]">
              <span class="block text-2xl font-black text-amber-300">{{ totalPendingCount }}</span>
              <span class="text-[10px] font-semibold text-slate-300 uppercase tracking-wider">Total Pending</span>
            </div>
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3.5 border border-white/10 text-center min-w-[100px]">
              <span class="block text-2xl font-black text-emerald-400">{{ totalApprovedCount }}</span>
              <span class="text-[10px] font-semibold text-slate-300 uppercase tracking-wider">Approved</span>
            </div>
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-3.5 border border-white/10 text-center min-w-[100px]">
              <span class="block text-2xl font-black text-indigo-300">{{ requestTypes.length }}</span>
              <span class="text-[10px] font-semibold text-slate-300 uppercase tracking-wider">Modules</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Category Tabs (High Contrast Dark Bar) -->
      <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-2.5 shadow-xl border border-slate-800">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none">
          <button
            v-for="cat in requestTypes"
            :key="cat.id"
            @click="activeCategory = cat.id"
            :class="[
              activeCategory === cat.id
                ? 'bg-gradient-to-r from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-500/30 font-bold'
                : 'text-slate-300 hover:text-white hover:bg-slate-800/70 font-semibold',
              'px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 whitespace-nowrap transition-all duration-200'
            ]"
          >
            <span>{{ cat.name }}</span>
            <span
              v-if="cat.pending > 0"
              :class="[
                activeCategory === cat.id ? 'bg-white/20 text-white' : 'bg-amber-400/20 text-amber-300 border border-amber-400/30',
                'px-2 py-0.5 rounded-full text-[10px] font-extrabold'
              ]"
            >
              {{ cat.pending }}
            </span>
          </button>
        </div>
      </div>

      <!-- Search & Filters Toolbar -->
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 flex-wrap flex-1">
          <el-input
            v-model="searchQuery"
            placeholder="Search employee name, department, or details..."
            clearable
            style="width: 300px;"
          />
          <el-select v-model="statusFilter" placeholder="Filter Status" style="width: 160px;">
            <el-option label="Pending Approvals" value="pending" />
            <el-option label="Approved Requests" value="approved" />
            <el-option label="Disapproved Requests" value="disapproved" />
            <el-option label="All Records" value="all" />
          </el-select>

          <el-select v-model="sortBy" placeholder="Sort By" style="width: 180px;">
            <el-option label="Date (Newest First)" value="date_desc" />
            <el-option label="Date (Oldest First)" value="date_asc" />
            <el-option label="Employee Name (A-Z)" value="name_asc" />
            <el-option label="Employee Name (Z-A)" value="name_desc" />
            <el-option label="Status" value="status" />
          </el-select>
        </div>
        <div class="text-xs text-slate-500 font-medium">
          Showing <span class="font-bold text-slate-900">{{ currentList.length }}</span> items
        </div>
      </div>

      <!-- Request Details Section -->
      <div v-if="isLoading" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4 animate-pulse">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div class="h-4 bg-slate-200 rounded w-1/4"></div>
          <div class="h-4 bg-slate-200 rounded w-1/6"></div>
        </div>
        <div v-for="i in 5" :key="i" class="h-16 bg-slate-50/80 rounded-xl flex items-center justify-between px-4 space-x-4">
          <div class="flex items-center gap-3 w-1/4">
            <div class="w-9 h-9 bg-slate-200 rounded-full"></div>
            <div class="space-y-1 w-3/4">
              <div class="h-3 bg-slate-200 rounded w-full"></div>
              <div class="h-2 bg-slate-200 rounded w-1/2"></div>
            </div>
          </div>
          <div class="h-4 bg-slate-200 rounded w-28"></div>
          <div class="h-4 bg-slate-200 rounded w-48"></div>
          <div class="h-4 bg-slate-200 rounded w-20"></div>
          <div class="h-8 bg-slate-200 rounded-xl w-36"></div>
        </div>
      </div>

      <div v-else class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
        <el-table
          :data="currentList"
          stripe
          empty-text="No pending employee requests found under this category"
        >
          <el-table-column label="Employee" min-width="220" sortable prop="employee_name">
            <template #default="{ row }">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                  {{ getInitials(row.employee_name || row.name || 'Emp') }}
                </div>
                <div>
                  <span class="font-bold text-slate-900 text-xs block">{{ row.employee_name || row.name || 'Employee' }}</span>
                  <span class="text-[11px] text-slate-500 block">{{ row.department || row.position || 'Staff' }}</span>
                </div>
              </div>
            </template>
          </el-table-column>

          <el-table-column label="Date Filed / Covered" min-width="170" sortable prop="request_date">
            <template #default="{ row }">
              <span class="text-xs font-semibold text-slate-700 block">{{ row.date_covered || row.request_date || row.date || 'N/A' }}</span>
              <span class="text-[10px] text-slate-400 block">{{ row.sub_info || '' }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Details & Purpose" min-width="240">
            <template #default="{ row }">
              <span class="text-xs text-slate-800 line-clamp-2">{{ row.reason || row.purpose || row.details || 'No remarks provided' }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Status" width="130" sortable prop="status_label">
            <template #default="{ row }">
              <span :class="statusBadgeClass(row.status_label || row.status)" class="px-2.5 py-1 rounded-full text-[11px] font-bold">
                {{ row.status_label || row.status || 'Pending' }}
              </span>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="230" fixed="right">
            <template #default="{ row }">
              <div class="flex items-center gap-1.5">
                <el-button
                  size="small"
                  class="!rounded-lg font-bold"
                  @click="openReviewModal(row)"
                >
                  Review
                </el-button>
                <template v-if="isPending(row.status_label || row.status)">
                  <el-button
                    type="success"
                    size="small"
                    class="!rounded-lg font-bold"
                    @click="handleApprove(row)"
                  >
                    Approve
                  </el-button>
                  <el-button
                    type="danger"
                    size="small"
                    class="!rounded-lg font-bold"
                    @click="handleDisapprove(row)"
                  >
                    Reject
                  </el-button>
                </template>
                <span v-else class="text-xs text-slate-400 italic">Reviewed</span>
              </div>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <!-- DTR Review Modal -->
      <el-dialog
        v-model="reviewModalVisible"
        title="DTR Correction Review"
        width="560px"
        destroy-on-close
      >
        <div v-if="reviewingRow" class="space-y-4">
          <!-- Status + Ref -->
          <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
            <div>
              <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Request #{{ reviewingRow.id }}</span>
              <span class="text-sm font-black text-slate-900">{{ reviewingRow.employee_name || reviewingRow.name }}</span>
              <span class="text-[11px] text-slate-500 block">{{ reviewingRow.department || reviewingRow.position || '' }}</span>
            </div>
            <span :class="statusTagClass(reviewingRow.status_label)" class="px-3 py-1 rounded-full text-xs font-bold">
              {{ reviewingRow.status_label || 'Pending' }}
            </span>
          </div>

          <!-- Key Info Grid: Date Filed & Target Date -->
          <div class="grid grid-cols-2 gap-3">
            <div class="p-3 bg-white border border-slate-200 rounded-xl">
              <span class="text-[11px] text-slate-400 font-semibold block">Date Filed</span>
              <span class="text-xs font-bold text-slate-800">{{ reviewingRow.request_date || reviewingRow.created_at || 'N/A' }}</span>
            </div>
            <div class="p-3 bg-white border border-slate-200 rounded-xl">
              <span class="text-[11px] text-slate-400 font-semibold block">Target Date</span>
              <span class="text-xs font-black text-indigo-900">{{ reviewingRow.target_date || reviewingRow.request_date || 'N/A' }}</span>
            </div>
          </div>

          <!-- Correction Details -->
          <div class="p-4 bg-amber-50/60 border border-amber-200 rounded-xl space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-amber-900 border-b border-amber-200/60 pb-2">
              <span>Requested Adjustment</span>
              <span v-if="reviewingRow.target_date || reviewingRow.request_date" class="text-amber-800 font-bold">
                Target: {{ reviewingRow.target_date || reviewingRow.request_date }}
              </span>
            </div>
            <div class="grid grid-cols-2 gap-3 text-xs pt-1">
              <div>
                <span class="text-slate-500 block text-[11px]">Reason / Justification:</span>
                <p class="font-medium text-slate-800 mt-0.5">{{ reviewingRow.reason || 'No reason provided.' }}</p>
              </div>
              <div>
                <span class="text-slate-500 block text-[11px]">Claimed Punch Time:</span>
                <div class="font-extrabold text-slate-900 space-y-0.5 mt-0.5">
                  <template v-if="reviewingRow.am_in || reviewingRow.am_out || reviewingRow.pm_in || reviewingRow.pm_out">
                    <span v-if="reviewingRow.am_in" class="block text-indigo-700 font-black">AM In: {{ reviewingRow.am_in }}</span>
                    <span v-if="reviewingRow.am_out" class="block text-indigo-700 font-black">AM Out: {{ reviewingRow.am_out }}</span>
                    <span v-if="reviewingRow.pm_in" class="block text-indigo-700 font-black">PM In: {{ reviewingRow.pm_in }}</span>
                    <span v-if="reviewingRow.pm_out" class="block text-indigo-700 font-black">PM Out: {{ reviewingRow.pm_out }}</span>
                  </template>
                  <span v-else-if="reviewingRow.field_type && reviewingRow.claimed_time" class="block text-indigo-700 font-black">
                    {{ formatFieldLabel(reviewingRow.field_type) }}: {{ reviewingRow.claimed_time }}
                  </span>
                  <span v-else-if="reviewingRow.claimed_time" class="block text-indigo-700 font-black">
                    {{ reviewingRow.claimed_time }}
                  </span>
                  <span v-else class="text-slate-400 font-normal italic">No punch time specified</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <template #footer>
          <div class="flex items-center justify-between w-full">
            <div class="flex gap-2" v-if="reviewingRow && isPending(reviewingRow.status_label)">
              <el-button type="success" @click="handleApproveFromModal">
                ✓ Approve
              </el-button>
              <el-button type="danger" @click="handleDisapproveFromModal">
                ✗ Reject
              </el-button>
            </div>
            <div v-else></div>
            <el-button @click="reviewModalVisible = false">Close</el-button>
          </div>
        </template>
      </el-dialog>

    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import ApiService from '../../services/api.js'
import { useToast } from 'vue-toastification'

export default {
  name: 'EmployeeRequestsView',
  components: { MainLayout },
  data() {
    return {
      toast: useToast(),
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Employee Requests', path: '/employee-requests' }
      ],
      activeCategory: 'leave',
      searchQuery: '',
      statusFilter: 'all',
      sortBy: 'date_desc',
      isLoading: false,
      requestTypes: [
        { id: 'leave', name: 'Leave', pending: 0 },
        { id: 'travel', name: 'Travel Authority / Order', pending: 0 },
        { id: 'overtime', name: 'Overtime', pending: 0 },
        { id: 'wfh', name: 'Work From Home', pending: 0 },
        { id: 'pass-slip', name: 'OB / Pass Slip', pending: 0 },
        { id: 'pickup', name: 'Request for Pickup', pending: 0 },
        { id: 'dtr', name: 'Daily Time Record', pending: 0 },
        { id: 'accomplishment', name: 'Accomplishment Report', pending: 0 }
      ],
      requestsMap: {
        leave: [],
        travel: [],
        overtime: [],
        wfh: [],
        'pass-slip': [],
        pickup: [],
        dtr: [],
        accomplishment: []
      },
      reviewModalVisible: false,
      reviewingRow: null
    }
  },
  computed: {
    totalPendingCount() {
      return this.requestTypes.reduce((acc, cat) => acc + (cat.pending || 0), 0)
    },
    totalApprovedCount() {
      let count = 0
      Object.values(this.requestsMap).forEach(list => {
        list.forEach(item => {
          const s = (item.status_label || item.status || '').toLowerCase()
          if (s.includes('approved') && !s.includes('disapproved')) count++
        })
      })
      return count
    },
    currentList() {
      const rawList = this.requestsMap[this.activeCategory] || []
      const filtered = rawList.filter(item => {
        const s = (item.status_label || item.status || '').toLowerCase()
        const isPend = s.includes('pending') || s === '0' || s === 'for approval'
        const isApp = s.includes('approved') && !s.includes('disapproved')
        const isDis = s.includes('disapproved') || s.includes('rejected') || s.includes('returned')

        if (this.statusFilter === 'pending' && !isPend) return false
        if (this.statusFilter === 'approved' && !isApp) return false
        if (this.statusFilter === 'disapproved' && !isDis) return false

        if (this.searchQuery) {
          const q = this.searchQuery.toLowerCase()
          const emp = (item.employee_name || item.name || '').toLowerCase()
          const dept = (item.department || '').toLowerCase()
          const details = (item.reason || item.purpose || item.details || '').toLowerCase()
          return emp.includes(q) || dept.includes(q) || details.includes(q)
        }
        return true
      })

      filtered.sort((a, b) => {
        if (this.sortBy === 'date_desc') {
          return new Date(b.request_date || b.date_covered || b.date || 0) - new Date(a.request_date || a.date_covered || a.date || 0)
        }
        if (this.sortBy === 'date_asc') {
          return new Date(a.request_date || a.date_covered || a.date || 0) - new Date(b.request_date || b.date_covered || b.date || 0)
        }
        if (this.sortBy === 'name_asc') {
          return (a.employee_name || a.name || '').localeCompare(b.employee_name || b.name || '')
        }
        if (this.sortBy === 'name_desc') {
          return (b.employee_name || b.name || '').localeCompare(a.employee_name || a.name || '')
        }
        if (this.sortBy === 'status') {
          return (a.status_label || a.status || '').localeCompare(b.status_label || b.status || '')
        }
        return 0
      })

      return filtered
    }
  },
  async mounted() {
    await this.loadAllRequests()
  },
  methods: {
    getInitials(name) {
      if (!name) return 'EP'
      const parts = name.trim().split(' ')
      if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
      }
      return name.slice(0, 2).toUpperCase()
    },
    isPending(status) {
      const s = (status || '').toLowerCase()
      return s.includes('pending') || s === '0' || s === 'for approval'
    },
    statusBadgeClass(status) {
      const s = (status || '').toLowerCase()
      if (s.includes('approved') && !s.includes('disapproved')) return 'bg-emerald-100 text-emerald-800 border border-emerald-300'
      if (s.includes('disapproved') || s.includes('rejected') || s.includes('returned')) return 'bg-rose-100 text-rose-800 border border-rose-300'
      return 'bg-amber-100 text-amber-800 border border-amber-300'
    },
    async loadAllRequests() {
      this.isLoading = true
      try {
        const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
        const userId = userData.id

        if (!userId) return

        // 1. Leave Requests for Approval
        try {
          const leaveRes = await ApiService.getLeaveDashboard(userId)
          const leaves = leaveRes?.data?.leave_for_approvals || leaveRes?.leave_for_approvals || []
          this.requestsMap.leave = leaves.map(l => {
            const isDisapproved = (l.disapproved == 1 || l.disapproved_2 == 1 || l.disapproved_3 == 1)
            const isApproved = (l.approved == 1 || l.approved_2 == 1 || l.approved_3 == 1)
            return {
              id: l.id,
              employee_name: l.employee_name || l.name || 'Staff Member',
              department: l.department || 'Department',
              request_date: l.date_from ? `${l.date_from} to ${l.date_to}` : l.created_at,
              reason: l.reason || l.leave_type || 'Leave Application',
              status_label: isDisapproved ? 'Disapproved' : (isApproved ? 'Approved' : 'Pending')
            }
          })
        } catch (_) {}

        // 2. Overtime Requests
        try {
          const otRes = await ApiService.getOvertimeData(userId)
          const pendingOt = otRes?.data?.pending_overtime_approvals || otRes?.data?.overtime_for_approvals || otRes?.data?.ForapprovalEmployeeOT || []
          const approvedOt = otRes?.data?.ApprovedEmployeeOT || otRes?.data?.approved_overtime || []
          const disapprovedOt = otRes?.data?.DisapprovedEmployeeOT || otRes?.data?.disapproved_overtime || []
          const otList = [...pendingOt, ...approvedOt, ...disapprovedOt]
          this.requestsMap.overtime = otList.map(o => {
            const isApproved = o.approved == 1 || o.approved === true
            const isDisapproved = o.disapproved == 1 || o.disapproved === true
            return {
              id: o.id,
              employee_name: o.employee_name || o.name || 'Staff Member',
              department: o.department || 'Department',
              request_date: o.date || o.created_at,
              reason: o.remarks || o.reason || 'Overtime Authorization',
              status_label: isDisapproved ? 'Disapproved' : (isApproved ? 'Approved' : 'Pending')
            }
          })
        } catch (_) {}

        // 3. Travel Orders / OB / Pass Slip / Pickup
        try {
          const obRes = await ApiService.getOfficialBusiness(userId)
          const pendingOb = obRes?.data?.ob_for_approvals || obRes?.data?.official_business_for_approvals || obRes?.data?.ForapprovalEmployeeOB || []
          const approvedOb = obRes?.data?.ApprovedEmployeeOB || []
          const disapprovedOb = obRes?.data?.DisapprovedEmployeeOB || []
          const obList = [...pendingOb, ...approvedOb, ...disapprovedOb]
          const travelItems = []
          const passSlipItems = []
          const pickupItems = []

          obList.forEach(item => {
            const isApproved = item.approved == 1 || item.approved === true
            const isDisapproved = item.disapproved == 1 || item.disapproved === true
            const mapped = {
              id: item.id,
              employee_name: item.employee_name || item.name || 'Staff Member',
              department: item.department || 'Department',
              request_date: item.date_from ? `${item.date_from} to ${item.date_to}` : (item.date || item.created_at),
              reason: item.purpose || item.client || 'Official Business',
              status_label: isDisapproved ? 'Disapproved' : (isApproved ? 'Approved' : 'Pending')
            }
            if (item.ob_type === 1 || item.ob_type === '1') passSlipItems.push(mapped)
            else if (item.ob_type === 4 || item.ob_type === '4') pickupItems.push(mapped)
            else travelItems.push(mapped)
          })

          this.requestsMap.travel = travelItems
          this.requestsMap['pass-slip'] = passSlipItems
          this.requestsMap.pickup = pickupItems
        } catch (_) {}

        // 4. Daily Time Record (DTR) Requests
        try {
          const dtrRes = await ApiService.getDtrApproverAccess(userId)
          const pendingDtr = dtrRes?.data?.dtr_for_approvals || dtrRes?.dtr_for_approvals || dtrRes?.data?.records || dtrRes?.data?.dtr_pending || []
          const approvedDtr = dtrRes?.data?.dtr_approved || []
          const returnedDtr = dtrRes?.data?.dtr_returned || []
          const dtrList = [...pendingDtr, ...approvedDtr, ...returnedDtr]
          this.requestsMap.dtr = dtrList.map(d => ({
            ...d,
            id: d.id,
            employee_name: d.employee_name || d.name || 'Staff Member',
            department: d.department || d.department_name || d.position || 'Department',
            request_date: d.request_date || d.created_at,
            target_date: d.target_date || d.request_date || d.created_at,
            field_type: d.field_type || null,
            claimed_time: d.claimed_time || null,
            am_in: d.am_in || null,
            am_out: d.am_out || null,
            pm_in: d.pm_in || null,
            pm_out: d.pm_out || null,
            reason: d.reason || d.remarks || d.purpose || 'DTR Request / Adjustment',
            status_label: d.status_label || (d.status ? 'Approved' : 'Pending')
          }))
        } catch (_) {}

        // 5. Work From Home (WFH)
        try {
          const wfhRes = await ApiService.getWfhApplications()
          const wfhList = wfhRes?.data?.applications || []
          this.requestsMap.wfh = wfhList.map(w => {
            const isApproved = w.approved_3 || (w.approved_2 && !w.disapproved_3) || (w.approved && !w.disapproved_2 && !w.disapproved_3)
            const isDisapproved = w.disapproved || w.disapproved_2 || w.disapproved_3
            const isCancelled = w.cancelled
            let status = 'Pending'
            if (isCancelled) status = 'Cancelled'
            else if (isDisapproved) status = 'Disapproved'
            else if (isApproved) status = 'Approved'

            return {
              id: w.id,
              employee_name: w.employee_name || 'Staff Member',
              department: w.department || 'Department',
              request_date: w.start_date ? `${w.start_date} to ${w.end_date}` : w.created_at,
              reason: w.reason || 'Work From Home Application',
              status_label: status
            }
          })
        } catch (_) {}


        // Update pending badges
        this.requestTypes.forEach(cat => {
          const list = this.requestsMap[cat.id] || []
          cat.pending = list.filter(item => this.isPending(item.status_label || item.status)).length
        })

      } catch (err) {
        console.error('Error loading employee requests:', err)
      } finally {
        this.isLoading = false
      }
    },
    openReviewModal(row) {
      this.reviewingRow = row
      this.reviewModalVisible = true
    },
    formatFieldLabel(fieldType) {
      const labels = { am_in: 'AM In', am_out: 'AM Out', pm_in: 'PM In', pm_out: 'PM Out', break_in: 'Break In', break_out: 'Break Out' }
      return labels[fieldType] || fieldType
    },
    statusTagClass(status) {
      const s = (status || '').toLowerCase()
      if (s.includes('approved') && !s.includes('disapproved')) return 'bg-emerald-100 text-emerald-800'
      if (s.includes('disapproved') || s.includes('returned')) return 'bg-rose-100 text-rose-800'
      return 'bg-amber-100 text-amber-800'
    },
    async handleApproveFromModal() {
      if (!this.reviewingRow) return
      await this.handleApprove(this.reviewingRow)
      this.reviewModalVisible = false
    },
    async handleDisapproveFromModal() {
      if (!this.reviewingRow) return
      await this.handleDisapprove(this.reviewingRow)
      this.reviewModalVisible = false
    },
    async handleApprove(row) {
      try {
        const cat = this.activeCategory
        if (cat === 'dtr') {
          await ApiService.approveDtrRequest(row.id)
        } else if (cat === 'leave') {
          await ApiService.processLeave(row.id, 1, '')
        } else if (cat === 'overtime') {
          await ApiService.approveOvertimeApplication(row.id, row.employee_id || 0, '')
        } else if (['travel', 'pass-slip', 'pickup'].includes(cat)) {
          await ApiService.approveOfficialBusiness(row.id, '')
        } else if (cat === 'wfh') {
          await ApiService.approveWfhApplication(row.id)
        }
        this.toast.success(`Request for ${row.employee_name || 'employee'} approved!`)
        await this.loadAllRequests()
      } catch (e) {
        console.error('Approval failed:', e)
        this.toast.error(e?.response?.data?.message || e?.message || 'Approval action failed')
      }
    },
    async handleDisapprove(row) {
      try {
        const cat = this.activeCategory
        if (cat === 'dtr') {
          await ApiService.disapproveDtrRequest(row.id)
        } else if (cat === 'leave') {
          await ApiService.processLeave(row.id, 2, '')
        } else if (cat === 'overtime') {
          await ApiService.disapproveOvertimeApplication(row.id, '')
        } else if (['travel', 'pass-slip', 'pickup'].includes(cat)) {
          await ApiService.disapproveOfficialBusiness(row.id, '')
        } else if (cat === 'wfh') {
          await ApiService.disapproveWfhApplication(row.id)
        }
        this.toast.info(`Request for ${row.employee_name || 'employee'} disapproved.`)
        await this.loadAllRequests()
      } catch (e) {
        console.error('Disapproval failed:', e)
        this.toast.error(e?.response?.data?.message || e?.message || 'Disapproval action failed')
      }
    }
  }
}
</script>
