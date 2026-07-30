<template>
  <div class="signatories-section">
    <h4>Signatory Information</h4>
    <p class="section-description">
      Signatory and LandBank personnel information with default placeholder
      values.
    </p>

    <el-row :gutter="20">
      <el-col :span="12">
        <h5>Prepared by (Signatory)</h5>
        <el-form-item label="Name" required>
          <el-select
            v-model="selectedSignatoryId"
            filterable
            clearable
            placeholder="Search employee"
            :disabled="!employeeOptions.length"
            @change="handleSignatorySelect"
            style="width: 100%"
          >
            <el-option
              v-for="option in employeeOptions"
              :key="option.id"
              :label="option.name"
              :value="option.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Position" required>
          <el-input
            v-model="signatories.signatory_position"
            :placeholder="placeholders.signatory_position || 'Position'"
            disabled
          />
        </el-form-item>
      </el-col>

      <el-col :span="12">
        <h5>Received by (LandBank Personnel)</h5>
        <el-form-item label="Name" required>
          <el-input
            v-model="signatories.personnel_name"
            :placeholder="
              placeholders.personnel_name || 'Enter LandBank personnel name'
            "
            @input="handleSignatoriesUpdate"
          />
        </el-form-item>
        <el-form-item label="Position" required>
          <el-input
            v-model="signatories.personnel_position"
            :placeholder="
              placeholders.personnel_position ||
              'Enter LandBank personnel position'
            "
            @input="handleSignatoriesUpdate"
          />
        </el-form-item>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";

const props = defineProps({
  signatories: {
    type: Object,
    required: true,
  },
  placeholders: {
    type: Object,
    default: () => ({
      signatory_name: "",
      signatory_position: "",
      personnel_name: "",
      personnel_position: "",
    }),
  },
  employeeOptions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:signatories"]);

const selectedSignatoryId = ref(null);

const handleSignatorySelect = (value) => {
  selectedSignatoryId.value = value ?? null;

  if (!value) {
    props.signatories.signatory_name = "";
    props.signatories.signatory_position = "";
    handleSignatoriesUpdate();
    return;
  }

  const selected = props.employeeOptions.find((option) => option.id === value);
  props.signatories.signatory_name = selected?.name || "";
  props.signatories.signatory_position = selected?.position || "";
  handleSignatoriesUpdate();
};

const handleSignatoriesUpdate = () => {
  emit("update:signatories", props.signatories);
};

// Sync selected signatory when signatories or employeeOptions change
const syncSelectedSignatory = () => {
  if (!props.signatories.signatory_name) {
    selectedSignatoryId.value = null;
    return;
  }

  const option = props.employeeOptions.find(
    (opt) => opt.name === props.signatories.signatory_name
  );
  selectedSignatoryId.value = option ? option.id : null;
};

// Watch for changes in signatories and emit updates
watch(
  () => props.signatories,
  (newVal) => {
    emit("update:signatories", newVal);
  },
  { deep: true }
);

// Watch for changes in employeeOptions or signatory_name to sync selection
watch(
  () => [props.employeeOptions, props.signatories.signatory_name],
  () => {
    syncSelectedSignatory();
  },
  { deep: true, immediate: true }
);
</script>

<style scoped>
.signatories-section {
  margin-bottom: 20px;
}

.signatories-section h4 {
  margin-bottom: 10px;
  color: #333;
  font-weight: 500;
}

.signatories-section h5 {
  margin-bottom: 15px;
  color: #666;
  font-weight: 500;
  font-size: 14px;
}

.section-description {
  margin-bottom: 20px;
  color: #666;
  font-size: 14px;
  font-style: italic;
}
</style>
