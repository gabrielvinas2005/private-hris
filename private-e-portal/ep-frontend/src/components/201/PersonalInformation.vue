<template>
  <div class="space-y-6">
    <!-- Read Mode View -->
    <template v-if="!isEditMode">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Contact & Basic Info Card -->
        <el-card shadow="never" class="border border-slate-200">
          <template #header>
            <div class="flex items-center space-x-2 font-semibold text-slate-800">
              <el-icon class="text-primary-600"><User /></el-icon>
              <span>Contact Information</span>
            </div>
          </template>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b border-slate-100 pb-2">
              <span class="text-slate-500 font-medium">Email Address</span>
              <span class="text-slate-900 font-medium">{{ employee?.email || '-' }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
              <span class="text-slate-500 font-medium">Mobile Number</span>
              <span class="text-slate-900 font-medium">{{ employee?.mobile_no || '-' }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
              <span class="text-slate-500 font-medium">Telephone Number</span>
              <span class="text-slate-900 font-medium">{{ employee?.telephone_no || '-' }}</span>
            </div>
          </div>
        </el-card>

        <!-- Personal Details Summary Card -->
        <el-card shadow="never" class="border border-slate-200">
          <template #header>
            <div class="flex items-center space-x-2 font-semibold text-slate-800">
              <el-icon class="text-primary-600"><Postcard /></el-icon>
              <span>Personal Details</span>
            </div>
          </template>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b border-slate-100 pb-2">
              <span class="text-slate-500 font-medium">Date of Birth</span>
              <span class="text-slate-900 font-medium">{{ formatDate(employee?.birthdate || employee?.date_of_birth) }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
              <span class="text-slate-500 font-medium">Place of Birth</span>
              <span class="text-slate-900 font-medium">{{ employee?.place_of_birth || '-' }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
              <span class="text-slate-500 font-medium">Gender / Sex</span>
              <span class="text-slate-900 font-medium">{{ employee?.gender || getOptionLabel(genderOptions, employee?.sex_id || employee?.gender_id) }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
              <span class="text-slate-500 font-medium">Civil Status</span>
              <span class="text-slate-900 font-medium">{{ employee?.civil_status || getOptionLabel(civilStatusOptions, employee?.civil_status_id) }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
              <span class="text-slate-500 font-medium">Citizenship</span>
              <span class="text-slate-900 font-medium">{{ employee?.citizenship || '-' }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
              <span class="text-slate-500 font-medium">Blood Type</span>
              <span class="text-slate-900 font-medium">{{ employee?.blood_type || getOptionLabel(bloodTypeOptions, employee?.blood_type_id) }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
              <span class="text-slate-500 font-medium">Religion</span>
              <span class="text-slate-900 font-medium">{{ employee?.religion || getOptionLabel(religionOptions, employee?.religion_id) }}</span>
            </div>
          </div>
        </el-card>
      </div>

      <!-- Address Details Section -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Current Address -->
        <el-card shadow="never" class="border border-slate-200">
          <template #header>
            <div class="flex items-center space-x-2 font-semibold text-slate-800">
              <el-icon class="text-primary-600"><Location /></el-icon>
              <span>Current Residential Address</span>
            </div>
          </template>
          <p class="text-sm text-slate-700 leading-relaxed font-normal">
            {{ formatAddress(currentAddress) }}
          </p>
        </el-card>

        <!-- Permanent Address -->
        <el-card shadow="never" class="border border-slate-200">
          <template #header>
            <div class="flex items-center space-x-2 font-semibold text-slate-800">
              <el-icon class="text-primary-600"><HomeFilled /></el-icon>
              <span>Permanent Address</span>
            </div>
          </template>
          <p class="text-sm text-slate-700 leading-relaxed font-normal">
            {{ formatAddress(permanentAddress) }}
          </p>
        </el-card>
      </div>
    </template>

    <!-- Edit Mode View -->
    <template v-else>
      <el-form :model="localFormData" label-width="140px" label-position="left" :disabled="!canUpdate">
        <!-- Contact & Personal Details Edit Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Contact Information Section -->
          <el-card shadow="never" class="border border-slate-200">
            <template #header>
              <span class="font-semibold text-slate-800">Edit Contact Information</span>
            </template>
            <div class="space-y-3">
              <el-form-item label="Email">
                <el-input v-model="localFormData.email" @input="updateField('email', $event)" placeholder="Email Address" />
              </el-form-item>
              <el-form-item label="Mobile No.">
                <el-input v-model="localFormData.mobile_no" @input="updateField('mobile_no', $event)" placeholder="Mobile Number" />
              </el-form-item>
              <el-form-item label="Telephone No.">
                <el-input v-model="localFormData.telephone_no" @input="updateField('telephone_no', $event)" placeholder="Telephone Number" />
              </el-form-item>
            </div>
          </el-card>

          <!-- Personal Details Section -->
          <el-card shadow="never" class="border border-slate-200">
            <template #header>
              <span class="font-semibold text-slate-800">Edit Personal Details</span>
            </template>
            <div class="space-y-3">
              <el-form-item label="Birth Date">
                <el-date-picker v-model="localFormData.birthdate" type="date" format="YYYY-MM-DD" value-format="YYYY-MM-DD" class="w-full" :disabled="!hasHrmAccess" @change="updateField('birthdate', $event)" />
                <p v-if="!hasHrmAccess" class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-medium">To change birthdate, please submit a request to HR.</p>
              </el-form-item>
              <el-form-item label="Birth Place">
                <el-input v-model="localFormData.birth_place" @input="updateField('birth_place', $event)" placeholder="Place of Birth" class="w-full" />
              </el-form-item>
              <el-form-item label="Age">
                <el-input :model-value="computedAgeDisplay" class="w-full" :disabled="true" placeholder="Auto-calculated from birthdate" />
                <p class="text-xs text-slate-500 mt-1">Automatically calculated from birthdate.</p>
              </el-form-item>
              <el-form-item label="Blood Type">
                <el-select v-model="localFormData.blood_type_id" placeholder="Select Blood Type" class="w-full" :disabled="!hasHrmAccess" @change="updateField('blood_type_id', $event)">
                  <el-option v-for="item in bloodTypeOptions" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
                <p v-if="!hasHrmAccess" class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-medium">To change blood type, please submit a request to HR.</p>
              </el-form-item>
              <el-form-item label="Citizenship">
                <el-input v-model="localFormData.citizenship" placeholder="Citizenship" class="w-full" :disabled="!hasHrmAccess" @input="updateField('citizenship', $event)" />
                <p v-if="!hasHrmAccess" class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-medium">To change citizenship, please submit a request to HR.</p>
              </el-form-item>
              <el-form-item label="Gender">
                <el-select v-model="localFormData.gender_id" @change="updateField('gender_id', $event)" placeholder="Select Gender" class="w-full">
                  <el-option v-for="item in genderOptions" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
              </el-form-item>
              <el-form-item label="Civil Status">
                <el-select v-model="localFormData.civil_status_id" @change="updateField('civil_status_id', $event)" placeholder="Select Civil Status" class="w-full">
                  <el-option v-for="item in civilStatusOptions" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
              </el-form-item>
              <el-form-item label="Religion">
                <el-select v-model="localFormData.religion_id" @change="updateField('religion_id', $event)" placeholder="Select Religion" class="w-full">
                  <el-option v-for="item in religionOptions" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
              </el-form-item>
            </div>
          </el-card>
        </div>

        <!-- Current Address Section -->
        <el-card shadow="never" class="mb-6 border border-slate-200">
          <template #header>
            <span class="font-semibold text-slate-800">Edit Current Address</span>
          </template>
          <el-row :gutter="24">
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="House/Block/Lot">
                <el-input v-model="localFormData.ra_house_no" @input="updateField('ra_house_no', $event)" placeholder="House/Block No." />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="Street">
                <el-input v-model="localFormData.ra_street" @input="updateField('ra_street', $event)" placeholder="Street" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="Subdivision/Village">
                <el-input v-model="localFormData.ra_village" @input="updateField('ra_village', $event)" placeholder="Subdivision/Village" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="Region">
                <el-select v-model="localFormData.ra_region" @change="handleRaRegionChange" placeholder="Select Region" class="w-full" filterable>
                  <el-option v-for="item in regionOptions" :key="item.regCode" :label="item.regDesc" :value="item.regCode" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="Province">
                <el-select v-model="localFormData.ra_province" @change="handleRaProvinceChange" placeholder="Select Province" class="w-full" filterable :disabled="!localFormData.ra_region">
                  <el-option v-for="item in filteredRaProvinces" :key="item.provCode" :label="item.provDesc" :value="item.provCode" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="City/Municipality">
                <el-select v-model="localFormData.ra_city" @change="handleRaCityChange" placeholder="Select City/Municipality" class="w-full" filterable :disabled="!localFormData.ra_province">
                  <el-option v-for="item in filteredRaCities" :key="item.citymunCode" :label="item.citymunDesc" :value="item.citymunCode" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="Barangay">
                <el-select v-model="localFormData.ra_barangay" @change="updateField('ra_barangay', $event)" placeholder="Select Barangay" class="w-full" filterable :disabled="!localFormData.ra_city">
                  <el-option v-for="item in filteredRaBarangays" :key="item.brgyCode" :label="item.brgyDesc" :value="item.brgyCode" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
        </el-card>

        <!-- Permanent Address Section -->
        <el-card shadow="never" class="border border-slate-200">
          <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <span class="font-semibold text-slate-800">Edit Permanent Address</span>
              <el-checkbox v-model="sameAsCurrentAddress" @change="syncCurrentToPermanent">
                Permanent address is the same as current address
              </el-checkbox>
            </div>
          </template>
          <el-row :gutter="24">
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="House/Block/Lot">
                <el-input v-model="localFormData.pa_house_no" @input="updateField('pa_house_no', $event)" placeholder="House/Block No." :disabled="sameAsCurrentAddress || !canUpdate" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="Street">
                <el-input v-model="localFormData.pa_street" @input="updateField('pa_street', $event)" placeholder="Street" :disabled="sameAsCurrentAddress || !canUpdate" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="8">
              <el-form-item label="Subdivision/Village">
                <el-input v-model="localFormData.pa_village" @input="updateField('pa_village', $event)" placeholder="Subdivision/Village" :disabled="sameAsCurrentAddress || !canUpdate" />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="Region">
                <el-select v-model="localFormData.pa_region" @change="handlePaRegionChange" placeholder="Select Region" class="w-full" filterable :disabled="sameAsCurrentAddress || !canUpdate">
                  <el-option v-for="item in regionOptions" :key="item.regCode" :label="item.regDesc" :value="item.regCode" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="Province">
                <el-select v-model="localFormData.pa_province" @change="handlePaProvinceChange" placeholder="Select Province" class="w-full" filterable :disabled="sameAsCurrentAddress || !canUpdate || !localFormData.pa_region">
                  <el-option v-for="item in filteredPaProvinces" :key="item.provCode" :label="item.provDesc" :value="item.provCode" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="City/Municipality">
                <el-select v-model="localFormData.pa_city" @change="handlePaCityChange" placeholder="Select City/Municipality" class="w-full" filterable :disabled="sameAsCurrentAddress || !canUpdate || !localFormData.pa_province">
                  <el-option v-for="item in filteredPaCities" :key="item.citymunCode" :label="item.citymunDesc" :value="item.citymunCode" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6">
              <el-form-item label="Barangay">
                <el-select v-model="localFormData.pa_barangay" @change="updateField('pa_barangay', $event)" placeholder="Select Barangay" class="w-full" filterable :disabled="sameAsCurrentAddress || !canUpdate || !localFormData.pa_city">
                  <el-option v-for="item in filteredPaBarangays" :key="item.brgyCode" :label="item.brgyDesc" :value="item.brgyCode" />
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
        </el-card>
      </el-form>
    </template>
  </div>
</template>

<script>
import { User, Postcard, Location, HomeFilled } from '@element-plus/icons-vue'

export default {
  name: 'PersonalInformation',
  components: {
    User,
    Postcard,
    Location,
    HomeFilled
  },
  props: {
    employee: { type: Object, default: () => ({}) },
    address: { type: Object, default: () => ({}) },
    isEditMode: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    formData: { type: Object, default: () => ({}) },
    genderOptions: { type: Array, default: () => [] },
    bloodTypeOptions: { type: Array, default: () => [] },
    civilStatusOptions: { type: Array, default: () => [] },
    religionOptions: { type: Array, default: () => [] },
    regionOptions: { type: Array, default: () => [] },
    provinceOptions: { type: Array, default: () => [] },
    cityOptions: { type: Array, default: () => [] },
    barangayOptions: { type: Array, default: () => [] }
  },
  data() {
    return {
      sameAsCurrentAddress: false,
      localFormData: {}
    }
  },
  watch: {
    formData: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.localFormData = this.normalizeFormData(newVal)
          if (this.sameAsCurrentAddress) {
            this.syncCurrentToPermanent()
          }
        }
      },
      deep: true
    },
    cityOptions: {
      immediate: true,
      handler() {
        if (this.formData && Object.keys(this.formData).length > 0) {
          this.localFormData = this.normalizeFormData(this.formData)
        }
      }
    },
    provinceOptions: {
      immediate: true,
      handler() {
        if (this.formData && Object.keys(this.formData).length > 0) {
          this.localFormData = this.normalizeFormData(this.formData)
        }
      }
    }
  },
  computed: {
    hasHrmAccess() {
      try {
        const stored = localStorage.getItem('user_data')
        if (stored) {
          const user = JSON.parse(stored)
          return !!(user.with_hrm_access || user.is_admin || Number(user.with_hrm_access) === 1 || Number(user.is_admin) === 1)
        }
      } catch (e) {}
      return false
    },
    currentAddress() {
      return {
        house_no: this.employee?.ra_house_no || '',
        village: this.employee?.ra_village || '',
        street: this.employee?.ra_street || '',
        barangay: this.address?.ra_brgy || this.address?.ra_barangay || this.employee?.ra_barangay || '',
        city: this.address?.ra_city || this.employee?.ra_city || '',
        province: this.address?.ra_province || this.employee?.ra_province || '',
        region: this.address?.ra_region || this.employee?.ra_region || ''
      }
    },
    computedAgeDisplay() {
      const bDate = this.localFormData?.birthdate || this.employee?.birthdate || this.employee?.date_of_birth
      if (bDate) {
        const birth = new Date(bDate)
        if (!isNaN(birth.getTime())) {
          const today = new Date()
          let age = today.getFullYear() - birth.getFullYear()
          const monthDiff = today.getMonth() - birth.getMonth()
          if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--
          }
          return age >= 0 ? `${age} years old` : '-'
        }
      }
      return this.employee?.age ? `${this.employee.age} years old` : '-'
    },
    permanentAddress() {
      return {
        house_no: this.employee?.pa_house_no || '',
        village: this.employee?.pa_village || '',
        street: this.employee?.pa_street || '',
        barangay: this.address?.pa_brgy || this.address?.pa_barangay || this.employee?.pa_barangay || '',
        city: this.address?.pa_city || this.employee?.pa_city || '',
        province: this.address?.pa_province || this.employee?.pa_province || '',
        region: this.address?.pa_region || this.employee?.pa_region || ''
      }
    },
    // Cascading options for Current Address
    filteredRaProvinces() {
      if (!this.localFormData.ra_region || !this.provinceOptions.length) return []
      const reg = String(this.localFormData.ra_region).trim()
      return this.provinceOptions.filter(p => {
        const pReg = String(p.regCode || '').trim()
        return pReg === reg || reg.startsWith(pReg) || pReg.startsWith(reg)
      })
    },
    filteredRaCities() {
      if (!this.localFormData.ra_province || !this.cityOptions.length) return []
      const prov = String(this.localFormData.ra_province).trim()
      return this.cityOptions.filter(c => {
        const cProv = String(c.provCode || '').trim()
        return cProv === prov || prov.startsWith(cProv) || cProv.startsWith(prov)
      })
    },
    filteredRaBarangays() {
      if (!this.localFormData.ra_city || !this.barangayOptions.length) return []
      const city = String(this.localFormData.ra_city).trim()
      return this.barangayOptions.filter(b => {
        const bCity = String(b.citymunCode || '').trim()
        return bCity === city || city.startsWith(bCity) || bCity.startsWith(city)
      })
    },
    // Cascading options for Permanent Address
    filteredPaProvinces() {
      if (!this.localFormData.pa_region || !this.provinceOptions.length) return []
      const reg = String(this.localFormData.pa_region).trim()
      return this.provinceOptions.filter(p => {
        const pReg = String(p.regCode || '').trim()
        return pReg === reg || reg.startsWith(pReg) || pReg.startsWith(reg)
      })
    },
    filteredPaCities() {
      if (!this.localFormData.pa_province || !this.cityOptions.length) return []
      const prov = String(this.localFormData.pa_province).trim()
      return this.cityOptions.filter(c => {
        const cProv = String(c.provCode || '').trim()
        return cProv === prov || prov.startsWith(cProv) || cProv.startsWith(prov)
      })
    },
    filteredPaBarangays() {
      if (!this.localFormData.pa_city || !this.barangayOptions.length) return []
      const city = String(this.localFormData.pa_city).trim()
      return this.barangayOptions.filter(b => {
        const bCity = String(b.citymunCode || '').trim()
        return bCity === city || city.startsWith(bCity) || bCity.startsWith(city)
      })
    }
  },
  methods: {
    syncCurrentToPermanent() {
      if (!this.sameAsCurrentAddress) return
      this.localFormData.pa_house_no = this.localFormData.ra_house_no || ''
      this.localFormData.pa_street = this.localFormData.ra_street || ''
      this.localFormData.pa_village = this.localFormData.ra_village || ''
      this.localFormData.pa_region = this.localFormData.ra_region || ''
      this.localFormData.pa_province = this.localFormData.ra_province || ''
      this.localFormData.pa_city = this.localFormData.ra_city || ''
      this.localFormData.pa_barangay = this.localFormData.ra_barangay || ''

      this.updateField('pa_house_no', this.localFormData.pa_house_no)
      this.updateField('pa_street', this.localFormData.pa_street)
      this.updateField('pa_village', this.localFormData.pa_village)
      this.updateField('pa_region', this.localFormData.pa_region)
      this.updateField('pa_province', this.localFormData.pa_province)
      this.updateField('pa_city', this.localFormData.pa_city)
      this.updateField('pa_barangay', this.localFormData.pa_barangay)
    },
    normalizeFormData(data) {
      const norm = { ...data }
      // Normalize citizenship if empty
      if (!norm.citizenship) {
        norm.citizenship = this.employee?.citizenship || this.getOptionLabel(this.citizenshipOptions || [], norm.citizenship_id) || 'Filipino'
      }

      // Reverse address lookup: use city code first to derive correct parent province & region codes
      const rawCity = String(norm.ra_city || '').trim()
      if (rawCity && this.cityOptions.length) {
        const matchCity = this.cityOptions.find(c =>
          String(c.citymunCode) === rawCity ||
          rawCity.startsWith(String(c.citymunCode)) ||
          String(c.citymunCode).startsWith(rawCity)
        )
        if (matchCity) {
          norm.ra_city = matchCity.citymunCode
          if (matchCity.provCode) {
            norm.ra_province = matchCity.provCode
            const matchProv = this.provinceOptions.find(p => String(p.provCode) === String(matchCity.provCode))
            if (matchProv && matchProv.regCode) {
              norm.ra_region = matchProv.regCode
            }
          }
        }
      }

      const rawPaCity = String(norm.pa_city || '').trim()
      if (rawPaCity && this.cityOptions.length) {
        const matchCity = this.cityOptions.find(c =>
          String(c.citymunCode) === rawPaCity ||
          rawPaCity.startsWith(String(c.citymunCode)) ||
          String(c.citymunCode).startsWith(rawPaCity)
        )
        if (matchCity) {
          norm.pa_city = matchCity.citymunCode
          if (matchCity.provCode) {
            norm.pa_province = matchCity.provCode
            const matchProv = this.provinceOptions.find(p => String(p.provCode) === String(matchCity.provCode))
            if (matchProv && matchProv.regCode) {
              norm.pa_region = matchProv.regCode
            }
          }
        }
      }

      // Fallback region/province string resolution if no city match
      if (norm.ra_region && this.regionOptions.length) {
        const str = String(norm.ra_region).trim()
        const matchReg = this.regionOptions.find(r => String(r.regCode) === str || String(r.regDesc).toLowerCase() === str.toLowerCase())
        if (matchReg) norm.ra_region = matchReg.regCode
      }
      if (norm.ra_province && this.provinceOptions.length) {
        const str = String(norm.ra_province).trim()
        const matchProv = this.provinceOptions.find(p => String(p.provCode) === str || str.startsWith(String(p.provCode)) || String(p.provCode).startsWith(str) || String(p.provDesc).toLowerCase() === str.toLowerCase())
        if (matchProv) norm.ra_province = matchProv.provCode
      }
      if (norm.pa_region && this.regionOptions.length) {
        const str = String(norm.pa_region).trim()
        const matchReg = this.regionOptions.find(r => String(r.regCode) === str || String(r.regDesc).toLowerCase() === str.toLowerCase())
        if (matchReg) norm.pa_region = matchReg.regCode
      }
      if (norm.pa_province && this.provinceOptions.length) {
        const str = String(norm.pa_province).trim()
        const matchProv = this.provinceOptions.find(p => String(p.provCode) === str || str.startsWith(String(p.provCode)) || String(p.provCode).startsWith(str) || String(p.provDesc).toLowerCase() === str.toLowerCase())
        if (matchProv) norm.pa_province = matchProv.provCode
      }

      return norm
    },
    resolveRegionName(val) {
      if (!val) return ''
      const str = String(val).trim()
      const match = this.regionOptions.find(r => String(r.regCode) === str || String(r.regDesc).toLowerCase() === str.toLowerCase())
      return match ? match.regDesc : str
    },
    resolveProvinceName(val) {
      if (!val) return ''
      const str = String(val).trim()
      const match = this.provinceOptions.find(p => String(p.provCode) === str || str.startsWith(String(p.provCode)) || String(p.provCode).startsWith(str) || String(p.provDesc).toLowerCase() === str.toLowerCase())
      return match ? match.provDesc : str
    },
    resolveCityName(val) {
      if (!val) return ''
      const str = String(val).trim()
      const match = this.cityOptions.find(c => String(c.citymunCode) === str || str.startsWith(String(c.citymunCode)) || String(c.citymunCode).startsWith(str) || String(c.citymunDesc).toLowerCase() === str.toLowerCase())
      return match ? match.citymunDesc : str
    },
    resolveBarangayName(val) {
      if (!val) return ''
      const str = String(val).trim()
      const match = this.barangayOptions.find(b => String(b.brgyCode) === str || str.startsWith(String(b.brgyCode)) || String(b.brgyCode).startsWith(str) || String(b.brgyDesc).toLowerCase() === str.toLowerCase())
      return match ? match.brgyDesc : str
    },
    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
    },
    formatAddress(addr) {
      const parts = []
      if (addr.house_no) parts.push(addr.house_no)
      if (addr.village) parts.push(addr.village)
      if (addr.street) parts.push(addr.street)
      const bName = this.resolveBarangayName(addr.barangay)
      if (bName) parts.push(`Brgy. ${bName}`)
      else if (addr.barangay) parts.push(`Brgy. ${addr.barangay}`)
      const cName = this.resolveCityName(addr.city)
      if (cName) parts.push(cName)
      else if (addr.city) parts.push(addr.city)
      const pName = this.resolveProvinceName(addr.province)
      const rName = this.resolveRegionName(addr.region)
      // Avoid duplicate entries: only add province if it differs from city and region, and region is not NCR
      const isNCR = rName && rName.toUpperCase().includes('NCR')
      if (!isNCR) {
        if (pName && pName !== cName && pName !== rName) parts.push(pName)
        else if (!pName && addr.province) parts.push(addr.province)
      }
      // Avoid duplicate region if already covered by city or province
      if (rName && rName !== cName && rName !== pName) parts.push(rName)
      else if (!rName && addr.region) parts.push(addr.region)
      return parts.length > 0 ? parts.join(', ') : 'No address provided'
    },
    getOptionLabel(options, id) {
      if (!id || !options || !options.length) return '-'
      const match = options.find(opt => String(opt.id || opt.code || opt.value) === String(id))
      return match ? (match.name || match.label || match.description || match.title) : '-'
    },
    updateField(field, value) {
      this.$emit('update:form-data', { field, value })
      if (this.sameAsCurrentAddress && field.startsWith('ra_')) {
        this.syncCurrentToPermanent()
      }
    },
    handleRaRegionChange(val) {
      this.localFormData.ra_region = val
      this.updateField('ra_region', val)
      this.localFormData.ra_province = ''
      this.localFormData.ra_city = ''
      this.localFormData.ra_barangay = ''
      this.updateField('ra_province', '')
      this.updateField('ra_city', '')
      this.updateField('ra_barangay', '')
      if (this.sameAsCurrentAddress) this.syncCurrentToPermanent()
    },
    handleRaProvinceChange(val) {
      this.localFormData.ra_province = val
      this.updateField('ra_province', val)
      this.localFormData.ra_city = ''
      this.localFormData.ra_barangay = ''
      this.updateField('ra_city', '')
      this.updateField('ra_barangay', '')
      if (this.sameAsCurrentAddress) this.syncCurrentToPermanent()
    },
    handleRaCityChange(val) {
      this.localFormData.ra_city = val
      this.updateField('ra_city', val)
      this.localFormData.ra_barangay = ''
      this.updateField('ra_barangay', '')
      if (this.sameAsCurrentAddress) this.syncCurrentToPermanent()
    },
    handlePaRegionChange(val) {
      this.localFormData.pa_region = val
      this.updateField('pa_region', val)
      this.localFormData.pa_province = ''
      this.localFormData.pa_city = ''
      this.localFormData.pa_barangay = ''
      this.updateField('pa_province', '')
      this.updateField('pa_city', '')
      this.updateField('pa_barangay', '')
    },
    handlePaProvinceChange(val) {
      this.localFormData.pa_province = val
      this.updateField('pa_province', val)
      this.localFormData.pa_city = ''
      this.localFormData.pa_barangay = ''
      this.updateField('pa_city', '')
      this.updateField('pa_barangay', '')
    },
    handlePaCityChange(val) {
      this.localFormData.pa_city = val
      this.updateField('pa_city', val)
      this.localFormData.pa_barangay = ''
      this.updateField('pa_barangay', '')
    }
  }
}
</script>
