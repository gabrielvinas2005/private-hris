<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div v-if="accessLoaded && canViewIpcr" class="ipcr-layout">
      <!-- Left Side: List/Overview -->
      <div class="ipcr-left-panel">
        <el-card shadow="hover" class="mb-4">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">IPCR Records</h3>
              <el-button type="primary" size="small" @click="showForm = true">
                <el-icon class="mr-1"><Plus /></el-icon>
                New IPCR
              </el-button>
            </div>
          </template>
          
          <div v-if="isAgencyHead" class="mb-4">
            <router-link to="/ipcr/agency-head-approval">
              <el-alert
                type="info"
                :closable="false"
                show-icon
                title="Head of Agency Approval"
                :description="pendingAgencyHeadCount > 0
                  ? `${pendingAgencyHeadCount} IPCR record(s) awaiting your approval after supervisor calibration.`
                  : 'Review IPCR records after supervisor calibration and before HR recalibration.'"
              />
            </router-link>
          </div>

          <div v-if="isHR" class="mb-4">
            <router-link to="/ipcr/hr-recalibration">
              <el-alert
                type="warning"
                :closable="false"
                show-icon
                title="HR Recalibration"
                :description="pendingHRCount > 0
                  ? `${pendingHRCount} IPCR record(s) approved by Head of Agency — ready for HR recalibration.`
                  : 'Recalibrate IPCR records after Head of Agency approval.'"
              />
            </router-link>
          </div>

          <!-- Search/Filter -->
          <div class="mb-4">
            <el-input
              v-model="searchQuery"
              placeholder="Search IPCR records..."
              size="small"
              clearable
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </div>

          <!-- IPCR Records List -->
          <div class="ipcr-list">
            <div 
              v-for="record in filteredRecords" 
              :key="record.id"
              class="ipcr-list-item"
              :class="{ active: selectedRecord?.id === record.id }"
            >
              <div class="flex items-center justify-between">
                <div @click="selectRecord(record)" class="flex-1 cursor-pointer">
                  <div class="font-medium text-slate-900">{{ record.period || 'No Period' }}</div>
                  <div class="text-sm text-slate-600">{{ record.division || 'No Division' }}</div>
                  <div v-if="record.employee_name" class="text-xs text-slate-500 mt-1">
                    {{ record.employee_name }}
                  </div>
                  <div class="text-xs text-blue-600 mt-1">
                    {{ formatRecalibrationStatus(record.recalibration_status) }}
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <el-button 
                    v-if="record.can_calibrate_supervisor"
                    type="warning" 
                    size="small"
                    @click.stop="openCalibrationDialog(record, 'supervisor')"
                  >
                    <el-icon class="mr-1"><Edit /></el-icon>
                    Calibrate (Sup)
                  </el-button>
                  <el-button 
                    v-if="record.can_calibrate_hr"
                    type="warning" 
                    size="small"
                    @click.stop="openCalibrationDialog(record, 'hr')"
                  >
                    <el-icon class="mr-1"><Edit /></el-icon>
                    Calibrate (HR)
                  </el-button>
                  <el-button 
                    v-if="record.can_calibrate_pmt"
                    type="warning" 
                    size="small"
                    @click.stop="openCalibrationDialog(record, 'pmt')"
                  >
                    <el-icon class="mr-1"><Edit /></el-icon>
                    Calibrate (PMT)
                  </el-button>
                  <el-icon class="text-slate-400" @click="selectRecord(record)">
                    <ArrowRight />
                  </el-icon>
                </div>
              </div>
            </div>
            
            <div v-if="filteredRecords.length === 0" class="text-center py-8 text-slate-500">
              <el-icon size="48" class="mb-2"><Document /></el-icon>
              <p>No IPCR records found</p>
              <p class="text-sm mt-2">Click "New IPCR" to create one</p>
            </div>
          </div>
        </el-card>

        <!-- Info Card -->
        <el-card shadow="hover">
          <template #header>
            <h3 class="text-lg font-semibold text-slate-900">Information</h3>
          </template>
          <div class="text-sm text-slate-600 space-y-2">
            <p><strong>Legend:</strong></p>
            <p>Q - Quality</p>
            <p>E - Efficiency</p>
            <p>T - Timeliness</p>
            <p>A - Average</p>
            <div class="mt-2">
              <p class="font-medium text-slate-700">Adjectival Ratings:</p>
              <ul class="list-disc ml-5 mt-1 space-y-1">
                <li>5 - Outstanding</li>
                <li>4 - Very Satisfactory</li>
                <li>3 - Satisfactory</li>
                <li>2 - Unsatisfactory</li>
                <li>1 - Very Unsatisfactory</li>
              </ul>
              <p class="mt-2 text-slate-500">Note: Q, E, T, and A accept ratings 1 to 5 only.</p>
            </div>
          </div>
        </el-card>
      </div>

      <!-- Right Side: Form Panel -->
      <div class="ipcr-right-panel" :class="{ 'panel-visible': showForm }">
        <el-card shadow="hover" class="ipcr-form-card">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">
                {{ selectedRecord ? (isEmployeeSelfAssessmentLocked ? 'View IPCR' : 'Edit IPCR') : 'New IPCR Form' }}
              </h3>
              <div class="flex items-center gap-2">
                <el-button 
                  v-if="selectedRecord && selectedRecord.id"
                  size="small"
                  @click="previewIPCR(selectedRecord.id)"
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

          <el-form :model="form" label-position="top" ref="formRef" :rules="rules" class="ipcr-form">
            <el-alert
              v-if="isEmployeeSelfAssessmentLocked"
              type="info"
              :closable="false"
              show-icon
              class="mb-4"
            >
              This IPCR has been calibrated and your self-assessment is locked. Use <strong>Print</strong> to view the document.
            </el-alert>
            <!-- Header Section -->
            <div class="ipcr-header">
              <el-row :gutter="12" align="middle">
                <el-col :span="12">
                  <el-form-item label="Department/Division">
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
                      :disabled="isEmployeeSelfAssessmentLocked"
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
              <el-col :span="12">
                <el-form-item label="Reviewed by">
                  <el-select 
                    v-model="form.reviewedByEmployeeId" 
                    placeholder="Select Immediate Supervisor"
                    filterable
                    :disabled="isEmployeeSelfAssessmentLocked"
                    style="width: 100%"
                    @change="onReviewedByChange"
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
                    v-model="form.reviewedDate" 
                    type="date" 
                    value-format="YYYY-MM-DD" 
                    placeholder="Select"
                    :disabled="isEmployeeSelfAssessmentLocked"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Approved by">
                  <el-input
                    :model-value="approvedByLabel"
                    placeholder="Head of Agency"
                    readonly
                    disabled
                    style="width: 100%"
                  />
                </el-form-item>
                <el-form-item label="Date">
                  <el-date-picker 
                    v-model="form.approvedDate" 
                    type="date" 
                    value-format="YYYY-MM-DD" 
                    placeholder="Select"
                    :disabled="isEmployeeSelfAssessmentLocked"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            
            <!-- Output Table Sections -->
            <template v-for="section in outputSections" :key="section.key">
              <el-divider>{{ section.title }}</el-divider>
              <div class="table-container">
                <el-table :data="form[section.key]" border size="small" style="width: 100%">
                  <el-table-column prop="output" label="Output" min-width="150">
                    <template #default="scope">
                      <el-input v-model="scope.row.output" :placeholder="section.placeholder" size="small" :disabled="isEmployeeSelfAssessmentLocked"/>
                    </template>
                  </el-table-column>
                <el-table-column prop="successIndicators" label="Success Indicators" min-width="180">
                  <template #default="scope">
                    <el-input v-model="scope.row.successIndicators" placeholder="Targets & Measures" size="small" :disabled="isEmployeeSelfAssessmentLocked" />
                  </template>
                </el-table-column>
                <el-table-column prop="accomplishment" label="Actual Accomplishments" min-width="150">
                  <template #default="scope">
                    <el-input v-model="scope.row.accomplishment" placeholder="Accomplishments" size="small" :disabled="isEmployeeSelfAssessmentLocked" />
                  </template>
                </el-table-column>
                <!-- Employee Original Ratings -->
                <el-table-column label="Q" width="60">
                  <template #default="scope">
                    <el-input 
                      v-model="scope.row.q" 
                      size="small" 
                      @input="onRatingInput(scope.row, 'q')" 
                      placeholder="2-5"
                      :disabled="isEmployeeSelfAssessmentLocked || (selectedRecord && selectedRecord.id)"
                    />
                  </template>
                </el-table-column>
                <el-table-column label="E" width="60">
                  <template #default="scope">
                    <el-input 
                      v-model="scope.row.e" 
                      size="small" 
                      @input="onRatingInput(scope.row, 'e')" 
                      placeholder="2-5"
                      :disabled="isEmployeeSelfAssessmentLocked || (selectedRecord && selectedRecord.id)"
                    />
                  </template>
                </el-table-column>
                <el-table-column label="T" width="60">
                  <template #default="scope">
                    <el-input 
                      v-model="scope.row.t" 
                      size="small" 
                      @input="onRatingInput(scope.row, 't')" 
                      placeholder="2-5"
                      :disabled="isEmployeeSelfAssessmentLocked || (selectedRecord && selectedRecord.id)"
                    />
                  </template>
                </el-table-column>
                <el-table-column label="A" width="60">
                  <template #default="scope">
                    <el-input 
                      v-model="scope.row.a" 
                      size="small" 
                      placeholder="Auto"
                      disabled
                    />
                  </template>
                </el-table-column>
                
                <!-- Supervisor Recalibration Columns -->
                <el-table-column v-if="isSupervisor || isHR || isPMT" label="Sup Q" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('supervisor') && scope.row.supervisor_recalibration"
                      v-model="scope.row.supervisor_recalibration.q" 
                      size="small" 
                      @input="onRatingInput(scope.row.supervisor_recalibration, 'q')"
                      placeholder="2-5"
                    />
                    <span v-else>{{ formatRatingDisplay(scope.row.supervisor_recalibration?.q) }}</span>
                  </template>
                </el-table-column>
                <el-table-column v-if="isSupervisor || isHR || isPMT" label="Sup E" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('supervisor') && scope.row.supervisor_recalibration"
                      v-model="scope.row.supervisor_recalibration.e" 
                      size="small" 
                      @input="onRatingInput(scope.row.supervisor_recalibration, 'e')"
                      placeholder="2-5"
                    />
                    <span v-else>{{ formatRatingDisplay(scope.row.supervisor_recalibration?.e) }}</span>
                  </template>
                </el-table-column>
                <el-table-column v-if="isSupervisor || isHR || isPMT" label="Sup T" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('supervisor') && scope.row.supervisor_recalibration"
                      v-model="scope.row.supervisor_recalibration.t" 
                      size="small" 
                      @input="onRatingInput(scope.row.supervisor_recalibration, 't')"
                      placeholder="2-5"
                    />
                    <span v-else>{{ formatRatingDisplay(scope.row.supervisor_recalibration?.t) }}</span>
                  </template>
                </el-table-column>
                <el-table-column v-if="isSupervisor || isHR || isPMT" label="Sup A" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('supervisor') && scope.row.supervisor_recalibration"
                      v-model="scope.row.supervisor_recalibration.a" 
                      size="small" 
                      placeholder="Auto"
                      disabled
                    />
                    <span v-else>{{ formatAverageDisplay(scope.row.supervisor_recalibration?.a) }}</span>
                  </template>
                </el-table-column>
                
                <!-- HR Recalibration Columns -->
                <el-table-column v-if="isHR || isPMT" label="HR Q" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('hr') && scope.row.hr_recalibration"
                      v-model="scope.row.hr_recalibration.q" 
                      size="small" 
                      @input="onRatingInput(scope.row.hr_recalibration, 'q')"
                      placeholder="2-5"
                    />
                    <span v-else>{{ formatRatingDisplay(scope.row.hr_recalibration?.q) }}</span>
                  </template>
                </el-table-column>
                <el-table-column v-if="isHR || isPMT" label="HR E" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('hr') && scope.row.hr_recalibration"
                      v-model="scope.row.hr_recalibration.e" 
                      size="small" 
                      @input="onRatingInput(scope.row.hr_recalibration, 'e')"
                      placeholder="2-5"
                    />
                    <span v-else>{{ formatRatingDisplay(scope.row.hr_recalibration?.e) }}</span>
                  </template>
                </el-table-column>
                <el-table-column v-if="isHR || isPMT" label="HR T" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('hr') && scope.row.hr_recalibration"
                      v-model="scope.row.hr_recalibration.t" 
                      size="small" 
                      @input="onRatingInput(scope.row.hr_recalibration, 't')"
                      placeholder="2-5"
                    />
                    <span v-else>{{ formatRatingDisplay(scope.row.hr_recalibration?.t) }}</span>
                  </template>
                </el-table-column>
                <el-table-column v-if="isHR || isPMT" label="HR A" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('hr') && scope.row.hr_recalibration"
                      v-model="scope.row.hr_recalibration.a" 
                      size="small" 
                      placeholder="Auto"
                      disabled
                    />
                    <span v-else>{{ formatAverageDisplay(scope.row.hr_recalibration?.a) }}</span>
                  </template>
                </el-table-column>
                
                <!-- PMT Recalibration Columns -->
                <el-table-column v-if="isPMT" label="PMT Q" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('pmt') && scope.row.pmt_recalibration"
                      v-model="scope.row.pmt_recalibration.q" 
                      size="small" 
                      @input="onRatingInput(scope.row.pmt_recalibration, 'q')"
                      placeholder="2-5"
                    />
                    <span v-else>{{ formatRatingDisplay(scope.row.pmt_recalibration?.q) }}</span>
                  </template>
                </el-table-column>
                <el-table-column v-if="isPMT" label="PMT E" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('pmt') && scope.row.pmt_recalibration"
                      v-model="scope.row.pmt_recalibration.e" 
                      size="small" 
                      @input="onRatingInput(scope.row.pmt_recalibration, 'e')"
                      placeholder="2-5"
                    />
                    <span v-else>{{ formatRatingDisplay(scope.row.pmt_recalibration?.e) }}</span>
                  </template>
                </el-table-column>
                <el-table-column v-if="isPMT" label="PMT T" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('pmt') && scope.row.pmt_recalibration"
                      v-model="scope.row.pmt_recalibration.t" 
                      size="small" 
                      @input="onRatingInput(scope.row.pmt_recalibration, 't')"
                      placeholder="2-5"
                    />
                    <span v-else>{{ formatRatingDisplay(scope.row.pmt_recalibration?.t) }}</span>
                  </template>
                </el-table-column>
                <el-table-column v-if="isPMT" label="PMT A" width="60">
                  <template #default="scope">
                    <el-input 
                      v-if="canRecalibrate('pmt') && scope.row.pmt_recalibration"
                      v-model="scope.row.pmt_recalibration.a" 
                      size="small" 
                      placeholder="Auto"
                      disabled
                    />
                    <span v-else>{{ formatAverageDisplay(scope.row.pmt_recalibration?.a) }}</span>
                  </template>
                </el-table-column>
                <el-table-column prop="remarks" label="Remarks" min-width="120">
                  <template #default="scope">
                    <el-input v-model="scope.row.remarks" placeholder="Remarks" size="small" :disabled="isEmployeeSelfAssessmentLocked"/>
                  </template>
                </el-table-column>
                <el-table-column v-if="!isEmployeeSelfAssessmentLocked" label="Action" width="80" fixed="right">
                  <template #default="scope">
                    <el-button @click="removeRow(section.key, scope.$index)" type="danger" size="small" text>
                      <el-icon><Delete /></el-icon>
                    </el-button>
                  </template>
                </el-table-column>
              </el-table>
            </div>
            <el-button v-if="!isEmployeeSelfAssessmentLocked" type="primary" @click="addRow(section.key)" size="small" class="add-row">
              <el-icon class="mr-1"><Plus /></el-icon>
              Add {{ section.addLabel }}
            </el-button>
            </template>
            
            <!-- Comments/Recommendations -->
            <el-divider>Development Comments</el-divider>
            <el-form-item label="Comments & Recommendations">
              <el-input
                v-model="form.comments"
                type="textarea"
                :rows="3"
                placeholder="Comments for development purposes"
                :disabled="isEmployeeSelfAssessmentLocked"
              />
            </el-form-item>

            <!-- Finalization Section -->
            <el-divider>Signatories</el-divider>
            <el-row :gutter="12">
              <el-col :span="8">
                <el-form-item label="Employee">
                  <el-input 
                    v-model="form.employee" 
                    placeholder="Employee Name"
                    readonly
                    disabled
                    style="width: 100%"
                  />
                </el-form-item>
                <el-form-item label="Date">
                  <el-date-picker 
                    v-model="form.employeeDate" 
                    type="date" 
                    value-format="YYYY-MM-DD"
                    :disabled="isEmployeeSelfAssessmentLocked"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Assessed by">
                  <el-select 
                    v-model="form.assessedByEmployeeId" 
                    placeholder="Select Supervisor"
                    filterable
                    :disabled="isEmployeeSelfAssessmentLocked"
                    style="width: 100%"
                    @change="onAssessedByChange"
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
                    v-model="form.assessedDate" 
                    type="date" 
                    value-format="YYYY-MM-DD"
                    :disabled="isEmployeeSelfAssessmentLocked"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Final Rating by">
                  <el-select 
                    v-model="form.finalRaterEmployeeId" 
                    placeholder="Select PMT Member"
                    filterable
                    :disabled="isEmployeeSelfAssessmentLocked"
                    style="width: 100%"
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
                    :disabled="isEmployeeSelfAssessmentLocked"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            
            <!-- Recalibration Actions (for supervisors, HR, PMT) -->
            <div v-if="(isSupervisor || isHR || isPMT) && selectedRecord && selectedRecord.id" class="recalibration-actions mb-4">
              <el-divider>Recalibration</el-divider>
              <div class="flex gap-2 flex-wrap items-center">
                <el-button 
                  v-if="canRecalibrateSupervisor"
                  type="warning" 
                  @click="saveRecalibration('supervisor')" 
                  :loading="isRecalibrating"
                >
                  <el-icon class="mr-1"><Edit /></el-icon>
                  Save Supervisor Recalibration
                </el-button>
                <el-button 
                  v-if="canRecalibrateHR"
                  type="warning" 
                  @click="saveRecalibration('hr')" 
                  :loading="isRecalibrating"
                >
                  <el-icon class="mr-1"><Edit /></el-icon>
                  Save HR Recalibration
                </el-button>
                <el-button 
                  v-if="canRecalibratePMT"
                  type="warning" 
                  @click="saveRecalibration('pmt')" 
                  :loading="isRecalibrating"
                >
                  <el-icon class="mr-1"><Edit /></el-icon>
                  Save PMT Recalibration
                </el-button>
                <el-tag v-if="recalibrationStatus !== 'self_assessment'" type="info" class="ml-2">
                  Status: {{ formatRecalibrationStatus(recalibrationStatus) }}
                </el-tag>
                <div v-if="isSupervisor && !canRecalibrateSupervisor && recalibrationStatus === 'self_assessment'" class="text-sm text-amber-600 ml-2">
                  <el-icon><InfoFilled /></el-icon>
                  Waiting for employee to complete self-assessment
                </div>
              </div>
              <div v-if="(isSupervisor || isHR || isPMT)" class="mt-2 text-sm text-slate-600">
                <p><strong>Recalibration Workflow:</strong></p>
                <ol class="list-decimal ml-5 space-y-1">
                  <li>Employee completes self-assessment</li>
                  <li>Supervisor recalibrates (if status is "self_assessment")</li>
                  <li>Head of Agency approves (if status is "supervisor_recalibrated")</li>
                  <li>HR recalibrates (if status is "agency_head_approved")</li>
                  <li>PMT recalibrates (if status is "hr_recalibrated")</li>
                </ol>
              </div>
            </div>

            <!-- Form Actions -->
            <div v-if="!isEmployeeSelfAssessmentLocked" class="form-actions">
              <el-button 
                v-if="!selectedRecord || !selectedRecord.id || (!isSupervisor && !isHR && !isPMT)"
                type="success" 
                @click="submitForm" 
                :loading="submitting"
                :disabled="deleting || isRecalibrating"
              >
                {{ selectedRecord && selectedRecord.id ? 'Update' : 'Submit' }}
              </el-button>
              <el-button 
                v-if="selectedRecord && selectedRecord.id && (!isSupervisor && !isHR && !isPMT)"
                type="danger" 
                @click="deleteIPCR" 
                :loading="deleting"
                :disabled="submitting || isRecalibrating"
              >
                Delete
              </el-button>
              <el-button @click="resetForm" :disabled="submitting || isRecalibrating || deleting">Reset</el-button>
              <el-button @click="closeForm" :disabled="submitting || isRecalibrating || deleting">Cancel</el-button>
            </div>
          </el-form>
        </el-card>

        <!-- Calibration Dialog -->
        <el-dialog 
          v-model="calibrationDialogVisible" 
          :title="`Calibrate IPCR Ratings (${calibrationLevel.toUpperCase()})`" 
          width="90%" 
          :close-on-click-modal="false"
          @close="closeCalibrationDialog"
        >
          <div v-if="calibrationLoading" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-slate-600">Loading IPCR data...</span>
          </div>
          <div v-else>
            <div class="mb-4">
              <p class="text-sm text-slate-600">
                <strong>Employee:</strong> {{ calibrationRecord?.employee_name || 'N/A' }} | 
                <strong>Period:</strong> {{ calibrationRecord?.period || 'N/A' }} |
                <strong>Level:</strong> {{ calibrationLevel.toUpperCase() }}
              </p>
            </div>
            <el-alert
              v-if="calibrationLevel === 'hr' && (calibrationDetailData?.agency_head_approved_at || calibrationDetailData?.recalibration_status === 'agency_head_approved')"
              type="success"
              :closable="false"
              show-icon
              class="mb-4"
            >
              <template #title>Approved by Head of Agency</template>
              <div class="text-sm">
                <span v-if="calibrationDetailData?.agency_head_approved_by_name">
                  <strong>Approved by:</strong> {{ calibrationDetailData.agency_head_approved_by_name }}
                </span>
                <span v-if="calibrationDetailData?.agency_head_approved_at" class="ml-3">
                  <strong>Date:</strong> {{ formatDateDisplay(calibrationDetailData.agency_head_approved_at) }}
                </span>
                <p v-if="calibrationDetailData?.agency_head_approval_remarks" class="mt-1 mb-0">
                  <strong>Remarks:</strong> {{ calibrationDetailData.agency_head_approval_remarks }}
                </p>
              </div>
            </el-alert>
            <p v-if="calibrationLevel === 'hr'" class="text-xs text-slate-500 mb-2">
              <strong>HoA Approved Average</strong> Q/E/T = (Employee + Supervisor) ÷ 2. HR ratings default to this average.
            </p>
            <el-table :data="calibrationOutputs" border size="small" style="width: 100%">
              <el-table-column prop="output" label="Output" min-width="200" />
              <el-table-column prop="successIndicators" label="Success Indicators" min-width="180" />
              <el-table-column prop="accomplishment" label="Actual Accomplishments" min-width="150" />
              <!-- Employee Original Ratings (Read-only) -->
              <el-table-column label="Emp Q" width="70">
                <template #default="scope">
                  <el-input 
                    v-model="scope.row.q" 
                    size="small" 
                    disabled
                    style="text-align: center;"
                  />
                </template>
              </el-table-column>
              <el-table-column label="Emp E" width="70">
                <template #default="scope">
                  <el-input 
                    v-model="scope.row.e" 
                    size="small" 
                    disabled
                    style="text-align: center;"
                  />
                </template>
              </el-table-column>
              <el-table-column label="Emp T" width="70">
                <template #default="scope">
                  <el-input 
                    v-model="scope.row.t" 
                    size="small" 
                    disabled
                    style="text-align: center;"
                  />
                </template>
              </el-table-column>
              <el-table-column label="Emp A" width="70">
                <template #default="scope">
                  <el-input 
                    v-model="scope.row.a" 
                    size="small" 
                    disabled
                    style="text-align: center;"
                  />
                </template>
              </el-table-column>
              <template v-if="calibrationLevel === 'hr'">
                <el-table-column label="Sup Q" width="70">
                  <template #default="scope">
                    <el-input v-model="scope.row.sup_q" size="small" disabled style="text-align: center;" />
                  </template>
                </el-table-column>
                <el-table-column label="Sup E" width="70">
                  <template #default="scope">
                    <el-input v-model="scope.row.sup_e" size="small" disabled style="text-align: center;" />
                  </template>
                </el-table-column>
                <el-table-column label="Sup T" width="70">
                  <template #default="scope">
                    <el-input v-model="scope.row.sup_t" size="small" disabled style="text-align: center;" />
                  </template>
                </el-table-column>
                <el-table-column label="Avg Q" width="70" class-name="hoa-approved-col">
                  <template #default="scope">
                    <el-input v-model="scope.row.avg_q" size="small" disabled style="text-align: center; font-weight: 600;" />
                  </template>
                </el-table-column>
                <el-table-column label="Avg E" width="70" class-name="hoa-approved-col">
                  <template #default="scope">
                    <el-input v-model="scope.row.avg_e" size="small" disabled style="text-align: center; font-weight: 600;" />
                  </template>
                </el-table-column>
                <el-table-column label="Avg T" width="70" class-name="hoa-approved-col">
                  <template #default="scope">
                    <el-input v-model="scope.row.avg_t" size="small" disabled style="text-align: center; font-weight: 600;" />
                  </template>
                </el-table-column>
                <el-table-column label="Avg A" width="70" class-name="hoa-approved-col">
                  <template #default="scope">
                    <el-input v-model="scope.row.avg_a" size="small" disabled style="text-align: center; font-weight: 600;" />
                  </template>
                </el-table-column>
              </template>
              <!-- Recalibrated Ratings (Editable) - Dynamic based on level -->
              <el-table-column :label="`${calibrationLevel.toUpperCase()} Q`" width="70">
                <template #default="scope">
                  <el-input 
                    v-model="scope.row.recal_q" 
                    size="small" 
                    @input="onRatingInput(scope.row, 'recal_q')"
                    placeholder="2-5"
                    style="text-align: center;"
                  />
                </template>
              </el-table-column>
              <el-table-column :label="`${calibrationLevel.toUpperCase()} E`" width="70">
                <template #default="scope">
                  <el-input 
                    v-model="scope.row.recal_e" 
                    size="small" 
                    @input="onRatingInput(scope.row, 'recal_e')"
                    placeholder="2-5"
                    style="text-align: center;"
                  />
                </template>
              </el-table-column>
              <el-table-column :label="`${calibrationLevel.toUpperCase()} T`" width="70">
                <template #default="scope">
                  <el-input 
                    v-model="scope.row.recal_t" 
                    size="small" 
                    @input="onRatingInput(scope.row, 'recal_t')"
                    placeholder="2-5"
                    style="text-align: center;"
                  />
                </template>
              </el-table-column>
              <el-table-column :label="`${calibrationLevel.toUpperCase()} A`" width="70">
                <template #default="scope">
                  <el-input 
                    v-model="scope.row.recal_a" 
                    size="small" 
                    placeholder="Auto"
                    disabled
                    style="text-align: center;"
                  />
                </template>
              </el-table-column>
              <el-table-column prop="remarks" label="Remarks" min-width="120">
                <template #default="scope">
                  <el-input 
                    v-model="scope.row.recal_remarks" 
                    placeholder="Remarks" 
                    size="small"
                  />
                </template>
              </el-table-column>
            </el-table>
          </div>
          <template #footer>
            <div class="flex items-center justify-between w-full">
              <div class="text-sm text-slate-500">
                Edit the {{ calibrationLevel.toUpperCase() }} ratings to calibrate the employee's self-assessment
              </div>
              <div>
                <el-button @click="closeCalibrationDialog">Cancel</el-button>
                <el-button 
                  type="warning" 
                  @click="saveCalibration" 
                  :loading="isRecalibrating"
                >
                  Save {{ calibrationLevel.toUpperCase() }} Calibration
                </el-button>
              </div>
            </div>
          </template>
        </el-dialog>

        <!-- Print Preview Dialog -->
        <el-dialog v-model="previewVisible" title="IPCR Print Preview" width="80%" :close-on-click-modal="false" @close="closePreview">
          <div v-if="previewLoading" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-slate-600">Generating PDF...</span>
          </div>
          <div v-else style="height:80vh;">
            <iframe :src="previewUrl" ref="previewFrame" style="width:100%;height:100%;border:0;"></iframe>
          </div>
          <template #footer>
            <div class="flex items-center justify-between w-full">
              <div class="text-sm text-slate-500">PDF generated from current IPCR entry</div>
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
      IPCR is not available. It will appear when there is an ongoing IPCR you need to complete.
    </div>
  </MainLayout>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Search, ArrowRight, Document, Close, Delete, Edit, InfoFilled } from '@element-plus/icons-vue'
