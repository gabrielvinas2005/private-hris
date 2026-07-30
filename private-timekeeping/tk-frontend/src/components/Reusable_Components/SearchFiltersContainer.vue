<template>
  <div class="search-filters-container">
    <div class="container-header">
      <div class="input-group" style="display: flex; gap: 20px; flex-shrink: 1;">
        <div v-if="showSearchSection" class="search-section">
          <EmployeeSearchbar
            v-model="searchValue"
            :placeholder="searchPlaceholder"
            :loading="searchLoading"
            :debounce-ms="searchDebounceMs"
            :available-years="availableYears"
            :show-advanced-filters="showAdvancedFilters"
            :show-year-filter="showYearFilter"
            :initial-year="initialYear"
            @search="onSearch"
            @filter-change="onSearchFilterChange"
          />
        </div>
        <div v-if="showFiltersSection" class="filters-section">
          <Filters
            v-model="filtersValue"
            :filters="filters"
            :loading="filtersLoading"
            :auto-fetch="autoFetch"
            @filter-change="onFiltersChange"
          />
        </div>
      </div>
      <div v-if="showDateTimeFilterSection" class="datetime-filter-section">
        <DateTimeFilter
          :model-value="dateTimeFilterValue"
          :show-time="showTimeInFilter"
          @update:model-value="onDateTimeFilterChange"
          @change="onDateTimeFilterChange"
        />
      </div>
      <div class="right-spacer"></div>
      <div class="actions-section" style="display: flex; gap: 12px; align-items: center; flex-shrink: 0;">
        <slot name="actions" />
      <div v-if="showExportSection" class="export-section">
        <PreviewExport
          :html-content="htmlContent"
          :title="previewTitle"
          :filename="filename"
          :on-excel="onExcel"
          :on-pdf="onPdf"
          :on-word="onWord"
          :loading="loading"
        />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * SearchFiltersContainer Component
 * 
 * STANDARD PATTERN FOR FILTERED DATA EXPORT:
 * 
 * When using SearchFiltersContainer with a child component that handles its own filtering
 * (e.g., a table component with SearchFiltersContainer inside it), follow this pattern:
 * 
 * 1. Child component should expose filtered/sorted data via defineExpose:
 *    defineExpose({ sortedRows, filteredRows })
 * 
 * 2. Parent component should use useFilteredDataExport composable:
 *    const childRef = ref(null)
 *    const filteredDataForExport = useFilteredDataExport(childRef, rawData, 'sortedRows')
 * 
 * 3. Use filteredDataForExport in computed properties for reports:
 *    const reportHtmlContent = computed(() => {
 *      const dataToExport = filteredDataForExport.value
 *      // ... generate HTML from dataToExport
 *    })
 * 
 * 4. Pass reportHtmlContent to PreviewExport component:
 *    <PreviewExport :html-content="reportHtmlContent" ... />
 * 
 * This ensures that Preview&Export always reflects the current filtered/sorted table state.
 */
import { ref, computed, watch } from 'vue'
import EmployeeSearchbar from '@/components/Reusable_Components/EmployeeSearchbar.vue'
import Filters from './Filters.vue'
import PreviewExport from './Preview&Export.vue'
import DateTimeFilter from './DateTimeFilter.vue'

