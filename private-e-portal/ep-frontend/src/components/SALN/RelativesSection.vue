<template>
  <div class="space-y-6">
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
            Are you sure you want to delete this relative? This action cannot be undone.
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
        RELATIVES IN THE GOVERNMENT SERVICE
      </h2>
      <p class="text-sm text-slate-600">
        (Within the Fourth Degree of Consanguinity or Affinity. Include also Biles, Balae and Inso)
      </p>
    </div>

    <!-- No Relatives Checkbox -->
    <div class="bg-slate-50 rounded-lg p-4">
      <label class="flex items-center space-x-2">
        <input 
          type="checkbox" 
          v-model="noRelatives"
          class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
        >
        <span class="text-sm text-slate-700">
          I/ We do not have any relatives in government service.
        </span>
      </label>
    </div>

    <!-- Relatives Table -->
    <div class="bg-white rounded-lg border border-slate-200">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                Name of Relative
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                Relationship
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                Position
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                Name of Agency/Office and Address
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-if="relatives.length === 0">
              <td colspan="5" class="px-4 py-4 text-center text-slate-500">
                No relatives found
              </td>
            </tr>
            <tr v-for="(relative, index) in relatives" :key="relative.id || index" class="hover:bg-slate-50">
              <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                <input 
                  v-if="!relative.id || relative.isEditing"
                  v-model="relative.relatives_name"
                  type="text"
                  class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter relative name"
                >
                <span v-else>{{ relative.relatives_name }}</span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                <input 
                  v-if="!relative.id || relative.isEditing"
                  v-model="relative.relationship"
                  type="text"
                  class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter relationship"
                >
                <span v-else>{{ relative.relationship }}</span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                <input 
                  v-if="!relative.id || relative.isEditing"
                  v-model="relative.position"
                  type="text"
                  class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter position"
                >
                <span v-else>{{ relative.position }}</span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                <input 
                  v-if="!relative.id || relative.isEditing"
                  v-model="relative.office_address"
                  type="text"
                  class="w-full px-2 py-1 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter office address"
                >
                <span v-else>{{ relative.office_address }}</span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-slate-900">
                <div v-if="!relative.id || relative.isEditing" class="flex space-x-2">
                  <button 
                    @click="saveRelative(relative, index)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Save
                  </button>
                  <button 
                    @click="cancelEdit(relative, index)"
                    class="text-gray-600 hover:text-gray-900"
                  >
                    Cancel
                  </button>
                </div>
                <div v-else class="flex space-x-2">
                  <button 
                    @click="editRelative(relative)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Edit
                  </button>
                  <button 
                    @click="deleteRelative(relative.id)"
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
          @click="addNewRelative"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
        >
          Add New Relative
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'RelativesSection',
  props: {
    relatives: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      noRelatives: false,
      showDeleteModal: false,
      deleteItemId: null,
      deleteCallback: null,
      showSaveModal: false,
      saveItemData: null,
      saveCallback: null
    }
  },
  methods: {
    addNewRelative() {
      this.$emit('update', 'add-relative', {})
    },
    editRelative(relative) {
      relative.isEditing = true
    },
    saveRelative(relative, index) {
      // Validate required fields
      if (!relative.relatives_name || !relative.relationship || !relative.position || !relative.office_address) {
        alert('Please fill in all required fields (Name, Relationship, Position, Office Address)')
        return
      }
      
      // Show save confirmation
      this.showSaveConfirmation({ relative, index }, () => {
        // Remove editing flag
        relative.isEditing = false
        // Emit save event
        this.$emit('update', 'save-relative', { relative, index })
      })
    },
    cancelEdit(relative, index) {
      // If it's a new relative (no id), remove it from the list
      if (!relative.id) {
        this.$emit('update', 'cancel-new-relative', { index })
      } else {
        // Reset editing mode for existing relative
        relative.isEditing = false
      }
    },
    deleteRelative(id) {
      this.showDeleteConfirmation(id, () => {
        this.$emit('update', 'delete-relative', { id })
      })
    },
    showDeleteConfirmation(itemId, callback) {
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
      this.deleteItemId = null
      this.deleteCallback = null
    },
    showSaveConfirmation(itemData, callback) {
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
      this.saveItemData = null
      this.saveCallback = null
    }
  }
}
</script> 