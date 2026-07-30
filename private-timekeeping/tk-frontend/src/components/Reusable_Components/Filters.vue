<template>
  <div class="filters-container">
    <div class="filters-row">
      <span class="filters-label">Filters:</span>
      
      <!-- Individual Filter Dropdowns -->
      <div class="filter-dropdowns">
        <div 
          v-for="filter in availableFilters" 
          :key="filter.key"
          class="filter-dropdown-item"
        >
          <!-- Date Range Filter -->
          <div v-if="filter.type === 'date-range'" class="date-range-filter">
            <div class="date-input-group">
              <el-date-picker
                v-model="selectedFilters[filter.fromKey]"
                type="date"
                placeholder="From"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                class="date-picker-small"
                @change="onFilterChange"
                :loading="loading || dataLoading"
              />
              <span class="date-separator">to</span>
              <el-date-picker
                v-model="selectedFilters[filter.toKey]"
                type="date"
                placeholder="To"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
                class="date-picker-small"
                @change="onFilterChange"
                :loading="loading || dataLoading"
              />
            </div>
          </div>
          
          <!-- Regular Dropdown Filter -->
          <el-select
            v-else
            v-model="selectedFilters[filter.key]"
            :placeholder="getAllLabel(filter)"
            :value-key="filter.valueKey || 'id'"
            filterable
            clearable
            class="filter-select"
            @change="onFilterChange"
            @clear="() => { selectedFilters[filter.key] = null; onFilterChange(); }"
            :loading="loading || dataLoading"
          >
            <el-option
              v-for="option in filter.options"
              :key="getOptionValue(option, filter.valueKey)"
              :label="getOptionLabel(option, filter.labelKey)"
              :value="getOptionValue(option, filter.valueKey)"
              :disabled="!option.active && option.active !== undefined"
            />
          </el-select>
        </div>
        
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Close } from '@element-plus/icons-vue'
import { api as rawApi } from '../../services/api'

