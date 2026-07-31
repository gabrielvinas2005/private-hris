<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Downloadables</h1>
        <p class="text-slate-600">Company forms, policies, and resources</p>
      </div>

      <el-card shadow="never" v-loading="loading">
        <el-table :data="downloadables" stripe empty-text="No downloadable files available">
          <el-table-column label="Title" min-width="250">
            <template #default="{ row }">
              <div>
                <div class="font-semibold text-slate-900">{{ row.title }}</div>
                <div v-if="row.description" class="text-sm text-slate-500 mt-1">{{ row.description }}</div>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="category" label="Category" width="130" />
          <el-table-column label="Added" width="120">
            <template #default="{ row }">{{ formatDate(row.created_at) }}</template>
          </el-table-column>
          <el-table-column label="Action" width="120" fixed="right" align="center">
            <template #default="{ row }">
              <el-button type="primary" link size="small" @click="download(row)">
                Download
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import { downloadablesApiService } from '@/services/apiService.js'
import { ElMessage } from 'element-plus'

export default {
  name: 'Downloadables',
  components: { MainLayout },
  data() {
    return {
      loading: false,
      downloadables: [],
      breadcrumbs: [{ name: 'Downloadables' }]
    }
  },
  async mounted() {
    await this.loadDownloadables()
  },
  methods: {
    async loadDownloadables() {
      this.loading = true
      try {
        const response = await downloadablesApiService.getDownloadables()
        this.downloadables = response.data?.downloadables || []
      } catch (error) {
        ElMessage.error('Failed to load downloadables')
      } finally {
        this.loading = false
      }
    },
    async download(row) {
      try {
        await downloadablesApiService.downloadFile(row.id, row.file_name)
        ElMessage.success('Download started')
      } catch (error) {
        ElMessage.error('Download failed')
      }
    },
    formatDate(value) {
      if (!value) return '—'
      return new Date(value).toLocaleDateString()
    }
  }
}
</script>