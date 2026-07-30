<template>
  <el-card shadow="hover" class="mb-6">
    <el-form 
      :model="isEditMode ? localFormData : employee" 
      label-width="140px" 
      label-position="left"
      :disabled="!isEditMode || !canUpdate"
    >
      <el-row :gutter="24">
        <!-- Left Column: Profile Photo and Contact Info -->
        <el-col :xs="24" :sm="24" :md="8" :lg="6">
          <div class="text-center mb-6">
            <!-- Profile Photo -->
            <div class="relative inline-block mb-4">
              <div class="w-32 h-32 rounded-full border-4 border-gray-200 overflow-hidden bg-gray-100 flex items-center justify-center mx-auto">
                <template v-if="hasPhoto">
                  <img
                    :src="computedImageSrc"
                    :alt="employee.name"
                    class="w-full h-full object-cover transition-opacity duration-200"
                    :class="{ 'opacity-0': !isImageLoaded, 'opacity-100': isImageLoaded }"
                    @load="handleImageLoad"
                    @error="handleImageError"
                  />
                </template>
                <template v-else>
                  <el-icon :size="64" color="#9ca3af">
                    <User />
                  </el-icon>
                </template>
              </div>
              <div class="absolute -bottom-2 -right-2 w-8 h-8 bg-green-500 rounded-full border-2 border-white"></div>
            </div>
            
            <!-- Employee Name -->
            <h3 v-if="!isEditable" class="text-lg font-semibold text-gray-900 mb-2">
              {{ employee.name || '-' }}
            </h3>
            <div v-else class="space-y-2 mb-2">
              <el-form-item label="" prop="first_name">
                <el-input
                  v-model="localFormData.first_name"
                  @input="updateField('first_name', $event)"
                  placeholder="First Name"
                />
              </el-form-item>
              <el-form-item label="" prop="middle_name">
                <el-input
                  v-model="localFormData.middle_name"
                  @input="updateField('middle_name', $event)"
                  placeholder="Middle Name"
                />
              </el-form-item>
              <el-form-item label="" prop="last_name">
                <el-input
                  v-model="localFormData.last_name"
                  @input="updateField('last_name', $event)"
                  placeholder="Last Name"
                />
              </el-form-item>
            </div>
            
            <!-- Employee Number -->
            <p class="text-sm text-gray-600 mb-4">
              <strong>Employee No:</strong> {{ employee.employee_no || '-' }}
            </p>

            <!-- Contact Information -->
            <el-divider content-position="left">Contact Information</el-divider>
            
            <el-form-item label="Email" prop="email">
              <span v-if="!isEditMode || !canUpdate" class="text-gray-700">{{ employee?.email || '-' }}</span>
              <el-input
                v-else
                v-model="localFormData.email"
                @input="updateField('email', $event)"
                placeholder="Email"
                :disabled="!canUpdate"
              />
            </el-form-item>
            
            <el-form-item label="Mobile No." prop="mobile_no">
              <span v-if="!isEditMode || !canUpdate" class="text-gray-700">{{ employee?.mobile_no || '-' }}</span>
              <el-input
                v-else
                v-model="localFormData.mobile_no"
                @input="updateField('mobile_no', $event)"
                placeholder="Mobile No."
                :disabled="!canUpdate"
              />
            </el-form-item>
            
            <el-form-item label="Telephone No." prop="telephone_no">
              <span v-if="!isEditMode || !canUpdate" class="text-gray-700">{{ employee?.telephone_no || '-' }}</span>
              <el-input
                v-else
                v-model="localFormData.telephone_no"
                @input="updateField('telephone_no', $event)"
                placeholder="Telephone No."
                :disabled="!canUpdate"
              />
            </el-form-item>
          </div>
        </el-col>

        <!-- Right Column: Personal and Address Information -->
        <el-col :xs="24" :sm="24" :md="16" :lg="18">
          <el-row :gutter="24">
            <!-- Personal Information -->
            <el-col :xs="24" :sm="24" :md="12">
              <el-card shadow="never" class="mb-4">
                <template #header>
                  <div class="card-header">
                    <span class="font-semibold">Personal Information</span>
                  </div>
                </template>
                
                <el-form-item label="Birth Date" prop="birthdate">
                  <span v-if="!isEditable" class="text-gray-900">
                    {{ employee?.birthdate ? formatDate(employee.birthdate) : '-' }}
                  </span>
                  <el-date-picker
                    v-else
                    v-model="localFormData.birthdate"
                    type="date"
                    format="YYYY-MM-DD"
                    value-format="YYYY-MM-DD"
                    @change="updateField('birthdate', $event)"
                    style="width: 100%"
                    :disabled="!canUpdate"
                  />
                </el-form-item>

                <el-form-item label="Age" prop="age">
                  <span v-if="!isEditable" class="text-gray-900">
                    {{ employee?.age ? `${employee.age} years old` : '-' }}
                  </span>
                  <el-input-number
                    v-else
                    v-model="localFormData.age"
                    @change="updateField('age', $event)"
                    :min="0"
                    :max="150"
                    style="width: 100%"
                    :disabled="!canUpdate"
                  />
                </el-form-item>

                <el-form-item label="Gender" prop="gender_id">
                  <span v-if="!isEditable" class="text-gray-900">{{ employee?.gender || '-' }}</span>
                  <el-select
                    v-else
                    v-model="localFormData.gender_id"
                    @change="updateField('gender_id', $event)"
                    placeholder="Select Gender"
                    style="width: 100%"
                    :disabled="!canUpdate"
                  >
                    <el-option
                      v-for="option in genderOptions"
                      :key="option.id"
                      :label="option.name"
                      :value="option.id"
                    />
                  </el-select>
                </el-form-item>

                <el-form-item label="Height" prop="height">
                  <span v-if="!isEditable" class="text-gray-900">
                    {{ employee?.height ? `${employee.height} m.` : '-' }}
                  </span>
                  <el-input
                    v-else
                    v-model="localFormData.height"
                    @input="updateField('height', $event)"
                    placeholder="Height in meters"
                    :disabled="!canUpdate"
                  />
                </el-form-item>

                <el-form-item label="Weight" prop="weight">
                  <span v-if="!isEditable" class="text-gray-900">
                    {{ employee?.weight ? `${employee.weight} kg.` : '-' }}
                  </span>
                  <el-input
                    v-else
                    v-model="localFormData.weight"
                    @input="updateField('weight', $event)"
                    placeholder="Weight in kg"
                    :disabled="!canUpdate"
                  />
                </el-form-item>

                <el-form-item label="Blood Type" prop="blood_type_id">
                  <span v-if="!isEditable" class="text-gray-900">{{ employee?.blood_type || '-' }}</span>
                  <el-select
                    v-else
                    v-model="localFormData.blood_type_id"
                    @change="updateField('blood_type_id', $event)"
                    placeholder="Select Blood Type"
                    style="width: 100%"
                    :disabled="!canUpdate"
                  >
                    <el-option
                      v-for="option in bloodTypeOptions"
                      :key="option.id"
                      :label="option.name"
                      :value="option.id"
                    />
                  </el-select>
                </el-form-item>

                <el-form-item label="Citizenship" prop="citizenship_id">
                  <span v-if="!isEditable" class="text-gray-900">{{ employee?.citizenship || '-' }}</span>
                  <el-input
                    v-else
                    v-model="localFormData.citizenship"
                    @input="updateField('citizenship', $event)"
                    placeholder="Citizenship"
                    :disabled="!canUpdate"
                  />
                </el-form-item>

                <el-form-item label="Civil Status" prop="civil_status_id">
                  <span v-if="!isEditable" class="text-gray-900">{{ employee?.civil_status || '-' }}</span>
                  <el-select
                    v-else
                    v-model="localFormData.civil_status_id"
                    @change="updateField('civil_status_id', $event)"
                    placeholder="Select Civil Status"
                    style="width: 100%"
                    :disabled="!canUpdate"
                  >
                    <el-option
                      v-for="option in civilStatusOptions"
                      :key="option.id"
                      :label="option.name"
                      :value="option.id"
                    />
                  </el-select>
                </el-form-item>

                <el-form-item label="Religion" prop="religion_id">
                  <span v-if="!isEditable" class="text-gray-900">{{ employee?.religion || '-' }}</span>
                  <el-select
                    v-else
                    v-model="localFormData.religion_id"
                    @change="updateField('religion_id', $event)"
                    placeholder="Select Religion"
                    style="width: 100%"
                    :disabled="!canUpdate"
                  >
                    <el-option
                      v-for="option in religionOptions"
                      :key="option.id"
                      :label="option.name"
                      :value="option.id"
                    />
                  </el-select>
                </el-form-item>
              </el-card>
            </el-col>

            <!-- Address Information -->
            <el-col :xs="24" :sm="24" :md="12">
              <el-card shadow="never">
                <template #header>
                  <div class="card-header">
                    <span class="font-semibold">Address Information</span>
                  </div>
                </template>

                <!-- Current Address -->
                <el-divider content-position="left">Current Address</el-divider>
                
                <div v-if="!isEditable" class="mb-4">
                  <p class="text-sm text-gray-900">{{ formatAddress(currentAddress) }}</p>
                </div>
                <div v-else>
                  <el-form-item label="House No." prop="ra_house_no">
                    <el-input
                      v-model="localFormData.ra_house_no"
                      @input="updateField('ra_house_no', $event)"
                      placeholder="House No."
                      :disabled="!canUpdate"
                    />
                  </el-form-item>
                  <el-form-item label="Street" prop="ra_street">
                    <el-input
                      v-model="localFormData.ra_street"
                      @input="updateField('ra_street', $event)"
                      placeholder="Street"
                      :disabled="!canUpdate"
                    />
                  </el-form-item>
                  <el-form-item label="Village" prop="ra_village">
                    <el-input
                      v-model="localFormData.ra_village"
                      @input="updateField('ra_village', $event)"
                      placeholder="Village"
                      :disabled="!canUpdate"
                    />
                  </el-form-item>
                  <el-form-item label="Region" prop="ra_region">
                    <el-select
                      v-model="localFormData.ra_region"
                      @change="handleRaRegionChange"
                      placeholder="Select Region"
                      style="width: 100%"
                      :disabled="!canUpdate"
                      filterable
                    >
                      <el-option
                        v-for="region in regionOptions"
                        :key="region.regCode"
                        :label="region.regDesc"
                        :value="region.regCode"
                      />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="Province" prop="ra_province">
                    <el-select
                      v-model="localFormData.ra_province"
                      @change="handleRaProvinceChange"
                      placeholder="Select Province"
                      style="width: 100%"
                      :disabled="!canUpdate || !localFormData.ra_region"
                      filterable
                    >
                      <el-option
                        v-for="province in filteredRaProvinces"
                        :key="province.provCode"
                        :label="province.provDesc"
                        :value="province.provCode"
                      />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="City" prop="ra_city">
                    <el-select
                      v-model="localFormData.ra_city"
                      @change="handleRaCityChange"
                      placeholder="Select City"
                      style="width: 100%"
                      :disabled="!canUpdate || !localFormData.ra_province"
                      filterable
                    >
                      <el-option
                        v-for="city in filteredRaCities"
                        :key="city.citymunCode"
                        :label="city.citymunDesc"
                        :value="city.citymunCode"
                      />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="Barangay" prop="ra_barangay">
                    <el-select
                      v-model="localFormData.ra_barangay"
                      @change="updateField('ra_barangay', $event)"
                      placeholder="Select Barangay"
                      style="width: 100%"
                      :disabled="!canUpdate || !localFormData.ra_city"
                      filterable
                    >
                      <el-option
                        v-for="barangay in filteredRaBarangays"
                        :key="barangay.brgyCode"
                        :label="barangay.brgyDesc"
                        :value="barangay.brgyCode"
                      />
                    </el-select>
                  </el-form-item>
                </div>

                <!-- Permanent Address -->
                <el-divider content-position="left">Permanent Address</el-divider>
                
                <div v-if="!isEditable" class="mb-4">
                  <p class="text-sm text-gray-900">{{ formatAddress(permanentAddress) }}</p>
                </div>
                <div v-else>
                  <el-form-item label="House No." prop="pa_house_no">
                    <el-input
                      v-model="localFormData.pa_house_no"
                      @input="updateField('pa_house_no', $event)"
                      placeholder="House No."
                      :disabled="!canUpdate"
                    />
                  </el-form-item>
                  <el-form-item label="Street" prop="pa_street">
                    <el-input
                      v-model="localFormData.pa_street"
                      @input="updateField('pa_street', $event)"
                      placeholder="Street"
                      :disabled="!canUpdate"
                    />
                  </el-form-item>
                  <el-form-item label="Village" prop="pa_village">
                    <el-input
                      v-model="localFormData.pa_village"
                      @input="updateField('pa_village', $event)"
                      placeholder="Village"
                      :disabled="!canUpdate"
                    />
                  </el-form-item>
                  <el-form-item label="Region" prop="pa_region">
                    <el-select
                      v-model="localFormData.pa_region"
                      @change="handlePaRegionChange"
                      placeholder="Select Region"
                      style="width: 100%"
                      :disabled="!canUpdate"
                      filterable
                    >
                      <el-option
                        v-for="region in regionOptions"
                        :key="region.regCode"
                        :label="region.regDesc"
                        :value="region.regCode"
                      />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="Province" prop="pa_province">
                    <el-select
                      v-model="localFormData.pa_province"
                      @change="handlePaProvinceChange"
                      placeholder="Select Province"
                      style="width: 100%"
                      :disabled="!canUpdate || !localFormData.pa_region"
                      filterable
                    >
                      <el-option
                        v-for="province in filteredPaProvinces"
                        :key="province.provCode"
                        :label="province.provDesc"
                        :value="province.provCode"
                      />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="City" prop="pa_city">
                    <el-select
                      v-model="localFormData.pa_city"
                      @change="handlePaCityChange"
                      placeholder="Select City"
                      style="width: 100%"
                      :disabled="!canUpdate || !localFormData.pa_province"
                      filterable
                    >
                      <el-option
                        v-for="city in filteredPaCities"
                        :key="city.citymunCode"
                        :label="city.citymunDesc"
                        :value="city.citymunCode"
                      />
                    </el-select>
                  </el-form-item>
                  <el-form-item label="Barangay" prop="pa_barangay">
                    <el-select
                      v-model="localFormData.pa_barangay"
                      @change="updateField('pa_barangay', $event)"
                      placeholder="Select Barangay"
                      style="width: 100%"
                      :disabled="!canUpdate || !localFormData.pa_city"
                      filterable
                    >
                      <el-option
                        v-for="barangay in filteredPaBarangays"
                        :key="barangay.brgyCode"
                        :label="barangay.brgyDesc"
                        :value="barangay.brgyCode"
                      />
                    </el-select>
                  </el-form-item>
                </div>
              </el-card>
            </el-col>
          </el-row>
        </el-col>
      </el-row>
    </el-form>
  </el-card>
