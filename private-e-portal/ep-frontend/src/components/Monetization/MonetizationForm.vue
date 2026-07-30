
<template>
  <el-dialog :model-value="true" :title="monetization ? 'Edit Leave Monetization' : 'Add Leave Monetization'" width="720px" @close="$emit('close')" append-to-body>
    <el-form label-position="top" @submit.prevent novalidate>
      <el-form-item label="Leave Monetization Type" required>
        <el-select v-model.number="form.type_id" style="width: 100%" @change="calculateTotal">
          <el-option label="Regular Monetization" :value="1" />
          <el-option label="Special Monetization" :value="2" />
        </el-select>
      </el-form-item>

      <div v-if="!vlEligible" class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-800">
        You must have at least {{ minVlToApply }} Vacation Leave credits to apply for monetization.
      </div>
      <div v-else class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-900">
        You must retain {{ minVlRetain }} VL credits. Maximum VL you can monetize:
        <span class="font-semibold">{{ formatNumber(maxVlToMonetize, 3) }}</span>
        (current balance {{ formatNumber(vlCreditBalance, 3) }}).
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <el-form-item label="Vacation Leave Credits">
            <el-input v-model="form.vl_credit" readonly />
            <div class="text-xs text-slate-500 mt-1">
              Remaining after monetization: {{ formatNumber(remainingVlAfterMonetize, 3) }}
              (minimum retain: {{ minVlRetain }})
            </div>
          </el-form-item>
          <el-form-item label="Vacation Leave to Monetize">
            <el-input
              v-model.number="form.vl_to_monetize"
              type="number"
              :min="0"
              :max="maxVlToMonetize"
              :disabled="!vlEligible"
              @input="calculateTotal"
            />
          </el-form-item>
        </div>

        <div v-if="Number(form.type_id) === 2">
          <el-form-item label="Sick Leave Credits">
            <el-input v-model="form.sl_credit" readonly />
            <div class="text-xs text-slate-500 mt-1">Remaining after monetization: {{ formatNumber((parseFloat(form.sl_credit)||0) - (parseFloat(form.sl_to_monetize)||0), 3) }}</div>
          </el-form-item>
          <el-form-item label="Sick Leave to Monetize">
            <el-input v-model.number="form.sl_to_monetize" type="number" @input="calculateTotal" />
          </el-form-item>
        </div>
      </div>

      <el-form-item label="Total Days">
        <el-input v-model.number="form.total_days" readonly />
      </el-form-item>

      <el-form-item label="Amount">
        <el-input :value="formatCurrency(form.amount)" readonly />
        <div class="text-xs text-slate-500 mt-1" v-if="form.total_days > 0">
          <strong>Calculation Formula:</strong> (Salary ÷ 22) × Total Days × Conversion Factor Rate
          <br>
          <span v-if="salary > 0">
            = ({{ formatCurrency(salary) }} ÷ 22) × {{ form.total_days }} × {{ (cf_rate || 0.0481927).toFixed(7) }}
            <br>
            = {{ formatCurrency((salary / 22) || 0) }} × {{ form.total_days }} × {{ (cf_rate || 0.0481927).toFixed(7) }}
            <br>
            = <strong>{{ formatCurrency(form.amount) }}</strong>
          </span>
          <span v-else class="text-amber-600">Please wait while loading salary information...</span>
        </div>
      </el-form-item>

      <el-form-item label="Attach Supporting Documents" required>
        <input ref="fileInput" type="file" @change="handleFileChange" accept=".jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.pdf" multiple class="w-full" />
        <div class="text-xs text-slate-500 mt-1">Accepted: Images, Excel, Word, PDF. Max 5 files, 25MB each.</div>
      </el-form-item>

      <div v-if="attachments.length" class="space-y-2">
        <div v-for="(file, index) in attachments" :key="index" class="flex items-center justify-between p-2 bg-slate-50 rounded border">
          <span class="text-sm text-slate-700">{{ file.name }}</span>
          <el-button type="danger" link @click="removeFile(index)"><el-icon><Close /></el-icon></el-button>
        </div>
      </div>
    </el-form>

    <template #footer>
      <div class="flex justify-end gap-2">
        <el-button @click="$emit('close')">Cancel</el-button>
        <el-button type="primary" :loading="isSubmitting" :disabled="!vlEligible" @click="handleSubmit">{{ isSubmitting ? 'Saving...' : (monetization ? 'Update' : 'Save') }}</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { useToast } from 'vue-toastification'
import {
  VL_MONETIZATION_MIN_BALANCE,
  VL_MONETIZATION_MIN_RETAIN
} from '../../composables/useLeaveMonetization.js'

