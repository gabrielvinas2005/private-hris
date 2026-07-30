<template>
  <PageScaffold title="Position Description" subtitle="Generate position description reports for recruitment">

    <!-- PDF Records Management -->
    <div class="mb-6 bg-white rounded-lg shadow">
      <div class="p-6">
        <h3 class="mb-4 text-lg font-semibold">PDF Records Management</h3>
        <div class="flex items-center justify-between mb-4">
          <el-button type="primary" @click="openAddPdfRecord">Add PDF Record</el-button>
          <el-button @click="fetchPdfRecords">Refresh Records</el-button>
          </div>
          
        <!-- PDF Records Table -->
        <el-table :data="pdfRecords" v-loading="loading" style="width: 100%">
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column prop="Employee_no" label="Employee No" width="120" />
          <el-table-column label="Position Title" width="200">
            <template #default="scope">
              {{ scope.row.position_title || 'N/A' }}
            </template>
          </el-table-column>
          <el-table-column prop="item_number" label="Item Number" width="120" />
          <el-table-column label="Salary Grade" width="120">
            <template #default="scope">
              {{ scope.row.salary_grade_name || 'N/A' }}
            </template>
          </el-table-column>
          <el-table-column prop="position_description" label="Position Description" show-overflow-tooltip />
           <el-table-column label="Actions" width="280">
             <template #default="scope">
               <el-button size="small" type="success" @click="generateReportFromRecord(scope.row)">
                 <el-icon><Document /></el-icon>
                 Generate Report
               </el-button>
               <el-button size="small" @click="editPdfRecord(scope.row)">Edit</el-button>
               <el-button size="small" type="danger" @click="deletePdfRecord(scope.row.id)">Delete</el-button>
             </template>
           </el-table-column>
        </el-table>
            </div>
          </div>
          


    <!-- PDF Record Form Modal -->
    <el-dialog v-model="showPdfForm" :title="editingPdfRecord ? 'Edit PDF Record' : 'Add PDF Record'" width="1200px">
      <el-form :model="pdfFormData" label-width="200px">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <!-- Position Title -->
          <el-form-item label="Position Title" required>
            <el-select 
              v-model="pdfFormData.position_id"
              placeholder="Select position title"
              filterable
              clearable
              class="w-full"
              :loading="loading"
            >
              <el-option 
                v-for="pos in positions" 
                :key="pos.id" 
                :label="pos.name" 
                :value="pos.id" 
              />
            </el-select>
          </el-form-item>

          <!-- Item Number -->
          <el-form-item label="Item Number" required>
            <el-input v-model="pdfFormData.item_number" placeholder="Enter item number" />
          </el-form-item>

          <!-- Salary Grade -->
          <el-form-item label="Salary Grade" required>
            <el-select 
              v-model="pdfFormData.salarygrade_id" 
              placeholder="Select salary grade"
              filterable
              clearable
              class="w-full"
              :loading="loading"
            >
               <el-option 
                 v-for="grade in salaryGrades" 
                 :key="grade.id" 
                 :label="grade.name" 
                 :value="grade.id" 
               />
            </el-select>
          </el-form-item>

          <!-- Immediate Supervisor Position Title (Section 13) -->
          <el-form-item label="Immediate Supervisor Position">
            <el-select 
              v-model="pdfFormData.immediate_supervisor_position_id" 
              placeholder="Select immediate supervisor position"
              filterable
              clearable
              class="w-full"
              :loading="loading"
            >
              <el-option 
                v-for="pos in positions" 
                :key="pos.id" 
                :label="pos.name" 
                :value="String(pos.id)" 
              />
            </el-select>
          </el-form-item>

          <!-- Next Higher Supervisor Position Title (Section 14) -->
          <el-form-item label="Next Higher Supervisor">
            <el-select 
              v-model="pdfFormData.next_higher_supervisor_position_id" 
              placeholder="Select next higher supervisor position"
              filterable
              clearable
              class="w-full"
              :loading="loading"
            >
              <el-option 
                v-for="pos in positions" 
                :key="pos.id" 
                :label="pos.name" 
                :value="String(pos.id)" 
              />
            </el-select>
          </el-form-item>

          <!-- 16. Machine, Equipment, Tools -->
          <div class="md:col-span-2">
          <el-form-item label="16. Machine, Equipment, Tools, etc.">
            <el-input
              v-model="pdfFormData.equiptment"
              type="textarea"
              :rows="3"
              placeholder="Computer/Laptop, Printer, Telephone, External Hard Drive, Switches, UTP Cable, Television, Headset and Microphone"
            />
          </el-form-item>
          </div>

          <!-- 17. Contacts / Clients / Stakeholders -->
          <div class="md:col-span-2">
          <el-form-item label="17. Contacts / Clients / Stakeholders">
            <div class="overflow-x-auto border border-gray-300 rounded">
              <table class="w-full text-xs">
                <thead class="bg-gray-200">
                  <tr>
                    <th class="p-2 text-left border border-gray-300">17a. Internal</th>
                    <th class="p-2 text-center border border-gray-300 w-24">Occasional</th>
                    <th class="p-2 text-center border border-gray-300 w-24">Frequent</th>
                    <th class="p-2 text-left border border-gray-300">17b. External</th>
                    <th class="p-2 text-center border border-gray-300 w-24">Occasional</th>
                    <th class="p-2 text-center border border-gray-300 w-24">Frequent</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="p-2 border border-gray-300">Executive / Managerial</td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.ieo" /></td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.ief" /></td>
                    <td class="p-2 border border-gray-300">General Public</td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.egpo" /></td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.egpf" /></td>
                  </tr>
                  <tr>
                    <td class="p-2 border border-gray-300">Supervisors</td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.iso" /></td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.isf" /></td>
                    <td class="p-2 border border-gray-300">Other Agencies</td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.eoao" /></td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.eoaf" /></td>
                  </tr>
                  <tr>
                    <td class="p-2 border border-gray-300">Non-Supervisors</td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.ino" /></td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.inf" /></td>
                    <td class="p-2 border border-gray-300">
                      Others (Please Specify):
                      <el-input v-model="stakeholdersChecks.eots" size="small" class="mt-1" placeholder="Specify" />
                    </td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.eoto" /></td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.eotf" /></td>
                  </tr>
                  <tr>
                    <td class="p-2 border border-gray-300">Staff</td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.isto" /></td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="stakeholdersChecks.istf" /></td>
                    <td class="p-2 border border-gray-300" colspan="3"></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </el-form-item>
          </div>

          <!-- 18. Working Condition -->
          <div class="md:col-span-2">
          <el-form-item label="18. Working Condition">
            <div class="overflow-x-auto border border-gray-300 rounded">
              <table class="w-full text-xs">
                <thead class="bg-gray-200">
                  <tr>
                    <th class="p-2 text-left border border-gray-300"></th>
                    <th class="p-2 text-center border border-gray-300 w-24">Occasional</th>
                    <th class="p-2 text-center border border-gray-300 w-24">Frequent</th>
                    <th class="p-2 text-left border border-gray-300"></th>
                    <th class="p-2 text-center border border-gray-300 w-24">Occasional</th>
                    <th class="p-2 text-center border border-gray-300 w-24">Frequent</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="p-2 border border-gray-300">Office Work</td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="workingConditionChecks.owo" /></td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="workingConditionChecks.owf" /></td>
                    <td class="p-2 border border-gray-300">
                      Other/s (Please Specify):
                      <el-input v-model="workingConditionChecks.ots" size="small" class="mt-1" placeholder="Specify" />
                    </td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="workingConditionChecks.oto" /></td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="workingConditionChecks.otf" /></td>
                  </tr>
                  <tr>
                    <td class="p-2 border border-gray-300">Field Work</td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="workingConditionChecks.fwo" /></td>
                    <td class="p-2 text-center border border-gray-300"><el-checkbox v-model="workingConditionChecks.fwf" /></td>
                    <td class="p-2 border border-gray-300" colspan="3"></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </el-form-item>
          </div>

          <!-- Employee Name -->
          <el-form-item label="Select Employee/Applicant" required>
            <el-select 
              v-model="pdfFormData.Employee_no" 
              placeholder="Select employee"
              filterable
              clearable
              class="w-full"
              :loading="loading"
            >
              <el-option 
                v-for="employee in employees" 
                :key="employee.id" 
                :label="`${employee.name || 'N/A'} (${employee.employee_no || ''})`" 
                :value="employee.employee_no" 
              />
            </el-select>
            </el-form-item>

          <!-- Supervisor Name -->
          <el-form-item label="Supervisor Name">
            <el-select 
              v-model="pdfFormData.supervisor" 
              placeholder="Select supervisor"
              filterable
              clearable
              class="w-full"
              :loading="loading"
            >
              <el-option 
                v-for="employee in employees" 
                :key="employee.id" 
                :label="`${employee.name || 'N/A'} (${employee.employee_no || ''})`" 
                :value="employee.employee_no" 
              />
            </el-select>
            </el-form-item>

          <!-- Employee Signature Date -->
          <el-form-item label="Employee Date">
            <el-date-picker
              v-model="pdfFormData.employee_date"
              type="date"
              placeholder="Select employee date"
              format="MMMM DD, YYYY"
              value-format="YYYY-MM-DD"
              class="w-full"
            />
          </el-form-item>

          <!-- Supervisor Signature Date -->
          <el-form-item label="Supervisor Date">
            <el-date-picker
              v-model="pdfFormData.supervisor_date"
              type="date"
              placeholder="Select supervisor date"
              format="MMMM DD, YYYY"
              value-format="YYYY-MM-DD"
              class="w-full"
            />
          </el-form-item>
          </div>

        <!-- Supervised Positions -->
        <el-form-item label="Supervised Positions">
          <div class="supervised-positions">
            <div
              v-for="(row, index) in visibleSupervisedPositions"
              :key="index"
              class="supervised-row"
            >
              <el-select
                v-model="row.supervised_positionTitle_ID"
                placeholder="Select supervised position"
                filterable
                clearable
                class="supervised-position-select"
                :loading="loading"
                @change="onSupervisedPositionChange(row, index)"
              >
                <el-option
                  v-for="pos in positions"
                  :key="pos.id"
                  :label="pos.name"
                  :value="String(pos.id)"
                />
              </el-select>

              <el-input
                v-model="row.supervised_item_number"
                placeholder="Item number"
                class="supervised-item-input"
              />

              <el-button
                type="danger"
                plain
                @click="removeSupervisedPosition(index)"
              >
                Remove
              </el-button>
            </div>

            <div class="supervised-actions">
              <el-button
                type="primary"
                size="small"
                @click="addLocalSupervisedPosition"
              >
                Add Position
              </el-button>
            </div>
          </div>
        </el-form-item>

        <!-- Full Width Fields -->
        <el-form-item label="Brief Description of Unit">
          <el-input v-model="pdfFormData.unit_description" type="textarea" :rows="3" placeholder="Enter brief description of unit" />
        </el-form-item>

        <el-form-item label="Brief Description of Position" required>
          <el-input v-model="pdfFormData.position_description" type="textarea" :rows="4" placeholder="Enter brief description of position" />
          </el-form-item>

        <!-- Qualification Standards -->
        <div class="mb-6">
          <h4 class="mb-4 text-lg font-semibold">Qualification Standards</h4>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <el-form-item label="Education">
              <el-input v-model="pdfFormData.education" type="textarea" :rows="3" placeholder="Enter education requirements" />
            </el-form-item>

            <el-form-item label="Experience">
              <el-input v-model="pdfFormData.experience" type="textarea" :rows="3" placeholder="Enter experience requirements" />
            </el-form-item>

            <el-form-item label="Training">
              <el-input v-model="pdfFormData.training" type="textarea" :rows="3" placeholder="Enter training requirements" />
          </el-form-item>

            <el-form-item label="Eligibility">
              <el-input v-model="pdfFormData.eigibility" type="textarea" :rows="3" placeholder="Enter eligibility requirements" />
              </el-form-item>
            </div>
          </div>

        <!-- Core Competencies (Tabular) -->
        <div class="mb-6">
          <h4 class="mb-4 text-lg font-semibold">Core Competencies</h4>
          <el-table :data="coreCompetencies" size="small" border style="width: 100%">
            <el-table-column type="index" label="#" width="50" />
            <el-table-column label="Competency">
              <template #default="scope">
                <el-input v-model="scope.row.competency" placeholder="Enter competency" />
              </template>
            </el-table-column>
            <el-table-column label="Competency Level" width="260">
              <template #default="scope">
                <el-select v-model="scope.row.competency_level_id" placeholder="Select level" class="w-full">
                  <el-option 
                    v-for="level in competencyLevels" 
                    :key="level.id" 
                    :label="level.Level" 
                    :value="level.id" 
                  />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="120">
              <template #default="scope">
            <el-button type="danger" size="small" @click="onRemoveCoreCompetency(scope.$index)">Remove</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div class="mt-3">
            <el-button type="primary" circle @click="addLocalCoreCompetency" :title="'Add Core Competency'">
              <el-icon><Plus /></el-icon>
            </el-button>
            </div>
          </div>

        <!-- Leadership Competencies (Tabular) -->
        <div class="mb-6">
          <h4 class="mb-4 text-lg font-semibold">Leadership Competencies</h4>
          <el-table :data="leadershipCompetencies" size="small" border style="width: 100%">
            <el-table-column type="index" label="#" width="50" />
            <el-table-column label="Competency">
              <template #default="scope">
                <el-input v-model="scope.row.competency" placeholder="Enter competency" />
              </template>
            </el-table-column>
            <el-table-column label="Competency Level" width="260">
              <template #default="scope">
                <el-select v-model="scope.row.competency_level_id" placeholder="Select level" class="w-full">
                  <el-option 
                    v-for="level in competencyLevels" 
                    :key="level.id" 
                    :label="level.Level" 
                    :value="level.id" 
                  />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="120">
              <template #default="scope">
            <el-button type="danger" size="small" @click="onRemoveLeadershipCompetency(scope.$index)">Remove</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div class="mt-3">
            <el-button type="primary" circle @click="addLocalLeadershipCompetency" :title="'Add Leadership Competency'">
              <el-icon><Plus /></el-icon>
            </el-button>
      </div>
    </div>

        <!-- SODAR (Statement of Duties and Responsibilities) Tabular -->
        <div class="mb-6">
          <h4 class="mb-4 text-lg font-semibold">Statement of Duties and Responsibilities (SODAR)</h4>
          <el-table :data="sodarRecords" size="small" border style="width: 100%">
            <el-table-column type="index" label="#" width="50" />
            <el-table-column label="Percentage" width="160">
              <template #default="scope">
                <el-input
                  v-model="scope.row.percentage"
                  placeholder="Enter percentage"
                  type="number"
                  min="0"
                  step="0.01"
                />
              </template>
            </el-table-column>
            <el-table-column label="Responsibilities">
              <template #default="scope">
                <el-input v-model="scope.row.responsibilities" placeholder="Enter responsibilities" />
              </template>
            </el-table-column>
            <el-table-column label="Competency Level" width="260">
              <template #default="scope">
                <el-select v-model="scope.row.competency_level_id" placeholder="Select level" class="w-full">
                  <el-option 
                    v-for="level in competencyLevels" 
                    :key="level.id" 
                    :label="level.Level" 
                    :value="level.id" 
                  />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column label="Actions" width="120">
              <template #default="scope">
            <el-button type="danger" size="small" @click="onRemoveSodar(scope.$index)">Remove</el-button>
              </template>
            </el-table-column>
          </el-table>
          <div class="mt-3">
            <el-button type="primary" circle @click="addLocalSodarRecord" :title="'Add SODAR Record'">
              <el-icon><Plus /></el-icon>
          </el-button>
        </div>
      </div>
      </el-form>
      <template #footer>
        <el-button @click="cancelPdfForm">Cancel</el-button>
        <el-button type="primary" @click="savePdfRecord" :loading="loading">Save</el-button>
      </template>
    </el-dialog>

  <!-- PDF Preview Panel -->
  <div v-if="showPreview && pdfUrl" class="mt-4 bg-white rounded-lg shadow">
    <div class="flex items-center justify-between p-3 border-b">
      <h3 class="font-semibold text-md">Report Preview</h3>
      <div class="flex items-center gap-2">
        <el-date-picker
          v-model="previewEmployeeDate"
          type="date"
          placeholder="Employee date (optional)"
          value-format="YYYY-MM-DD"
          class="w-44"
        />
        <el-date-picker
          v-model="previewSupervisorDate"
          type="date"
          placeholder="Supervisor date (optional)"
          value-format="YYYY-MM-DD"
          class="w-44"
        />
        <el-button size="small" @click="regeneratePreview" :loading="generateLoading">
          Apply Dates
        </el-button>
        <el-button size="small" @click="clearPreviewDates" :loading="generateLoading">
          Clear Dates
        </el-button>
        <span class="text-sm text-gray-600">Download as:</span>
        <el-button type="danger" size="small" @click="downloadPdf" :loading="generateLoading">
          PDF
        </el-button>
        <el-button type="primary" size="small" @click="handleDownloadWord" :loading="generateLoading">
          Word
        </el-button>
        <el-button size="small" @click="closePreview">Close Preview</el-button>
      </div>
    </div>
    <div class="p-0">
      <embed :src="pdfUrl + '#toolbar=1&navpanes=1&scrollbar=1'" type="application/pdf" width="100%" height="700px" />
    </div>
    </div>

  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { ElMessage } from 'element-plus'
