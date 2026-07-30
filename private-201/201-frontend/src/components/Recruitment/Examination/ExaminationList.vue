<template>
  <el-card shadow="never">
    <template #header>
      <div class="flex items-center justify-between">
        <span class="font-bold">Examination Setup</span>
        <el-button type="primary" @click="$emit('add')">Add New</el-button>
      </div>
    </template>

    <el-table :data="paginatedExams" size="small" stripe>
      <el-table-column label="#" width="70" align="center">
        <template #default="{ $index }">
          {{ examRowDisplayIndex($index) }}
        </template>
      </el-table-column>
      <el-table-column prop="exam_set" label="Online Exam Set" />
      <el-table-column label="Passing Score" width="200">
        <template #default="{ row }">
          {{ formatScore(row.passing_criteria) }}  
        </template>
      </el-table-column>
      <!-- <el-table-column label="Weighted Allocation" width="180">
        <template #default="{ row }">
          {{ formatWeightedAllocation(row.weighted_allocation) }}
        </template>
      </el-table-column> -->
      <el-table-column label="Duration of Exam in minutes" width="200">
        <template #default="{ row }">
          {{ formatDuration(row.exam_duration) }}
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="200">
        <template #default="{ row }">
          <el-button size="small" type="primary" @click="$emit('edit', row)">Details</el-button>
          <el-button size="small" type="danger" @click="$emit('delete', row)">Delete</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="mt-4 pagination-container">
      <el-pagination
        v-model:current-page="examListPage"
        v-model:page-size="examListPageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="exams.length"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="onExamListPageSizeChange"
      />
    </div>
  </el-card>
  
  <el-card class="mt-4" shadow="never">
    <template #header>
      <div class="flex items-center justify-between">
        <span class="font-bold">Examination Schedules</span>
        <el-button type="primary" @click="$emit('add-schedule')">Add Schedule</el-button>
      </div>
    </template>
    <el-tabs v-model="scheduleTab" class="schedule-tabs">
      <el-tab-pane label="Current" name="current">
        <el-table :data="paginatedCurrentSchedules" size="small" stripe>
          <el-table-column label="#" width="70" align="center">
            <template #default="{ $index }">
              {{ scheduleRowDisplayIndex('current', $index) }}
            </template>
          </el-table-column>
          <el-table-column prop="exam_set" label="Exam Set" />
          <el-table-column prop="exam_category" label="Category" />
          <el-table-column prop="exam_date_from" label="From" width="140" />
          <el-table-column prop="exam_date_to" label="To" width="140" />
          <el-table-column label="Time From" width="120">
            <template #default="{ row }">{{ formatTime12(row.exam_time_from) }}</template>
          </el-table-column>
          <el-table-column label="Time To" width="120">
            <template #default="{ row }">{{ formatTime12(row.exam_time_to) }}</template>
          </el-table-column>
          <el-table-column label="Actions" width="380">
            <template #default="{ row }">
              <el-button size="small" @click="$emit('edit-schedule', row)">Edit</el-button>
              <el-button size="small" @click="$emit('details', row)">Details</el-button>
              <el-button size="small" type="info" @click="$emit('tag', row)">Tag</el-button>
              <el-button
                size="small"
                type="success"
                :loading="processingId != null && Number(processingId) === Number(row.id)"
                @click="$emit(isPosted(row) ? 'unpost' : 'post', row)"
              >
                {{ isPosted(row) ? 'Unpost' : 'Post Now' }}
              </el-button>
              <el-button size="small" type="danger" @click="$emit('delete-schedule', row)">Delete</el-button>
            </template>
          </el-table-column>
        </el-table>
        <div class="mt-4 pagination-container">
          <el-pagination
            v-model:current-page="schedulePageCurrent"
            v-model:page-size="schedulePageSize"
            :page-sizes="[10, 20, 50, 100]"
            :total="currentSchedules.length"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="onSchedulePageSizeChange"
          />
        </div>
      </el-tab-pane>
      <el-tab-pane label="Expired" name="expired">
        <el-table :data="paginatedExpiredSchedules" size="small" stripe>
          <el-table-column label="#" width="70" align="center">
            <template #default="{ $index }">
              {{ scheduleRowDisplayIndex('expired', $index) }}
            </template>
          </el-table-column>
          <el-table-column prop="exam_set" label="Exam Set" />
          <el-table-column prop="exam_category" label="Category" />
          <el-table-column prop="exam_date_from" label="From" width="140" />
          <el-table-column prop="exam_date_to" label="To" width="140" />
          <el-table-column label="Time From" width="120">
            <template #default="{ row }">{{ formatTime12(row.exam_time_from) }}</template>
          </el-table-column>
          <el-table-column label="Time To" width="120">
            <template #default="{ row }">{{ formatTime12(row.exam_time_to) }}</template>
          </el-table-column>
          <el-table-column label="Status" width="100">
            <template #default>Expired</template>
          </el-table-column>
          <el-table-column label="Actions" width="220">
            <template #default="{ row }">
              <el-button size="small" @click="$emit('edit-schedule', row)">Edit</el-button>
              <el-button size="small" @click="$emit('details', row)">Details</el-button>
              <el-button size="small" type="danger" @click="$emit('delete-schedule', row)">Delete</el-button>
            </template>
          </el-table-column>
        </el-table>
        <div class="mt-4 pagination-container">
          <el-pagination
            v-model:current-page="schedulePageExpired"
            v-model:page-size="schedulePageSize"
            :page-sizes="[10, 20, 50, 100]"
            :total="expiredSchedules.length"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="onSchedulePageSizeChange"
          />
        </div>
      </el-tab-pane>
      <el-tab-pane label="Done" name="done">
        <el-table :data="paginatedDoneSchedules" size="small" stripe>
          <el-table-column label="#" width="70" align="center">
            <template #default="{ $index }">
              {{ scheduleRowDisplayIndex('done', $index) }}
            </template>
          </el-table-column>
          <el-table-column prop="exam_set" label="Exam Set" />
          <el-table-column prop="exam_category" label="Category" />
          <el-table-column prop="exam_date_from" label="From" width="140" />
          <el-table-column prop="exam_date_to" label="To" width="140" />
          <el-table-column label="Time From" width="120">
            <template #default="{ row }">{{ formatTime12(row.exam_time_from) }}</template>
          </el-table-column>
          <el-table-column label="Time To" width="120">
            <template #default="{ row }">{{ formatTime12(row.exam_time_to) }}</template>
          </el-table-column>
          <el-table-column label="Status" width="100">
            <template #default>Done</template>
          </el-table-column>
          <el-table-column label="Actions" width="200">
            <template #default="{ row }">
              <el-button size="small" @click="$emit('details', row)">Details</el-button>
              <el-button size="small" type="danger" @click="$emit('delete-schedule', row)">Delete</el-button>
            </template>
          </el-table-column>
        </el-table>
        <div class="mt-4 pagination-container">
          <el-pagination
            v-model:current-page="schedulePageDone"
            v-model:page-size="schedulePageSize"
            :page-sizes="[10, 20, 50, 100]"
            :total="doneSchedules.length"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="onSchedulePageSizeChange"
          />
        </div>
      </el-tab-pane>
    </el-tabs>
  </el-card>
