<template>
  <div class="report-header-section" style="flex-shrink: 0; margin-bottom: 30px;">
    <div class="report-header" style="width: 100%; page-break-inside: avoid;">
      <!-- DomPDF-safe table layout (CSS Grid/Flex are not supported) -->
      <table style="width: 100%; border-collapse: collapse;">
        <tr>
          <td style="width: 120px; text-align: center; vertical-align: middle;">
            <img v-if="logoUrl" :src="logoUrl" alt="Company Logo" style="max-width: 120px; max-height: 60px;" />
          </td>
          <td style="text-align: center; vertical-align: middle; padding: 0 8px;">
            <div style="font-weight: 700; color: #222; margin: 0 0 4px 0; line-height: 1.2; font-size: 18px;">
              {{ departmentName }}
            </div>
            <div style="font-weight: 800; color: #111; margin: 0; line-height: 1.2; letter-spacing: 0.5px; font-size: 16px; text-transform: uppercase;">
              {{ organizationName || 'COMPANY NAME' }}
            </div>
          </td>
          <td style="width: 120px; text-align: center; vertical-align: middle;">
            <img :src="'/dist/img/reports/Pilipinas_logo.png'" alt="Pilipinas Logo" style="max-width: 120px; max-height: 60px;" />
          </td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  departmentName: { type: String, default: 'Department Of Trade and Industry' },
  organizationName: { type: String, default: '' },
  logoUrl: { type: String, default: '' },
  paperSize: { type: String, default: 'A4' }
})
</script>

<style scoped>
</style>