import PageScaffold from '../PageScaffold.vue'
import { Download, Printer, Close, Document, Plus } from '@element-plus/icons-vue'
import { usePositionDescription } from '../../composable/usePositionDescription.js'
import { positionDescriptionApi } from '../../services/api.js'
import {
  defaultStakeholdersChecks,
  defaultWorkingConditionChecks,
  parseStakeholdersChecks,
  parseWorkingConditionChecks,
  serializeStakeholdersChecks,
  serializeWorkingConditionChecks,
} from '../../utils/positionDescriptionChecks.js'

const {
  loading,
  generateLoading,
  pdfRecords,
  competencyLevels,
  employees,
  salaryGrades,
  positions,
  selectedPdfRecord,
  pdfFormData,
  sodarFormData,
  coreCompetencyFormData,
  leadershipCompetencyFormData,
  // PDF Records Management
  fetchPdfRecords,
  getPdfRecord,
  createPdfRecord,
  updatePdfRecord,
  deletePdfRecord,
  // SODAR Management
  addSodarRecord,
  deleteSodarRecord,
  // Competencies Management
  addCoreCompetency,
  deleteCoreCompetency,
  addLeadershipCompetency,
  deleteLeadershipCompetency,
  fetchCompetencyLevels,
  // Data fetching
  fetchPositions,
  fetchEmployees,
  fetchSalaryGrades,
  // Reset functions
  resetPdfForm,
  resetSodarForm,
  resetCoreCompetencyForm,
  resetLeadershipCompetencyForm,
  // Report generation
  generatePositionDescriptionPdf,
  downloadPDFFromBlob,
  downloadDocx,
  // Supervised positions
  addSupervisedPosition,
  deleteSupervisedPosition
} = usePositionDescription()
// Preview state
const showPreview = ref(false)
const pdfUrl = ref('')
const selectedPdfId = ref(null)
const currentPreviewRecord = ref(null)
const previewEmployeeDate = ref('')
const previewSupervisorDate = ref('')



