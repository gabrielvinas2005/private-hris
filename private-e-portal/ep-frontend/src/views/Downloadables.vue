<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Header Banner & Search -->
      <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <div class="p-2.5 bg-blue-50 text-blue-600 rounded-lg">
                <el-icon class="text-xl"><Files /></el-icon>
              </div>
              <div>
                <h1 class="text-2xl font-bold text-slate-900">Downloadable Documents</h1>
                <p class="text-sm text-slate-500 mt-0.5">Access official HR setup forms, policies, and company resources</p>
              </div>
            </div>
          </div>
          
          <div class="flex items-center gap-3">
            <el-input
              v-model="searchQuery"
              placeholder="Search documents..."
              clearable
              class="w-64"
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>

            <el-select
              v-model="selectedCategory"
              placeholder="Category"
              clearable
              class="w-40"
            >
              <el-option label="All Categories" value="" />
              <el-option
                v-for="cat in categories"
                :key="cat"
                :label="cat"
                :value="cat"
              />
            </el-select>
          </div>
        </div>
      </div>

      <!-- Main Documents Table -->
      <el-card shadow="never" class="rounded-xl border border-slate-200/80" v-loading="loading">
        <el-table :data="filteredDownloadables" stripe empty-text="No downloadable files available">
          <el-table-column label="Document Title" min-width="280">
            <template #default="{ row }">
              <div class="flex items-start gap-3 py-1">
                <div class="p-2 rounded-lg bg-slate-100 text-slate-600 shrink-0 mt-0.5">
                  <el-icon class="text-lg"><Document /></el-icon>
                </div>
                <div>
                  <div class="font-semibold text-slate-900 hover:text-blue-600 cursor-pointer transition-colors" @click="handlePreview(row)">
                    {{ row.title }}
                  </div>
                  <div v-if="row.file_name" class="text-xs text-slate-500 mt-0.5 font-mono">
                    {{ row.file_name }}
                  </div>
                  <div v-if="row.description && row.description !== 'HR Setup Downloadable Document'" class="text-xs text-slate-500 mt-1">
                    {{ row.description }}
                  </div>
                </div>
              </div>
            </template>
          </el-table-column>

          <el-table-column prop="category" label="Category" width="150">
            <template #default="{ row }">
              <el-tag size="small" effect="plain" type="info" class="rounded-md font-medium">
                {{ row.category || 'HR Form' }}
              </el-tag>
            </template>
          </el-table-column>

          <el-table-column label="Added Date" width="140">
            <template #default="{ row }">
              <span class="text-sm text-slate-600">{{ formatDate(row.created_at) }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="200" fixed="right" align="center">
            <template #default="{ row }">
              <div class="flex items-center justify-center gap-2">
                <el-button type="primary" plain size="small" @click="handlePreview(row)">
                  <el-icon class="mr-1"><View /></el-icon> Preview
                </el-button>
                <el-button type="success" size="small" @click="download(row)">
                  <el-icon class="mr-1"><Download /></el-icon> Download
                </el-button>
              </div>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <!-- Document Preview Dialog -->
      <el-dialog
        v-model="previewVisible"
        :title="previewDoc ? `Preview: ${previewDoc.title || previewDoc.file_name}` : 'Document Preview'"
        width="75%"
        top="5vh"
        destroy-on-close
        @close="closePreview"
        class="preview-dialog"
      >
        <div v-loading="previewLoading" element-loading-text="Loading document preview..." class="min-h-[500px] flex flex-col">
          <div v-if="previewError" class="flex flex-col items-center justify-center py-16 text-center space-y-4">
            <el-icon class="text-5xl text-rose-500"><Warning /></el-icon>
            <div>
              <p class="text-lg font-semibold text-slate-800">Unable to load document preview</p>
              <p class="text-sm text-slate-500 mt-1">{{ previewError }}</p>
            </div>
            <el-button type="primary" @click="download(previewDoc)">
              <el-icon class="mr-1"><Download /></el-icon> Download File
            </el-button>
          </div>

          <template v-else-if="previewUrl">
            <!-- Image Preview -->
            <div v-if="isImage" class="flex items-center justify-center p-4 bg-slate-900/5 rounded-lg max-h-[70vh] overflow-auto">
              <img :src="previewUrl" :alt="previewDoc?.title" class="max-w-full max-h-[65vh] object-contain rounded shadow-sm" />
            </div>

            <!-- PDF or Text/HTML Browser Viewable Preview -->
            <div v-else-if="isViewableInIframe" class="w-full flex-1 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 min-h-[600px]">
              <iframe
                :src="previewUrl"
                class="w-full h-full min-h-[600px] border-none"
                title="Document Preview Frame"
              />
            </div>

            <!-- Non-renderable File Fallback (DOCX, XLSX, ZIP, etc.) -->
            <div v-else class="flex flex-col items-center justify-center py-16 bg-slate-50 rounded-xl border border-dashed border-slate-300 text-center space-y-4">
              <div class="p-4 bg-blue-100 text-blue-600 rounded-full">
                <el-icon class="text-4xl"><Document /></el-icon>
              </div>
              <div class="max-w-md">
                <h3 class="text-lg font-semibold text-slate-800">{{ previewDoc?.file_name || previewDoc?.title }}</h3>
                <p class="text-sm text-slate-500 mt-2">
                  Direct browser preview is not supported for this file format ({{ getFileExtension(previewDoc?.file_name) }}).
                </p>
                <p class="text-xs text-slate-400 mt-1">Please download the file to open it in its native application.</p>
              </div>
              <el-button type="primary" size="large" @click="download(previewDoc)" class="mt-2">
                <el-icon class="mr-1"><Download /></el-icon> Download File Now
              </el-button>
            </div>
          </template>
        </div>

        <template #footer>
          <div class="flex items-center justify-between">
            <div class="text-xs text-slate-500">
              <span v-if="previewDoc?.file_name">File: {{ previewDoc.file_name }}</span>
            </div>
            <div class="flex items-center gap-2">
              <el-button v-if="previewUrl && isViewableInIframe" type="info" plain @click="openInNewTab">
                <el-icon class="mr-1"><FullScreen /></el-icon> Open Fullscreen
              </el-button>
              <el-button type="success" @click="download(previewDoc)">
                <el-icon class="mr-1"><Download /></el-icon> Download
              </el-button>
              <el-button @click="previewVisible = false">Close</el-button>
            </div>
          </div>
        </template>
      </el-dialog>
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
      searchQuery: '',
      selectedCategory: '',
      breadcrumbs: [{ name: 'Downloadables' }],

      // Preview Modal state
      previewVisible: false,
      previewLoading: false,
      previewDoc: null,
      previewUrl: '',
      contentType: '',
      previewError: ''
    }
  },
  computed: {
    categories() {
      const cats = new Set()
      this.downloadables.forEach(d => {
        if (d.category) cats.add(d.category)
      })
      return Array.from(cats)
    },
    filteredDownloadables() {
      return this.downloadables.filter(item => {
        const matchesSearch = !this.searchQuery ||
          (item.title && item.title.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
          (item.file_name && item.file_name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
          (item.description && item.description.toLowerCase().includes(this.searchQuery.toLowerCase()))

        const matchesCategory = !this.selectedCategory || item.category === this.selectedCategory
        return matchesSearch && matchesCategory
      })
    },
    isImage() {
      if (!this.contentType && !this.previewDoc?.file_name) return false
      const type = (this.contentType || '').toLowerCase()
      const ext = this.getFileExtension(this.previewDoc?.file_name)
      return type.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'].includes(ext)
    },
    isViewableInIframe() {
      if (this.isImage) return false
      const type = (this.contentType || '').toLowerCase()
      const ext = this.getFileExtension(this.previewDoc?.file_name)
      return type.includes('pdf') || type.includes('text') || type.includes('html') || ['pdf', 'txt', 'html'].includes(ext)
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
        ElMessage.error('Failed to load downloadable documents')
      } finally {
        this.loading = false
      }
    },
    async handlePreview(row) {
      this.previewDoc = row
      this.previewVisible = true
      this.previewLoading = true
      this.previewError = ''
      if (this.previewUrl) {
        window.URL.revokeObjectURL(this.previewUrl)
        this.previewUrl = ''
      }

      try {
        const res = await downloadablesApiService.previewFile(row.id)
        this.previewUrl = res.url
        this.contentType = res.contentType
      } catch (error) {
        console.error('Failed to fetch preview:', error)
        this.previewError = 'File content could not be retrieved for preview.'
      } finally {
        this.previewLoading = false
      }
    },
    async download(row) {
      if (!row) return
      try {
        await downloadablesApiService.downloadFile(row.id, row.file_name || row.title)
        ElMessage.success('Download started')
      } catch (error) {
        ElMessage.error('Download failed')
      }
    },
    closePreview() {
      if (this.previewUrl) {
        window.URL.revokeObjectURL(this.previewUrl)
        this.previewUrl = ''
      }
      this.previewDoc = null
      this.contentType = ''
      this.previewError = ''
    },
    openInNewTab() {
      if (this.previewUrl) {
        window.open(this.previewUrl, '_blank')
      }
    },
    formatDate(value) {
      if (!value) return '—'
      return new Date(value).toLocaleDateString()
    },
    getFileExtension(fileName) {
      if (!fileName || !fileName.includes('.')) return ''
      return fileName.split('.').pop().toLowerCase()
    }
  }
}
</script>