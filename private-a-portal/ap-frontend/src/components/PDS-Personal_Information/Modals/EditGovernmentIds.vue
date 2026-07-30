<template>
    <div class="p-4 bg-white border rounded-lg shadow">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Government IDs</h3>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">TIN</label>
          <input 
            v-model="localInfo.tin_no"
            type="text" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter TIN"
          >
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">SSS</label>
          <input 
            v-model="localInfo.sss_no"
            type="text" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter SSS number"
          >
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">GSIS</label>
          <input 
            v-model="localInfo.gsis_no"
            type="text" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter GSIS number"
          >
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Pag-IBIG</label>
          <input 
            v-model="localInfo.pagibig_no"
            type="text" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter Pag-IBIG number"
          >
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">PhilHealth</label>
          <input 
            v-model="localInfo.philhealth_no"
            type="text" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter PhilHealth number"
          >
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  info: { type: Object, required: true }
})

const emit = defineEmits(['update'])

const localInfo = ref({ ...props.info })

watch(localInfo, (newValue) => {
  emit('update', newValue)
}, { deep: true })

watch(() => props.info, (newValue) => {
  localInfo.value = { ...newValue }
}, { deep: true })
</script>
  