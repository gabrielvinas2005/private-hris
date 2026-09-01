#!/bin/bash
# Stub out public HRIS report views (router no longer routes to them)
STUB='<script setup>\n// Removed: public HRIS report not applicable to private sector.\nimport { onMounted } from "vue";\nimport { useRouter } from "vue-router";\nconst router = useRouter();\nonMounted(() => router.replace("/"));\n</script>\n<template><div /></template>'

BASE="/home/gab/Desktop/Private HRIS/private-payroll/pr-frontend/src/Views/Payroll_Reports"

for f in \
  rptLoyaltyAward.vue rptMonetizationPayroll.vue \
  rptRataPayroll.vue rptSBSISTENCE.vue rptSUBSISTENCE.vue \
  rptLandbankTextReport.vue rptAtmLetterLandbank.vue \
  rptHazardPay.vue rptHazzardPay.vue \
  rptPayrollCommunicationMacco.vue rptATMMidYearBonus.vue \
  rptMidYearIndividualVoucher.vue rptMidYearVoucher.vue rptMidYearBonusHub.vue; do
  printf '%b\n' "$STUB" > "$BASE/$f"
  echo "Stubbed: $f"
done

# Handle the file with special character in name
printf '%b\n' "$STUB" > "$BASE/rptUniform&ClothingAllowance.vue"
echo "Stubbed: rptUniform&ClothingAllowance.vue"

echo "All public HRIS report views stubbed."