// Local reactive variables for competencies, supervised positions, and SODAR
const coreCompetencies = ref([])
const leadershipCompetencies = ref([])
const sodarRecords = ref([])
const supervisedPositions = ref([])
const visibleSupervisedPositions = computed(() =>
  supervisedPositions.value.filter(row => !row._deleted)
)


// New reactive variables for database management
const showPdfForm = ref(false)
const showSodarForm = ref(false)
const showCoreCompetencyForm = ref(false)
const showLeadershipCompetencyForm = ref(false)
const editingPdfRecord = ref(null)

const stakeholdersChecks = ref(defaultStakeholdersChecks())
const workingConditionChecks = ref(defaultWorkingConditionChecks())

const resetCheckboxForms = () => {
  stakeholdersChecks.value = defaultStakeholdersChecks()
  workingConditionChecks.value = defaultWorkingConditionChecks()
}

// PDF Record Management Methods
const editPdfRecord = async (record) => {
  editingPdfRecord.value = record
  Object.assign(pdfFormData.value, record)
  stakeholdersChecks.value = parseStakeholdersChecks(record.stakeholders)
  workingConditionChecks.value = parseWorkingConditionChecks(record.working_Condition)
  // Normalize select-bound fields so dropdowns show labels
  pdfFormData.value.position_id = record.position_id ?? null
  pdfFormData.value.supervised_positionTitle_ID = record.supervised_positionTitle_ID != null ? String(record.supervised_positionTitle_ID) : null
  pdfFormData.value.immediate_supervisor_position_id = record.immediate_supervisor_position_id != null ? String(record.immediate_supervisor_position_id) : null
  pdfFormData.value.next_higher_supervisor_position_id = record.next_higher_supervisor_position_id != null ? String(record.next_higher_supervisor_position_id) : null
  // Avoid carrying computed titles into select models
  delete pdfFormData.value.position_title
  delete pdfFormData.value.supervised_position_title

  // Load related records (SODAR, core, leadership) for this PDF
  try {
    // Clear existing arrays to avoid visual duplication
    sodarRecords.value = []
    coreCompetencies.value = []
    leadershipCompetencies.value = []

    const full = await getPdfRecord(record.id)
    const pdf = full?.pdf || {}
    const sodar = full?.sodar || []
    const cores = full?.core_competencies || []
    const leaders = full?.leadership_competencies || []
    const supervised = full?.supervised_positions || []

    // Map to editable structures expected by the tabular editors
    sodarRecords.value = (Array.isArray(sodar) ? sodar : []).map(r => ({
      id: r.id,
      percentage: normalizeSodarPercentage(r.Percetage ?? r.percentage ?? ''),
      responsibilities: r.Responsibilities ?? r.responsibilities ?? '',
      competency_level_id: r.Competencylevel_id ?? r.competency_level_id ?? null,
      _existing: true,
    }))

    coreCompetencies.value = (Array.isArray(cores) ? cores : []).map(r => ({
      id: r.id,
      competency: r.Competency ?? r.competency ?? '',
      competency_level_id: r.CompetencyLevel_id ?? r.competency_level_id ?? null,
      _existing: true,
    }))

    leadershipCompetencies.value = (Array.isArray(leaders) ? leaders : []).map(r => ({
      id: r.id,
      competency: r.COmpetency ?? r.Competency ?? r.competency ?? '',
      competency_level_id: r.CompetencyLevel_id ?? r.competency_level_id ?? null,
      _existing: true,
    }))

    // Initialize supervised positions from database records, with legacy single-field fallback
    const mappedSupervised = (Array.isArray(supervised) ? supervised : []).map(r => ({
      id: r.id,
      supervised_positionTitle_ID: r.supervised_positionTitle_ID != null ? String(r.supervised_positionTitle_ID) : null,
      supervised_item_number: r.supervised_item_number ?? '',
      _existing: true,
      _deleted: false,
    }))

    if (mappedSupervised.length > 0) {
      supervisedPositions.value = mappedSupervised
    } else {
      supervisedPositions.value = [
        {
          id: null,
          supervised_positionTitle_ID: pdfFormData.value.supervised_positionTitle_ID ?? null,
          supervised_item_number: pdfFormData.value.supervised_item_number ?? '',
          _existing: false,
          _deleted: false,
        },
      ]
    }
  } catch (e) {
    console.error('Failed to load related records for editing:', e)
  }

  showPdfForm.value = true
}