const props = defineProps({
  // Filter configuration - can be simple array or object with fetch configuration
  filters: {
    type: Array,
    default: () => []
  },
  
  // Current filter values
  modelValue: {
    type: Object,
    default: () => ({})
  },
  
  // Loading state
  loading: {
    type: Boolean,
    default: false
  },
  
  // Auto-fetch reference data
  autoFetch: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits([
  'update:modelValue',
  'filter-change'
])

// Reactive state
const selectedFilters = ref({ ...props.modelValue })

// Reference data stores
const departmentsRef = ref([])
const positionsRef = ref([])
const employmentTypesRef = ref([])
const dataLoading = ref(false)

const filterOutId0 = arr => Array.isArray(arr)
  ? arr.filter(item => {
      if (item === null || item === undefined) return false
      if (typeof item === 'object' && 'id' in item) return item.id !== 0 && item.id !== null
      return item !== 0 && item !== null && item !== undefined
    })
  : [];

// Available filters with auto-populated options
const availableFilters = computed(() => {
  if (!props.autoFetch) {
    return (props.filters || []).map(filter => ({
      ...filter,
      options: filterOutId0(filter.options)
    }));
  }
  const filters = props.filters || [];
  return filters.map(filter => {
    switch (filter.key) {
      case 'year':
        return filter;
      case 'departmentId':
      case 'department_id':
        return {
          ...filter,
          options: filterOutId0(departmentsRef.value.filter(dept => dept.active !== false))
        };
      case 'positionId':
      case 'position_id':
        return {
          ...filter,
          options: filterOutId0(positionsRef.value.filter(pos => pos.active !== false))
        };
      case 'employmentTypeId':
        return {
          ...filter,
          options: filterOutId0(employmentTypesRef.value.filter(emp => emp.active !== false))
        };
      default:
        return {
          ...filter,
          options: filterOutId0(filter.options)
        };
    }
  });
});

// Computed properties
const hasActiveFilters = computed(() => {
  return Object.values(selectedFilters.value).some(value => 
    value !== null && value !== undefined && value !== ''
  )
})

const onFilterChange = () => {
  emit('update:modelValue', { ...selectedFilters.value })
  emit('filter-change', { ...selectedFilters.value })
}



const getOptionValue = (option, valueKey = 'id') => {
  if (typeof option === 'object') {
    return option[valueKey]
  }
  return option
}

const getOptionLabel = (option, labelKey = 'name') => {
  if (typeof option === 'object') {
    return option[labelKey]
  }
  return String(option)
}

const getPlaceholder = (filter) => {
  if (!filter || !filter.key) return ''
  // Hide the "All Report Type" placeholder specifically for report type
  if (filter.key === 'reportType') return ''
  return `All ${filter.label}`
}

const getAllLabel = (filter) => {
  if (!filter) return 'All';
  // Use custom placeholder if provided
  if (filter.placeholder) return filter.placeholder;
  if (!filter.label) return 'All';
  // Pluralize label if needed (basic English pluralization for demo)
  let label = filter.label;
  if (/[^sS]$/.test(label)) label = label + 's';
  return `All ${label}`;
}

// Load reference data for filters
const loadReferenceData = async () => {
  if (!props.autoFetch) return
  
  dataLoading.value = true
  try {
    const promises = []
    const filterKeys = props.filters?.map(f => f.key) || []
    
    // Only fetch data for filters that are configured
    // Check for both camelCase and snake_case versions
    if (filterKeys.includes('departmentId') || filterKeys.includes('department_id')) {
      promises.push(
        rawApi.get('/departments').catch(() => [])
      )
    } else {
      promises.push(Promise.resolve([]))
    }
    
    if (filterKeys.includes('positionId') || filterKeys.includes('position_id')) {
      promises.push(
        rawApi.get('/positions').catch(() => [])
      )
    } else {
      promises.push(Promise.resolve([]))
    }
    
    if (filterKeys.includes('employmentTypeId')) {
      promises.push(
        rawApi.get('/employment-types').catch(() => [])
      )
    } else {
      promises.push(Promise.resolve([]))
    }
    
    const [depRes, posRes, empRes] = await Promise.all(promises)
    
    departmentsRef.value = Array.isArray(depRes) ? depRes : (depRes?.data || depRes?.departments || [])
    positionsRef.value = Array.isArray(posRes) ? posRes : (posRes?.data || posRes?.positions || [])
    employmentTypesRef.value = Array.isArray(empRes) ? empRes : (empRes?.data || empRes?.employment_types || [])
    
  } catch (err) {
    console.error('Failed to load filter reference data:', err)
  } finally {
    dataLoading.value = false
  }
}

// In script setup, after selectedFilters:
const initFiltersNull = () => {
  for (const filter of availableFilters.value) {
    if (filter && filter.key && selectedFilters.value[filter.key] === undefined) {
      selectedFilters.value[filter.key] = null;
    }
  }
};

// Call after mounting and when props.modelValue or props.filters change
watch(() => props.filters, () => {
  if (props.autoFetch) {
    loadReferenceData();
  }
  initFiltersNull();
}, { immediate: true });

watch(() => props.modelValue, (newVal) => {
  selectedFilters.value = { ...newVal };
  initFiltersNull();
}, { deep: true });

onMounted(async () => {
  selectedFilters.value = { ...props.modelValue }
  // Removed duplicate loadReferenceData(). Only handled by watcher now.
})

watch(
  () => availableFilters.value,
  (filters) => {
    filters.forEach((filter) => {
      if (
        filter &&
        filter.key &&
        (selectedFilters.value[filter.key] === undefined ||
          selectedFilters.value[filter.key] === '' ||
          selectedFilters.value[filter.key] === 0)
      ) {
        selectedFilters.value[filter.key] = null;
        onFilterChange();
      }
    });
  },
  { immediate: true, deep: true }
);
</script>

<style scoped>
.filters-container {
  margin-bottom: 0;
}

.filters-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  width: 100%;
}

.filters-label {
  font-size: 14px;
  color: #606266;
  font-weight: 500;
  white-space: nowrap;
  flex-shrink: 0;
}

.filter-dropdowns {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  flex: 1;
  min-width: 0;
}

.filter-dropdown-item {
  display: flex;
  align-items: center;
}

.filter-select {
  min-width: 180px;
  max-width: 200px;
}


/* Date Range Filter Styles */
.date-range-filter {
  display: flex;
  align-items: center;
}

.date-input-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.date-picker-small {
  width: 140px;
}

.date-separator {
  font-size: 12px;
  color: #606266;
  white-space: nowrap;
}


/* Responsive adjustments */
@media (max-width: 1366px) {
  .filter-select {
    min-width: 160px;
    max-width: 180px;
  }
  
  .filter-dropdowns {
    gap: 8px;
  }
}

@media (max-width: 768px) {
  .filters-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .filter-dropdowns {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
  }
  
  .filter-select {
    min-width: 100%;
    width: 100%;
    max-width: none;
  }
  
}

@media (max-width: 480px) {
  .filter-select {
    min-width: 100%;
    max-width: none;
  }
}
</style>
