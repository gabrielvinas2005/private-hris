<template>
  <el-dialog
    v-model="visible"
    width="90%"
    :close-on-click-modal="false"
    destroy-on-close
  >
    <template #header>
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <span>Employee Details</span>
      </div>
    </template>
    
    <el-form
      label-position="top"
      size="small"
      v-loading="loading"
      :disabled="true"
    >
      <div class="readonly-container">
      <!-- Two-column onboarding layout -->
      <el-row :gutter="20" class="mb-4">
        <!-- Left sidebar -->
        <el-col :span="6">
          <el-card shadow="never">
            <div class="avatar-box">
              <el-avatar :size="96" :src="photoSrc">
                <el-icon :size="60"><User /></el-icon>
              </el-avatar>
          </div>
            <el-divider />
            <div class="section-title">Employee No.</div>
            <el-input :model-value="employeeData?.employee_no || ''" disabled placeholder="Employee Number" />
            <div class="section-title mt-2">Access No.</div>
            <el-input :model-value="employeeData?.access_no || ''" disabled placeholder="Access Number" />
          </el-card>

          <el-card shadow="never" class="mt-2">
            <div class="section-heading">Contact Informations</div>
            <el-form-item label="Email">
              <el-input :model-value="employeeData?.email || ''" disabled placeholder="Email Address" />
            </el-form-item>
            <el-form-item label="Mobile No.">
              <el-input :model-value="employeeData?.mobile_no || ''" disabled placeholder="Mobile Number" />
            </el-form-item>
            <el-form-item label="Telephone No.">
              <el-input :model-value="employeeData?.telephone_no || ''" disabled placeholder="Telephone Number" />
            </el-form-item>
          </el-card>

          <el-card shadow="never" class="mt-2">
            <div class="section-heading">Contributions ID</div>
            <el-form-item label="TIN"><el-input :model-value="employeeData?.tin_no || ''" disabled placeholder="TIN Number" /></el-form-item>
            <el-form-item label="GSIS No."><el-input :model-value="employeeData?.gsis_no || ''" disabled placeholder="GSIS Number" /></el-form-item>
            <el-form-item label="SSS No."><el-input :model-value="employeeData?.sss_no || ''" disabled placeholder="SSS Number" /></el-form-item>
            <el-form-item label="Pag-ibig No."><el-input :model-value="employeeData?.pagibig_no || ''" disabled placeholder="Pag-ibig Number" /></el-form-item>
            <el-form-item label="PhilHealth No."><el-input :model-value="employeeData?.philhealth_no || ''" disabled placeholder="PhilHealth Number" /></el-form-item>
            <el-form-item label="CRN No."><el-input :model-value="employeeData?.crn_no || ''" disabled placeholder="CRN Number" /></el-form-item>
          </el-card>
        </el-col>

        <!-- Right content -->
        <el-col :span="18">
          <el-card shadow="never">
            <div class="section-heading">Personal Information</div>
            <el-row :gutter="16">
              <el-col :span="4">
                <el-form-item label="Prefix">
                  <el-input :model-value="getLookupName(employeeData?.name_prefix_id, formOptions?.prefixes)" disabled placeholder="Select Prefix" />
                </el-form-item>
              </el-col>
              <el-col :span="5"><el-form-item label="First Name"><el-input :model-value="employeeData?.first_name || ''" disabled /></el-form-item></el-col>
              <el-col :span="5"><el-form-item label="Middle Name"><el-input :model-value="employeeData?.middle_name || ''" disabled /></el-form-item></el-col>
              <el-col :span="5"><el-form-item label="Last Name"><el-input :model-value="employeeData?.last_name || ''" disabled /></el-form-item></el-col>
              <el-col :span="5"><el-form-item label="Suffix"><el-input :model-value="getLookupName(employeeData?.name_suffix_id, formOptions?.suffixes)" disabled placeholder="Select Suffix" /></el-form-item></el-col>
            </el-row>

            <el-row :gutter="16">
              <el-col :span="12"><el-form-item label="Birth Place"><el-input :model-value="employeeData?.birth_place || ''" disabled /></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Birth Date"><el-input :model-value="formatDate(employeeData?.birthdate)" disabled placeholder="mm/dd/yyyy" /></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Age"><el-input :model-value="employeeData?.age || ''" disabled placeholder="Age" /></el-form-item></el-col>
            </el-row>

            <el-row :gutter="16">
              <el-col :span="6"><el-form-item label="Gender"><el-input :model-value="getGenderName()" disabled placeholder="Select Gender" /></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Civil Status"><el-input :model-value="getCivilStatusName()" disabled placeholder="Select Civil Status" /></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Citizenship"><el-input :model-value="getLookupName(employeeData?.citizenship_id, formOptions?.citizenships)" disabled placeholder="Select Citizenship" /></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Religion"><el-input :model-value="getLookupName(employeeData?.religion_id, formOptions?.religions)" disabled placeholder="Select Religion" /></el-form-item></el-col>
            </el-row>

            <el-row :gutter="16">
              <el-col :span="6"><el-form-item label="Height (cm)"><el-input :model-value="employeeData?.height || ''" disabled placeholder="Height" /></el-form-item></el-col>
              <el-col :span="6"><el-form-item label="Weight (kg)"><el-input :model-value="employeeData?.weight || ''" disabled placeholder="Weight" /></el-form-item></el-col>
              <el-col :span="12"><el-form-item label="Blood Type"><el-input :model-value="getBloodTypeName()" disabled placeholder="Select Blood Type" /></el-form-item></el-col>
            </el-row>
          </el-card>
          
          <!-- Current Address -->
          <el-card shadow="never" class="mt-2">
            <div class="section-heading">Current Address</div>
            <el-row :gutter="16">
              <el-col :span="8">
                <el-form-item label="Region">
                  <el-input :model-value="getRegionName(employeeData?.ca_region)" disabled placeholder="Select Region" />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Province">
                  <el-input :model-value="getProvinceName(employeeData?.ca_province)" disabled placeholder="Select Province" />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Municipality/City">
                  <el-input :model-value="getCityName(employeeData?.ca_city)" disabled placeholder="Select City/Municipality" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="16">
              <el-col :span="6">
                <el-form-item label="Zip Code">
                  <el-input :model-value="employeeData?.ca_zip || ''" disabled placeholder="Zip Code" />
                </el-form-item>
              </el-col>
              <el-col :span="10">
                 <el-form-item label="Barangay/Purok">
                   <el-input :model-value="getBarangayName(employeeData?.ca_barangay)" disabled placeholder="Select Barangay" />
                 </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="House No.">
                  <el-input :model-value="employeeData?.ca_house_no || ''" disabled placeholder="House Number" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Street">
                  <el-input :model-value="employeeData?.ca_street || ''" disabled placeholder="Street" />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Village/Subdivision">
                  <el-input :model-value="employeeData?.ca_village || ''" disabled placeholder="Village/Subdivision" />
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
                  <el-input :model-value="getRegionName(employeeData?.pa_region)" disabled placeholder="Select Region" />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Province">
                  <el-input :model-value="getProvinceName(employeeData?.pa_province)" disabled placeholder="Select Province" />
                </el-form-item>
              </el-col>
               <el-col :span="8">
                 <el-form-item label="Municipality/City">
                   <el-input :model-value="getCityName(employeeData?.pa_city)" disabled placeholder="Select City/Municipality" />
                 </el-form-item>
               </el-col>
            </el-row>
            <el-row :gutter="12">
              <el-col :span="6">
                <el-form-item label="Zip Code">
                  <el-input :model-value="employeeData?.pa_zip || ''" disabled placeholder="Zip Code" />
                </el-form-item>
              </el-col>
              <el-col :span="10">
                 <el-form-item label="Barangay/Purok">
                   <el-input :model-value="getBarangayName(employeeData?.pa_barangay)" disabled placeholder="Select Barangay" />
                 </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="House No.">
                  <el-input :model-value="employeeData?.pa_house_no || ''" disabled placeholder="House Number" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Street">
                  <el-input :model-value="employeeData?.pa_street || ''" disabled placeholder="Street" />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Village/Subdivision">
                  <el-input :model-value="employeeData?.pa_village || ''" disabled placeholder="Village/Subdivision" />
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>

          <!-- Dual Citizenship Information -->
          <el-card shadow="never" class="mt-2">
            <div class="section-heading">Dual-Citizenship Information</div>
            <el-form-item label="With Dual Citizenship?">
              <el-input :model-value="employeeData?.is_dual_citizent ? 'Yes' : 'No'" disabled />
            </el-form-item>
            <el-row :gutter="12">
              <el-col :span="12">
                <el-input :model-value="employeeData?.dual_citizenship_type === 'by_birth' ? 'By Birth' : employeeData?.dual_citizenship_type === 'by_naturalization' ? 'By Naturalization' : ''" disabled />
              </el-col>
              <el-col :span="12">
                <el-form-item label="Country of Origin">
                  <el-input :model-value="employeeData?.indicate_country || ''" disabled placeholder="Select Country of Origin" />
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>
        </el-col>
      </el-row>

      <!-- Tabs section -->
      <el-tabs v-model="activeTab" type="card" class="pill-tabs">
        <!-- Work Information Tab -->
        <el-tab-pane label="Work Information" name="work_information">
          <el-row :gutter="20">
            <el-col :span="12"><el-form-item label="Agency"><el-input :model-value="agencyName" disabled /></el-form-item></el-col>
            <el-col :span="12"><el-form-item label="Is Plantilla?"><el-input :model-value="isPlantillaEmployee() ? 'Yes' : 'No'" disabled /></el-form-item></el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Department">
                <el-input :model-value="getDepartmentName()" disabled placeholder="Select Department" />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Plantilla Item">
                <el-input :model-value="getPlantillaCode()" disabled placeholder="Select Plantilla Item" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="8">
              <el-form-item label="Division">
                <el-input :model-value="getLookupName(employeeData?.division_id, formOptions?.divisions)" disabled placeholder="Select Division" />
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="Section">
                <el-input :model-value="getLookupName(employeeData?.section_id, formOptions?.sections)" disabled placeholder="Section" />
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="Salary Grade">
                <el-input :model-value="getSalaryGradeName(employeeData?.salary_grade_id)" disabled placeholder="Salary Grade" />
              </el-form-item>
            </el-col>
            <el-col :span="8">
              <el-form-item label="Salary Step">
                <el-input :model-value="getSalaryStepName(employeeData?.salary_step_id)" disabled placeholder="Salary Step" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12"><el-form-item label="Employment Type"><el-input :model-value="getEmploymentTypeName()" disabled placeholder="Select Employment Type" /></el-form-item></el-col>
            <el-col :span="12">
              <el-form-item label="Date Hired">
                <el-input :model-value="formatDate(employeeData?.date_hired)" disabled placeholder="Select Date Hired" />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12"><el-form-item label="Position"><el-input :model-value="getPositionName()" disabled placeholder="Please Select Position" /></el-form-item></el-col>
          </el-row>
        </el-tab-pane>

        <!-- Payroll Related Tab -->
        <el-tab-pane label="Payroll Related" name="payroll">
          <el-card shadow="never" class="mb-4">
            <template #header>
              <span class="card-header-title">General Payroll Information</span>
            </template>
            <el-row :gutter="20">
              <el-col :span="8">
                <el-form-item label="Account No.">
                  <el-input :model-value="employeeData?.account_no || ''" disabled placeholder="Enter account number" />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Payroll Interval">
                  <el-input :model-value="getPayrollIntervalName()" disabled placeholder="Select Payroll Interval" />
                </el-form-item>
              </el-col>
              <el-col :span="8">
                <el-form-item label="Is Hold?">
                  <el-input :model-value="employeeData?.is_hold ? 'Yes - Hold Payroll' : 'No'" disabled />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row v-if="employeeData?.is_hold" :gutter="20">
              <el-col :span="24">
                <el-form-item label="Hold Remarks">
                  <el-input 
                    :model-value="employeeData?.hold_remarks || ''" 
                    type="textarea" 
                    :rows="2"
                    disabled
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
                <el-form-item label="Basic Salary">
                  <el-input :model-value="formatCurrency(employeeData?.salary)" disabled placeholder="0.00" />
                </el-form-item>
                <el-form-item label="Daily Rate">
                  <el-input :model-value="formatCurrency(employeeData?.daily_rate)" disabled />
                </el-form-item>
                <el-form-item label="Hourly Rate">
                  <el-input :model-value="formatCurrency(employeeData?.hourly_rate)" disabled />
                </el-form-item>
              </el-col>
              
              <el-col :span="8">
                <el-form-item label="Tax Amount">
                  <el-input :model-value="formatCurrency(employeeData?.tax_amount)" disabled placeholder="0.00" />
                </el-form-item>
                <el-form-item label="GSIS Amount">
                  <el-input :model-value="formatCurrency(employeeData?.gsis_amount)" disabled placeholder="0.00" />
                </el-form-item>
                <el-form-item label="SSS Amount">
                  <el-input :model-value="formatCurrency(employeeData?.sss_amount)" disabled placeholder="0.00" />
                </el-form-item>
              </el-col>
              
              <el-col :span="8">
                <el-form-item label="Pag-Ibig Amount">
                  <el-input :model-value="formatCurrency(employeeData?.pagibig_amount)" disabled placeholder="0.00" />
                </el-form-item>
                <el-form-item label="PhilHealth Amount">
                  <el-input :model-value="formatCurrency(employeeData?.philhealth_amount)" disabled placeholder="0.00" />
                </el-form-item>
            </el-col>
            </el-row>
          </el-card> -->

          <!-- Income and Loans Tables -->
          <el-row :gutter="20">
            <el-col :span="12">
              <el-card shadow="never" class="income-card">
                <template #header>
                  <div class="table-header income-header">
                    <span>Income</span>
              </div>
                </template>
                <el-table 
                  :data="relatedData?.incomes || []" 
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
            
            <el-col :span="12">
              <el-card shadow="never" class="loans-card">
                <template #header>
                  <div class="table-header loans-header">
                    <span>Loans</span>
              </div>
                </template>
                <el-table 
                  :data="relatedData?.loans || []" 
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
          <el-row :gutter="20" class="mb-4">
            <el-col :span="24">
              <h4 style="margin-bottom: 16px;">Father's Name:</h4>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="4">
              <el-form-item label="Prefix">
                <el-input :model-value="getLookupName(employeeData?.father_name_prefix_id, formOptions?.prefixes)" disabled placeholder="Prefix" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="First Name">
                <el-input :model-value="employeeData?.father_first_name || ''" disabled placeholder="First Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Middle Name">
                <el-input :model-value="employeeData?.father_middle_name || ''" disabled placeholder="Middle Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Last Name">
                <el-input :model-value="employeeData?.father_last_name || ''" disabled placeholder="Last Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Suffix">
                <el-input :model-value="getLookupName(employeeData?.father_name_suffix_id, formOptions?.suffixes)" disabled placeholder="Suffix" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20" class="mb-4 mt-4">
            <el-col :span="24">
              <h4 style="margin-bottom: 16px;">Mother's Maiden Name:</h4>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="4">
              <el-form-item label="Prefix">
                <el-input :model-value="getLookupName(employeeData?.mother_name_prefix_id, formOptions?.prefixes)" disabled placeholder="Prefix" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="First Name">
                <el-input :model-value="employeeData?.mother_first_name || ''" disabled placeholder="First Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Middle Name">
                <el-input :model-value="employeeData?.mother_middle_name || ''" disabled placeholder="Middle Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Surname">
                <el-input :model-value="employeeData?.mother_last_name || ''" disabled placeholder="Last Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Suffix">
                <el-input :model-value="getLookupName(employeeData?.mother_name_suffix_id, formOptions?.suffixes)" disabled placeholder="Suffix" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20" class="mb-4 mt-4">
            <el-col :span="24">
              <h4 style="margin-bottom: 16px;">Spouse's Name:</h4>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="4">
              <el-form-item label="Prefix">
                <el-input :model-value="getLookupName(employeeData?.spouse_name_prefix_id, formOptions?.prefixes)" disabled placeholder="Prefix" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="First Name">
                <el-input :model-value="employeeData?.spouse_first_name || ''" disabled placeholder="First Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Middle Name">
                <el-input :model-value="employeeData?.spouse_middle_name || ''" disabled placeholder="Middle Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Last Name">
                <el-input :model-value="employeeData?.spouse_last_name || ''" disabled placeholder="Last Name" />
              </el-form-item>
            </el-col>
            <el-col :span="5">
              <el-form-item label="Suffix">
                <el-input :model-value="getLookupName(employeeData?.spouse_name_suffix_id, formOptions?.suffixes)" disabled placeholder="Suffix" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Occupation">
                <el-input :model-value="employeeData?.spouse_occupation || ''" disabled />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Employer/Business Name">
                <el-input :model-value="employeeData?.spouse_business_name || ''" disabled />
              </el-form-item>
            </el-col>
          </el-row>
          <el-row :gutter="20">
            <el-col :span="12">
              <el-form-item label="Business Address">
                <el-input :model-value="employeeData?.spouse_business_address || ''" disabled />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Telephone No.">
                <el-input :model-value="employeeData?.spouse_telephone_no || ''" disabled />
              </el-form-item>
            </el-col>
          </el-row>
        </el-tab-pane>

        <!-- Education Tab -->
        <el-tab-pane label="Education" name="education">
          <el-card shadow="never">
            <el-table 
              :data="relatedData?.educations || []" 
              border 
              size="small"
              :empty-text="'No education records'"
              style="width: 100%"
            >
              <el-table-column label="Academic Level" width="140" align="center" prop="academic_level_id">
                <template #default="{ row }">
                  {{ getAcademicLevel(row.academic_level_id) }}
                </template>
              </el-table-column>
              <el-table-column label="School Name" min-width="180" prop="school_name" />
              <el-table-column label="Program" min-width="150" prop="program" />
              <el-table-column label="From" width="100" align="center" prop="from" />
              <el-table-column label="To" width="100" align="center" prop="to" />
              <el-table-column label="Year Graduated" width="120" align="center" prop="graduated_year" />
              <el-table-column label="Units Earned" width="120" align="center" prop="units_earned" />
              <el-table-column label="Honors" min-width="120" prop="honors" />
            </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Service Record Tab -->
        <el-tab-pane label="Service Record" name="service_record">
          <el-card shadow="never">
            <el-table
              :data="relatedData?.service_records || []"
              border
              size="small"
              :empty-text="'No service records'"
              style="width: 100%"
            >
              <el-table-column label="Start Date" width="150" prop="start_date">
                <template #default="{ row }">
                  {{ formatDate(row.start_date) }}
                </template>
              </el-table-column>
              <el-table-column label="End Date" width="150" prop="end_date">
                <template #default="{ row }">
                  {{ formatDate(row.end_date) }}
                </template>
              </el-table-column>
              <el-table-column label="Designation" min-width="180" prop="designation" />
              <el-table-column label="Employment Type" min-width="180" prop="employment_type">
                <template #default="{ row }">
                  {{ getLookupName(row.employment_type, formOptions?.employment_types) }}
                </template>
              </el-table-column>
              <el-table-column label="Place of Assignment" min-width="200" prop="place_of_assignment" />
              <el-table-column label="L/WOP" width="120" align="center" prop="leave_without_pay" />
              <el-table-column label="Separation Date" width="160" prop="separation_date">
                <template #default="{ row }">
                  {{ formatDate(row.separation_date) }}
                </template>
              </el-table-column>
              <el-table-column label="Cause" min-width="200" prop="cause" />
            </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Work Experience Tab -->
        <el-tab-pane label="Work Experience" name="work_experience">
          <el-card shadow="never">
            <el-table
              :data="relatedData?.employments || []"
              border
              size="small"
              :empty-text="'No work experience records'"
              style="width: 100%"
            >
              <el-table-column label="Start Date" width="150" prop="work_start_date">
                <template #default="{ row }">
                  {{ formatDate(row.work_start_date) }}
                </template>
              </el-table-column>
              <el-table-column label="End Date" width="150" prop="work_end_date">
                <template #default="{ row }">
                  {{ formatDate(row.work_end_date) }}
                </template>
              </el-table-column>
              <el-table-column label="Company / Office" min-width="200" prop="work_company" />
              <el-table-column label="Position" min-width="180" prop="position_we" />
              <el-table-column label="Monthly Salary" width="160" align="right" prop="monthly_salary">
                <template #default="{ row }">
                  {{ formatCurrency(row.monthly_salary) }}
                </template>
              </el-table-column>
              <el-table-column label="Grade / Step" width="140" prop="salary_grade_step" />
              <el-table-column label="Status" min-width="160" prop="status_of_appointment" />
              <el-table-column label="Gov. Service" width="140" align="center" prop="government_service_id">
                <template #default="{ row }">
                  {{ row.government_service_id === 1 ? 'Yes' : 'No' }}
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Eligibility Tab -->
        <el-tab-pane label="Eligibility" name="eligibility">
          <el-card shadow="never">
            <el-table 
              :data="relatedData?.examinations || []" 
              border 
              size="small"
              :empty-text="'No examination records'"
              style="width: 100%"
            >
              <el-table-column label="Examinations" width="180" align="center" prop="eligibility_id">
                <template #default="{ row }">
                  {{ getLookupName(row.eligibility_id, formOptions?.eligibilities) }}
                </template>
              </el-table-column>
              <el-table-column label="Description" min-width="180" prop="eligibility_description" />
              <el-table-column label="Rating" width="120" align="center" prop="exam_rating" />
              <el-table-column label="Examination Date" width="160" align="center" prop="exam_date">
                <template #default="{ row }">
                  {{ formatDate(row.exam_date) }}
                </template>
              </el-table-column>
              <el-table-column label="Place of Exam" min-width="150" prop="place_of_exam" />
              <el-table-column label="License Number" width="140" align="center" prop="license_number" />
              <el-table-column label="Date Released" width="160" align="center" prop="date_released">
                <template #default="{ row }">
                  {{ formatDate(row.date_released) }}
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Trainings Tab -->
        <el-tab-pane label="Trainings" name="trainings">
          <el-card shadow="never">
            <el-table 
              :data="relatedData?.trainings || []" 
              border 
              size="small"
              :empty-text="'No training records'"
              style="width: 100%"
            >
              <el-table-column label="Title" min-width="250" prop="training" />
              <el-table-column label="From" width="150" align="center" prop="training_from">
                <template #default="{ row }">
                  {{ formatDate(row.training_from) }}
                </template>
              </el-table-column>
              <el-table-column label="To" width="150" align="center" prop="training_to">
                <template #default="{ row }">
                  {{ formatDate(row.training_to) }}
                </template>
              </el-table-column>
              <el-table-column label="Hours" width="100" align="center" prop="hours" />
              <el-table-column label="Sponsored By" min-width="180" prop="sponsored_by" />
              <el-table-column label="Specialization" width="160" align="center" prop="learning_id">
                <template #default="{ row }">
                  {{ getLookupName(row.learning_id, formOptions?.learnings) }}
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Voluntary Work Tab -->
        <el-tab-pane label="Voluntary Work" name="voluntary_work">
          <el-card shadow="never">
            <el-table 
              :data="relatedData?.organizations || []" 
              border 
              size="small"
              :empty-text="'No voluntary work records'"
              style="width: 100%"
            >
              <el-table-column label="Organization" min-width="180" prop="organization" />
              <el-table-column label="Address" min-width="180" prop="organization_address" />
              <el-table-column label="From" width="150" align="center" prop="org_from">
                <template #default="{ row }">
                  {{ formatDate(row.org_from) }}
                </template>
              </el-table-column>
              <el-table-column label="To" width="150" align="center" prop="org_to">
                <template #default="{ row }">
                  {{ formatDate(row.org_to) }}
                </template>
              </el-table-column>
              <el-table-column label="Hours" width="100" align="center" prop="org_hours" />
              <el-table-column label="Position" min-width="150" prop="org_position" />
            </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Recognitions Tab -->
        <el-tab-pane label="Recognitions" name="recognitions">
          <el-card shadow="never">
            <template #header>
              <span style="font-weight: 600;">Non-Academic Distinctions/Recognition</span>
            </template>
            <div style="padding: 12px 0;">
              <div 
                v-for="(recognition, index) in (relatedData?.recognitions || [])" 
                :key="index" 
                style="padding: 8px 12px; margin-bottom: 8px; background: #f5f7fa; border-radius: 4px;"
              >
                {{ recognition.recognation || recognition.recognition_name || 'N/A' }}
                </div>
              <div v-if="!relatedData?.recognitions || relatedData.recognitions.length === 0" style="text-align: center; padding: 20px; color: #909399;">
                No recognitions added
                </div>
                </div>
          </el-card>
        </el-tab-pane>

        <!-- Skills Tab -->
        <el-tab-pane label="Skills" name="skills">
          <el-card shadow="never">
            <template #header>
              <span style="font-weight: 600;">Special Skills/Hobbies</span>
            </template>
            <div style="padding: 12px 0;">
              <div 
                v-for="(skill, index) in (relatedData?.skills || [])" 
                :key="index" 
                style="padding: 8px 12px; margin-bottom: 8px; background: #f5f7fa; border-radius: 4px;"
              >
                {{ skill.skill || 'N/A' }}
                </div>
              <div v-if="!relatedData?.skills || relatedData.skills.length === 0" style="text-align: center; padding: 20px; color: #909399;">
                No skills/hobbies added
              </div>
                </div>
          </el-card>
        </el-tab-pane>

        <!-- Memberships Tab -->
        <el-tab-pane label="Memberships" name="memberships">
          <el-card shadow="never">
            <div class="section-heading">Membership in Association/Organization</div>
            <el-divider />
            <div style="padding: 12px 0;">
              <div
                v-for="(membership, index) in (relatedData?.memberships || [])"
                :key="index"
                style="padding: 8px 12px; margin-bottom: 8px; background: #f5f7fa; border-radius: 4px;"
              >
                {{ membership.membership || 'N/A' }}
                </div>
              <div v-if="!relatedData?.memberships || relatedData.memberships.length === 0" style="text-align: center; padding: 20px; color: #909399;">
                No membership records
              </div>
            </div>
          </el-card>
        </el-tab-pane>

        <!-- References Tab -->
        <el-tab-pane label="References" name="references">
          <el-card shadow="never">
            <el-table 
              :data="relatedData?.references || []" 
              border 
              size="small"
              :empty-text="'No reference records'"
              style="width: 100%"
            >
              <el-table-column label="Name" min-width="180" prop="ref_name" />
              <el-table-column label="Address" min-width="180" prop="ref_address" />
              <el-table-column label="Occupation" min-width="150" prop="ref_occupation" />
              <el-table-column label="Contact Number" width="160" align="center" prop="ref_contact_no" />
              <el-table-column label="Email" min-width="180" prop="ref_email" />
            </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Dependents Tab -->
        <el-tab-pane label="Dependents" name="dependents">
          <el-card shadow="never">
            <el-table 
              :data="relatedData?.dependents || []" 
              border 
              size="small"
              :empty-text="'No dependent records'"
              style="width: 100%"
            >
              <el-table-column label="Name" min-width="200" prop="name" />
              <el-table-column label="Relationship" min-width="180" prop="relationship" />
              <el-table-column label="Course" min-width="200" prop="course" />
            </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Contract Tab - COS employees only -->
        <el-tab-pane v-if="isCosEmployee" label="Contract" name="contract">
          <el-card shadow="never">
            <el-table
              :data="contractList"
              border
              size="small"
              :empty-text="'No contracts found'"
              style="width: 100%"
            >
              <el-table-column type="index" label="#" width="60" align="center" />
              <el-table-column label="Start Date" width="140">
                <template #default="{ row }">
                  {{ formatDate(row.Start_date) }}
                </template>
              </el-table-column>
              <el-table-column label="End Date" width="140">
                <template #default="{ row }">
                  {{ formatDate(row.End_date) }}
                </template>
              </el-table-column>
              <el-table-column label="Salary Grade" width="140">
                <template #default="{ row }">
                  {{ getSalaryGradeName(row.salary_grade_id) }}
                </template>
              </el-table-column>
              <el-table-column label="Salary Step" width="120">
                <template #default="{ row }">
                  {{ getSalaryStepName(row.salary_step_id) }}
                </template>
              </el-table-column>
              <el-table-column label="Monthly Service Fee" width="170">
                <template #default="{ row }">
                  {{ formatCurrency(row.salary) }}
                </template>
              </el-table-column>
              <el-table-column label="Active Status" width="120" align="center">
                <template #default="{ row }">
                  <el-tag :type="getContractStatusDisplay(row).type" size="small">
                    {{ getContractStatusDisplay(row).label }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column label="Attachment" min-width="220">
                <template #default="{ row }">
                  <span v-if="getContractFileLabel(row)" class="contract-file-name">
                    {{ getContractFileLabel(row) }}
                  </span>
                  <span v-else class="text-muted">No attachment</span>
                  <el-button
                    v-if="row.contract_file?.id"
                    type="primary"
                    link
                    size="small"
                    @click="downloadContractFile(row)"
                  >
                    Download
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-tab-pane>

        <!-- Documents Tab -->
        <el-tab-pane label="Documents" name="documents">
          <el-card shadow="never">
            <el-table 
              :data="documentsList" 
              border 
              size="small"
              :empty-text="'No documents found'"
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
            </el-table>
          </el-card>
        </el-tab-pane>
      </el-tabs>
      </div>
    </el-form>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible = false">Close</el-button>
        <el-button type="primary" @click="handleEdit" :disabled="!employeeData">Edit Employee</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { User } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import api from '@/services/api'
import { useCompany } from '@/composable/useCompany'

const { primaryCompany, fetchCompanies } = useCompany()
const agencyName = computed(() => primaryCompany.value?.name?.trim() || 'Company Name')

onMounted(() => {
  fetchCompanies()
})

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
const emit = defineEmits(['update:modelValue', 'edit-employee'])

// Reactive data
const activeTab = ref('work_information')
const loading = ref(false)
const documentsList = ref([])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const photoSrc = computed(() => {
  const photo = props.employeeData?.photo
  if (photo) {
    return `data:image/jpeg;base64,${photo}`
  }
  return ''
})

// Helper methods
const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString()
}

