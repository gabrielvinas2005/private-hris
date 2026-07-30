<template>
  <div class="page-scaffold">
    <el-page-header v-if="showBack" content="" @back="$router.back()" />
    <el-breadcrumb v-if="breadcrumbs && breadcrumbs.length" :separator="breadcrumbSeparator" class="mb-3">
      <el-breadcrumb-item v-for="(bc, idx) in breadcrumbs" :key="idx">
        <RouterLink v-if="bc.to" :to="bc.to">{{ bc.label }}</RouterLink>
        <span v-else>{{ bc.label }}</span>
      </el-breadcrumb-item>
    </el-breadcrumb>
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <div class="titles">
            <h2 class="title">{{ title }}</h2>
            <p v-if="subtitle" class="subtitle">{{ subtitle }}</p>
          </div>
          <div class="actions">
            <slot name="actions" />
          </div>
        </div>
      </template>
      <slot />
    </el-card>
  </div>
 </template>

<script setup>
import { ElCard, ElBreadcrumb, ElBreadcrumbItem, ElPageHeader } from 'element-plus'

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  breadcrumbs: { type: Array, default: () => [] },
  breadcrumbSeparator: { type: String, default: '/' },
  showBack: { type: Boolean, default: false }
})
</script>

<style scoped>
.card-header { display: grid; grid-template-columns: 1fr auto; align-items: center; }
.actions { display: flex; align-items: center; gap: 10px; }
.title { margin: 0; font-size: 18px; font-weight: 600; }
.subtitle { margin: 2px 0 0; color: #6b7280; font-size: 13px; }
.mb-3 { margin-bottom: 12px; }
</style>


