<template>
  <PageScaffold title="Acceptance Letter Intern" subtitle="Generate acceptance letter for interns">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="180px">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="School Officer" prop="school_officer">
              <el-input v-model="formData.school_officer" placeholder="Enter school officer name" />
            </el-form-item>
            <el-form-item label="School Name" prop="school_name">
              <el-input v-model="formData.school_name" placeholder="Enter school name" />
            </el-form-item>
            <el-form-item label="School Address" prop="school_address">
              <el-input
                v-model="formData.school_address"
                type="textarea"
                :rows="2"
                placeholder="Enter school address"
              />
            </el-form-item>
            <el-form-item label="Course / Program" prop="course_program">
              <el-input
                v-model="formData.course_program"
                placeholder="e.g. Bachelor of Science in Information Technology"
              />
            </el-form-item>
            <el-form-item label="Date of Start" prop="date_of_start">
              <el-date-picker v-model="formData.date_of_start" type="date" placeholder="Select date of start" class="w-full"/>
            </el-form-item>
            <el-form-item label="Signatory" prop="signatory">
              <el-input v-model="formData.signatory" placeholder="Enter signatory name" enabled />
            </el-form-item>
            <el-form-item label="Position" prop="position">
              <el-input v-model="formData.position" placeholder="Enter signatory position" enabled />
            </el-form-item>
          </div>

          <!-- Students Table -->
          <div class="mt-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold">Students</h3>
              <el-button type="primary" @click="showAddDialog = true">
                <el-icon><Plus /></el-icon>
                Add Student
              </el-button>
            </div>

            <el-table
              :data="formData.students"
              stripe
              style="width: 100%"
              v-if="formData.students.length > 0"
            >
              <el-table-column prop="first_name" label="First Name" min-width="150" />
              <el-table-column prop="middle_name" label="Middle Name" min-width="150" />
              <el-table-column prop="last_name" label="Last Name" min-width="150" />
              <el-table-column label="Actions" width="100" align="center">
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    text
                    size="small"
                    @click="removeStudent($index)"
                  >
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </template>
              </el-table-column>
            </el-table>

            <div v-else class="text-center py-8 text-gray-500 border border-dashed rounded">
              No students added yet. Click "Add Student" to add students.
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <el-button @click="reset">Reset</el-button>
            <el-button type="success" :loading="saveLoading" @click="saveOnly">Save Record</el-button>
            <el-button type="primary" :loading="generateLoading" @click="onPreview('pdf')">Preview PDF</el-button>
          </div>
        </el-form>
      </div>
    </div>

    <!-- Add Student Dialog -->
    <el-dialog
      v-model="showAddDialog"
      title="Add Student"
      width="500px"
      @close="resetStudentForm"
    >
      <el-form
        ref="studentFormRef"
        :model="studentForm"
        :rules="studentRules"
        label-width="120px"
      >
        <el-form-item label="First Name" prop="first_name">
          <el-input v-model="studentForm.first_name" placeholder="Enter first name" />
        </el-form-item>
        <el-form-item label="Middle Name" prop="middle_name">
          <el-input v-model="studentForm.middle_name" placeholder="Enter middle name" />
        </el-form-item>
        <el-form-item label="Last Name" prop="last_name">
          <el-input v-model="studentForm.last_name" placeholder="Enter last name" />
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="showAddDialog = false">Cancel</el-button>
          <el-button type="primary" @click="addStudent">Add</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- Inline Preview Section -->
    <div v-if="showPreview" class="mt-6 bg-white rounded-lg shadow p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <div class="text-base font-semibold">Acceptance Letter Intern - {{ formData.school_name }}</div>
          <div class="text-xs text-gray-500">Preview below reflects your latest inputs</div>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Download as:</span>
          <el-button type="danger" size="small" @click="downloadPdf">PDF</el-button>
          <el-button type="primary" size="small" @click="downloadWord">WORD</el-button>
        </div>
      </div>

      <div class="border rounded overflow-hidden" style="height:75vh;">
        <iframe
          v-if="pdfUrl || wordUrl"
          :src="previewType === 'pdf' ? pdfUrl : pdfUrl"  
          class="w-full h-full border-0"
        ></iframe>
        <div v-else class="p-6 text-center text-gray-500">No preview available</div>
      </div>
    </div>

    <!-- Saved Records Table -->
    <div class="bg-white rounded-lg shadow mt-6">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Saved School Entries</h3>
          <el-button type="primary" :icon="Refresh" @click="loadRecords" :loading="loading">Refresh</el-button>
        </div>

        <el-table
          :data="records"
          v-loading="loading"
          stripe
          border
          style="width: 100%"
        >
          <el-table-column prop="school_name" label="School Name" min-width="200" />
          <el-table-column prop="school_officer" label="School Officer" min-width="180" />
          <el-table-column prop="school_address" label="School Address" min-width="250" show-overflow-tooltip />
          <el-table-column prop="date_of_start" label="Date of Start" width="150">
            <template #default="{ row }">
              {{ formatDate(row.date_of_start) }}
            </template>
          </el-table-column>
          <el-table-column prop="student_count" label="No. of Students" width="140" align="center">
            <template #default="{ row }">
              <el-tag type="info">{{ row.student_count || 0 }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="Created At" width="180">
            <template #default="{ row }">
              {{ formatDateTime(row.created_at) }}
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="120" fixed="right" align="center">
            <template #default="{ row }">
              <el-button
                type="primary"
                size="small"
                :icon="View"
                @click="viewStudents(row.id)"
                title="View Students"
              >
                View
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <el-empty
          v-if="!loading && records.length === 0"
          description="No saved records found"
        />
      </div>
    </div>

    <!-- View Students Dialog -->
    <el-dialog
      v-model="showStudentsDialog"
      title=""
      width="750px"
      class="students-dialog"
      :close-on-click-modal="false"
    >
      <template #header>
        <div class="dialog-header">
          <div class="flex items-center gap-3">
            <el-icon class="header-icon"><User /></el-icon>
            <div>
              <h3 class="dialog-title">Student List</h3>
              <p class="dialog-subtitle" v-if="selectedRecord?.record">
                {{ selectedRecord.record.school_name }}
              </p>
            </div>
          </div>
        </div>
      </template>

      <div v-loading="loadingStudents" class="dialog-content">
        <!-- School Info Card -->
        <div v-if="selectedRecord?.record" class="info-card">
          <div class="info-grid">
            <div class="info-item">
              <el-icon class="info-icon"><OfficeBuilding /></el-icon>
              <div class="info-content">
                <span class="info-label">School Officer</span>
                <span class="info-value">{{ selectedRecord.record.school_officer }}</span>
              </div>
            </div>
            <div class="info-item">
              <el-icon class="info-icon"><Calendar /></el-icon>
              <div class="info-content">
                <span class="info-label">Start Date</span>
                <span class="info-value">{{ formatDate(selectedRecord.record.date_of_start) }}</span>
              </div>
            </div>
            <div class="info-item">
              <el-icon class="info-icon"><UserFilled /></el-icon>
              <div class="info-content">
                <span class="info-label">Total Students</span>
                <span class="info-value highlight">{{ selectedRecord.students?.length || 0 }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Students Table -->
        <div class="students-section">
          <el-table
            :data="selectedRecord?.students || []"
            stripe
            style="width: 100%"
            :header-cell-style="{ background: '#f5f7fa', color: '#606266', fontWeight: '600' }"
            empty-text="No students found"
          >
            <el-table-column label="" width="60" align="center">
              <template #default>
                <el-icon class="student-icon"><User /></el-icon>
              </template>
            </el-table-column>
            <el-table-column prop="first_name" label="First Name" min-width="140" />
            <el-table-column prop="middle_name" label="Middle Name" min-width="140">
              <template #default="{ row }">
                <span class="text-gray-400">{{ row.middle_name || '-' }}</span>
              </template>
            </el-table-column>
            <el-table-column prop="last_name" label="Last Name" min-width="140" />
            <el-table-column label="Full Name" min-width="200">
              <template #default="{ row }">
                <span class="full-name">
                  {{ [row.first_name, row.middle_name, row.last_name].filter(Boolean).join(' ') }}
                </span>
              </template>
            </el-table-column>
            <el-table-column label="Status" width="100" align="center">
              <template #default="{ row }">
                <el-switch
                  :model-value="!!row.is_active"
                  :active-value="true"
                  :inactive-value="false"
                  @change="handleStatusChange(row.id, $event)"
                  active-text=""
                  inactive-text=""
                />
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="showStudentsDialog = false" type="primary">Close</el-button>
        </div>
      </template>
    </el-dialog>
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, Delete, View, Refresh, User, OfficeBuilding, Calendar, UserFilled } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import PageScaffold from '../PageScaffold.vue'
import { useAcceptanceLetterIntern } from '../../composable/useAcceptanceLetterIntern.js'

const { loading, generateLoading, saveLoading, records, fetchRecords, fetchStudents, updateStudentStatus, saveAcceptanceLetter, generateAcceptancePdf, generateAcceptanceWord, previewAcceptanceWord, downloadPDFFromBlob, downloadWordFromBlob } = useAcceptanceLetterIntern()

const formRef = ref()
const studentFormRef = ref()
const showAddDialog = ref(false)
const showPreview = ref(false)
const showStudentsDialog = ref(false)
const loadingStudents = ref(false)
const selectedRecord = ref(null)
const pdfUrl = ref('')
const wordUrl = ref('')
const previewType = ref('pdf')

const formData = ref({
  id: null,
  school_officer: '',
  school_name: '',
  school_address: '',
  course_program: '',
  date_of_start: '',
  signatory: 'MA FE J. AVILA',
  position: 'OIC Executive Director',
  students: []
})

const studentListKey = (s) => {
  const fn = (s.first_name || '').trim().toLowerCase()
  const mn = (s.middle_name || '').trim().toLowerCase()
  const ln = (s.last_name || '').trim().toLowerCase()
  return `${fn}|${mn}|${ln}`
}

/** After save/update, keep DB ids so the next save updates the same row (avoids duplicate school entries). */
const applySavedRecordToForm = (apiRes) => {
  const record = apiRes?.data
  if (!record?.id) return
  formData.value.id = record.id
  formData.value.course_program = record.course_program ?? ''
  formData.value.students = (record.students || []).map((s) => ({
    id: s.id,
    first_name: s.first_name,
    middle_name: s.middle_name ?? '',
    last_name: s.last_name,
    is_active: s.is_active !== undefined ? !!s.is_active : true
  }))
}

const studentForm = ref({
  first_name: '',
  middle_name: '',
  last_name: ''
})

const rules = {
  school_officer: [{ required: true, message: 'School officer is required', trigger: 'blur' }],
  school_name: [{ required: true, message: 'School name is required', trigger: 'blur' }],
  school_address: [{ required: true, message: 'School address is required', trigger: 'blur' }],
  course_program: [{ required: true, message: 'Course / program is required', trigger: 'blur' }],
  date_of_start: [{ required: true, message: 'Date of start is required', trigger: 'change' }],
  signatory: [{ required: true, message: 'Signatory is required', trigger: 'blur' }],
  position: [{ required: true, message: 'Position is required', trigger: 'blur' }],
  students: [
    { 
      required: true, 
      type: 'array', 
      min: 1, 
      message: 'At least one student is required', 
      trigger: 'change' 
    }
  ]
}

const studentRules = {
  first_name: [{ required: true, message: 'First name is required', trigger: 'blur' }],
  last_name: [{ required: true, message: 'Last name is required', trigger: 'blur' }]
}

const addStudent = () => {
  if (!studentFormRef.value) return

  studentFormRef.value.validate((valid) => {
    if (!valid) return

    const candidate = {
      first_name: studentForm.value.first_name,
      middle_name: studentForm.value.middle_name || '',
      last_name: studentForm.value.last_name
    }
    if (formData.value.students.some((s) => studentListKey(s) === studentListKey(candidate))) {
      ElMessage.warning('This student is already in the list')
      return
    }

    formData.value.students.push(candidate)

    resetStudentForm()
    showAddDialog.value = false
  })
}

const removeStudent = (index) => {
  formData.value.students.splice(index, 1)
}

const resetStudentForm = () => {
  studentForm.value = {
    first_name: '',
    middle_name: '',
    last_name: ''
  }
  if (studentFormRef.value) {
    studentFormRef.value.resetFields()
  }
}

const onPreview = async (type = 'pdf') => {
  await formRef.value?.validate()

  if (formData.value.students.length === 0) {
    ElMessage.warning('Please add at least one student')
    return
  }

  if (type === 'pdf') {
    // Standard PDF preview
    const url = await generateAcceptancePdf({ ...formData.value })
    pdfUrl.value = url
    wordUrl.value = ''
    previewType.value = 'pdf'
  } else {
    // For Word preview: generate Word for download, and PDF for iframe preview
    const [wordObjectUrl, pdfObjectUrl] = await Promise.all([
      previewAcceptanceWord({ ...formData.value }),
      generateAcceptancePdf({ ...formData.value })
    ])
    wordUrl.value = wordObjectUrl
    pdfUrl.value = pdfObjectUrl
    // Force iframe to show PDF for reliable in-browser preview
    previewType.value = 'pdf'
  }

  showPreview.value = true
}

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {
    downloadPDFFromBlob(blob, `acceptance_letter_intern_${formData.value.school_name.replace(/\s+/g, '_')}.pdf`)
  })
}

