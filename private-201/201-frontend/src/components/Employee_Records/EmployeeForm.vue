
<template>
  <el-dialog
    v-model="visible"
    width="90%"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <template #header>
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <span>{{ isEdit ? 'Edit Employee' : 'On-board Employee' }}</span>
        <span v-if="autoSaveStatus === 'saving'" style="font-size: 12px; color: #409eff;">
          <el-icon class="is-loading"><Loading /></el-icon> Saving...
        </span>
        <span v-else-if="autoSaveStatus === 'saved'" style="font-size: 12px; color: #67c23a;">
          ✓ Draft saved
        </span>
      </div>
    </template>
    <el-form
      ref="formRef"
      :model="formData"
      :rules="formRules"
      label-position="top"
      size="small"
      v-loading="loading"
    >
      <!-- Two-column onboarding layout -->
      <el-row :gutter="20" class="mb-4">
        <!-- Left sidebar -->
        <el-col :span="6">
          <el-card shadow="never">
            <div class="avatar-box">
              <el-avatar :size="96" :src="photoPreview" />
              <el-upload
                class="mt-2"
                :show-file-list="false"
                :before-upload="handleAvatarSelect"
              >
                <el-button size="small">Upload Photo</el-button>
              </el-upload>
            </div>
            <el-divider />
            <div class="section-title"></div>
            <el-form-item label="Employee No." prop="employee_no" required>
              <el-input v-model="formData.employee_no" placeholder="Employee Number" required />
            </el-form-item>
            <div class="section-title mt-2">Access No.</div>
            <el-input v-model="formData.access_no" placeholder="Access Number" />
          </el-card>

          <el-card shadow="never" class="mt-2">
            <div class="section-heading">Contact Informations</div>
            <el-form-item label="Email" prop="email" required>
              <el-input v-model="formData.email" placeholder="Email Address" />
            </el-form-item>
            <el-form-item label="Mobile No.">
              <el-input v-model="formData.mobile_no" placeholder="Mobile Number" />
            </el-form-item>
            <el-form-item label="Telephone No.">
              <el-input v-model="formData.telephone_no" placeholder="Telephone Number" />
            </el-form-item>
          </el-card>

          <el-card shadow="never" class="mt-2">
            <div class="section-heading">Contributions ID</div>
            <el-form-item label="TIN"><el-input v-model="formData.tin_no" placeholder="TIN Number" /></el-form-item>
            <el-form-item label="GSIS No."><el-input v-model="formData.gsis_no" placeholder="GSIS Number" /></el-form-item>
            <el-form-item label="SSS No."><el-input v-model="formData.sss_no" placeholder="SSS Number" /></el-form-item>
            <el-form-item label="Pag-ibig No."><el-input v-model="formData.pagibig_no" placeholder="Pag-ibig Number" /></el-form-item>
            <el-form-item label="PhilHealth No."><el-input v-model="formData.philhealth_no" placeholder="PhilHealth Number" /></el-form-item>
            <el-form-item label="CRN No."><el-input v-model="formData.crn_no" placeholder="CRN Number" /></el-form-item>
          </el-card>
        </el-col>

        <!-- Right content -->
        <el-col :span="18">
          <el-card shadow="never">
            <div class="section-heading">Personal Information</div>
            <el-row :gutter="16">
              <el-col :span="4">
                <el-form-item label="Prefix" prop="name_prefix_id">
                  <el-select v-model="formData.name_prefix_id" placeholder="Select Prefix" style="width: 100%">
                    <el-option v-for="prefix in formOptions.prefixes" :key="prefix.id" :label="prefix.name" :value="Number(prefix.id)" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="5"><el-form-item label="First Name" prop="first_name"><el-input v-model="formData.first_name" /></el-form-item></el-col>
              <el-col :span="5"><el-form-item label="Middle Name"><el-input v-model="formData.middle_name" /></el-form-item></el-col>
              <el-col :span="5"><el-form-item label="Last Name" prop="last_name"><el-input v-model="formData.last_name" /></el-form-item></el-col>
              <el-col :span="5"><el-form-item label="Suffix"><el-select v-model="formData.name_suffix_id" placeholder="Select Suffix" style="width:100%"><el-option v-for="s in formOptions.suffixes" :key="s.id" :label="s.name" :value="Number(s.id)" /></el-select></el-form-item></el-col>
            </el-row>

            <el-row :gutter="16">
              <el-col :span="12"><el-form-item label="Birth Place"><el-input v-model="formData.birth_place" /></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Birth Date" prop="birthdate"><el-date-picker v-model="formData.birthdate" type="date" placeholder="mm/dd/yyyy" style="width:100%" @change="calculateAge" /></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Age"><el-input-number v-model="formData.age" :min="0" :controls="false" placeholder="Age" style="width:100%" /></el-form-item></el-col>
            </el-row>

            <el-row :gutter="16">
              <el-col :span="6"><el-form-item label="Gender" prop="gender_id"><el-select v-model="formData.gender_id" placeholder="Select Gender" style="width:100%"><el-option v-for="g in formOptions.genders" :key="g.id" :label="g.name" :value="Number(g.id)" /></el-select></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Civil Status" prop="civil_status_id"><el-select v-model="formData.civil_status_id" placeholder="Select Civil Status" style="width:100%"><el-option v-for="s in formOptions.civil_status" :key="s.id" :label="s.name" :value="Number(s.id)" /></el-select></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Citizenship" prop="citizenship_id"><el-select v-model="formData.citizenship_id" placeholder="Select Citizenship" style="width:100%"><el-option v-for="c in formOptions.citizenships" :key="c.id" :label="c.name" :value="Number(c.id)" /></el-select></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Religion" prop="religion_id"><el-select v-model="formData.religion_id" placeholder="Select Religion" style="width:100%"><el-option v-for="r in formOptions.religions" :key="r.id" :label="r.name" :value="Number(r.id)" /></el-select></el-form-item></el-col>
            </el-row>

            <el-row :gutter="16">
              <el-col :span="6"><el-form-item label="Height (cm)"><el-input-number v-model="formData.height" :min="0" :controls="false" placeholder="Height" style="width:100%" /></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Weight (kg)"><el-input-number v-model="formData.weight" :min="0" :controls="false" placeholder="Weight" style="width:100%" /></el-form-item></el-col>
              <el-col :span="12"><el-form-item label="Blood Type"><el-select v-model="formData.blood_type_id" placeholder="Select Blood Type" style="width:100%"><el-option v-for="b in formOptions.blood_types" :key="b.id" :label="b.name" :value="Number(b.id)" /></el-select></el-form-item></el-col>
            </el-row>
          </el-card>
          
          <!-- Current Address -->
          <el-card shadow="never" class="mt-2">
            <div class="section-heading">Current Address</div>
            <el-row :gutter="16">
              <el-col :span="8">
                <el-form-item label="Region">
                  <el-select 
                    v-model="formData.ca_region" 
                    placeholder="Select Region" 
                    style="width: 100%"
                    filterable
                    clearable
                  >
                    <el-option 
                      v-for="region in addressData.regions" 
                      :key="region.regCode" 
                      :label="region.regDesc" 
                      :value="region.regCode" 
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Province">
                  <el-select 
                    v-model="formData.ca_province" 
                    placeholder="Select Province" 
                    style="width: 100%"
                    :disabled="!formData.ca_region"
                    filterable
                    clearable
                  >
                    <el-option 
                      v-for="province in currentProvinces" 
                      :key="province.provCode" 
                      :label="province.provDesc" 
                      :value="province.provCode" 
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Municipality/City">
                  <el-select 
                    v-model="formData.ca_city" 
                    placeholder="Select City/Municipality" 
                    style="width: 100%"
                    :disabled="!formData.ca_province || currentCities.length === 0"
                    filterable
                    clearable
                    no-data-text="No cities available for selected province"
                  >
                    <el-option 
                      v-for="city in currentCities" 
                      :key="city.citymunCode" 
                      :label="city.citymunDesc" 
                      :value="city.citymunCode" 
                    />
                  </el-select>
                  <small v-if="formData.ca_province && currentCities.length === 0" style="color: #f56c6c; font-size: 12px; display: block; margin-top: 4px;">
                    No cities found for this province. The data file may be incomplete.
                  </small>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="16">
              <el-col :span="6">
                <el-form-item label="Zip Code">
                  <el-input v-model="formData.ca_zip" placeholder="Zip Code" />
                </el-form-item>
              </el-col>
              <el-col :span="10">
                 <el-form-item label="Barangay/Purok">
                   <el-select 
                     v-model="formData.ca_barangay" 
                     placeholder="Select Barangay" 
                     style="width: 100%"
                     :disabled="!formData.ca_city || currentBarangays.length === 0"
                     filterable
                     clearable
                     no-data-text="No barangays available for selected city"
                   >
                     <el-option 
                       v-for="barangay in currentBarangays" 
                       :key="barangay.brgyCode" 
                       :label="barangay.brgyDesc" 
                       :value="barangay.brgyCode" 
                     />
                   </el-select>
                 </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="House No.">
                  <el-input v-model="formData.ca_house_no" placeholder="House Number" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Street">
                  <el-input v-model="formData.ca_street" placeholder="Street" />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Village/Subdivision">
                  <el-input v-model="formData.ca_village" placeholder="Village/Subdivision" />
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>

          <!-- Permanent Address -->
          <el-card shadow="never" class="mt-2">
            <div class="section-heading">Permanent Address</div>
            <el-row :gutter="12">
              <el-col :span="8">
                <el-form-item label="Region">
                  <el-select 
                    v-model="formData.pa_region" 
                    placeholder="Select Region" 
                    style="width: 100%"
                    filterable
                    clearable
                  >
                    <el-option 
                      v-for="region in addressData.regions" 
                      :key="region.regCode" 
                      :label="region.regDesc" 
                      :value="region.regCode" 
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Province">
                  <el-select 
                    v-model="formData.pa_province" 
                    placeholder="Select Province" 
                    style="width: 100%"
                    :disabled="!formData.pa_region"
                    filterable
                    clearable
                  >
                    <el-option 
                      v-for="province in permanentProvinces" 
                      :key="province.provCode" 
                      :label="province.provDesc" 
                      :value="province.provCode" 
                    />
                  </el-select>
                </el-form-item>
              </el-col>
               <el-col :span="8">
                 <el-form-item label="Municipality/City">
                   <el-select 
                     v-model="formData.pa_city" 
                     placeholder="Select City/Municipality" 
                     style="width: 100%"
                     :disabled="!formData.pa_province || permanentCities.length === 0"
                     filterable
                     clearable
                     no-data-text="No cities available for selected province"
                   >
                     <el-option 
                       v-for="city in permanentCities" 
                       :key="city.citymunCode" 
                       :label="city.citymunDesc" 
                       :value="city.citymunCode" 
                     />
                   </el-select>
                   <small v-if="formData.pa_province && permanentCities.length === 0" style="color: #f56c6c; font-size: 12px; display: block; margin-top: 4px;">
                     No cities found for this province. The data file may be incomplete.
                   </small>
                 </el-form-item>
               </el-col>
            </el-row>
            <el-row :gutter="12">
              <el-col :span="6">
                <el-form-item label="Zip Code">
                  <el-input v-model="formData.pa_zip" placeholder="Zip Code" />
                </el-form-item>
              </el-col>
              <el-col :span="10">
                 <el-form-item label="Barangay/Purok">
                   <el-select 
                     v-model="formData.pa_barangay" 
                     placeholder="Select Barangay" 
                     style="width: 100%"
                     :disabled="!formData.pa_city || permanentBarangays.length === 0"
                     filterable
                     clearable
                     no-data-text="No barangays available for selected city"
                   >
                     <el-option 
                       v-for="barangay in permanentBarangays" 
                       :key="barangay.brgyCode" 
                       :label="barangay.brgyDesc" 
                       :value="barangay.brgyCode" 
                     />
                   </el-select>
                 </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="House No.">
                  <el-input v-model="formData.pa_house_no" placeholder="House Number" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Street">
                  <el-input v-model="formData.pa_street" placeholder="Street" />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Village/Subdivision">
                  <el-input v-model="formData.pa_village" placeholder="Village/Subdivision" />
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>

          <!-- Dual Citizenship Information -->
          <el-card shadow="never" class="mt-2">
            <div class="section-heading">Dual-Citizenship Information</div>
            <el-form-item label="With Dual Citizenship?">
              <el-switch v-model="formData.is_dual_citizent" />
            </el-form-item>
            <el-row :gutter="12">
              <el-col :span="12">
                <el-radio-group v-model="formData.dual_citizenship_type" :disabled="!formData.is_dual_citizent">
                  <el-radio label="by_birth">By Birth</el-radio>
                  <el-radio label="by_naturalization">By Naturalization</el-radio>
                </el-radio-group>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Country of Origin">
                  <el-input v-model="formData.indicate_country" placeholder="Select Country of Origin" />
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>
        </el-col>
      </el-row>

      <!-- Tabs section -->
      <el-tabs v-model="activeTab" type="card" class="pill-tabs">
        <!-- Work Information (summary) Tab -->
        <el-tab-pane label="Work Information" name="work_information">
          <el-row :gutter="20">
            <el-col :span="12"><el-form-item label="Agency"><el-input disabled :value="agencyName" /></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="Is Plantilla?"><el-switch v-model="formData.is_plantilla" /></el-form-item></el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Department">
                <el-select 
                  v-model="formData.department_id" 
                  placeholder="Select Department" 
                  style="width:100%"
                  filterable
                  clearable
                >
                  <el-option 
                    v-for="d in formOptions.departments" 
                    :key="d.id" 
                    :label="d.name" 
                    :value="Number(d.id)" 
                  />
                </el-select>
                <el-input v-if="departmentName" :model-value="departmentName" disabled style="margin-top: 8px;" />
                <small v-if="formData.department_id && !departmentName" style="color: #f56c6c; font-size: 12px; display: block; margin-top: 4px;">
                  Department ID {{ formData.department_id }} not found in list
                </small>
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Plantilla Item">
                <el-select 
                  v-model="formData.plantilla_id" 
                  placeholder="Select Plantilla Item" 
                  style="width:100%"
                  :disabled="!formData.is_plantilla || !formData.department_id"
                  clearable
                >
                  <el-option 
                    v-for="pl in filteredPlantillas" 
                    :key="pl.id" 
                    :label="pl.code || pl.item_no || pl.name || ('Item #' + pl.id)" 
                    :value="Number(pl.id)" 
                  />
                </el-select>
                <el-input v-if="plantillaName" :model-value="plantillaName" disabled style="margin-top: 8px;" />
                <small v-if="!formData.is_plantilla" style="color: #909399; font-size: 12px; display: block; margin-top: 4px;">
                  Please enable "Is Plantilla" first
                </small>
                <small v-else-if="!formData.department_id" style="color: #909399; font-size: 12px; display: block; margin-top: 4px;">
                  Please select a department first
                </small>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="8">
              <el-form-item label="Division">
                <el-select 
                  v-model="formData.division_id" 
                  placeholder="Select Division" 
                  style="width:100%"
                  filterable
                  clearable
                >
                  <el-option 
                    v-for="div in formOptions.divisions || []" 
                    :key="div.id" 
                    :label="div.name" 
                    :value="Number(div.id)" 
                  />
                </el-select>
                <el-input v-if="divisionName" :model-value="divisionName" disabled style="margin-top: 8px;" />
                <small v-if="formData.division_id && !divisionName" style="color: #f56c6c; font-size: 12px; display: block; margin-top: 4px;">
                  Division ID {{ formData.division_id }} not found in list
                </small>
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="Section">
                <el-select v-model="formData.section_id" placeholder="Section" style="width:100%">
                  <el-option v-for="s in formOptions.sections || []" :key="s.id" :label="s.name" :value="Number(s.id)" />
                </el-select>
                <el-input v-if="sectionName" :model-value="sectionName" disabled style="margin-top: 8px;" />
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="Salary Grade">
                <el-select
                  v-model="formData.salary_grade_id"
                  placeholder="Salary Grade"
                  style="width:100%"
                  filterable
                  :disabled="formData.employment_type_id === 2"
                >
                  <el-option v-for="g in formOptions.salary_grades || []" :key="g.id" :label="g.name || ('Grade ' + g.id)" :value="Number(g.id)" />
                </el-select>
                <el-input v-if="salaryGradeName" :model-value="salaryGradeName" disabled style="margin-top: 8px;" />
                <small v-if="formData.employment_type_id === 2" style="color: #909399; font-size: 12px; display: block; margin-top: 4px;">
                  Synced from the active contract
                </small>
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="Salary Step">
                <el-select
                  v-model="formData.salary_step_id"
                  placeholder="Salary Step"
                  style="width:100%"
                  filterable
                  :disabled="formData.employment_type_id === 2"
                >
                  <el-option v-for="st in formOptions.salary_steps || []" :key="st.id" :label="st.name || ('Step ' + st.id)" :value="Number(st.id)" />
                </el-select>
                <el-input v-if="salaryStepName" :model-value="salaryStepName" disabled style="margin-top: 8px;" />
                <small v-if="formData.employment_type_id === 2" style="color: #909399; font-size: 12px; display: block; margin-top: 4px;">
                  Synced from the active contract
                </small>
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12"><el-form-item label="Employment Type"><el-select v-model="formData.employment_type_id" placeholder="Select Employment Type" style="width:100%"><el-option v-for="t in formOptions.employment_types" :key="t.id" :label="t.name" :value="Number(t.id)" /></el-select></el-form-item></el-col>
            <el-col :span="12">
              <el-form-item label="Date Hired">
                <el-date-picker
                  v-model="formData.date_hired"
                  type="date"
                  placeholder="Select Date Hired"
                  style="width: 100%"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12"><el-form-item label="Position"><el-select v-model="formData.position_id" placeholder="Please Select Position" style="width:100%" filterable><el-option v-for="p in formOptions.positions" :key="p.id" :label="p.name" :value="Number(p.id)" /></el-select></el-form-item></el-col>
          </el-row>
        </el-tab-pane>

        <!-- Payroll Related Tab -->
        <el-tab-pane label="Payroll Related" name="payroll">
          <!-- General Payroll Information -->
          <el-card shadow="never" class="mb-4">
            <template #header>
              <span class="card-header-title">General Payroll Information</span>
            </template>
          <el-row :gutter="20">
              <el-col :span="8">
                <el-form-item label="Account No.">
                  <el-input v-model="formData.account_no" placeholder="Enter account number" />
              </el-form-item>
            </el-col>
              <el-col :span="8">
                <el-form-item label="Payroll Interval" required>
                  <el-select 
                    v-model="formData.payroll_interval_id" 
                    placeholder="Select Payroll Interval" 
                    style="width: 100%"
                    filterable
                    clearable
                  >
                  <el-option
                      v-for="interval in formOptions.payroll_intervals || []" 
                      :key="interval.id" 
                      :label="interval.name" 
                      :value="Number(interval.id)" 
                  />
                </el-select>
              </el-form-item>
            </el-col>
              <el-col :span="8">
                <el-form-item label="Is Hold?">
                  <el-checkbox v-model="formData.is_hold">Hold Payroll</el-checkbox>
                  <p
                    v-if="formData.employment_type_id === 2"
                    class="cos-hold-hint"
                  >
                    COS employees without an active contract, or with an expired contract end date, are automatically placed on payroll hold.
                  </p>
              </el-form-item>
            </el-col>
            </el-row>
            <el-row v-if="formData.is_hold" :gutter="20">
              <el-col :span="24">
                <el-form-item label="Hold Remarks">
                  <el-input 
                    v-model="formData.hold_remarks" 
                    type="textarea" 
                    :rows="2"
                    placeholder="Enter reason for hold" 
                  />
              </el-form-item>
            </el-col>
          </el-row>
          </el-card>

         
          <!-- <el-card shadow="never" class="mb-4">
            <template #header>
              <span class="card-header-title">Salary and Deduction Details</span>
            </template>
          <el-row :gutter="20">
              <el-col :span="8">
                <el-form-item label="Basic Salary" required>
                  <el-input-number 
                    v-model="formData.salary" 
                    :precision="2" 
                    :min="0" 
                    :controls="false"
                    style="width: 100%"
                    placeholder="0.00"
                  />
              </el-form-item>
                <el-form-item label="Daily Rate">
                  <el-input-number 
                    :model-value="dailyRate" 
                    :precision="2" 
                    :min="0" 
                    :controls="false"
                    disabled
                  style="width: 100%"
                />
              </el-form-item>
                <el-form-item label="Hourly Rate">
                  <el-input-number 
                    :model-value="hourlyRate" 
                    :precision="2" 
                    :min="0" 
                    :controls="false"
                    disabled
                    style="width: 100%"
                  />
              </el-form-item>
            </el-col>
              
              <el-col :span="8">
                <el-form-item label="Tax Amount">
                  <el-input-number 
                    v-model="formData.tax_amount" 
                    :precision="2" 
                    :min="0" 
                    :controls="false"
                    style="width: 100%"
                    placeholder="0.00"
                  />
              </el-form-item>
                <el-form-item label="GSIS Amount">
                  <el-input-number 
                    v-model="formData.gsis_amount" 
                    :precision="2" 
                    :min="0" 
                    :controls="false"
                    style="width: 100%"
                    placeholder="0.00"
                  />
              </el-form-item>
                <el-form-item label="SSS Amount">
                  <el-input-number 
                    v-model="formData.sss_amount" 
                    :precision="2" 
                    :min="0" 
                    :controls="false"
                    style="width: 100%"
                    placeholder="0.00"
                  />
              </el-form-item>
            </el-col>
              
              <el-col :span="8">
                <el-form-item label="Pag-Ibig Amount">
                  <el-input-number 
                    v-model="formData.pagibig_amount" 
                    :precision="2" 
                    :min="0" 
                    :controls="false"
                    style="width: 100%"
                    placeholder="0.00"
                  />
                </el-form-item>
                <el-form-item label="PhilHealth Amount">
                  <el-input-number 
                    v-model="formData.philhealth_amount" 
                    :precision="2" 
                    :min="0" 
                    :controls="false"
                    style="width: 100%"
                    placeholder="0.00"
                  />
              </el-form-item>
            </el-col>
          </el-row>
          </el-card> -->
        
          <!-- Income and Loans Tables -->
          <el-row :gutter="20">
            <!-- Income Table -->
            <el-col :span="12">
              <el-card shadow="never" class="income-card">
                <template #header>
                  <div class="table-header income-header">
                    <span>Income</span>
                  </div>
                </template>
                <el-table 
                  :data="relatedData.incomes || []" 
                  border 
                  size="small"
                  :empty-text="'No income records'"
                >
                  <el-table-column prop="name" label="Item" min-width="150" />
                  <el-table-column prop="amount" label="Amount" width="140" align="right">
                    <template #default="{ row }">
                      {{ formatCurrency(row.amount) }}
                    </template>
                  </el-table-column>
                </el-table>
              </el-card>
            </el-col>
            
            <!-- Loans Table -->
            <el-col :span="12">
              <el-card shadow="never" class="loans-card">
                <template #header>
                  <div class="table-header loans-header">
                    <span>Loans</span>
                  </div>
                </template>
                <el-table 
                  :data="relatedData.loans || []" 
                  border 
                  size="small"
                  :empty-text="'No loan records'"
                >
                  <el-table-column prop="name" label="Item" min-width="120" />
                  <el-table-column prop="loan_amount" label="Amount" width="100" align="right">
                    <template #default="{ row }">
                      {{ formatCurrency(row.loan_amount) }}
                    </template>
                  </el-table-column>
                  <el-table-column prop="payment" label="Payment" width="100" align="right">
                    <template #default="{ row }">
                      {{ formatCurrency(row.payment) }}
                    </template>
                  </el-table-column>
                  <el-table-column prop="balance" label="Balance" width="100" align="right">
                    <template #default="{ row }">
                      {{ formatCurrency(row.balance) }}
                    </template>
                  </el-table-column>
                </el-table>
              </el-card>
            </el-col>
          </el-row>
        </el-tab-pane>

        <!-- Family Tab -->
        <el-tab-pane label="Family" name="family">
          <!-- Father's Name -->
          <el-row :gutter="20" class="mb-4">
            <el-col :span="24">
              <h4 style="margin-bottom: 16px;">Father's Name:</h4>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="4">
              <el-form-item label="Prefix">
                <el-select v-model="formData.father_name_prefix_id" placeholder="Prefix" style="width: 100%">
                  <el-option v-for="prefix in formOptions.prefixes" :key="prefix.id" :label="prefix.name" :value="Number(prefix.id)" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="First Name">
                <el-input v-model="formData.father_first_name" placeholder="First Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Middle Name">
                <el-input v-model="formData.father_middle_name" placeholder="Middle Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Last Name">
                <el-input v-model="formData.father_last_name" placeholder="Last Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Suffix">
                <el-select v-model="formData.father_name_suffix_id" placeholder="Suffix" style="width: 100%">
                  <el-option v-for="suffix in formOptions.suffixes" :key="suffix.id" :label="suffix.name" :value="Number(suffix.id)" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <!-- Mother's Maiden Name -->
          <el-row :gutter="20" class="mb-4 mt-4">
            <el-col :span="24">
              <h4 style="margin-bottom: 16px;">Mother's Maiden Name:</h4>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="4">
              <el-form-item label="Prefix">
                <el-select v-model="formData.mother_name_prefix_id" placeholder="Prefix" style="width: 100%">
                  <el-option v-for="prefix in formOptions.prefixes" :key="prefix.id" :label="prefix.name" :value="Number(prefix.id)" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="First Name">
                <el-input v-model="formData.mother_first_name" placeholder="First Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Middle Name">
                <el-input v-model="formData.mother_middle_name" placeholder="Middle Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Surname">
                <el-input v-model="formData.mother_last_name" placeholder="Last Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Suffix">
                <el-select v-model="formData.mother_name_suffix_id" placeholder="Suffix" style="width: 100%">
                  <el-option v-for="suffix in formOptions.suffixes" :key="suffix.id" :label="suffix.name" :value="Number(suffix.id)" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <!-- Spouse's Name -->
          <el-row :gutter="20" class="mb-4 mt-4">
            <el-col :span="24">
              <h4 style="margin-bottom: 16px;">Spouse's Name:</h4>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="4">
              <el-form-item label="Prefix">
                <el-select v-model="formData.spouse_name_prefix_id" placeholder="Prefix" style="width: 100%">
                  <el-option v-for="prefix in formOptions.prefixes" :key="prefix.id" :label="prefix.name" :value="Number(prefix.id)" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="First Name">
                <el-input v-model="formData.spouse_first_name" placeholder="First Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Middle Name">
                <el-input v-model="formData.spouse_middle_name" placeholder="Middle Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Last Name">
                <el-input v-model="formData.spouse_last_name" placeholder="Last Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Suffix">
                <el-select v-model="formData.spouse_name_suffix_id" placeholder="Suffix" style="width: 100%">
                  <el-option v-for="suffix in formOptions.suffixes" :key="suffix.id" :label="suffix.name" :value="Number(suffix.id)" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>

          <!-- Spouse Details -->
          <el-row :gutter="20" class="mt-4">
            <el-col :span="12">
              <el-form-item label="Spouse Occupation">
                <el-input v-model="formData.spouse_occupation" placeholder="Occupation" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Spouse Work Address">
                <el-input v-model="formData.spouse_business_address" placeholder="Work Address" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Spouse Employer">
                <el-input v-model="formData.spouse_employer" placeholder="Employer" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Spouse Mobile Number">
                <el-input v-model="formData.spouse_mobile_no" placeholder="Mobile Number" />
              </el-form-item>
            </el-col>
          </el-row>

          <!-- Children Information -->
          <el-row :gutter="20" class="mt-4">
            <el-col :span="24">
              <div style="background: #409EFF; color: white; padding: 12px; margin-bottom: 16px; border-radius: 4px;">
                <strong>Children Information</strong>
              </div>
              <el-table :data="childrenList" border style="width: 100%">
                <el-table-column prop="first_name" label="First Name" width="200">
                  <template #default="{ row, $index }">
                    <el-input v-model="row.first_name" placeholder="First Name" size="small" />
                  </template>
                </el-table-column>
                <el-table-column prop="middle_name" label="Middle Name" width="200">
                  <template #default="{ row, $index }">
                    <el-input v-model="row.middle_name" placeholder="Middle Name" size="small" />
                  </template>
                </el-table-column>
                <el-table-column prop="last_name" label="Last Name" width="200">
                  <template #default="{ row, $index }">
                    <el-input v-model="row.last_name" placeholder="Last Name" size="small" />
                  </template>
                </el-table-column>
                <el-table-column prop="birthdate" label="Birth Date" width="200">
                  <template #default="{ row, $index }">
                    <el-date-picker
                      v-model="row.birthdate"
                      type="date"
                      placeholder="Birth Date"
                      style="width: 100%"
                      size="small"
                    />
                  </template>
                </el-table-column>
                <el-table-column label="Actions" width="80" align="center">
                  <template #default="{ $index }">
                    <el-button
                      type="danger"
                      size="small"
                      :icon="Delete"
                      circle
                      @click="removeChild($index)"
                    />
                  </template>
                </el-table-column>
              </el-table>
              <el-button type="primary" :icon="Plus" @click="addChild" style="margin-top: 12px;">
                Add Child
              </el-button>
            </el-col>
          </el-row>
        </el-tab-pane>
        
        <!-- Education Tab -->
        <el-tab-pane label="Education" name="education">
          <el-card shadow="never">
            <el-table 
              :data="educationList" 
              border 
              size="small"
              :empty-text="'No education records. Click + to add.'"
              style="width: 100%"
            >
              <el-table-column label="Academic Level" width="140" align="center">
                <template #default="{ row, $index }">
                  <el-select 
                    v-model="row.academic_level_id" 
                    placeholder="Select Level" 
                    style="width: 100%"
                    size="small"
                  >
                    <el-option label="Elementary" :value="1" />
                    <el-option label="High School" :value="2" />
                    <el-option label="College" :value="3" />
                    <el-option label="Graduate Studies" :value="4" />
                    <el-option label="Vocational/Trade" :value="5" />
                  </el-select>
                </template>
              </el-table-column>
              
              <el-table-column label="School Name" min-width="180">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.school_name" 
                    placeholder="School Name" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Program" min-width="150">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.program" 
                    placeholder="Program/Course" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Period of Attendance From" width="140" align="center">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.from" 
                    placeholder="Year" 
                    size="small"
                    maxlength="4"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Period of Attendance To" width="140" align="center">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.to" 
                    placeholder="Year" 
                    size="small"
                    maxlength="4"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Year Graduated" width="120" align="center">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.graduated_year" 
                    placeholder="Year" 
                    size="small"
                    maxlength="4"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Units Earned" width="120" align="center">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.units_earned" 
                    placeholder="Units" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Honors" min-width="120">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.honors" 
                    placeholder="Honors" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Action" width="80" align="center" fixed="right">
                <template #header>
                  <el-button
                    type="primary"
                    :icon="Plus"
                    size="small"
                    circle
                    @click="addEducation"
                  />
                </template>
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    :icon="Delete"
                    size="small"
                    circle
                    @click="removeEducation($index)"
                  />
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-tab-pane>
                
        <!-- Service Record Tab -->
        <el-tab-pane label="Service Record" name="service_record">
          <el-card shadow="never">
            <el-table
              :data="serviceRecordList"
              border
              size="small"
              :empty-text="'No service records. Click + to add.'"
              style="width: 100%"
            >
              <el-table-column label="Start Date" width="150">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.start_date"
                    type="date"
                    placeholder="Start Date"
                    size="small"
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>

              <el-table-column label="End Date" width="150">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.end_date"
                    type="date"
                    placeholder="End Date"
                    size="small"
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Designation" min-width="180">
                <template #default="{ row }">
                  <el-input
                    v-model="row.designation"
                    placeholder="Designation"
                    size="small"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Employment Type" min-width="180">
                <template #default="{ row }">
                  <el-select
                    v-model="row.employment_type"
                    placeholder="Employment Type"
                    size="small"
                    filterable
                    style="width: 100%"
                  >
                    <el-option
                      v-for="type in formOptions.employment_types || []"
                      :key="type.id"
                      :label="type.name"
                      :value="Number(type.id)"
                    />
                  </el-select>
                </template>
              </el-table-column>

              <el-table-column label="Place of Assignment" min-width="200">
                <template #default="{ row }">
                  <el-input
                    v-model="row.place_of_assignment"
                    placeholder="Place of Assignment"
                    size="small"
                  />
                </template>
              </el-table-column>

              <el-table-column label="L/WOP" width="120" align="center">
                <template #default="{ row }">
                  <el-input-number
                    v-model="row.leave_without_pay"
                    :min="0"
                    :controls="false"
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Separation Date" width="160">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.separation_date"
                    type="date"
                    placeholder="Separation Date"
                    size="small"
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Cause" min-width="200">
                <template #default="{ row }">
                  <el-input
                    v-model="row.cause"
                    placeholder="Cause"
                    size="small"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Action" width="80" fixed="right" align="center">
                <template #header>
                  <el-button
                    type="primary"
                    :icon="Plus"
                    size="small"
                    circle
                    @click="addServiceRecord"
                  />
                </template>
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    :icon="Delete"
                    size="small"
                    circle
                    @click="removeServiceRecord($index)"
                  />
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-tab-pane>
        
        <!-- Work Experience Tab -->
        <el-tab-pane label="Work Experience" name="work_experience">
          <el-card shadow="never">
            <el-table
              :data="workExperienceList"
              border
              size="small"
              :empty-text="'No work experience records. Click + to add.'"
              style="width: 100%"
            >
              <el-table-column label="Start Date" width="150">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.work_start_date"
                    type="date"
                    placeholder="Start Date"
                    size="small"
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>

              <el-table-column label="End Date" width="150">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.work_end_date"
                    type="date"
                    placeholder="End Date"
                    size="small"
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Company / Office" min-width="200">
                <template #default="{ row }">
                  <el-input
                    v-model="row.work_company"
                    placeholder="Company / Office"
                    size="small"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Position" min-width="180">
                <template #default="{ row }">
                  <el-input
                    v-model="row.position_we"
                    placeholder="Position"
                    size="small"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Monthly Salary" width="160" align="center">
                <template #default="{ row }">
                  <el-input-number
                    v-model="row.monthly_salary"
                    :min="0"
                    :precision="2"
                    :controls="false"
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Grade / Step" width="140">
                <template #default="{ row }">
                  <el-input
                    v-model="row.salary_grade_step"
                    placeholder="Grade/Step"
                    size="small"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Status" min-width="160">
                <template #default="{ row }">
                  <el-input
                    v-model="row.status_of_appointment"
                    placeholder="Status of Appointment"
                    size="small"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Gov. Service" width="140" align="center">
                <template #default="{ row }">
                  <el-switch v-model="row.government_service_id" :active-value="1" :inactive-value="0" />
                </template>
              </el-table-column>

              <el-table-column label="Action" width="80" fixed="right" align="center">
                <template #header>
                  <el-button
                    type="primary"
                    :icon="Plus"
                    size="small"
                    circle
                    @click="addWorkExperience"
                  />
                </template>
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    :icon="Delete"
                    size="small"
                    circle
                    @click="removeWorkExperience($index)"
                  />
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-tab-pane>
        
        <!-- Eligibility Tab -->
        <el-tab-pane label="Eligibility" name="eligibility">
          <el-card shadow="never">
            <el-table 
              :data="examinationsList" 
              border 
              size="small"
              :empty-text="'No examination records. Click + to add.'"
              style="width: 100%"
            >
              <el-table-column label="Examinations" width="180" align="center">
                <template #default="{ row, $index }">
                  <el-select 
                    v-model="row.eligibility_id" 
                    placeholder="Select Examination" 
                    style="width: 100%"
                    size="small"
                    filterable
                    @change="(value) => onEligibilityChange(value, $index)"
                  >
                    <el-option 
                      v-for="eligibility in formOptions.eligibilities || []" 
                      :key="eligibility.id" 
                      :label="eligibility.name" 
                      :value="Number(eligibility.id)" 
                    />
                  </el-select>
                </template>
              </el-table-column>
              
              <el-table-column label="Description" min-width="180">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.eligibility_description" 
                    placeholder="Description" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Rating" width="120" align="center">
                <template #default="{ row }">
                  <el-input-number 
                    v-model="row.exam_rating" 
                    :precision="2" 
                    :min="0" 
                    :max="999999.99"
                    :controls="false"
                    placeholder="Rating" 
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Examination Date" width="160" align="center">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.exam_date"
                    type="date"
                    placeholder="mm/dd/yyyy"
                    format="MM/DD/YYYY"
                    value-format="YYYY-MM-DD"
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Place of Exam" min-width="150">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.place_of_exam" 
                    placeholder="Place of Exam" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="License Number" width="140" align="center">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.license_number" 
                    placeholder="License Number" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Date Released" width="160" align="center">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.date_released"
                    type="date"
                    placeholder="mm/dd/yyyy"
                    format="MM/DD/YYYY"
                    value-format="YYYY-MM-DD"
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Action" width="80" align="center" fixed="right">
                <template #header>
                  <el-button
                    type="primary"
                    :icon="Plus"
                    size="small"
                    circle
                    @click="addExamination"
                  />
                </template>
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    :icon="Delete"
                    size="small"
                    circle
                    @click="removeExamination($index)"
                  />
                </template>
              </el-table-column>
          </el-table>
          </el-card>
        </el-tab-pane>
        
        <!-- Trainings Tab -->
        <el-tab-pane label="Trainings" name="trainings">
          <el-card shadow="never">
            <el-table 
              :data="trainingsList" 
              border 
              size="small"
              :empty-text="'No training records. Click + to add.'"
              style="width: 100%"
            >
              <el-table-column label="Title of Seminar/Conference/Workshops/Short Course" min-width="250">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.training" 
                    placeholder="Title of Seminar/Conference/Workshops/Short Course" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Inclusive Dates of Attendance From" width="200" align="center">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.training_from"
                    type="date"
                    placeholder="mm/dd/yyyy"
                    format="MM/DD/YYYY"
                    value-format="YYYY-MM-DD"
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Inclusive Dates of Attendance To" width="200" align="center">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.training_to"
                    type="date"
                    placeholder="mm/dd/yyyy"
                    format="MM/DD/YYYY"
                    value-format="YYYY-MM-DD"
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Number of Hours" width="140" align="center">
                <template #default="{ row }">
                  <el-input-number 
                    v-model="row.hours" 
                    :min="0" 
                    :precision="0"
                    :controls="false"
                    placeholder="Hours" 
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Conducted/Sponsored By" min-width="180">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.sponsored_by" 
                    placeholder="Conducted/Sponsored By" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Specialization" width="160" align="center">
                <template #default="{ row }">
                  <el-select 
                    v-model="row.learning_id" 
                    placeholder="Select Specialization" 
                    style="width: 100%"
                    size="small"
                    filterable
                    clearable
                  >
                    <el-option 
                      v-for="learning in formOptions.learnings || []" 
                      :key="learning.id" 
                      :label="learning.name" 
                      :value="Number(learning.id)" 
                    />
                  </el-select>
                </template>
              </el-table-column>
              
              <el-table-column label="Action" width="80" align="center" fixed="right">
                <template #header>
                  <el-button
                    type="primary"
                    :icon="Plus"
                    size="small"
                    circle
                    @click="addTraining"
                  />
                </template>
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    :icon="Delete"
                    size="small"
                    circle
                    @click="removeTraining($index)"
                  />
                </template>
              </el-table-column>
          </el-table>
          </el-card>
        </el-tab-pane>
        
        <!-- Voluntary Work Tab -->
        <el-tab-pane label="Voluntary Work" name="voluntary_work">
          <el-card shadow="never">
            <el-table 
              :data="organizationsList" 
              border 
              size="small"
              :empty-text="'No voluntary work records. Click + to add.'"
              style="width: 100%"
            >
              <el-table-column label="Organization" min-width="180">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.organization" 
                    placeholder="Organization" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Address" min-width="180">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.organization_address" 
                    placeholder="Address" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Period of Attendance From" width="200" align="center">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.org_from"
                    type="date"
                    placeholder="mm/dd/yyyy"
                    format="MM/DD/YYYY"
                    value-format="YYYY-MM-DD"
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Period of Attendance To" width="200" align="center">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.org_to"
                    type="date"
                    placeholder="mm/dd/yyyy"
                    format="MM/DD/YYYY"
                    value-format="YYYY-MM-DD"
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="No. of Hours" width="140" align="center">
                <template #default="{ row }">
                  <el-input-number 
                    v-model="row.org_hours" 
                    :min="0" 
                    :precision="0"
                    :controls="false"
                    placeholder="Hours" 
                    size="small"
                    style="width: 100%"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Position" min-width="150">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.org_position" 
                    placeholder="Position" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Action" width="80" align="center" fixed="right">
                <template #header>
                  <el-button
                    type="primary"
                    :icon="Plus"
                    size="small"
                    circle
                    @click="addOrganization"
                  />
                </template>
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    :icon="Delete"
                    size="small"
                    circle
                    @click="removeOrganization($index)"
                  />
                </template>
              </el-table-column>
          </el-table>
          </el-card>
        </el-tab-pane>
        
        <!-- Recognitions Tab -->
        <el-tab-pane label="Recognitions" name="recognitions">
          <el-card shadow="never">
            <template #header>
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600;">Non-Academic Distinctions/Recognition</span>
                <el-button
                  type="primary"
                  :icon="Plus"
                  size="small"
                  circle
                  @click="addRecognition"
                />
              </div>
            </template>
            <div style="padding: 12px 0;">
              <div 
                v-for="(recognition, index) in recognitionsList" 
                :key="index" 
                style="display: flex; align-items: center; margin-bottom: 12px; gap: 8px;"
              >
                <el-input 
                  v-model="recognition.recognation" 
                  placeholder="Enter non-academic distinction or recognition" 
                  style="flex: 1;"
                  size="small"
                />
                <el-button
                  type="danger"
                  :icon="Delete"
                  size="small"
                  circle
                  @click="removeRecognition(index)"
                />
              </div>
              <div v-if="recognitionsList.length === 0" style="text-align: center; padding: 20px; color: #909399;">
                No recognitions added. Click + to add.
              </div>
            </div>
          </el-card>
        </el-tab-pane>
        
        <!-- Skills Tab -->
        <el-tab-pane label="Skills" name="skills">
          <el-card shadow="never">
            <template #header>
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600;">Special Skills/Hobbies</span>
                <el-button
                  type="primary"
                  :icon="Plus"
                  size="small"
                  circle
                  @click="addSkill"
                />
              </div>
            </template>
            <div style="padding: 12px 0;">
              <div 
                v-for="(skill, index) in skillsList" 
                :key="index" 
                style="display: flex; align-items: center; margin-bottom: 12px; gap: 8px;"
              >
                <el-input 
                  v-model="skill.skill" 
                  placeholder="Enter special skill or hobby" 
                  style="flex: 1;"
                  size="small"
                />
                <el-button
                  type="danger"
                  :icon="Delete"
                  size="small"
                  circle
                  @click="removeSkill(index)"
                />
              </div>
              <div v-if="skillsList.length === 0" style="text-align: center; padding: 20px; color: #909399;">
                No skills/hobbies added. Click + to add.
              </div>
            </div>
          </el-card>
        </el-tab-pane>
        
        <!-- Memberships Tab -->
        <el-tab-pane label="Memberships" name="memberships">
          <el-card shadow="never">
            <div class="skills-header">
              <div class="section-heading">Membership in Association/Organization</div>
              <el-button type="primary" circle size="small" :icon="Plus" @click="addMembership" />
            </div>
            <el-divider />
            <div
              v-for="(membership, index) in membershipList"
              :key="index"
              style="display: flex; align-items: center; margin-bottom: 12px; gap: 8px;"
            >
              <el-input
                v-model="membership.membership"
                placeholder="Enter membership"
                size="small"
                style="flex: 1;"
              />
              <el-button
                type="danger"
                :icon="Delete"
                size="small"
                circle
                @click="removeMembership(index)"
              />
            </div>
            <div v-if="membershipList.length === 0" style="text-align: center; padding: 20px; color: #909399;">
              No membership records. Click + to add.
            </div>
          </el-card>
        </el-tab-pane>

        <!-- References Tab -->
        <el-tab-pane label="References" name="references">
          <el-card shadow="never">
            <el-table 
              :data="referencesList" 
              border 
              size="small"
              :empty-text="'No reference records. Click + to add.'"
              style="width: 100%"
            >
              <el-table-column label="Name" min-width="180">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.ref_name" 
                    placeholder="Name" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Address" min-width="180">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.ref_address" 
                    placeholder="Address" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Occupation" min-width="150">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.ref_occupation" 
                    placeholder="Occupation" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Contact Number" width="160" align="center">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.ref_contact_no" 
                    placeholder="Contact Number" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Email" min-width="180">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.ref_email" 
                    placeholder="Email" 
                    size="small"
                    type="email"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Action" width="80" align="center" fixed="right">
                <template #header>
                  <el-button
                    type="primary"
                    :icon="Plus"
                    size="small"
                    circle
                    @click="addReference"
                  />
                </template>
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    :icon="Delete"
                    size="small"
                    circle
                    @click="removeReference($index)"
                  />
                </template>
              </el-table-column>
          </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Dependents Tab -->
        <el-tab-pane label="Dependents" name="dependents">
          <el-card shadow="never">
            <el-table 
              :data="dependentsList" 
              border 
              size="small"
              :empty-text="'No dependent records. Click + to add.'"
              style="width: 100%"
            >
              <el-table-column label="Name" min-width="200">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.dep_name" 
                    placeholder="Name" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Relationship" min-width="180">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.dep_relationship" 
                    placeholder="Relationship" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Degree/Course sought" min-width="220">
                <template #default="{ row }">
                  <el-input 
                    v-model="row.dep_course" 
                    placeholder="Degree/Course sought" 
                    size="small"
                  />
                </template>
              </el-table-column>
              
              <el-table-column label="Action" width="80" align="center" fixed="right">
                <template #header>
                  <el-button
                    type="primary"
                    :icon="Plus"
                    size="small"
                    circle
                    @click="addDependent"
                  />
                </template>
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    :icon="Delete"
                    size="small"
                    circle
                    @click="removeDependent($index)"
                  />
                </template>
              </el-table-column>
          </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Contract Tab - Only for COS employees (employment_type_id = 2) -->
        <el-tab-pane v-if="formData.employment_type_id === 2" label="Contract" name="contract">
          <el-card shadow="never">
            <el-alert
              class="contract-upload-tip mb-3"
              type="info"
              :closable="false"
              show-icon
              title="Contract attachment guidelines"
            >
              <p class="contract-upload-tip-text">
                <strong>Accepted for upload:</strong> PDF (.pdf), Word (.doc, .docx), and images (.jpg, .jpeg, .png).
                <strong>Maximum size:</strong> {{ CONTRACT_FILE_MAX_SIZE_MB }}MB per file.
              </p>
              <p class="contract-upload-tip-text">
                <strong>Preview available for:</strong> PDF and images (.pdf, .jpg, .jpeg, .png) only.
                Word files can be uploaded but must be downloaded to view.
              </p>
              <p class="contract-upload-tip-text">
                Only one contract is active at a time, based on its start and end dates.
                Adding a renewal keeps the current contract active until it expires; the new contract becomes active on its start date.
                The active contract's monthly service fee is synced to the employee payroll record on save.
                Saved contract fees are shown as stored; changing salary grade or step auto-fills from the active salary schedule.
              </p>
            </el-alert>
            <div class="mb-3">
              <el-button type="primary" size="small" @click="addContract">
                <el-icon class="mr-1"><Plus /></el-icon>
                Add Contract
              </el-button>
            </div>
            <el-table
              :data="contractList"
              border
              size="small"
              :empty-text="'No contracts. Click + to add.'"
              style="width: 100%"
            >
              <el-table-column type="index" label="#" width="60" align="center" />
              
              <el-table-column label="Start Date" width="180">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.Start_date"
                    type="date"
                    placeholder="Start Date"
                    size="small"
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                    @change="onContractFieldChange"
                  />
                </template>
              </el-table-column>

              <el-table-column label="End Date" width="180">
                <template #default="{ row }">
                  <el-date-picker
                    v-model="row.End_date"
                    type="date"
                    placeholder="End Date"
                    size="small"
                    value-format="YYYY-MM-DD"
                    :disabled-date="(time) => {
                      // Allow all dates, including future dates (contracts can extend into future)
                      return false
                    }"
                    style="width: 100%"
                    @change="onContractFieldChange"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Salary Grade" width="160">
                <template #default="{ row }">
                  <el-select
                    v-model="row.salary_grade_id"
                    placeholder="Grade"
                    size="small"
                    filterable
                    clearable
                    style="width: 100%"
                    @change="onContractSalaryGradeStepChange(row)"
                  >
                    <el-option
                      v-for="g in formOptions.salary_grades || []"
                      :key="g.id"
                      :label="g.name || ('Grade ' + g.id)"
                      :value="Number(g.id)"
                    />
                  </el-select>
                </template>
              </el-table-column>

              <el-table-column label="Salary Step" width="140">
                <template #default="{ row }">
                  <el-select
                    v-model="row.salary_step_id"
                    placeholder="Step"
                    size="small"
                    filterable
                    clearable
                    style="width: 100%"
                    @change="onContractSalaryGradeStepChange(row)"
                  >
                    <el-option
                      v-for="st in formOptions.salary_steps || []"
                      :key="st.id"
                      :label="st.name || ('Step ' + st.id)"
                      :value="Number(st.id)"
                    />
                  </el-select>
                </template>
              </el-table-column>

              <el-table-column label="Monthly Service Fee" width="180">
                <template #default="{ row }">
                  <el-input-number
                    v-model="row.salary"
                    :precision="2"
                    :min="0"
                    :controls="false"
                    size="small"
                    style="width: 100%"
                    placeholder="0.00"
                    @change="onContractFieldChange"
                  />
                </template>
              </el-table-column>

              <el-table-column label="Active Status" width="120" align="center">
                <template #default="{ row }">
                  <el-tag :type="getContractStatusDisplay(row).type" size="small">
                    {{ getContractStatusDisplay(row).label }}
                  </el-tag>
                </template>
              </el-table-column>

              <el-table-column label="Attachment" min-width="300">
                <template #default="{ row, $index }">
                  <div class="contract-upload-cell">
                    <el-upload
                      :show-file-list="false"
                      :auto-upload="false"
                      :limit="1"
                      :accept="CONTRACT_FILE_ACCEPT"
                      :before-upload="beforeContractFileUpload"
                      :on-change="(file) => handleContractFileChange($index, file)"
                    >
                      <el-button size="small" type="primary">Upload</el-button>
                    </el-upload>
                    <span
                      v-if="getContractFileLabel(row)"
                      class="contract-file-name"
                      :title="getContractFileLabel(row)"
                    >
                      {{ getContractFileLabel(row) }}
                    </span>
                    <el-button
                      v-if="canPreviewContractFile(row)"
                      link
                      type="primary"
                      size="small"
                      :loading="previewingContractFileKey === getContractPreviewKey(row, $index)"
                      @click="handlePreviewContractFile(row, $index)"
                    >
                      Preview
                    </el-button>
                    <el-button
                      v-if="row.contract_file?.id && !row.pendingFile && !row.removeFile"
                      link
                      type="primary"
                      size="small"
                      @click="downloadContractFile(row)"
                    >
                      Download
                    </el-button>
                    <el-button
                      v-if="row.pendingFile && canPreviewContractFile(row)"
                      link
                      type="primary"
                      size="small"
                      @click="downloadPendingContractFile(row)"
                    >
                      Download
                    </el-button>
                    <el-button
                      v-if="getContractFileLabel(row)"
                      link
                      type="danger"
                      size="small"
                      @click="clearContractFile($index)"
                    >
                      Remove
                    </el-button>
                  </div>
                </template>
              </el-table-column>

              <el-table-column label="Action" width="100" align="center">
                <template #default="{ $index }">
                  <el-button
                    type="danger"
                    size="small"
                    :icon="Delete"
                    circle
                    @click="removeContract($index)"
                  />
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Documents Tab -->
        <el-tab-pane label="Documents" name="documents">
          <el-card shadow="never">
            <!-- Upload Form -->
            <el-form 
              v-if="documentFormVisible"
              :model="documentForm" 
              label-position="top" 
              size="small" 
              class="mb-4"
            >
              <el-row :gutter="16">
                <el-col :span="8">
                  <el-form-item label="Document Type" required>
                    <el-select 
                      v-model="documentForm.document_type_id" 
                      placeholder="Select Document Type" 
                      style="width: 100%"
                      filterable
                    >
                      <el-option 
                        v-for="dt in formOptions.document_types || []" 
                        :key="dt.id" 
                        :label="dt.name" 
                        :value="dt.id" 
                      />
                    </el-select>
                  </el-form-item>
                </el-col>
                <el-col :span="16">
                  <el-form-item label="Description" required>
                    <el-input 
                      v-model="documentForm.description" 
                      type="textarea" 
                      :rows="2"
                      placeholder="Enter document description"
                    />
                  </el-form-item>
                </el-col>
              </el-row>
              <el-row :gutter="16">
                <el-col :span="24">
                  <el-form-item label="Attachment" required>
                    <el-upload
                      ref="documentUploadRef"
                      :file-list="documentForm.fileList"
                      :auto-upload="false"
                      :limit="5"
                      :on-exceed="handleDocumentExceed"
                      :before-upload="beforeDocumentUpload"
                      :on-change="handleDocumentFileChange"
                      :on-remove="handleDocumentFileRemove"
                      multiple
                      drag
                    >
                      <el-icon class="el-icon--upload"><upload-filled /></el-icon>
                      <div class="el-upload__text">
                        Drop files here or <em>click to upload</em>
                      </div>
                      <template #tip>
                        <div class="el-upload__tip">
                          Accepted Files: image (.jpg, .jpeg, .png), Excel (.xls, .xlsx), Word File (.doc, .docx) and PDF (.pdf) only. Maximum of 5 files to upload. File Maximum of 100MB each.
                        </div>
                      </template>
                    </el-upload>
                  </el-form-item>
                </el-col>
              </el-row>
              <el-row>
                <el-col :span="24" style="text-align: right;">
                  <el-button @click="resetDocumentForm">Clear</el-button>
                  <el-button type="primary" @click="handleDocumentUpload" :loading="uploadingDocument">
                    Save
                  </el-button>
                </el-col>
              </el-row>
            </el-form>

            <el-divider />

            <!-- Filters and Search -->
            <el-row :gutter="16" class="mb-3">
              <el-col :span="12">
                <el-row :gutter="8" align="middle">
                  <el-col :span="10">
                    <el-date-picker
                      v-model="documentFilters.dateFrom"
                      type="date"
                      placeholder="From Date"
                      format="MM/DD/YYYY"
                      value-format="YYYY-MM-DD"
                      style="width: 100%"
                      size="small"
                    />
                  </el-col>
                  <el-col :span="2" style="text-align: center;">
                    <span>-</span>
                  </el-col>
                  <el-col :span="10">
                    <el-date-picker
                      v-model="documentFilters.dateTo"
                      type="date"
                      placeholder="To Date"
                      format="MM/DD/YYYY"
                      value-format="YYYY-MM-DD"
                      style="width: 100%"
                      size="small"
                    />
                  </el-col>
                  <el-col :span="2">
                    <el-button type="primary" size="small" @click="filterDocuments">
                      Filter
                    </el-button>
                  </el-col>
                </el-row>
              </el-col>
              <el-col :span="12" style="text-align: right;">
                <el-input
                  v-model="documentFilters.search"
                  placeholder="Search.."
                  size="small"
                  style="width: 200px;"
                  clearable
                >
                  <template #prefix>
                    <el-icon><Search /></el-icon>
                  </template>
                </el-input>
              </el-col>
            </el-row>

            <!-- Documents Table -->
            <el-table 
              :data="filteredDocuments" 
              border 
              size="small"
              :empty-text="'No documents found. Upload documents using the form above.'"
            >
              <el-table-column label="Document Type" min-width="150">
                <template #default="{ row }">
                  {{ row.name || row.document_type || row.document_type_name || 'N/A' }}
                </template>
              </el-table-column>
              <el-table-column prop="description" label="Description" min-width="200" />
              <el-table-column prop="attachment_name" label="File Name" min-width="200" />
              <el-table-column label="Upload Date" width="150">
              <template #default="{ row }">
                  {{ formatDate(row.created_at) }}
                </template>
              </el-table-column>
              <el-table-column label="Actions" width="180" align="center" fixed="right">
                <template #header>
                  <el-button
                    type="primary"
                    :icon="Plus"
                    size="small"
                    circle
                    @click="toggleDocumentForm"
                  />
                </template>
                <template #default="{ row }">
                  <el-button 
                    size="small" 
                    type="warning" 
                    text 
                    @click="handlePreviewDocument(row)"
                    :loading="previewingDocument === (row.employee_document_id || row.id || row.document_id)"
                  >
                    Preview
                  </el-button>
                  <el-button 
                    size="small" 
                    type="primary" 
                    text 
                    @click="handleDownloadDocument(row)"
                    :loading="downloadingDocument === (row.employee_document_id || row.id || row.document_id)"
                  >
                    Download
                  </el-button>
              </template>
            </el-table-column>
          </el-table>
          </el-card>
        </el-tab-pane>
      </el-tabs>
    </el-form>

    <!-- Document Preview Modal -->
    <el-dialog
      v-model="showPreviewModal"
      :title="previewDocumentRow?._previewSource === 'contract' ? 'Contract Attachment Preview' : 'Document Preview'"
      width="90%"
      :close-on-click-modal="false"
      destroy-on-close
    >
      <div v-if="previewUrl" style="text-align: center; min-height: 500px;">
        <!-- PDF Preview -->
        <iframe
          v-if="previewFileType === 'pdf'"
          :src="previewUrl"
          style="width: 100%; height: 70vh; border: none;"
          frameborder="0"
        />
        <!-- Image Preview -->
        <img
          v-else-if="previewFileType === 'image'"
          :src="previewUrl"
          style="max-width: 100%; max-height: 70vh; object-fit: contain;"
          alt="Document Preview"
        />
        <!-- Other file types - show download link -->
        <div v-else style="padding: 40px;">
          <el-icon :size="64" style="color: #909399; margin-bottom: 20px;">
            <Document />
          </el-icon>
          <p style="color: #606266; margin-bottom: 20px;">
            This file type cannot be previewed in the browser.
            <span v-if="previewDocumentRow?._previewSource === 'contract'">
              Word files (.doc, .docx) can be uploaded but must be downloaded to view.
            </span>
          </p>
          <el-button type="primary" @click="downloadFromPreview">
            Download File
          </el-button>
        </div>
      </div>
      <div v-else style="text-align: center; padding: 40px;">
        <el-icon class="is-loading" :size="48" style="color: #409eff;">
          <Loading />
        </el-icon>
        <p style="margin-top: 20px; color: #909399;">Loading preview...</p>
      </div>
      <template #footer>
        <el-button @click="showPreviewModal = false">Close</el-button>
        <el-button v-if="previewUrl && previewFileType !== 'other'" type="primary" @click="downloadFromPreview">
          Download
        </el-button>
      </template>
    </el-dialog>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">Cancel</el-button>
        <el-button type="primary" @click="handleSave" :loading="loading">
          {{ isEdit ? 'Update' : 'Create' }} Employee
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { Delete, Plus, UploadFilled, Search, Document, Loading } from '@element-plus/icons-vue'
import { useEmployee } from '@/composable/useEmployee'
import { ElMessage } from 'element-plus'
import api from '@/services/api'
import { useCompany } from '@/composable/useCompany'

