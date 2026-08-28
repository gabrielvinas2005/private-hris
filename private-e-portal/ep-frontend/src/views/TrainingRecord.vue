<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto space-y-6 pb-12">
      
      <!-- HERO BANNER -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-slate-800">
        <!-- Ambient background glow -->
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-semibold uppercase tracking-wider">
              <el-icon><Medal /></el-icon>
              <span>Professional Development</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
              Training & Seminar Records
            </h1>
            <p class="text-slate-300 text-sm max-w-xl">
              Track your certified learnings, career development milestones, and professional hours.
            </p>
          </div>

          <button
            @click="openAddModal"
            class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-semibold text-sm shadow-lg shadow-indigo-500/25 hover:shadow-indigo-500/40 transform hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 cursor-pointer"
          >
            <el-icon class="text-base"><Plus /></el-icon>
            <span>Add Training Record</span>
          </button>
        </div>

        <!-- STATS OVERVIEW ROW -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8 pt-6 border-t border-slate-800/80">
          <div class="bg-slate-800/50 backdrop-blur-md rounded-2xl p-4 border border-slate-700/50 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl flex-shrink-0">
              <el-icon><Document /></el-icon>
            </div>
            <div>
              <div class="text-2xl font-bold   text-white">{{ records.length }}</div>
              <div class="text-xs text-slate-400 font-medium">Total Courses</div>
            </div>
          </div>

          <div class="bg-slate-800/50 backdrop-blur-md rounded-2xl p-4 border border-slate-700/50 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center text-xl flex-shrink-0">
              <el-icon><Clock /></el-icon>
            </div>
            <div>
              <div class="text-2xl font-bold   text-white">{{ totalHours }} <span class="text-xs font-normal text-slate-400">hrs</span></div>
              <div class="text-xs text-slate-400 font-medium">Training Hours</div>
            </div>
          </div>

          <div class="bg-slate-800/50 backdrop-blur-md rounded-2xl p-4 border border-slate-700/50 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl flex-shrink-0">
              <el-icon><Check /></el-icon>
            </div>
            <div>
              <div class="text-2xl font-bold   text-white">{{ certifiedCount }}</div>
              <div class="text-xs text-slate-400 font-medium">Certified Training</div>
            </div>
          </div>

          <div class="bg-slate-800/50 backdrop-blur-md rounded-2xl p-4 border border-slate-700/50 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-xl flex-shrink-0">
              <el-icon><OfficeBuilding /></el-icon>
            </div>
            <div>
              <div class="text-sm font-semibold text-white truncate max-w-[120px]">{{ topProvider }}</div>
              <div class="text-xs text-slate-400 font-medium">Main Provider</div>
            </div>
          </div>
        </div>
      </div>

      <!-- MAIN CONTENT CARD -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-6">
        
        <!-- SEARCH AND FILTER BAR -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
          <div class="relative flex-1 max-w-md">
            <el-input
              v-model="searchQuery"
              placeholder="Search by training title or provider..."
              clearable
              size="large"
              class="w-full"
            >
              <template #prefix>
                <el-icon class="text-slate-400"><Search /></el-icon>
              </template>
            </el-input>
          </div>

          <div class="flex items-center gap-3 flex-wrap">
            <!-- Filter by Certificate -->
            <el-select v-model="filterCertificate" placeholder="Filter Certificate" size="large" class="w-44">
              <el-option label="All Records" value="all" />
              <el-option label="With Certificate" value="yes" />
              <el-option label="No Certificate" value="no" />
            </el-select>

            <!-- Sort Order -->
            <el-select v-model="sortOrder" placeholder="Sort By" size="large" class="w-40">
              <el-option label="Latest First" value="desc" />
              <el-option label="Oldest First" value="asc" />
              <el-option label="Longest Duration" value="duration" />
            </el-select>
          </div>
        </div>

        <!-- SKELETON LOADER -->
        <div v-if="loading" class="space-y-4">
          <el-skeleton animated :rows="5">
            <template #template>
              <div class="space-y-3">
                <el-skeleton-item variant="rect" style="width: 100%; height: 50px; border-radius: 12px;" />
                <el-skeleton-item variant="rect" style="width: 100%; height: 50px; border-radius: 12px;" />
                <el-skeleton-item variant="rect" style="width: 100%; height: 50px; border-radius: 12px;" />
              </div>
            </template>
          </el-skeleton>
        </div>

        <!-- TABLE SECTION -->
        <div v-else class="overflow-x-auto rounded-2xl border border-slate-100">
          <el-table
            :data="filteredRecords"
            stripe
            style="width: 100%"
            header-cell-class-name="bg-slate-50 text-slate-700 font-semibold text-xs uppercase tracking-wider py-3.5"
            row-class-name="hover:bg-indigo-50/30 transition-colors"
          >
            <el-table-column prop="training_name" label="Training / Seminar Title" min-width="240">
              <template #default="{ row }">
                <div class="font-semibold text-slate-900">{{ row.training_name }}</div>
                <div v-if="row.notes" class="text-xs text-slate-500 truncate max-w-xs mt-0.5">{{ row.notes }}</div>
              </template>
            </el-table-column>

            <el-table-column label="Date Attended" width="150">
              <template #default="{ row }">
                <div class="flex items-center gap-2 text-slate-700 text-sm font-medium">
                  <el-icon class="text-slate-400"><Calendar /></el-icon>
                  <span>{{ formatDate(row.date_attended) }}</span>
                </div>
              </template>
            </el-table-column>

            <el-table-column prop="duration" label="Duration" width="130" align="center">
              <template #default="{ row }">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs   font-semibold bg-slate-100 text-slate-800">
                  {{ row.duration }} {{ row.duration === 1 ? 'hour' : 'hours' }}
                </span>
              </template>
            </el-table-column>

            <el-table-column prop="provider" label="Training Provider" min-width="180">
              <template #default="{ row }">
                <div class="flex items-center gap-2 text-slate-700 text-sm">
                  <el-icon class="text-indigo-500"><OfficeBuilding /></el-icon>
                  <span>{{ row.provider || 'N/A' }}</span>
                </div>
              </template>
            </el-table-column>

            <el-table-column label="Certificate Status" width="160" align="center">
              <template #default="{ row }">
                <span
                  v-if="row.has_certificate"
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80 shadow-xs"
                >
                  <el-icon class="text-emerald-600"><Check /></el-icon>
                  Verified
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200/60"
                >
                  No Certificate
                </span>
              </template>
            </el-table-column>

            <el-table-column label="Action" width="100" align="right" fixed="right">
              <template #default="{ row }">
                <button
                  @click="viewRecord(row)"
                  class="px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition cursor-pointer"
                >
                  Details
                </button>
              </template>
            </el-table-column>

            <template #empty>
              <div class="py-12 text-center space-y-3">
                <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto text-2xl">
                  <el-icon><Medal /></el-icon>
                </div>
                <div class="text-base font-semibold text-slate-800">No training records found</div>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                  {{ searchQuery ? 'Try adjusting your search terms or filters.' : 'Add your first training or seminar record to keep your professional file up to date.' }}
                </p>
                <button
                  v-if="!searchQuery"
                  @click="openAddModal"
                  class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition cursor-pointer"
                >
                  + Add Training Now
                </button>
              </div>
            </template>
          </el-table>
        </div>
      </div>

      <!-- ADD / EDIT DIALOG -->
      <el-dialog
        v-model="formVisible"
        title="Add Training Record"
        width="620px"
        custom-class="rounded-3xl shadow-2xl overflow-hidden"
        destroy-on-close
      >
        <template #header>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-lg">
              <el-icon><Medal /></el-icon>
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900">Add New Training Record</h3>
              <p class="text-xs text-slate-500">Record a seminar, workshop, or course completion</p>
            </div>
          </div>
        </template>

        <el-form ref="formRef" :model="form" :rules="rules" label-position="top" class="space-y-4 pt-2">
          <el-form-item label="Training / Seminar Title" prop="training_name">
            <el-input v-model="form.training_name" placeholder="e.g. Executive Leadership & Public Governance" size="large" />
          </el-form-item>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <el-form-item label="Date Attended" prop="date_attended">
              <el-date-picker v-model="form.date_attended" type="date" placeholder="Select completion date" style="width: 100%" size="large" />
            </el-form-item>

            <el-form-item label="Duration (Hours)" prop="duration">
              <el-input-number v-model="form.duration" :min="1" :max="500" style="width: 100%" size="large" />
            </el-form-item>
          </div>

          <el-form-item label="Training Provider / Institution" prop="provider">
            <el-input v-model="form.provider" placeholder="e.g. Civil Service Commission / DAP" size="large" />
          </el-form-item>

          <div class="p-4 rounded-2xl bg-indigo-50/50 border border-indigo-100 flex items-center justify-between">
            <div>
              <div class="text-sm font-semibold text-slate-900">Certificate Issued</div>
              <div class="text-xs text-slate-500">Check if you possess a formal certificate of completion</div>
            </div>
            <el-switch v-model="form.has_certificate" active-text="Yes" inactive-text="No" />
          </div>

          <el-form-item label="Additional Notes / Objectives" prop="notes">
            <el-input v-model="form.notes" type="textarea" :rows="3" placeholder="Key topics covered or notes (optional)" />
          </el-form-item>
        </el-form>

        <template #footer>
          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              @click="formVisible = false"
              class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-xs transition cursor-pointer"
            >
              Cancel
            </button>
            <button
              @click="submitForm"
              :disabled="submitting"
              class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-md shadow-indigo-500/20 transition cursor-pointer flex items-center gap-2"
            >
              <span v-if="!submitting">Save Record</span>
              <span v-else>Saving...</span>
            </button>
          </div>
        </template>
      </el-dialog>

      <!-- DETAILS VIEW DIALOG -->
      <el-dialog
        v-model="detailVisible"
        title="Training Record Details"
        width="540px"
        custom-class="rounded-3xl shadow-2xl overflow-hidden"
      >
        <div v-if="selectedRecord" class="space-y-6 pt-1">
          <!-- Header info card -->
          <div class="p-5 rounded-2xl bg-gradient-to-br from-indigo-900 to-slate-900 text-white space-y-2">
            <div class="flex items-center justify-between">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                {{ selectedRecord.duration }} Hours Total
              </span>
              <span v-if="selectedRecord.has_certificate" class="text-xs font-semibold text-emerald-400 flex items-center gap-1">
                <el-icon><Check /></el-icon> Certified
              </span>
            </div>
            <h4 class="text-xl font-bold leading-snug">{{ selectedRecord.training_name }}</h4>
            <div class="text-xs text-slate-300 flex items-center gap-2">
              <el-icon><OfficeBuilding /></el-icon>
              <span>{{ selectedRecord.provider || 'N/A' }}</span>
            </div>
          </div>

          <!-- Metadata grid -->
          <div class="grid grid-cols-2 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
              <span class="text-slate-400 font-medium">Completion Date</span>
              <div class="font-semibold text-slate-800 text-sm">{{ formatDate(selectedRecord.date_attended) }}</div>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
              <span class="text-slate-400 font-medium">Certificate Available</span>
              <div class="font-semibold text-slate-800 text-sm">{{ selectedRecord.has_certificate ? 'Yes (Verified)' : 'No' }}</div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="selectedRecord.notes" class="p-4 rounded-xl bg-slate-50 border border-slate-100 space-y-1">
            <span class="text-xs font-semibold text-slate-500">Notes & Topics</span>
            <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">{{ selectedRecord.notes }}</p>
          </div>
        </div>
      </el-dialog>

    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import { trainingRecordApiService } from '@/services/apiService.js'
