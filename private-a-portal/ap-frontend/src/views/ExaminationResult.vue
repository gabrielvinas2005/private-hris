<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              Examination Results
            </h1>
            <p class="mt-2 text-gray-600">
              {{ examinationTitle || "Loading results..." }}
            </p>
          </div>
          <div class="flex space-x-4">
            <router-link
              to="/dashboard"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              <i class="fas fa-home mr-2"></i>
              Back to Dashboard
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div
          class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
        ></div>
        <p class="mt-4 text-gray-600">Loading examination results...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div
          class="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md mx-auto"
        >
          <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-4"></i>
          <p class="text-red-700">{{ error }}</p>
          <button
            @click="loadResults"
            class="mt-4 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>

      <!-- Results Content -->
      <div v-else-if="resultData" class="space-y-6">
        <!-- Overall Score Card -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="text-center">
            <div
              class="mx-auto w-24 h-24 rounded-full flex items-center justify-center mb-4"
              :class="getScoreColor(resultData.score_percentage)"
            >
              <span class="text-3xl font-bold text-white"
                >{{ resultData.score_percentage }}%</span
              >
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Your Score</h2>
            <p class="text-gray-600 mb-4">
              {{ getScoreMessage(resultData.score_percentage) }}
            </p>

            <div class="grid grid-cols-3 gap-4 max-w-md mx-auto">
              <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                  {{ resultData.correct_answers }}
                </div>
                <div class="text-sm text-gray-600">Correct</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                  {{ resultData.incorrect_answers }}
                </div>
                <div class="text-sm text-gray-600">Incorrect</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-gray-900">
                  {{ resultData.total_questions }}
                </div>
                <div class="text-sm text-gray-600">Total</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Examination Details -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Examination Details
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Examination Title</label
              >
              <p class="text-gray-900">{{ resultData.examination_title }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Date Taken</label
              >
              <p class="text-gray-900">
                {{ formatDate(resultData.submitted_at) }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700"
                >Time Spent</label
              >
              <p class="text-gray-900">
                {{ formatDuration(resultData.time_spent) }}
              </p>
            </div>
            <!-- <div>
               <label class="block text-sm font-medium text-gray-700"
                >Status</label
              >
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="getStatusClass(resultData.status)"
              >
                {{ resultData.status }}
              </span> -->
            <!-- </div> --> 
          </div>
        </div>

        <!-- Category Breakdown -->
        <div
          v-if="resultData.category_breakdown && resultData.category_breakdown.length"
          class="bg-white rounded-lg shadow p-6"
        >
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Category Breakdown
          </h3>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    scope="col"
                    class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Category
                  </th>
                  <th
                    scope="col"
                    class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Correct
                  </th>
                  <th
                    scope="col"
                    class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Total
                  </th>
                  <th
                    scope="col"
                    class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Score
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="row in resultData.category_breakdown"
                  :key="row.label"
                >
                  <td class="px-4 py-2 whitespace-nowrap">
                    <span class="font-medium text-gray-900">{{
                      row.label
                    }}</span>
                    <span
                      v-if="row.isEssay"
                      class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-yellow-50 text-yellow-800 border border-yellow-100"
                    >
                      For review
                    </span>
                  </td>
                  <td class="px-4 py-2 text-center text-gray-900">
                    <span v-if="!row.isEssay">{{ row.correct }}</span>
                    <span v-else>–</span>
                  </td>
                  <td class="px-4 py-2 text-center text-gray-900">
                    {{ row.total }}
                  </td>
                  <td class="px-4 py-2 text-center">
                    <span
                      v-if="!row.isEssay"
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                      :class="
                        row.percentage >= 75
                          ? 'bg-green-100 text-green-800'
                          : row.percentage >= 50
                          ? 'bg-yellow-100 text-yellow-800'
                          : 'bg-red-100 text-red-800'
                      "
                    >
                      {{ row.percentage }}%
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                    >
                      For review
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Detailed Results -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Question Review
          </h3>
          <div class="space-y-4">
            <div
              v-for="(question, index) in resultData.questions"
              :key="question.id"
              class="border border-gray-200 rounded-lg p-4"
              :class="{
                'bg-green-50 border-green-200': question.is_correct,
                'bg-red-50 border-red-200': !question.is_correct,
              }"
            >
              <div class="flex items-start justify-between mb-3">
                <h4 class="text-lg font-medium text-gray-900">
                  Question {{ index + 1 }}
                </h4>
                <span
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                  :class="
                    question.is_correct
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800'
                  "
                >
                  <i
                    :class="
                      question.is_correct ? 'fas fa-check' : 'fas fa-times'
                    "
                    class="mr-1"
                  ></i>
                  {{ question.is_correct ? "Correct" : "Incorrect" }}
                </span>
              </div>

              <div class="mb-3">
                <p class="text-gray-700">{{ question.question }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1"
                    >Your Answer</label
                  >
                  <div
                    class="p-2 bg-gray-100 rounded text-sm"
                    :class="
                      question.is_correct
                        ? 'bg-green-100 text-green-800'
                        : 'bg-red-100 text-red-800'
                    "
                  >
                    {{ question.user_answer_text || "No answer provided" }}
                  </div>
                </div>
                <div v-if="!question.is_correct">
                  <label class="block text-sm font-medium text-gray-700 mb-1"
                    >Correct Answer</label
                  >
                  <div class="p-2 bg-green-100 text-green-800 rounded text-sm">
                    {{ question.correct_answer_text }}
                  </div>
                </div>
              </div>

              <div
                v-if="question.explanation"
                class="mt-3 pt-3 border-t border-gray-200"
              >
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >Explanation</label
                >
                <p class="text-sm text-gray-600">{{ question.explanation }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-center space-x-4">
          <button
            @click="downloadResults"
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
          >
            <i class="fas fa-download mr-2"></i>
            Download Results
          </button>
          <router-link
            to="/dashboard"
            class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors"
          >
            <i class="fas fa-home mr-2"></i>
            Back to Dashboard
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { ApiService } from "@/services/api.js";

const router = useRouter();
const route = useRoute();

// Reactive data
const resultData = ref(null);
const loading = ref(false);
const error = ref(null);

// Computed properties
const examinationTitle = computed(() => {
  return resultData.value?.examination_title || "Examination Results";
});

// Methods
const loadResults = async () => {
  try {
    loading.value = true;
    error.value = null;

    const examinationId = route.params.id;
    if (!examinationId) {
      throw new Error("Examination ID not provided");
    }

    const response = await ApiService.getExamResult(examinationId);

    if (response.data && response.data.data) {
      const data = response.data.data;

      // Transform backend data to frontend format
      if (data.exam && data.exam.length > 0) {
        const exam = data.exam[0];
        const passingCriteria = exam.passing_criteria || 0;

        // Convert last_duration from minutes to seconds for display
        const timeSpent = exam.last_duration
          ? Math.round(exam.last_duration * 60)
          : 0;

        // Build per-category breakdown (sub-categories)
        const breakdownRaw = data.exam_total_sub_categories || [];
        const category_breakdown = breakdownRaw.map((row) => {
          const label = row.sub_category || "Uncategorized";
          const isEssay = label.toLowerCase() === "essay";
          const total = Number(row.total_items || 0);
          const correct = Number(row.total_correct || 0);
          const percentage =
            !isEssay && total > 0
              ? Math.round((correct / total) * 100)
              : null;

          return {
            label,
            total,
            correct,
            percentage,
            isEssay,
          };
        });

        // Derive overall totals from objective categories only (exclude Essay)
        let totalItems = 0;
        let totalCorrect = 0;
        category_breakdown.forEach((row) => {
          if (!row.isEssay) {
            totalItems += row.total;
            totalCorrect += row.correct;
          }
        });

        const scorePercentage =
          totalItems > 0 ? Math.round((totalCorrect / totalItems) * 100) : 0;

        const status =
          scorePercentage >= passingCriteria
            ? "passed"
            : scorePercentage > 0
              ? "failed"
              : "pending";

        resultData.value = {
          examination_title: exam.exam_set || "Examination",
          score_percentage: scorePercentage,
          correct_answers: totalCorrect,
          incorrect_answers: totalItems - totalCorrect,
          total_questions: totalItems,
          status: status,
          submitted_at: exam.date_completed || new Date().toISOString(),
          time_spent: timeSpent, // Use last_duration from backend
          category_breakdown,
          questions: [], // You'll need to fetch question details separately
        };
      } else {
        throw new Error("No exam data found in response");
      }
    } else {
      throw new Error("No result data received");
    }
  } catch (err) {
    console.error("Error loading examination results:", err);
    error.value =
      err.response?.data?.message || "Failed to load examination results";
  } finally {
    loading.value = false;
  }
};

const getScoreColor = (score) => {
  if (score >= 80) return "bg-green-500";
  if (score >= 60) return "bg-yellow-500";
  return "bg-red-500";
};

const getScoreMessage = (score) => {
  if (score >= 90) return "Excellent! Outstanding performance.";
  if (score >= 80) return "Very good! Well done.";
  if (score >= 70) return "Good job! Keep up the good work.";
  if (score >= 60) return "Satisfactory. Consider reviewing the material.";
  return "Below passing. Please review the material and try again.";
};

const getStatusClass = (status) => {
  if (!status) return "bg-gray-100 text-gray-800"; // Handle undefined/null
  switch (status.toLowerCase()) {
    case "passed":
      return "bg-green-100 text-green-800";
    case "failed":
      return "bg-red-100 text-red-800";
    case "pending":
      return "bg-yellow-100 text-yellow-800";
    default:
      return "bg-gray-100 text-gray-800";
  }
};

const formatDate = (dateString) => {
  if (!dateString) {
    return "N/A";
  }
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) {
      return "Invalid Date";
    }
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      hour12: true,
    });
  } catch (error) {
    console.error("Error formatting date:", error);
    return "Invalid Date";
  }
};