const savePdfRecord = async () => {
  try {
    // Keep legacy single supervised fields in sync with the first non-deleted row
    const first = supervisedPositions.value.find(r => !r._deleted) || {
      supervised_positionTitle_ID: null,
      supervised_item_number: '',
    }
    pdfFormData.value.supervised_positionTitle_ID = first.supervised_positionTitle_ID || null
    pdfFormData.value.supervised_item_number = first.supervised_item_number || ''

    // Do not persist preview-only signature dates to PDF table.
    // They can still be applied during preview/download request payloads.
    const { employee_date, supervisor_date, ...pdfPayload } = pdfFormData.value
    pdfPayload.stakeholders = serializeStakeholdersChecks(stakeholdersChecks.value)
    pdfPayload.working_Condition = serializeWorkingConditionChecks(workingConditionChecks.value)

    // Create the main PDF record
    let pdfRecord
    if (editingPdfRecord.value) {
      pdfRecord = await updatePdfRecord(editingPdfRecord.value.id, pdfPayload)
      ElMessage.success('PDF record updated successfully')
    } else {
      pdfRecord = await createPdfRecord(pdfPayload)
      ElMessage.success('PDF record created successfully')
    }

    // Resolve the PDF ID for child records (handles different response shapes)
    const pdfId = editingPdfRecord.value
      ? editingPdfRecord.value.id
      : (pdfRecord?.id ?? pdfRecord?.data?.id ?? pdfRecord?.data?.data?.id)

    // Persist supervised positions:
    // - delete rows that exist in DB and are marked _deleted
    // - create rows that are new (!_existing), not deleted, and have a position selected
    for (const row of supervisedPositions.value) {
      if (row._existing && row._deleted && row.id) {
        await deleteSupervisedPosition(row.id)
      } else if (!row._existing && !row._deleted && row.supervised_positionTitle_ID) {
        await addSupervisedPosition({
          PDF_id: pdfId,
          supervised_positionTitle_ID: row.supervised_positionTitle_ID
            ? parseInt(row.supervised_positionTitle_ID, 10)
            : null,
          supervised_item_number: row.supervised_item_number || null,
        })
        row._existing = true
      }
    }

    // Save core competencies (new rows only)
    for (const competency of coreCompetencies.value) {
      if (!competency._existing && competency.competency && competency.competency_level_id) {
        await addCoreCompetency({
          PDF_id: pdfId,
          Competency: competency.competency,
          CompetencyLevel_id: competency.competency_level_id
        })
      }
    }

    // Save leadership competencies (new rows only)
    for (const competency of leadershipCompetencies.value) {
      if (!competency._existing && competency.competency && competency.competency_level_id) {
        await addLeadershipCompetency({
          PDF_id: pdfId,
          Competency: competency.competency,
          CompetencyLevel_id: competency.competency_level_id
        })
      }
    }

    // Save SODAR records:
    // - Edit mode: replace all current SODAR rows so percentage edits are persisted.
    // - Create mode: insert provided rows.
    if (editingPdfRecord.value) {
      for (const row of sodarRecords.value) {
        if (row.id) {
          try {
            await deleteSodarRecord(row.id)
          } catch (e) {
            // Ignore already-deleted rows and proceed with full reinsert.
          }
        }
      }
    }

    for (const sodar of sodarRecords.value) {
      const normalizedPercentage = normalizeSodarPercentage(sodar.percentage)
      if (normalizedPercentage && sodar.responsibilities && sodar.competency_level_id) {
        await addSodarRecord({
          PDF_id: pdfId,
          // Backend expects 'Percetage' (typo preserved in DB)
          Percetage: normalizedPercentage,
          Responsibilities: sodar.responsibilities,
          Competencylevel_id: sodar.competency_level_id
        })
      }
    }

    showPdfForm.value = false
    editingPdfRecord.value = null
    resetPdfForm()
    resetCheckboxForms()
    // Clear local arrays
    coreCompetencies.value = []
    leadershipCompetencies.value = []
    sodarRecords.value = []
    await fetchPdfRecords()
  } catch (error) {
    console.error('Error saving PDF record:', error)
  }
}