const { primaryCompany, fetchCompanies } = useCompany()
const agencyName = computed(() => primaryCompany.value?.name?.trim() || 'Company Name')

// Import address reference data directly from assets
import regionsData from '@/assets/refregion.json'
import provincesData from '@/assets/refprovince.json'
import citiesData from '@/assets/refcitymun.json'
import barangaysData from '@/assets/refbrgy.json'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  employeeData: {
    type: Object,
    default: null
  },
  formOptions: {
    type: Object,
    default: () => ({})
  },
  relatedData: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'saved'])

// Composables
const { loading, createEmployee, updateEmployee, fetchEmployee } = useEmployee()

// Reactive data
const formRef = ref()
const photoPreview = ref('')
const apiBaseUrl = (api.defaults.baseURL || '').replace(/\/$/, '')
const serverBaseUrl = apiBaseUrl.replace(/\/api$/, '')

const normalizeServerUrl = (url) => {
  if (!url) return ''
  try {
    if (/^https?:\/\//i.test(url)) {
      const parsed = new URL(url)
      if (serverBaseUrl && parsed.origin !== serverBaseUrl) {
        return `${serverBaseUrl}${parsed.pathname}${parsed.search}`
      }
      return url
    }
  } catch (error) {
    console.warn('Failed to parse preview URL, using raw value:', url, error)
  }
  const cleanPath = url.startsWith('/') ? url : `/${url}`
  return serverBaseUrl ? `${serverBaseUrl}${cleanPath}` : cleanPath
}
const avatarFile = ref(null)
const activeTab = ref('basic')
const documentFormVisible = ref(true)
const isEdit = computed(() => !!props.employeeData?.id)

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Form data
const formData = reactive({
  // Basic Info
  employee_no: '',
  access_no: '',
  name_prefix_id: 0,
  first_name: '',
  middle_name: '',
  last_name: '',
  name_suffix_id: 0,
  birth_place: '',
  birthdate: '',
  age: 0,
  gender_id: 0,
  height: 0,
  weight: 0,
  blood_type_id: 0,
  
  // Contact Info
  email: '',
  mobile_no: '',
  telephone_no: '',
  civil_status_id: 0,
  citizenship_id: 0,
  religion_id: 0,
  
  // Employment Info
  company_id: 1,
  department_id: 0,
  position_id: 0,
  employment_type_id: 0,
  date_hired: '',
  salary: 0,
  is_plantilla: false,
  is_teaching: false,
  is_employee: true,
  plantilla_id: 0,
  division_id: 0,
  section_id: 0,
  salary_grade_id: 0,
  salary_step_id: 0,
  
  // Payroll Related
  payroll_interval_id: 0,
  account_no: '',
  is_hold: false,
  hold_remarks: '',
  tax_amount: 0,
  gsis_amount: 0,
  sss_amount: 0,
  pagibig_amount: 0,
  philhealth_amount: 0,
  
  // Government IDs
  tin_no: '',
  gsis_no: '',
  sss_no: '',
  pagibig_no: '',
  philhealth_no: '',
  crn_no: '',
  
  // Current Address (using codes)
  ca_region: '',
  ca_province: '',
  ca_city: '',
  ca_barangay: '',
  ca_zip: '',
  ca_house_no: '',
  ca_street: '',
  ca_village: '',
  
  // Permanent Address (using codes)
  pa_region: '',
  pa_province: '',
  pa_city: '',
  pa_barangay: '',
  pa_zip: '',
  pa_house_no: '',
  pa_street: '',
  pa_village: '',
  
  // Family Info
  father_name_prefix_id: 0,
  father_first_name: '',
  father_middle_name: '',
  father_last_name: '',
  father_name_suffix_id: 0,
  mother_name_prefix_id: 0,
  mother_first_name: '',
  mother_middle_name: '',
  mother_last_name: '',
  mother_name_suffix_id: 0,
  spouse_name_prefix_id: 0,
  spouse_first_name: '',
  spouse_middle_name: '',
  spouse_last_name: '',
  spouse_name_suffix_id: 0,
  spouse_occupation: '',
  spouse_employer: '',
  spouse_business_address: '',
  spouse_mobile_no: ''
})

