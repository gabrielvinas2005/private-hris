<template>
  <el-card shadow="hover">
    <!-- Table Header -->
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <el-icon class="mr-2"><Calendar /></el-icon>
          <span class="font-semibold">WFH Attendance Records</span>
        </div>
        <div class="flex items-center space-x-3">
          <el-button
            @click="fillCurrentTime"
            type="primary"
            size="small"
            :icon="Clock"
          >
            Fill Current Time
          </el-button>
          <el-button
            @click="clearAll"
            type="danger"
            size="small"
            :icon="Delete"
          >
            Clear All
          </el-button>
        </div>
      </div>
    </template>

    <!-- Table -->
    <el-table 
      :data="timeData" 
      stripe
      style="width: 100%"
      :row-class-name="getRowClassName"
    >
      <el-table-column prop="date" label="Date" width="120">
        <template #default="{ row }">
          <div class="flex items-center">
            <span class="font-medium text-gray-800">{{ formatDate(row.date) }}</span>
            <el-tag v-if="isWeekend(row.date)" type="warning" size="small" class="ml-2">
              Weekend
            </el-tag>
          </div>
        </template>
      </el-table-column>
      
      <el-table-column label="AM In" width="120">
        <template #default="{ row }">
          <el-time-picker
            v-model="row.am_in"
            format="HH:mm"
            value-format="HH:mm"
            placeholder="--:--"
            size="small"
            @change="calculateWorkHours(row)"
          />
        </template>
      </el-table-column>
      
      <el-table-column label="AM Out" width="120">
        <template #default="{ row }">
          <el-time-picker
            v-model="row.am_out"
            format="HH:mm"
            value-format="HH:mm"
            placeholder="--:--"
            size="small"
            @change="calculateWorkHours(row)"
          />
        </template>
      </el-table-column>
      
      <el-table-column label="Break In" width="120">
        <template #default="{ row }">
          <el-time-picker
            v-model="row.break_in"
            format="HH:mm"
            value-format="HH:mm"
            placeholder="--:--"
            size="small"
            @change="calculateWorkHours(row)"
          />
        </template>
      </el-table-column>
      
      <el-table-column label="Break Out" width="120">
        <template #default="{ row }">
          <el-time-picker
            v-model="row.break_out"
            format="HH:mm"
            value-format="HH:mm"
            placeholder="--:--"
            size="small"
            @change="calculateWorkHours(row)"
          />
        </template>
      </el-table-column>
      
      <el-table-column label="PM In" width="120">
        <template #default="{ row }">
          <el-time-picker
            v-model="row.pm_in"
            format="HH:mm"
            value-format="HH:mm"
            placeholder="--:--"
            size="small"
            @change="calculateWorkHours(row)"
          />
        </template>
      </el-table-column>
      
      <el-table-column label="PM Out" width="120">
        <template #default="{ row }">
          <el-time-picker
            v-model="row.pm_out"
            format="HH:mm"
            value-format="HH:mm"
            placeholder="--:--"
            size="small"
            @change="calculateWorkHours(row)"
          />
        </template>
      </el-table-column>
      
      <el-table-column label="Hours" width="100">
        <template #default="{ row }">
          <el-tag type="success" size="small">
            {{ row.work_hours || 0 }}h
          </el-tag>
        </template>
      </el-table-column>
      
      <el-table-column label="WFH Reason" width="150">
        <template #default="{ row }">
          <el-input
            v-model="row.wfh_reason"
            placeholder="WFH reason..."
            size="small"
            clearable
          />
        </template>
      </el-table-column>
      
      <el-table-column label="Location" width="120">
        <template #default="{ row }">
          <el-input
            v-model="row.wfh_location"
            placeholder="Location..."
            size="small"
            clearable
          />
        </template>
      </el-table-column>
      
      <el-table-column label="Status" width="100">
        <template #default="{ row }">
          <el-tag 
            :type="getStatusTagType(row.status)"
            size="small"
          >
            {{ getStatusText(row.status) }}
          </el-tag>
        </template>
      </el-table-column>
      
      <el-table-column label="Actions" width="120" fixed="right">
        <template #default="{ row, $index }">
          <div class="flex items-center space-x-2">
            <el-button
              v-if="row.id"
              @click="deleteRecord(row.id)"
              type="danger"
              size="small"
              :icon="Delete"
              circle
              title="Delete record"
            />
            <el-button
              v-if="$index > 0"
              @click="copyFromPrevious($index)"
              type="primary"
              size="small"
              :icon="CopyDocument"
              circle
              title="Copy from previous day"
            />
          </div>
        </template>
      </el-table-column>
    </el-table>

    <!-- Quick Fill Options -->
    <div class="mt-4">
      <el-card>
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <span class="text-sm font-medium text-gray-700">Quick Fill:</span>
            <el-button
              @click="fillWeekdays"
              type="success"
              size="small"
              :icon="Calendar"
            >
              Fill Weekdays Only
            </el-button>
            <el-button
              @click="fillEmptyFields"
              type="warning"
              size="small"
              :icon="Edit"
            >
              Fill Empty Fields Only
            </el-button>
          </div>
          <div class="text-sm text-gray-600">
            Total Hours: <span class="font-medium text-blue-600">{{ totalWorkHours }}</span>
          </div>
        </div>
      </el-card>
    </div>
  </el-card>
</template>

<script>
import { 
  Calendar, 
  Clock, 
  Delete, 
  CopyDocument, 
  Edit 
} from '@element-plus/icons-vue'

