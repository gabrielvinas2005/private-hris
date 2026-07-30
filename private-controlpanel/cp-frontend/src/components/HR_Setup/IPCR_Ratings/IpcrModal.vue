<template>
  <el-dialog v-model="visible" title="IPCR" width="700px" append-to-body>
    <el-form :model="form" label-width="130px">
      <el-form-item label="Department">
        <el-select v-model="form.department_id" filterable placeholder="Select department">
          <el-option v-for="d in formOptions.departments" :key="d.id" :label="d.name" :value="d.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="Division">
        <el-select v-model="form.division_id" filterable placeholder="Select division">
          <el-option v-for="d in formOptions.divisions" :key="d.id" :label="d.name" :value="d.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="Semester">
        <el-select v-model="form.semester_id" placeholder="Select semester">
          <el-option v-for="s in formOptions.semesters" :key="s.id" :label="s.name" :value="s.id" />
        </el-select>
      </el-form-item>
      <el-form-item label="Months">
        <div style="display:flex; gap:10px; width:100%">
          <el-select v-model="form.month_from" placeholder="From" style="flex:1">
            <el-option v-for="m in formOptions.months" :key="m.id" :label="m.name" :value="m.id" />
          </el-select>
          <el-select v-model="form.month_to" placeholder="To" style="flex:1">
            <el-option v-for="m in formOptions.months" :key="m.id" :label="m.name" :value="m.id" />
          </el-select>
        </div>
      </el-form-item>
      <el-form-item label="Year">
        <el-input v-model="form.year" placeholder="YYYY" />
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button @click="emit('close')">Cancel</el-button>
      <el-button type="primary" :loading="saving" @click="emit('save', form)">Save</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch, reactive } from 'vue'
const props = defineProps({ modelValue: { type: Boolean, default: false }, formOptions: { type: Object, default: () => ({}) }, value: { type: Object, default: () => ({}) }, saving: { type: Boolean, default: false } })
const emit = defineEmits(['update:modelValue', 'close', 'save'])

const visible = ref(false)
watch(() => props.modelValue, v => visible.value = v, { immediate: true })
watch(visible, v => emit('update:modelValue', v))

const form = reactive({ id: 0, department_id: 0, division_id: 0, semester_id: 0, month_from: 0, month_to: 0, year: new Date().getFullYear() })
watch(() => props.value, v => Object.assign(form, v || {}), { immediate: true, deep: true })
</script>


