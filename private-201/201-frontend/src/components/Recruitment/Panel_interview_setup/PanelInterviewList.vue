<template>
  <el-card shadow="never" :class="{ 'hide-on-hover': !showProcessActions }">
    <template #header>
      <div class="flex items-center justify-between">
        <span class="font-bold">Interview Setup</span>
        <el-button
          v-if="showProcessActions"
          type="primary"
          class="add-btn"
          @click="$emit('add')"
        >
          Add New
        </el-button>
      </div>
    </template>

    <el-table :data="paginatedItems" size="small" stripe>
      <el-table-column label="#" width="70" align="center">
        <template #default="{ $index }">
          {{ rowDisplayIndex($index) }}
        </template>
      </el-table-column>
      <el-table-column prop="panel_group" label="Panel Group" />
      <el-table-column label="Level" width="140">
        <template #default="{ row }">
          {{ row.level || `Level ${row.panel_group_level || ''}` }}
        </template>
      </el-table-column>
      <el-table-column prop="interview_location" label="Location" />
      <el-table-column prop="start_date" label="Start" width="140" />
      <el-table-column prop="end_date" label="End" width="140" />
      <el-table-column label="Time From" width="120">
        <template #default="{ row }">{{ formatTime12(row.start_time) }}</template>
      </el-table-column>
      <el-table-column label="Time To" width="120">
        <template #default="{ row }">{{ formatTime12(row.end_time) }}</template>
      </el-table-column>
      <el-table-column label="Actions" width="280">
        <template #default="{ row }">
          <template v-if="showProcessActions">
            <el-button size="small" @click="$emit('edit', row)">Edit</el-button>
            <el-button size="small" @click="$emit('assign', row)">Assign</el-button>
            <el-button
              v-if="!isPosted(row)"
              size="small"
              type="success"
              :loading="processingId != null && Number(processingId) === Number(row.id)"
              @click="$emit('post', row)"
            >
              Post
            </el-button>
            <el-button
              v-else
              size="small"
              type="warning"
              :loading="processingId != null && Number(processingId) === Number(row.id)"
              @click="$emit('unpost', row)"
            >
              Unpost
            </el-button>
            <el-button
              v-if="isPosted(row)"
              size="small"
              type="primary"
              :loading="processingId != null && Number(processingId) === Number(row.id)"
              @click="$emit('done', row)"
            >
              Done
            </el-button>
          </template>
          <template v-else>
            <el-button size="small" type="primary" @click="$emit('view', row)">View Interview</el-button>
            <el-button size="small" type="danger" @click="$emit('delete', row)">Delete</el-button>
          </template>
        </template>
      </el-table-column>
    </el-table>

    <div class="pagination-container mt-4">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="items.length"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="onPageSizeChange"
      />
    </div>
  </el-card>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  showProcessActions: { type: Boolean, default: true },
  processingId: { type: [Number, String], default: null }
})
defineEmits(['add','edit','assign','post','unpost','done','view','delete'])

const currentPage = ref(1)
const pageSize = ref(10)

const paginatedItems = computed(() => {
  const list = props.items || []
  const start = (currentPage.value - 1) * pageSize.value
  return list.slice(start, start + pageSize.value)
})

const rowDisplayIndex = (pageIndex) =>
  (currentPage.value - 1) * pageSize.value + pageIndex + 1

const onPageSizeChange = () => {
  currentPage.value = 1
}

watch(
  () => [props.items?.length ?? 0, pageSize.value],
  () => {
    const total = props.items?.length ?? 0
    const maxPage = Math.max(1, Math.ceil(total / pageSize.value) || 1)
    if (currentPage.value > maxPage) currentPage.value = maxPage
  }
)

// Only treat explicit 1/true as posted so new interviews (null/0) show "Post" not "Unpost"
const isPosted = (row) => {
  if (!row) return false
  const v = row.posted
  return v === true || v === 1 || v === '1'
}

const formatTime12 = (value) => {
  if (!value) return ''
  const match = String(value).match(/^(\d{2}):(\d{2})/)
  if (!match) return String(value)
  let hour = parseInt(match[1], 10)
  const minute = match[2]
  const ampm = hour >= 12 ? 'PM' : 'AM'
  hour = hour % 12
  if (hour === 0) hour = 12
  return `${hour}:${minute} ${ampm}`
}
</script>

<style scoped>
.flex { display: flex; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.font-bold { font-weight: 700; }

/* When in Done view (showProcessActions === false), hide the Add button on hover anywhere over the card */
.hide-on-hover:hover .add-btn { display: none; }

.pagination-container {
  display: flex;
  justify-content: center;
}

.mt-4 {
  margin-top: 16px;
}
</style>


