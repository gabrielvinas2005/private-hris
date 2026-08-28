<template>
  <div class="space-y-5">
    <!-- Header & OT Metric Cards -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-6">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-md shadow-amber-500/20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">Overtime Requests</h3>
            <p class="text-xs font-medium text-slate-500">File overtime authorizations, track hours, and check supervisor review status</p>
          </div>
        </div>

        <el-button 
          type="primary" 
          class="!rounded-xl font-semibold !px-5 !py-2.5 !bg-amber-600 hover:!bg-amber-700 !border-amber-600 shadow-md shadow-amber-600/20 hover:shadow-amber-600/35 hover:-translate-y-0.5 transition-all duration-200" 
          @click="$emit('open-modal')"
        >
          <span class="text-sm">+ File Overtime Request</span>
        </el-button>
      </div>

      <!-- OT Metric Stat Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 pt-2">
        <div class="p-4 bg-gradient-to-br from-slate-50 to-white rounded-2xl border border-slate-200/80 shadow-xs text-center">
          <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Requests</div>
          <div class="text-2xl font-black text-slate-800   mt-1">{{ overtimeList.length }}</div>
        </div>

        <div class="p-4 bg-gradient-to-br from-amber-50/50 to-white rounded-2xl border border-amber-200/60 shadow-xs text-center">
          <div class="text-xs font-bold text-amber-600 uppercase tracking-wider">Pending Approvals</div>
          <div class="text-2xl font-black text-amber-600   mt-1">
            {{ overtimeList.filter(o => (o.status || 'Pending').toLowerCase().includes('pending')).length }}
          </div>
        </div>

        <div class="p-4 bg-gradient-to-br from-emerald-50/50 to-white rounded-2xl border border-emerald-200/60 shadow-xs text-center">
          <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Approved Requests</div>
          <div class="text-2xl font-black text-emerald-600   mt-1">
            {{ overtimeList.filter(o => (o.status || '').toLowerCase().includes('approved')).length }}
          </div>
        </div>

        <div class="p-4 bg-gradient-to-br from-orange-50/50 to-white rounded-2xl border border-orange-200/60 shadow-xs text-center">
          <div class="text-xs font-bold text-orange-600 uppercase tracking-wider">OT Types Configured</div>
          <div class="text-2xl font-black text-orange-600   mt-1">
            {{ otTypesList.length || 3 }}
          </div>
        </div>
      </div>

      <!-- Quick Search Toolbar -->
      <div class="flex items-center gap-3 flex-wrap pt-4 border-t border-slate-100">
        <el-input 
          v-model="search" 
          size="default" 
          placeholder="Search overtime date or reason..." 
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
              { label: 'Disapproved', val: 'disapproved' }
            ]" 
            :key="st.val"
            @click="statusFilter = st.val"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200"
            :class="statusFilter === st.val ? 'bg-white text-slate-900 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900'"
          >
            {{ st.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Skeleton Loader -->
    <div v-if="loading" class="bg-white rounded-3xl p-6 border border-slate-200/80 space-y-4">
      <el-skeleton :rows="4" animated />
    </div>

    <!-- Overtime Applications Table Card -->
    <div v-else class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-bold text-slate-800">Overtime Log & Applications</h4>
        <span class="text-xs text-slate-500 font-medium">Total: {{ filteredList.length }}</span>
      </div>

      <el-table :data="filteredList" stripe empty-text="No overtime requests filed">
        <el-table-column prop="date" label="Date" width="140">
          <template #default="{ row }">
            <span class="text-xs font-bold   text-slate-800">{{ row.date }}</span>
          </template>
        </el-table-column>
        <el-table-column label="Time Duration" width="180">
          <template #default="{ row }">
            <span class="text-xs font-semibold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200/60  ">
              {{ row.time_from }} – {{ row.time_to }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="reason" label="Reason / Work Deliverable" min-width="240" show-overflow-tooltip />
        <el-table-column label="OT Type" width="150">
          <template #default="{ row }">
            <span class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-100 px-2.5 py-1 rounded-full font-bold">
              {{ row.type_name || 'Regular OT' }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="Status" width="140">
          <template #default="{ row }">
            <span :class="getStatusBadgeClass(row.status)" class="px-3 py-1 rounded-full text-xs font-bold inline-block">
              {{ row.status || 'Pending' }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="Approver Trail" min-width="180">
          <template #default="{ row }">
            <span class="text-xs text-slate-600 font-medium">{{ row.approver_name || 'Supervisor Review' }}</span>
          </template>
        </el-table-column>
      </el-table>
    </div>
  </div>
</template>

<script>
export default {
  name: 'OvertimeSection',
  props: {
    overtimeList: { type: Array, default: () => [] },
    otTypesList: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false }
  },
  emits: ['open-modal'],
  data() {
    return {
      search: '',
      statusFilter: ''
    }
  },
  computed: {
    filteredList() {
      let list = this.overtimeList
      if (this.search) {
        const s = this.search.toLowerCase()
        list = list.filter(o => o.reason?.toLowerCase().includes(s) || o.date?.includes(s))
      }
      if (this.statusFilter) {
        list = list.filter(o => (o.status || 'Pending').toLowerCase().includes(this.statusFilter))
      }
      return list
    }
  },
  methods: {
    getStatusBadgeClass(status) {
      const s = (status || 'pending').toLowerCase()
      if (s.includes('approved')) return 'bg-emerald-500/10 text-emerald-700 border border-emerald-500/20'
      if (s.includes('disapproved') || s.includes('rejected')) return 'bg-rose-500/10 text-rose-700 border border-rose-500/20'
      return 'bg-amber-500/10 text-amber-700 border border-amber-500/20'
    }
  }
}
</script>