const formatCurrency = (amount) => {
  if (!amount && amount !== 0) return ''
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount)
}

const getLookupName = (id, lookupArray, field = 'name') => {
  if (!id || !lookupArray || !Array.isArray(lookupArray)) return ''
  const item = lookupArray.find(item => Number(item.id) === Number(id))
  return item ? (item[field] || '') : ''
}

const getAcademicLevel = (id) => {
  const levels = {
    1: 'Elementary',
    2: 'High School',
    3: 'College',
    4: 'Graduate Studies',
    5: 'Vocational/Trade'
  }
  return levels[id] || ''
}

const getGenderName = () => getLookupName(props.employeeData?.gender_id, props.formOptions?.genders)
const getBloodTypeName = () => getLookupName(props.employeeData?.blood_type_id, props.formOptions?.blood_types)
const getCivilStatusName = () => getLookupName(props.employeeData?.civil_status_id, props.formOptions?.civil_status)
const getDepartmentName = () => getLookupName(props.employeeData?.department_id, props.formOptions?.departments)
const getPositionName = () => getLookupName(props.employeeData?.position_id, props.formOptions?.positions)
const getEmploymentTypeName = () => getLookupName(props.employeeData?.employment_type_id, props.formOptions?.employment_types)

const isCosEmployee = computed(() => Number(props.employeeData?.employment_type_id) === 2)

