<template>
  <div class="signatories-container">
    <div class="center-wrapper">
      <el-card class="groupCard">
        <div class="groupHeader">Signatory</div>
        <el-form label-width="180px" class="signatory-form">
          <el-form-item label="Authorized Representative" class="form-item">
            <el-select
              v-model="selectedSignatoryId"
              filterable
              clearable
              placeholder="Select signatory"
              @change="handleSignatorySelect"
            >
              <el-option
                v-for="option in employeeOptions"
                :key="option.id"
                :label="option.name"
                :value="option.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Position/Designation" class="form-item">
            <el-input v-model="model.position" disabled />
          </el-form-item>

          <el-form-item label="Date" class="form-item">
            <el-date-picker v-model="model.date" type="date" />
          </el-form-item>
        </el-form>
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, ref } from "vue";

const props = defineProps({
  signatories: { type: Object, required: true },
  employeeOptions: { type: Array, default: () => [] },
});

const model = reactive({
  signatory: props.signatories.signatory || "",
  position: props.signatories.position || "",
  date: props.signatories.date || new Date(),
});
const selectedSignatoryId = ref(null);

const emit = defineEmits(["update:signatories"]);

const handleSignatorySelect = (value) => {
  if (!value) {
    selectedSignatoryId.value = null;
    model.signatory = "";
    model.position = "";
    return;
  }

  const selected = props.employeeOptions.find((option) => option.id === value);
  selectedSignatoryId.value = value;
  model.signatory = selected?.name || "";
  model.position = selected?.position || "";
};

const syncSelectedSignatory = () => {
  if (!model.signatory) {
    selectedSignatoryId.value = null;
    return;
  }

  const option = props.employeeOptions.find((opt) => opt.name === model.signatory);
  selectedSignatoryId.value = option ? option.id : null;
};

watch(
  () => model,
  () => emit("update:signatories", { ...model }),
  { deep: true },
);

watch(
  () => props.signatories,
  (val) => {
    Object.assign(model, val || {});
    syncSelectedSignatory();
  },
  { deep: true },
);

watch(
  () => props.employeeOptions,
  () => {
    syncSelectedSignatory();
  },
  { deep: true },
);
</script>

<style scoped>
.signatories-container {
  display: flex;
  justify-content: center;
  margin-top: 1rem;
  width: 100%;
}

.center-wrapper {
  display: flex;
  justify-content: center;
  width: 100%;
}

.groupCard {
  border: 1px solid #e0e0e0;
  border-radius: 25px;
  width: 50%;
}

.groupHeader {
  font-weight: 600;
  color: #333;
  padding: 0.5rem 0;
  font-size: 1rem;
  line-height: 1.4;
  margin-bottom: 16px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}

.signatory-form {
  margin-top: 1rem;
}

.form-item :deep(.el-form-item__label) {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 500;
}
</style>
