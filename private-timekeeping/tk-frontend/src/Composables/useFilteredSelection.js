import { computed, ref } from 'vue'

/**
 * Composable for handling filtered selection logic
 * Provides utilities for managing selection state when items are filtered
 */
export function useFilteredSelection() {
  /**
   * Creates filtered selection state and handlers
   * @param {Array} allItems - All available items
   * @param {Object} filters - Current filter values
   * @param {Array} currentSelection - Currently selected item IDs
   * @param {Function} filterFunction - Function to filter items based on filters
   * @param {Function} onSelectionChange - Callback function when selection changes
   * @returns {Object} Selection state and handlers
   */
  const createFilteredSelection = (allItems, filters, currentSelection, filterFunction, onSelectionChange) => {
    const filteredItems = computed(() => {
      const items = typeof allItems === 'function' ? allItems() : allItems
      const filterValues = typeof filters === 'function' ? filters() : filters
      return filterFunction(items, filterValues)
    })
    
    const isAllFilteredSelected = computed(() => {
      if (filteredItems.value.length === 0) return false
      const selection = typeof currentSelection === 'function' ? currentSelection() : currentSelection
      return filteredItems.value.every(item => selection.includes(item.id))
    })
    
    const isIndeterminate = computed(() => {
      const selection = typeof currentSelection === 'function' ? currentSelection() : currentSelection
      const selectedCount = filteredItems.value.filter(item => selection.includes(item.id)).length
      return selectedCount > 0 && selectedCount < filteredItems.value.length
    })
    
    const handleSelectAllFiltered = (selected) => {
      const selection = typeof currentSelection === 'function' ? currentSelection() : currentSelection
      
      if (selected) {
        // Select all filtered items
        const filteredIds = filteredItems.value.map(item => item.id)
        const newSelection = [...new Set([...selection, ...filteredIds])]
        onSelectionChange(newSelection)
      } else {
        // Deselect all filtered items
        const filteredIds = new Set(filteredItems.value.map(item => item.id))
        const newSelection = selection.filter(id => !filteredIds.has(id))
        onSelectionChange(newSelection)
      }
    }
    
    return {
      filteredItems,
      isAllFilteredSelected,
      isIndeterminate,
      handleSelectAllFiltered
    }
  }
  
  return {
    createFilteredSelection
  }
}

export default useFilteredSelection