const props = defineProps({ 
  // Search props
  searchValue: { type: String, default: '' },
  searchPlaceholder: { type: String, default: 'Search employee or position...' },
  searchLoading: { type: Boolean, default: false },
  searchDebounceMs: { type: Number, default: 300 },
  
  // New visibility toggles
  showSearchSection: { type: Boolean, default: true },
  showFiltersSection: { type: Boolean, default: true },
  showDateTimeFilterSection: { type: Boolean, default: false },
  showExportSection: { type: Boolean, default: true },
  
  // DateTime Filter props
  dateTimeFilterValue: { type: Object, default: () => ({ dateFrom: null, dateTo: null, timeFrom: null, timeTo: null }) },
  showTimeInFilter: { type: Boolean, default: false },
  
  // Search filter props
  availableYears: { type: Array, default: () => [] },
  showAdvancedFilters: { type: Boolean, default: true },
  showYearFilter: { type: Boolean, default: false },
  initialYear: { type: [String, Number], default: null },
  
  // Filters props
  filters: { type: Array, default: () => [] },
  filtersValue: { type: Object, default: () => ({}) },
  filtersLoading: { type: Boolean, default: false },
  autoFetch: { type: Boolean, default: true },
  
  // PreviewButton props
  htmlContent: { type: String, default: '' },
  previewTitle: { type: String, default: 'Document Preview' },
  filename: { type: String, default: 'document' },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits([
  'update:searchValue',
  'update:filtersValue',
  'update:dateTimeFilterValue',
  'search',
  'search-filter-change',
  'filters-change',
  'datetime-filter-change',
  'excel',
  'pdf',
  'word'
])

// Local reactive values
const searchValue = ref(props.searchValue)
const filtersValue = ref({ ...props.filtersValue })
const dateTimeFilterValue = ref({ ...props.dateTimeFilterValue })

// Methods
const onSearch = (value) => {
  emit('search', value)
}

const onSearchFilterChange = (filters) => {
  emit('search-filter-change', filters)
}

const onFiltersChange = (filters) => {
  filtersValue.value = { ...filters }
  emit('update:filtersValue', { ...filters })
  emit('filters-change', { ...filters })
}

const onExcel = () => {
  emit('excel')
}

const onPdf = () => {
  emit('pdf')
}

const onWord = () => {
  emit('word')
}

const onDateTimeFilterChange = (value) => {
  dateTimeFilterValue.value = { ...value }
  emit('update:dateTimeFilterValue', { ...value })
  emit('datetime-filter-change', { ...value })
}

// Watch for external changes
watch(() => props.searchValue, (newValue) => {
  searchValue.value = newValue
})

watch(() => props.filtersValue, (newValue) => {
  filtersValue.value = { ...newValue }
}, { deep: true })

watch(() => props.dateTimeFilterValue, (newValue) => {
  dateTimeFilterValue.value = { ...newValue }
}, { deep: true })

// Emit changes
watch(searchValue, (newValue) => {
  emit('update:searchValue', newValue)
})
</script>

<style scoped>
.search-filters-container {
  background: #ffffff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 20px;
}

.container-header {
  display: flex;
  align-items: center;
  gap: 0;
  flex-wrap: nowrap !important;
  overflow-x: auto;
  width: 100%;
  min-width: 0;
}
.input-group {
  gap: 20px;
  flex-shrink: 1;
}
.search-section,
.filters-section {
  flex-shrink: 0;
  min-width: 200px;
}
.right-spacer {
  flex-grow: 1;
  flex-shrink: 1;
  min-width: 10px;
}
.datetime-filter-section {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  min-width: 300px;
  z-index: 2;
}
.export-section {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  min-width: 180px;
  z-index: 2;
}
@media (max-width: 1366px) {
  .input-group { gap: 16px; }
  .search-section, .filters-section { min-width: 180px; }
  .datetime-filter-section { min-width: 280px; }
  .export-section { min-width: 130px; }
}
@media (max-width: 768px) {
  .search-filters-container {
    padding: 16px;
    margin-bottom: 16px;
  }
  .input-group { gap: 12px; }
  .search-section, .filters-section { min-width: 150px; }
  .datetime-filter-section { min-width: 250px; }
  .export-section { min-width: 110px; }
}
@media (max-width: 480px) {
  .search-filters-container { padding: 12px; }
  .input-group { gap: 10px; }
  .search-section, .filters-section { min-width: 120px; }
  .datetime-filter-section { min-width: 200px; }
  .export-section { min-width: 90px; }
}
</style>