const downloadWord = async () => {
  try {
    const blob = await generateAcceptanceWord({ ...formData.value })
    downloadWordFromBlob(blob, `acceptance_letter_intern_${formData.value.school_name.replace(/\s+/g, '_')}.docx`)
  } catch (error) {
    console.error('Failed to download Word document:', error)
  }
}

const saveOnly = async () => {
  await formRef.value?.validate()

  if (formData.value.students.length === 0) {
    ElMessage.warning('Please add at least one student')
    return
  }

  try {
    const apiRes = await saveAcceptanceLetter({ ...formData.value })
    applySavedRecordToForm(apiRes)
    await loadRecords()
  } catch (error) {
    console.error('Failed to save record:', error)
  }
}

const loadRecords = async () => {
  try {
    await fetchRecords()
  } catch (error) {
    console.error('Failed to load records:', error)
  }
}

const viewStudents = async (id) => {
  loadingStudents.value = true
  showStudentsDialog.value = true
  selectedRecord.value = null
  
  try {
    const data = await fetchStudents(id)
    // Ensure is_active is properly handled (can be 1/0 or true/false from database)
    if (data.students && Array.isArray(data.students)) {
      data.students = data.students.map(student => ({
        ...student,
        is_active: !!student.is_active // Convert to boolean (handles 1/0, true/false)
      }))
    }
    selectedRecord.value = data
  } catch (error) {
    console.error('Failed to load students:', error)
    ElMessage.error('Failed to load students')
  } finally {
    loadingStudents.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const handleStatusChange = async (studentId, isActive) => {
  try {
    await updateStudentStatus(studentId, isActive)
    // Update the local state immediately
    if (selectedRecord.value?.students) {
      const student = selectedRecord.value.students.find(s => s.id === studentId)
      if (student) {
        student.is_active = !!isActive // Ensure boolean
      }
    }
  } catch (error) {
    console.error('Failed to update student status:', error)
    // Revert the toggle if update fails by refreshing the data
    if (selectedRecord.value?.record?.id) {
      const data = await fetchStudents(selectedRecord.value.record.id)
      if (data.students && Array.isArray(data.students)) {
        data.students = data.students.map(student => ({
          ...student,
          is_active: !!student.is_active // Convert to boolean
        }))
      }
      selectedRecord.value = data
    }
  }
}

const reset = () => {
  formData.value = {
    id: null,
    school_officer: '',
    school_name: '',
    school_address: '',
    course_program: '',
    date_of_start: '',
    signatory: 'MA FE J. AVILA',
    position: 'OIC Executive Director',
    students: []
  }
  formRef.value?.resetFields()
  showPreview.value = false
  pdfUrl.value = ''
  wordUrl.value = ''
}

onMounted(async () => {
  // Load saved records on mount
  await loadRecords()
})
</script>

<style scoped>
.w-full { width: 100%; }

/* Students Dialog Styles */
:deep(.students-dialog .el-dialog__header) {
  padding: 24px 24px 16px;
  border-bottom: 1px solid #ebeef5;
  background: #fff;
  border-radius: 8px 8px 0 0;
}

.dialog-header {
  width: 100%;
}

.dialog-title {
  font-size: 20px;
  font-weight: 600;
  color: #303133;
  margin: 0;
  line-height: 1.4;
}

.dialog-subtitle {
  font-size: 14px;
  color: #909399;
  margin: 4px 0 0;
  font-weight: 400;
}

.header-icon {
  font-size: 28px;
  color: #606266;
}

:deep(.students-dialog .el-dialog__body) {
  padding: 0;
}

.dialog-content {
  padding: 24px;
}

/* Info Card */
.info-card {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 24px;
  border: 1px solid #e9ecef;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.info-icon {
  font-size: 24px;
  color: #606266;
  flex-shrink: 0;
}

.info-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-label {
  font-size: 12px;
  color: #6c757d;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.info-value {
  font-size: 15px;
  color: #212529;
  font-weight: 600;
}

.info-value.highlight {
  color: #303133;
  font-size: 18px;
  font-weight: 700;
}

/* Students Section */
.students-section {
  margin-top: 8px;
}

:deep(.students-section .el-table) {
  border-radius: 8px;
  overflow: hidden;
}

:deep(.students-section .el-table__header) {
  background: #f5f7fa;
}

:deep(.students-section .el-table th) {
  background: #f5f7fa !important;
  color: #606266;
  font-weight: 600;
  border-bottom: 2px solid #e4e7ed;
}

:deep(.students-section .el-table td) {
  padding: 16px 0;
  border-bottom: 1px solid #f0f0f0;
}

:deep(.students-section .el-table tr:hover > td) {
  background-color: #f5f7fa !important;
}

.student-icon {
  font-size: 24px;
  color: #606266;
}

.full-name {
  font-weight: 500;
  color: #212529;
}

:deep(.students-dialog .el-dialog__footer) {
  padding: 16px 24px;
  border-top: 1px solid #ebeef5;
  background: #fafbfc;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
}

/* Responsive */
@media (max-width: 768px) {
  .info-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .dialog-content {
    padding: 16px;
  }
}
</style>

