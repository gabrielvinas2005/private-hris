<template>
  <el-dialog :model-value="true" :title="getFormTitle()" width="720px" @close="$emit('close')" append-to-body>
    <el-form label-position="top" @submit.prevent>
      <!-- Travel Authority Type - Top of form -->
      <div v-if="formType === 'travel_authority'" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <el-form-item label="Travel Authority Type" :rules="[{ required: true, message: 'Travel Authority Type is required', trigger: 'change' }]">
          <el-select v-model="form.type_id" placeholder="Select travel authority type" filterable style="width: 100%">
            <el-option
              v-for="type in taTypes"
              :key="type.id"
              :label="type.name"
              :value="type.id"
            />
          </el-select>
        </el-form-item>
      </div>

      <!-- Travel Order Type - Top of form -->
      <div v-if="formType === 'travel_order'" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <el-form-item label="Travel Order Type" required>
          <el-select v-model="form.type_id" placeholder="Select travel order type" filterable style="width: 100%">
            <el-option
              v-for="type in toTypes"
              :key="type.id"
              :label="type.name"
              :value="type.id"
            />
          </el-select>
        </el-form-item>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <el-form-item v-if="formType !== 'travel_authority'" label="Date" required>
          <el-date-picker v-model="form.date" type="date" placeholder="Select date" format="YYYY-MM-DD" value-format="YYYY-MM-DD" style="width: 100%" />
        </el-form-item>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <el-form-item :label="(formType === 'travel_authority' || formType === 'travel_order') ? 'From' : 'Departure Date and Time'" required>
          <el-date-picker v-model="form.date_time_from" type="datetime" placeholder="Select date & time" format="YYYY-MM-DD hh:mm A" value-format="YYYY-MM-DDTHH:mm" style="width: 100%" />
        </el-form-item>
        <el-form-item :label="(formType === 'travel_authority' || formType === 'travel_order') ? 'To' : 'Arrival Date and Time'" required>
          <el-date-picker v-model="form.date_time_to" type="datetime" placeholder="Select date & time" format="YYYY-MM-DD hh:mm A" value-format="YYYY-MM-DDTHH:mm" style="width: 100%" />
        </el-form-item>
      </div>

      <div v-if="formType === 'official'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <el-form-item label="Type" required>
          <el-select v-model="form.ob_type" placeholder="Select type">
            <el-option label="Personal" value="1" />
            <el-option label="Official" value="5" />
          </el-select>
        </el-form-item>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <el-form-item :label="travelAuthorityClientLabel" required>
          <el-input v-model="form.client" placeholder="Enter client name" />
        </el-form-item>
        <el-form-item v-if="formType === 'official'" label="Telephone Number/s">
          <el-input
            v-model="form.telephone_numbers"
            placeholder="Enter telephone number(s)"
          />
        </el-form-item>
      </div>

      <div v-if="formType === 'travel_authority' && !isPersonalTravelAuthority" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <el-form-item label="Fund Source" required>
          <el-input v-model="form.funds" type="textarea" :rows="3" placeholder="Enter fund source" />
        </el-form-item>
      </div>

      <!-- Budget Officer (recommending_approval) - Travel Authority only -->
      <div v-if="formType === 'travel_authority' && !isPersonalTravelAuthority" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <el-form-item label="Budget Officer" required>
          <el-select
            v-model="form.recommending_approval"
            placeholder="Select Budget Officer"
            filterable
            clearable
            style="width: 100%"
          >
            <el-option
              v-for="emp in budgetOfficerOptions"
              :key="emp.id"
              :label="emp.name"
              :value="emp.id"
            />
          </el-select>
        </el-form-item>
      </div>

      <!-- Budget Officer (recommending_approval) - Travel Order -->
      <div v-if="isTravelOrderBudgetOfficerRequired" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <el-form-item label="Budget Officer" required>
          <el-select
            v-model="form.recommending_approval"
            placeholder="Select Budget Officer"
            filterable
            clearable
            style="width: 100%"
          >
            <el-option
              v-for="emp in budgetOfficerOptions"
              :key="emp.id"
              :label="emp.name"
              :value="emp.id"
            />
          </el-select>
        </el-form-item>
      </div>

      <el-form-item v-if="formType !== 'travel_authority' || !isPersonalTravelAuthority" label="Purpose" required>
        <el-input v-model="form.purpose" type="textarea" :rows="3" placeholder="Describe the purpose" />
      </el-form-item>
    </el-form>

    <template #footer>
      <div class="flex justify-end gap-2">
        <el-button @click="$emit('close')">Cancel</el-button>
        <el-button type="primary" :loading="isSubmitting" @click="handleSubmit">{{ isSubmitting ? 'Saving...' : (obApplication ? 'Update' : 'Save') }}</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { useToast } from 'vue-toastification'