// Children list
const childrenList = ref([])

// Education list
const educationList = ref([])

// Service record list
const serviceRecordList = ref([])

// Contract list (for COS employees)
const contractList = ref([])

// Work experience list
const workExperienceList = ref([])

// Examinations list
const examinationsList = ref([])

// Trainings list
const trainingsList = ref([])

// Organizations (Voluntary Work) list
const organizationsList = ref([])

// Skills list
const skillsList = ref([])

// Recognitions list
const recognitionsList = ref([])

// Memberships list
const membershipList = ref([])

// References list
const referencesList = ref([])

// Dependents list
const dependentsList = ref([])

// Documents management
const documentForm = reactive({
  document_type_id: null,
  description: '',
  fileList: [],
  editingDocumentId: null
})

const documentFilters = reactive({
  dateFrom: null,
  dateTo: null,
  search: ''
})

const uploadingDocument = ref(false)
const documentUploadRef = ref()
const documentsList = ref([])
const previewingDocument = ref(null)
const downloadingDocument = ref(null)
const showPreviewModal = ref(false)
const previewUrl = ref(null)
const previewFileType = ref('pdf')
const previewDocumentRow = ref(null)

const revokePreviewObjectUrlIfAny = () => {
  const url = previewUrl.value
  if (url && typeof url === 'string' && url.startsWith('blob:')) {
    try {
      URL.revokeObjectURL(url)
    } catch {
      // ignore
    }
  }
}

