<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div v-if="accessLoaded && canViewAnyOpcr" class="opcr-layout">
      <!-- Left Side: List/Overview -->
      <div class="opcr-left-panel">
        <el-card shadow="hover" class="opcr-records-card">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">OPCR Records</h3>
              <el-button v-if="canViewOpcrFormTab" type="primary" size="small" @click="showForm = true">
                <el-icon class="mr-1"><Plus /></el-icon>
                New OPCR
              </el-button>
            </div>
          </template>
          
          <!-- Search/Filter -->
          <div class="opcr-search">
            <el-input
              v-model="searchQuery"
              placeholder="Search OPCR records..."
              size="small"
              clearable
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </div>

          <!-- OPCR Records List -->
          <div class="opcr-list">
            <div 
              v-for="record in filteredRecords" 
              :key="record.id"
              class="opcr-list-item"
              :class="{ active: selectedRecord?.id === record.id }"
              @click="selectRecord(record)"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="font-medium text-slate-900">{{ record.period || 'No Period' }}</div>
                  <div class="text-sm text-slate-600">{{ record.division || 'No Division' }}</div>
                  <div
                    v-if="formatRecalibrationStatus(record.recalibration_status)"
                    class="text-xs text-blue-600 mt-1"
                  >
                    {{ formatRecalibrationStatus(record.recalibration_status) }}
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <el-button
                    v-if="canShowRecalibrateButton(record)"
                    type="warning"
                    size="small"
                    text
                    @click.stop="recalibrateFromList(record)"
                  >
                    <el-icon class="mr-1"><Edit /></el-icon>
                    Recalibrate
                  </el-button>
                  <el-icon class="text-slate-400">
                    <ArrowRight />
                  </el-icon>
                </div>
              </div>
            </div>
            
            <div v-if="filteredRecords.length === 0" class="text-center py-8 text-slate-500">
              <el-icon size="48" class="mb-2"><Document /></el-icon>
              <p>No OPCR records found</p>
              <p class="text-sm mt-2">Click "New OPCR" to create one</p>
            </div>
          </div>
        </el-card>

        <!-- Info Card -->
        <el-card shadow="hover" class="opcr-info-card">
          <template #header>
            <h3 class="text-lg font-semibold text-slate-900">Information</h3>
          </template>
          <div class="opcr-info-body text-sm text-slate-600">
            <p class="opcr-info-label"><strong>Legend:</strong></p>
            <p>Q - Quality · E - Efficiency · T - Timeliness · A - Average</p>
            <div class="opcr-info-ratings">
              <p class="font-medium text-slate-700">Adjectival Ratings:</p>
              <ul class="list-disc ml-5 mt-1">
                <li>5 - Outstanding</li>
                <li>4 - Very Satisfactory</li>
                <li>3 - Satisfactory</li>
                <li>2 - Unsatisfactory</li>
                <li>1 - Very Unsatisfactory</li>
              </ul>
              <p class="opcr-info-note text-slate-500">Note: Q, E, T, and A accept ratings 2 to 5 only.</p>
            </div>
          </div>
        </el-card>
      </div>

      <!-- Right Side: Form Panel -->
      <div class="opcr-right-panel" :class="{ 'panel-visible': showForm }">
        <el-card shadow="hover" class="opcr-form-card">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">
                {{ isFormLocked ? 'View OPCR' : (selectedRecord ? 'Edit OPCR' : 'New OPCR Form') }}
              </h3>
              <div class="flex items-center gap-2">
                <el-button 
                  v-if="selectedRecord && selectedRecord.id && canViewOpcrFormTab"
                  size="small"
                  @click="previewOPCR(selectedRecord.id)"
                  :loading="previewLoading"
                >
                  <el-icon class="mr-1"><Document /></el-icon>
                  Print
                </el-button>
                <el-button 
                  text 
                  @click="closeForm"
                  class="close-btn"
                >
                  <el-icon><Close /></el-icon>
                </el-button>
              </div>
            </div>
          </template>

          <el-tabs v-model="activeTab" class="opcr-tabs">
            <el-tab-pane label="OPCR Form" name="form" v-if="canViewOpcrFormTab">
              <el-alert
                v-if="isFormLocked"
                type="info"
                :closable="false"
                show-icon
                class="mb-4"
                title="This OPCR has been PMT recalibrated and is locked for editing."
              />
              <el-form :model="form" label-position="top" ref="formRef" :rules="rules" class="opcr-form">
                <!-- Header Section -->
                <div class="opcr-header">
                  <el-row :gutter="12" align="middle">
                    <el-col :span="12">
                      <el-form-item label="Division/Office">
                        <el-input 
                          v-model="form.division" 
                          placeholder="Enter division/office" 
                          :disabled="true"
                          readonly
                        />
                      </el-form-item>
                    </el-col>
                    <el-col :span="12">
                      <el-form-item label="Period">
                        <el-date-picker
                          v-model="form.period"
                          type="daterange"
                          range-separator="to"
                          start-placeholder="Start date"
                          end-placeholder="End date"
                          format="YYYY-MM-DD"
                          value-format="YYYY-MM-DD"
                          :editable="false"
                          :disabled="isFormLocked"
                          unlink-panels
                          @change="handlePeriodChange"
                          style="width: 100%"
                        />
                      </el-form-item>
                    </el-col>
                  </el-row>
                </div>
                
                <!-- Signatory Section -->
                <el-divider>Review & Approval</el-divider>
                <el-row :gutter="12">
                  <el-col :span="24">
                    <el-form-item label="Approved by">
                      <el-select 
                        v-model="form.approvedByEmployeeId" 
                        placeholder="Agency Head"
                        filterable
                        style="width: 100%"
                        @change="onApprovedByChange"
                        :disabled="true"
                      >
                        <el-option
                          v-for="emp in employees"
                          :key="emp.id"
                          :label="emp.name"
                          :value="emp.id"
                        />
                      </el-select>
                    </el-form-item>
                    <el-form-item label="Date">
                      <el-date-picker 
                        v-model="form.approvedDate" 
                        type="date" 
                        value-format="YYYY-MM-DD" 
                        placeholder="Select"
                        :disabled="isFormLocked"
                        style="width: 100%"
                      />
                    </el-form-item>
                  </el-col>
                </el-row>
                
                <!-- Performance Table Section -->
                <el-divider>Performance Commitment & Review</el-divider>
                <template v-for="section in outputSections" :key="section.key">
                  <el-divider>{{ section.title }}</el-divider>
                  <div class="table-container">
                    <el-table :data="form[section.key]" border size="small" style="width: 100%">
                      <el-table-column prop="mfoPap" label="MFO/PAP" min-width="120">
                        <template #default="scope">
                          <el-input v-model="scope.row.mfoPap" :placeholder="section.placeholder" size="small" :disabled="isFormLocked"/>
                        </template>
                      </el-table-column>
                      <el-table-column prop="successIndicators" label="Success Indicators" min-width="150">
                        <template #default="scope">
                          <el-input v-model="scope.row.successIndicators" placeholder="Targets & Measures" size="small" :disabled="isFormLocked" />
                        </template>
                      </el-table-column>
                      <el-table-column prop="allottedBudget" label="Budget" width="100">
                        <template #default="scope">
                          <el-input-number v-model="scope.row.allottedBudget" :precision="2" :min="0" size="small" style="width: 100%" :disabled="isFormLocked"/>
                        </template>
                      </el-table-column>
                      <el-table-column prop="divisionIndividualsAccountable" label="Division/Individuals" min-width="130">
                        <template #default="scope">
                          <el-input v-model="scope.row.divisionIndividualsAccountable" placeholder="Division/Individuals" size="small" :disabled="isFormLocked" />
                        </template>
                      </el-table-column>
                      <el-table-column prop="actualAccomplishments" label="Actual Accomplishments" min-width="130">
                        <template #default="scope">
                          <el-input v-model="scope.row.actualAccomplishments" placeholder="Accomplishments" size="small" :disabled="isFormLocked" />
                        </template>
                      </el-table-column>
                      <el-table-column label="Q" width="60">
                        <template #default="scope">
                          <el-input v-model="scope.row.q" size="small" @input="onRatingInput(scope.row, 'q')" placeholder="2-5" :disabled="isFormLocked" />
                        </template>
                      </el-table-column>
                      <el-table-column label="E" width="60">
                        <template #default="scope">
                          <el-input v-model="scope.row.e" size="small" @input="onRatingInput(scope.row, 'e')" placeholder="2-5" :disabled="isFormLocked" />
                        </template>
                      </el-table-column>
                      <el-table-column label="T" width="60">
                        <template #default="scope">
                          <el-input v-model="scope.row.t" size="small" @input="onRatingInput(scope.row, 't')" placeholder="2-5" :disabled="isFormLocked" />
                        </template>
                      </el-table-column>
                      <el-table-column label="A" width="60">
                        <template #default="scope">
                          <el-input v-model="scope.row.a" size="small" placeholder="Auto" disabled />
                        </template>
                      </el-table-column>
                      <el-table-column prop="remarks" label="Remarks" min-width="120">
                        <template #default="scope">
                          <el-input v-model="scope.row.remarks" placeholder="Remarks" size="small" :disabled="isFormLocked"/>
                        </template>
                      </el-table-column>
                      <el-table-column v-if="!isFormLocked" label="Action" width="80" fixed="right">
                        <template #default="scope">
                          <el-button @click="removeRow(section.key, scope.$index)" type="danger" size="small" text>
                            <el-icon><Delete /></el-icon>
                          </el-button>
                        </template>
                      </el-table-column>
                    </el-table>
                  </div>
                  <el-button v-if="!isFormLocked" type="primary" @click="addRow(section.key)" size="small" class="add-row">
                    <el-icon class="mr-1"><Plus /></el-icon>
                    Add {{ section.addLabel }}
                  </el-button>
                </template>

                <el-divider>Category Summary</el-divider>
                <div class="table-container">
                  <el-table :data="categorySummaryRows" border size="small" style="width: 100%">
                    <el-table-column label="Category" min-width="240">
                      <template #default="scope">
                        <span class="inline-flex items-center gap-1 flex-wrap">
                          <span>{{ scope.row.label }} (</span>
                          <el-input
                            v-model="form.categorySummary[scope.row.key].percentage"
                            size="small"
                            placeholder="%"
                            style="width: 72px"
                            :disabled="isCategorySummaryFormDisabled"
                          />
                          <span>%)</span>
                        </span>
                      </template>
                    </el-table-column>
                    <el-table-column label="MFO" width="100" align="center">
                      <template #default="scope">
                        <span>{{ scope.row.mfoCount || '—' }}</span>
                      </template>
                    </el-table-column>
                    <el-table-column label="Rating" width="100" align="center">
                      <template #default="scope">
                        <span>{{ scope.row.rating || '—' }}</span>
                      </template>
                    </el-table-column>
                  </el-table>
                </div>

                <!-- Finalization Section -->
                <el-divider>Signatories</el-divider>
            <!-- Row 1: Planning Office + Assessed by -->
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Planning Office">
                  <el-select
                    v-model="form.planningOfficerEmployeeId"
                    placeholder="Select Planning Officer"
                    filterable
                    style="width: 100%"
                    :disabled="isFormLocked"
                    @change="onPlanningOfficerChange"
                  >
                    <el-option
                      v-for="emp in employees"
                      :key="emp.id"
                      :label="emp.name"
                      :value="emp.id"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Assessed by">
                  <el-select 
                    v-model="form.assessedByEmployeeId" 
                    placeholder="Select Planning Office/PMT"
                    filterable
                    style="width: 100%"
                    @change="onAssessedByChange"
                    :disabled="true"
                  >
                    <el-option
                      v-for="emp in employees"
                      :key="emp.id"
                      :label="emp.name"
                      :value="emp.id"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>

            <!-- Row 2: spacer + assessed date (align) -->
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Date">
                  <el-date-picker 
                    v-model="form.planningOfficerDate" 
                    type="date" 
                    value-format="YYYY-MM-DD"
                    :disabled="isFormLocked"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Date">
                  <el-date-picker 
                    v-model="form.assessedDate" 
                    type="date" 
                    value-format="YYYY-MM-DD"
                    :disabled="isFormLocked"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
            </el-row>

            <!-- Row 3: Final rating by -->
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Final Rating by">
                  <el-select 
                    v-model="form.finalRaterEmployeeId" 
                    placeholder="Select Head of Agency"
                    filterable
                    style="width: 100%"
                    :disabled="isFormLocked"
                    @change="onFinalRaterChange"
                  >
                    <el-option
                      v-for="emp in employees"
                      :key="emp.id"
                      :label="emp.name"
                      :value="emp.id"
                    />
                  </el-select>
                </el-form-item>
                <el-form-item label="Date">
                  <el-date-picker 
                    v-model="form.finalRateDate" 
                    type="date" 
                    value-format="YYYY-MM-DD"
                    :disabled="isFormLocked"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            
                <!-- Form Actions -->
                <div v-if="!isFormLocked" class="form-actions">
                  <el-button type="success" @click="submitForm" :loading="submitting">
                    {{ selectedRecord && selectedRecord.id ? 'Update' : 'Submit' }}
                  </el-button>
                  <el-button @click="resetForm" :disabled="submitting">Reset</el-button>
                  <el-button @click="closeForm" :disabled="submitting">Cancel</el-button>
                </div>
              </el-form>
            </el-tab-pane>

            <el-tab-pane
              v-if="canViewRecalibrationTab && (isHR || isPMT) && selectedRecord && selectedRecord.id && !isFormLocked"
              label="Recalibration"
              name="recalibration"
            >
              <div class="mb-2 text-sm text-slate-600">
                Adjust Q, E, and T for each output. A (Average) is computed automatically.
              </div>

              <div class="flex items-center justify-between mb-3">
                <div class="text-sm text-slate-700">
                  Level:
                  <strong>{{ recalibrationLevel === 'pmt' ? 'PMT' : 'HR' }}</strong>
                </div>
                <div class="flex gap-2">
                  <el-button v-if="isHR" size="small" @click="openRecalibration('hr')">HR</el-button>
                  <el-button v-if="isPMT" size="small" @click="openRecalibration('pmt')">PMT</el-button>
                </div>
              </div>

              <el-table :data="recalibrationOutputs" border size="small" style="width: 100%;">
                <el-table-column prop="mfoPap" label="MFO/PAP" min-width="160" />
                <el-table-column prop="successIndicators" label="Success Indicators" min-width="180" />

                <el-table-column label="Original (Office Head)" align="center">
                  <el-table-column label="Q" width="70">
                    <template #default="scope">
                      <el-input v-model="scope.row.orig_q" size="small" disabled />
                    </template>
                  </el-table-column>
                  <el-table-column label="E" width="70">
                    <template #default="scope">
                      <el-input v-model="scope.row.orig_e" size="small" disabled />
                    </template>
                  </el-table-column>
                  <el-table-column label="T" width="70">
                    <template #default="scope">
                      <el-input v-model="scope.row.orig_t" size="small" disabled />
                    </template>
                  </el-table-column>
                  <el-table-column label="A" width="80">
                    <template #default="scope">
                      <el-input v-model="scope.row.orig_a" size="small" disabled />
                    </template>
                  </el-table-column>
                </el-table-column>

                <el-table-column :label="recalibrationLevel === 'pmt' ? 'PMT Recalibration' : 'HR Recalibration'" align="center">
                  <el-table-column label="Q" width="70">
                    <template #default="scope">
                      <el-input v-model="scope.row.q" size="small" @input="onRecalibrationRatingInput(scope.row, 'q')" placeholder="2-5" />
                    </template>
                  </el-table-column>
                  <el-table-column label="E" width="70">
                    <template #default="scope">
                      <el-input v-model="scope.row.e" size="small" @input="onRecalibrationRatingInput(scope.row, 'e')" placeholder="2-5" />
                    </template>
                  </el-table-column>
                  <el-table-column label="T" width="70">
                    <template #default="scope">
                      <el-input v-model="scope.row.t" size="small" @input="onRecalibrationRatingInput(scope.row, 't')" placeholder="2-5" />
                    </template>
                  </el-table-column>
                  <el-table-column label="A" width="80">
                    <template #default="scope">
                      <el-input v-model="scope.row.a" size="small" placeholder="Auto" disabled />
                    </template>
                  </el-table-column>
                </el-table-column>

                <el-table-column prop="remarks" label="Remarks" min-width="160">
                  <template #default="scope">
                    <el-input v-model="scope.row.remarks" size="small" />
                  </template>
                </el-table-column>
              </el-table>

              <el-divider>Category Summary</el-divider>
              <div class="mb-2 text-sm text-slate-600">
                PMT may adjust the category percentage values below.
              </div>
              <el-table :data="categorySummaryRows" border size="small" style="width: 100%;">
                <el-table-column label="Category" min-width="240">
                  <template #default="scope">
                    <span class="inline-flex items-center gap-1 flex-wrap">
                      <span>{{ scope.row.label }} (</span>
                      <el-input
                        v-model="form.categorySummary[scope.row.key].percentage"
                        size="small"
                        placeholder="%"
                        style="width: 72px"
                        :disabled="recalibrationLevel !== 'pmt'"
                      />
                      <span>%)</span>
                    </span>
                  </template>
                </el-table-column>
                <el-table-column label="MFO" width="100" align="center">
                  <template #default="scope">
                    <span>{{ scope.row.mfoCount || '—' }}</span>
                  </template>
                </el-table-column>
                <el-table-column label="Rating" width="100" align="center">
                  <template #default="scope">
                    <span>{{ scope.row.rating || '—' }}</span>
                  </template>
                </el-table-column>
              </el-table>

              <div class="flex justify-end gap-2 mt-4">
                <el-button @click="activeTab = 'form'" :disabled="recalibrationSubmitting">Back</el-button>
                <el-button type="primary" @click="submitRecalibration" :loading="recalibrationSubmitting">
                  Save Recalibration
                </el-button>
              </div>
            </el-tab-pane>
          </el-tabs>
        </el-card>

        <!-- Print Preview Dialog -->
        <el-dialog v-model="previewVisible" title="OPCR Print Preview" width="80%" :close-on-click-modal="false" @close="closePreview">
          <div v-if="previewLoading" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-slate-600">Generating PDF...</span>
          </div>
          <div v-else style="height:80vh;">
            <iframe :src="previewUrl" ref="previewFrame" style="width:100%;height:100%;border:0;"></iframe>
          </div>
          <template #footer>
            <div class="flex items-center justify-between w-full">
              <div class="text-sm text-slate-500">PDF generated from current OPCR entry</div>
              <div>
                <el-button @click="downloadPreview" :disabled="!previewUrl">Download</el-button>
                <el-button type="primary" @click="printPreview" :disabled="!previewUrl">Print</el-button>
                <el-button @click="closePreview">Close</el-button>
              </div>
            </div>
          </template>
        </el-dialog>

      </div>
    </div>
    <div v-else-if="!accessLoaded" class="p-6 text-center text-slate-600">
      Loading access...
    </div>
    <div v-else class="p-6 text-center text-slate-600">
      OPCR is not available. It will appear when there is an ongoing OPCR you need to complete.
    </div>
  </MainLayout>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElNotification } from 'element-plus'
