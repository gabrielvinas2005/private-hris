<template>
    <div class="bg-white rounded-lg shadow-sm border">
      <!-- Section Header -->
      <div class="bg-white text-gray-800 px-6 py-3 rounded-t-lg border-b">
        <h2 class="text-sm font-semibold flex items-center">
          <i class="fas fa-phone mr-2"></i>
           CONTACT INFORMATION
        </h2>
      </div>
      
      <!-- Form Content -->
      <div class="p-6">
        <div class="flex flex-col gap-6">
          <div>
            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
              TELEPHONE NO.
            </label>
            <div class="border-b border-gray-300 pb-1 min-h-[24px]">
              {{ info.tel_no || '' }}
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
              MOBILE NO.
            </label>
            <div class="border-b border-gray-300 pb-1 min-h-[24px]">
              {{ info.mobile_no || '' }}
            </div>
          </div>
          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
              E-MAIL ADDRESS
            </label>
            <div class="border-b border-gray-300 pb-1 min-h-[24px]">
              {{ info.email || '' }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  const props = defineProps({
    info: { type: Object, required: true }
  })
  </script>
  