watch(showPreviewModal, (isOpen) => {
  if (!isOpen) {
    revokePreviewObjectUrlIfAny()
    previewUrl.value = null
    previewDocumentRow.value = null
  }
})

const createGuardedPreviewObjectUrl = async (response, extension) => {
  // Create blob URL with correct MIME type. Server may send wrong Content-Type
  // (e.g. text/html in test/proxy), so we force the type from document extension.
  const mimeType =
    extension === 'pdf'
      ? 'application/pdf'
      : extension === 'jpg' || extension === 'jpeg'
        ? 'image/jpeg'
        : extension === 'png'
          ? 'image/png'
          : 'application/octet-stream'

  // Strip UTF-8 BOM (EF BB BF) if present — test/proxy sometimes prepends it and
  // breaks image (PNG/JPEG) signature so <img> shows broken; PDF can still render.
  let payload = response.data
  if (response.data instanceof Blob && response.data.size >= 3) {
    try {
      const bomBuf = await response.data.slice(0, 3).arrayBuffer()
      const b = new Uint8Array(bomBuf)
      if (b[0] === 0xef && b[1] === 0xbb && b[2] === 0xbf) {
        payload = response.data.slice(3)
      }
    } catch {
      // If BOM detection fails, fall back to raw blob
    }
  }

  const blob =
    payload instanceof Blob
      ? new Blob([payload], { type: mimeType })
      : new Blob([payload], { type: mimeType })

  return URL.createObjectURL(blob)
}

// Form validation rules
const formRules = {
  employee_no: [
    { required: true, message: 'Employee number is required', trigger: 'blur' }
  ],
  email: [
    { required: true, message: 'Email is required', trigger: 'blur' },
    { type: 'email', message: 'Please enter a valid email', trigger: 'blur' }
  ],
  first_name: [
    { required: true, message: 'First name is required', trigger: 'blur' }
  ],
  last_name: [
    { required: true, message: 'Last name is required', trigger: 'blur' }
  ]
}

// Auto-save functionality
const getStorageKey = () => {
  const employeeId = props.employeeData?.id
  return employeeId ? `employee_form_draft_${employeeId}` : 'employee_form_draft_new'
}

let autoSaveTimeout = null
let isRestoringFromLocalStorage = false
const autoSaveStatus = ref('') // 'saving', 'saved', ''

const saveToLocalStorage = () => {
  try {
    const storageKey = getStorageKey()
    const dataToSave = {
      formData: { ...formData },
      childrenList: [...childrenList.value],
      educationList: [...educationList.value],
      serviceRecordList: [...serviceRecordList.value],
      contractList: [...contractList.value],
      workExperienceList: [...workExperienceList.value],
      examinationsList: [...examinationsList.value],
      trainingsList: [...trainingsList.value],
      organizationsList: [...organizationsList.value],
      skillsList: [...skillsList.value],
      recognitionsList: [...recognitionsList.value],
      membershipList: [...membershipList.value],
      referencesList: [...referencesList.value],
      dependentsList: [...dependentsList.value],
      activeTab: activeTab.value,
      timestamp: new Date().toISOString()
    }
    localStorage.setItem(storageKey, JSON.stringify(dataToSave))
    autoSaveStatus.value = 'saved'
    // Clear status after 2 seconds
    setTimeout(() => {
      if (autoSaveStatus.value === 'saved') {
        autoSaveStatus.value = ''
      }
    }, 2000)
  } catch (error) {
    console.warn('Failed to save to localStorage:', error)
    autoSaveStatus.value = ''
  }
}

const debouncedSave = () => {
  if (autoSaveTimeout) {
    clearTimeout(autoSaveTimeout)
  }
  autoSaveStatus.value = 'saving'
  autoSaveTimeout = setTimeout(() => {
    saveToLocalStorage()
  }, 1000) // Save after 1 second of inactivity
}

// Immediate save (for page refresh/unload)
const immediateSave = () => {
  if (autoSaveTimeout) {
    clearTimeout(autoSaveTimeout)
  }
  autoSaveStatus.value = 'saving'
  saveToLocalStorage()
}

// Handle page refresh/unload - save immediately
const handleBeforeUnload = (e) => {
  if (visible.value) {
    // Save immediately before page unloads
    immediateSave()
    // Optional: Show browser warning (commented out to allow smooth refresh)
    // e.preventDefault()
    // e.returnValue = ''
  }
}

const restoreFromLocalStorage = () => {
  try {
    const storageKey = getStorageKey()
    const savedData = localStorage.getItem(storageKey)
    if (savedData) {
      isRestoringFromLocalStorage = true
      const parsed = JSON.parse(savedData)
      
      // Restore formData
      Object.keys(parsed.formData || {}).forEach(key => {
        if (formData.hasOwnProperty(key)) {
          formData[key] = parsed.formData[key]
        }
      })
      
      // Restore lists
      if (parsed.childrenList) childrenList.value = parsed.childrenList
      if (parsed.educationList) educationList.value = parsed.educationList
      if (parsed.serviceRecordList) serviceRecordList.value = parsed.serviceRecordList
      if (parsed.contractList) contractList.value = parsed.contractList
      if (parsed.workExperienceList) workExperienceList.value = parsed.workExperienceList
      if (parsed.examinationsList) examinationsList.value = parsed.examinationsList
      if (parsed.trainingsList) trainingsList.value = parsed.trainingsList
      if (parsed.organizationsList) organizationsList.value = parsed.organizationsList
      if (parsed.skillsList) skillsList.value = parsed.skillsList
      if (parsed.recognitionsList) recognitionsList.value = parsed.recognitionsList
      if (parsed.membershipList) membershipList.value = parsed.membershipList
      if (parsed.referencesList) referencesList.value = parsed.referencesList
      if (parsed.dependentsList) dependentsList.value = parsed.dependentsList
      if (parsed.activeTab) activeTab.value = parsed.activeTab
      
      // Reset flag after a short delay to allow watchers to settle
      setTimeout(() => {
        isRestoringFromLocalStorage = false
      }, 100)
      
      return true
    }
  } catch (error) {
    console.warn('Failed to restore from localStorage:', error)
    isRestoringFromLocalStorage = false
  }
  return false
}

const clearLocalStorage = () => {
  try {
    const storageKey = getStorageKey()
    localStorage.removeItem(storageKey)
  } catch (error) {
    console.warn('Failed to clear localStorage:', error)
  }
}

// Load address data from imported JSON files
const addressData = reactive({
  regions: regionsData.RECORDS || [],
  provinces: provincesData.RECORDS || [],
  cities: citiesData.RECORDS || [],
  barangays: barangaysData.RECORDS || []
})

// Log loaded data for debugging
console.log('📊 Address Data Loaded from Assets:', {
  regions: addressData.regions.length,
  provinces: addressData.provinces.length,
  cities: addressData.cities.length,
  barangays: addressData.barangays.length,
  hasRIZAL: addressData.cities.some(c => c.provCode === '0458'),
  sampleCity: addressData.cities[0]
})

// Computed properties for filtered address options
const currentProvinces = computed(() => {
  if (!formData.ca_region || !addressData.provinces) return []
  const regionCode = String(formData.ca_region).trim()
  return addressData.provinces.filter(p => {
    if (!p || !p.regCode) return false
    return String(p.regCode).trim() === regionCode
  })
})

const currentCities = computed(() => {
  // Check if province is selected
  if (!formData.ca_province || !addressData.cities) {
    return []
  }
  
  // Normalize province code for comparison
  const selectedProvince = String(formData.ca_province).trim()
  
  if (!selectedProvince) {
    return []
  }
  
  // Filter cities by province code - ensure exact match
  const filtered = addressData.cities.filter(city => {
    if (!city || !city.provCode) return false
    const cityProvCode = String(city.provCode).trim()
    return cityProvCode === selectedProvince
  })
  
  // Debug logging when no cities found
  if (filtered.length === 0 && selectedProvince) {
    console.warn('🔍 No cities found for province:', selectedProvince)
  } else if (filtered.length > 0) {
    console.log('✓ Found', filtered.length, 'cities for province', selectedProvince)
  }
  
  // Sort cities alphabetically by description
  return filtered.sort((a, b) => {
    const nameA = (a.citymunDesc || '').toUpperCase()
    const nameB = (b.citymunDesc || '').toUpperCase()
    return nameA.localeCompare(nameB)
  })
})

const currentBarangays = computed(() => {
  // Check if city is selected
  if (!formData.ca_city || !addressData.barangays) {
    return []
  }
  
  // Normalize city code for comparison
  const selectedCity = String(formData.ca_city).trim()
  
  if (!selectedCity) {
    return []
  }
  
  // Filter barangays by city code
  const filtered = addressData.barangays.filter(barangay => {
    if (!barangay || !barangay.citymunCode) return false
    const barangayCityCode = String(barangay.citymunCode).trim()
    return barangayCityCode === selectedCity
  })
  
  // Sort barangays alphabetically by description
  return filtered.sort((a, b) => {
    const nameA = (a.brgyDesc || '').toUpperCase()
    const nameB = (b.brgyDesc || '').toUpperCase()
    return nameA.localeCompare(nameB)
  })
})

const permanentProvinces = computed(() => {
  if (!formData.pa_region || !addressData.provinces) return []
  const regionCode = String(formData.pa_region).trim()
  return addressData.provinces.filter(p => {
    if (!p || !p.regCode) return false
    return String(p.regCode).trim() === regionCode
  })
})

const permanentCities = computed(() => {
  // Check if province is selected
  if (!formData.pa_province || !addressData.cities) {
    return []
  }
  
  // Normalize province code for comparison
  const selectedProvince = String(formData.pa_province).trim()
  
  if (!selectedProvince) {
    return []
  }
  
  // Filter cities by province code
  const filtered = addressData.cities.filter(city => {
    if (!city || !city.provCode) return false
    const cityProvCode = String(city.provCode).trim()
    return cityProvCode === selectedProvince
  })
  
  // Sort cities alphabetically by description
  return filtered.sort((a, b) => {
    const nameA = (a.citymunDesc || '').toUpperCase()
    const nameB = (b.citymunDesc || '').toUpperCase()
    return nameA.localeCompare(nameB)
  })
})