const openAddPdfRecord = () => {
  // Clear form model
  resetPdfForm()
  resetCheckboxForms()
  // Clear local dynamic tables
  coreCompetencies.value = []
  leadershipCompetencies.value = []
  sodarRecords.value = []
  supervisedPositions.value = [
    {
      supervised_positionTitle_ID: null,
      supervised_item_number: '',
      _existing: false,
      _deleted: false,
    },
  ]
  // Clear editing state and open modal
  editingPdfRecord.value = null
  showPdfForm.value = true
}

const cancelPdfForm = () => {
  showPdfForm.value = false
  editingPdfRecord.value = null
  resetPdfForm()
  resetCheckboxForms()
  // Clear local arrays
  coreCompetencies.value = []
  leadershipCompetencies.value = []
  sodarRecords.value = []
  supervisedPositions.value = []
}

// Handle position selection change
const onPositionChange = (positionId) => {
  if (positionId) {
    const selectedPosition = positions.value.find(pos => pos.id === positionId)
    if (selectedPosition) {
      // Auto-populate position description from selected position
      pdfFormData.value.position_description = selectedPosition.description || selectedPosition.name
      // You can also auto-populate other fields from the position if needed
      // pdfFormData.value.unit_description = selectedPosition.unit_description
    }
  }
}