import { Plus, Search, ArrowRight, Document, Close, Delete, Edit } from '@element-plus/icons-vue'
import MainLayout from '../../layout/MainLayout.vue'

export default {
  name: 'OPCRView',
  components: {
    MainLayout,
    Plus,
    Search,
    ArrowRight,
    Document,
    Close,
    Delete,
    Edit
  },
  setup() {
    const hasPortalAccess = ref(false)
    const accessLoaded = ref(false)
    const formRef = ref(null)
    const activeTab = ref('form')
    const showForm = ref(true)
    const selectedRecord = ref(null)
    const searchQuery = ref('')
    
    const rules = {
      division: [
        { required: true, message: 'Division is required', trigger: 'blur' }
      ],
      period: [
        { 
          required: true, 
          message: 'Period is required', 
          trigger: 'change',
          validator: (rule, value, callback) => {
            if (!value || !Array.isArray(value) || value.length !== 2) {
              callback(new Error('Period must have both start and end dates'))
            } else if (!value[0] || !value[1]) {
              callback(new Error('Both start and end dates are required'))
            } else if (new Date(value[0]) > new Date(value[1])) {
              callback(new Error('Start date must be before end date'))
            } else {
              callback()
            }
          }
        }
      ],
    }

    const outputSections = [
      { key: 'strategicOutputs', title: 'Strategic Priority', placeholder: 'MFO/PAP', addLabel: 'Strategic Row' },
      { key: 'coreOutputs', title: 'Core Functions', placeholder: 'MFO/PAP', addLabel: 'Core Row' },
      { key: 'supportOutputs', title: 'Support Functions', placeholder: 'MFO/PAP', addLabel: 'Support Row' }
    ]

    const categorySummaryDefaults = () => ({
      strategic: { percentage: '' },
      core: { percentage: '' },
      support: { percentage: '' }
    })

    const categorySummaryRowDefs = [
      { key: 'strategic', label: 'Strategic Priority' },
      { key: 'core', label: 'Core Functions' },
      { key: 'support', label: 'Support Functions' }
    ]

    const defaultRow = () => ({
      mfoPap: '',
      successIndicators: '',
      allottedBudget: 0,
      divisionIndividualsAccountable: '',
      actualAccomplishments: '',
      q: '',
      e: '',
      t: '',
      a: '',
      remarks: ''
    })

    const form = reactive({
      division: '',
      period: [],
      planningOfficer: '',
      planningOfficerEmployeeId: null,
      planningOfficerDate: null,
      approvedBy: '',
      approvedByEmployeeId: null,
      approvedDate: null,
      strategicOutputs: [defaultRow()],
      coreOutputs: [defaultRow()],
      supportOutputs: [defaultRow()],
      assessedBy: '',
      assessedByEmployeeId: null,
      assessedDate: null,
      finalRater: '',
      finalRaterEmployeeId: null,
      finalRateDate: null,
      categorySummary: categorySummaryDefaults()
    })

    const opcrRecords = ref([])
    const loading = ref(false)
    const submitting = ref(false)
    const employees = ref([])
    const departmentEmployees = ref([])
    const previewVisible = ref(false)
    const previewUrl = ref('')
    const previewLoading = ref(false)
    const previewFrame = ref(null)
    const fixedStart = ref(null)
    const isPMT = ref(false)
    const isHR = ref(false)

    const canViewOpcrFormTab = computed(() => hasPortalAccess.value)
    const canViewRecalibrationTab = computed(() => hasPortalAccess.value)
    const canViewAnyOpcr = computed(() => hasPortalAccess.value)

    // Recalibration state (HR/PMT only)
    const recalibrationLevel = ref('hr') // 'hr' or 'pmt'
    const recalibrationOutputs = ref([])
    const recalibrationSubmitting = ref(false)
    const recalibrationStatus = ref('submitted')

    const isFormLocked = computed(() => recalibrationStatus.value === 'pmt_recalibrated')

    const isCategorySummaryFormDisabled = computed(() => {
      if (isFormLocked.value) return true
      if (isPMT.value && canViewRecalibrationTab.value) return true
      return false
    })

    const categorySummaryRows = computed(() => {
      const outputMap = {
        strategic: form.strategicOutputs,
        core: form.coreOutputs,
        support: form.supportOutputs
      }
      return categorySummaryRowDefs.map((row) => ({
        ...row,
        mfoCount: outputMap[row.key].filter((entry) => rowHasContent(entry)).length || '',
        rating: categoryAvgFromRows(outputMap[row.key])
      }))
    })

    const filteredRecords = computed(() => {
      if (!searchQuery.value) return opcrRecords.value
      const query = searchQuery.value.toLowerCase()
      return opcrRecords.value.filter(record => 
        (record.period || '').toLowerCase().includes(query) ||
        (record.division || '').toLowerCase().includes(query)
      )
    })

    function formatRecalibrationStatus(status) {
      const labels = {
        submitted: '',
        hr_recalibrated: 'HR Recalibrated',
        pmt_recalibrated: 'PMT Recalibrated'
      }
      return labels[status] || ''
    }

    function canShowRecalibrateButton(record) {
      if (!record || !canViewRecalibrationTab.value) return false
      if (isHR.value && record.can_recalibrate_hr) return true
      if (isPMT.value && record.can_recalibrate_pmt) return true
      return false
    }

    function applyRecalibrationMeta(data) {
      recalibrationStatus.value = data?.recalibration_status || 'submitted'
      if (selectedRecord.value) {
        selectedRecord.value = {
          ...selectedRecord.value,
          recalibration_status: recalibrationStatus.value,
          is_locked: !!data?.is_locked,
          can_recalibrate_hr: !!data?.can_recalibrate_hr,
          can_recalibrate_pmt: !!data?.can_recalibrate_pmt
        }
      }
    }

    function syncSelectedRecordFromList(id) {
      const updated = opcrRecords.value.find((record) => record.id === id)
      if (updated) {
        selectedRecord.value = { ...updated }
      }
    }

    function collectRatingValues(q, e, t) {
      return [q, e, t]
        .map(v => Number(v))
        .filter(v => Number.isFinite(v) && v >= 2 && v <= 5)
    }

    function normalizeRatingForDisplay(v) {
      const n = Number(v)
      if (!Number.isFinite(n) || n < 2 || n > 5) return ''
      return n
    }

    function normalizeAverageForDisplay(v) {
      const n = Number(v)
      if (!Number.isFinite(n) || n < 2 || n > 5) return ''
      return Number.isInteger(n) ? String(n) : n.toFixed(2)
    }

    function ratingForPayload(v) {
      const n = Number(v)
      if (!Number.isFinite(n) || n < 2 || n > 5) return null
      return n
    }

    function averageRatingForPayload(q, e, t, fallback = null) {
      const values = [q, e, t].filter(v => v !== null)
      if (values.length) {
        const avg = values.reduce((sum, v) => sum + v, 0) / values.length
        return Math.round(Math.max(2, Math.min(5, avg)) * 100) / 100
      }
      const fb = Number(fallback)
      return Number.isFinite(fb) && fb >= 2 ? Math.round(fb * 100) / 100 : null
    }

    function mapOutputFromApi(o) {
      const q = normalizeRatingForDisplay(o.q)
      const e = normalizeRatingForDisplay(o.e)
      const t = normalizeRatingForDisplay(o.t)
      return {
        id: o.id != null ? Number(o.id) : undefined,
        mfoPap: o.mfoPap ?? '',
        successIndicators: o.successIndicators ?? '',
        allottedBudget: o.allottedBudget ?? 0,
        divisionIndividualsAccountable: o.divisionIndividualsAccountable ?? '',
        actualAccomplishments: o.actualAccomplishments ?? '',
        q,
        e,
        t,
        a: normalizeAverageForDisplay(o.a) || computeAverage(q, e, t),
        remarks: o.remarks ?? '',
        hr_recalibration: o.hr_recalibration ?? null,
        pmt_recalibration: o.pmt_recalibration ?? null
      }
    }

    function resolveFunctionType(o) {
      const type = String(o.functionType || o.function_type || '').toLowerCase().trim()
      if (type === 'strategic' || type === 'support') return type
      const category = String(o.category || '').toLowerCase()
      if (category.includes('strateg')) return 'strategic'
      if (category.includes('support')) return 'support'
      return 'core'
    }

    function applyOutputsToForm(outputs) {
      const strategic = []
      const core = []
      const support = []

      for (const o of outputs || []) {
        const type = resolveFunctionType(o)
        const row = mapOutputFromApi(o)
        if (type === 'strategic') strategic.push(row)
        else if (type === 'support') support.push(row)
        else core.push(row)
      }

      form.strategicOutputs = strategic.length ? strategic : [defaultRow()]
      form.coreOutputs = core.length ? core : [defaultRow()]
      form.supportOutputs = support.length ? support : [defaultRow()]
    }

    function getAllFormOutputs() {
      return [...form.strategicOutputs, ...form.coreOutputs, ...form.supportOutputs]
    }

    function rowHasContent(row) {
      return [
        row.mfoPap,
        row.successIndicators,
        row.actualAccomplishments,
        row.divisionIndividualsAccountable
      ].some(v => String(v ?? '').trim() !== '')
    }

    function mergeOutputsForPayload() {
      return [
        ...form.strategicOutputs.map(o => ({ ...o, functionType: 'strategic', function_type: 'strategic' })),
        ...form.coreOutputs.map(o => ({ ...o, functionType: 'core', function_type: 'core' })),
        ...form.supportOutputs.map(o => ({ ...o, functionType: 'support', function_type: 'support' }))
      ].filter(rowHasContent)
    }

    function categoryAvgFromRows(rows) {
      const values = (rows || [])
        .map((row) => parseFloat(row.a))
        .filter((value) => !Number.isNaN(value) && value >= 2)
      if (!values.length) return ''
      return (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(2)
    }

    function applyCategorySummaryToForm(summary) {
      const source = summary || {}
      ;['strategic', 'core', 'support'].forEach((key) => {
        const entry = source[key] || {}
        const stored = entry.percentage ?? entry.mfo ?? null
        form.categorySummary[key] = {
          percentage: stored !== null && stored !== undefined && stored !== ''
            ? String(stored)
            : ''
        }
      })
    }

    function buildCategorySummaryPayload() {
      const payload = categorySummaryDefaults()
      ;['strategic', 'core', 'support'].forEach((key) => {
        const entry = form.categorySummary[key] || {}
        payload[key].percentage = entry.percentage === '' || entry.percentage === null
          ? null
          : Number(entry.percentage)
      })
      return payload
    }

    function todayDateString() {
      return new Date().toISOString().slice(0, 10)
    }

    function ensureSignatoryDates() {
      const today = todayDateString()
      if (!form.planningOfficerDate) form.planningOfficerDate = today
      if (!form.approvedDate) form.approvedDate = today
      if (!form.assessedDate) form.assessedDate = today
      if (!form.finalRateDate) form.finalRateDate = today
    }

    const breadcrumbs = [
      { name: 'Dashboard', path: '/dashboard' },
      { name: 'OPCR', path: '/opcr' }
    ]

    async function loadFormData() {
      try {
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.getEmployeeOPCRFormData()
        if (response && response.success) {
          const data = response.data
          form.division = data.division || ''
          employees.value = data.employees || []
          departmentEmployees.value = data.department_employees || []
          
          // Auto-set Approved by = Agency Head (branch_head_id)
          if (data.agency_head && data.agency_head.id) {
            form.approvedByEmployeeId = data.agency_head.id
            form.approvedBy = data.agency_head.name || ''
          }

          // Automatically set "Assessed by" if PMT member is available
          if (data.pmt_assessed_by && data.pmt_assessed_by.id) {
            form.assessedByEmployeeId = data.pmt_assessed_by.id
            form.assessedBy = data.pmt_assessed_by.name || ''
          }
        } else {
          ElNotification({
            title: 'Warning',
            message: response?.message || 'Failed to load form data. Please refresh the page.',
            type: 'warning',
            duration: 5000
          })
        }
      } catch (error) {
        console.error('Error loading form data:', error)
        ElNotification({
          title: 'Error',
          message: error?.message || 'Failed to load form data. Please check your connection and try again.',
          type: 'error',
          duration: 6000
        })
      }
    }

    async function loadOPCRRecords() {
      try {
        loading.value = true
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.getEmployeeOPCRList()
        if (response && response.success) {
          opcrRecords.value = response.data || []
        } else {
          ElNotification({
            title: 'Error',
            message: response?.message || 'Failed to load OPCR records. Please try again.',
            type: 'error',
            duration: 6000
          })
        }
      } catch (error) {
        console.error('Error loading OPCR records:', error)
        ElNotification({
          title: 'Error',
          message: error?.message || 'Failed to load OPCR records. Please check your connection and try again.',
          type: 'error',
          duration: 6000
        })
      } finally {
        loading.value = false
      }
    }

    async function loadOPCRRecord(id) {
      try {
        loading.value = true
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.getEmployeeOPCR(id)
        if (response && response.success) {
          const data = response.data
          form.division = data.division || ''
          form.period = data.period || []
          form.planningOfficerEmployeeId = data.planningOfficerEmployeeId || null
          form.planningOfficer = data.planningOfficer || ''
          form.planningOfficerDate = data.planningOfficerDate || null
          form.approvedBy = data.approvedBy || ''
          form.approvedByEmployeeId = data.approvedByEmployeeId || null
          form.approvedDate = data.approvedDate || null
          applyOutputsToForm(data.outputs)
          applyCategorySummaryToForm(data.categorySummary)
          
          // Set assessedBy - use existing value if set, otherwise auto-populate from PMT
          if (data.assessedByEmployeeId) {
            form.assessedBy = data.assessedBy || ''
            form.assessedByEmployeeId = data.assessedByEmployeeId
          } else if (data.pmt_assessed_by && data.pmt_assessed_by.id) {
            // Auto-populate if not already set
            form.assessedByEmployeeId = data.pmt_assessed_by.id
            form.assessedBy = data.pmt_assessed_by.name || ''
          } else {
          form.assessedBy = data.assessedBy || ''
          form.assessedByEmployeeId = data.assessedByEmployeeId || null
          }
          
          form.assessedDate = data.assessedDate || null
          form.finalRater = data.finalRater || ''
          form.finalRaterEmployeeId = data.finalRaterEmployeeId || null
          form.finalRateDate = data.finalRateDate || null
          
          if (data.employees) {
            employees.value = data.employees
          }
          if (data.department_employees) {
            departmentEmployees.value = data.department_employees
          }

          applyRecalibrationMeta(data)
        } else {
          ElNotification({
            title: 'Error',
            message: response?.message || 'Failed to load OPCR record. Please try again.',
            type: 'error',
            duration: 6000
          })
        }
      } catch (error) {
        console.error('Error loading OPCR record:', error)
        ElNotification({
          title: 'Error',
          message: error?.message || 'Failed to load OPCR record. Please check your connection and try again.',
          type: 'error',
          duration: 6000
        })
      } finally {
        loading.value = false
      }
    }

    function addRow(sectionKey) {
      form[sectionKey].push(defaultRow())
    }

    function removeRow(sectionKey, idx) {
      if (form[sectionKey].length > 1) form[sectionKey].splice(idx, 1)
    }

    async function selectRecord(record) {
      selectedRecord.value = record
      showForm.value = true
      resetForm()
      await loadOPCRRecord(record.id)
      // Ensure active tab is allowed by access rights.
      if (canViewOpcrFormTab.value) activeTab.value = 'form'
      else if (canViewRecalibrationTab.value && !isFormLocked.value) activeTab.value = 'recalibration'
      else activeTab.value = 'form'
    }

    async function recalibrateFromList(record) {
      if (!record?.id || !canShowRecalibrateButton(record)) return
      await selectRecord(record)
      // Default: HR if available, otherwise PMT
      const level = isHR.value ? 'hr' : 'pmt'
      openRecalibration(level)
    }

    function closeForm() {
      showForm.value = false
      selectedRecord.value = null
      recalibrationStatus.value = 'submitted'
      resetForm()
      if (canViewOpcrFormTab.value) activeTab.value = 'form'
      else if (canViewRecalibrationTab.value) activeTab.value = 'recalibration'
      else activeTab.value = 'form'
    }

    function handlePeriodChange(val) {
      if (!val || val.length < 2) {
        fixedStart.value = null
        return
      }
      if (!fixedStart.value) {
        fixedStart.value = val[0]
        return
      }
      if (val[0] !== fixedStart.value) {
        form.period = [fixedStart.value, val[1]]
      }
    }

    async function previewOPCR(id) {
      if (!id) return
      try {
        previewLoading.value = true
        const ApiService = (await import('../../services/api.js')).default
        const blob = await ApiService.downloadEmployeeOPCRPDF(id)
        if (blob) {
          if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
          previewUrl.value = URL.createObjectURL(blob)
          previewVisible.value = true
        } else {
          ElNotification({
            title: 'Error',
            message: 'Failed to generate OPCR PDF. Please try again.',
            type: 'error',
            duration: 6000
          })
        }
      } catch (e) {
        console.error('Preview failed:', e)
        ElNotification({
          title: 'Error',
          message: e?.message || 'Failed to generate OPCR PDF. Please check your connection and try again.',
          type: 'error',
          duration: 6000
        })
      } finally {
        previewLoading.value = false
      }
    }

    function downloadPreview() {
      if (!previewUrl.value) return
      const a = document.createElement('a')
      a.href = previewUrl.value
      a.download = selectedRecord.value ? `opcr_${selectedRecord.value.id}.pdf` : 'opcr.pdf'
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
    }

    function printPreview() {
      const frame = previewFrame.value
      if (frame && frame.contentWindow) {
        frame.contentWindow.focus()
        frame.contentWindow.print()
      }
    }

    function closePreview() {
      previewVisible.value = false
      if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = ''
      }
    }

    // Validate outputs array
    function validateOutputs() {
      const errors = []
      const outputRows = mergeOutputsForPayload()

      if (!outputRows.length) {
        errors.push('Add at least one output row in Strategic, Core, or Support functions.')
        return errors
      }

      outputRows.forEach((output, index) => {
        const rowNum = index + 1
        if (!output.mfoPap || output.mfoPap.trim() === '') {
          errors.push(`Row ${rowNum}: MFO/PAP is required`)
        }

        const ratings = ['q', 'e', 't']
        const ratingLabels = { q: 'Quality (Q)', e: 'Efficiency (E)', t: 'Timeliness (T)' }
        ratings.forEach(rating => {
          const value = output[rating]
          if (value !== null && value !== undefined && value !== '') {
            const numValue = Number(value)
            if (isNaN(numValue) || numValue < 2 || numValue > 5) {
              errors.push(`Row ${rowNum}: ${ratingLabels[rating]} rating must be between 2 and 5 (current: ${value})`)
            }
          }
        })
      })

      return errors
    }
    
    // Parse backend validation errors
    function parseBackendErrors(response) {
      const errorMessages = []
      
      if (response?.errors) {
        // Handle Laravel validation errors format
        const errors = response.errors
        
        if (typeof errors === 'object') {
          Object.keys(errors).forEach(field => {
            const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]]
            fieldErrors.forEach(error => {
              // Format field names for better readability
              let fieldName = field
                .replace(/([A-Z])/g, ' $1')
                .replace(/^./, str => str.toUpperCase())
                .replace(/period\.\d+/, (match) => {
                  const index = match.match(/\d+/)[0]
                  return `Period ${index === '0' ? 'Start' : 'End'} Date`
                })
                .replace(/outputs\.\d+\.(\w+)/, (match, prop) => {
                  const rowMatch = match.match(/outputs\.(\d+)/)
                  const rowNum = rowMatch ? parseInt(rowMatch[1]) + 1 : '?'
                  const propNames = {
                    mfoPap: 'MFO/PAP',
                    successIndicators: 'Success Indicators',
                    allottedBudget: 'Allotted Budget',
                    divisionIndividualsAccountable: 'Division/Individuals Accountable',
                    actualAccomplishments: 'Actual Accomplishments',
                    q: 'Quality (Q)',
                    e: 'Efficiency (E)',
                    t: 'Timeliness (T)',
                    a: 'Average (A)',
                    remarks: 'Remarks'
                  }
                  return `Row ${rowNum} - ${propNames[prop] || prop}`
                })
              
              errorMessages.push(`${fieldName}: ${error}`)
            })
          })
        }
      }
      
      return errorMessages
    }

    async function submitForm() {
      if (!formRef.value) return
      if (isFormLocked.value) {
        ElMessage.warning('This OPCR is locked after PMT recalibration.')
        return
      }
      
      try {
        // Validate form fields
        await formRef.value.validate()
        
        // Validate outputs array
        const outputErrors = validateOutputs()
        if (outputErrors.length > 0) {
          ElNotification({
            title: 'Validation Error',
            message: outputErrors.join('\n'),
            type: 'error',
            duration: 6000,
            dangerouslyUseHTMLString: false
          })
          return
        }
        
        submitting.value = true
        ensureSignatoryDates()
        const ApiService = (await import('../../services/api.js')).default
        const outputRows = mergeOutputsForPayload()

        const submitData = {
          id: selectedRecord.value?.id || 0,
          division: form.division,
          period: form.period,
          planningOfficerEmployeeId: form.planningOfficerEmployeeId,
          planningOfficerDate: form.planningOfficerDate,
          approvedByEmployeeId: form.approvedByEmployeeId,
          approvedDate: form.approvedDate,
          outputs: outputRows.map(o => {
            const q = ratingForPayload(o.q)
            const e = ratingForPayload(o.e)
            const t = ratingForPayload(o.t)
            return {
              id: o.id,
              mfoPap: o.mfoPap ?? '',
              successIndicators: o.successIndicators ?? '',
              allottedBudget: o.allottedBudget ?? 0,
              divisionIndividualsAccountable: o.divisionIndividualsAccountable ?? '',
              actualAccomplishments: o.actualAccomplishments ?? '',
              functionType: o.functionType,
              function_type: o.function_type || o.functionType,
              q,
              e,
              t,
              a: averageRatingForPayload(q, e, t, o.a),
              remarks: o.remarks ?? ''
            }
          }),
          assessedByEmployeeId: form.assessedByEmployeeId,
          assessedDate: form.assessedDate,
          finalRaterEmployeeId: form.finalRaterEmployeeId,
          finalRateDate: form.finalRateDate,
          categorySummary: buildCategorySummaryPayload()
        }
        
        const response = await ApiService.saveEmployeeOPCR(submitData)
        
        if (response && response.success) {
          ElMessage.success('OPCR saved successfully!')
          const savedId = Number(response.data?.id || selectedRecord.value?.id || 0)
          if (savedId) {
            selectedRecord.value = { ...(selectedRecord.value || {}), id: savedId }
            await loadOPCRRecord(savedId)
          }
          await loadOPCRRecords()
        } else {
          // Parse and display backend validation errors
          const errorMessages = parseBackendErrors(response)
          
          if (errorMessages.length > 0) {
            ElNotification({
              title: 'Validation Error',
              message: errorMessages.join('\n'),
              type: 'error',
              duration: 8000,
              dangerouslyUseHTMLString: false
            })
          } else {
            ElNotification({
              title: 'Error',
              message: response?.message || 'Failed to save OPCR. Please check your input and try again.',
              type: 'error',
              duration: 6000
            })
          }
        }
      } catch (errors) {
        // Handle form validation errors
        if (errors && typeof errors === 'object') {
          if (errors.message) {
            // Network or API error
            ElNotification({
              title: 'Error',
              message: errors.message || 'An error occurred while saving OPCR. Please try again.',
              type: 'error',
              duration: 6000
            })
        } else {
            // Form validation errors from Element Plus
            const validationErrors = []
            if (errors.fields) {
              Object.keys(errors.fields).forEach(field => {
                const fieldErrors = errors.fields[field]
                if (Array.isArray(fieldErrors)) {
                  fieldErrors.forEach(err => {
                    validationErrors.push(`${field}: ${err.message || err}`)
                  })
                }
              })
            }
            
            if (validationErrors.length > 0) {
              ElNotification({
                title: 'Validation Error',
                message: validationErrors.join('\n'),
                type: 'error',
                duration: 6000
              })
            } else {
              ElNotification({
                title: 'Validation Error',
                message: 'Please fill in all required fields correctly.',
                type: 'error',
                duration: 6000
              })
        }
          }
        } else {
          ElNotification({
            title: 'Error',
            message: 'An unexpected error occurred. Please try again.',
            type: 'error',
            duration: 6000
          })
        }
        console.error('OPCR submission error:', errors)
      } finally {
        submitting.value = false
      }
    }

    function resetForm() {
      form.period = []
      form.planningOfficer = ''
      form.planningOfficerEmployeeId = null
      form.planningOfficerDate = null
      form.approvedBy = ''
      form.approvedByEmployeeId = null
      form.approvedDate = null
      form.strategicOutputs = [defaultRow()]
      form.coreOutputs = [defaultRow()]
      form.supportOutputs = [defaultRow()]
      form.assessedBy = ''
      form.assessedByEmployeeId = null
      form.assessedDate = null
      form.finalRater = ''
      form.finalRaterEmployeeId = null
      form.finalRateDate = null
      form.categorySummary = categorySummaryDefaults()
      fixedStart.value = null
    }

    function onApprovedByChange(employeeId) {
      const emp = employees.value.find(e => e.id === employeeId)
      form.approvedBy = emp ? emp.name : ''
    }

    function onPlanningOfficerChange(employeeId) {
      const emp = employees.value.find(e => e.id === employeeId)
      form.planningOfficer = emp ? emp.name : ''
    }

    function onAssessedByChange(employeeId) {
      const emp = employees.value.find(e => e.id === employeeId)
      form.assessedBy = emp ? emp.name : ''
    }

    function onFinalRaterChange(employeeId) {
      const emp = employees.value.find(e => e.id === employeeId)
      form.finalRater = emp ? emp.name : ''
    }

    function computeAverage(q, e, t) {
      const values = collectRatingValues(q, e, t)
      if (!values.length) return ''
      const avg = values.reduce((sum, v) => sum + v, 0) / values.length
      const clamped = Math.max(2, Math.min(5, avg))
      return clamped.toFixed(2)
    }

    function onRatingInput(row, key) {
      let v = String(row[key] ?? '').replace(/[^0-9]/g, '')
      if (v === '' || v === '0' || v === '1') {
        row[key] = ''
        row.a = computeAverage(row.q, row.e, row.t)
        return
      }
      const n = Math.max(2, Math.min(5, parseInt(v, 10)))
      row[key] = n
      row.a = computeAverage(row.q, row.e, row.t)
    }

    function onRecalibrationRatingInput(row, key) {
      let v = String(row[key] ?? '').replace(/[^0-9]/g, '')
      if (v === '') {
        row[key] = ''
        if (key === 'q' || key === 'e' || key === 't') row.a = ''
        return
      }
      const n = Math.max(2, Math.min(5, parseInt(v, 10)))
      row[key] = n

      if (key === 'q' || key === 'e' || key === 't') {
        row.a = computeAverage(row.q, row.e, row.t)
      }
    }

    function openRecalibration(level) {
      if (!selectedRecord.value || !selectedRecord.value.id) return
      if (isFormLocked.value) return
      if (!canViewRecalibrationTab.value) {
        ElNotification({
          title: 'Access Denied',
          message: 'You do not have permission to view recalibration.',
          type: 'warning',
          duration: 5000,
        })
        return
      }
      // Determine recalibration level based on role or explicit parameter
      if (level) {
        recalibrationLevel.value = level
      } else {
        recalibrationLevel.value = (isPMT.value && !isHR.value) ? 'pmt' : 'hr'
      }
      recalibrationOutputs.value = getAllFormOutputs().map(o => {
        const existing = (recalibrationLevel.value === 'pmt' ? o.pmt_recalibration : o.hr_recalibration) || null
        return {
          id: o.id,
          mfoPap: o.mfoPap || '',
          successIndicators: o.successIndicators || '',
          // original (read-only display)
          orig_q: o.q ?? 2,
          orig_e: o.e ?? 2,
          orig_t: o.t ?? 2,
          orig_a: o.a ?? 2,
          orig_remarks: o.remarks ?? '',
          // recalibration inputs (editable)
          q: existing ? existing.q : '',
          e: existing ? existing.e : '',
          t: existing ? existing.t : '',
          a: existing ? existing.a : '',
          remarks: existing ? (existing.remarks ?? '') : ''
        }
      })
      activeTab.value = 'recalibration'
    }

    async function submitRecalibration() {
      if (!selectedRecord.value || !selectedRecord.value.id) return
      try {
        recalibrationSubmitting.value = true
        const ApiService = (await import('../../services/api.js')).default
        const payload = {
          outputs: recalibrationOutputs.value.map(o => ({
            id: o.id,
            q: o.q,
            e: o.e,
            t: o.t,
            a: o.a,
            remarks: o.remarks
          })),
          categorySummary: buildCategorySummaryPayload()
        }
        const response = await ApiService.saveOPCRRecalibration(
          selectedRecord.value.id,
          recalibrationLevel.value,
          payload
        )
        if (response && response.success) {
          ElMessage.success(`${recalibrationLevel.value.toUpperCase()} recalibration saved successfully!`)
          await loadOPCRRecords()
          await loadOPCRRecord(selectedRecord.value.id)
          syncSelectedRecordFromList(selectedRecord.value.id)
          activeTab.value = 'form'
        } else {
          ElNotification({
            title: 'Error',
            message: response?.message || 'Failed to save OPCR recalibration.',
            type: 'error',
            duration: 6000
          })
        }
      } catch (error) {
        console.error('OPCR recalibration error:', error)
        ElNotification({
          title: 'Error',
          message: error?.message || 'Failed to save OPCR recalibration.',
          type: 'error',
          duration: 6000
        })
      } finally {
        recalibrationSubmitting.value = false
      }
    }

    async function checkUserAccess() {
      try {
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.checkDivisionChiefAccessOPCR()
        if (response && response.success) {
          hasPortalAccess.value = !!response.data?.has_access
          isPMT.value = response.data?.is_pmt_user || false
        } else {
          hasPortalAccess.value = false
        }

        const storedUserData = localStorage.getItem('user_data')
        if (storedUserData) {
          const userData = JSON.parse(storedUserData)
          isHR.value = !!(userData.with_hrm_access || userData.is_admin)
        }
      } catch (error) {
        console.error('Error checking user access:', error)
        hasPortalAccess.value = false
      } finally {
        accessLoaded.value = true
      }
    }

    onMounted(async () => {
      await checkUserAccess()
      if (!hasPortalAccess.value) return
      await loadFormData()
      await loadOPCRRecords()
    })

    return {
      outputSections,
      formRef,
      rules,
      form,
      showForm,
      selectedRecord,
      searchQuery,
      opcrRecords,
      filteredRecords,
      breadcrumbs,
      loading,
      submitting,
      addRow,
      removeRow,
      selectRecord,
      recalibrateFromList,
      closeForm,
      submitForm,
      resetForm,
      loadOPCRRecords,
      loadOPCRRecord,
      loadFormData,
      employees,
      departmentEmployees,
      onApprovedByChange,
      onPlanningOfficerChange,
      onAssessedByChange,
      onFinalRaterChange,
      onRatingInput,
      onRecalibrationRatingInput,
      handlePeriodChange,
      previewVisible,
      previewUrl,
      previewLoading,
      previewFrame,
      previewOPCR,
      downloadPreview,
      printPreview,
      closePreview,
      isPMT,
      isHR,
      activeTab,
      canViewOpcrFormTab,
      canViewRecalibrationTab,
      canViewAnyOpcr,
      accessLoaded,
      recalibrationOutputs,
      recalibrationLevel,
      recalibrationSubmitting,
      openRecalibration,
      submitRecalibration,
      recalibrationStatus,
      isFormLocked,
      isCategorySummaryFormDisabled,
      categorySummaryRows,
      formatRecalibrationStatus,
      canShowRecalibrateButton
    }
  }
}
</script>

