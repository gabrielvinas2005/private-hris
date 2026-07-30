<template>
  <el-dialog
    v-model="visible"
    title="Generate Loyalty Award Report"
    width="70%"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="formData"
      :rules="formRules"
      label-width="150px"
      v-loading="loading"
    >
      <el-row :gutter="20">
        <!-- <el-col :span="12">
          <el-form-item label="Branch" prop="branch_id">
            <el-select 
              v-model="formData.branch_id" 
              placeholder="Select Branch"
              style="width: 100%"
              @change="handleBranchChange"
            >
              <el-option 
                v-for="branch in branches" 
                :key="branch.id" 
                :label="branch.name" 
                :value="branch.id" 
              />
            </el-select>
          </el-form-item>
        </el-col> -->
        <el-col :span="12">
          <el-form-item label="Period" prop="payroll_period_id">
            <el-select
              v-model="formData.payroll_period_id"
              placeholder="Select Period"
              style="width: 100%"
              @change="handlePeriodChange"
            >
              <el-option
                v-for="period in payrollPeriodTypes"
                :key="period.id"
                :label="period.name"
                :value="period.id"
              />
            </el-select>
          </el-form-item>
        </el-col>
      </el-row>

      <!-- Signatory Section -->
      <el-divider content-position="left">Report Signatories</el-divider>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Signatory 1">
            <el-input
              v-model="formData.signatory_1"
              placeholder="Enter name"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Position 1">
            <el-input
              v-model="formData.signatory_position_1"
              placeholder="Enter position"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Signatory 2">
            <el-input
              v-model="formData.signatory_2"
              placeholder="Enter name"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Position 2">
            <el-input
              v-model="formData.signatory_position_2"
              placeholder="Enter position"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Signatory 3">
            <el-input
              v-model="formData.signatory_3"
              placeholder="Enter name"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Position 3">
            <el-input
              v-model="formData.signatory_position_3"
              placeholder="Enter position"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Signatory 4">
            <el-input
              v-model="formData.signatory_4"
              placeholder="Enter name"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Position 4">
            <el-input
              v-model="formData.signatory_position_4"
              placeholder="Enter position"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="12">
          <el-form-item label="Signatory 5">
            <el-input
              v-model="formData.signatory_5"
              placeholder="Enter name"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
        <el-col :span="12">
          <el-form-item label="Position 5">
            <el-input
              v-model="formData.signatory_position_5"
              placeholder="Enter position"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <!-- Description Section -->
      <el-divider content-position="left">Signatory Descriptions</el-divider>

      <el-row :gutter="20">
        <el-col :span="24">
          <el-form-item label="Description 1">
            <el-input
              v-model="formData.description_1"
              placeholder="Enter description"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="24">
          <el-form-item label="Description 2">
            <el-input
              v-model="formData.description_2"
              placeholder="Enter description"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="24">
          <el-form-item label="Description 3">
            <el-input
              v-model="formData.description_3"
              placeholder="Enter description"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="24">
          <el-form-item label="Description 4">
            <el-input
              v-model="formData.description_4"
              placeholder="Enter description"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>

      <el-row :gutter="20">
        <el-col :span="24">
          <el-form-item label="Description 5">
            <el-input
              v-model="formData.description_5"
              placeholder="Enter description"
              style="width: 100%"
            />
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">Cancel</el-button>
        <el-button
          type="primary"
          @click="handleGenerateReport"
          :loading="loading"
          :disabled="!isFormValid"
          icon="el-icon-printer"
        >
          Generate Report
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useLoyaltyAward } from "../../../Composables/useLoyaltyAward.js";

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:modelValue", "report-generated", "close"]);

const { loading, branches, loadReportData, generateReport } = useLoyaltyAward();

// Local state
const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
});

const formRef = ref(null);
const payrollPeriodTypes = ref([]);

const formData = ref({
  payroll_period_id: null,
  signatory_1: "",
  signatory_position_1: "",
  description_1: "",
  signatory_2: "",
  signatory_position_2: "",
  description_2: "",
  signatory_3: "",
  signatory_position_3: "",
  description_3: "",
  signatory_4: "",
  signatory_position_4: "",
  description_4: "",
  signatory_5: "",
  signatory_position_5: "",
  description_5: "",
});

const formRules = {
  payroll_period_id: [
    { required: true, message: "Please select period", trigger: "change" },
  ],
};

// Computed properties
const isFormValid = computed(() => {
  return formData.value.payroll_period_id;
});

// Watch for dialog visibility changes
watch(visible, async (newVal) => {
  if (newVal) {
    await initializeForm();
  }
});

const initializeForm = async () => {
  try {
    const data = await loadReportData();
    payrollPeriodTypes.value = data.payroll_period_types || [];

    // Reset form data
    formData.value = {
      payroll_period_id: null,
      signatory_1: "",
      signatory_position_1: "",
      description_1: "",
      signatory_2: "",
      signatory_position_2: "",
      description_2: "",
      signatory_3: "",
      signatory_position_3: "",
      description_3: "",
      signatory_4: "",
      signatory_position_4: "",
      description_4: "",
      signatory_5: "",
      signatory_position_5: "",
      description_5: "",
    };
  } catch (error) {
    console.error("Error initializing report form:", error);
  }
};

const handleBranchChange = () => {
  // Load signatory data for the selected branch if needed
};

const handlePeriodChange = () => {
  // Handle period change if needed
};

const handleGenerateReport = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();

    const requestData = {
      payroll_period_id: formData.value.payroll_period_id,
      signatory_1: formData.value.signatory_1,
      signatory_position_1: formData.value.signatory_position_1,
      description_1: formData.value.description_1,
      signatory_2: formData.value.signatory_2,
      signatory_position_2: formData.value.signatory_position_2,
      description_2: formData.value.description_2,
      signatory_3: formData.value.signatory_3,
      signatory_position_3: formData.value.signatory_position_3,
      description_3: formData.value.description_3,
      signatory_4: formData.value.signatory_4,
      signatory_position_4: formData.value.signatory_position_4,
      description_4: formData.value.description_4,
      signatory_5: formData.value.signatory_5,
      signatory_position_5: formData.value.signatory_position_5,
      description_5: formData.value.description_5,
    };

    await generateReport(requestData);
    emit("report-generated");
    handleClose();
  } catch (error) {
    console.error("Error generating report:", error);
  }
};

const handleClose = () => {
  visible.value = false;
  emit("close");
};
</script>

<style scoped>
.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* Enhanced form styling */
:deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
}

:deep(.el-input__inner),
:deep(.el-select .el-input__inner) {
  border-radius: 8px;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
}

:deep(.el-input__inner:focus),
:deep(.el-select .el-input__inner:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Button styling */
.el-button {
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.el-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.el-button.is-loading {
  transform: none;
  box-shadow: none;
}

/* Divider styling */
:deep(.el-divider__text) {
  background-color: #f8fafc;
  color: #374151;
  font-weight: 600;
  padding: 0 20px;
}

/* Responsive design */
@media (max-width: 768px) {
  .dialog-footer {
    flex-direction: column;
  }

  .dialog-footer .el-button {
    width: 100%;
  }
}
</style>
