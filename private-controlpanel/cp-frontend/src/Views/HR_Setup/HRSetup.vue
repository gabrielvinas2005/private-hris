<template>
  <MainLayout>
    <template #header>
      <div class="title">HR Setup</div>
    </template>

    <el-card shadow="never" class="search-card">
      <el-input
        v-model="searchQuery"
        placeholder="Search HR Setup options..."
        clearable
        class="search-input"
      >
        <template #prefix>
          <el-icon><Search /></el-icon>
        </template>
      </el-input>
    </el-card>

    <section class="grid">
      <RouterLink v-for="i in filteredItems" :key="i.to" :to="i.to" class="link-reset">
        <MiniServiceCard :title="i.label" :gradient="i.gradient" :description="i.desc" />
      </RouterLink>
    </section>

    <div v-if="filteredItems.length === 0" class="no-results">
      <el-empty description="No matching HR Setup options found" />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Search } from '@element-plus/icons-vue'
import MainLayout from '../../Layout/MainLayout.vue'
import MiniServiceCard from '../../components/MiniServiceCard.vue'

const searchQuery = ref('')

const items = [
  { to: '/hr-setup/company', label: 'Company Setup', gradient: 'linear-gradient(135deg,#ef4444,#f97316)', desc: 'Manage companies' },
  { to: '/hr-setup/branch', label: 'Branch Setup', gradient: 'linear-gradient(135deg,#3b82f6,#22d3ee)', desc: 'Branches and locations' },
  { to: '/hr-setup/office', label: 'Office Setup', gradient: 'linear-gradient(135deg,#10b981,#06b6d4)', desc: 'Office units' },
  { to: '/hr-setup/division', label: 'Division Setup', gradient: 'linear-gradient(135deg,#f59e0b,#f97316)', desc: 'Divisions hierarchy' },
  { to: '/hr-setup/section', label: 'Section Setup', gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)', desc: 'Sections within units' },
  { to: '/hr-setup/eligibility', label: 'Eligibility Setup', gradient: 'linear-gradient(135deg,#60a5fa,#22d3ee)', desc: 'Eligibility criteria' },
  { to: '/hr-setup/employment-type', label: 'Employment Type Setup', gradient: 'linear-gradient(135deg,#0ea5e9,#22d3ee)', desc: 'Full-time, contract, etc.' },
  { to: '/hr-setup/specialization', label: 'Specialization Setup', gradient: 'linear-gradient(135deg,#a855f7,#ec4899)', desc: 'Skill specializations' },
  { to: '/hr-setup/position', label: 'Position Setup', gradient: 'linear-gradient(135deg,#0f766e,#14b8a6)', desc: 'Job positions' },
  { to: '/hr-setup/plantila', label: 'Plantila Setup', gradient: 'linear-gradient(135deg,#fb923c,#ef4444)', desc: 'Plantilla items' },
  { to: '/hr-setup/non-plantila', label: 'Non-Plantila Setup', gradient: 'linear-gradient(135deg,#f97316,#f59e0b)', desc: 'Non-plantilla items' },
  { to: '/hr-setup/promotion-types', label: 'Promotion Types Setup', gradient: 'linear-gradient(135deg,#3b82f6,#6366f1)', desc: 'Promotion categories' },
  { to: '/hr-setup/off-boarding-types', label: 'Off Boarding Types', gradient: 'linear-gradient(135deg,#f43f5e,#fb7185)', desc: 'Exit reasons' },
  { to: '/hr-setup/ipcr-ratings', label: 'IPCR Ratings', gradient: 'linear-gradient(135deg,#22c55e,#84cc16)', desc: 'Performance ratings' },
  { to: '/hr-setup/document-no', label: 'Document No. Setup', gradient: 'linear-gradient(135deg,#8b5cf6,#3b82f6)', desc: 'Document numbering' },
  { to: '/hr-setup/document-type', label: 'Document Type Setup', gradient: 'linear-gradient(135deg,#06b6d4,#0ea5e9)', desc: 'Document categories' },
  { to: '/hr-setup/semester-rating', label: 'Semester Rating Setup', gradient: 'linear-gradient(135deg,#14b8a6,#06b6d4)', desc: 'Semestral ratings' },
  { to: '/hr-setup/competencies', label: 'Competencies Setup', gradient: 'linear-gradient(135deg,#f59e0b,#84cc16)', desc: 'Competency models' },
  { to: '/hr-setup/eete-rating', label: 'EETE Rating Setup', gradient: 'linear-gradient(135deg,#38bdf8,#22d3ee)', desc: 'EETE scoring' },
  { to: '/hr-setup/exam-category', label: 'Exam Category Setup', gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)', desc: 'Exam categories' },
  { to: '/hr-setup/pmt', label: 'PMT Setup', gradient: 'linear-gradient(135deg,#ec4899,#f43f5e)', desc: 'Performance Management Team' },
  { to: '/hr-setup/downloadable-docs', label: 'Downloadable Docs', gradient: 'linear-gradient(135deg,#0ea5e9,#6366f1)', desc: 'Manage downloadable forms' }
]

const filteredItems = computed(() => {
  if (!searchQuery.value) return items
  const query = searchQuery.value.toLowerCase()
  return items.filter(item => 
    item.label.toLowerCase().includes(query) ||
    item.desc.toLowerCase().includes(query) ||
    item.to.toLowerCase().includes(query)
  )
})
</script>

<style scoped>
.title { font-weight: 600; }
.search-card { margin-bottom: 20px; }
.search-input { width: 100%; max-width: 500px; }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
.link-reset { text-decoration: none; }
.no-results { padding: 40px; text-align: center; }
</style>


