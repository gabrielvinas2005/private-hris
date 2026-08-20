<template>
  <div class="mb-6 relative">
    <!-- Profile Photo Avatar (Outside the white frame on the left) -->
    <div class="relative flex items-center justify-center mb-4 md:mb-0 md:absolute md:left-6 md:top-1/2 md:-translate-y-1/2 z-10 flex-shrink-0">
      <div class="w-28 h-28 sm:w-40 sm:h-40 rounded-full border-[6px] border-[#3f3f3f] overflow-hidden bg-gray-100 flex items-center justify-center shadow-md">
        <template v-if="hasPhoto">
          <img
            :src="computedImageSrc"
            :alt="formattedName"
            class="w-full h-full object-cover transition-opacity duration-200"
            :class="{ 'opacity-0': !isImageLoaded, 'opacity-100': isImageLoaded }"
            @load="handleImageLoad"
            @error="handleImageError"
          />
        </template>
        <template v-else>
          <el-icon :size="56" color="#6b7280">
            <User />
          </el-icon>
        </template>
      </div>
    </div>

    <!-- Frame 98 Read-Mode Profile Header Banner -->
    <div 
      class="ml-8 lg:ml-20 w-[calc(100%-2rem)] lg:w-[calc(100%-5rem)] rounded-[9px] p-5 lg:p-6 md:pl-44 lg:pl-52 transition-all border border-slate-200/60 shadow-sm"
      style="background: white;"
    >
      <div class="flex flex-col lg:flex-row items-center justify-between gap-6 lg:gap-10">
        
        <!-- Left Section: Name & Designation -->
        <div class="flex items-center min-w-[200px]">
          <!-- Name and Position/Title -->
          <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-800 tracking-tight leading-tight">
              {{ formattedName }}
            </h2>
            <p class="text-lg sm:text-xl text-slate-600 font-normal italic mt-1">
              {{ formattedPosition }}
            </p>
          </div>
        </div>

        <!-- Middle Section: Key Metrics Table with Horizontal Dividers -->
        <div class="flex-1 max-w-xl w-full px-2 lg:px-6">
          <div class="space-y-2">
            <!-- Row 1 -->
            <div class="flex items-center justify-between pb-2 border-b border-gray-300/80 text-sm sm:text-base">
              <div class="w-1/2 flex items-center gap-1.5">
                <span class="text-slate-500 font-normal">Code:</span>
                <span class="font-bold text-slate-800">{{ formattedCode }}</span>
              </div>
              <div class="w-1/2 flex items-center gap-1.5 justify-start pl-4">
                <span class="text-slate-500 font-normal">Status:</span>
                <span class="font-bold text-slate-800">{{ formattedStatus }}</span>
              </div>
            </div>

            <!-- Row 2 -->
            <div class="flex items-center justify-between pb-2 border-b border-gray-300/80 text-sm sm:text-base">
              <div class="w-1/2 flex items-center gap-1.5">
                <span class="text-slate-500 font-normal">Born:</span>
                <span class="font-bold text-slate-800">{{ formattedBirthdate }}</span>
              </div>
              <div class="w-1/2 flex items-center gap-1.5 justify-start pl-4">
                <span class="text-slate-500 font-normal">Age:</span>
                <span class="font-bold text-slate-800">{{ formattedAge }}</span>
              </div>
            </div>

            <!-- Row 3 -->
            <div class="flex items-center justify-between pt-0.5 text-sm sm:text-base">
              <div class="w-1/2 flex items-center gap-1.5">
                <span class="text-slate-500 font-normal">Gender:</span>
                <span class="font-bold text-slate-800">{{ formattedGender }}</span>
              </div>
              <div class="w-1/2 flex items-center gap-1.5 justify-start pl-4">
                <span class="text-slate-500 font-normal">Employed for:</span>
                <span class="font-bold text-slate-800">{{ computedServiceLength }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Section: Branch & Department -->
        <div class="flex flex-col justify-center space-y-4 min-w-[200px] border-t lg:border-t-0 pt-4 lg:pt-0 border-gray-300/60 lg:mr-8 lg:-translate-x-4">
          <div>
            <p class="text-xs sm:text-sm text-slate-500 font-normal">Branch</p>
            <p class="text-sm sm:text-base font-bold text-slate-800 truncate">
              {{ formattedBranch }}
            </p>
          </div>
          <div>
            <p class="text-xs sm:text-sm text-slate-500 font-normal">Department</p>
            <p class="text-sm sm:text-base font-bold text-slate-800 truncate">
              {{ formattedDepartment }}
            </p>
          </div>
        </div>

      </div>
    </div>
  </div>
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
      showDetails: false,
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
        this.isImageLoaded = !!newVal
        this.imageFailed = false
      }
    }
  },
  computed: {
    formattedName() {
      if (this.employee?.last_name && this.employee?.first_name) {
        const m = this.employee.middle_name ? ` ${this.employee.middle_name.charAt(0)}.` : ''
        return `${this.employee.last_name}, ${this.employee.first_name}${m}`
      }
      return this.employee?.name || '-'
    },
    formattedPosition() {
      return this.employee?.position || this.employee?.designation || this.employee?.role || '-'
    },
    formattedCode() {
      return this.employee?.employee_no || this.employee?.code || '-'
    },
    formattedStatus() {
      return this.employee?.employment_type || this.employee?.status || '-'
    },
    formattedBirthdate() {
      if (this.employee?.birthdate) {
        const d = new Date(this.employee.birthdate)
        if (!isNaN(d.getTime())) {
          return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
        }
      }
      return '-'
    },
    computedAgeDisplay() {
      const bDate = this.localFormData?.birthdate || this.employee?.birthdate
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
    formattedAge() {
      return this.computedAgeDisplay
    },
    formattedGender() {
      return this.employee?.gender || '-'
    },
    computedServiceLength() {
      if (this.employee?.employed_for || this.employee?.service_length) {
        return this.employee.employed_for || this.employee.service_length
      }
      if (this.employee?.date_hired || this.employee?.created_at) {
        const hired = new Date(this.employee.date_hired || this.employee.created_at)
        if (!isNaN(hired.getTime())) {
          const now = new Date()
          let years = now.getFullYear() - hired.getFullYear()
          let months = now.getMonth() - hired.getMonth()
          if (months < 0) {
            years--
            months += 12
          }
          return `${years}y , ${months}m`
        }
      }
      return '-'
    },
    formattedBranch() {
      return this.employee?.branch || this.employee?.company || '-'
    },
    formattedDepartment() {
      return this.employee?.department || '-'
    },
    hasPhoto() {
      return !!(this.employee && this.employee.photo && !this.imageFailed)
    },
    computedImageSrc() {
      if (!this.employee || !this.employee.photo) return null
      const photo = this.employee.photo
      if (typeof photo === 'string') {
        const trimmed = photo.trim()
        if (!trimmed) return null
        if (trimmed.startsWith('data:image/') || trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('blob:')) {
          return trimmed
        }
        return `data:image/jpeg;base64,${trimmed}`
      }
      return null
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
        const dataUrl = (typeof base64 === 'string' && (base64.startsWith('data:image/') || base64.startsWith('http://') || base64.startsWith('https://') || base64.startsWith('blob:')))
          ? base64
          : `data:image/jpeg;base64,${base64}`
        const res = await fetch(dataUrl)
        const blob = await res.blob()
        this.objectUrl = URL.createObjectURL(blob)
        this.isImageLoaded = true
        this.imageFailed = false
      } catch (e) {
        this.objectUrl = null
        this.imageFailed = false
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
