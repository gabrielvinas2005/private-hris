<template>
    <div class="p-4 bg-white border rounded-lg shadow">
      <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
          <input 
            v-model="localInfo.email"
            type="email" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter email address"
          >
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Mobile Number</label>
          <input 
            v-model="localInfo.mobile_no"
            type="tel" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter mobile number"
          >
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Telephone Number</label>
          <input 
            v-model="localInfo.tel_no"
            type="tel" 
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter telephone number"
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
  