</template>

<script>
import { ref, computed, watch } from 'vue'

export default {
  name: 'ExaminationList',
  props: {
    exams: { type: Array, default: () => [] },
    schedules: { type: Array, default: () => [] },
    processingId: {type: [Number, String], default: null },
    showProcessActions: {type: Boolean, default: true }
  },
  emits: ['add', 'edit', 'delete', 'add-schedule', 'edit-schedule', 'delete-schedule', 'details', 'post', 'unpost', 'tag'],
  setup(props) {
    const scheduleTab = ref('current')

    const examListPage = ref(1)
    const examListPageSize = ref(10)

    const schedulePageCurrent = ref(1)
    const schedulePageExpired = ref(1)
    const schedulePageDone = ref(1)
    const schedulePageSize = ref(10)

    // const props = defineProps({
    //   items: { type: Array, default: () => [] },
    //   showProcessActions: { type: Boolean, default: true },
    //   processingId: { type: [Number, String], default: null }
    // })

    
 

    const currentSchedules = computed(() =>
      (props.schedules || []).filter((row) => !row.is_expired && !row.is_done)
    )
    const expiredSchedules = computed(() =>
      (props.schedules || []).filter((row) => row.is_expired)
    )
    const doneSchedules = computed(() =>
      (props.schedules || []).filter((row) => row.is_done)
    )

    const paginatedExams = computed(() => {
      const list = props.exams || []
      const start = (examListPage.value - 1) * examListPageSize.value
      return list.slice(start, start + examListPageSize.value)
    })

    const paginatedCurrentSchedules = computed(() => {
      const list = currentSchedules.value
      const start = (schedulePageCurrent.value - 1) * schedulePageSize.value
      return list.slice(start, start + schedulePageSize.value)
    })

    const paginatedExpiredSchedules = computed(() => {
      const list = expiredSchedules.value
      const start = (schedulePageExpired.value - 1) * schedulePageSize.value
      return list.slice(start, start + schedulePageSize.value)
    })

    const paginatedDoneSchedules = computed(() => {
      const list = doneSchedules.value
      const start = (schedulePageDone.value - 1) * schedulePageSize.value
      return list.slice(start, start + schedulePageSize.value)
    })

    const examRowDisplayIndex = ($index) =>
      (examListPage.value - 1) * examListPageSize.value + $index + 1

    const schedulePageForTab = (tab) => {
      if (tab === 'current') return schedulePageCurrent
      if (tab === 'expired') return schedulePageExpired
      return schedulePageDone
    }

    const scheduleRowDisplayIndex = (tab, $index) => {
      const page = schedulePageForTab(tab).value
      return (page - 1) * schedulePageSize.value + $index + 1
    }

    const onExamListPageSizeChange = () => {
      examListPage.value = 1
    }

    const onSchedulePageSizeChange = () => {
      schedulePageCurrent.value = 1
      schedulePageExpired.value = 1
      schedulePageDone.value = 1
    }

    const clampPage = (pageRef, totalLen, size) => {
      const maxPage = Math.max(1, Math.ceil(totalLen / size) || 1)
      if (pageRef.value > maxPage) pageRef.value = maxPage
    }

    watch(
      () => [props.exams?.length ?? 0, examListPageSize.value],
      () => clampPage(examListPage, props.exams?.length ?? 0, examListPageSize.value)
    )

    watch(
      () => [
        currentSchedules.value.length,
        expiredSchedules.value.length,
        doneSchedules.value.length,
        schedulePageSize.value
      ],
      () => {
        const sz = schedulePageSize.value
        clampPage(schedulePageCurrent, currentSchedules.value.length, sz)
        clampPage(schedulePageExpired, expiredSchedules.value.length, sz)
        clampPage(schedulePageDone, doneSchedules.value.length, sz)
      }
    )

    const isPosted = (row) => {
      if (!row) return false
      const v = row.posted
      if (v === true) return true
      if (v === 1 || v === '1') return true
      return false
    }

    const formatScore = (value) => {
      const n = Number(value)
      if (value === null || value === undefined || value === '' || Number.isNaN(n)) return '0'
      return String(Math.trunc(n))
    }

    const formatDuration = (value) => {
      if (!value) return '0.00'
      const duration = parseFloat(value)
      if (duration >= 60) {
        const hours = Math.floor(duration / 60)
        const minutes = duration % 60
        if (minutes === 0) {
          return `${hours} hr/s`
        }
        return `${hours} hr/s ${minutes} mins`
      }
      return `${duration.toFixed(2)} mins`
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

    return {
      scheduleTab,
      examListPage,
      examListPageSize,
      schedulePageCurrent,
      schedulePageExpired,
      schedulePageDone,
      schedulePageSize,
      currentSchedules,
      expiredSchedules,
      doneSchedules,
      paginatedExams,
      paginatedCurrentSchedules,
      paginatedExpiredSchedules,
      paginatedDoneSchedules,
      examRowDisplayIndex,
      scheduleRowDisplayIndex,
      onExamListPageSizeChange,
      onSchedulePageSizeChange,
      isPosted,
      formatScore,
      formatDuration,
      formatTime12
    }
  }
}
</script>

<style scoped>
.mt-4 { margin-top: 1rem; }
.flex { display: flex; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.font-bold { font-weight: 700; }

.pagination-container {
  display: flex;
  justify-content: center;
}
</style>