import MainLayout from '../../layout/MainLayout.vue'

export default {
  name: 'IPCRView',
  components: {
    MainLayout,
    Plus,
    Search,
    ArrowRight,
    Document,
    Close,
    Delete,
    Edit,
    InfoFilled
  },
  setup() {
    const hasPortalAccess = ref(false)
    const accessLoaded = ref(false)
    const canViewIpcr = computed(() => hasPortalAccess.value)
    const formRef = ref(null)
    const showForm = ref(true) // Show form by default, similar to DTR
    const selectedRecord = ref(null)
    const searchQuery = ref('')
    
    const rules = {
      division: [{ required: true, message: 'Division is required', trigger: 'blur' }],
      period: [{ required: true, message: 'Period is required', trigger: 'change' }],
      reviewedBy: [{ required: true, message: 'Reviewer is required', trigger: 'blur' }],
      approvedBy: [{ required: true, message: 'Approver is required', trigger: 'blur' }]
    }

    const outputSections = [
      { key: 'coreOutputs', title: 'Core Functions & Ratings', placeholder: 'Core Function', addLabel: 'Core Row' },
      { key: 'strategicOutputs', title: 'Strategic Functions & Ratings', placeholder: 'Strategic Function', addLabel: 'Strategic Row' },
      { key: 'supportOutputs', title: 'Support Functions & Ratings', placeholder: 'Support Function', addLabel: 'Support Row' }
    ]

    const defaultRow = () => ({
      output: '',
      successIndicators: '',
      accomplishment: '',
      q: 2,
      e: 2,
      t: 2,
      a: 2,
      remarks: '',
      supervisor_recalibration: null,
      hr_recalibration: null,
      pmt_recalibration: null
    })

    const form = reactive({
      division: '',
      period: [],
      reviewedBy: '',
      reviewedByEmployeeId: null,
      reviewedDate: null,
      approvedBy: '',
      approvedByEmployeeId: null,
      approvedDate: null,
      coreOutputs: [defaultRow()],
      strategicOutputs: [defaultRow()],
      supportOutputs: [defaultRow()],
      comments: '',
      employee: '',
      employeeId: null,
      employeeDate: null,
      assessedBy: '',
      assessedByEmployeeId: null,
      assessedDate: null,
      finalRater: '',
      finalRaterEmployeeId: null,
      finalRateDate: null
    })

    // IPCR records and form data
    const ipcrRecords = ref([])
    const loading = ref(false)
    const submitting = ref(false)
    const deleting = ref(false)
    const employees = ref([])
    const departmentEmployees = ref([])
    const sectionChief = ref(null)
    const agencyHead = ref(null)
    const pmtMember = ref(null)
    const printing = ref(false)
    const previewVisible = ref(false)
    const previewUrl = ref('')
    const previewLoading = ref(false)
    const previewFrame = ref(null)
    const fixedStart = ref(null)
    const currentEmployeeName = ref('') // Store current employee name
    
    // Recalibration state
    const isSupervisor = ref(false)
    const isHR = ref(false)
    const isPMT = ref(false)
    const isAgencyHead = ref(false)
    const pendingAgencyHeadCount = ref(0)
    const pendingHRCount = ref(0)
    const recalibrationStatus = ref('self_assessment')
    const canRecalibrateSupervisor = ref(false)
    const canRecalibrateHR = ref(false)
    const canRecalibratePMT = ref(false)
    const isRecalibrating = ref(false)
    
    // Calibration dialog state
    const calibrationDialogVisible = ref(false)
    const calibrationRecord = ref(null)
    const calibrationOutputs = ref([])
    const calibrationLoading = ref(false)
    const calibrationLevel = ref('supervisor') // 'supervisor', 'hr', or 'pmt'
    const calibrationDetailData = ref(null)

    const employeeLockedStatuses = [
      'supervisor_recalibrated',
      'agency_head_approved',
      'hr_recalibrated',
      'pmt_recalibrated'
    ]

    const isEmployeeSelfAssessmentLocked = computed(() => {
      if (isSupervisor.value || isHR.value || isPMT.value) return false
      return employeeLockedStatuses.includes(recalibrationStatus.value)
    })

    // Ensure a select list contains the given id; if missing, inject it so labels show when editing
    function ensureOption(listRef, id, name) {
      const normalizedId = normalizeEmployeeId(id)
      if (!normalizedId) return
      const exists = listRef.value.some(opt => String(opt.id) === String(normalizedId))
      if (!exists) {
        const existingName = listRef.value.find(opt => String(opt.id) === String(normalizedId))?.name
        listRef.value = [...listRef.value, {
          id: normalizedId,
          name: name || existingName || `Employee #${normalizedId}`
        }]
      }
    }

    function normalizeEmployeeId(id) {
      const normalized = Number(id)
      return Number.isFinite(normalized) && normalized > 0 ? normalized : null
    }

    function backfillSignatoryNames() {
      const pairs = [
        ['reviewedByEmployeeId', 'reviewedBy'],
        ['approvedByEmployeeId', 'approvedBy'],
        ['assessedByEmployeeId', 'assessedBy'],
        ['finalRaterEmployeeId', 'finalRater']
      ]
      for (const [idField, nameField] of pairs) {
        if (!form[nameField] && form[idField]) {
          const match = employees.value.find(e => String(e.id) === String(form[idField]))
          if (match?.name) form[nameField] = match.name
        }
      }
    }

    function applySignatoriesToForm(data) {
      form.reviewedBy = data.reviewedBy || ''
      form.reviewedByEmployeeId = normalizeEmployeeId(data.reviewedByEmployeeId)
      form.reviewedDate = data.reviewedDate || null
      form.approvedBy = data.approvedBy || ''
      form.approvedByEmployeeId = normalizeEmployeeId(data.approvedByEmployeeId)
      form.approvedDate = data.approvedDate || null
      form.assessedBy = data.assessedBy || ''
      form.assessedByEmployeeId = normalizeEmployeeId(data.assessedByEmployeeId)
      form.assessedDate = data.assessedDate || null
      form.finalRater = data.finalRater || ''
      form.finalRaterEmployeeId = normalizeEmployeeId(data.finalRaterEmployeeId)
      form.finalRateDate = data.finalRateDate || null

      if (!form.reviewedByEmployeeId && form.assessedByEmployeeId) {
        form.reviewedByEmployeeId = form.assessedByEmployeeId
      }
      if (!form.reviewedBy && form.assessedBy) {
        form.reviewedBy = form.assessedBy
      }
      if (!form.assessedByEmployeeId && form.reviewedByEmployeeId) {
        form.assessedByEmployeeId = form.reviewedByEmployeeId
      }
      if (!form.assessedBy && form.reviewedBy) {
        form.assessedBy = form.reviewedBy
      }
    }

    function syncSignatorySelectOptions() {
      ensureOption(employees, form.reviewedByEmployeeId, form.reviewedBy)
      ensureOption(employees, form.approvedByEmployeeId, form.approvedBy)
      ensureOption(employees, form.assessedByEmployeeId, form.assessedBy)
      ensureOption(employees, form.finalRaterEmployeeId, form.finalRater)

      if (!form.reviewedByEmployeeId && form.reviewedBy) {
        const match = employees.value.find(e => e.name === form.reviewedBy)
        if (match) form.reviewedByEmployeeId = normalizeEmployeeId(match.id)
      }
      if (!form.approvedByEmployeeId && form.approvedBy) {
        const match = employees.value.find(e => e.name === form.approvedBy)
        if (match) form.approvedByEmployeeId = normalizeEmployeeId(match.id)
      }
      if (!form.assessedByEmployeeId && form.assessedBy) {
        const match = employees.value.find(e => e.name === form.assessedBy)
        if (match) form.assessedByEmployeeId = normalizeEmployeeId(match.id)
      }
      if (!form.finalRaterEmployeeId && form.finalRater) {
        const match = employees.value.find(e => e.name === form.finalRater)
        if (match) form.finalRaterEmployeeId = normalizeEmployeeId(match.id)
      }

      backfillSignatoryNames()
    }

    function normalizeSignatory(signatory) {
      if (!signatory) return null
      if (typeof signatory === 'number' || (typeof signatory === 'string' && /^\d+$/.test(signatory))) {
        const id = normalizeEmployeeId(signatory)
        return id ? { id, name: '' } : null
      }
      const id = normalizeEmployeeId(signatory.id ?? signatory.employee_id)
      if (!id) return null
      return {
        id,
        name: signatory.name || signatory.employee_name || ''
      }
    }

    function applyDefaultSignatories() {
      const chief = normalizeSignatory(sectionChief.value)
      const head = normalizeSignatory(agencyHead.value)
      const pmt = normalizeSignatory(pmtMember.value)

      if (chief?.id) {
        form.reviewedByEmployeeId = chief.id
        form.reviewedBy = chief.name || ''
        form.assessedByEmployeeId = chief.id
        form.assessedBy = chief.name || ''
      }
      if (head?.id) {
        form.approvedByEmployeeId = head.id
        form.approvedBy = head.name || ''
      }
      if (pmt?.id) {
        form.finalRaterEmployeeId = pmt.id
        form.finalRater = pmt.name || ''
      }

      syncSignatorySelectOptions()
    }

    const approvedByLabel = computed(() => {
      if (form.approvedBy) return form.approvedBy
      const match = employees.value.find(e => String(e.id) === String(form.approvedByEmployeeId))
      if (match?.name) return match.name
      const head = normalizeSignatory(agencyHead.value)
      return head?.name || ''
    })

    function normalizeEmployees(list) {
      return (list || []).map(emp => ({
        ...emp,
        id: normalizeEmployeeId(emp.id) ?? emp.id
      }))
    }

    const filteredRecords = computed(() => {
      if (!searchQuery.value) return ipcrRecords.value
      const query = searchQuery.value.toLowerCase()
      return ipcrRecords.value.filter(record => 
        (record.period || '').toLowerCase().includes(query) ||
        (record.division || '').toLowerCase().includes(query)
      )
    })

    const breadcrumbs = [
      { name: 'Dashboard', path: '/dashboard' },
      { name: 'IPCR', path: '/ipcr' }
    ]

    // Load form data (division and employee lists)
    async function loadFormData() {
      try {
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.getEmployeeIPCRFormData()
        if (response && response.success) {
          const data = response.data
          form.division = data.division || ''
          employees.value = normalizeEmployees(data.employees)
          departmentEmployees.value = normalizeEmployees(data.department_employees)
          sectionChief.value = normalizeSignatory(data.section_chief)
          agencyHead.value = normalizeSignatory(data.agency_head)
          pmtMember.value = normalizeSignatory(data.pmt_member)
          
          // Auto-populate employee name from API response (self-assessment)
          // This is the person filling up the IPCR - always use logged-in user
          if (data.current_employee) {
            currentEmployeeName.value = data.current_employee.name || ''
            form.employee = currentEmployeeName.value
            form.employeeId = data.current_employee.id || null
          } else {
            // Fallback to localStorage if API doesn't return current employee
            const userData = localStorage.getItem('user_data')
            if (userData) {
              try {
                const user = JSON.parse(userData)
                if (user.name) {
                  currentEmployeeName.value = user.name
                  form.employee = currentEmployeeName.value
                  // Find employee ID from the employees list
                  const currentEmployee = employees.value.find(emp => 
                    emp.name === user.name || emp.employee_no === user.employee_no
                  )
                  if (currentEmployee) {
                    form.employeeId = currentEmployee.id
                  }
                }
              } catch (e) {
                console.error('Error parsing user data:', e)
              }
            }
          }

          // Default signatories for new records: section chief (Reviewed by), agency head (Approved by), PMT member (Final Rater)
          if (!selectedRecord.value) {
            applyDefaultSignatories()
          }
        } else {
          ElMessage.warning(response?.message || 'Failed to load form data')
        }
      } catch (error) {
        console.error('Error loading form data:', error)
        ElMessage.warning('Failed to load form data')
      }
    }

    // Load IPCR records
    function mapOutputFromApi(o) {
      let supervisor_recal = o.supervisor_recalibration
      if (!supervisor_recal && (isSupervisor.value || isHR.value || isPMT.value)) {
        supervisor_recal = mapRecalibrationFromApi(null, o)
      } else if (supervisor_recal) {
        supervisor_recal = mapRecalibrationFromApi(supervisor_recal, o)
      }

      let hr_recal = o.hr_recalibration
      if (!hr_recal && (isHR.value || isPMT.value)) {
        hr_recal = mapRecalibrationFromApi(null, o)
      } else if (hr_recal) {
        hr_recal = mapRecalibrationFromApi(hr_recal, o)
      }

      let pmt_recal = o.pmt_recalibration
      if (!pmt_recal && isPMT.value) {
        pmt_recal = mapRecalibrationFromApi(null, o)
      } else if (pmt_recal) {
        pmt_recal = mapRecalibrationFromApi(pmt_recal, o)
      }

      return {
        id: o.id,
        output: o.output || '',
        successIndicators: o.successIndicators || '',
        accomplishment: o.accomplishment || '',
        q: normalizeRatingForDisplay(o.q),
        e: normalizeRatingForDisplay(o.e),
        t: normalizeRatingForDisplay(o.t),
        a: normalizeAverageForDisplay(o.a) || computeAverage(o.q, o.e, o.t),
        remarks: o.remarks || '',
        supervisor_recalibration: supervisor_recal,
        hr_recalibration: hr_recal,
        pmt_recalibration: pmt_recal
      }
    }

    function applyOutputsToForm(outputs) {
      const core = []
      const strategic = []
      const support = []

      for (const o of outputs || []) {
        const type = o.functionType || o.function_type || 'core'
        const row = mapOutputFromApi(o)
        if (type === 'strategic') {
          strategic.push(row)
        } else if (type === 'support') {
          support.push(row)
        } else {
          core.push(row)
        }
      }

      form.coreOutputs = core.length ? core : [defaultRow()]
      form.strategicOutputs = strategic.length ? strategic : [defaultRow()]
      form.supportOutputs = support.length ? support : [defaultRow()]
    }

    function getAllFormOutputs() {
      return [...form.coreOutputs, ...form.strategicOutputs, ...form.supportOutputs]
    }

    function mergeOutputsForPayload() {
      return [
        ...form.coreOutputs.map(o => ({ ...o, functionType: 'core' })),
        ...form.strategicOutputs.map(o => ({ ...o, functionType: 'strategic' })),
        ...form.supportOutputs.map(o => ({ ...o, functionType: 'support' }))
      ].filter(o => String(o.output || '').trim() !== '')
    }

    function formatRecalibrationStatus(status) {
      const labels = {
        self_assessment: 'Submitted for Review',
        supervisor_recalibrated: 'Pending Head of Agency Approval',
        agency_head_approved: 'Approved by Head of Agency',
        hr_recalibrated: 'HR Recalibrated',
        pmt_recalibrated: 'PMT Recalibrated'
      }
      return labels[status] || 'Submitted for Review'
    }

    function formatDateDisplay(value) {
      if (!value) return '—'
      const d = new Date(value)
      return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString()
    }

    async function loadIPCRRecords() {
      try {
        loading.value = true
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.getEmployeeIPCRList()
        if (response && response.success) {
          ipcrRecords.value = response.data || []
        } else {
          ElMessage.error(response?.message || 'Failed to load IPCR records')
        }
      } catch (error) {
        console.error('Error loading IPCR records:', error)
        ElMessage.error('Failed to load IPCR records')
      } finally {
        loading.value = false
      }
    }

    // Load single IPCR record
    async function loadIPCRRecord(id) {
      try {
        loading.value = true
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.getEmployeeIPCR(id)
        if (response && response.success) {
          const data = response.data
          // Populate form with data
          form.division = data.division || ''
          form.period = data.period || []
          // set fixed start when editing
          fixedStart.value = Array.isArray(form.period) && form.period.length > 0 ? form.period[0] : null

          if (data.employees) {
            employees.value = normalizeEmployees(data.employees)
          }
          if (data.department_employees) {
            departmentEmployees.value = normalizeEmployees(data.department_employees)
          }

          applySignatoriesToForm(data)

          isSupervisor.value = data.is_supervisor || false
          isHR.value = data.is_hr || false
          isPMT.value = data.is_pmt || false
          recalibrationStatus.value = data.recalibration_status || 'self_assessment'
          canRecalibrateSupervisor.value = data.can_recalibrate_supervisor || false
          canRecalibrateHR.value = data.can_recalibrate_hr || false
          canRecalibratePMT.value = data.can_recalibrate_pmt || false

          applyOutputsToForm(data.outputs)
          form.comments = data.comments || ''
          // Employee name logic:
          // - If viewing an existing IPCR: use the IPCR owner's name (data.employee) - this is the employee who self-assessed
          // - If creating new IPCR: use the logged-in user's name (self-assessment)
          // - For supervisors/HR/PMT viewing subordinate IPCRs: show the subordinate's name
          if (selectedRecord.value && selectedRecord.value.id && data.employee) {
            // Viewing existing IPCR - use the IPCR owner's name (the employee who created it)
            form.employee = data.employee
            form.employeeId = data.employeeId || null
          } else {
            // Creating new IPCR - use logged-in user's name (self-assessment)
            form.employee = currentEmployeeName.value || ''
            form.employeeId = data.current_employee?.id || null
          }
          form.employeeDate = data.employeeDate || null

          syncSignatorySelectOptions()
        } else {
          ElMessage.error(response?.message || 'Failed to load IPCR record')
        }
      } catch (error) {
        console.error('Error loading IPCR record:', error)
        ElMessage.error('Failed to load IPCR record')
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
      resetForm({ applyDefaults: false })
      await loadIPCRRecord(record.id)
    }

    function closeForm() {
      showForm.value = false
      selectedRecord.value = null
      resetForm()
    }

    async function previewIPCR(id) {
      if (!id) return
      try {
        previewLoading.value = true
        const ApiService = (await import('../../services/api.js')).default
        const blob = await ApiService.downloadEmployeeIPCRPDF(id)
        if (blob) {
          if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
          previewUrl.value = URL.createObjectURL(blob)
          previewVisible.value = true
        } else {
          ElMessage.error('Failed to generate IPCR PDF')
        }
      } catch (e) {
        console.error('Preview failed:', e)
        ElMessage.error('Failed to generate IPCR PDF')
      } finally {
        previewLoading.value = false
      }
    }

    function downloadPreview() {
      if (!previewUrl.value) return
      const a = document.createElement('a')
      a.href = previewUrl.value
      a.download = selectedRecord.value ? `ipcr_${selectedRecord.value.id}.pdf` : 'ipcr.pdf'
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

    // Save recalibration
    async function saveRecalibration(level) {
      if (!selectedRecord.value) return
      
      try {
        isRecalibrating.value = true
        const ApiService = (await import('../../services/api.js')).default
        
        const recalData = {
          outputs: getAllFormOutputs().map(output => {
            let recal = null
            if (level === 'supervisor') {
              recal = output.supervisor_recalibration || {
                q: output.q,
                e: output.e,
                t: output.t,
                a: output.a,
                remarks: '',
                justification: ''
              }
            } else if (level === 'hr') {
              if (output.hr_recalibration) {
                recal = output.hr_recalibration
              } else {
                const combined = combinedBaselineForHr(output)
                recal = {
                  q: combined.q ?? output.q,
                  e: combined.e ?? output.e,
                  t: combined.t ?? output.t,
                  a: combined.a ?? output.a,
                  remarks: '',
                  justification: ''
                }
              }
            } else if (level === 'pmt') {
              recal = output.pmt_recalibration || {
                q: output.q,
                e: output.e,
                t: output.t,
                a: output.a,
                remarks: '',
                justification: ''
              }
            }
            
            const combined = level === 'hr' ? combinedBaselineForHr(output) : null
            const q = resolveRecalibrationRating(recal.q, combined?.q, output.q)
            const e = resolveRecalibrationRating(recal.e, combined?.e, output.e)
            const t = resolveRecalibrationRating(recal.t, combined?.t, output.t)
            return {
              id: output.id,
              q,
              e,
              t,
              a: averageRatingForPayload(q, e, t, recal.a ?? output.a),
              remarks: recal.remarks || '',
              justification: recal.justification || ''
            }
          })
        }
        
        const response = await ApiService.saveIPCRRecalibration(
          selectedRecord.value.id, 
          level, 
          recalData
        )
        
        if (response && response.success) {
          ElMessage.success(`${level.charAt(0).toUpperCase() + level.slice(1)} recalibration saved successfully!`)
          await loadIPCRRecord(selectedRecord.value.id) // Reload to show updated data
        } else {
          ElMessage.error(response?.message || 'Failed to save recalibration')
        }
      } catch (error) {
        ElMessage.error('Failed to save recalibration')
        console.error(error)
      } finally {
        isRecalibrating.value = false
      }
    }

    // Check if user can recalibrate at a specific level
    function canRecalibrate(level) {
      if (level === 'supervisor') return canRecalibrateSupervisor.value
      if (level === 'hr') return canRecalibrateHR.value
      if (level === 'pmt') return canRecalibratePMT.value
      return false
    }

    // Open calibration dialog
    async function openCalibrationDialog(record, level = 'supervisor') {
      try {
        calibrationLoading.value = true
        calibrationRecord.value = record
        calibrationLevel.value = level
        calibrationDialogVisible.value = true
        
        // Load IPCR data
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.getEmployeeIPCR(record.id)
        
        if (response && response.success) {
          const data = response.data
          calibrationDetailData.value = data
          // Prepare calibration outputs based on level
          calibrationOutputs.value = (data.outputs || []).map(o => {
            // Get the appropriate recalibration data based on level
            let recal_data = null
            if (level === 'supervisor') {
              recal_data = o.supervisor_recalibration
            } else if (level === 'hr') {
              recal_data = o.hr_recalibration
            } else if (level === 'pmt') {
              recal_data = o.pmt_recalibration
            }

            const combined = level === 'hr' ? combinedBaselineForHr(o) : null
            
            const resolvedQ = resolveRecalibrationRating(recal_data?.q, combined?.q, o.q)
            const resolvedE = resolveRecalibrationRating(recal_data?.e, combined?.e, o.e)
            const resolvedT = resolveRecalibrationRating(recal_data?.t, combined?.t, o.t)
            const base_a = normalizeAverageForDisplay(recal_data?.a ?? combined?.a ?? o.a)
              || computeAverage(resolvedQ, resolvedE, resolvedT)
            
            return {
              id: o.id,
              output: o.output || '',
              successIndicators: o.successIndicators || '',
              accomplishment: o.accomplishment || '',
              // Employee's original ratings (read-only)
              q: normalizeRatingForDisplay(o.q),
              e: normalizeRatingForDisplay(o.e),
              t: normalizeRatingForDisplay(o.t),
              a: normalizeAverageForDisplay(o.a) || computeAverage(o.q, o.e, o.t),
              // Supervisor combined baseline for HR (read-only when HR level)
              ...(level === 'hr' ? {
                sup_q: normalizeRatingForDisplay(o.supervisor_recalibration?.q),
                sup_e: normalizeRatingForDisplay(o.supervisor_recalibration?.e),
                sup_t: normalizeRatingForDisplay(o.supervisor_recalibration?.t),
                avg_q: combined?.q !== null ? combined.q : '',
                avg_e: combined?.e !== null ? combined.e : '',
                avg_t: combined?.t !== null ? combined.t : '',
                avg_a: combined?.a ? normalizeAverageForDisplay(combined.a) : ''
              } : {}),
              // Recalibrated ratings (editable) - dynamic based on level
              recal_q: String(resolvedQ),
              recal_e: String(resolvedE),
              recal_t: String(resolvedT),
              recal_a: base_a,
              recal_remarks: recal_data?.remarks || ''
            }
          })
        } else {
          ElMessage.error(response?.message || 'Failed to load IPCR data')
          closeCalibrationDialog()
        }
      } catch (error) {
        console.error('Error opening calibration dialog:', error)
        ElMessage.error('Failed to load IPCR data')
        closeCalibrationDialog()
      } finally {
        calibrationLoading.value = false
      }
    }

    // Close calibration dialog
    function closeCalibrationDialog() {
      calibrationDialogVisible.value = false
      calibrationRecord.value = null
      calibrationOutputs.value = []
      calibrationDetailData.value = null
      calibrationLevel.value = 'supervisor'
    }

    // Save calibration
    async function saveCalibration() {
      if (!calibrationRecord.value) return
      
      try {
        isRecalibrating.value = true
        const ApiService = (await import('../../services/api.js')).default
        
        const recalData = {
          outputs: calibrationOutputs.value.map(output => {
            const q = resolveRecalibrationRating(output.recal_q, output.avg_q, output.q)
            const e = resolveRecalibrationRating(output.recal_e, output.avg_e, output.e)
            const t = resolveRecalibrationRating(output.recal_t, output.avg_t, output.t)
            return {
              id: output.id,
              q,
              e,
              t,
              a: averageRatingForPayload(q, e, t, output.recal_a ?? output.a),
              remarks: output.recal_remarks || '',
              justification: ''
            }
          })
        }
        
        const response = await ApiService.saveIPCRRecalibration(
          calibrationRecord.value.id, 
          calibrationLevel.value, 
          recalData
        )
        
        if (response && response.success) {
          ElMessage.success(`${calibrationLevel.value.toUpperCase()} calibration saved successfully!`)
          closeCalibrationDialog()
          await loadIPCRRecords() // Refresh the list
        } else {
          ElMessage.error(response?.message || 'Failed to save calibration')
        }
      } catch (error) {
        ElMessage.error('Failed to save calibration')
        console.error(error)
      } finally {
        isRecalibrating.value = false
      }
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
        // lock the start date to the initially selected one
        form.period = [fixedStart.value, val[1]]
      }
    }

    async function submitForm() {
      if (!formRef.value) return
      
      try {
        await formRef.value.validate()

        const payloadOutputs = mergeOutputsForPayload()
        if (payloadOutputs.length === 0) {
          ElMessage.error('Please add at least one function entry with an output description')
          return
        }

        submitting.value = true
        const ApiService = (await import('../../services/api.js')).default
        
        // Prepare data for API
        const submitData = {
          id: selectedRecord.value?.id || 0,
          division: form.division,
          period: form.period,
          reviewedBy: form.reviewedBy,
          reviewedByEmployeeId: form.reviewedByEmployeeId,
          reviewedDate: form.reviewedDate,
          approvedBy: form.approvedBy,
          approvedByEmployeeId: form.approvedByEmployeeId,
          approvedDate: form.approvedDate,
          outputs: payloadOutputs,
          comments: form.comments,
          // employeeId is not sent - backend will use logged-in employee
          employeeDate: form.employeeDate,
          assessedBy: form.assessedBy,
          assessedByEmployeeId: form.assessedByEmployeeId,
          assessedDate: form.assessedDate,
          finalRater: form.finalRater,
          finalRaterEmployeeId: form.finalRaterEmployeeId,
          finalRateDate: form.finalRateDate
        }
        
        const response = await ApiService.saveEmployeeIPCR(submitData)
        
        if (response && response.success) {
          ElMessage.success('IPCR saved successfully!')
          await loadIPCRRecords() // Refresh the list
          closeForm()
        } else {
          ElMessage.error(response?.message || 'Failed to save IPCR')
        }
      } catch (errors) {
        if (errors && typeof errors === 'object' && !errors.message) {
          ElMessage.error('Please fill in all required fields')
        } else {
          ElMessage.error(errors?.message || 'Failed to save IPCR')
        }
        console.error(errors)
      } finally {
        submitting.value = false
      }
    }

    async function deleteIPCR() {
      if (!selectedRecord.value?.id) return

      try {
        await ElMessageBox.confirm(
          'Are you sure you want to delete this IPCR record? This will permanently remove all outputs, ratings, recalibrations, and related data.',
          'Confirm Delete',
          {
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            type: 'warning',
          }
        )

        deleting.value = true
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.deleteEmployeeIPCR(selectedRecord.value.id)

        if (response && response.success) {
          ElMessage.success('IPCR deleted successfully')
          closeForm()
          await loadIPCRRecords()
        } else {
          ElMessage.error(response?.message || 'Failed to delete IPCR')
        }
      } catch (error) {
        if (error !== 'cancel') {
          ElMessage.error('Failed to delete IPCR')
          console.error('Error deleting IPCR:', error)
        }
      } finally {
        deleting.value = false
      }
    }

    function resetForm(options = {}) {
      const applyDefaults = options.applyDefaults !== false
      // Keep division and employee lists
      form.period = []
      fixedStart.value = null
      form.reviewedBy = ''
      form.reviewedByEmployeeId = null
      form.reviewedDate = null
      form.approvedBy = ''
      form.approvedByEmployeeId = null
      form.approvedDate = null
      form.coreOutputs = [defaultRow()]
      form.strategicOutputs = [defaultRow()]
      form.supportOutputs = [defaultRow()]
      form.comments = ''
      // Always set employee to current logged-in user (person filling up the form)
      form.employee = currentEmployeeName.value || ''
      form.employeeDate = null
      form.assessedBy = ''
      form.assessedByEmployeeId = null
      form.assessedDate = null
      form.finalRater = ''
      form.finalRaterEmployeeId = null
      form.finalRateDate = null

      if (!applyDefaults) {
        return
      }

      // Re-apply defaults for signatories
      applyDefaultSignatories()
    }

    // Employee selection change handlers
    function onReviewedByChange(employeeId) {
      const emp = employees.value.find(e => e.id === employeeId)
      form.reviewedBy = emp ? emp.name : ''
      // Set Assessed by to same as Reviewed by
      form.assessedByEmployeeId = employeeId
      form.assessedBy = emp ? emp.name : ''
    }

    function onApprovedByChange(employeeId) {
      const emp = employees.value.find(e => e.id === employeeId)
      form.approvedBy = emp ? emp.name : ''
    }

    // Removed onEmployeeChange - employee is now auto-populated and read-only

    function onAssessedByChange(employeeId) {
      const emp = employees.value.find(e => e.id === employeeId)
      form.assessedBy = emp ? emp.name : ''
    }

    function onFinalRaterChange(employeeId) {
      const emp = employees.value.find(e => e.id === employeeId)
      form.finalRater = emp ? emp.name : ''
    }

    function collectRatingValues(q, e, t) {
      return [q, e, t]
        .map(v => Number(v))
        .filter(v => Number.isFinite(v) && v >= 2 && v <= 5)
    }

    /** Valid IPCR ratings are 2–5; legacy 1 / empty values are shown as blank. */
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

    function formatRatingDisplay(v) {
      const display = normalizeRatingForDisplay(v)
      return display === '' ? '-' : display
    }

    function formatAverageDisplay(v) {
      const display = normalizeAverageForDisplay(v)
      return display === '' ? '-' : display
    }

    function ratingForPayload(v) {
      const n = Number(v)
      if (!Number.isFinite(n) || n < 2 || n > 5) return null
      return Math.round(n)
    }

    /** Resolve Q/E/T for recalibration API — never returns null. */
    function resolveRecalibrationRating(...candidates) {
      for (const v of candidates) {
        const n = ratingForPayload(v)
        if (n !== null) return n
      }
      return 2
    }

    function pairAverage(empVal, supVal) {
      const emp = ratingForPayload(empVal)
      const sup = ratingForPayload(supVal)
      if (emp === null || sup === null) return null
      return Math.round(((emp + sup) / 2) * 100) / 100
    }

    function combinedBaselineForHr(o) {
      const sup = o.supervisor_recalibration || {}
      const avgQ = pairAverage(o.q, sup.q)
      const avgE = pairAverage(o.e, sup.e)
      const avgT = pairAverage(o.t, sup.t)
      const values = [avgQ, avgE, avgT].filter(v => v !== null && Number.isFinite(v))
      const avgA = values.length
        ? Math.round((values.reduce((s, v) => s + v, 0) / values.length) * 100) / 100
        : null
      return { q: avgQ, e: avgE, t: avgT, a: avgA }
    }

    function mapRecalibrationFromApi(recal, source) {
      const src = source || {}
      if (recal) {
        return {
          ...recal,
          q: normalizeRatingForDisplay(recal.q),
          e: normalizeRatingForDisplay(recal.e),
          t: normalizeRatingForDisplay(recal.t),
          a: normalizeAverageForDisplay(recal.a),
          remarks: recal.remarks || '',
          justification: recal.justification || ''
        }
      }
      return {
        q: normalizeRatingForDisplay(src.q),
        e: normalizeRatingForDisplay(src.e),
        t: normalizeRatingForDisplay(src.t),
        a: normalizeAverageForDisplay(src.a) || computeAverage(src.q, src.e, src.t),
        remarks: '',
        justification: ''
      }
    }

    function computeAverage(q, e, t) {
      const values = collectRatingValues(q, e, t)
      if (values.length === 0) return ''
      const avg = values.reduce((sum, v) => sum + v, 0) / values.length
      const clamped = Math.max(2, Math.min(5, avg))
      return clamped.toFixed(2)
    }

    /** Numeric average for API payloads (Q/E/T are integers; A may be decimal). */
    function averageRatingForPayload(q, e, t, fallback = 2) {
      const values = collectRatingValues(q, e, t)
      if (values.length > 0) {
        const avg = values.reduce((sum, v) => sum + v, 0) / values.length
        return Math.round(Math.max(2, Math.min(5, avg)) * 100) / 100
      }
      const fb = Number(fallback)
      return Number.isFinite(fb) ? Math.round(fb * 100) / 100 : 2
    }

    function onRatingInput(row, key) {
      const isMainQet = key === 'q' || key === 'e' || key === 't'
      const isRecalQet = key === 'recal_q' || key === 'recal_e' || key === 'recal_t'
      let v = String(row[key] ?? '').replace(/[^0-9]/g, '')
      if (v === '' || v === '0' || v === '1') {
        row[key] = ''
        if (isMainQet) {
          row.a = computeAverage(row.q, row.e, row.t)
        }
        if (isRecalQet) {
          row.recal_a = computeAverage(row.recal_q, row.recal_e, row.recal_t)
        }
        return
      }
      const n = Math.max(2, Math.min(5, parseInt(v, 10)))
      row[key] = n

      if (isMainQet) {
        row.a = computeAverage(row.q, row.e, row.t)
      }
      if (isRecalQet) {
        row.recal_a = computeAverage(row.recal_q, row.recal_e, row.recal_t)
      }
    }

    async function loadPortalAccess() {
      try {
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.checkDivisionChiefAccess()
        if (response && response.success) {
          hasPortalAccess.value = !!response.data?.is_available
          isAgencyHead.value = !!response.data?.is_agency_head
          pendingAgencyHeadCount.value = response.data?.pending_agency_head_count ?? 0
          isHR.value = !!response.data?.is_hr_user
          pendingHRCount.value = response.data?.pending_hr_recalibration_count ?? 0
        } else {
          hasPortalAccess.value = false
        }
      } catch (error) {
        console.error('Error checking IPCR access:', error)
        hasPortalAccess.value = false
      } finally {
        accessLoaded.value = true
      }
    }

    onMounted(async () => {
      await loadPortalAccess()
      if (!hasPortalAccess.value) return
      await loadFormData()
      if (!form.employee && currentEmployeeName.value) {
        form.employee = currentEmployeeName.value
      }
      await loadIPCRRecords()
    })

    return {
      canViewIpcr,
      accessLoaded,
      formRef,
      rules,
      outputSections,
      form,
      approvedByLabel,
      showForm,
      selectedRecord,
      searchQuery,
      ipcrRecords,
      filteredRecords,
      breadcrumbs,
      loading,
      addRow,
      removeRow,
      selectRecord,
      closeForm,
      submitForm,
      deleteIPCR,
      resetForm,
      loadIPCRRecords,
      formatRecalibrationStatus,
      formatDateDisplay,
      loadIPCRRecord,
      loadFormData,
      employees,
      calibrationDialogVisible,
      calibrationRecord,
      calibrationOutputs,
      calibrationLoading,
      calibrationLevel,
      calibrationDetailData,
      openCalibrationDialog,
      closeCalibrationDialog,
      saveCalibration,
      departmentEmployees,
      onReviewedByChange,
      onApprovedByChange,
      onAssessedByChange,
      onFinalRaterChange,
      onRatingInput,
      formatRatingDisplay,
      formatAverageDisplay,
      // preview/print
      previewVisible,
      previewUrl,
      previewLoading,
      previewFrame,
      previewIPCR,
      downloadPreview,
      printPreview,
      closePreview,
      // recalibration / role flags
      isSupervisor,
      isHR,
      isPMT,
      isAgencyHead,
      pendingAgencyHeadCount,
      pendingHRCount,
      recalibrationStatus,
      isEmployeeSelfAssessmentLocked,
      canRecalibrateSupervisor,
      canRecalibrateHR,
      canRecalibratePMT,
      canRecalibrate,
      saveRecalibration,
      submitting,
      deleting,
      handlePeriodChange,
      isRecalibrating
    }
  }
}
</script>

<style scoped>
.ipcr-layout {
  display: flex;
  gap: 1.5rem;
  height: calc(100vh - 200px);
  min-height: 600px;
}

.ipcr-left-panel {
  flex: 0 0 400px;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}

.ipcr-right-panel {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: all 0.3s ease;
}

.ipcr-right-panel:not(.panel-visible) {
  opacity: 0;
  pointer-events: none;
  transform: translateX(20px);
}

.ipcr-right-panel.panel-visible {
  opacity: 1;
  pointer-events: all;
  transform: translateX(0);
}

.ipcr-form-card {
  flex: 1;
  min-height: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.ipcr-form-card :deep(.el-card) {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.ipcr-form-card :deep(.el-card__header) {
  flex-shrink: 0;
}

.ipcr-form-card :deep(.el-card__body) {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 20px;
}

.ipcr-form {
  max-width: 100%;
}

.ipcr-header {
  margin-bottom: 20px;
}

.ipcr-list {
  max-height: 400px;
  overflow-y: auto;
  margin-top: 1rem;
}

.ipcr-list-item {
  padding: 12px;
  margin-bottom: 8px;
  border: 1px solid #e4e7ed;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.ipcr-list-item:hover {
  background-color: #f5f7fa;
  border-color: #409eff;
}

.ipcr-list-item.active {
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
:deep(.el-textarea) {
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

/* Utility classes */
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

/* Responsive adjustments */
@media (max-width: 1200px) {
  .ipcr-layout {
    flex-direction: column;
    height: auto;
    max-height: none;
  }
  
  .ipcr-left-panel {
    flex: 1;
    max-height: 400px;
  }
  
  .ipcr-right-panel {
    flex: 1;
    min-height: 0;
    max-height: calc(100vh - 280px);
  }
  
  .ipcr-right-panel:not(.panel-visible) {
    display: none;
  }
}
</style>
