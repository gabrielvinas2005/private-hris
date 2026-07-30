<template>
  <el-dialog
    :model-value="true"
    title="Request for Pick-up"
    width="600px"
    @close="$emit('close')"
    append-to-body
  >
    <el-form label-position="top" @submit.prevent>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- From -->
        <el-form-item label="From" required>
          <el-input 
            v-model="form.from"
            placeholder="Enter name"
          />
        </el-form-item>

        <!-- Date (top right) -->
        <el-form-item label="Date" required>
          <el-date-picker 
            v-model="form.date"
            type="date"
            placeholder="Select date"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            style="width: 100%"
            disabled
          />
        </el-form-item>
      </div>

      <div class="grid grid-cols-1 gap-4 mb-4">
        <!-- Company -->
        <el-form-item label="Company" required>
          <el-input 
            v-model="form.company"
            placeholder="Enter company name"
          />
        </el-form-item>

        <!-- Address -->
        <el-form-item label="Address" required>
          <el-input 
            v-model="form.address"
            type="textarea"
            :rows="2"
            placeholder="Enter address"
          />
        </el-form-item>

        <!-- Contact No -->
        <el-form-item label="Contact No." required>
          <el-input 
            v-model="form.contact_no"
            placeholder="Enter contact number"
          />
        </el-form-item>

        <!-- Type of Documents/Materials -->
        <el-form-item label="Type of Documents/Materials" required>
          <el-input 
            v-model="form.documents_materials"
            type="textarea"
            :rows="3"
            placeholder="Enter type of documents/materials"
          />
        </el-form-item>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Picked-up By -->
        <el-form-item label="Picked-up By">
          <el-input 
            v-model="form.picked_up_by"
            placeholder="Enter name"
          />
        </el-form-item>

        <!-- Date (below Picked-up By) -->
        <el-form-item label="Date">
          <el-date-picker 
            v-model="form.pickup_date"
            type="date"
            placeholder="Select date"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            style="width: 100%"
            clearable
          />
        </el-form-item>
      </div>

      <div class="grid grid-cols-1 gap-4 mb-4">
        <!-- Requested By (auto-filled) -->
        <el-form-item label="Requested By (Name/Division)">
          <el-input 
            v-model="form.requested_by_display"
            placeholder="Requested by"
            disabled
          />
        </el-form-item>

        <!-- Received By -->
        <el-form-item label="Received By">
          <el-input 
            v-model="form.received_by"
            placeholder="Enter name"
          />
        </el-form-item>
      </div>
    </el-form>

    <template #footer>
      <div class="flex justify-end gap-2">
        <el-button @click="$emit('close')">Cancel</el-button>
        <el-button 
          type="primary" 
          :loading="isSubmitting" 
          @click="handleSubmit"
        >
          {{ isSubmitting ? 'Submitting...' : 'Submit' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { useToast } from 'vue-toastification'
import ApiService from '../../services/api.js'

export default {
  name: 'RequestPickupModal',
  setup() {
    const toast = useToast()
    return { toast }
  },
  data() {
    return {
      isSubmitting: false,
      form: {
        from: '',
        company: '',
        address: '',
        contact_no: '',
        documents_materials: '',
        picked_up_by: '',
        pickup_date: '',
        requested_by_employee_id: null,
        requested_by_display: '',
        received_by: '',
        date: ''
      }
    }
  },
  async mounted() {
    await this.initializeForm()
  },
  methods: {
    async initializeForm() {
      // Set current date
      const today = new Date()
      const year = today.getFullYear()
      const month = String(today.getMonth() + 1).padStart(2, '0')
      const day = String(today.getDate()).padStart(2, '0')
      this.form.date = `${year}-${month}-${day}`

      // Get current user information
      const userData = this.getCurrentUser()
      if (userData && userData.id) {
        try {
          // Fetch employee information with division from API
          const response = await ApiService.getEmployeeInfoForPickup(userData.id)
          
          if (response.success && response.data) {
            // Use the employee id and formatted name/division from API
            this.form.requested_by_employee_id = response.data.employee_id || null
            this.form.requested_by_display = response.data.requested_by || 'User'
          } else {
            // Fallback to user data if API call fails
            this.setRequestedByFromUserData(userData)
          }
        } catch (error) {
          console.error('Error fetching employee info:', error)
          // Fallback to user data if API call fails
          this.setRequestedByFromUserData(userData)
        }
      } else {
      this.form.requested_by_display = 'User'
      }
    },
    setRequestedByFromUserData(userData) {
      // Format: "Name / Division"
      // Try different possible name fields
      let name = userData.name || 
                 userData.full_name || 
                 `${userData.first_name || ''} ${userData.last_name || ''}`.trim() ||
                 userData.email ||
                 'User'
      
      // Try different possible division fields
      const division = userData.division || 
                      userData.division_name || 
                      userData.department ||
                      ''
      
      const display = division ? `${name} / ${division}` : name
      this.form.requested_by_display = display
      this.form.requested_by_employee_id = userData.employee_id || null
    },
    getCurrentUser() {
      try {
        const raw = localStorage.getItem('user_data')
        if (raw) {
          return JSON.parse(raw)
        }
      } catch (error) {
        console.error('Error parsing user data:', error)
      }
      return null
    },
    async handleSubmit() {
      // Validate required fields
      if (!this.form.from.trim()) {
        this.toast.error('Please enter "From" field')
        return
      }
      if (!this.form.company.trim()) {
        this.toast.error('Please enter "Company" field')
        return
      }
      if (!this.form.address.trim()) {
        this.toast.error('Please enter "Address" field')
        return
      }
      if (!this.form.contact_no.trim()) {
        this.toast.error('Please enter "Contact No." field')
        return
      }
      if (!this.form.documents_materials.trim()) {
        this.toast.error('Please enter "Type of Documents/Materials" field')
        return
      }

      this.isSubmitting = true

      try {
        // Emit the form data to parent component
        const { requested_by_display, ...payload } = { ...this.form }
        // Do not auto-set pickup_date; send null when user leaves it blank
        payload.pickup_date = payload.pickup_date ? payload.pickup_date : null
        // Ensure we send the employee id for requested_by
        payload.requested_by_employee_id = payload.requested_by_employee_id || null

        this.$emit('submit', payload)
      } catch (error) {
        console.error('Error submitting request:', error)
        this.toast.error('Failed to submit request')
      } finally {
        this.isSubmitting = false
      }
    }
  }
}
</script>