const normalizeSodarPercentage = (rawValue) => {
  const raw = String(rawValue ?? '').trim()
  if (!raw) return ''

  const hadPercent = raw.includes('%')
  const numeric = Number(raw.replace('%', ''))
  if (!Number.isFinite(numeric)) return ''

  // Treat whole numbers as percent-as-is (50 => 50%).
  // Keep compatibility for legacy decimal entries (0.5 => 50%).
  if (!hadPercent && numeric > 0 && numeric <= 1 && raw.includes('.')) {
    return String(numeric * 100)
  }

  return String(numeric)
}

// Generate Report from PDF Record
const generateReportFromRecord = async (record) => {
  try {
    generateLoading.value = true
    currentPreviewRecord.value = record
    selectedPdfId.value = record.id
    
    // Prepare request data with supervisor position IDs if available
    const requestData = {
      pdf_id: record.id,
      immediate_supervisor_position_id: record.immediate_supervisor_position_id || pdfFormData.value.immediate_supervisor_position_id || null,
      next_higher_supervisor_position_id: record.next_higher_supervisor_position_id || pdfFormData.value.next_higher_supervisor_position_id || null,
      // Allow preview override; blank string means intentionally blank date
      employee_date: previewEmployeeDate.value ?? '',
      supervisor_date: previewSupervisorDate.value ?? '',
    }
    
    const blob = await generatePositionDescriptionPdf(requestData)
    const url = URL.createObjectURL(blob)
    if (pdfUrl.value) URL.revokeObjectURL(pdfUrl.value)
    pdfUrl.value = url
    showPreview.value = true
  } catch (error) {
    console.error('Error generating report from PDF record:', error)
    ElMessage.error('Failed to generate report')
  } finally {
    generateLoading.value = false
  }
}