<style scoped>
.opcr-layout {
  display: flex;
  gap: 1.5rem;
  height: calc(100vh - 200px);
  min-height: 600px;
}

.opcr-left-panel {
  flex: 0 0 400px;
  display: flex;
  flex-direction: column;
  min-height: 0;
  height: 100%;
  overflow: hidden;
  gap: 12px;
}

.opcr-records-card {
  flex: 1 1 auto;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.opcr-records-card :deep(.el-card__header) {
  flex-shrink: 0;
  padding: 12px 16px;
}

.opcr-records-card :deep(.el-card__body) {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding: 12px 16px 16px;
}

.opcr-search {
  flex-shrink: 0;
  margin-bottom: 12px;
}

.opcr-info-card {
  flex: 0 0 auto;
  flex-shrink: 0;
}

.opcr-info-card :deep(.el-card__header) {
  padding: 10px 16px;
}

.opcr-info-card :deep(.el-card__body) {
  padding: 10px 16px 14px;
}

.opcr-info-body {
  line-height: 1.45;
}

.opcr-info-label {
  margin: 0 0 4px;
}

.opcr-info-ratings {
  margin-top: 8px;
}

.opcr-info-ratings ul {
  margin: 4px 0 0;
  padding-left: 1.25rem;
}

.opcr-info-ratings li {
  margin: 2px 0;
}

.opcr-info-note {
  margin: 8px 0 0;
  font-size: 0.8125rem;
}

.opcr-right-panel {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: all 0.3s ease;
  max-width: 1200px;
}

.opcr-right-panel:not(.panel-visible) {
  opacity: 0;
  pointer-events: none;
  transform: translateX(20px);
}

.opcr-right-panel.panel-visible {
  opacity: 1;
  pointer-events: all;
  transform: translateX(0);
}

.opcr-form-card {
  flex: 1;
  min-height: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.opcr-form-card :deep(.el-card) {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.opcr-form-card :deep(.el-card__header) {
  flex-shrink: 0;
}

.opcr-form-card :deep(.el-card__body) {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 20px;
}

.opcr-form {
  max-width: 100%;
}

.opcr-header {
  margin-bottom: 20px;
}

.opcr-list {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding-right: 4px;
}

.opcr-list-item {
  padding: 12px;
  margin-bottom: 8px;
  border: 1px solid #e4e7ed;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.opcr-list-item:hover {
  background-color: #f5f7fa;
  border-color: #409eff;
}

.opcr-list-item.active {
  background-color: #ecf5ff;
  border-color: #409eff;
}

.add-row {
  margin: 16px 0 0 0;
}

.table-container {
  margin: 20px 0;
  overflow-x: auto;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e4e7ed;
}

.close-btn {
  padding: 4px;
}

:deep(.el-form-item) {
  margin-bottom: 18px;
}

:deep(.el-input),
:deep(.el-date-picker),
:deep(.el-textarea),
:deep(.el-input-number) {
  width: 100%;
}

:deep(.el-table) {
  margin: 20px 0;
}

:deep(.el-divider) {
  margin: 24px 0;
}

:deep(.el-col) {
  margin-bottom: 12px;
}

.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.justify-between {
  justify-content: space-between;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mr-1 {
  margin-right: 0.25rem;
}

.text-center {
  text-align: center;
}

.text-sm {
  font-size: 0.875rem;
}

.text-lg {
  font-size: 1.125rem;
}

.font-medium {
  font-weight: 500;
}

.font-semibold {
  font-weight: 600;
}

.text-slate-900 {
  color: #0f172a;
}

.text-slate-600 {
  color: #475569;
}

.text-slate-500 {
  color: #64748b;
}

.text-slate-400 {
  color: #94a3b8;
}

.py-8 {
  padding-top: 2rem;
  padding-bottom: 2rem;
}

.space-y-2 > * + * {
  margin-top: 0.5rem;
}

@media (max-width: 1200px) {
  .opcr-layout {
    flex-direction: column;
    height: auto;
    min-height: calc(100vh - 200px);
  }
  
  .opcr-left-panel {
    flex: 0 0 auto;
    width: 100%;
    max-height: none;
    height: min(520px, calc(100vh - 240px));
    min-height: 360px;
  }
  
  .opcr-right-panel {
    flex: 1;
    min-height: 400px;
  }
  
  .opcr-right-panel:not(.panel-visible) {
    display: none;
  }
}
</style>