const contractList = computed(() => {
  const contracts = props.relatedData?.cos_contracts
  if (!Array.isArray(contracts)) return []
  return contracts.map((contract) => ({
    id: contract.id || null,
    Start_date: contract.Start_date || '',
    End_date: contract.End_date || '',
    salary: contract.salary != null ? Number(contract.salary) : 0,
    salary_grade_id: contract.salary_grade_id != null ? Number(contract.salary_grade_id) : 0,
    salary_step_id: contract.salary_step_id != null ? Number(contract.salary_step_id) : 0,
    is_active: contract.is_active === true || contract.is_active === 1 || contract.is_active === '1',
    contract_file: contract.contract_file || null
  }))
})

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

const getContractFileLabel = (row) => row?.contract_file?.file_name || ''

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
const getPayrollIntervalName = () => getLookupName(props.employeeData?.payroll_interval_id, props.formOptions?.payroll_intervals)
const isPlantillaEmployee = () => {
  const v = props.employeeData?.is_plantilla
  if (v === true || v === 1 || v === '1') return true
  if (typeof v === 'string') {
    const s = v.trim().toLowerCase()
    if (s === 'true' || s === 'yes') return true
    if (s === 'false' || s === 'no' || s === '0' || s === '') return false
  }
  if (typeof v === 'number') return v === 1
  return false
}
const getPlantillaCode = () => {
  const plantillaId = props.employeeData?.plantilla_id
  if (!plantillaId) return ''
  const plantilla = Array.isArray(props.formOptions?.plantillas)
    ? props.formOptions.plantillas.find((p) => Number(p.id) === Number(plantillaId))
    : null
  return plantilla?.code || String(plantillaId)
}

