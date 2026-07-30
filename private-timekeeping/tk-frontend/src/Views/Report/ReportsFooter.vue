<template>
  <div class="report-footer-section" style="flex-shrink: 0; margin-top: auto; page-break-inside: avoid; background: white;">
    <div class="report-footer" style="width: 100%; page-break-inside: avoid;">
      <!-- One-line footer, no borders -->
      <table style="width: 100%; border-collapse: collapse; border: none; font-size: 10px; color: #666;">
        <tr>
          <td style="text-align: left; vertical-align: top; border: none; padding: 0;">
            <span style="font-weight: 500;">Printed on: {{ printDate }}</span>
          </td>
          <td style="text-align: right; vertical-align: top; border: none; padding: 0; white-space: nowrap;">
            <span style="font-weight: 500;">Page <span style="font-weight: 600; color: #333;">{{ currentPage }}</span> of <span style="font-weight: 600; color: #333;">{{ totalPages }}</span></span>
          </td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  printDate: { type: String, default: () => new Date().toLocaleDateString() },
  additionalInfo: { type: String, default: '' },
  currentPage: { type: [String, Number], default: 1 },
  totalPages: { type: [String, Number], default: 1 },
  paperSize: { type: String, default: 'A4' }
})
</script>

<style scoped>
</style>


