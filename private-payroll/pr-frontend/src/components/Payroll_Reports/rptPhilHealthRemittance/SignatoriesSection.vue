<template>
  <div class="signatories-container">
    <el-card class="groupCard">
      <div class="groupHeader">Signatories</div>

      <el-form-item label="Certified Correct">
        <el-input v-model="model.certified_correct" />
      </el-form-item>

      <el-form-item label="Position/Designation">
        <el-input v-model="model.position" />
      </el-form-item>
    </el-card>
  </div>
</template>

<script setup>
import { reactive, watch } from "vue";

const props = defineProps({
  signatories: { type: Object, required: true },
});

const model = reactive({
  certified_correct: props.signatories.certified_correct || "",
  position: props.signatories.position || "",
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
</style>
