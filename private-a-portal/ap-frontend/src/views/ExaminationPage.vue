<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Examination</h1>
            <p class="mt-2 text-gray-600">
              {{ examinationData?.title || "Loading examination details..." }}
            </p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-sm text-gray-600">
              Time Remaining:
              <span
                class="  font-bold text-lg"
                :class="timeRemaining <= 300 ? 'text-red-600' : 'text-gray-900'"
              >
                {{ formatTime(timeRemaining) }}
              </span>
            </div>
            <router-link
              to="/dashboard"
              class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors"
            >
              <i class="fas fa-arrow-left mr-2"></i>
              Back to Dashboard
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div
          class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
        ></div>
        <p class="mt-4 text-gray-600">Loading examination...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div
          class="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md mx-auto"
        >
          <i class="fas fa-exclamation-triangle text-red-500 text-2xl mb-4"></i>
          <p class="text-red-700">{{ error }}</p>
          <button
            @click="loadExamination"
            class="mt-4 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>

      <!-- Examination Content -->
      <div
        v-else-if="examinationData"
        class="grid grid-cols-1 lg:grid-cols-12 gap-6"
      >
        <!-- Left sidebar -->
        <aside class="lg:col-span-4 xl:col-span-3">
          <div
            class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden lg:sticky lg:top-6"
          >
            <div
              class="px-5 py-4 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-white"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-sm font-semibold text-gray-900">
                    Sections
                  </div>
                  <div class="text-xs text-gray-600 mt-0.5">
                    Pick a sub-category to start
                  </div>
                </div>
                <div class="text-xs text-gray-500">
                  {{ groupedQuestions.length }} total
                </div>
              </div>
            </div>

            <div class="p-4 space-y-4 max-h-[70vh] overflow-auto">
              <div
                v-for="group in groupedQuestions"
                :key="group.subCategory"
                class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm"
              >
                <div
                  class="px-4 py-3 bg-gradient-to-r from-indigo-50 to-white border-b border-gray-100"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="flex items-center gap-2">
                        <div
                          class="h-2.5 w-2.5 rounded-full bg-indigo-500 mt-1"
                        ></div>
                        <div
                          class="text-sm font-semibold text-gray-900 truncate"
                        >
                          {{ group.label }}
                        </div>
                        <span
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-700"
                        >
                          {{ group.total }}
                        </span>
                      </div>
                      <div class="mt-2">
                        <div
                          class="flex items-center justify-between text-[11px] text-gray-500"
                        >
                          <span>{{ group.answered }} answered</span>
                          <span>{{ group.total - group.answered }} left</span>
                        </div>
                        <div
                          class="mt-1 w-full bg-gray-200 rounded-full h-1.5 overflow-hidden"
                        >
                          <div
                            class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300"
                            :style="{ width: group.progressPct + '%' }"
                          ></div>
                        </div>
                      </div>
                    </div>
                    <button
                      type="button"
                      class="shrink-0 inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border border-indigo-200 text-indigo-700 bg-white hover:bg-indigo-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      @click="goToQuestion(group.items[0].index)"
                      :disabled="!group.items.length"
                      title="Jump to this section"
                    >
                      Jump
                      <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
                    </button>
                  </div>
                </div>

                <!-- <div class="px-4 py-3">
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="item in group.items"
                      :key="item.question.id"
                      @click="goToQuestion(item.index)"
                      class="px-3 py-1 text-sm rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-200"
                      :class="getQuestionButtonClass(item.index)"
                      :title="`Question ${item.index + 1}`"
                    >
                      {{ item.index + 1 }}
                    </button>
                  </div>
                </div> -->
              </div>
            </div>
          </div>
        </aside>

        <!-- Main content -->
        <div class="lg:col-span-8 xl:col-span-9 bg-white rounded-lg shadow">
          <!-- Progress Bar -->
          <!-- <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm font-medium text-gray-700">Progress</span>
              <span class="text-sm text-gray-600"
                >{{
                  currentQuestionIndex !== null ? currentQuestionIndex + 1 : 0
                }}
                of {{ examinationData.questions?.length || 0 }}</span
              >
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div
                class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                :style="{ width: progressPercentage + '%' }"
              ></div>
            </div>
          </div> -->

          <!-- Question -->
          <div class="p-6">
            <div v-if="currentQuestion" class="space-y-6">
              <!-- Question Header -->
              <div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">
                  Question
                  <span v-if="currentSectionProgress" class="ml-2 text-gray-700">
                    {{ currentSectionProgress.current }}/{{ currentSectionProgress.total }}
                  </span>
                  <span v-else class="ml-2 text-gray-700">
                    {{ currentQuestionIndex + 1 }}/{{ examinationData.questions.length }}
                  </span>
                </h2>
                <div
                  v-if="currentQuestion.sub_category"
                  class="inline-flex items-center px-2 py-1 rounded-full text-sm font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 uppercase mb-3"
                >
                  {{ currentQuestion.sub_category }}
                </div>
                <div class="text-gray-700 text-lg leading-relaxed">
                  {{ currentQuestion.question }}
                </div>
              </div>

              <!-- Question Image -->
              <div v-if="currentQuestion.image_url" class="flex justify-center">
                <img
                  :src="currentQuestion.image_url"
                  :alt="'Question ' + (currentQuestionIndex + 1)"
                  class="max-w-full h-auto rounded-lg shadow"
                />
              </div>

              <!-- Answer Options -->
              <div v-if="isEssayQuestion(currentQuestion)" class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Your answer
                </label>
                <textarea
                  v-model="textAnswers[currentQuestion.id]"
                  rows="6"
                  placeholder="Type your answer here..."
                  class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-800 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors"
                  @blur="saveProgress"
                ></textarea>
                <div class="flex items-center justify-between text-xs text-gray-500">
                  <span>Autosaves on blur</span>
                  <span>
                    {{ (textAnswers[currentQuestion.id] || "").length }} characters
                  </span>
                </div>
              </div>

              <div v-else class="space-y-3">
                <label
                  v-for="(option, index) in currentQuestion.options"
                  :key="index"
                  class="flex items-start p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                  :class="{
                    'bg-blue-50 border-blue-300':
                      selectedAnswers[currentQuestion.id] === index,
                  }"
                >
                  <input
                    type="radio"
                    :name="'question_' + currentQuestion.id"
                    :value="index"
                    v-model="selectedAnswers[currentQuestion.id]"
                    @change="saveAnswer"
                    class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                  />
                  <span class="ml-3 text-gray-700">{{ option }}</span>
                </label>
              </div>

              <!-- Navigation -->
              <div class="flex justify-between pt-6 border-t border-gray-200">
                <button
                  @click="previousQuestion"
                  :disabled="
                    currentQuestionIndex === null || currentQuestionIndex === 0
                  "
                  class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                >
                  <i class="fas fa-arrow-left mr-2"></i>
                  Previous
                </button>

                <div class="flex space-x-3">
                  <button
                    @click="saveProgress"
                    :disabled="saving || currentQuestionIndex === null"
                    class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <i class="fas fa-save mr-2"></i>
                    {{ saving ? "Saving..." : "Save Progress" }}
                  </button>

                  <button
                    v-if="
                      currentQuestionIndex !== null &&
                      currentQuestionIndex ===
                        examinationData.questions.length - 1
                    "
                    @click="submitExamination"
                    :disabled="submitting"
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <i class="fas fa-check mr-2"></i>
                    {{ submitting ? "Submitting..." : "Submit Exam" }}
                  </button>

                  <button
                    v-else
                    @click="nextQuestion"
                    :disabled="currentQuestionIndex === null"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                  >
                    Next
                    <i class="fas fa-arrow-right ml-2"></i>
                  </button>
                </div>
              </div>
            </div>

            <div
              v-else
              class="border border-dashed border-gray-300 rounded-xl p-10 bg-gradient-to-b from-white to-gray-50"
            >
              <div class="text-center max-w-md mx-auto">
                <div
                  class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-50 mb-4"
                >
                  <i class="fas fa-hand-pointer text-indigo-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                  Select a question to begin
                </h3>
                <p class="text-sm text-gray-600">
                  Choose a number from the section navigator on the left. You
                  can start with any sub-category.
                </p>

                <div
                  v-if="examinationData?.exam_instruction"
                  class="mt-5 text-left"
                >
                  <div class="text-sm font-semibold text-gray-900 mb-2">
                    Instructions
                  </div>
                  <p class="text-sm text-gray-700 whitespace-pre-wrap">
                    {{ examinationData.exam_instruction }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Examination Complete Modal -->
      <div
        v-if="showCompletionModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
      >
        <div class="bg-white rounded-lg max-w-md w-full p-6">
          <div class="text-center">
            <div
              class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4"
            >
              <i class="fas fa-check text-green-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              Examination Submitted!
            </h3>
            <p class="text-gray-600 mb-6">
              Your examination has been submitted successfully. You will be
              notified when results are available.
            </p>
            <button
              @click="goToResults"
              class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              View Results
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { ApiService } from "@/services/api.js";
import { ElMessageBox, ElNotification } from "element-plus";

const router = useRouter();
const route = useRoute();

// Reactive data
const examinationData = ref(null);
const applicantExaminationId = ref(null); // Store applicant_examination_id for saving/submitting
const currentQuestionIndex = ref(null); // no default question selected
const selectedAnswers = ref({});
const textAnswers = ref({});
const loading = ref(false);
const error = ref(null);
const saving = ref(false);
const submitting = ref(false);
const showCompletionModal = ref(false);
const timeRemaining = ref(0);
const timerInterval = ref(null);
const initialExamDuration = ref(0); // Store initial exam duration in seconds

// Computed properties
const currentQuestion = computed(() => {
  const idx = currentQuestionIndex.value;
  if (idx === null || idx === undefined) return null;
  return examinationData.value?.questions?.[idx] || null;
});

const currentSectionProgress = computed(() => {
  const q = currentQuestion.value;
  const questions = examinationData.value?.questions || [];
  const idx = currentQuestionIndex.value;
  if (!q || idx === null || idx === undefined) return null;

  const key = getSubCategoryKey(q);
  const sectionIndexes = [];

  questions.forEach((question, index) => {
    if (getSubCategoryKey(question) === key) {
      sectionIndexes.push(index);
    }
  });

  const total = sectionIndexes.length;
  const pos = sectionIndexes.indexOf(idx);
  if (!total || pos === -1) return null;

  return { current: pos + 1, total, key };
});

const progressPercentage = computed(() => {
  if (!examinationData.value?.questions?.length) return 0;
  if (currentQuestionIndex.value === null) return 0;
  return (
    ((currentQuestionIndex.value + 1) /
      examinationData.value.questions.length) *
    100
  );
});

const toTitleCase = (value) => {
  const s = (value ?? "").toString().trim();
  if (!s) return "Other";
  return s
    .replace(/[_-]+/g, " ")
    .replace(/\s+/g, " ")
    .toLowerCase()
    .replace(/\b\w/g, (c) => c.toUpperCase());
};

const getSubCategoryKey = (question) => {
  return question?.sub_category || "Other";
};

const isEssayQuestion = (question) => {
  return getSubCategoryKey(question).toLowerCase() === "essay";
};

const isQuestionAnswered = (question) => {
  if (!question) return false;
  if (isEssayQuestion(question)) {
    return (textAnswers.value?.[question.id] || "").trim().length > 0;
  }
  return selectedAnswers.value?.[question.id] !== undefined;
};

const isSubCategoryComplete = (subCategoryKey) => {
  const questions = examinationData.value?.questions || [];
  const inSection = questions.filter(
    (q) => getSubCategoryKey(q) === subCategoryKey,
  );
  if (!inSection.length) return false;
  return inSection.every((q) => isQuestionAnswered(q));
};

const groupedQuestions = computed(() => {
  const questions = examinationData.value?.questions || [];
  const map = new Map();

  questions.forEach((question, index) => {
    const key = getSubCategoryKey(question);
    if (!map.has(key)) map.set(key, []);
    map.get(key).push({ question, index });
  });

  return Array.from(map, ([subCategory, items]) => {
    const answered = items.reduce((acc, item) => {
      return acc + (isQuestionAnswered(item.question) ? 1 : 0);
    }, 0);
    const total = items.length;
    const progressPct = total ? Math.round((answered / total) * 100) : 0;

    return {
      subCategory,
      label: toTitleCase(subCategory),
      items,
      answered,
      total,
      progressPct,
    };
  });
});

// Methods
const loadExamination = async () => {
  try {
    loading.value = true;
    error.value = null;

    const examinationId = route.params.id;
    if (!examinationId) {
      throw new Error("Examination ID not provided");
    }

    const response = await ApiService.getExamPage(examinationId);

    if (response.data && response.data.data) {
      const data = response.data.data;

      // Extract applicant_examination_id from exam data
      // The backend returns it in the exam array
      if (data.exam && data.exam.length > 0) {
        applicantExaminationId.value = data.exam[0].applicant_examination_id;

        // Transform questions and choices into the format expected by the UI
        const questions = data.exam_questions || [];
        const choices = data.exam_choices || [];

        // Group choices by question_id
        const choicesByQuestion = {};
        choices.forEach((choice) => {
          if (!choicesByQuestion[choice.question_id]) {
            choicesByQuestion[choice.question_id] = [];
          }
          choicesByQuestion[choice.question_id].push(choice.choice_details);
        });

        // Build questions array with options and choice IDs
        const choicesWithIdsByQuestion = {};
        choices.forEach((choice) => {
          if (!choicesWithIdsByQuestion[choice.question_id]) {
            choicesWithIdsByQuestion[choice.question_id] = [];
          }
          choicesWithIdsByQuestion[choice.question_id].push({
            id: choice.choice_id,
            text: choice.choice_details,
            image: choice.choice_image,
          });
        });

        const formattedQuestions = questions.map((q) => ({
          id: q.question_id,
          sub_category_id: q.sub_category_id,
          sub_category: q.sub_category,
          question: q.question,
          image_url: q.question_image,
          options: (choicesWithIdsByQuestion[q.question_id] || []).map(
            (c) => c.text,
          ),
          choiceIds: (choicesWithIdsByQuestion[q.question_id] || []).map(
            (c) => c.id,
          ),
        }));

        // Load saved answers from choices (selected_choice field)
        const savedAnswers = {};
        choices.forEach((choice) => {
          if (choice.selected_choice && choice.selected_choice !== 0) {
            // Find the index of the selected choice in the options array
            const questionChoices =
              choicesWithIdsByQuestion[choice.question_id] || [];
            const selectedIndex = questionChoices.findIndex(
              (c) => c.id === choice.selected_choice,
            );
            if (selectedIndex !== -1) {
              savedAnswers[choice.question_id] = selectedIndex;
            }
          }
        });

        // Initialize examinationData.value with all data including choiceIdsMap
        examinationData.value = {
          title: data.exam[0].exam_set || "Examination",
          exam_set: data.exam[0].exam_set,
          exam_instruction: data.exam[0].exam_instruction,
          exam_duration: data.exam[0].exam_duration,
          passing_criteria: data.exam[0].passing_criteria,
          category: data.exam[0].category,
          category_description: data.exam[0].description,
          questions: formattedQuestions,
          saved_answers: savedAnswers,
          choiceIdsMap: choicesWithIdsByQuestion, // Include choiceIdsMap here
        };

        // Initialize timer using exam_duration
        if (data.exam[0].exam_duration) {
          const examDurationInSeconds = data.exam[0].exam_duration * 60; // Convert minutes to seconds
          initialExamDuration.value = examDurationInSeconds;

          // If there's a saved last_duration, calculate remaining time
          if (data.exam[0].last_duration) {
            const timeSpentInSeconds = data.exam[0].last_duration * 60; // Convert minutes to seconds
            timeRemaining.value = Math.max(
              0,
              examDurationInSeconds - timeSpentInSeconds,
            );
          } else {
            timeRemaining.value = examDurationInSeconds;
          }

          startTimer();
        }

        // Load saved answers
        if (Object.keys(savedAnswers).length > 0) {
          selectedAnswers.value = savedAnswers;
        }

        // Load saved essay/text answers
        const savedTextAnswers = {};
        (data.exam_text_answers || []).forEach((row) => {
          if (row && row.question_id) {
            savedTextAnswers[row.question_id] = row.answer_text || "";
          }
        });
        if (Object.keys(savedTextAnswers).length > 0) {
          textAnswers.value = savedTextAnswers;
        }
      } else {
        throw new Error("No exam data found in response");
      }
    } else {
      throw new Error("No examination data received");
    }
  } catch (err) {
    console.error("Error loading examination:", err);
    const status = err?.response?.status;
    error.value = err.response?.data?.message || "Failed to load examination";

    if (status === 409) {
      ElNotification({
        title: "Already submitted",
        message:
          error.value ||
          "This examination was already submitted. Retakes are not permitted.",
        type: "info",
        duration: 6000,
        position: "top-right",
      });
      router.replace("/dashboard");
      return;
    }

    if (status === 404) {
      ElNotification({
        title: "External examination",
        message:
          "This exam is not hosted in the portal (likely third-party). Returning to dashboard.",
        type: "info",
        duration: 5500,
        position: "top-right",
      });
      router.replace("/dashboard");
      return;
    }

    ElNotification({
      title: "Load failed",
      message: error.value,
      type: "error",
      duration: 5000,
      position: "top-right",
    });
  } finally {
    loading.value = false;
  }
};

const startTimer = () => {
  timerInterval.value = setInterval(() => {
    timeRemaining.value--;

    if (timeRemaining.value <= 0) {
      clearInterval(timerInterval.value);
      submitExamination();
    }
  }, 1000);
};

const formatTime = (seconds) => {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, "0")}:${secs
      .toString()
      .padStart(2, "0")}`;
  }
  return `${minutes}:${secs.toString().padStart(2, "0")}`;
};

const saveAnswer = async () => {
  // Auto-save answer
  await saveProgress();
};

const saveProgress = async () => {
  try {
    saving.value = true;

    if (!applicantExaminationId.value || !examinationData.value) {
      console.error("Applicant examination ID or exam data not available");
      return;
    }

    // Transform answers to backend format
    const questionIds = [];
    const formattedAnswers = {};

    examinationData.value.questions.forEach((question) => {
      const qid = parseInt(question.id);
      questionIds.push(qid);

      if (isEssayQuestion(question)) {
        formattedAnswers[`text${qid}`] = (textAnswers.value?.[qid] || "").toString();
        return;
      }

      const answerIndex = selectedAnswers.value?.[qid];
      if (
        answerIndex !== undefined &&
        question.choiceIds &&
        question.choiceIds[answerIndex] !== undefined
      ) {
        const choiceId = question.choiceIds[answerIndex];
        formattedAnswers[`choices${qid}`] = {
          [qid]: choiceId,
        };
      }
    });

    const answers = {
      question_id: questionIds,
      ...formattedAnswers,
    };

    await ApiService.autoSaveExam(applicantExaminationId.value, answers);
  } catch (err) {
    console.error("Error saving progress:", err);
    if (err?.response?.status === 409) {
      if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
      }
      ElNotification({
        title: "Examination submitted",
        message:
          err.response?.data?.message ||
          "This exam was already submitted (possibly in another tab). Opening results.",
        type: "info",
        duration: 6000,
        position: "top-right",
      });
      const id = applicantExaminationId.value;
      router.replace(id ? `/exam-result/${id}` : "/dashboard");
    }
    // Don't show error to user for other auto-save failures
  } finally {
    saving.value = false;
  }
};

const submitExamination = async () => {
  try {
    submitting.value = true;

    if (!applicantExaminationId.value || !examinationData.value) {
      ElNotification({
        title: "Submission blocked",
        message:
          "Unable to submit: Examination ID not found. Please refresh and try again.",
        type: "warning",
        duration: 5000,
        position: "top-right",
      });
      return;
    }

    // Transform answers to backend format (same as saveProgress)
    const questionIds = [];
    const formattedAnswers = {};

    // Include all questions, even unanswered ones
    examinationData.value.questions.forEach((question) => {
      const qid = parseInt(question.id);
      questionIds.push(qid);

      if (isEssayQuestion(question)) {
        formattedAnswers[`text${qid}`] = (textAnswers.value?.[qid] || "").toString();
        return;
      }

      const answerIndex = selectedAnswers.value[qid];

      if (
        answerIndex !== undefined &&
        question.choiceIds &&
        question.choiceIds[answerIndex] !== undefined
      ) {
        const choiceId = question.choiceIds[answerIndex];
        formattedAnswers[`choices${qid}`] = {
          [qid]: choiceId,
        };
      }
      // If unanswered, the backend will handle it as unanswered
    });

    // Calculate time spent in minutes
    const timeSpentInSeconds = initialExamDuration.value - timeRemaining.value;
    const timeSpentInMinutes =
      timeSpentInSeconds > 0
        ? Math.round((timeSpentInSeconds / 60) * 100) / 100 // Round to 2 decimal places
        : 0;

    const answers = {
      question_id: questionIds,
      ...formattedAnswers,
      submitted_at: new Date().toISOString(),
      last_duration: timeSpentInMinutes, // Send time spent in minutes
    };

    const response = await ApiService.submitExam(
      applicantExaminationId.value,
      answers,
    );

    if (response.data.success) {
      showCompletionModal.value = true;
      clearInterval(timerInterval.value);
    }
  } catch (err) {
    console.error("Error submitting examination:", err);
    const status = err?.response?.status;
    const msg = err?.response?.data?.message;
    if (status === 409) {
      if (timerInterval.value) {
        clearInterval(timerInterval.value);
        timerInterval.value = null;
      }
      ElNotification({
        title: "Already submitted",
        message:
          msg ||
          "This examination was already submitted. Retakes are not permitted.",
        type: "info",
        duration: 6000,
        position: "top-right",
      });
      const id = applicantExaminationId.value;
      router.replace(id ? `/exam-result/${id}` : "/dashboard");
      return;
    }
    ElNotification({
      title: "Submission failed",
      message: msg || "Failed to submit examination. Please try again.",
      type: "error",
      duration: 5000,
      position: "top-right",
    });
  } finally {
    submitting.value = false;
  }
};

const nextQuestion = () => {
  if (currentQuestionIndex.value === null) return;
  const questions = examinationData.value?.questions || [];
  const currentIndex = currentQuestionIndex.value;
  const nextIndex = currentIndex + 1;

  if (nextIndex >= questions.length) return;

  const currentQ = questions[currentIndex];
  const nextQ = questions[nextIndex];

  const currentKey = getSubCategoryKey(currentQ);
  const nextKey = getSubCategoryKey(nextQ);
  const crossingToNextSection = currentKey !== nextKey;

  if (crossingToNextSection && isSubCategoryComplete(currentKey)) {
    const fromLabel = toTitleCase(currentKey);
    const toLabel = toTitleCase(nextKey);

    ElMessageBox.confirm(
      `${fromLabel} category is complete. Proceed to ${toLabel}?`,
      "Proceed to next category",
      {
        confirmButtonText: "Proceed",
        cancelButtonText: "Stay",
        type: "info",
        closeOnClickModal: false,
        closeOnPressEscape: true,
      },
    )
      .then(() => {
        currentQuestionIndex.value = nextIndex;
      })
      .catch(() => {
        // user chose to stay
      });

    return;
  }

  currentQuestionIndex.value = nextIndex;
};

const previousQuestion = () => {
  if (currentQuestionIndex.value === null) return;
  if (currentQuestionIndex.value > 0) {
    currentQuestionIndex.value--;
  }
};

const goToQuestion = (index) => {
  if (index >= 0 && index < examinationData.value.questions.length) {
    currentQuestionIndex.value = index;
  }
};

const getQuestionButtonClass = (index) => {
  const isCurrent = index === currentQuestionIndex.value;
  const isAnswered = isQuestionAnswered(examinationData.value.questions[index]);

  if (isCurrent) {
    return "bg-indigo-600 text-white shadow-sm";
  } else if (isAnswered) {
    return "bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100";
  } else {
    return "bg-white text-gray-700 border border-gray-200 hover:bg-gray-50";
  }
};

const goToResults = () => {
  if (applicantExaminationId.value) {
    router.push(`/exam-result/${applicantExaminationId.value}`);
  } else {
    router.push("/dashboard");
  }
};

// Lifecycle
onMounted(async () => {
  await loadExamination();
});

onUnmounted(() => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value);
  }
});
</script>

<style scoped>
/* Custom styles for examination interface */
</style>
