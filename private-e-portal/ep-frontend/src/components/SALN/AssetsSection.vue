<template>
  <div class="space-y-8">
    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Confirm Delete</h3>
          </div>
        </div>
        <div class="mb-6">
          <p class="text-sm text-gray-500">
            Are you sure you want to delete this {{ deleteItemType }}? This action cannot be undone.
          </p>
        </div>
        <div class="flex justify-end space-x-3">
          <button
            @click="cancelDelete"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
          >
            Cancel
          </button>
          <button
            @click="confirmDelete"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Save Confirmation Modal -->
    <div v-if="showSaveModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Confirm Save</h3>
          </div>
        </div>
        <div class="mb-6">
          <p class="text-sm text-gray-500">
            Are you sure the information you input is correct?
          </p>
        </div>
        <div class="flex justify-end space-x-3">
          <button
            @click="cancelSave"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
          >
            Cancel
          </button>
          <button
            @click="confirmSave"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Save
          </button>
        </div>
      </div>
    </div>

    <!-- Header -->
    <div class="text-center mb-6">
      <h2 class="text-2xl font-bold text-slate-900 mb-2">
        ASSETS, LIABILITIES AND NETWORTH
      </h2>
      <p class="text-sm text-slate-600">
        (Including those of the spouse and unmarried children below eighteen (18) years of age living in declarant's household)
      </p>
    </div>

    <!-- 1. ASSETS -->
    <div class="space-y-6">
      <h3 class="text-lg font-bold text-slate-900">1. ASSETS</h3>
      
      <!-- Real Properties -->
      <div class="bg-white rounded-lg border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200">
          <h4 class="font-semibold text-slate-900">a. Real Properties*</h4>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Description
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Kind
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Exact Location
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Assessed Value
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Current Fair Market Value
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Acquisition Year
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Acquisition Mode
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Acquisition Cost
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-if="realProperties.length === 0">
                <td colspan="9" class="px-4 py-4 text-center text-slate-500">
                  No real properties found
                </td>
              </tr>
              <tr v-for="(property, index) in realProperties" :key="property.id || index" class="hover:bg-slate-50">
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.description"
                    type="text"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter description"
                  >
                  <span v-else>{{ property.description }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.kind"
                    type="text"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter kind"
                  >
                  <span v-else>{{ property.kind }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.location"
                    type="text"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter location"
                  >
                  <span v-else>{{ property.exact_location }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.assessed_value"
                    type="number"
                    step="0.01"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="0.00"
                  >
                  <span v-else>{{ formatCurrency(property.assessed_value) }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.current_fair_market_value"
                    type="number"
                    step="0.01"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="0.00"
                  >
                  <span v-else>{{ formatCurrency(property.current_fair_market_value) }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.year_acquired"
                    type="number"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="YYYY"
                  >
                  <span v-else>{{ property.acquisition_year }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.mode_of_acquisition"
                    type="text"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter mode"
                  >
                  <span v-else>{{ property.acquisition_mode }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.acquisition_cost"
                    type="number"
                    step="0.01"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="0.00"
                  >
                  <span v-else>{{ formatCurrency(property.acquisition_cost) }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <div v-if="!property.id || property.isEditing" class="flex space-x-2">
                    <button 
                      @click="saveProperty(property, index)"
                      class="text-green-600 hover:text-green-900"
                    >
                      Save
                    </button>
                    <button 
                      @click="cancelEdit(property, index)"
                      class="text-gray-600 hover:text-gray-900"
                    >
                      Cancel
                    </button>
                  </div>
                  <div v-else class="flex space-x-2">
                    <button 
                      @click="editProperty(property)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      Edit
                    </button>
                    <button 
                      @click="deleteProperty(property.id)"
                      class="text-red-600 hover:text-red-900"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
          <button 
            @click="addNewProperty"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
          >
            Add New Property
          </button>
        </div>
      </div>

      <!-- Personal Properties -->
      <div class="bg-white rounded-lg border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-200">
          <h4 class="font-semibold text-slate-900">b. Personal Properties*</h4>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Description
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Year Acquired
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Acquisition Cost/Amount
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-if="personalProperties.length === 0">
                <td colspan="4" class="px-4 py-4 text-center text-slate-500">
                  No personal properties found
                </td>
              </tr>
              <tr v-for="(property, index) in personalProperties" :key="property.id || index" class="hover:bg-slate-50">
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.description"
                    type="text"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter description"
                  >
                  <span v-else>{{ property.description }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.year_acquired"
                    type="number"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="YYYY"
                  >
                  <span v-else>{{ property.year_acquired }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!property.id || property.isEditing"
                    v-model="property.acquisition_cost"
                    type="number"
                    step="0.01"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="0.00"
                  >
                  <span v-else>{{ formatCurrency(property.acquisition_cost) }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <div v-if="!property.id || property.isEditing" class="flex space-x-2">
                    <button 
                      @click="savePersonalProperty(property, index)"
                      class="text-green-600 hover:text-green-900"
                    >
                      Save
                    </button>
                    <button 
                      @click="cancelPersonalEdit(property, index)"
                      class="text-gray-600 hover:text-gray-900"
                    >
                      Cancel
                    </button>
                  </div>
                  <div v-else class="flex space-x-2">
                    <button 
                      @click="editPersonalProperty(property)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      Edit
                    </button>
                    <button 
                      @click="deletePersonalProperty(property.id)"
                      class="text-red-600 hover:text-red-900"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
          <button 
            @click="addNewPersonalProperty"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
          >
            Add New Personal Property
          </button>
        </div>
      </div>
    </div>

    <!-- 2. LIABILITIES -->
    <div class="space-y-6">
      <h3 class="text-lg font-bold text-slate-900">2. LIABILITIES</h3>
      
      <div class="bg-white rounded-lg border border-slate-200">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Nature
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Name of Creditors
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Outstanding Balance
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-if="liabilities.length === 0">
                <td colspan="4" class="px-4 py-4 text-center text-slate-500">
                  No liabilities found
                </td>
              </tr>
              <tr v-for="(liability, index) in liabilities" :key="liability.id || index" class="hover:bg-slate-50">
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!liability.id || liability.isEditing"
                    v-model="liability.nature"
                    type="text"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter nature"
                  >
                  <span v-else>{{ liability.nature }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!liability.id || liability.isEditing"
                    v-model="liability.creditor_name"
                    type="text"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter creditor name"
                  >
                  <span v-else>{{ liability.creditor_name }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <input 
                    v-if="!liability.id || liability.isEditing"
                    v-model="liability.outstanding_balance"
                    type="number"
                    step="0.01"
                    class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="0.00"
                  >
                  <span v-else>{{ formatCurrency(liability.outstanding_balance) }}</span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                  <div v-if="!liability.id || liability.isEditing" class="flex space-x-2">
                    <button 
                      @click="saveLiability(liability, index)"
                      class="text-green-600 hover:text-green-900"
                    >
                      Save
                    </button>
                    <button 
                      @click="cancelLiabilityEdit(liability, index)"
                      class="text-gray-600 hover:text-gray-900"
                    >
                      Cancel
                    </button>
                  </div>
                  <div v-else class="flex space-x-2">
                    <button 
                      @click="editLiability(liability)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      Edit
                    </button>
                    <button 
                      @click="deleteLiability(liability.id)"
                      class="text-red-600 hover:text-red-900"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-200">
          <button 
            @click="addNewLiability"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
          >
            Add New Liability
          </button>
        </div>
      </div>
    </div>

    
  </div>
</template>

<script>
export default {
  name: 'AssetsSection',
  props: {
    realProperties: {
      type: Array,
      default: () => []
    },
    personalProperties: {
      type: Array,
      default: () => []
    },
    liabilities: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      showDeleteModal: false,
      deleteItemType: '',
      deleteItemId: null,
      deleteCallback: null,
      showSaveModal: false,
      saveItemType: '',
      saveItemData: null,
      saveCallback: null
    }
  },

  methods: {
    formatCurrency(amount) {
      if (!amount) return '₱0.00'
      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
      }).format(amount)
    },
    addNewProperty() {
      this.$emit('update', 'add-real-property', {})
    },
    editProperty(property) {
      // Set editing mode for existing property
      property.isEditing = true
    },
    saveProperty(property, index) {
      // Validate required fields
      if (!property.description || !property.kind || !property.location) {
        alert('Please fill in all required fields (Description, Kind, Location)')
        return
      }
      
      // Show save confirmation
      this.showSaveConfirmation('real property', { property, index }, () => {
        // Remove editing flag
        property.isEditing = false
        // Emit save event
        this.$emit('update', 'save-real-property', { property, index })
      })
    },
    cancelEdit(property, index) {
      // If it's a new property (no id), remove it from the list
      if (!property.id) {
        this.$emit('update', 'cancel-new-real-property', { index })
      } else {
        // Reset editing mode for existing property
        property.isEditing = false
      }
    },
    deleteProperty(id) {
      this.showDeleteConfirmation('real property', id, () => {
        this.$emit('update', 'delete-real-property', { id })
      })
    },
    addNewPersonalProperty() {
      this.$emit('update', 'add-personal-property', {})
    },
    editPersonalProperty(property) {
      // Set editing mode for existing property
      property.isEditing = true
    },
    savePersonalProperty(property, index) {
      // Validate required fields
      if (!property.description || !property.year_acquired || !property.acquisition_cost) {
        alert('Please fill in all required fields (Description, Year Acquired, Acquisition Cost)')
        return
      }
      
      // Show save confirmation
      this.showSaveConfirmation('personal property', { property, index }, () => {
        // Remove editing flag
        property.isEditing = false
        // Emit save event
        this.$emit('update', 'save-personal-property', { property, index })
      })
    },
    cancelPersonalEdit(property, index) {
      // If it's a new property (no id), remove it from the list
      if (!property.id) {
        this.$emit('update', 'cancel-new-personal-property', { index })
      } else {
        // Reset editing mode for existing property
        property.isEditing = false
      }
    },
    deletePersonalProperty(id) {
      this.showDeleteConfirmation('personal property', id, () => {
        this.$emit('update', 'delete-personal-property', { id })
      })
    },
    addNewLiability() {
      this.$emit('update', 'add-liability', {})
    },
    editLiability(liability) {
      // Set editing mode for existing liability
      liability.isEditing = true
    },
    saveLiability(liability, index) {
      // Validate required fields
      if (!liability.nature || !liability.creditor_name || !liability.outstanding_balance) {
        alert('Please fill in all required fields (Nature, Creditor Name, Outstanding Balance)')
        return
      }
      
      // Show save confirmation
      this.showSaveConfirmation('liability', { liability, index }, () => {
        // Remove editing flag
        liability.isEditing = false
        // Emit save event
        this.$emit('update', 'save-liability', { liability, index })
      })
    },
    cancelLiabilityEdit(liability, index) {
      // If it's a new liability (no id), remove it from the list
      if (!liability.id) {
        this.$emit('update', 'cancel-new-liability', { index })
      } else {
        // Reset editing mode for existing liability
        liability.isEditing = false
      }
    },
    deleteLiability(id) {
      this.showDeleteConfirmation('liability', id, () => {
        this.$emit('update', 'delete-liability', { id })
      })
    },
    showDeleteConfirmation(itemType, itemId, callback) {
      this.deleteItemType = itemType
      this.deleteItemId = itemId
      this.deleteCallback = callback
      this.showDeleteModal = true
    },
    confirmDelete() {
      if (this.deleteCallback) {
        this.deleteCallback()
      }
      this.showDeleteModal = false
      this.resetDeleteModal()
    },
    cancelDelete() {
      this.showDeleteModal = false
      this.resetDeleteModal()
    },
    resetDeleteModal() {
      this.deleteItemType = ''
      this.deleteItemId = null
      this.deleteCallback = null
    },
    showSaveConfirmation(itemType, itemData, callback) {
      this.saveItemType = itemType
      this.saveItemData = itemData
      this.saveCallback = callback
      this.showSaveModal = true
    },
    confirmSave() {
      if (this.saveCallback) {
        this.saveCallback()
      }
      this.showSaveModal = false
      this.resetSaveModal()
    },
    cancelSave() {
      this.showSaveModal = false
      this.resetSaveModal()
    },
    resetSaveModal() {
      this.saveItemType = ''
      this.saveItemData = null
      this.saveCallback = null
    }
  }
}
</script> 