<template>
  <div class="signatories-container">
    <el-card class="groupCard">
      <div class="groupHeader">Signatories</div>

      <el-form-item label="Certified Correct">
        <el-select
          v-model="model.certified_correct"
          filterable
          clearable
          placeholder="Select signatory"
          @change="handleSignatorySelect"
        >
          <el-option
            v-for="emp in employeeOptions"
            :key="emp.id"
            :label="emp.name"
            :value="emp.name"
          />
        </el-select>
      </el-form-item>

      <el-form-item label="Position/Designation">
        <el-input v-model="model.position" disabled />
      </el-form-item>
    </el-card>
  </div>
</template>

<script setup>
import { reactive, watch } from "vue";

const props = defineProps({
  signatories: { type: Object, required: true },
  employeeOptions: { type: Array, default: () => [] },
});

const model = reactive({
  certified_correct: props.signatories.certified_correct || "",
  position: props.signatories.position || "",
});

const emit = defineEmits(["update:signatories"]);

const handleSignatorySelect = (selectedName) => {
  if (!selectedName) {
    model.position = "";
    return;
  }
  const emp = (props.employeeOptions || []).find((e) => e.name === selectedName);
  model.position = emp?.position || "";
};

watch(
  () => model,
  () => emit("update:signatories", { ...model }),
  { deep: true }
);

watch(
  () => props.signatories,
  (val) => Object.assign(model, val || {}),
  { deep: true }
);
</script>

<style scoped>
.signatories-container {
  display: flex;
  justify-content: center;
  margin-top: 1rem;
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
  font-size: 0.875rem;
  line-height: 1.4;
  margin-bottom: 16px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}
</style>