const permanentBarangays = computed(() => {
  // Check if city is selected
  if (!formData.pa_city || !addressData.barangays) {
    return []
  }
  
  // Normalize city code for comparison
  const selectedCity = String(formData.pa_city).trim()
  
  if (!selectedCity) {
    return []
  }
  
  // Filter barangays by city code
  const filtered = addressData.barangays.filter(barangay => {
    if (!barangay || !barangay.citymunCode) return false
    const barangayCityCode = String(barangay.citymunCode).trim()
    return barangayCityCode === selectedCity
  })
  
  // Sort barangays alphabetically by description
  return filtered.sort((a, b) => {
    const nameA = (a.brgyDesc || '').toUpperCase()
    const nameB = (b.brgyDesc || '').toUpperCase()
    return nameA.localeCompare(nameB)
  })
})

// Filter plantillas based on selected department and is_plantilla flag
const filteredPlantillas = computed(() => {
  // Only show plantillas if is_plantilla is enabled
  if (!formData.is_plantilla || !formData.department_id || !props.formOptions.plantillas) {
    return []
  }
  
  const departmentId = Number(formData.department_id)
  
  // Filter plantillas where department_id matches
  const filtered = props.formOptions.plantillas.filter(plantilla => {
    // Handle both number and string comparisons
    const plantillaDeptId = plantilla.department_id ? Number(plantilla.department_id) : null
    return plantillaDeptId === departmentId
  })
  
  // Sort by code if available
  return filtered.sort((a, b) => {
    const codeA = (a.code || '').toUpperCase()
    const codeB = (b.code || '').toUpperCase()
    return codeA.localeCompare(codeB)
  })
})

// Computed properties to get names from IDs for display
const departmentName = computed(() => {
  if (!formData.department_id || !props.formOptions.departments || !Array.isArray(props.formOptions.departments)) return ''
  // Ensure both values are numbers for comparison
  const deptId = Number(formData.department_id)
  if (isNaN(deptId) || deptId === 0) return ''
  const dept = props.formOptions.departments.find(d => Number(d.id) === deptId)
  return dept ? dept.name : ''
})

const plantillaName = computed(() => {
  if (!formData.plantilla_id || !props.formOptions.plantillas) return ''
  const plantilla = props.formOptions.plantillas.find(p => Number(p.id) === Number(formData.plantilla_id))
  if (!plantilla) return ''
  return plantilla.code || plantilla.item_no || plantilla.name || `Item #${plantilla.id}`
})

const divisionName = computed(() => {
  if (!formData.division_id || !props.formOptions.divisions || !Array.isArray(props.formOptions.divisions)) return ''
  const divId = Number(formData.division_id)
  if (isNaN(divId) || divId === 0) return ''
  const division = props.formOptions.divisions.find(d => Number(d.id) === divId)
  return division ? division.name : ''
})

const sectionName = computed(() => {
  if (!formData.section_id || !props.formOptions.sections) return ''
  const section = props.formOptions.sections.find(s => Number(s.id) === Number(formData.section_id))
  return section ? section.name : ''
})

const salaryGradeName = computed(() => {
  if (!formData.salary_grade_id || !props.formOptions.salary_grades) return ''
  const grade = props.formOptions.salary_grades.find(g => Number(g.id) === Number(formData.salary_grade_id))
  return grade ? (grade.name || `Grade ${grade.id}`) : ''
})

const salaryStepName = computed(() => {
  if (!formData.salary_step_id || !props.formOptions.salary_steps) return ''
  const step = props.formOptions.salary_steps.find(s => Number(s.id) === Number(formData.salary_step_id))
  return step ? (step.name || `Step ${step.id}`) : ''
})

const payrollIntervalName = computed(() => {
  if (!formData.payroll_interval_id || !props.formOptions.payroll_intervals) return ''
  const interval = props.formOptions.payroll_intervals.find(i => Number(i.id) === Number(formData.payroll_interval_id))
  return interval ? interval.name : ''
})

// Computed properties for payroll calculations
const dailyRate = computed(() => {
  if (!formData.salary || formData.salary <= 0) return 0
  if (!formData.payroll_interval_id) return 0
  
  // Get payroll interval name to determine calculation
  const interval = props.formOptions.payroll_intervals?.find(i => Number(i.id) === Number(formData.payroll_interval_id))
  if (!interval) return 0
  
  const intervalName = (interval.name || '').toLowerCase()
  
  // Calculate based on interval type
  if (intervalName.includes('monthly')) {
    // Monthly: divide by 22 working days (standard)
    return Number((formData.salary / 22).toFixed(2))
  } else if (intervalName.includes('semi-monthly')) {
    // Semi-monthly: divide by 11 working days
    return Number((formData.salary / 11).toFixed(2))
  } else if (intervalName.includes('weekly')) {
    // Weekly: divide by 5 working days
    return Number((formData.salary / 5).toFixed(2))
  } else if (intervalName.includes('daily')) {
    // Daily: same as salary
    return Number(formData.salary.toFixed(2))
  }
  
  // Default: assume monthly
  return Number((formData.salary / 22).toFixed(2))
})

const hourlyRate = computed(() => {
  if (!dailyRate.value || dailyRate.value <= 0) return 0
  // Standard 8 hours per day
  return Number((dailyRate.value / 8).toFixed(2))
})

// Format currency for display
const formatCurrency = (value) => {
  if (!value && value !== 0) return '0.00'
  return Number(value).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

// Flag to prevent watchers from clearing fields during form population
const isPopulatingForm = ref(false)

// Watchers to reset dependent fields when parent changes (only if value actually changed)
watch(() => formData.ca_region, (newVal, oldVal) => {
  if (isPopulatingForm.value) return // Skip during population
  if (newVal !== oldVal && oldVal !== undefined && oldVal !== '') {
    formData.ca_province = ''
    formData.ca_city = ''
    formData.ca_barangay = ''
  }
})

watch(() => formData.ca_province, (newVal, oldVal) => {
  if (isPopulatingForm.value) return // Skip during population
  if (newVal !== oldVal && oldVal !== undefined && oldVal !== '') {
    formData.ca_city = ''
    formData.ca_barangay = ''
  }
})

watch(() => formData.ca_city, (newVal, oldVal) => {
  if (isPopulatingForm.value) return // Skip during population
  if (newVal !== oldVal && oldVal !== undefined && oldVal !== '') {
    formData.ca_barangay = ''
  }
})

watch(() => formData.pa_region, (newVal, oldVal) => {
  if (isPopulatingForm.value) return // Skip during population
  if (newVal !== oldVal && oldVal !== undefined && oldVal !== '') {
    formData.pa_province = ''
    formData.pa_city = ''
    formData.pa_barangay = ''
  }
})

watch(() => formData.pa_province, (newVal, oldVal) => {
  if (isPopulatingForm.value) return // Skip during population
  if (newVal !== oldVal && oldVal !== undefined && oldVal !== '') {
    formData.pa_city = ''
    formData.pa_barangay = ''
  }
})

watch(() => formData.pa_city, (newVal, oldVal) => {
  if (isPopulatingForm.value) return // Skip during population
  if (newVal !== oldVal && oldVal !== undefined && oldVal !== '') {
    formData.pa_barangay = ''
  }
})

// Watch for department changes and reset plantilla_id
watch(() => formData.department_id, (newDeptId, oldDeptId) => {
  // Don't reset during form population
  if (isPopulatingForm.value) return
  // Reset plantilla_id when department changes (but not on initial load)
  if (newDeptId !== oldDeptId && oldDeptId !== undefined && oldDeptId !== null && oldDeptId !== '') {
    formData.plantilla_id = 0
  }
})

// Watch for is_plantilla changes and reset plantilla_id when disabled
watch(() => formData.is_plantilla, (newValue, oldValue) => {
  // Don't reset during form population
  if (isPopulatingForm.value) return
  // Reset plantilla_id when is_plantilla is turned off (but not on initial load)
  if (!newValue && oldValue !== undefined && oldValue !== null) {
    formData.plantilla_id = 0
  }
})

// Watch for plantilla selection and auto-populate related fields
watch(() => formData.plantilla_id, (newPlantillaId) => {
  if (newPlantillaId && props.formOptions.plantillas) {
    // Find the selected plantilla
    const selectedPlantilla = props.formOptions.plantillas.find(
      pl => Number(pl.id) === Number(newPlantillaId)
    )
    
    if (selectedPlantilla) {
      // Auto-populate department, division, section, position, salary grade, and salary step.
      // Only override when plantilla has a value; if missing, keep existing/null.
      if (selectedPlantilla.department_id != null) {
        formData.department_id = Number(selectedPlantilla.department_id)
      }
      if (selectedPlantilla.division_id != null) {
        formData.division_id = Number(selectedPlantilla.division_id)
      }
      if (selectedPlantilla.section_id != null) {
        formData.section_id = Number(selectedPlantilla.section_id)
      }

      // NOTE: Temporarily swapped - salary_grade_id gets salary_step_id value and vice versa
      if (selectedPlantilla.position_id) {
        formData.position_id = Number(selectedPlantilla.position_id)
      }
      if (selectedPlantilla.salary_step_id) {
        formData.salary_grade_id = Number(selectedPlantilla.salary_step_id)
      }
      if (selectedPlantilla.salary_grade_id) {
        formData.salary_step_id = Number(selectedPlantilla.salary_grade_id)
      }
    }
  } else if (!newPlantillaId) {
    // If plantilla is cleared, optionally clear the related fields
    // (You might want to keep them filled, so I'll leave them as is)
  }
})



// Methods
const calculateAge = () => {
  if (formData.birthdate) {
    const today = new Date()
    const birthDate = new Date(formData.birthdate)
    let age = today.getFullYear() - birthDate.getFullYear()
    const monthDiff = today.getMonth() - birthDate.getMonth()
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--
    }
    
    formData.age = age
  }
}

const resetForm = () => {
  Object.keys(formData).forEach(key => {
    if (typeof formData[key] === 'boolean') {
      formData[key] = false
    } else if (typeof formData[key] === 'number') {
      formData[key] = 0
    } else {
      formData[key] = ''
    }
  })
  formData.is_employee = true
  formData.company_id = 1
  childrenList.value = []
  educationList.value = []
  serviceRecordList.value = []
  contractList.value = []
  workExperienceList.value = []
  examinationsList.value = []
  trainingsList.value = []
  organizationsList.value = []
  skillsList.value = []
  recognitionsList.value = []
  membershipList.value = []
  referencesList.value = []
  dependentsList.value = []
  resetDocumentForm()
  documentsList.value = []
  documentFilters.dateFrom = null
  documentFilters.dateTo = null
  documentFilters.search = ''
}

// Children management
const addChild = () => {
  childrenList.value.push({
    children_id: null,
    first_name: '',
    middle_name: '',
    last_name: '',
    birthdate: '',
    gender_id: 0
  })
}

const removeChild = (index) => {
  childrenList.value.splice(index, 1)
}

// Education management
const addEducation = () => {
  educationList.value.push({
    education_id: null,
    academic_level_id: 0,
    school_name: '',
    program: '',
    from: '',
    to: '',
    graduated_year: '',
    units_earned: '',
    honors: ''
  })
}

const removeEducation = (index) => {
  educationList.value.splice(index, 1)
}

// Service record management
const addServiceRecord = () => {
  serviceRecordList.value.push({
    service_record_id: null,
    start_date: '',
    end_date: '',
    designation: '',
    employment_type: null,
    place_of_assignment: '',
    leave_without_pay: 0,
    separation_date: '',
    cause: ''
  })
}

const removeServiceRecord = (index) => {
  serviceRecordList.value.splice(index, 1)
}

// Contract management (COS employees)
const getContractScheduleSalary = (gradeId, stepId) => {
  const grade = Number(gradeId)
  const step = Number(stepId)
  if (!grade || !step) return null

  const scheduleAmounts = props.formOptions?.salary_schedule_amounts || {}
  const amount = scheduleAmounts[`${grade}-${step}`]

  return amount != null ? Number(amount) : null
}

const syncContractRowSalary = (row) => {
  if (!row) return

  const grade = Number(row.salary_grade_id)
  const step = Number(row.salary_step_id)
  if (!grade || !step) {
    row.salary = 0
    return
  }

  row.salary = getContractScheduleSalary(grade, step) ?? 0
}

const parseContractDate = (dateStr) => {
  if (!dateStr) return null
  const [year, month, day] = dateStr.split('-').map(Number)
  return new Date(year, month - 1, day)
}

const getTodayDateOnly = () => {
  const today = new Date()
  return new Date(today.getFullYear(), today.getMonth(), today.getDate())
}

const isContractEffectiveOnDate = (contract, refDate = getTodayDateOnly()) => {
  if (!contract?.Start_date && !contract?.End_date) return false

  const today = new Date(refDate.getFullYear(), refDate.getMonth(), refDate.getDate())
  const start = parseContractDate(contract.Start_date)
  const end = parseContractDate(contract.End_date)

  if (start && start > today) return false
  if (end && end < today) return false

  return true
}

const isFutureContract = (contract) => {
  const start = parseContractDate(contract?.Start_date)
  if (!start) return false
  return start > getTodayDateOnly()
}

const getContractStatusDisplay = (contract) => {
  if (isContractEffectiveOnDate(contract)) {
    return { label: 'Active', type: 'success' }
  }
  if (isFutureContract(contract)) {
    return { label: 'Scheduled', type: 'warning' }
  }
  return { label: 'Inactive', type: 'info' }
}

const getActiveCosContract = () => {
  return contractList.value.find((contract) => isContractEffectiveOnDate(contract)) || null
}

const addContract = () => {
  let defaultStartDate = ''
  if (contractList.value.length === 0 && formData.date_hired) {
    defaultStartDate = formData.date_hired
  } else if (contractList.value.length > 0) {
    const latestEndDate = contractList.value
      .map((contract) => contract.End_date)
      .filter(Boolean)
      .sort()
      .pop()
    if (latestEndDate) {
      const nextDay = new Date(latestEndDate)
      nextDay.setDate(nextDay.getDate() + 1)
      const year = nextDay.getFullYear()
      const month = String(nextDay.getMonth() + 1).padStart(2, '0')
      const day = String(nextDay.getDate()).padStart(2, '0')
      defaultStartDate = `${year}-${month}-${day}`
    }
  }

  const isFirstContract = contractList.value.length === 0

  contractList.value.push({
    id: null,
    Start_date: defaultStartDate,
    End_date: '',
    salary: Number(formData.salary) || 0,
    salary_grade_id: Number(formData.salary_grade_id) || 0,
    salary_step_id: Number(formData.salary_step_id) || 0,
    is_active: isFirstContract,
    contract_file: null,
    pendingFile: null,
    removeFile: false
  })

  syncContractRowSalary(contractList.value[contractList.value.length - 1])
  onContractFieldChange()
}

const syncWorkInfoFromActiveContract = () => {
  if (Number(formData.employment_type_id) !== 2) return

  const activeContract = getActiveCosContract()
  if (!activeContract) return

  formData.salary_grade_id = Number(activeContract.salary_grade_id) || 0
  formData.salary_step_id = Number(activeContract.salary_step_id) || 0
  formData.salary = Number(activeContract.salary) || 0
}

const onContractFieldChange = () => {
  syncCosPayrollHoldPreview()
  syncWorkInfoFromActiveContract()
}

const onContractSalaryGradeStepChange = (row) => {
  syncContractRowSalary(row)
  onContractFieldChange()
}

const formatContractPeriod = (row) => {
  if (!row?.Start_date && !row?.End_date) return '—'
  const start = row.Start_date || '—'
  const end = row.End_date || '—'
  return `${start} to ${end}`
}

// Contract attachment upload / preview settings
const CONTRACT_FILE_MAX_SIZE_MB = 10
const CONTRACT_FILE_ACCEPT = '.pdf,.jpg,.jpeg,.png,.doc,.docx'
const CONTRACT_FILE_ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']
const CONTRACT_FILE_PREVIEW_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png']
const previewingContractFileKey = ref(null)
const COS_AUTO_HOLD_REMARK = 'Automatic hold: No active COS contract or contract end date has passed.'

const cosHasActiveContract = computed(() =>
  contractList.value.some((contract) => isContractEffectiveOnDate(contract))
)

const syncCosPayrollHoldPreview = () => {
  if (Number(formData.employment_type_id) !== 2) return

  if (!cosHasActiveContract.value) {
    formData.is_hold = true
    formData.hold_remarks = COS_AUTO_HOLD_REMARK
    return
  }

  if (formData.hold_remarks === COS_AUTO_HOLD_REMARK) {
    formData.is_hold = false
    formData.hold_remarks = ''
  }
}

const getContractFileExtension = (row) => {
  const name = row?.pendingFile?.name || row?.contract_file?.file_name || ''
  return (name.split('.').pop() || '').toLowerCase()
}

const canPreviewContractFile = (row) => {
  if (!getContractFileLabel(row)) return false
  return CONTRACT_FILE_PREVIEW_EXTENSIONS.includes(getContractFileExtension(row))
}

const getContractPreviewKey = (row, index) => {
  return row?.contract_file?.id || row?.pendingFile?.name || `contract-row-${index}`
}

const resolvePreviewFileType = (extension) => {
  if (extension === 'pdf') return 'pdf'
  if (['jpg', 'jpeg', 'png'].includes(extension)) return 'image'
  return 'other'
}

const beforeContractFileUpload = (file) => {
  const fileExtension = (file.name.split('.').pop() || '').toLowerCase()
  const isValidType = CONTRACT_FILE_ALLOWED_EXTENSIONS.includes(fileExtension)
  const isLtMax = file.size / 1024 / 1024 < CONTRACT_FILE_MAX_SIZE_MB

  if (!isValidType) {
    ElMessage.error(`File type not allowed. Accepted: ${CONTRACT_FILE_ACCEPT}`)
    return false
  }
  if (!isLtMax) {
    ElMessage.error(`File size must be less than ${CONTRACT_FILE_MAX_SIZE_MB}MB`)
    return false
  }
  return false
}

const handleContractFileChange = (index, file) => {
  const row = contractList.value[index]
  if (!row || !file?.raw) return
  row.pendingFile = file.raw
  row.removeFile = false
}

const getContractFileLabel = (row) => {
  if (row?.pendingFile?.name) return row.pendingFile.name
  if (row?.removeFile) return ''
  return row?.contract_file?.file_name || ''
}

const clearContractFile = (index) => {
  const row = contractList.value[index]
  if (!row) return
  row.pendingFile = null
  if (row.contract_file?.id) {
    row.removeFile = true
  } else {
    row.removeFile = false
    row.contract_file = null
  }
}

