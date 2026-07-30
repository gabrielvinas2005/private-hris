<template>
  <div class="benefits-index-redirect" aria-hidden="true" />
</template>

<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { ensurePayrollAccessUser, useAuth } from "@/Composables/useAuth";
import {
  BENEFIT_ROUTES,
  PAYROLL_ROUTE_ACCESS,
  payrollNavVisible,
} from "@/config/payrollAccess";

const router = useRouter();
const { user } = useAuth();

onMounted(async () => {
  await ensurePayrollAccessUser();
  for (const b of BENEFIT_ROUTES) {
    if (payrollNavVisible(user.value, PAYROLL_ROUTE_ACCESS[b.name])) {
      await router.replace(b.path);
      return;
    }
  }
  await router.replace({ name: "payroll" });
});
</script>

<style scoped>
.benefits-index-redirect {
  min-height: 40px;
}
</style>