export default {
  name: 'WFHAttendanceTable',
  components: {
    Calendar,
    Clock,
    Delete,
    CopyDocument,
    Edit
  },
  props: {
    timeData: {
      type: Array,
      required: true
    }
  },
  emits: ['update:timeData', 'delete-record'],
  computed: {
    totalWorkHours() {
      return this.timeData.reduce((total, record) => {
        return total + (parseFloat(record.work_hours) || 0);
      }, 0).toFixed(2);
    }
  },
  methods: {
    formatDate(dateStr) {
      const date = new Date(dateStr);
      return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric',
        weekday: 'short'
      });
    },

    isWeekend(dateStr) {
      const date = new Date(dateStr);
      const day = date.getDay();
      return day === 0 || day === 6; // Sunday or Saturday
    },

    getRowClassName({ row }) {
      if (this.isWeekend(row.date)) {
        return 'weekend-row';
      }
      return '';
    },

    getStatusTagType(status) {
      const types = {
        'complete': 'success',
        'partial': 'warning',
        'empty': 'info',
        'wfh': 'primary'
      };
      return types[status] || 'info';
    },

    getStatusText(status) {
      const texts = {
        'complete': 'Complete',
        'partial': 'Partial',
        'empty': 'Empty',
        'wfh': 'WFH'
      };
      return texts[status] || 'Unknown';
    },

    calculateWorkHours(record) {
      if (!record.am_in || !record.pm_out) {
        record.work_hours = 0;
        return;
      }

      try {
        const amIn = new Date(`2000-01-01T${record.am_in}:00`);
        const pmOut = new Date(`2000-01-01T${record.pm_out}:00`);
        
        let totalMinutes = (pmOut - amIn) / (1000 * 60);

        // Subtract break time if provided
        if (record.break_in && record.break_out) {
          const breakIn = new Date(`2000-01-01T${record.break_in}:00`);
          const breakOut = new Date(`2000-01-01T${record.break_out}:00`);
          const breakMinutes = (breakOut - breakIn) / (1000 * 60);
          totalMinutes -= breakMinutes;
        }

        record.work_hours = Math.max(0, totalMinutes / 60).toFixed(2);
        
        // Update status
        if (record.am_in && record.pm_out) {
          record.status = 'complete';
        } else if (record.am_in || record.pm_out) {
          record.status = 'partial';
        } else {
          record.status = 'empty';
        }

        this.$emit('update:timeData', this.timeData);
      } catch (error) {
        console.error('Error calculating work hours:', error);
        record.work_hours = 0;
      }
    },

    fillCurrentTime() {
      const now = new Date();
      const currentTime = now.toTimeString().slice(0, 5);
      
      this.timeData.forEach(record => {
        if (!record.am_in) {
          record.am_in = currentTime;
        }
        if (!record.pm_out) {
          record.pm_out = currentTime;
        }
        this.calculateWorkHours(record);
      });
      
      this.$emit('update:timeData', this.timeData);
    },

    clearAll() {
      this.$confirm('Are you sure you want to clear all time entries?', 'Confirm Clear', {
        confirmButtonText: 'Yes, Clear All',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        this.timeData.forEach(record => {
          record.am_in = null;
          record.am_out = null;
          record.break_in = null;
          record.break_out = null;
          record.pm_in = null;
          record.pm_out = null;
          record.work_hours = 0;
          record.status = 'empty';
        });
        
        this.$emit('update:timeData', this.timeData);
        this.$message.success('All time entries cleared successfully');
      }).catch(() => {
        // User cancelled
      });
    },

    fillWeekdays() {
      this.timeData.forEach(record => {
        if (!this.isWeekend(record.date)) {
          if (!record.am_in) record.am_in = '08:00';
          if (!record.am_out) record.am_out = '12:00';
          if (!record.break_in) record.break_in = '12:00';
          if (!record.break_out) record.break_out = '13:00';
          if (!record.pm_in) record.pm_in = '13:00';
          if (!record.pm_out) record.pm_out = '17:00';
          this.calculateWorkHours(record);
        }
      });
      
      this.$emit('update:timeData', this.timeData);
    },

    fillEmptyFields() {
      this.timeData.forEach(record => {
        if (!record.am_in) record.am_in = '08:00';
        if (!record.am_out) record.am_out = '12:00';
        if (!record.break_in) record.break_in = '12:00';
        if (!record.break_out) record.break_out = '13:00';
        if (!record.pm_in) record.pm_in = '13:00';
        if (!record.pm_out) record.pm_out = '17:00';
        this.calculateWorkHours(record);
      });
      
      this.$emit('update:timeData', this.timeData);
    },

    copyFromPrevious(index) {
      if (index > 0) {
        const previousRecord = this.timeData[index - 1];
        const currentRecord = this.timeData[index];
        
        currentRecord.am_in = previousRecord.am_in;
        currentRecord.am_out = previousRecord.am_out;
        currentRecord.break_in = previousRecord.break_in;
        currentRecord.break_out = previousRecord.break_out;
        currentRecord.pm_in = previousRecord.pm_in;
        currentRecord.pm_out = previousRecord.pm_out;
        currentRecord.wfh_reason = previousRecord.wfh_reason;
        currentRecord.wfh_location = previousRecord.wfh_location;
        
        this.calculateWorkHours(currentRecord);
        this.$emit('update:timeData', this.timeData);
      }
    },

    deleteRecord(recordId) {
      this.$confirm('Are you sure you want to delete this record?', 'Confirm Delete', {
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        this.$emit('delete-record', recordId);
        this.$message.success('Record deleted successfully');
      }).catch(() => {
        // User cancelled
      });
    }
  }
}
</script>

<style scoped>
.weekend-row {
  background-color: #fef3c7 !important;
}
</style>