const downloadContractFile = async (row) => {
  if (!row?.contract_file?.id) return
  try {
    const response = await api.get(`/cos-contract-files/${row.contract_file.id}/download`, {
      responseType: 'blob'
    })
    const blob = new Blob([response.data], {
      type: response.headers['content-type'] || 'application/octet-stream'
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = row.contract_file.file_name || 'contract-file'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error(error)
    ElMessage.error('Failed to download contract attachment')
  }
}

const downloadPendingContractFile = (row) => {
  if (!row?.pendingFile) return
  const url = window.URL.createObjectURL(row.pendingFile)
  const link = document.createElement('a')
  link.href = url
  link.download = row.pendingFile.name || 'contract-file'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

const handlePreviewContractFile = async (row, index) => {
  if (!canPreviewContractFile(row)) {
    ElMessage.warning('Only PDF and image files (.pdf, .jpg, .jpeg, .png) can be previewed.')
    return
  }

  const previewKey = getContractPreviewKey(row, index)
  previewingContractFileKey.value = previewKey
  previewDocumentRow.value = { ...row, _previewSource: 'contract' }
  revokePreviewObjectUrlIfAny()
  previewUrl.value = null
  showPreviewModal.value = true

  const extension = getContractFileExtension(row)
  previewFileType.value = resolvePreviewFileType(extension)

  try {
    if (row.pendingFile) {
      previewUrl.value = URL.createObjectURL(row.pendingFile)
      return
    }

    if (row.contract_file?.id) {
      const response = await api.get(`/cos-contract-files/${row.contract_file.id}/download`, {
        responseType: 'blob'
      })
      previewUrl.value = await createGuardedPreviewObjectUrl(response, extension)
      return
    }

    throw new Error('No contract file available for preview')
  } catch (error) {
    console.error(error)
    ElMessage.error('Failed to preview contract attachment')
    showPreviewModal.value = false
  } finally {
    previewingContractFileKey.value = null
  }
}

const removeContract = (index) => {
  contractList.value.splice(index, 1)
  onContractFieldChange()
}

// Work experience management
const addWorkExperience = () => {
  workExperienceList.value.push({
    employment_record_id: null,
    work_start_date: '',
    work_end_date: '',
    work_company: '',
    position_we: '',
    monthly_salary: 0,
    salary_grade_step: '',
    status_of_appointment: '',
    government_service_id: 0,
    work_specialization_id: 0,
    is_present: false
  })
}

const removeWorkExperience = (index) => {
  workExperienceList.value.splice(index, 1)
}

// Examinations management
const addExamination = () => {
  examinationsList.value.push({
    examination_id: null,
    eligibility_id: 0,
    eligibility_description: '',
    exam_rating: 0,
    exam_date: '',
    place_of_exam: '',
    license_number: '',
    date_released: ''
  })
}

const removeExamination = (index) => {
  examinationsList.value.splice(index, 1)
}

// Handle eligibility selection change to auto-populate description
const onEligibilityChange = (eligibilityId, index) => {
  if (eligibilityId && props.formOptions.eligibilities) {
    const selectedEligibility = props.formOptions.eligibilities.find(
      el => Number(el.id) === Number(eligibilityId)
    )
    if (selectedEligibility && examinationsList.value[index]) {
      // Auto-populate description with eligibility name if description is empty
      if (!examinationsList.value[index].eligibility_description) {
        examinationsList.value[index].eligibility_description = selectedEligibility.name || ''
      }
    }
  }
}

// Trainings management
const addTraining = () => {
  trainingsList.value.push({
    training_id: null,
    training: '',
    training_from: '',
    training_to: '',
    hours: 0,
    sponsored_by: '',
    learning_id: 0
  })
}

const removeTraining = (index) => {
  trainingsList.value.splice(index, 1)
}

// Organizations (Voluntary Work) management
const addOrganization = () => {
  organizationsList.value.push({
    organization_id: null,
    organization: '',
    organization_address: '',
    org_from: '',
    org_to: '',
    org_hours: 0,
    org_position: ''
  })
}

const removeOrganization = (index) => {
  organizationsList.value.splice(index, 1)
}

// Skills management
const addSkill = () => {
  skillsList.value.push({
    skill_id: null,
    skill: ''
  })
}

const removeSkill = (index) => {
  skillsList.value.splice(index, 1)
}

// Recognitions management
const addRecognition = () => {
  recognitionsList.value.push({
    recognation_id: null,
    recognation: ''
  })
}

const removeRecognition = (index) => {
  recognitionsList.value.splice(index, 1)
}

// Membership management
const addMembership = () => {
  membershipList.value.push({
    membership_id: null,
    membership: ''
  })
}

const removeMembership = (index) => {
  membershipList.value.splice(index, 1)
}

// References management
const addReference = () => {
  referencesList.value.push({
    reference_id: null,
    ref_name: '',
    ref_address: '',
    ref_occupation: '',
    ref_contact_no: '',
    ref_email: ''
  })
}

const removeReference = (index) => {
  referencesList.value.splice(index, 1)
}

// Dependents management
const addDependent = () => {
  dependentsList.value.push({
    dependent_id: null,
    dep_name: '',
    dep_relationship: '',
    dep_course: ''
  })
}

const removeDependent = (index) => {
  dependentsList.value.splice(index, 1)
}

// Documents management
const filteredDocuments = computed(() => {
  let docs = documentsList.value || []
  
  // Apply search filter
  if (documentFilters.search) {
    const searchLower = documentFilters.search.toLowerCase()
    docs = docs.filter(doc => 
      (doc.document_type && doc.document_type.toLowerCase().includes(searchLower)) ||
      (doc.description && doc.description.toLowerCase().includes(searchLower)) ||
      (doc.attachment_name && doc.attachment_name.toLowerCase().includes(searchLower))
    )
  }
  
  return docs
})

const beforeDocumentUpload = (file) => {
  const allowedTypes = [
    'image/jpeg', 'image/jpg', 'image/png',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/pdf'
  ]
  const allowedExtensions = ['.jpg', '.jpeg', '.png', '.xls', '.xlsx', '.doc', '.docx', '.pdf']
  
  const fileExtension = '.' + file.name.split('.').pop().toLowerCase()
  const isValidType = allowedTypes.includes(file.type) || allowedExtensions.includes(fileExtension)
  const isLt100M = file.size / 1024 / 1024 < 100
  
  if (!isValidType) {
    ElMessage.error('File type not allowed. Accepted: .jpg, .jpeg, .png, .xls, .xlsx, .doc, .docx, .pdf')
    return false
  }
  if (!isLt100M) {
    ElMessage.error('File size must be less than 100MB')
    return false
  }
  return true
}

const handleDocumentExceed = () => {
  ElMessage.warning('Maximum 5 files can be uploaded at once')
}

const handleDocumentFileChange = (file, fileList) => {
  documentForm.fileList = fileList
}

const handleDocumentFileRemove = (file, fileList) => {
  documentForm.fileList = fileList
}

const resetDocumentForm = () => {
  documentForm.document_type_id = null
  documentForm.description = ''
  documentForm.fileList = []
  documentForm.editingDocumentId = null
  if (documentUploadRef.value) {
    documentUploadRef.value.clearFiles()
  }
}

const toggleDocumentForm = () => {
  if (documentFormVisible.value) {
    resetDocumentForm()
    documentFormVisible.value = false
  } else {
    resetDocumentForm()
    documentFormVisible.value = true
  }
}

const handleDocumentUpload = async () => {
  if (!documentForm.document_type_id) {
    ElMessage.error('Please select a document type')
    return
  }
  if (!documentForm.description || documentForm.description.trim() === '') {
    ElMessage.error('Please enter a description')
    return
  }
  if (documentForm.fileList.length === 0) {
    ElMessage.error('Please select at least one file')
    return
  }
  
  if (!props.employeeData?.id) {
    ElMessage.warning('Please save the employee first before uploading documents')
    return
  }
  
  uploadingDocument.value = true
  try {
    const uploadFormData = new FormData()
    uploadFormData.append('employee_no', props.employeeData.employee_no || formData.employee_no)
    uploadFormData.append('employee_id', props.employeeData.id)
    uploadFormData.append('description', documentForm.description)
    uploadFormData.append('document_type_id', documentForm.document_type_id)
    
    // Append files
    documentForm.fileList.forEach((file) => {
      uploadFormData.append('attachments[]', file.raw || file)
    })
    
    const employeeDocumentId = documentForm.editingDocumentId || 0
    await api.post(`/employee-documents/${employeeDocumentId}`, uploadFormData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    ElMessage.success('Documents uploaded successfully')
    resetDocumentForm()
    await loadDocuments()
  } catch (error) {
    console.error('Upload error:', error)
    ElMessage.error(error.response?.data?.message || 'Failed to upload documents')
  } finally {
    uploadingDocument.value = false
  }
}

const loadDocuments = async (employeeId = null) => {
  const id = employeeId || props.employeeData?.id
  
  if (!id) {
    // If no employee ID, set empty list (no fallback to old data)
    documentsList.value = []
    return
  }
  
  try {
    let url = `/employee-documents/${id}/load`
    if (documentFilters.dateFrom && documentFilters.dateTo) {
      url = `/employee-documents/${id}/load-range/${documentFilters.dateFrom}/${documentFilters.dateTo}`
    }
    
    const response = await api.get(url)
    // Handle API response format: { success: true, data: [...], message: "..." }
    let documents = []
    if (response.data?.success && response.data?.data) {
      documents = Array.isArray(response.data.data) ? response.data.data : []
    } else if (response.data?.data && Array.isArray(response.data.data)) {
      documents = response.data.data
    } else if (response.data?.documents && Array.isArray(response.data.documents)) {
      documents = response.data.documents
    } else if (Array.isArray(response.data)) {
      documents = response.data
    }
    
    // Ensure all documents have employee_document_id
    documentsList.value = documents.map(doc => {
      // The backend returns employee_document_id as the primary key
      const docId = doc.employee_document_id || doc.id || doc.document_id || 0
      return {
        ...doc,
        employee_document_id: docId,
        // Also set id for consistency
        id: docId
      }
    })
    
    // Debug log to check document structure
    if (documentsList.value.length > 0) {
      console.log('Loaded documents - first doc:', documentsList.value[0])
      console.log('Document IDs:', documentsList.value.map(d => ({ 
        employee_document_id: d.employee_document_id, 
        id: d.id,
        name: d.attachment_name 
      })))
    }
  } catch (error) {
    console.error('Load documents error:', error)
    // Always set empty list on error (no fallback to old data)
    documentsList.value = []
  }
}

const filterDocuments = async () => {
  if (documentFilters.dateFrom && documentFilters.dateTo) {
    await loadDocuments()
  } else {
    ElMessage.warning('Please select both From and To dates')
  }
}

const handlePreviewDocument = async (row) => {
  console.log('=== Preview Document Debug ===')
  console.log('Row data:', row)
  console.log('employee_document_id:', row.employee_document_id)
  console.log('id:', row.id)
  console.log('document_id:', row.document_id)
  
  // Get the document ID - check multiple possible field names
  const documentId = row.employee_document_id || row.id || row.document_id
  
  console.log('Final documentId:', documentId)
  
  if (!documentId || documentId === 0) {
    ElMessage.error('Invalid document ID. Please reload the documents list.')
    console.error('Invalid document - full row data:', JSON.stringify(row, null, 2))
    return
  }

  previewingDocument.value = documentId
  previewDocumentRow.value = row
  revokePreviewObjectUrlIfAny()
  previewUrl.value = null
  showPreviewModal.value = true

  // If file_content is available (attachments DB), use it directly for preview
  if (row.file_content) {
    try {
      const fileName = row.attachment_name || row.name || 'document'
      const extension = (fileName.split('.').pop() || '').toLowerCase()
      const mimeType =
        row.file_type ||
        (extension === 'pdf'
          ? 'application/pdf'
          : ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(extension)
          ? `image/${extension === 'jpg' ? 'jpeg' : extension}`
          : 'application/octet-stream')

      const byteCharacters = atob(row.file_content)
      const byteNumbers = new Array(byteCharacters.length)
      for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i)
      }
      const byteArray = new Uint8Array(byteNumbers)
      const blob = new Blob([byteArray], { type: mimeType })
      const url = URL.createObjectURL(blob)

      previewUrl.value = url

      if (extension === 'pdf') {
        previewFileType.value = 'pdf'
      } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(extension)) {
        previewFileType.value = 'image'
      } else {
        previewFileType.value = 'other'
      }
    } catch (error) {
      console.error('Error creating preview from file_content:', error)
      ElMessage.error('Failed to prepare document preview from stored content.')
      showPreviewModal.value = false
    } finally {
      previewingDocument.value = null
    }
    return
  }
  
  // Fallback to legacy preview endpoint if file_content is not available
  try {
    const response = await api.get(`/employee-documents/${documentId}/preview`)
    
    // Backend returns JSON with preview_url
    let url = null
    
    if (response.data?.success && response.data?.data?.preview_url) {
      url = response.data.data.preview_url
    } else if (response.data?.data?.preview_url) {
      url = response.data.data.preview_url
    }
    
    if (url) {
      // Determine file type from extension
      const fileName = row.attachment_name || response.data?.data?.file_name || ''
      const extension = fileName.split('.').pop()?.toLowerCase() || ''

      if (['pdf'].includes(extension)) {
        previewFileType.value = 'pdf'
      } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(extension)) {
        previewFileType.value = 'image'
      } else {
        previewFileType.value = 'other'
      }

      const normalizedUrl = normalizeServerUrl(url)

      // Guard: for PDF/PNG/JPG previews, fetch blob and force MIME + strip BOM,
      // so <iframe>/<img> renders even when server Content-Type is wrong.
      if (['pdf', 'png', 'jpg', 'jpeg'].includes(extension)) {
        const blobResp = await api.get(normalizedUrl, { responseType: 'blob' })
        previewUrl.value = await createGuardedPreviewObjectUrl(blobResp, extension)
      } else {
        previewUrl.value = normalizedUrl
      }
    } else {
      throw new Error('Preview URL not available in response')
    }
  } catch (error) {
    console.error('=== Preview Error Details ===')
    console.error('Error object:', error)
    console.error('Response status:', error.response?.status)
    console.error('Response data:', error.response?.data)
    console.error('Full error:', JSON.stringify(error.response?.data, null, 2))
    
    const errorMessage = error.response?.data?.message || error.message || 'Failed to preview document'
    console.error('Error message:', errorMessage)
    
    ElMessage.error(errorMessage)
    
    // If the error is about file already existing, try to construct the URL directly
    if (errorMessage.includes('already exists') || errorMessage.includes('File already exists')) {
      // Try to construct preview URL from document path
      const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
      const storageBase = baseUrl.replace('/api', '')
      // The file should be accessible at storage path
      const constructedUrl = normalizeServerUrl(`${storageBase}/storage${row.path || ''}/file/${row.attachment_name || ''}`)
      console.log('Attempting constructed URL:', constructedUrl)

      // Determine file type
      const extension = (row.attachment_name || '').split('.').pop()?.toLowerCase() || ''
      if (['pdf'].includes(extension)) {
        previewFileType.value = 'pdf'
      } else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(extension)) {
        previewFileType.value = 'image'
      } else {
        previewFileType.value = 'other'
      }

      if (['pdf', 'png', 'jpg', 'jpeg'].includes(extension)) {
        const blobResp = await api.get(constructedUrl, { responseType: 'blob' })
        previewUrl.value = await createGuardedPreviewObjectUrl(blobResp, extension)
      } else {
        previewUrl.value = constructedUrl
      }
    } else {
      showPreviewModal.value = false
    }
  } finally {
    previewingDocument.value = null
  }
}

const downloadFromPreview = () => {
  if (!previewDocumentRow.value) return

  if (previewDocumentRow.value._previewSource === 'contract') {
    const row = previewDocumentRow.value
    if (row.pendingFile) {
      downloadPendingContractFile(row)
    } else {
      downloadContractFile(row)
    }
    return
  }

  handleDownloadDocument(previewDocumentRow.value)
}

const handleDownloadDocument = async (row) => {
  // Get the document ID - check multiple possible field names
  const documentId = row.employee_document_id || row.id || row.document_id
  
  if (!documentId || documentId === 0) {
    ElMessage.error('Invalid document ID. Please refresh and try again.')
    console.error('Document row data:', row)
    return
  }
  
  downloadingDocument.value = documentId
  try {
    // If we already have file_content (attachments DB), use it directly
    if (row.file_content) {
      const fileName = row.attachment_name || row.name || 'document'
      const extension = (fileName.split('.').pop() || '').toLowerCase()
      const mimeType =
        row.file_type ||
        (extension === 'pdf'
          ? 'application/pdf'
          : ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(extension)
          ? `image/${extension === 'jpg' ? 'jpeg' : extension}`
          : 'application/octet-stream')

      const byteCharacters = atob(row.file_content)
      const byteNumbers = new Array(byteCharacters.length)
      for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i)
      }
      const byteArray = new Uint8Array(byteNumbers)
      const blob = new Blob([byteArray], { type: mimeType })
      const url = window.URL.createObjectURL(blob)

      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', fileName)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      ElMessage.success('Document downloaded successfully')
      return
    }

    // Fallback to existing download API if file_content is not available
    // First, get the download info from the API
    const response = await api.get(`/employee-documents/${documentId}/download`)
    
    // Backend returns JSON with download_url or file info
    let downloadUrl = null
    let fileName = row.attachment_name || 'document'
    
    if (response.data?.success && response.data?.data) {
      if (response.data.data.download_url) {
        downloadUrl = response.data.data.download_url
      } else if (response.data.data.file_path) {
        // If we have a file path, we need to fetch it
        downloadUrl = response.data.data.file_path
      }
      if (response.data.data.file_name) {
        fileName = response.data.data.file_name
      }
    } else if (response.data?.data?.download_url) {
      downloadUrl = response.data.data.download_url
    }
    
    if (downloadUrl) {
      // Try to use the download URL directly
      if (downloadUrl.startsWith('http')) {
        // Full URL - open directly
        const link = document.createElement('a')
        link.href = downloadUrl
        link.setAttribute('download', fileName)
        link.setAttribute('target', '_blank')
        document.body.appendChild(link)
        link.click()
        link.remove()
        ElMessage.success('Document download started')
      } else {
        // Relative URL - construct full URL
        const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000'
        const fullUrl = downloadUrl.startsWith('/') 
          ? `${baseUrl.replace('/api', '')}${downloadUrl}`
          : `${baseUrl.replace('/api', '')}/${downloadUrl}`
        
        const link = document.createElement('a')
        link.href = fullUrl
        link.setAttribute('download', fileName)
        link.setAttribute('target', '_blank')
        document.body.appendChild(link)
        link.click()
        link.remove()
        ElMessage.success('Document download started')
      }
    } else {
      // Fallback: try to get the file as blob
      const fileResponse = await api.get(`/employee-documents/${documentId}/download`, {
        responseType: 'blob'
      })
      
      if (fileResponse.data instanceof Blob) {
        const url = window.URL.createObjectURL(fileResponse.data)
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', fileName)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        ElMessage.success('Document downloaded successfully')
      } else {
        throw new Error('Download URL not available')
      }
    }
  } catch (error) {
    console.error('Download error:', error)
    ElMessage.error(error.response?.data?.message || 'Failed to download document')
  } finally {
    downloadingDocument.value = null
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' })
  } catch (error) {
    return dateString
  }
}

const getEmploymentTypeId = (value, employmentTypes) => {
  if (value === null || value === undefined || value === '') return null
  if (!isNaN(Number(value))) {
    return Number(value)
  }
  const match = employmentTypes.find(type => type.name?.toLowerCase() === String(value).toLowerCase())
  return match ? Number(match.id) : null
}

