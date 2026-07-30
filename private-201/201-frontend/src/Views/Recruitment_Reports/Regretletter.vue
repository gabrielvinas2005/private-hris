<template>
    <PageScaffold
        title="Regret Letter"
        subtitle="Manage regret letters for applicants"
        :breadcrumbs="[{ label: 'HR Module', to: '/hr' }, { label: 'Recruitment Reports', to: '/recruitment-reports' }, { label: 'Regret Letter' }]"
    >
        <el-card>
            <template #header>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Select Applicant</span>
                    <el-button type="primary" @click="fetchData" :loading="loading">
                        Refresh
                    </el-button>
                </div>
            </template>

            <div v-if="loading" style="text-align: center; padding: 20px;">
                <el-icon class="is-loading"><Loading /></el-icon>
                <p>Loading applicants...</p>
            </div>

            <div v-else>
                <el-form :model="formData" label-width="120px" style="max-width: 600px;">
                    <el-form-item label="Applicant">
                        <el-select 
                            v-model="selectedApplicantId" 
                            placeholder="Select an applicant" 
                            filterable 
                            clearable
                            style="width: 100%"
                        >
                            <el-option
                                v-for="applicant in data"
                                :key="applicant.id"
                                :label="applicant.name"
                                :value="applicant.id"
                            >
                                <span style="float: left">{{ applicant.name }}</span>
                                <span style="float: right; color: #8492a6; font-size: 13px">ID: {{ applicant.id }}</span>
                            </el-option>
                        </el-select>
                    </el-form-item>

                    <el-form-item v-if="selectedApplicant" label="Selected Applicant">
                        <el-descriptions :column="1" border>
                            <el-descriptions-item label="ID">{{ selectedApplicant.id }}</el-descriptions-item>
                            <el-descriptions-item label="Name">{{ selectedApplicant.name }}</el-descriptions-item>
                            <el-descriptions-item label="Status">
                                <el-tag :type="selectedApplicant.status === '1' ? 'warning' : 'info'">
                                    Status: {{ selectedApplicant.status }}
                                </el-tag>
                            </el-descriptions-item>
                        </el-descriptions>
                    </el-form-item>

                    <el-form-item>
                        <el-button 
                            type="primary" 
                            :disabled="!selectedApplicantId"
                            @click="handleGenerateLetter"
                        >
                            Generate Regret Letter
                        </el-button>
                        <el-button @click="selectedApplicantId = null">Clear Selection</el-button>
                    </el-form-item>
                </el-form>

                <el-divider>
                    <span style="color: #909399; font-size: 12px">Total Applicants: {{ totalItems }}</span>
                </el-divider>
            </div>
        </el-card>

        <!-- Preview Section -->
        <el-card v-if="previewUrl" style="margin-top: 20px;">
            <template #header>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Letter Preview</span>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #606266; font-size: 14px; margin-right: 4px;">Download as:</span>
                        <el-button type="danger" @click="downloadLetter" :loading="loading">
                            PDF
                        </el-button>
                        <el-button type="primary" @click="handleDownloadWord" :loading="loading">
                            DOCX
                        </el-button>
                        <el-button @click="clearPreview" :icon="Close">
                            Close Preview
                        </el-button>
                    </div>
                </div>
            </template>
            
            <div style="width: 100%; height: 800px; border: 1px solid #dcdfe6; border-radius: 4px; overflow: hidden;">
                <iframe 
                    :src="previewUrl" 
                    style="width: 100%; height: 100%; border: none;"
                    type="application/pdf"
                ></iframe>
            </div>
        </el-card>
    </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Loading, Download, Close } from '@element-plus/icons-vue'
import PageScaffold from '@/components/PageScaffold.vue'
import { useRegretLetter } from '@/composable/useRegretLetter'
import { ElMessage } from 'element-plus'

// Use the composable
const { loading, data, totalItems, fetchData, generateLetter, previewUrl, previewFilename, downloadLetter, downloadWord, clearPreview } = useRegretLetter()

// Selected applicant
const selectedApplicantId = ref(null)

// Computed: Get selected applicant details
const selectedApplicant = computed(() => {
    if (!selectedApplicantId.value) return null
    return data.value.find(applicant => applicant.id === selectedApplicantId.value)
})

// Handle generate letter
const handleGenerateLetter = async () => {
    if (!selectedApplicantId.value) {
        ElMessage.warning('Please select an applicant first')
        return
    }
    
    try {
        ElMessage.info(`Generating regret letter for ${selectedApplicant.value?.name || selectedApplicantId.value}...`)
        await generateLetter(selectedApplicantId.value)
        // Success message is already shown by generateLetter composable
    } catch (e) {
        // Error message is already shown by generateLetter composable
        console.error('Error generating letter:', e)
    }
    // Loading state is already handled by generateLetter composable
}

// Handle download Word document
const handleDownloadWord = async () => {
    if (!selectedApplicantId.value) {
        ElMessage.warning('Please select an applicant first')
        return
    }
    
    try {
        await downloadWord(selectedApplicantId.value)
    } catch (e) {
        console.error('Error downloading Word document:', e)
    }
}

// Fetch data when component mounts
onMounted(() => {
    fetchData()
})

// Clean up preview URL when component unmounts
onUnmounted(() => {
    clearPreview()
})
</script>

<style scoped>
</style>