const downloadPdf = () => {
  if (pdfUrl.value) {
    const link = document.createElement('a')
    link.href = pdfUrl.value
    link.download = `position_description_${new Date().toISOString().split('T')[0]}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}

const handleDownloadWord = async () => {
  if (!selectedPdfId.value) {
    ElMessage.warning('No record selected')
    return
  }

  try {
    generateLoading.value = true
    
    // Find the record to get supervisor position IDs
    const record = pdfRecords.value.find(r => r.id === selectedPdfId.value)
    const requestData = {
      pdf_id: selectedPdfId.value,
      immediate_supervisor_position_id: record?.immediate_supervisor_position_id || pdfFormData.value.immediate_supervisor_position_id || null,
      next_higher_supervisor_position_id: record?.next_higher_supervisor_position_id || pdfFormData.value.next_higher_supervisor_position_id || null,
      employee_date: previewEmployeeDate.value ?? '',
      supervisor_date: previewSupervisorDate.value ?? '',
    }
    
    const response = await downloadDocx(requestData)
    
    // Handle both direct blob and response object
    const blob = response?.data instanceof Blob 
      ? response.data 
      : (response instanceof Blob 
        ? response 
        : new Blob([response?.data || response], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' }))
    
    if (!blob || blob.size === 0) {
      ElMessage.error('Empty document received')
      return
    }
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    const fileName = `position_description_${selectedPdfId.value}_${new Date().toISOString().split('T')[0]}.docx`
    link.href = url
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    ElMessage.success('Word document downloaded successfully')
  } catch (error) {
    console.error('Word download failed:', error)
    ElMessage.error('Failed to download Word document')
  } finally {
    generateLoading.value = false
  }
}

const regeneratePreview = async () => {
  if (!currentPreviewRecord.value) {
    ElMessage.warning('No record selected')
    return
  }
  await generateReportFromRecord(currentPreviewRecord.value)
}

const clearPreviewDates = () => {
  previewEmployeeDate.value = ''
  previewSupervisorDate.value = ''
}

const closePreview = () => {
  if (pdfUrl.value) {
    URL.revokeObjectURL(pdfUrl.value)
    pdfUrl.value = ''
  }
  selectedPdfId.value = null
  currentPreviewRecord.value = null
  previewEmployeeDate.value = ''
  previewSupervisorDate.value = ''
  showPreview.value = false
}


// SODAR Management Methods
const saveSodarRecord = async () => {
  try {
    await addSodarRecord(sodarFormData.value)
    showSodarForm.value = false
    resetSodarForm()
  } catch (error) {
    console.error('Error saving SODAR record:', error)
  }
}

const cancelSodarForm = () => {
  showSodarForm.value = false
  resetSodarForm()
}

// Core Competency Management Methods
const saveCoreCompetency = async () => {
  try {
    await addCoreCompetency(coreCompetencyFormData.value)
    showCoreCompetencyForm.value = false
    resetCoreCompetencyForm()
  } catch (error) {
    console.error('Error saving core competency:', error)
  }
}

const cancelCoreCompetencyForm = () => {
  showCoreCompetencyForm.value = false
  resetCoreCompetencyForm()
}

// Leadership Competency Management Methods
const saveLeadershipCompetency = async () => {
  try {
    await addLeadershipCompetency(leadershipCompetencyFormData.value)
    showLeadershipCompetencyForm.value = false
    resetLeadershipCompetencyForm()
  } catch (error) {
    console.error('Error saving leadership competency:', error)
  }
}

const cancelLeadershipCompetencyForm = () => {
  showLeadershipCompetencyForm.value = false
  resetLeadershipCompetencyForm()
}

// Local competency and SODAR management methods
const addLocalCoreCompetency = () => {
  coreCompetencies.value.push({
    competency: '',
    competency_level_id: null
  })
}

const removeCoreCompetency = (index) => {
  coreCompetencies.value.splice(index, 1)
}

const addLocalLeadershipCompetency = () => {
  leadershipCompetencies.value.push({
    competency: '',
    competency_level_id: null
  })
}

const removeLeadershipCompetency = (index) => {
  leadershipCompetencies.value.splice(index, 1)
}

const addLocalSodarRecord = () => {
  sodarRecords.value.push({
    percentage: '',
    responsibilities: '',
    competency_level_id: null
  })
}

const removeSodarRecord = (index) => {
  sodarRecords.value.splice(index, 1)
}

// Supervised positions UI helpers
const addLocalSupervisedPosition = () => {
  supervisedPositions.value.push({
    supervised_positionTitle_ID: null,
    supervised_item_number: '',
    _existing: false,
    _deleted: false,
  })
}

const removeSupervisedPosition = (index) => {
  const row = supervisedPositions.value[index]
  if (!row) return
  // Soft-delete: hide from UI and mark for deletion on save
  row._deleted = true
}

const onSupervisedPositionChange = async (row, index) => {
  const positionId = row.supervised_positionTitle_ID
  if (!positionId) {
    row.supervised_item_number = ''
    if (index === 0) {
      pdfFormData.value.supervised_positionTitle_ID = null
      pdfFormData.value.supervised_item_number = ''
    }
    return
  }

  try {
    const response = await positionDescriptionApi.getPlantillaItemNumber(positionId)
    const itemNumber = response.data?.data?.item_number || ''
    row.supervised_item_number = itemNumber

    if (index === 0) {
      pdfFormData.value.supervised_positionTitle_ID = positionId
      pdfFormData.value.supervised_item_number = itemNumber
    }
  } catch (error) {
    console.error('Failed to fetch plantilla item number:', error)
    row.supervised_item_number = ''
    if (index === 0) {
      pdfFormData.value.supervised_item_number = ''
    }
  }
}

// New: remove handlers that delete existing rows server-side
const onRemoveCoreCompetency = async (index) => {
  const row = coreCompetencies.value[index]
  // If existing row has id, delete from server first
  if (row && row.id) {
    try {
      await deleteCoreCompetency(row.id)
    } catch (e) {
      console.error('Failed to delete core competency:', e)
      return
    }
  }
  coreCompetencies.value.splice(index, 1)
}

const onRemoveLeadershipCompetency = async (index) => {
  const row = leadershipCompetencies.value[index]
  if (row && row.id) {
    try {
      await deleteLeadershipCompetency(row.id)
    } catch (e) {
      console.error('Failed to delete leadership competency:', e)
      return
    }
  }
  leadershipCompetencies.value.splice(index, 1)
}

const onRemoveSodar = async (index) => {
  const row = sodarRecords.value[index]
  if (row && row.id) {
    try {
      await deleteSodarRecord(row.id)
    } catch (e) {
      console.error('Failed to delete SODAR record:', e)
      return
    }
  }
  sodarRecords.value.splice(index, 1)
}


// Lifecycle
onMounted(() => {
  console.log('PositionDescriptionList component mounted')
  fetchPdfRecords()
  fetchCompetencyLevels()
  fetchEmployees()
  fetchSalaryGrades()
  fetchPositions()
})
</script>

<style scoped>
.grid {
  display: grid;
}

.grid-cols-1 {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

.grid-cols-2 {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.gap-4 {
  gap: 1rem;
}

.gap-3 {
  gap: 0.75rem;
}

.supervised-positions {
  width: 100%;
}

.supervised-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.supervised-position-select {
  flex: 2;
}

.supervised-item-input {
  flex: 2;
}

.supervised-actions {
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.supervised-help-text {
  font-size: 0.75rem;
  color: #6b7280;
}

@media (min-width: 768px) {
  .md\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
