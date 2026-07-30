<template>
    <div class="bg-white rounded-lg shadow-sm border">
      <!-- Section Header -->
      <div class="bg-white text-gray-800 px-6 py-3 rounded-t-lg border-b">
        <h2 class="text-sm font-semibold flex items-center">
          <i class="fas fa-id-card mr-2"></i>
           GOVERNMENT ISSUED ID
        </h2>
      </div>
      
      <!-- Form Content -->
      <div class="p-6">
        <div class="flex flex-col gap-6">
          <div>
            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
              GSIS ID NO.
            </label>
            <div class="border-b border-gray-300 pb-1 min-h-[24px]">
              {{ info.gsis_no || '' }}
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
              PAG-IBIG ID NO.
            </label>
            <div class="border-b border-gray-300 pb-1 min-h-[24px]">
              {{ info.pagibig_no || '' }}
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
              PHILHEALTH NO.
            </label>
            <div class="border-b border-gray-300 pb-1 min-h-[24px]">
              {{ info.philhealth_no || '' }}
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
              SSS NO.
            </label>
            <div class="border-b border-gray-300 pb-1 min-h-[24px]">
              {{ info.sss_no || '' }}
            </div>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 uppercase mb-1">
              TIN NO.
            </label>
            <div class="border-b border-gray-300 pb-1 min-h-[24px]">
              {{ info.tin_no || '' }}
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
  