<template>
  <div class="pds-display-container">
    <!-- Personal Information Display -->
    <el-descriptions title="Personal Information" border :column="3">
      <el-descriptions-item label="Applicant ID" :span="2">{{ pdsData.applicant_id || 'N/A' }}</el-descriptions-item>
      <el-descriptions-item
        :rowspan="12"
        label="Photo"
        align="center"
        class="photo-cell"
      >
        <div class="photo-container">
          <el-avatar 
            :size="100" 
            :src="applicantInfo?.photo ? `data:image/jpeg;base64,${applicantInfo.photo}` : null"
            shape="circle"
            class="photo-avatar"
          >
            <i class="el-icon-user" />
          </el-avatar>
        </div>
      </el-descriptions-item>
      <el-descriptions-item label="Full Name" :span="2">
        {{ [pdsData.prefix, pdsData.first_name, pdsData.middle_name, pdsData.last_name, pdsData.suffix].filter(Boolean).join(' ') || 'Not specified' }}
      </el-descriptions-item>
      <el-descriptions-item label="Gender" :span="2">{{ pdsData.gender || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Birth Date" :span="2">{{ pdsData.birth_date || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Age" :span="2">{{ pdsData.age || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Civil Status" :span="2">{{ pdsData.civil_status || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Citizenship" :span="2">{{ pdsData.citizenship || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Religion" :span="2">{{ pdsData.religion || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Blood Type" :span="2">{{ pdsData.blood_type || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Height" :span="2">{{ pdsData.height ? `${pdsData.height}m` : 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Weight" :span="2">{{ pdsData.weight ? `${pdsData.weight}kg` : 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Birth Place" :span="2">{{ pdsData.birth_place || 'Not specified' }}</el-descriptions-item>
    </el-descriptions>

    <!-- Contact Information Display -->
    <el-descriptions title="Contact Information" border style="margin-top: 20px">
      <el-descriptions-item label="Email">{{ pdsData.email }}</el-descriptions-item>
      <el-descriptions-item label="Mobile No.">{{ pdsData.mobile_no }}</el-descriptions-item>
      <el-descriptions-item label="Telephone No.">{{ pdsData.telephone_no || 'Not provided' }}</el-descriptions-item>
    </el-descriptions>

    <!-- Government IDs Display -->
    <el-descriptions title="Government IDs" border style="margin-top: 20px">
      <el-descriptions-item label="TIN No.">{{ pdsData.tin_no || 'Not provided' }}</el-descriptions-item>
      <el-descriptions-item label="GSIS No.">{{ pdsData.gsis_no || 'Not provided' }}</el-descriptions-item>
      <el-descriptions-item label="SSS No.">{{ pdsData.sss_no || 'Not provided' }}</el-descriptions-item>
      <el-descriptions-item label="HDMF Premium No.">{{ pdsData.hdmf_premium_no || 'Not provided' }}</el-descriptions-item>
      <el-descriptions-item label="PhilHealth No.">{{ pdsData.philhealth_no || 'Not provided' }}</el-descriptions-item>
    </el-descriptions>

    <!-- Current Address Display -->
    <el-descriptions title="Current Address" border style="margin-top: 20px">
      <el-descriptions-item label="Region">{{ currentRegionName || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Province">{{ currentProvinceName || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Municipality/City">{{ currentCityMunName || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Barangay/Purok">{{ currentBarangayName || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="House No.">{{ pdsData.current_house_no || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Street">{{ pdsData.current_street || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Village/Subdivision">{{ pdsData.current_village || 'Not specified' }}</el-descriptions-item>
    </el-descriptions>

    <!-- Permanent Address Display -->
    <el-descriptions title="Permanent Address" border style="margin-top: 20px">
      <el-descriptions-item label="Region">{{ permanentRegionName || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Province">{{ permanentProvinceName || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Municipality/City">{{ permanentCityMunName || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Barangay/Purok">{{ permanentBarangayName || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="House No.">{{ pdsData.permanent_house_no || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Street">{{ pdsData.permanent_street || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Village/Subdivision">{{ pdsData.permanent_village || 'Not specified' }}</el-descriptions-item>
    </el-descriptions>

    <!-- Family Information Display -->
    <el-descriptions title="Family Information" border style="margin-top: 20px">
      <el-descriptions-item label="Father's Name">
        {{ pdsData.father_prefix }} {{ pdsData.father_first_name }} {{ pdsData.father_middle_name }} {{ pdsData.father_last_name }} {{ pdsData.father_suffix }}
      </el-descriptions-item>
      <el-descriptions-item label="Mother's Maiden Name">
        {{ pdsData.mother_prefix }} {{ pdsData.mother_first_name }} {{ pdsData.mother_middle_name }} {{ pdsData.mother_surname }} {{ pdsData.mother_suffix }}
      </el-descriptions-item>
      <el-descriptions-item label="Spouse's Name">
        {{ pdsData.spouse_prefix }} {{ pdsData.spouse_first_name }} {{ pdsData.spouse_middle_name }} {{ pdsData.spouse_last_name }} {{ pdsData.spouse_suffix }}
      </el-descriptions-item>
      <el-descriptions-item label="Spouse Occupation">{{ pdsData.spouse_occupation || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Spouse Employer">{{ pdsData.spouse_employer || 'Not specified' }}</el-descriptions-item>
      <el-descriptions-item label="Spouse Work Address">{{ pdsData.spouse_work_address || 'Not specified' }}</el-descriptions-item>
    </el-descriptions>

    <!-- Dual Citizenship Display - Collapsible -->
    <el-descriptions title="Dual Citizenship Information" border style="margin-top: 20px">
      <el-descriptions-item label="With Dual Citizenship?">
        <el-checkbox 
          v-model="pdsData.has_dual_citizenship" 
          disabled
          :label="pdsData.has_dual_citizenship ? 'Yes' : 'No'"
        />
      </el-descriptions-item>
      <el-descriptions-item v-if="pdsData.has_dual_citizenship" label="Type">
        {{ pdsData.dual_citizenship_type === 'by_birth' ? 'By Birth' : 'By Naturalization' }}
      </el-descriptions-item>
      <el-descriptions-item v-if="pdsData.has_dual_citizenship" label="Country of Origin">
        {{ pdsData.dual_citizenship_country || 'Not specified' }}
      </el-descriptions-item>
    </el-descriptions>
  </div>
</template>

<script setup>
import { defineProps, computed } from 'vue'
import refRegion from '@/assets/refregion.json'
import refProvince from '@/assets/refprovince.json'
import refCityMun from '@/assets/refcitymun.json'
import refBrgy from '@/assets/refbrgy.json'

const props = defineProps({
  applicantInfo: {
    type: Object,
    default: () => null
  },
  pdsData: {
    type: Object,
    default: () => ({})
  }
})

// Helper function to get region name by code
const getRegionName = (regionCode) => {
  if (!regionCode) return ''
  const region = refRegion.RECORDS.find(r => r.regCode === String(regionCode))
  return region ? region.regDesc : regionCode
}

// Helper function to get province name by code
const getProvinceName = (provinceCode) => {
  if (!provinceCode) return ''
  const province = refProvince.RECORDS.find(p => p.provCode === String(provinceCode))
  return province ? province.provDesc : provinceCode
}

// Helper function to get city/municipality name by code
const getCityMunName = (cityMunCode) => {
  if (!cityMunCode) return ''
  const cityMun = refCityMun.RECORDS.find(c => c.citymunCode === String(cityMunCode))
  return cityMun ? cityMun.citymunDesc : cityMunCode
}

// Helper function to get barangay name by code
const getBarangayName = (barangayCode) => {
  if (!barangayCode) return ''
  const barangay = refBrgy.RECORDS.find(b => b.brgyCode === String(barangayCode))
  return barangay ? barangay.brgyDesc : barangayCode
}

// Computed properties for current address
const currentRegionName = computed(() => getRegionName(props.pdsData.current_region))
const currentProvinceName = computed(() => getProvinceName(props.pdsData.current_province))
const currentCityMunName = computed(() => getCityMunName(props.pdsData.current_municipality))
const currentBarangayName = computed(() => getBarangayName(props.pdsData.current_barangay))

// Computed properties for permanent address
const permanentRegionName = computed(() => getRegionName(props.pdsData.permanent_region))
const permanentProvinceName = computed(() => getProvinceName(props.pdsData.permanent_province))
const permanentCityMunName = computed(() => getCityMunName(props.pdsData.permanent_municipality))
const permanentBarangayName = computed(() => getBarangayName(props.pdsData.permanent_barangay))
</script>

<style scoped>
.pds-display-container {
  padding: 20px;
  background: white;
}

.el-descriptions {
  margin-bottom: 20px;
}

.el-descriptions__title {
  font-size: 16px;
  font-weight: 600;
  color: #374151;
}

.el-descriptions__label {
  font-weight: 500;
  color: #6b7280;
}

.el-descriptions__content {
  color: #111827;
}

:deep(.photo-cell) {
  vertical-align: top;
  text-align: center;
}

:deep(.el-descriptions__table) {
  table-layout: fixed;
  width: 100%;
}

:deep(.el-descriptions__table .el-descriptions__cell) {
  vertical-align: top;
}

:deep(.photo-cell) {
  vertical-align: top !important;
  text-align: center;
  width: 150px;
}

:deep(.photo-cell .el-descriptions__cell) {
  vertical-align: top !important;
  padding: 8px 10px !important;
  width: 150px;
}

.photo-container {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 0;
  margin: 0;
}

.photo-avatar {
  border: 2px solid #e4e7ed;
  flex-shrink: 0;
}

:deep(.el-descriptions__label) {
  font-weight: 600;
  width: 150px;
}

:deep(.el-descriptions__content) {
  font-weight: normal;
}
</style>