// Salary grade/step helpers with graceful fallback labels
const getSalaryGradeName = (id) => {
  // Derive from plantilla if employee record doesn't carry the value
  if ((id === null || id === undefined || id === '') && props.employeeData?.plantilla_id) {
    const plantilla = Array.isArray(props.formOptions?.plantillas)
      ? props.formOptions.plantillas.find(p => Number(p.id) === Number(props.employeeData.plantilla_id))
      : null
    if (plantilla) {
      const derivedId =
        // Prefer explicit salary_grade_id
        (plantilla.salary_grade_id != null && plantilla.salary_grade_id !== '')
          ? Number(plantilla.salary_grade_id)
          // Some data sources swap grade/step — fall back to salary_step_id
          : (plantilla.salary_step_id != null && plantilla.salary_step_id !== '')
            ? Number(plantilla.salary_step_id)
            : null
      if (derivedId != null) {
        id = derivedId
      }
    }
  }
  if (!id && id !== 0) return ''
  const arr = props.formOptions?.salary_grades
  if (!arr || !Array.isArray(arr)) {
    return `Grade ${id}`
  }
  const item = arr.find(g => Number(g.id) === Number(id))
  return item ? (item.name || `Grade ${item.id}`) : `Grade ${id}`
}

const getSalaryStepName = (id) => {
  // Derive from plantilla if employee record doesn't carry the value
  if ((id === null || id === undefined || id === '') && props.employeeData?.plantilla_id) {
    const plantilla = Array.isArray(props.formOptions?.plantillas)
      ? props.formOptions.plantillas.find(p => Number(p.id) === Number(props.employeeData.plantilla_id))
      : null
    if (plantilla) {
      const derivedId =
        // Prefer explicit salary_step_id
        (plantilla.salary_step_id != null && plantilla.salary_step_id !== '')
          ? Number(plantilla.salary_step_id)
          // Some data sources swap grade/step — fall back to salary_grade_id
          : (plantilla.salary_grade_id != null && plantilla.salary_grade_id !== '')
            ? Number(plantilla.salary_grade_id)
            : null
      if (derivedId != null) {
        id = derivedId
      }
    }
  }
  if (!id && id !== 0) return ''
  const arr = props.formOptions?.salary_steps
  if (!arr || !Array.isArray(arr)) {
    return `Step ${id}`
  }
  const item = arr.find(s => Number(s.id) === Number(id))
  return item ? (item.name || `Step ${item.id}`) : `Step ${id}`
}