export default {
  name: 'MonetizationForm',
  props: {
    monetization: {
      type: Object,
      default: null
    },
    balances: {
      type: Array,
      default: () => []
    }
  },

  data() {
    return {
      isSubmitting: false,
      form: {
        type_id: 1,
        vl_credit: '0.000',
        vl_to_monetize: 0,
        sl_credit: '0.000',
        sl_to_monetize: 0,
        total_days: 0,
        amount: 0
      },
      attachments: [],
      toast: null,
      salary: 0,
      cf_rate: 0.0481927,
      minVlToApply: VL_MONETIZATION_MIN_BALANCE,
      minVlRetain: VL_MONETIZATION_MIN_RETAIN
    }
  },
  computed: {
    vlCreditBalance() {
      return parseFloat(this.form.vl_credit) || 0
    },
    vlEligible() {
      return this.vlCreditBalance >= this.minVlToApply
    },
    maxVlToMonetize() {
      return Math.max(0, this.vlCreditBalance - this.minVlRetain)
    },
    remainingVlAfterMonetize() {
      return this.vlCreditBalance - (parseFloat(this.form.vl_to_monetize) || 0)
    }
  },
  async mounted() {
    this.toast = useToast()
    await this.loadLeaveBalances()
    if (this.monetization) {
      this.loadMonetizationData()
    }
  },
  watch: {
    balances: {
      handler(newVal) {
        if (newVal && newVal.length > 0) {
          // Reload balances when prop changes
          this.loadLeaveBalances()
        }
      },
      immediate: true,
      deep: true
    }
  },
     methods: {
     formatNumber(value, decimals = 2) {
       const n = Number(value)
       return isFinite(n) ? n.toFixed(decimals) : '0.000'
     },
     formatCurrency(value) {
       const n = Number(value)
       if (!isFinite(n)) return '₱0.00'
       return '₱' + n.toLocaleString('en-US', { 
         minimumFractionDigits: 2, 
         maximumFractionDigits: 2 
       })
     },
     async loadLeaveBalances() {
      try {
        // Always fetch directly from API to ensure we have the latest data
        const ApiService = (await import('../../services/api.js')).default
        const userData = localStorage.getItem('user_data')
        const userId = userData ? JSON.parse(userData).id : null
        
        console.log('Loading leave balances for user ID:', userId)
        
        if (!userId) {
          console.warn('No user ID found, cannot load leave balances')
          this.form.vl_credit = '0.000'
          this.form.sl_credit = '0.000'
          return
        }
        
        // Fetch from monetization endpoint which returns actual leave_credits from leave_credits table
        const monetizationRes = await ApiService.getLeaveMonetization(userId)
        console.log('Full API response:', monetizationRes)
        
        // Store salary and cf_rate for amount calculation
        if (monetizationRes && monetizationRes.success && monetizationRes.data) {
          this.salary = Number(monetizationRes.data.salary) || 0
          this.cf_rate = Number(monetizationRes.data.cf_rate) || 0.0481927
          const rules = monetizationRes.data.vl_monetization_rules || {}
          this.minVlToApply = Number(rules.min_vl_balance_to_apply) || VL_MONETIZATION_MIN_BALANCE
          this.minVlRetain = Number(rules.min_vl_balance_to_retain) || VL_MONETIZATION_MIN_RETAIN
        }
        
        let vlBalance = 0
        let slBalance = 0
        
        if (monetizationRes && monetizationRes.success && monetizationRes.data && monetizationRes.data.leave_balances) {
          const leaveBalances = monetizationRes.data.leave_balances
          console.log('Leave balances from API:', leaveBalances, 'for user ID:', userId)
          
          // Find Vacation Leave (leave_type_id = 16)
          const vlItem = leaveBalances.find(b => {
            const typeId = Number(b.leave_type_id)
            const typeName = String(b.type || '').toLowerCase()
            console.log('Checking item for VL:', { typeId, typeName, balance: b.balance, userId })
            return typeId === 16 || typeName.includes('vacation')
          })
          
          // Find Sick Leave (leave_type_id = 3)
          const slItem = leaveBalances.find(b => {
            const typeId = Number(b.leave_type_id)
            const typeName = String(b.type || '').toLowerCase()
            console.log('Checking item for SL:', { typeId, typeName, balance: b.balance, userId })
            return typeId === 3 || typeName.includes('sick')
          })
          
          if (vlItem) {
            vlBalance = Number(vlItem.balance) || 0
            console.log('Found VL balance:', vlBalance, 'for user ID:', userId)
          } else {
            console.warn('Vacation Leave not found in API response')
          }
          
          if (slItem) {
            slBalance = Number(slItem.balance) || 0
            console.log('Found SL balance:', slBalance, 'for user ID:', userId)
          } else {
            console.warn('Sick Leave not found in API response')
          }
        } else {
          console.error('Invalid API response structure:', monetizationRes)
        }
        
        // Set the form values
        this.form.vl_credit = vlBalance.toFixed(3)
        this.form.sl_credit = slBalance.toFixed(3)
        
        console.log('=== FINAL BALANCES === (user ID:', userId, ')')
        console.log('Vacation Leave Credit:', this.form.vl_credit)
        console.log('Sick Leave Credit:', this.form.sl_credit)
        console.log('======================')
        
      } catch (error) {
        console.error('Error loading leave balances:', error)
        this.toast?.error('Failed to load leave balances. Please try again.')
        // Ensure form has default values even on error
        this.form.vl_credit = '0.000'
        this.form.sl_credit = '0.000'
      }
    },
    loadMonetizationData() {
      if (this.monetization) {
        this.form = {
          type_id: Number(this.monetization.type_id) || 1,
          vl_credit: this.monetization.vl_credit ?? '0.000',
          vl_to_monetize: this.monetization.vl_to_monetize ?? 0,
          sl_credit: this.monetization.sl_credit ?? '0.000',
          sl_to_monetize: this.monetization.sl_to_monetize ?? 0,
          total_days: this.monetization.total_days ?? 0,
          amount: this.monetization.amount ?? 0
        }
        this.attachments = this.monetization.attachments || []
      }
    },
    calculateTotal() {
      const vl = parseFloat(this.form.vl_to_monetize) || 0
      const sl = parseFloat(this.form.sl_to_monetize) || 0
      this.form.total_days = vl + sl
      
      // Calculate amount: (salary / 22) * total_days * cf_rate
      // Formula: Daily Salary × Total Days × Conversion Factor Rate
      const dailySalary = (this.salary / 22) || 0
      const totalDays = this.form.total_days || 0
      const cfRate = this.cf_rate || 0.0481927
      const calculatedAmount = dailySalary * totalDays * cfRate
      // Round to 2 decimal places for currency
      this.form.amount = Math.round(calculatedAmount * 100) / 100
    },
    handleFileChange(event) {
      const input = event.target
      const files = input && input.files ? Array.from(input.files) : []
      if (!files || files.length === 0) {
        // Some browsers suppress files when validation tooltip is active; retry on next tick
        setTimeout(() => {
          const again = input && input.files ? Array.from(input.files) : []
          if (again && again.length > 0) {
            this.processSelectedFiles(again)
          }
        }, 0)
        return
      }
      this.processSelectedFiles(files)
    },
    processSelectedFiles(files) {
      files.forEach(file => {
        const allowedTypes = [
          'image/jpeg','image/jpg','image/png',
          'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'application/pdf'
        ]
        if (!allowedTypes.includes(file.type)) {
          this.toast.error(`Invalid file type: ${file.name}`)
          return
        }
        if (file.size > 25 * 1024 * 1024) {
          this.toast.error(`File too large: ${file.name}`)
          return
        }
        if (this.attachments.length >= 5) {
          this.toast.error('Maximum 5 files allowed')
          return
        }
        this.attachments.push(file)
      })
      // Reset input so the same file can be re-selected if removed
      if (this.$refs.fileInput) this.$refs.fileInput.value = ''
    },
    removeFile(index) {
      this.attachments.splice(index, 1)
    },
    validateVlMonetizationRules() {
      const vlBalance = this.vlCreditBalance
      const vlToMonetize = parseFloat(this.form.vl_to_monetize) || 0

      if (vlBalance < this.minVlToApply) {
        this.toast.error(
          `You must have at least ${this.minVlToApply} Vacation Leave credits to apply for monetization.`
        )
        return false
      }

      if (vlToMonetize > 0) {
        const maxVl = Math.max(0, vlBalance - this.minVlRetain)
        if (vlToMonetize > maxVl) {
          this.toast.error(
            `You must retain at least ${this.minVlRetain} VL credits. Maximum VL you can monetize is ${maxVl.toFixed(3)}.`
          )
          return false
        }
        if (vlBalance - vlToMonetize < this.minVlRetain) {
          this.toast.error(`You must retain at least ${this.minVlRetain} Vacation Leave credits after monetization.`)
          return false
        }
      }

      return true
    },
    async handleSubmit() {
      if (!this.validateVlMonetizationRules()) {
        return
      }

      const vl = parseFloat(this.form.vl_to_monetize) || 0
      const sl = parseFloat(this.form.sl_to_monetize) || 0
      const total = vl + sl
      if (total <= 0) {
        this.toast.error('Please enter days to monetize')
        return
      }
      // Business rule: minimum 10 credits to monetize
      if (total < 10) {
        this.toast.error('Minimum credit amount to monetize is 10.')
        return
      }
      if (this.attachments.length === 0) {
        this.toast.error('Please upload at least one file')
        return
      }
      this.isSubmitting = true
             try {
         const formData = new FormData()
         

         
         Object.keys(this.form).forEach(key => {
           // Ensure numeric values are sent as numbers, not strings
           let value = this.form[key]
           if (key === 'vl_credit' || key === 'sl_credit' || key === 'vl_to_monetize' || key === 'sl_to_monetize' || key === 'total_days' || key === 'amount') {
             value = parseFloat(value) || 0
           } else if (key === 'type_id') {
             // Ensure type_id is sent as integer (1 for Regular, 2 for Special)
             value = parseInt(value) || 1
           }
           formData.append(key, value)
         })
         this.attachments.forEach(file => {
           formData.append('supporting_documents[]', file)
         })

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