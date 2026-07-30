<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center mb-6">
      <h2 class="text-2xl font-bold text-slate-900 mb-2">
        Sworn Statement of Assets, Liabilities and Net Worth
      </h2>
      <p class="text-sm text-slate-600">
        Note: Husband and wife who are both public officials and employees may file the required statements jointly or separately.
      </p>
    </div>

    <!-- Declarant and Position Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Declarant Information -->
      <div class="space-y-4">
        <div>
          <h3 class="font-bold text-slate-900 mb-2">DECLARANT</h3>
          <p class="text-slate-700 border-b-2 border-slate-300 pb-1">
            {{ employee.name || 'N/A' }}
          </p>
        </div>
        
        <div>
          <h3 class="font-bold text-slate-900 mb-2">Address</h3>
          <p class="text-slate-700 border-b-2 border-slate-300 pb-1">
            {{ formatAddress(address) }}
          </p>
        </div>
        
        <div>
          <h3 class="font-bold text-slate-900 mb-2">Spouse's Name</h3>
          <p class="text-slate-700 border-b-2 border-slate-300 pb-1">
            {{ employee.spouse_name || 'N/A' }}
          </p>
        </div>
      </div>

      <!-- Position Information -->
      <div class="space-y-4">
        <div>
          <h3 class="font-bold text-slate-900 mb-2">POSITION</h3>
          <p class="text-slate-700 border-b-2 border-slate-300 pb-1">
            {{ employee.position || 'N/A' }}
          </p>
        </div>
        
        <div>
          <h3 class="font-bold text-slate-900 mb-2">AGENCY/OFFICE</h3>
          <p class="text-slate-700 border-b-2 border-slate-300 pb-1">
            {{ employee.department || 'N/A' }}
          </p>
        </div>
        
        <div>
          <h3 class="font-bold text-slate-900 mb-2">OFFICE ADDRESS</h3>
          <p class="text-slate-700 border-b-2 border-slate-300 pb-1">
            -
          </p>
        </div>
        
        <div>
          <h3 class="font-bold text-slate-900 mb-2">POSITION (Spouse)</h3>
          <p class="text-slate-700 border-b-2 border-slate-300 pb-1">
            {{ employee.spouse_occupation || 'N/A' }}
          </p>
        </div>
        
        <div>
          <h3 class="font-bold text-slate-900 mb-2">AGENCY/OFFICE (Spouse)</h3>
          <p class="text-slate-700 border-b-2 border-slate-300 pb-1">
            {{ employee.spouse_employer || 'N/A' }}
          </p>
        </div>
        
        <div>
          <h3 class="font-bold text-slate-900 mb-2">OFFICE ADDRESS (Spouse)</h3>
          <p class="text-slate-700 border-b-2 border-slate-300 pb-1">
            {{ employee.spouse_business_address || 'N/A' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Children Information -->
    <div class="mt-8">
      <h3 class="font-bold text-slate-900 mb-4">
        UNMARRIED CHILDREN BELOW EIGHTEEN (18) YEARS OF AGE LIVING IN DECLARANT'S HOUSEHOLD
      </h3>
      
      <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Name
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Birth Date
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Age
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-if="eligibleChildren.length === 0">
                <td colspan="3" class="px-6 py-4 text-center text-slate-500">
                  No eligible children records found
                </td>
              </tr>
              <tr v-for="child in eligibleChildren" :key="child.children_id" class="hover:bg-slate-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ formatChildName(child) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ formatDate(child.child_birthdate) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  {{ calculateAge(child.child_birthdate) }} years old
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PersonalInformation',
  props: {
    employee: {
      type: Object,
      default: () => ({})
    },
    address: {
      type: Object,
      default: () => ({})
    },
    children: {
      type: Array,
      default: () => []
    }
  },
  computed: {
    eligibleChildren() {
      return (this.children || []).filter(child => {
        const age = this.calculateAge(child.child_birthdate)
        return age > 0 && age < 18
      })
    }
  },
  methods: {
    formatAddress(address) {
      if (!address) return 'N/A'
      
      const parts = [
        address.pa_house_no,
        address.pa_village,
        address.pa_street,
        address.pa_brgy ? `Brgy. ${address.pa_brgy}` : null,
        address.pa_city,
        address.pa_province,
        address.pa_region
      ].filter(Boolean)
      
      return parts.join(', ') || 'N/A'
    },
    formatChildName(child) {
      const parts = [
        child.child_name,
        child.child_middlename,
        child.child_lastname
      ].filter(Boolean)
      
      return parts.join(' ')
    },
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      
      const date = new Date(dateString)
      return date.toLocaleDateString('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric'
      })
    },
    calculateAge(birthDate) {
      if (!birthDate) return 0
      
      const birth = new Date(birthDate)
      const today = new Date()
      let age = today.getFullYear() - birth.getFullYear()
      const monthDiff = today.getMonth() - birth.getMonth()
      
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--
      }
      
      return age
    }
  }
}
</script> 