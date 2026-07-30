<template>
  <div class="signatories-container">
    <div class="center-wrapper">
      <el-card class="groupCard">
        <div class="groupHeader">Signatory</div>
        <el-form label-width="180px" class="signatory-form">
          <el-form-item label="Authorized Representative" class="form-item">
            <el-input v-model="model.signatory" />
          </el-form-item>

          <el-form-item label="OIC-Municipal Accountant" class="form-item">
            <el-input v-model="model.accsignatory" />
          </el-form-item>

          <el-form-item label="Position/Designation" class="form-item">
            <el-input v-model="model.position" />
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
});

const model = reactive({
  signatory: props.signatories.signatory || "",
  accsignatory: props.signatories.accsignatory || "",
  position: props.signatories.position || "",
  date: props.signatories.date || new Date(),
});

const emit = defineEmits(["update:signatories"]);

watch(
  () => model,
  () => emit("update:signatories", { ...model }),
  { deep: true },
);

watch(
  () => props.signatories,
  (val) => Object.assign(model, val || {}),
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