import ApiService from '../../services/api'

export default {
  name: 'OBForm',
  setup() {
    const toast = useToast()
    return { toast }
  },
  computed: {
    isPersonalTravelAuthority() {
      return this.formType === 'travel_authority' && String(this.form.type_id) === '1'
    },
    isTravelOrderBudgetOfficerRequired() {
      // Budget Officer is required for Travel Orders with type_id 3 or 4
      if (this.formType !== 'travel_order') return false
      const typeId = String(this.form.type_id)
      return typeId === '3' || typeId === '4'
    },
    travelAuthorityClientLabel() {
      if (this.formType === 'travel_authority') {
        return this.isPersonalTravelAuthority ? 'Destination' : 'Activity Organized/Sponsored by'
      }
      return 'Client'
    }
  },
  props: {
    obApplication: {
      type: Object,
      default: null
    },
    formType: {
      type: String,
      default: 'official' // official, travel_authority, travel_order
    }
  },
  data() {
    return {
      isSubmitting: false,
      taTypes: [],
      toTypes: [],
      budgetOfficerOptions: [],
      form: {
        date: '',
        date_time_from: '',
        date_time_to: '',
        ob_type: '1',
        type_id: null,
        client: '',
        telephone_numbers: '',
        funds: '',
        purpose: '',
        recommending_approval: '',
        recommending_position: '',
        approver: '',
        approver_position: ''
      }
    }
  },
  async mounted() {
    // Load type options based on form type
    if (this.formType === 'travel_authority') {
      await this.loadTATypes()
      await this.loadBudgetOfficers()
    } else if (this.formType === 'travel_order') {
      await this.loadTOTypes()
      // Always load Budget Officers once for Travel Order; validation still
      // only requires it for specific TO types (3 and 4).
      await this.loadBudgetOfficers()
    }

    if (this.obApplication) {
      this.loadOBData()
    } else {
      this.initializeForm()
    }
  },
  watch: {
    obApplication: {
      handler(newVal) {
        if (newVal) {
          this.loadOBData()
        } else {
          this.initializeForm()
        }
      },
      immediate: false,
      deep: true
    },
    formType: {
      async handler(newVal, oldVal) {
        // Clear types when switching form types
        if (oldVal && oldVal !== newVal) {
          this.taTypes = []
          this.toTypes = []
          this.form.type_id = null
        }
        
        // Reload type options when form type changes
        if (newVal === 'travel_authority') {
          await this.loadTATypes()
          await this.loadBudgetOfficers()
        } else if (newVal === 'travel_order') {
          await this.loadTOTypes()
          await this.loadBudgetOfficers()
        }
        
        // Reload data if obApplication exists
        if (this.obApplication) {
          this.loadOBData()
        } else {
          this.initializeForm()
        }
      },
      immediate: false
    }
  },
  methods: {
    getFormTitle() {
      if (this.obApplication) {
        return `Edit ${this.getFormTypeName()}`
      }
      return `Add ${this.getFormTypeName()}`
    },
    getFormTypeName() {
      switch (this.formType) {
        case 'official': return 'Official Business'
        case 'travel_authority': return 'Travel Authority'
        case 'travel_order': return 'Travel Order'
        default: return 'Official Business'
      }
    },
    initializeForm() {
      // Set default values based on form type
      if (this.formType === 'travel_authority') {
        this.form.ob_type = '2'
        this.form.date = this.formatToday()
      } else if (this.formType === 'travel_order') {
        this.form.ob_type = '3'
      } else {
        this.form.ob_type = '1'
      }
    },
    formatToday() {
      const d = new Date()
      const m = String(d.getMonth() + 1).padStart(2, '0')
      const day = String(d.getDate()).padStart(2, '0')
      return `${d.getFullYear()}-${m}-${day}`
    },
    formatDateForInput(dateString) {
      if (!dateString) return ''

      let date

      if (typeof dateString === 'string') {
        // Handle formats like "Y/m/d H:i:s" or "Y-m-d H:i:s"
        const slashFormatMatch = dateString.match(/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})/)
        if (slashFormatMatch) {
          const [, year, month, day] = slashFormatMatch
          date = new Date(
            parseInt(year),
            parseInt(month) - 1,
            parseInt(day),
            0,
            0,
            0
          )
        } else {
          date = new Date(dateString)
        }
      } else {
        date = new Date(dateString)
      }

      if (isNaN(date.getTime())) {
        console.warn('Invalid date string for date input:', dateString)
        return ''
      }

      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')

      // Return in YYYY-MM-DD format as expected by backend validation (Y-m-d)
      return `${year}-${month}-${day}`
    },
    async loadOBData() {
      if (this.obApplication) {
        // Always load type options when editing (they might not be loaded yet)
        if (this.formType === 'travel_authority') {
          if (this.taTypes.length === 0) {
            await this.loadTATypes()
          }
        } else if (this.formType === 'travel_order') {
          if (this.toTypes.length === 0) {
            await this.loadTOTypes()
          }
        }

        this.form = {
          date: this.formatDateForInput(this.obApplication.date),
          date_time_from: this.formatDateTimeForInput(this.obApplication.date_time_from),
          date_time_to: this.formatDateTimeForInput(this.obApplication.date_time_to),
          ob_type: this.obApplication.ob_type?.toString() || '1',
          type_id: this.obApplication.type_id || null,
          client: this.obApplication.client || '',
          telephone_numbers: this.obApplication.telephone_numbers || '',
          funds: this.obApplication.funds || '',
          purpose: this.obApplication.purpose || '',
          recommending_approval: this.obApplication.recommending_approval || '',
          recommending_position: this.obApplication.recommending_position || '',
          approver: this.obApplication.approver || '',
          approver_position: this.obApplication.approver_position || ''
        }

        // For Travel Authority / Travel Order, if we have an existing recommending_approval name
        // and loaded Budget Officer options, map the name back to employee ID
        if (
          (this.formType === 'travel_authority' || this.formType === 'travel_order') &&
          this.form.recommending_approval &&
          Array.isArray(this.budgetOfficerOptions) &&
          this.budgetOfficerOptions.length > 0
        ) {
          const match = this.budgetOfficerOptions.find(
            emp => emp.name === this.form.recommending_approval
          )
          if (match) {
            this.form.recommending_approval = match.id
          }
        }
      }
    },
    async loadTATypes() {
      try {
        const response = await ApiService.getTATypes()
        if (response.success && response.data) {
          this.taTypes = response.data
        }
      } catch (error) {
        console.error('Failed to load TA types:', error)
        this.toast.error('Failed to load travel authority types')
      }
    },
    async loadTOTypes() {
      try {
        const response = await ApiService.getTOTypes()
        if (response.success && response.data) {
          this.toTypes = response.data
        }
      } catch (error) {
        console.error('Failed to load TO types:', error)
        this.toast.error('Failed to load travel order types')
      }
    },
    formatDateTimeForInput(dateString) {
      if (!dateString) return ''
      
      let date
      
      // Handle different date formats from backend
      if (typeof dateString === 'string') {
        // If it's in format "Y/m/d H:i:s" (from database), parse it manually to avoid timezone issues
        // Example: "2024/01/15 14:30:00" or "2024/1/15 14:30:00"
        const slashFormatMatch = dateString.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})\s+(\d{1,2}):(\d{1,2}):?(\d{0,2})/)
        if (slashFormatMatch) {
          // Format: "Y/m/d H:i:s" - parse as local time (no timezone conversion)
          const [, year, month, day, hour, minute] = slashFormatMatch
          date = new Date(
            parseInt(year),
            parseInt(month) - 1, // Month is 0-indexed
            parseInt(day),
            parseInt(hour),
            parseInt(minute),
            0
          )
        } else if (dateString.includes('T')) {
          // ISO format with T
          if (dateString.includes('Z') || dateString.match(/[+-]\d{2}:\d{2}$/)) {
            // Has timezone info
            date = new Date(dateString)
          } else {
            // No timezone, treat as local time by appending Z and adjusting
            const tempDate = new Date(dateString + 'Z')
            const offset = tempDate.getTimezoneOffset()
            date = new Date(tempDate.getTime() - (offset * 60 * 1000))
          }
        } else {
          // Try standard parsing
          date = new Date(dateString)
        }
      } else {
        date = new Date(dateString)
      }
      
      // Check if date is valid
      if (isNaN(date.getTime())) {
        console.warn('Invalid date string for input:', dateString)
        return ''
      }
      
      // Format as YYYY-MM-DDTHH:MM for datetime-local input
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      const hours = String(date.getHours()).padStart(2, '0')
      const minutes = String(date.getMinutes()).padStart(2, '0')
      
      return `${year}-${month}-${day}T${hours}:${minutes}`
    },
    async loadBudgetOfficers() {
      try {
        // Simple in-memory caching across component instances
        if (this.$root && this.$root.$budgetOfficerCache && Array.isArray(this.$root.$budgetOfficerCache)) {
          this.budgetOfficerOptions = this.$root.$budgetOfficerCache
          return
        }

        const response = await ApiService.getBudgetOfficers()
        if (response && response.success && Array.isArray(response.data)) {
          this.budgetOfficerOptions = response.data
          if (this.$root) {
            this.$root.$budgetOfficerCache = response.data
          }
        }
      } catch (error) {
        console.error('Failed to load Budget Officers:', error)
        this.toast.error('Failed to load Budget Officers')
      }
    },
    async handleSubmit() {
      // Validate required fields
      if (this.formType === 'travel_authority' && !this.form.type_id) {
        this.toast.error('Please select a Travel Authority Type')
        this.isSubmitting = false
        return
      }
      if (this.formType === 'travel_order' && !this.form.type_id) {
        this.toast.error('Please select a Travel Order Type')
        this.isSubmitting = false
        return
      }
      if (this.isTravelOrderBudgetOfficerRequired && !this.form.recommending_approval) {
        this.toast.error('Please select a Budget Officer')
        this.isSubmitting = false
        return
      }

      this.isSubmitting = true
      
      try {
        const formData = new FormData()
        
        // Add all form fields
        Object.keys(this.form).forEach(key => {
          const value = this.form[key]
          // Convert null to empty string for FormData compatibility
          if (value === null || value === undefined) {
            formData.append(key, '')
          } else {
            formData.append(key, value)
          }
        })
        
        // Explicitly ensure type_id is included for Travel Authority and Travel Order
        if (this.formType === 'travel_authority' || this.formType === 'travel_order') {
          if (this.form.type_id) {
            formData.set('type_id', this.form.type_id) // Use set to override if already exists
          }
        }
        
        this.$emit('submit', formData)
      } catch (error) {
        console.error('Error submitting form:', error)
        this.toast.error('Failed to submit form')
      } finally {
        this.isSubmitting = false
      }
    }
  }
}
</script> 