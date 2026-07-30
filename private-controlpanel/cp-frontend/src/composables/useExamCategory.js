import { ref, reactive } from 'vue'
import ApiService from '../Services/api.js'

export function useExamCategory() {
    const items = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const formData = reactive({
        name: '',
        description: '',
        subcategories: []
    })

    // Fetch all exam categories
    async function fetchList() {
        loading.value = true
        try {
            const response = await ApiService.getExamCategories()
            if (response && response.success) {
                items.value = response.data.map(item => ({
                    id: parseInt(item.id),
                    category_code: item.category_code,
                    name: item.name,
                    description: item.description
                }))
            }
        } catch (error) {
            console.error('Error fetching exam categories:', error)
        } finally {
            loading.value = false
        }
    }

    async function fetchCategoryDetails(id = 0) {
        try {
            const response = await ApiService.getExamCategoryFormData(id)
            if (response && response.success) {
                return response.data
            }
        } catch (error) {
            console.error('Error fetching exam category details:', error)
            throw error
        }
    }

    // Get form data for adding/editing
    async function getFormData(id = 0) {
        try {
            const data = await fetchCategoryDetails(id)
            if (data) {

                // Set main category data
                if (data.exam_categories && data.exam_categories.length > 0) {
                    const category = data.exam_categories[0]
                    formData.name = category.name || ''
                    formData.description = category.description || ''
                } else {
                    formData.name = ''
                    formData.description = ''
                }

                // Set subcategories
                formData.subcategories = data.sub_categories ? data.sub_categories.map(sub => ({
                    _rowKey: `sub-${sub.id}`,
                    id: parseInt(sub.id, 10) || 0,
                    sub_category_code: sub.sub_category_code || '',
                    sub_category: sub.sub_category || '',
                    difficulty_level: parseInt(sub.difficulty_level, 10) || 1,
                    existing_questions: parseInt(sub.existing_questions, 10) || 0,
                    is_essay: sub.is_essay === true || sub.is_essay === 1 || sub.is_essay === '1'
                })) : []

                return {
                    exam_diff_levels: data.exam_diff_levels || [],
                    subcategories: formData.subcategories
                }
            }
        } catch (error) {
            console.error('Error fetching form data:', error)
            throw error
        }
    }

    function buildPayloadFromSource(source) {
        const subcategories = source?.subcategories || []
        return {
            name: source?.name || '',
            description: source?.description || '',
            sub_name: subcategories.map(sub => sub.sub_category || ''),
            sub_id: subcategories.map(sub => sub.id ?? 0),
            difficulty_level: subcategories.map(sub => sub.difficulty_level ?? 1),
            existing_questions: subcategories.map(sub => sub.existing_questions ?? 0),
            is_essay: subcategories.map(sub => (sub.is_essay ? 1 : 0))
        }
    }

    // Save exam category
    async function saveExamCategory(id = 0, data = null) {
        saving.value = true
        try {
            const payload = data
                ? buildPayloadFromSource(data)
                : buildPayloadFromSource(formData)

            const response = await ApiService.saveExamCategory(id ?? 0, payload)
            if (response && response.success) {
                await fetchList() // Refresh the list
                resetForm()
                return response
            }
        } catch (error) {
            console.error('Error saving exam category:', error)
            throw error
        } finally {
            saving.value = false
        }
    }

    // Add subcategory
    function addSubcategory() {
        formData.subcategories.push({
            _rowKey: `new-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
            id: 0,
            sub_category_code: '',
            sub_category: '',
            difficulty_level: 1,
            existing_questions: 0,
            is_essay: false
        })
    }

    // Remove subcategory
    function removeSubcategory(index) {
        formData.subcategories.splice(index, 1)
    }

    // Delete exam category
    async function deleteExamCategory(id) {
        try {
            const response = await ApiService.deleteExamCategory(id)
            if (response && response.success) {
                await fetchList()
                return response
            }
            throw new Error(response?.message || 'Failed to delete exam category')
        } catch (error) {
            console.error('Error deleting exam category:', error)
            throw error
        }
    }

    // Delete subcategory
    async function deleteSubcategory(id) {
        try {
            const response = await ApiService.deleteSubCategory(id)
            if (response && response.success) {
                await fetchList() // Refresh the list
                return response
            }
        } catch (error) {
            console.error('Error deleting subcategory:', error)
            throw error
        }
    }

    // Get subcategory positions
    async function getSubCategoryPositions(id) {
        try {
            const response = await ApiService.getSubCategoryPositions(id)
            if (response && response.success) {
                return response.data
            }
        } catch (error) {
            console.error('Error fetching subcategory positions:', error)
            throw error
        }
    }

    // Add positions to subcategory
    async function addPositions(id, payload) {
        try {
            const response = await ApiService.addPositions(id, payload)
            if (response && response.success) {
                return response
            }
        } catch (error) {
            console.error('Error adding positions:', error)
            throw error
        }
    }

    // Get subcategory questions
    async function getSubCategoryQuestions(id) {
        try {
            const response = await ApiService.getSubCategoryQuestions(id)
            if (response && response.success) {
                return response.data
            }
        } catch (error) {
            console.error('Error fetching subcategory questions:', error)
            throw error
        }
    }

    // Add questions to subcategory
    async function addQuestions(id, questionId, payload) {
        try {
            const response = await ApiService.addQuestions(id, questionId, payload)
            if (response && response.success) {
                return response
            }
        } catch (error) {
            console.error('Error adding questions:', error)
            throw error
        }
    }

    // Delete question
    async function deleteQuestion(id) {
        try {
            const response = await ApiService.deleteQuestion(id)
            if (response && response.success) {
                return response
            }
        } catch (error) {
            console.error('Error deleting question:', error)
            throw error
        }
    }

    // Delete choice
    async function deleteChoice(id) {
        try {
            const response = await ApiService.deleteChoice(id)
            if (response && response.success) {
                return response
            }
        } catch (error) {
            console.error('Error deleting choice:', error)
            throw error
        }
    }

    // Get question for editing
    async function getQuestionForEdit(id) {
        try {
            const response = await ApiService.getQuestionForEdit(id)
            if (response && response.success) {
                return response.data
            }
        } catch (error) {
            console.error('Error fetching question for edit:', error)
            throw error
        }
    }

    // Reset form data
    function resetForm() {
        formData.name = ''
        formData.description = ''
        formData.subcategories = []
    }

    // Set form data for editing
    function setFormData(data) {
        formData.name = data.name || ''
        formData.description = data.description || ''
        formData.subcategories = data.subcategories || []
    }

    return {
        items,
        loading,
        saving,
        formData,
        fetchList,
        getFormData,
        saveExamCategory,
        deleteExamCategory,
        addSubcategory,
        removeSubcategory,
        deleteSubcategory,
        getSubCategoryPositions,
        addPositions,
        getSubCategoryQuestions,
        addQuestions,
        deleteQuestion,
        deleteChoice,
        getQuestionForEdit,
        fetchCategoryDetails,
        resetForm,
        setFormData
    }
}