// Address lookup functions
const getRegionName = (code) => {
  if (!code || !props.formOptions?.regions || !Array.isArray(props.formOptions.regions)) return ''
  const region = props.formOptions.regions.find(r => r.regCode === code)
  return region ? (region.regDesc || '') : ''
}

const getProvinceName = (code) => {
  if (!code || !props.formOptions?.provinces || !Array.isArray(props.formOptions.provinces)) return ''
  const province = props.formOptions.provinces.find(p => p.provCode === code)
  return province ? (province.provDesc || '') : ''
}

const getCityName = (code) => {
  if (!code || !props.formOptions?.cities || !Array.isArray(props.formOptions.cities)) return ''
  const city = props.formOptions.cities.find(c => c.citymunCode === code)
  return city ? (city.citymunDesc || '') : ''
}

const getBarangayName = (code) => {
  if (!code || !props.formOptions?.barangays || !Array.isArray(props.formOptions.barangays)) return ''
  const barangay = props.formOptions.barangays.find(b => b.brgyCode === code)
  return barangay ? (barangay.brgyDesc || '') : ''
}

const handleEdit = () => {
  emit('edit-employee', props.employeeData)
  visible.value = false
}

// Load documents from attachments DB
const loadDocuments = async () => {
  const employeeId = props.employeeData?.id
  
  if (!employeeId) {
    documentsList.value = []
    return
  }
  
  try {
    const response = await api.get(`/employee-documents/${employeeId}/load`)
    
    // Handle API response format
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
    
    documentsList.value = documents.map(doc => {
      const docId = doc.employee_document_id || doc.id || doc.document_id || 0
      return {
        ...doc,
        employee_document_id: docId,
        id: docId
      }
    })
  } catch (error) {
    console.error('Load documents error:', error)
    documentsList.value = []
  }
}

// Watch for tab changes to load documents when Documents tab is accessed
watch(activeTab, (newTab) => {
  if (newTab === 'documents' && props.employeeData?.id) {
    loadDocuments()
  }
})

// Watch for modal visibility to clear documents when closed
watch(visible, (newVal) => {
  if (!newVal) {
    documentsList.value = []
  }
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
.mt-4 { margin-top: 16px; }
.mb-4 { margin-bottom: 16px; }
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

.contract-file-name {
  margin-right: 8px;
}

.text-muted {
  color: #909399;
  margin-right: 8px;
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
/* Readonly overlay to disable all interactions in dialog body */
.readonly-container {
  pointer-events: none;
}
.dialog-footer {
  /* Keep footer buttons usable */
  pointer-events: auto;
}
/* Allow tab navigation to remain clickable while content stays read-only */
.readonly-container :deep(.el-tabs__header),
.readonly-container :deep(.el-tabs__nav-wrap),
.readonly-container :deep(.el-tabs__nav-scroll),
.readonly-container :deep(.el-tabs__nav),
.readonly-container :deep(.el-tabs__item) {
  pointer-events: auto;
}
</style>