const formatDuration = (seconds) => {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  if (hours > 0) {
    return `${hours}h ${minutes}m ${secs}s`;
  }
  return `${minutes}m ${secs}s`;
};

const downloadResults = () => {
  // Create a simple text report
  const report = generateReport();
  const blob = new Blob([report], { type: "text/plain" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `examination-results-${Date.now()}.txt`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
};

const generateReport = () => {
  if (!resultData.value) return "";

  let report = `EXAMINATION RESULTS REPORT\n`;
  report += `================================\n\n`;
  report += `Examination: ${resultData.value.examination_title}\n`;
  report += `Date: ${formatDate(resultData.value.submitted_at)}\n`;
  report += `Score: ${resultData.value.score_percentage}%\n`;
  report += `Status: ${resultData.value.status}\n`;
  report += `Correct Answers: ${resultData.value.correct_answers}\n`;
  report += `Incorrect Answers: ${resultData.value.incorrect_answers}\n`;
  report += `Total Questions: ${resultData.value.total_questions}\n`;
  report += `Time Spent: ${formatDuration(resultData.value.time_spent)}\n\n`;

  report += `DETAILED RESULTS\n`;
  report += `================\n\n`;

  resultData.value.questions.forEach((question, index) => {
    report += `Question ${index + 1}: ${
      question.is_correct ? "CORRECT" : "INCORRECT"
    }\n`;
    report += `Question: ${question.question}\n`;
    report += `Your Answer: ${
      question.user_answer_text || "No answer provided"
    }\n`;
    if (!question.is_correct) {
      report += `Correct Answer: ${question.correct_answer_text}\n`;
    }
    if (question.explanation) {
      report += `Explanation: ${question.explanation}\n`;
    }
    report += `\n`;
  });

  return report;
};

// Lifecycle
onMounted(async () => {
  await loadResults();
});
</script>

<style scoped>
/* Custom styles for results page */
</style>
