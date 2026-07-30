/**
 * Composable for tracking filtered/sorted data from child components
 * to ensure reactivity when exporting or generating reports.
 * 
 * This is the standard pattern for ensuring that filtered table data
 * is properly reflected in Preview&Export components.
 * 
 * @param {Ref} childComponentRef - Reference to the child component that exposes filtered/sorted data
 * @param {Ref} fallbackData - Fallback data to use when child component data is not available
 * @param {String} exposedPropertyName - Name of the exposed property (default: 'sortedRows')
 * 
 * @returns {Ref} filteredDataForExport - Reactive ref containing the filtered/sorted data
 * 
 * @example
 * // In parent component:
 * const childRef = ref(null)
 * const rawData = ref([])
 * const filteredDataForExport = useFilteredDataExport(childRef, rawData, 'sortedRows')
 * 
 * // In child component:
 * defineExpose({ sortedRows })
 * 
 * // Use filteredDataForExport in computed properties for reports:
 * const reportHtmlContent = computed(() => {
 *   const dataToExport = filteredDataForExport.value
 *   // ... generate HTML from dataToExport
 * })
 */
import { ref, watch } from 'vue'

export function useFilteredDataExport(childComponentRef, fallbackData, exposedPropertyName = 'sortedRows') {
  // Track filtered/sorted data from child component for reactivity
  const filteredDataForExport = ref([])

  // Watch for changes in child component's exposed property to ensure reactivity
  watch(
    () => {
      if (!childComponentRef.value) return null
      return childComponentRef.value[exposedPropertyName]
    },
    (newData) => {
      if (newData && Array.isArray(newData)) {
        filteredDataForExport.value = newData
      } else {
        // Fallback to fallbackData if child component data is not available
        filteredDataForExport.value = Array.isArray(fallbackData.value) ? fallbackData.value : []
      }
    },
    { immediate: true, deep: true }
  )

  // Also watch fallbackData as fallback when child component is not ready
  watch(
    () => fallbackData.value,
    () => {
      // Only update if child component data is not available
      if (!childComponentRef.value || 
          !childComponentRef.value[exposedPropertyName] || 
          childComponentRef.value[exposedPropertyName].length === 0) {
        filteredDataForExport.value = Array.isArray(fallbackData.value) ? fallbackData.value : []
      }
    },
    { deep: true }
  )

  return filteredDataForExport
}






