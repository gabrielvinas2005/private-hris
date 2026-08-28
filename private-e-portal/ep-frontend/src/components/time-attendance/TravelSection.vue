<template>
  <div class="space-y-5">
    <!-- Header & Travel Metric Cards -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-6">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 flex items-center justify-center text-white shadow-md shadow-indigo-600/20">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900 tracking-tight">Travel Orders & Official Travel</h3>
            <p class="text-xs font-medium text-slate-500">File multi-day travel authorizations, official business orders, and monitor review status</p>
          </div>
        </div>

        <el-button 
          type="primary" 
          class="!rounded-xl font-semibold !px-5 !py-2.5 !bg-indigo-600 hover:!bg-indigo-700 !border-indigo-600 shadow-md shadow-indigo-600/20 hover:shadow-indigo-600/35 hover:-translate-y-0.5 transition-all duration-200" 
          @click="$emit('open-modal')"
        >
          <span class="text-sm">+ File Travel Order</span>
        </el-button>
      </div>

      <!-- Travel Metric Overview Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 pt-2">
        <div class="p-4 bg-gradient-to-br from-slate-50 to-white rounded-2xl border border-slate-200/80 shadow-xs text-center">
          <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Travel Orders</div>
          <div class="text-2xl font-black text-slate-800   mt-1">{{ travelList.length }}</div>
        </div>

        <div class="p-4 bg-gradient-to-br from-indigo-50/50 to-white rounded-2xl border border-indigo-200/60 shadow-xs text-center">
          <div class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Pending Review</div>
          <div class="text-2xl font-black text-indigo-600   mt-1">
            {{ travelList.filter(t => (t.status || 'Pending').toLowerCase().includes('pending')).length }}
          </div>
        </div>

        <div class="p-4 bg-gradient-to-br from-emerald-50/50 to-white rounded-2xl border border-emerald-200/60 shadow-xs text-center">
          <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Approved Travels</div>
          <div class="text-2xl font-black text-emerald-600   mt-1">
            {{ travelList.filter(t => (t.status || '').toLowerCase().includes('approved')).length }}
          </div>
        </div>

        <div class="p-4 bg-gradient-to-br from-blue-50/50 to-white rounded-2xl border border-blue-200/60 shadow-xs text-center">
          <div class="text-xs font-bold text-blue-600 uppercase tracking-wider">Local & Official Business</div>
          <div class="text-2xl font-black text-blue-600   mt-1">
            {{ travelList.filter(t => (t.travel_type || '').toLowerCase().includes('local') || (t.travel_type || '').toLowerCase().includes('official')).length }}
          </div>
        </div>
      </div>

      <!-- Quick Search Toolbar -->
      <div class="flex items-center gap-3 flex-wrap pt-4 border-t border-slate-100">
        <el-input 
          v-model="search" 
          size="default" 
          placeholder="Search destination, purpose, or dates..." 
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

    <!-- Travel Orders Table Card -->
    <div v-else class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/80 space-y-4">
      <div class="flex items-center justify-between">
        <h4 class="text-sm font-bold text-slate-800">Travel Orders & Itineraries</h4>
        <span class="text-xs text-slate-500 font-medium">Total: {{ filteredList.length }}</span>
      </div>

      <el-table :data="filteredList" stripe empty-text="No travel orders filed">
        <el-table-column label="Date Covered" min-width="190">
          <template #default="{ row }">
            <span class="text-xs font-bold   text-slate-800">{{ row.date_from }} to {{ row.date_to }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="destination" label="Destination" min-width="160">
          <template #default="{ row }">
            <span class="text-xs font-semibold text-slate-900">{{ row.destination || 'N/A' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="purpose" label="Purpose / Objectives" min-width="220" show-overflow-tooltip />
        <el-table-column label="Travel Type" width="150">
          <template #default="{ row }">
            <span class="text-xs bg-indigo-50 text-indigo-700 border border-indigo-100 px-2.5 py-1 rounded-full font-bold">
              {{ row.travel_type || 'Local Official' }}
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
      </el-table>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TravelSection',
  props: {
    travelList: { type: Array, default: () => [] },
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
      let list = this.travelList
      if (this.search) {
        const s = this.search.toLowerCase()
        list = list.filter(t => 
          t.destination?.toLowerCase().includes(s) || 
          t.purpose?.toLowerCase().includes(s) || 
          t.date_from?.includes(s)
        )
      }
      if (this.statusFilter) {
        list = list.filter(t => (t.status || 'Pending').toLowerCase().includes(this.statusFilter))
      }
      return list
    }
  },
  methods: {
    getStatusBadgeClass(status) {
      const s = (status || 'pending').toLowerCase()
      if (s.includes('approved')) return 'bg-emerald-500/10 text-emerald-700 border border-emerald-500/20'
      if (s.includes('disapproved') || s.includes('rejected')) return 'bg-rose-500/10 text-rose-700 border border-rose-500/20'
      return 'bg-indigo-500/10 text-indigo-700 border border-indigo-500/20'
    }
  }
}
</script>
