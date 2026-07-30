<template>
  <PageScaffold
    title="Payroll Benefits"
    subtitle="Manage all payroll benefit types in one place"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Benefits' },
    ]"
  >
    <!-- Benefit type selector (Income/Deduction-style UX) -->
    <el-row :gutter="16" class="mb-3">
      <el-col :md="12">
        <el-form label-width="140px">
          <el-form-item label="Benefit Type:">
            <el-select
              v-model="selectedBenefitRoute"
              placeholder="Select benefit type"
              style="width: 100%"
              @change="handleBenefitChange"
            >
              <el-option
                v-for="benefit in benefitOptions"
                :key="benefit.path"
                :label="benefit.label"
                :value="benefit.path"
              />
            </el-select>
          </el-form-item>
        </el-form>
      </el-col>
    </el-row>

    <!-- Existing benefit pages render here; their tables stay as–is -->
    <RouterView />
  </PageScaffold>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useRoute, useRouter, RouterView } from "vue-router";
import PageScaffold from "@/components/PageScaffold.vue";
import {
  BENEFIT_ROUTES,
  PAYROLL_ROUTE_ACCESS,
  payrollNavVisible,
} from "@/config/payrollAccess";
import { useAuth, ensurePayrollAccessUser } from "@/Composables/useAuth";

const route = useRoute();
const router = useRouter();
const { user } = useAuth();

const benefitOptions = computed(() =>
  BENEFIT_ROUTES.filter((b) =>
    payrollNavVisible(user.value, PAYROLL_ROUTE_ACCESS[b.name]),
  ),
);

const selectedBenefitRoute = ref("");

const currentBenefitRoute = computed(() => route.path);

watch(
  [currentBenefitRoute, benefitOptions],
  () => {
    const path = route.path;
    const opts = benefitOptions.value;
    const matched = opts.find((item) => path.startsWith(item.path));
    selectedBenefitRoute.value = matched
      ? matched.path
      : opts[0]?.path ?? "";
  },
  { immediate: true },
);

const handleBenefitChange = (newRoute) => {
  if (!newRoute || newRoute === route.path) return;
  router.push(newRoute);
};

onMounted(() => {
  ensurePayrollAccessUser();
});
</script>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
}
</style>
