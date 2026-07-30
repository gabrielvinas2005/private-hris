<template>
  <div class="space-y-6">
    <h3 class="text-lg font-semibold text-slate-900 mb-4">IPCR Result Information</h3>
    
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Rating</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Adjectival Rating</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Attachment</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">View</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="result in ipcrResults" :key="result.id" class="hover:bg-slate-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ result.rating || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ result.adjectival_rating || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                {{ result.attachment || 'N/A' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                <button
                  @click="viewIPCR(result.id)"
                  class="text-blue-600 hover:text-blue-800 font-medium"
                  :disabled="!result.attachment"
                >
                  {{ result.attachment ? 'View' : 'No File' }}
                </button>
              </td>
            </tr>
            <tr v-if="ipcrResults.length === 0">
              <td colspan="4" class="px-6 py-4 text-sm text-slate-500 text-center">
                No IPCR results found
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'IPCRResults',
  props: {
    ipcrResults: {
      type: Array,
      default: () => []
    }
  },
  methods: {
    viewIPCR(id) {
      // Navigate to IPCR view page
      this.$router.push(`/201-file/ipcr/${id}`)
    }
  }
}
</script> 