const getEmploymentTypeName = (id, employmentTypes) => {
  if (id === null || id === undefined || id === '') return ''
  if (typeof id === 'string' && isNaN(Number(id))) {
    return id
  }
  const match = employmentTypes.find(type => Number(type.id) === Number(id))
  return match ? match.name : String(id)
}

const populateForm = (data) => {
  // Set flag to prevent watchers from clearing address fields during population
  isPopulatingForm.value = true
  
  const employmentTypes = props.formOptions?.employment_types || []

  // ID fields that should be converted to numbers
  const idFields = [
    'name_prefix_id', 'name_suffix_id', 'gender_id', 'blood_type_id',
    'civil_status_id', 'citizenship_id', 'religion_id', 'company_id',
    'department_id', 'division_id', 'position_id', 'employment_type_id', 'section_id',
    'plantilla_id', 'salary_grade_id', 'salary_step_id', 'payroll_interval_id',
    'father_name_prefix_id', 'father_name_suffix_id',
    'mother_name_prefix_id', 'mother_name_suffix_id',
    'spouse_name_prefix_id', 'spouse_name_suffix_id'
  ]
  
  // Map backend fields to frontend fields
  const fieldMapping = {
    // Backend ra_* (residential address) maps to frontend ca_* (current address)
    'ra_region': 'ca_region',
    'ra_province': 'ca_province',
    'ra_city': 'ca_city',
    'ra_barangay': 'ca_barangay',
    'ra_postal_id': 'ca_zip',
    'ra_house_no': 'ca_house_no',
    'ra_street': 'ca_street',
    'ra_village': 'ca_village',
    // Backend pa_* (permanent address) maps to frontend pa_*
    'pa_region': 'pa_region',
    'pa_province': 'pa_province',
    'pa_city': 'pa_city',
    'pa_barangay': 'pa_barangay',
    'pa_postal_id': 'pa_zip',
    'pa_house_no': 'pa_house_no',
    'pa_street': 'pa_street',
    'pa_village': 'pa_village'
  }
  
  // First, populate direct field matches
  Object.keys(formData).forEach(key => {
    if (data[key] !== undefined && data[key] !== null) {
      if (idFields.includes(key)) {
        // Convert ID fields to numbers (including 0, null, empty string)
        // Handle plantilla_id and other ID fields that can be 0
        if (data[key] === '' || data[key] === null) {
          formData[key] = 0
        } else {
          const numValue = Number(data[key])
          formData[key] = isNaN(numValue) ? 0 : numValue
        }
      } else if (typeof formData[key] === 'boolean') {
        // Handle boolean fields explicitly (is_hold, is_plantilla, is_teaching, etc.)
        // Convert various truthy/falsy values to proper boolean
        if (data[key] === true || data[key] === 1 || data[key] === '1' || data[key] === 'true') {
          formData[key] = true
        } else if (data[key] === false || data[key] === 0 || data[key] === '0' || data[key] === 'false' || data[key] === null || data[key] === '') {
          formData[key] = false
        } else {
          // Default to false for any other value
          formData[key] = false
        }
      } else if (typeof formData[key] === 'number') {
        // Handle numeric fields
        formData[key] = data[key] === '' ? 0 : Number(data[key])
      } else {
        // Handle string and other fields
      formData[key] = data[key]
      }
    }
  })
  
  // Then, apply field mappings for address fields (skip region/province/city/barangay - handled in normalization)
  Object.entries(fieldMapping).forEach(([backendKey, frontendKey]) => {
    // Skip address cascade fields - they'll be set in normalization section
    if (backendKey.endsWith('_region') || backendKey.endsWith('_province') || 
        backendKey.endsWith('_city') || backendKey.endsWith('_barangay')) {
      return
    }
    
    if (data[backendKey] !== undefined && data[backendKey] !== null) {
      const rawValue = data[backendKey]
      // Convert postal IDs to strings for ca_zip/pa_zip
      // Note: postal_id might be 0 (not set) or might contain the zipcode value directly
      if (backendKey.includes('postal_id')) {
        // If postal_id is 0, null, or empty, set zipcode to empty string
        // Otherwise, convert to string (treating it as the zipcode value itself)
        if (rawValue === 0 || rawValue === null || rawValue === '' || rawValue === '0') {
          formData[frontendKey] = ''
        } else {
          // Convert to string and trim (in case it's stored as zipcode string or number)
          formData[frontendKey] = String(rawValue).trim()
        }
      } else {
        formData[frontendKey] = rawValue
      }
    }
  })
  
  // Also check if backend returns zipcode directly (in case it's stored separately)
  // Some backends might return both postal_id and a separate zipcode field
  if (data.ca_zip !== undefined && data.ca_zip !== null && data.ca_zip !== '') {
    formData.ca_zip = String(data.ca_zip).trim()
  }
  if (data.pa_zip !== undefined && data.pa_zip !== null && data.pa_zip !== '') {
    formData.pa_zip = String(data.pa_zip).trim()
  }

  // Extra normalization: if backend stored region/province/city/barangay as names
  // instead of codes, best-effort map them to codes using addressData.
  const normalizeAddressCode = (value, list, codeKey, descKey) => {
    if (!value || !list || !Array.isArray(list)) return ''
    const val = String(value).trim()
    // First, match by exact code
    const byCode = list.find(item => String(item[codeKey] || '').trim() === val)
    if (byCode) return String(byCode[codeKey]).trim()
    // Then, match by description (case-insensitive)
    const lowerVal = val.toLowerCase()
    const byDesc = list.find(
      item => String(item[descKey] || '').trim().toLowerCase() === lowerVal
    )
    if (byDesc) return String(byDesc[codeKey]).trim()
    return val
  }

  // Store normalized address values first (before setting to avoid watcher interference)
  const normalizedAddresses = {
    ca_region: data.ra_region ? normalizeAddressCode(data.ra_region, addressData.regions, 'regCode', 'regDesc') : '',
    ca_province: data.ra_province ? normalizeAddressCode(data.ra_province, addressData.provinces, 'provCode', 'provDesc') : '',
    ca_city: data.ra_city ? normalizeAddressCode(data.ra_city, addressData.cities, 'citymunCode', 'citymunDesc') : '',
    ca_barangay: data.ra_barangay ? normalizeAddressCode(data.ra_barangay, addressData.barangays, 'brgyCode', 'brgyDesc') : '',
    pa_region: data.pa_region ? normalizeAddressCode(data.pa_region, addressData.regions, 'regCode', 'regDesc') : '',
    pa_province: data.pa_province ? normalizeAddressCode(data.pa_province, addressData.provinces, 'provCode', 'provDesc') : '',
    pa_city: data.pa_city ? normalizeAddressCode(data.pa_city, addressData.cities, 'citymunCode', 'citymunDesc') : '',
    pa_barangay: data.pa_barangay ? normalizeAddressCode(data.pa_barangay, addressData.barangays, 'brgyCode', 'brgyDesc') : ''
  }

  // Use nextTick to set address fields in correct order after watchers have run
  // This ensures computed properties (currentProvinces, currentCities, etc.) update before we set dependent fields
  nextTick(() => {
    // Set current address fields in cascade order
    if (normalizedAddresses.ca_region) {
      formData.ca_region = normalizedAddresses.ca_region
      nextTick(() => {
        if (normalizedAddresses.ca_province) {
          // Verify province exists in filtered list before setting
          const provinceExists = currentProvinces.value.some(p => String(p.provCode).trim() === normalizedAddresses.ca_province)
          if (provinceExists) {
            formData.ca_province = normalizedAddresses.ca_province
            nextTick(() => {
              if (normalizedAddresses.ca_city) {
                // Verify city exists in filtered list before setting
                const cityExists = currentCities.value.some(c => String(c.citymunCode).trim() === normalizedAddresses.ca_city)
                if (cityExists) {
                  formData.ca_city = normalizedAddresses.ca_city
                  nextTick(() => {
                    if (normalizedAddresses.ca_barangay) {
                      // Verify barangay exists in filtered list before setting
                      const barangayExists = currentBarangays.value.some(b => String(b.brgyCode).trim() === normalizedAddresses.ca_barangay)
                      if (barangayExists) {
                        formData.ca_barangay = normalizedAddresses.ca_barangay
                      }
                    }
                  })
                }
              }
            })
          }
        }
      })
    }

    // Set permanent address fields in cascade order
    if (normalizedAddresses.pa_region) {
      formData.pa_region = normalizedAddresses.pa_region
      nextTick(() => {
        if (normalizedAddresses.pa_province) {
          // Verify province exists in filtered list before setting
          const provinceExists = permanentProvinces.value.some(p => String(p.provCode).trim() === normalizedAddresses.pa_province)
          if (provinceExists) {
            formData.pa_province = normalizedAddresses.pa_province
            nextTick(() => {
              if (normalizedAddresses.pa_city) {
                // Verify city exists in filtered list before setting
                const cityExists = permanentCities.value.some(c => String(c.citymunCode).trim() === normalizedAddresses.pa_city)
                if (cityExists) {
                  formData.pa_city = normalizedAddresses.pa_city
                  nextTick(() => {
                    if (normalizedAddresses.pa_barangay) {
                      // Verify barangay exists in filtered list before setting
                      const barangayExists = permanentBarangays.value.some(b => String(b.brgyCode).trim() === normalizedAddresses.pa_barangay)
                      if (barangayExists) {
                        formData.pa_barangay = normalizedAddresses.pa_barangay
                      }
                    }
                  })
                }
              }
            })
          }
        }
      })
    }

    // Re-enable watchers after population is complete
    nextTick(() => {
      isPopulatingForm.value = false
    })
  })

  // Populate salary grade and step based on selected plantilla (if provided)
  const plantillaSelected = props.formOptions?.plantillas_selected
  if (Array.isArray(plantillaSelected) && plantillaSelected.length > 0) {
    const selected = plantillaSelected[0]
    if (selected) {
      formData.salary_grade_id = selected.salary_grade_id ? Number(selected.salary_grade_id) : 0
      formData.salary_step_id = selected.salary_step_id ? Number(selected.salary_step_id) : 0
    }
  }
  
  // Load children data if available
  if (props.relatedData?.children && Array.isArray(props.relatedData.children)) {
    childrenList.value = props.relatedData.children.map(child => ({
      children_id: child.children_id || child.id,
      first_name: child.child_name || child.name || '',
      middle_name: child.child_middlename || '',
      last_name: child.child_lastname || '',
      birthdate: child.child_birthdate || '',
      gender_id: child.child_gender_id || 0
    }))
  }
  
  // Load education data if available
  if (props.relatedData?.educations && Array.isArray(props.relatedData.educations)) {
    educationList.value = props.relatedData.educations.map(edu => ({
      education_id: edu.education_id || edu.id || null,
      academic_level_id: edu.academic_level_id ? Number(edu.academic_level_id) : 0,
      school_name: edu.school_name || '',
      program: edu.program || '',
      from: edu.from || '',
      to: edu.to || '',
      graduated_year: edu.graduated_year || '',
      units_earned: edu.units_earned || '',
      honors: edu.honors || ''
    }))
  }

  // Load service record data if available
  if (props.relatedData?.service_records && Array.isArray(props.relatedData.service_records)) {
    serviceRecordList.value = props.relatedData.service_records.map(record => ({
      service_record_id: record.service_record_id || record.id || null,
      start_date: record.start_date || '',
      end_date: record.end_date || '',
      designation: record.designation || '',
      employment_type: getEmploymentTypeId(record.employment_type, employmentTypes),
      place_of_assignment: record.place_of_assignment || '',
      leave_without_pay: Number(record.leave_without_pay || 0),
      separation_date: record.separation_date || '',
      cause: record.cause || ''
    }))
  } else {
    serviceRecordList.value = []
  }

  // Load contract data if available (COS employees)
  if (props.relatedData?.cos_contracts && Array.isArray(props.relatedData.cos_contracts) && props.relatedData.cos_contracts.length > 0) {
    contractList.value = props.relatedData.cos_contracts.map(contract => ({
      id: contract.id || null,
      Start_date: contract.Start_date || '',
      End_date: contract.End_date || '',
      salary: contract.salary != null ? Number(contract.salary) : 0,
      salary_grade_id: contract.salary_grade_id != null ? Number(contract.salary_grade_id) : 0,
      salary_step_id: contract.salary_step_id != null ? Number(contract.salary_step_id) : 0,
      is_active: contract.is_active === true || contract.is_active === 1 || contract.is_active === '1',
      contract_file: contract.contract_file || null,
      pendingFile: null,
      removeFile: false
    }))
  } else {
    // If no contracts exist but employee has date_hired, use it as default start date
    contractList.value = []
    if (props.employeeData?.date_hired && formData.employment_type_id === 2) {
      contractList.value.push({
        id: null,
        Start_date: props.employeeData.date_hired || '',
        End_date: '',
        salary: Number(props.employeeData?.salary) || 0,
        salary_grade_id: Number(props.employeeData?.salary_grade_id) || 0,
        salary_step_id: Number(props.employeeData?.salary_step_id) || 0,
        is_active: true,
        contract_file: null,
        pendingFile: null,
        removeFile: false
      })
    }
  }

  syncWorkInfoFromActiveContract()
  syncCosPayrollHoldPreview()

  // Load work experience data if available
  if (props.relatedData?.employments && Array.isArray(props.relatedData.employments)) {
    workExperienceList.value = props.relatedData.employments.map(emp => ({
      employment_record_id: emp.employment_record_id || emp.id || null,
      work_start_date: emp.work_start_date || '',
      work_end_date: emp.work_end_date || '',
      work_company: emp.work_company || '',
      position_we: emp.position_we || emp.position || '',
      monthly_salary: Number(emp.monthly_salary || 0),
      salary_grade_step: emp.salary_grade_step || '',
      status_of_appointment: emp.status_of_appointment || '',
      government_service_id: Number(emp.government_service_id) === 1 ? 1 : 0,
      work_specialization_id: emp.work_specialization_id || 0,
      is_present: emp.is_present ? true : false
    }))
  } else {
    workExperienceList.value = []
  }
  
  // Load examinations data if available
  if (props.relatedData?.examinations && Array.isArray(props.relatedData.examinations)) {
    examinationsList.value = props.relatedData.examinations.map(exam => ({
      examination_id: exam.examination_id || exam.id || null,
      eligibility_id: exam.eligibility_id || 0,
      eligibility_description: exam.eligibility_description || '',
      exam_rating: exam.exam_rating || 0,
      exam_date: exam.exam_date || '',
      place_of_exam: exam.place_of_exam || '',
      license_number: exam.license_number || '',
      date_released: exam.date_released || ''
    }))
  }
  
  // Load trainings data if available
  if (props.relatedData?.trainings && Array.isArray(props.relatedData.trainings)) {
    trainingsList.value = props.relatedData.trainings.map(training => ({
      training_id: training.training_id || training.id || null,
      training: training.training || '',
      training_from: training.training_from || '',
      training_to: training.training_to || '',
      hours: training.hours || 0,
      sponsored_by: training.sponsored_by || '',
      learning_id: training.learning_id || 0
    }))
  }
  
  // Load organizations (voluntary work) data if available
  if (props.relatedData?.organizations && Array.isArray(props.relatedData.organizations)) {
    organizationsList.value = props.relatedData.organizations.map(org => ({
      organization_id: org.organization_id || org.id || null,
      organization: org.organization || '',
      organization_address: org.organization_address || '',
      org_from: org.org_from || '',
      org_to: org.org_to || '',
      org_hours: org.org_hours || 0,
      org_position: org.org_position || ''
    }))
  }
  
  // Load skills data if available
  if (props.relatedData?.skills && Array.isArray(props.relatedData.skills)) {
    skillsList.value = props.relatedData.skills.map(skill => ({
      skill_id: skill.skill_id || skill.id || null,
      skill: skill.skill || ''
    }))
  }
  
  // Load recognitions data if available
  if (props.relatedData?.recognitions && Array.isArray(props.relatedData.recognitions)) {
    recognitionsList.value = props.relatedData.recognitions.map(recog => ({
      recognation_id: recog.recognation_id || recog.id || null,
      recognation: recog.recognation || recog.recognition_name || ''
    }))
  }

  // Load memberships data if available
  if (props.relatedData?.memberships && Array.isArray(props.relatedData.memberships)) {
    membershipList.value = props.relatedData.memberships.map(mem => ({
      membership_id: mem.membership_id || mem.id || null,
      membership: mem.membership || ''
    }))
  } else {
    membershipList.value = []
  }
  
  // Load references data if available
  if (props.relatedData?.references && Array.isArray(props.relatedData.references)) {
    referencesList.value = props.relatedData.references.map(ref => ({
      reference_id: ref.reference_id || ref.id || null,
      ref_name: ref.ref_name || '',
      ref_address: ref.ref_address || '',
      ref_occupation: ref.ref_occupation || '',
      ref_contact_no: ref.ref_contact_no || '',
      ref_email: ref.ref_email || ''
    }))
  }
  
  // Load dependents data if available
  if (props.relatedData?.dependents && Array.isArray(props.relatedData.dependents)) {
    dependentsList.value = props.relatedData.dependents.map(dep => ({
      dependent_id: dep.id || dep.dependent_id || null,
      dep_name: dep.name || '',
      dep_relationship: dep.relationship || '',
      dep_course: dep.course || ''
    }))
  }
  
  // Load documents if editing - use nextTick to ensure component is fully rendered
  // Always load from attachments DB via API (no fallback to relatedData)
  if (isEdit.value && props.employeeData?.id) {
    nextTick(() => {
      loadDocuments()
    })
  } else {
    // Initialize empty list if no employee ID
    documentsList.value = []
  }
}

const handleClose = () => {
  visible.value = false
  resetForm()
  activeTab.value = 'basic'
  if (photoPreview.value?.startsWith('blob:')) {
    URL.revokeObjectURL(photoPreview.value)
  }
  photoPreview.value = ''
  avatarFile.value = null
  clearLocalStorage()
}

