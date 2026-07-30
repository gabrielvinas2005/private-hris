<template>
  <div class="pagination-container">
    <div class="pagination-info">
      <span>Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries</span>
      <slot name="left"></slot>
      <el-select v-model="localPerPage" size="small" style="width: 80px; margin-left: 16px;" @change="onPerPage">
        <el-option v-for="n in perPageOptions" :key="n" :label="String(n)" :value="n" />
      </el-select>
      <span style="margin-left: 8px;">per page</span>
    </div>
    <el-pagination
      :current-page="pagination.current_page"
      :page-size="pagination.per_page"
      :total="pagination.total"
      :pager-count="pagerCount"
      :layout="layout"
      background
      @current-change="$emit('page-change', $event)"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  pagination: { type: Object, default: () => ({ current_page: 1, per_page: 10, total: 0, from: 0, to: 0 }) },
  perPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
  pagerCount: { type: Number, default: 5 },
  layout: { type: String, default: 'prev, pager, next' }
})

const emit = defineEmits(['page-change', 'per-page-change'])

const localPerPage = ref(props.pagination.per_page || 10)
watch(() => props.pagination.per_page, v => { if (v) localPerPage.value = v })

function onPerPage(v) { emit('per-page-change', Number(v)) }
</script>

<style scoped>
.pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding: 16px 0; }
.pagination-info { display: flex; align-items: center; color: #606266; font-size: 14px; }
.pagination-info span { margin-right: 8px; }
</style>


