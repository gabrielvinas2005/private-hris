<template>
  <div class="pds-questionnaire">
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
      <div
        class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200"
      >
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-semibold text-gray-900 inline-flex items-center">
            <svg
              class="w-5 h-5 mr-2 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            PDS Questionnaire
          </h3>
          <button
            @click="saveQuestionnaire"
            :disabled="saving"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center gap-2 disabled:bg-gray-400 disabled:cursor-not-allowed"
          >
            <svg
              v-if="saving"
              class="w-4 h-4 animate-spin"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
            {{ saving ? "Saving..." : "Save Answers" }}
          </button>
        </div>
      </div>

      <div class="p-6">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div
            class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"
          ></div>
          <p class="mt-4 text-gray-600">Loading questionnaire...</p>
        </div>

        <!-- Error State -->
        <div
          v-else-if="error"
          class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6"
        >
          <p class="text-red-600">{{ error }}</p>
        </div>

        <!-- Success Message -->
        <div
          v-if="successMessage"
          class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6"
        >
          <p class="text-green-600">{{ successMessage }}</p>
        </div>

        <!-- Questions List -->
        <div v-else-if="!loading && questions.length > 0" class="space-y-6">
          <div
            v-for="(question, index) in questions"
            :key="question.id"
            class="bg-gray-50 rounded-lg p-6 border border-gray-200"
          >
            <div class="flex items-start gap-4">
              <div
                class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1"
              >
                <span class="text-blue-600 font-semibold text-sm">{{
                  question.code || question.que_id || index + 1
                }}</span>
              </div>
              <div class="flex-1">
                <h4 class="text-base font-medium text-gray-900 mb-4">
                  {{ question.questions }}
                </h4>

                <!-- Yes/No Radio Buttons -->
                <div class="flex gap-6 mb-4">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="radio"
                      :name="`question_${question.id}`"
                      :value="true"
                      :checked="question.is_yes"
                      @change="handleAnswerChange(question.id, true, false)"
                      class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                    />
                    <span class="text-sm font-medium text-gray-700">Yes</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="radio"
                      :name="`question_${question.id}`"
                      :value="false"
                      :checked="question.is_no"
                      @change="handleAnswerChange(question.id, false, true)"
                      class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                    />
                    <span class="text-sm font-medium text-gray-700">No</span>
                  </label>
                </div>

                <!-- Conditional Fields (shown when Yes is selected) -->
                <div
                  v-if="question.is_yes"
                  class="mt-4 space-y-4 pl-6 border-l-2 border-blue-200"
                >
                  <!-- Yes Details -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Details (if yes)
                    </label>
                    <textarea
                      v-model="question.yes_details"
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Please provide details..."
                    ></textarea>
                  </div>

                  <!-- Date Filed -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Date Filed
                    </label>
                    <input
                      type="date"
                      v-model="question.date_filed"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                  </div>

                  <!-- Case Status -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Case Status
                    </label>
                    <input
                      type="text"
                      v-model="question.case_status"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Enter case status..."
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="!loading && questions.length === 0"
          class="text-center py-12 text-gray-500"
        >
          <svg
            class="w-16 h-16 mx-auto mb-4 text-gray-300"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <p>No questions available.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { ElNotification } from "element-plus";
import ApiService from "@/services/api";

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});

const questions = ref([]);
const loading = ref(false);
const saving = ref(false);
const error = ref(null);
const successMessage = ref(null);
const employeeId = ref(null);

const loadQuestions = async () => {
  loading.value = true;
  error.value = null;
  successMessage.value = null;

  try {
    // Get employee ID
    if (employeeId.value === null || employeeId.value === undefined) {
      const response = await ApiService.getApplicantPage();
      if (response.data && response.data.data) {
        employeeId.value =
          response.data.data.employee_info?.[0]?.id ??
          props.info?.employee_info?.[0]?.id ??
          props.info?.employee_id ??
          props.info?.id ??
          null;
      }
    }

    if (employeeId.value === null || employeeId.value === undefined) {
      error.value =
        "Unable to determine employee ID. Please refresh the page and try again.";
      loading.value = false;
      return;
    }

    // Fetch questions
    const response = await ApiService.getPDSQuestions(employeeId.value);

    if (response.data && response.data.success) {
      questions.value = response.data.data || [];
    } else {
      error.value = response.data?.message || "Failed to load questions";
    }
  } catch (err) {
    console.error("Error loading questions:", err);
    error.value =
      err.response?.data?.message ||
      err.message ||
      "Failed to load questionnaire. Please try again.";
  } finally {
    loading.value = false;
  }
};

const handleAnswerChange = (questionId, isYes, isNo) => {
  const question = questions.value.find((q) => q.id === questionId);
  if (question) {
    question.is_yes = isYes;
    question.is_no = isNo;
    // Clear conditional fields if No is selected
    if (isNo) {
      question.yes_details = null;
      question.date_filed = null;
      question.case_status = null;
    }
  }
};

const saveQuestionnaire = async () => {
  if (!employeeId.value) {
    ElNotification({
      title: "Error",
      message: "Unable to determine employee ID.",
      type: "error",
      duration: 3000,
    });
    return;
  }

  saving.value = true;
  error.value = null;
  successMessage.value = null;

  try {
    // Prepare data for submission
    const formData = {
      question_id: questions.value.map((q) => q.id),
      is_yes: {},
      is_no: {},
      yes_details: questions.value.map((q) => q.yes_details || ""),
      date_filed: questions.value.map((q) => q.date_filed || ""),
      case_status: questions.value.map((q) => q.case_status || ""),
    };

    // Set is_yes and is_no as object with question_id as key
    questions.value.forEach((q) => {
      if (q.is_yes) {
        formData.is_yes[q.id] = true;
      }
      if (q.is_no) {
        formData.is_no[q.id] = true;
      }
    });

    const response = await ApiService.storePDSQuestionnaire(
      employeeId.value,
      formData
    );

    if (response.data && response.data.success) {
      successMessage.value = "Questionnaire answers saved successfully!";
      ElNotification({
        title: "Success",
        message: "Questionnaire answers saved successfully!",
        type: "success",
        duration: 3000,
      });
    } else {
      throw new Error(response.data?.message || "Failed to save answers");
    }
  } catch (err) {
    console.error("Error saving questionnaire:", err);
    error.value =
      err.response?.data?.message ||
      err.message ||
      "Failed to save questionnaire. Please try again.";
    ElNotification({
      title: "Error",
      message: error.value,
      type: "error",
      duration: 5000,
    });
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadQuestions();
});
</script>

<style scoped>
.pds-questionnaire {
  max-width: 100%;
}
</style>