import { ElMessage } from 'element-plus'
import {
  Medal,
  Plus,
  Document,
  Clock,
  Check,
  OfficeBuilding,
  Search,
  Calendar
} from '@element-plus/icons-vue'

export default {
  name: 'TrainingRecord',
  components: {
    MainLayout,
    Medal,
    Plus,
    Document,
    Clock,
    Check,
    OfficeBuilding,
    Search,
    Calendar
  },
  data() {
    return {
      loading: false,
      submitting: false,
      formVisible: false,
      detailVisible: false,
      records: [],
      selectedRecord: null,
      searchQuery: '',
      filterCertificate: 'all',
      sortOrder: 'desc',
      form: {
        training_name: '',
        date_attended: null,
        duration: 8,
        provider: '',
        has_certificate: false,
        notes: ''
      },
      rules: {
        training_name: [{ required: true, message: 'Training name is required', trigger: 'blur' }],
        date_attended: [{ required: true, message: 'Date is required', trigger: 'change' }],
        duration: [{ required: true, message: 'Duration is required', trigger: 'blur' }],
        provider: [{ required: true, message: 'Provider is required', trigger: 'blur' }]
      },
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Training Records', path: '/training-records' }
      ]
    }
  },
  computed: {
    totalHours() {
      return this.records.reduce((acc, curr) => acc + (Number(curr.duration) || 0), 0)
    },
    certifiedCount() {
      return this.records.filter(r => r.has_certificate).length
    },
    topProvider() {
      if (!this.records.length) return '—'
      const counts = {}
      this.records.forEach(r => {
        if (r.provider) counts[r.provider] = (counts[r.provider] || 0) + 1
      })
      const sorted = Object.keys(counts).sort((a, b) => counts[b] - counts[a])
      return sorted[0] || '—'
    },
    filteredRecords() {
      let list = [...this.records]

      // Search filter
      if (this.searchQuery.trim()) {
        const q = this.searchQuery.toLowerCase().trim()
        list = list.filter(r =>
          (r.training_name && r.training_name.toLowerCase().includes(q)) ||
          (r.provider && r.provider.toLowerCase().includes(q)) ||
          (r.notes && r.notes.toLowerCase().includes(q))
        )
      }

      // Certificate filter
      if (this.filterCertificate === 'yes') {
        list = list.filter(r => r.has_certificate)
      } else if (this.filterCertificate === 'no') {
        list = list.filter(r => !r.has_certificate)
      }

      // Sort
      if (this.sortOrder === 'desc') {
        list.sort((a, b) => new Date(b.date_attended || 0) - new Date(a.date_attended || 0))
      } else if (this.sortOrder === 'asc') {
        list.sort((a, b) => new Date(a.date_attended || 0) - new Date(b.date_attended || 0))
      } else if (this.sortOrder === 'duration') {
        list.sort((a, b) => (Number(b.duration) || 0) - (Number(a.duration) || 0))
      }

      return list
    }
  },
  async mounted() {
    await this.loadRecords()
  },
  methods: {
    async loadRecords() {
      this.loading = true
      try {
        const response = await trainingRecordApiService.getRecords()
        this.records = response.data?.records || response.records || []
      } catch (error) {
        ElMessage.error('Failed to load training records')
      } finally {
        this.loading = false
      }
    },
    openAddModal() {
      this.form = {
        training_name: '',
        date_attended: null,
        duration: 8,
        provider: '',
        has_certificate: false,
        notes: ''
      }
      this.formVisible = true
    },
    async submitForm() {
      try {
        await this.$refs.formRef.validate()
      } catch {
        return
      }
      this.submitting = true
      try {
        await trainingRecordApiService.createRecord(this.form)
        ElMessage.success('Training record added successfully')
        this.formVisible = false
        await this.loadRecords()
      } catch (error) {
        ElMessage.error('Failed to save training record')
      } finally {
        this.submitting = false
      }
    },
    viewRecord(row) {
      this.selectedRecord = row
      this.detailVisible = true
    },
    formatDate(value) {
      if (!value) return '—'
      return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }
}
</script>