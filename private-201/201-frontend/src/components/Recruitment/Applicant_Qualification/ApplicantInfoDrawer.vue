<template>
  <el-drawer :model-value="visible" @close="$emit('update:visible', false)" size="60%" title="Applicant Details">
    <template #default>
      <div v-if="info">
        <el-card shadow="never" class="mb-3">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <div><strong>Name:</strong> {{ info.applicant?.[0]?.first_name }} {{ info.applicant?.[0]?.last_name }}</div>
              <div><strong>Gender:</strong> {{ info.applicant?.[0]?.gender }}</div>
              <div><strong>Email:</strong> {{ info.applicant?.[0]?.email }}</div>
              <div><strong>Mobile:</strong> {{ info.applicant?.[0]?.mobile_no }}</div>
            </div>
            <div>
              <div><strong>Position(s) Applied:</strong></div>
              <ul>
                <li v-for="p in info.data" :key="p.id">{{ p.position }} ({{ p.department }})</li>
                <li v-for="p in info.non_plantillas" :key="'n'+p.id">{{ p.position }} ({{ p.department }})</li>
              </ul>
            </div>
          </div>
        </el-card>

        <el-form :model="form" label-width="200px">
          <el-form-item label="Education Rating"><el-input-number v-model="form.education_rating" :min="0" :max="100" /></el-form-item>
          <el-form-item label="Experience Rating"><el-input-number v-model="form.experience_rating" :min="0" :max="100" /></el-form-item>
          <el-form-item label="Training Rating"><el-input-number v-model="form.training_rating" :min="0" :max="100" /></el-form-item>
          <el-form-item label="Eligibility Rating"><el-input-number v-model="form.eligibility_rating" :min="0" :max="100" /></el-form-item>
          <el-form-item label="Reviewed Status">
            <el-select v-model="form.reviewed_status_id" placeholder="Select status">
              <el-option :value="2" label="Qualified" />
              <el-option :value="3" label="Not Qualified" />
            </el-select>
          </el-form-item>
          <el-form-item label="Interview"><el-input v-model="form.interview" placeholder="Notes" /></el-form-item>
          <el-form-item label="Bonus"><el-input-number v-model="form.bonus" :min="0" /></el-form-item>
        </el-form>

        <div class="flex justify-end gap-2 mt-3">
          <el-button @click="$emit('update:visible', false)">Close</el-button>
          <el-button type="primary" :loading="saving" @click="$emit('submit')">Save Rating</el-button>
        </div>
      </div>
    </template>
  </el-drawer>
</template>

<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  info: { type: Object, default: () => null },
  form: { type: Object, default: () => ({}) },
  saving: { type: Boolean, default: false }
})
</script>

<style scoped>
.grid{display:grid}
.grid-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.gap-4{gap:1rem}
.mb-3{margin-bottom:.75rem}
.mt-3{margin-top:.75rem}
</style>