</template>

<script>
import { User } from '@element-plus/icons-vue'

export default {
  name: 'EmployeeProfileCard',
  components: {
    User
  },
  props: {
    employee: {
      type: Object,
      default: () => ({})
    },
    address: {
      type: Object,
      default: () => ({})
    },
    isEditMode: {
      type: Boolean,
      default: false
    },
    canUpdate: {
      type: Boolean,
      default: false
    },
    formData: {
      type: Object,
      default: () => ({})
    },
    genderOptions: {
      type: Array,
      default: () => []
    },
    bloodTypeOptions: {
      type: Array,
      default: () => []
    },
    civilStatusOptions: {
      type: Array,
      default: () => []
    },
    religionOptions: {
      type: Array,
      default: () => []
    },
    regionOptions: {
      type: Array,
      default: () => []
    },
    provinceOptions: {
      type: Array,
      default: () => []
    },
    cityOptions: {
      type: Array,
      default: () => []
    },
    barangayOptions: {
      type: Array,
      default: () => []
    }
  },
  emits: ['update:form-data'],
  data() {
    return {
      objectUrl: null,
      isImageLoaded: false,
      imageFailed: false,
      lastBase64: null,
      localFormData: {}
    }
  },
  watch: {
    formData: {
      immediate: true,
      deep: true,
      handler(newVal) {
        if (newVal && Object.keys(newVal).length > 0) {
          this.localFormData = { ...newVal }
        } else if (this.isEditMode) {
          this.initializeFromEmployee()
        }
      }
    },
    isEditMode: {
      immediate: true,
      handler(newVal) {
        if (newVal && (!this.formData || Object.keys(this.formData).length === 0)) {
          this.initializeFromEmployee()
        }
      }
    },
    'employee.photo': {
      immediate: true,
      handler(newVal) {
        this.isImageLoaded = false
        this.imageFailed = false
        this.updateObjectUrl(newVal)
      }
    }
  },
  computed: {
    hasPhoto() {
      return !!(this.employee && this.employee.photo)
    },
    computedImageSrc() {
      return this.objectUrl
    },
    isEditable() {
      return this.isEditMode && this.canUpdate
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
    // Cascading dropdowns for Current Address
    filteredRaProvinces() {
      if (!this.localFormData.ra_region || !this.provinceOptions || this.provinceOptions.length === 0) return []
      return this.provinceOptions.filter(p => String(p.regCode) === String(this.localFormData.ra_region))
    },
    filteredRaCities() {
      if (!this.localFormData.ra_province || !this.cityOptions || this.cityOptions.length === 0) return []
      const filtered = this.cityOptions.filter(c => String(c.provCode) === String(this.localFormData.ra_province))
      return filtered
    },
    filteredRaBarangays() {
      if (!this.localFormData.ra_city || !this.barangayOptions || this.barangayOptions.length === 0) return []
      return this.barangayOptions.filter(b => String(b.citymunCode) === String(this.localFormData.ra_city))
    },
    // Cascading dropdowns for Permanent Address
    filteredPaProvinces() {
      if (!this.localFormData.pa_region || !this.provinceOptions || this.provinceOptions.length === 0) return []
      return this.provinceOptions.filter(p => String(p.regCode) === String(this.localFormData.pa_region))
    },
    filteredPaCities() {
      if (!this.localFormData.pa_province || !this.cityOptions || this.cityOptions.length === 0) return []
      const filtered = this.cityOptions.filter(c => String(c.provCode) === String(this.localFormData.pa_province))
      return filtered
    },
    filteredPaBarangays() {
      if (!this.localFormData.pa_city || !this.barangayOptions || this.barangayOptions.length === 0) return []
      return this.barangayOptions.filter(b => String(b.citymunCode) === String(this.localFormData.pa_city))
    }
  },
  beforeUnmount() {
    if (this.objectUrl) URL.revokeObjectURL(this.objectUrl)
  },
  methods: {
    async updateObjectUrl(base64) {
      if (base64 === this.lastBase64) return
      this.lastBase64 = base64 || null

      if (this.objectUrl) {
        URL.revokeObjectURL(this.objectUrl)
        this.objectUrl = null
      }
      if (!base64) return
      try {
        const dataUrl = `data:image/jpeg;base64,${base64}`
        const res = await fetch(dataUrl)
        const blob = await res.blob()
        this.objectUrl = URL.createObjectURL(blob)
      } catch (e) {
        this.objectUrl = null
        this.imageFailed = true
      }
    },
    handleImageLoad() {
      this.isImageLoaded = true
    },
    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
    formatAddress(address) {
      const parts = []
      
      if (address.house_no) parts.push(address.house_no)
      if (address.village) parts.push(address.village)
      if (address.street) parts.push(address.street)
      if (address.barangay) parts.push(`Brgy. ${address.barangay}`)
      if (address.city) parts.push(address.city)
      if (address.province) parts.push(address.province)
      if (address.region) parts.push(address.region)
      
      return parts.length > 0 ? parts.join(', ') : 'No address provided'
    },
    handleImageError() {
      this.imageFailed = true
      this.isImageLoaded = true
      if (this.objectUrl) {
        URL.revokeObjectURL(this.objectUrl)
        this.objectUrl = null
      }
    },
    updateField(field, value) {
      this.$emit('update:form-data', { field, value })
    },
    // Current Address cascading handlers
    handleRaRegionChange(value) {
      this.localFormData.ra_region = value
      this.updateField('ra_region', value)
      // Clear dependent fields
      this.localFormData.ra_province = ''
      this.localFormData.ra_city = ''
      this.localFormData.ra_barangay = ''
      this.updateField('ra_province', '')
      this.updateField('ra_city', '')
      this.updateField('ra_barangay', '')
    },
    handleRaProvinceChange(value) {
      this.localFormData.ra_province = value
      this.updateField('ra_province', value)
      // Clear dependent fields
      this.localFormData.ra_city = ''
      this.localFormData.ra_barangay = ''
      this.updateField('ra_city', '')
      this.updateField('ra_barangay', '')
    },
    handleRaCityChange(value) {
      this.localFormData.ra_city = value
      this.updateField('ra_city', value)
      // Clear dependent fields
      this.localFormData.ra_barangay = ''
      this.updateField('ra_barangay', '')
    },
    // Permanent Address cascading handlers
    handlePaRegionChange(value) {
      this.localFormData.pa_region = value
      this.updateField('pa_region', value)
      // Clear dependent fields
      this.localFormData.pa_province = ''
      this.localFormData.pa_city = ''
      this.localFormData.pa_barangay = ''
      this.updateField('pa_province', '')
      this.updateField('pa_city', '')
      this.updateField('pa_barangay', '')
    },
    handlePaProvinceChange(value) {
      this.localFormData.pa_province = value
      this.updateField('pa_province', value)
      // Clear dependent fields
      this.localFormData.pa_city = ''
      this.localFormData.pa_barangay = ''
      this.updateField('pa_city', '')
      this.updateField('pa_barangay', '')
    },
    handlePaCityChange(value) {
      this.localFormData.pa_city = value
      this.updateField('pa_city', value)
      // Clear dependent fields
      this.localFormData.pa_barangay = ''
      this.updateField('pa_barangay', '')
    },
    initializeFromEmployee() {
      const ra_barangay = this.employee.ra_barangay || this.address.ra_barangay || this.address.ra_brgy || ''
      const pa_barangay = this.employee.pa_barangay || this.address.pa_barangay || this.address.pa_brgy || ''

      this.localFormData = {
        name_prefix_id: this.employee.name_prefix_id || 0,
        first_name: this.employee.first_name || '',
        middle_name: this.employee.middle_name || '',
        last_name: this.employee.last_name || '',
        name_suffix_id: this.employee.name_suffix_id || 0,
        email: this.employee.email || '',
        mobile_no: this.employee.mobile_no || '',
        telephone_no: this.employee.telephone_no || '',
        birthdate: this.employee.birthdate || '',
        age: this.employee.age || 0,
        height: this.employee.height || '',
        weight: this.employee.weight || '',
        gender_id: this.employee.gender_id || 0,
        civil_status_id: this.employee.civil_status_id || 0,
        citizenship_id: this.employee.citizenship_id || 0,
        religion_id: this.employee.religion_id || 0,
        blood_type_id: this.employee.blood_type_id || 0,
        ra_house_no: this.employee.ra_house_no || '',
        ra_street: this.employee.ra_street || '',
        ra_village: this.employee.ra_village || '',
        ra_barangay,
        ra_city: this.employee.ra_city || this.address.ra_city || '',
        ra_province: this.employee.ra_province || this.address.ra_province || '',
        ra_region: this.employee.ra_region || this.address.ra_region || '',
        pa_house_no: this.employee.pa_house_no || '',
        pa_street: this.employee.pa_street || '',
        pa_village: this.employee.pa_village || '',
        pa_barangay,
        pa_city: this.employee.pa_city || this.address.pa_city || '',
        pa_province: this.employee.pa_province || this.address.pa_province || '',
        pa_region: this.employee.pa_region || this.address.pa_region || ''
      }
      
      this.$emit('update:form-data', this.localFormData)
    }
  }
}
</script>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mb-6 {
  margin-bottom: 1.5rem;
}

.text-sm {
  font-size: 0.875rem;
  line-height: 1.25rem;
}

.text-lg {
  font-size: 1.125rem;
  line-height: 1.75rem;
}

.font-semibold {
  font-weight: 600;
}

.text-gray-600 {
  color: #4b5563;
}

.text-gray-700 {
  color: #374151;
}

.text-gray-900 {
  color: #111827;
}

.space-y-2 > * + * {
  margin-top: 0.5rem;
}

:deep(.el-form-item__label) {
  font-weight: 500;
  color: #374151;
}

:deep(.el-card__header) {
  background-color: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  padding: 12px 20px;
}

:deep(.el-divider__text) {
  background-color: white;
  font-weight: 500;
  color: #374151;
}
</style>
