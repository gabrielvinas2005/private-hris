<template>
  <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <div class="text-center">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Confirm Delete</h3>
        <p class="text-slate-600 mb-6">
          Are you sure you want to delete <strong>{{ itemName }}</strong>?
        </p>
        
        <div class="flex justify-end space-x-3">
          <button 
            @click="cancel"
            class="px-4 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors duration-200"
          >
            Cancel
          </button>
          <button 
            @click="confirm"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200"
            :disabled="isDeleting"
          >
            {{ isDeleting ? 'Deleting...' : 'Yes, Delete' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DeleteModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    itemName: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      isDeleting: false
    }
  },
  methods: {
    cancel() {
      this.$emit('cancel')
    },
    async confirm() {
      this.isDeleting = true
      try {
        await this.$emit('confirm')
      } finally {
        this.isDeleting = false
      }
    }
  }
}
</script> 