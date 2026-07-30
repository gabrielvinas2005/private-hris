<template>
  <div class="report-parameters">
    <h4>Report Parameters</h4>

    <el-row :gutter="20">
      <el-col :span="12">
        <el-form-item label="Division" required>
          <el-select
            v-model="formData.division_id"
            placeholder="Select Division"
            style="width: 100%"
            clearable
            @change="handleFormDataUpdate"
          >
            <el-option label="All Divisions" value="all" />
            <el-option
              v-for="division in divisions"
              :key="division.id"
              :label="division.name"
              :value="division.id"
            />
          </el-select>
        </el-form-item>
      </el-col>

      <el-col :span="12">
        <el-form-item label="Year" required>
          <el-select
            v-model="formData.years"
            placeholder="Select Year"
            style="width: 100%"
            clearable
            @change="handleFormDataUpdate"
          >
            <el-option
              v-for="year in years"
              :key="year.years"
              :label="year.years"
              :value="year.years"
            />
          </el-select>
        </el-form-item>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { watch } from "vue";

const props = defineProps({
  formData: {
    type: Object,
    required: true,
  },
  divisions: {
    type: Array,
    default: () => [],
  },
  years: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:formData"]);

const handleFormDataUpdate = () => {
  emit("update:formData", props.formData);
};

// Watch for changes in formData and emit updates
watch(
  () => props.formData,
  (newVal) => {
    emit("update:formData", newVal);
  },
  { deep: true },
);
</script>

<style scoped>
.report-parameters {
  margin-bottom: 20px;
}

.report-parameters h4 {
  margin-bottom: 15px;
  color: #333;
  font-weight: 500;
}
</style>