const handleSave = async () => {
  try {
    await formRef.value.validate()
    
    // Format children data for backend (arrays as expected by backend)
    const payload = { ...formData }
    
    // Format birthdate to YYYY-MM-DD format if it's a Date object
    if (payload.birthdate instanceof Date) {
      payload.birthdate = payload.birthdate.toISOString().slice(0, 10)
    } else if (payload.birthdate && typeof payload.birthdate === 'string') {
      // If it's already a string, ensure it's in the correct format
      const date = new Date(payload.birthdate)
      if (!isNaN(date.getTime())) {
        payload.birthdate = date.toISOString().slice(0, 10)
      }
    } else if (!payload.birthdate || payload.birthdate === '') {
      payload.birthdate = null
    }
    
    // Ensure plantilla_id is properly set as a number
    // Convert to number explicitly to avoid string/number type issues
    if (payload.plantilla_id !== null && payload.plantilla_id !== undefined && payload.plantilla_id !== '') {
      payload.plantilla_id = Number(payload.plantilla_id) || 0
    } else {
      payload.plantilla_id = 0
    }
    
    // Ensure division_id is properly set as a number
    if (payload.division_id !== null && payload.division_id !== undefined && payload.division_id !== '') {
      payload.division_id = Number(payload.division_id) || 0
    } else {
      payload.division_id = 0
    }
    
    const employmentTypes = props.formOptions?.employment_types || []
    
    // Format children data into arrays
    if (childrenList.value.length > 0) {
      payload.child_name = childrenList.value.map(child => child.first_name || '')
      payload.child_middlename = childrenList.value.map(child => child.middle_name || '')
      payload.child_lastname = childrenList.value.map(child => child.last_name || '')
      payload.child_birthdate = childrenList.value.map(child => {
        if (child.birthdate instanceof Date) {
          return child.birthdate.toISOString().slice(0, 10)
        } else if (child.birthdate && typeof child.birthdate === 'string') {
          const date = new Date(child.birthdate)
          if (!isNaN(date.getTime())) {
            return date.toISOString().slice(0, 10)
          }
        }
        return child.birthdate || ''
      })
      payload.children_id = childrenList.value.map(child => child.children_id || null)
      payload.child_gender_id = childrenList.value.map(child => child.gender_id || 0)
    } else {
      // Send empty arrays if no children
      payload.child_name = []
      payload.child_middlename = []
      payload.child_lastname = []
      payload.child_birthdate = []
      payload.children_id = []
      payload.child_gender_id = []
    }
    
    // Format education data into arrays
    if (educationList.value.length > 0) {
      payload.education_id = educationList.value.map(edu => edu.education_id || null)
      payload.academic_level_id = educationList.value.map(edu => edu.academic_level_id || 0)
      payload.school_name = educationList.value.map(edu => edu.school_name || '')
      payload.program = educationList.value.map(edu => edu.program || '')
      payload.from = educationList.value.map(edu => edu.from || '')
      payload.to = educationList.value.map(edu => edu.to || '')
      payload.graduated_year = educationList.value.map(edu => edu.graduated_year || '')
      payload.units_earned = educationList.value.map(edu => edu.units_earned || '')
      payload.honors = educationList.value.map(edu => edu.honors || '')
    } else {
      // Send empty arrays if no education records
      payload.education_id = []
      payload.academic_level_id = []
      payload.school_name = []
      payload.program = []
      payload.from = []
      payload.to = []
      payload.graduated_year = []
      payload.units_earned = []
      payload.honors = []
    }
    
    // Format service record data into arrays
    if (serviceRecordList.value.length > 0) {
      payload.service_record_id = serviceRecordList.value.map(record => record.service_record_id || null)
      payload.start_date = serviceRecordList.value.map(record => record.start_date || '')
      payload.end_date = serviceRecordList.value.map(record => record.end_date || '')
      payload.designation = serviceRecordList.value.map(record => record.designation || '')
      payload.employment_type = serviceRecordList.value.map(record => getEmploymentTypeName(record.employment_type, employmentTypes))
      payload.place_of_assignment = serviceRecordList.value.map(record => record.place_of_assignment || '')
      payload.leave_without_pay = serviceRecordList.value.map(record => record.leave_without_pay || 0)
      payload.separation_date = serviceRecordList.value.map(record => record.separation_date || '')
      payload.cause = serviceRecordList.value.map(record => record.cause || '')
    } else {
      payload.service_record_id = []
      payload.start_date = []
      payload.end_date = []
      payload.designation = []
      payload.employment_type = []
      payload.place_of_assignment = []
      payload.leave_without_pay = []
      payload.separation_date = []
      payload.cause = []
    }

    // Format work experience data into arrays
    if (workExperienceList.value.length > 0) {
      payload.employment_record_id = workExperienceList.value.map(record => record.employment_record_id || null)
      payload.work_start_date = workExperienceList.value.map(record => record.work_start_date || '')
      payload.work_end_date = workExperienceList.value.map(record => record.work_end_date || '')
      payload.work_company = workExperienceList.value.map(record => record.work_company || '')
      payload.monthly_salary = workExperienceList.value.map(record => record.monthly_salary || 0)
      payload.salary_grade_step = workExperienceList.value.map(record => record.salary_grade_step || '')
      payload.status_of_appointment = workExperienceList.value.map(record => record.status_of_appointment || '')
      payload.position_we = workExperienceList.value.map(record => record.position_we || '')
      payload.government_service_id = workExperienceList.value.map(record => Number(record.government_service_id) === 1 ? 1 : 0)
      payload.work_specialization_id = workExperienceList.value.map(record => record.work_specialization_id || 0)
      const presentRecord = workExperienceList.value.find(record => record.is_present)
      if (presentRecord) {
        payload.is_present = [presentRecord.employment_record_id || 0]
      } else {
        delete payload.is_present
      }
    } else {
      payload.employment_record_id = []
      payload.work_start_date = []
      payload.work_end_date = []
      payload.work_company = []
      payload.monthly_salary = []
      payload.salary_grade_step = []
      payload.status_of_appointment = []
      payload.position_we = []
      payload.government_service_id = []
      payload.work_specialization_id = []
      delete payload.is_present
    }
    
    // Format examinations data into arrays
    if (examinationsList.value.length > 0) {
      payload.examination_id = examinationsList.value.map(exam => exam.examination_id || null)
      payload.eligibility_id = examinationsList.value.map(exam => exam.eligibility_id || 0)
      payload.eligibility_description = examinationsList.value.map(exam => exam.eligibility_description || '')
      payload.exam_rating = examinationsList.value.map(exam => exam.exam_rating || 0)
      payload.exam_date = examinationsList.value.map(exam => exam.exam_date || '')
      payload.place_of_exam = examinationsList.value.map(exam => exam.place_of_exam || '')
      payload.license_number = examinationsList.value.map(exam => exam.license_number || '')
      payload.date_released = examinationsList.value.map(exam => exam.date_released || '')
    } else {
      // Send empty arrays if no examinations
      payload.examination_id = []
      payload.eligibility_id = []
      payload.eligibility_description = []
      payload.exam_rating = []
      payload.exam_date = []
      payload.place_of_exam = []
      payload.license_number = []
      payload.date_released = []
    }
    
    // Format trainings data into arrays
    if (trainingsList.value.length > 0) {
      payload.training_id = trainingsList.value.map(training => training.training_id || null)
      payload.training = trainingsList.value.map(training => training.training || '')
      payload.training_from = trainingsList.value.map(training => training.training_from || '')
      payload.training_to = trainingsList.value.map(training => training.training_to || '')
      payload.hours = trainingsList.value.map(training => training.hours || 0)
      payload.sponsored_by = trainingsList.value.map(training => training.sponsored_by || '')
      payload.learning_id = trainingsList.value.map(training => training.learning_id || 0)
    } else {
      // Send empty arrays if no trainings
      payload.training_id = []
      payload.training = []
      payload.training_from = []
      payload.training_to = []
      payload.hours = []
      payload.sponsored_by = []
      payload.learning_id = []
    }
    
    // Format organizations (voluntary work) data into arrays
    if (organizationsList.value.length > 0) {
      payload.organization_id = organizationsList.value.map(org => org.organization_id || null)
      payload.organization = organizationsList.value.map(org => org.organization || '')
      payload.organization_address = organizationsList.value.map(org => org.organization_address || '')
      payload.org_from = organizationsList.value.map(org => org.org_from || '')
      payload.org_to = organizationsList.value.map(org => org.org_to || '')
      payload.org_hours = organizationsList.value.map(org => org.org_hours || 0)
      payload.org_position = organizationsList.value.map(org => org.org_position || '')
    } else {
      // Send empty arrays if no organizations
      payload.organization_id = []
      payload.organization = []
      payload.organization_address = []
      payload.org_from = []
      payload.org_to = []
      payload.org_hours = []
      payload.org_position = []
    }
    
    // Format skills data into arrays
    if (skillsList.value.length > 0) {
      payload.skill_id = skillsList.value.map(skill => skill.skill_id || null)
      payload.skill = skillsList.value.map(skill => skill.skill || '')
    } else {
      // Send empty arrays if no skills
      payload.skill_id = []
      payload.skill = []
    }
    
    // Format recognitions data into arrays
    if (recognitionsList.value.length > 0) {
      payload.recognation_id = recognitionsList.value.map(recog => recog.recognation_id || null)
      payload.recognation = recognitionsList.value.map(recog => recog.recognation || '')
    } else {
      // Send empty arrays if no recognitions
      payload.recognation_id = []
      payload.recognation = []
    }

    // Format memberships data into arrays
    if (membershipList.value.length > 0) {
      payload.membership_id = membershipList.value.map(mem => mem.membership_id || null)
      payload.membership = membershipList.value.map(mem => mem.membership || '')
    } else {
      payload.membership_id = []
      payload.membership = []
    }
    
    // Format references data into arrays
    if (referencesList.value.length > 0) {
      payload.reference_id = referencesList.value.map(ref => ref.reference_id || null)
      payload.ref_name = referencesList.value.map(ref => ref.ref_name || '')
      payload.ref_address = referencesList.value.map(ref => ref.ref_address || '')
      payload.ref_occupation = referencesList.value.map(ref => ref.ref_occupation || '')
      payload.ref_contact_no = referencesList.value.map(ref => ref.ref_contact_no || '')
      payload.ref_email = referencesList.value.map(ref => ref.ref_email || '')
    } else {
      // Send empty arrays if no references
      payload.reference_id = []
      payload.ref_name = []
      payload.ref_address = []
      payload.ref_occupation = []
      payload.ref_contact_no = []
      payload.ref_email = []
    }
    
    // Format dependents data into arrays
    if (dependentsList.value.length > 0) {
      payload.dependent_id = dependentsList.value.map(dep => dep.dependent_id || null)
      payload.dep_name = dependentsList.value.map(dep => dep.dep_name || '')
      payload.dep_relationship = dependentsList.value.map(dep => dep.dep_relationship || '')
      payload.dep_course = dependentsList.value.map(dep => dep.dep_course || '')
    } else {
      // Send empty arrays if no dependents
      payload.dependent_id = []
      payload.dep_name = []
      payload.dep_relationship = []
      payload.dep_course = []
    }
    
    // Format contract data into arrays (COS employees only)
    if (formData.employment_type_id === 2 && contractList.value.length > 0) {
      payload.contract_id = contractList.value.map(contract => contract.id || null)
      payload.contract_start_date = contractList.value.map(contract => contract.Start_date || '')
      payload.contract_end_date = contractList.value.map(contract => contract.End_date || '')
      payload.contract_salary = contractList.value.map(contract => contract.salary ?? 0)
      payload.contract_salary_grade_id = contractList.value.map(contract => contract.salary_grade_id ?? 0)
      payload.contract_salary_step_id = contractList.value.map(contract => contract.salary_step_id ?? 0)
      payload.contract_is_active = contractList.value.map(contract => isContractEffectiveOnDate(contract) ? 1 : 0)
    } else {
      // Send empty arrays if no contracts or not COS employee
      payload.contract_id = []
      payload.contract_start_date = []
      payload.contract_end_date = []
      payload.contract_salary = []
      payload.contract_salary_grade_id = []
      payload.contract_salary_step_id = []
      payload.contract_is_active = []
    }
    
    // Build FormData payload to support photo upload
    const toFormData = (obj) => {
      const fd = new FormData()
      Object.entries(obj).forEach(([key, val]) => {
        if (Array.isArray(val)) {
          val.forEach(item => fd.append(`${key}[]`, item ?? ''))
        } else if (typeof val === 'boolean') {
          fd.append(key, val ? 1 : 0)
        } else if (val === null || val === undefined) {
          fd.append(key, '')
        } else if (typeof val === 'number') {
          // Ensure numeric values (like plantilla_id, department_id, etc.) are sent as numbers
          fd.append(key, val.toString())
        } else {
          fd.append(key, val)
        }
      })
      if (avatarFile.value) {
        fd.append('photo', avatarFile.value)
      }
      return fd
    }

    const formDataPayload = toFormData(payload)

    if (formData.employment_type_id === 2) {
      contractList.value.forEach((contract, index) => {
        if (contract.pendingFile) {
          formDataPayload.append(`contract_file[${index}]`, contract.pendingFile)
        }
        if (contract.removeFile) {
          formDataPayload.append(`contract_remove_file[${index}]`, '1')
        }
      })
    }

    let employeeId = props.employeeData?.id
    
    if (isEdit.value) {
      await updateEmployee(props.employeeData.id, formDataPayload)
    } else {
      const result = await createEmployee(formDataPayload)
      // If creating new employee, get the new ID from response
      if (result?.data?.data?.id) {
        employeeId = result.data.data.id
      }
    }
    
    // Reload documents if on documents tab and we have an employee ID
    if (activeTab.value === 'documents' && employeeId) {
      await loadDocuments(employeeId)
    }
    
    // Clear auto-saved draft after successful save
    clearLocalStorage()
    
    emit('saved')
    handleClose()
  } catch (error) {
    console.error('Form validation failed:', error)
  }
}

// Download document
const handleDownload = async (employeeDocumentId) => {
  try {
    // bubble up; actual download handled elsewhere if needed
    // could call a composable download here if required
    // placeholder for integration with employeeApi.downloadDocument
  } catch (e) {
    console.error('Download failed', e)
  }
}

// Watch for employee data changes
watch(() => props.employeeData, (newData) => {
  // Don't populate if we're restoring from localStorage
  if (isRestoringFromLocalStorage) {
    return
  }
  
  if (newData) {
    populateForm(newData)
    const photoSrc = newData.photo_url || (newData.photo ? `data:image/jpeg;base64,${newData.photo}` : '')
    photoPreview.value = photoSrc
  } else {
    resetForm()
    if (photoPreview.value?.startsWith('blob:')) {
      URL.revokeObjectURL(photoPreview.value)
    }
    photoPreview.value = ''
    avatarFile.value = null
  }
}, { immediate: true })

const handleAvatarSelect = (file) => {
  const isImage = file.type.startsWith('image/')
  const isLt3M = file.size / 1024 / 1024 < 3

  if (!isImage) {
    ElMessage.error('Please upload image files only')
    return false
  }

  if (!isLt3M) {
    ElMessage.error('Image size must be less than 3MB')
    return false
  }

  if (photoPreview.value?.startsWith('blob:')) {
    URL.revokeObjectURL(photoPreview.value)
  }

  photoPreview.value = URL.createObjectURL(file)
  avatarFile.value = file
  return false
}

// Watch for dialog visibility
watch(visible, (newVal) => {
  if (newVal) {
    if (props.employeeData) {
      // Editing existing employee - check if there's a draft to restore
      const storageKey = getStorageKey()
      const hasDraft = localStorage.getItem(storageKey)
      if (hasDraft) {
        // Restore draft if available (user was editing)
        const restored = restoreFromLocalStorage()
        if (restored) {
          ElMessage.info('Draft restored from previous editing session')
        }
      } else {
        // No draft, populate from employeeData
        populateForm(props.employeeData)
      }
    } else {
      // Creating new employee - restore from localStorage if available
      const restored = restoreFromLocalStorage()
      if (restored) {
        ElMessage.info('Draft restored from previous session')
      }
    }
  } else if (!newVal) {
    resetForm()
  }
})

// Watch for tab changes to load documents when Documents tab is accessed
watch(activeTab, (newTab) => {
  if (newTab === 'documents' && props.employeeData?.id) {
    loadDocuments()
  }
})

// Auto-save watchers - save to localStorage when form data changes (only when visible)
watch(() => formData, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => childrenList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => educationList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => serviceRecordList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => contractList.value, () => {
  syncCosPayrollHoldPreview()
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => formData.employment_type_id, () => {
  syncCosPayrollHoldPreview()
})

watch(() => workExperienceList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => examinationsList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => trainingsList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => organizationsList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => skillsList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => recognitionsList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => membershipList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => referencesList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => dependentsList.value, () => {
  if (visible.value) {
    debouncedSave()
  }
}, { deep: true })

watch(() => activeTab.value, () => {
  if (visible.value) {
    debouncedSave()
  }
})

// Set up page unload handler
onMounted(() => {
  fetchCompanies()
  window.addEventListener('beforeunload', handleBeforeUnload)
  // Also save on visibility change (when user switches tabs)
  document.addEventListener('visibilitychange', () => {
    if (document.hidden && visible.value) {
      immediateSave()
    }
  })
})

// Clean up event listeners
onBeforeUnmount(() => {
  // Save before component unmounts
  if (visible.value) {
    immediateSave()
  }
  window.removeEventListener('beforeunload', handleBeforeUnload)
})
</script>

<style scoped>
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

:deep(.el-tabs__content) {
  padding: 20px;
}

:deep(.el-form-item__label) {
  font-weight: 600;
}

.avatar-box { display: flex; flex-direction: column; align-items: center; }
.section-heading { font-weight: 600; margin-bottom: 8px; }
.section-title { font-size: 12px; color: #64748b; margin: 6px 0 4px; }
.mt-2 { margin-top: 8px; }
.mb-4 { margin-bottom: 16px; }
.skills-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.el-card { padding: 12px; }
.el-form-item { margin-bottom: 10px; }

/* Payroll Related Styles */
.card-header-title {
  font-weight: 600;
  font-size: 14px;
}

.table-header {
  padding: 12px 16px;
  font-weight: 600;
  font-size: 14px;
  color: white;
  margin: -12px -12px 12px -12px;
}

.income-header {
  background-color: #ffc107;
  color: #000;
}

.loans-header {
  background-color: #f44336;
  color: white;
}

:deep(.income-card .el-card__header) {
  padding: 0;
  border-bottom: none;
}

:deep(.loans-card .el-card__header) {
  padding: 0;
  border-bottom: none;
}

:deep(.income-card .el-card__body) {
  padding: 12px;
}

:deep(.loans-card .el-card__body) {
  padding: 12px;
}

/* Pill-like tabs styling */
:deep(.pill-tabs) {
  --tab-radius: 6px;
}

:deep(.pill-tabs .el-tabs__header) {
  margin-bottom: 12px;
  border-bottom: 2px solid #e6eefc;
}

:deep(.pill-tabs .el-tabs__nav) { border: none !important; }

:deep(.pill-tabs .el-tabs__item) {
  border: 1px solid transparent;
  border-radius: var(--tab-radius);
  margin-right: 8px;
  height: 34px;
  line-height: 34px;
  padding: 0 14px;
  color: #64748b;
}

:deep(.pill-tabs .el-tabs__item.is-active) {
  background: #1976d2;
  color: #fff;
  border-color: #1976d2;
  box-shadow: 0 0 0 1px #1976d2 inset;
}

:deep(.pill-tabs .el-tabs__active-bar) { display: none; }
:deep(.pill-tabs .el-tabs__nav-wrap::after) { display: none; }

.contract-upload-cell {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
}

.contract-file-name {
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 12px;
  color: #606266;
}

.contract-upload-tip-text {
  margin: 4px 0 0;
  font-size: 13px;
  line-height: 1.5;
  color: #606266;
}

.contract-upload-tip-text strong {
  color: #303133;
}

.cos-hold-hint {
  margin: 6px 0 0;
  font-size: 12px;
  line-height: 1.4;
  color: #909399;
}
</style>
