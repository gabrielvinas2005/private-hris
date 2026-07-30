<template>
  <div class="pds-recognitions">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Other Information</h3>

    <div class="space-y-6">
      <!-- Recognitions Section -->
      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div
          class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200 rounded-t-lg"
        >
          <div class="flex justify-between items-center">
            <h4
              class="text-lg font-semibold text-gray-900 inline-flex items-center"
            >
              <Award :size="20" class="mr-2 text-indigo-600" />
              Awards/Recognitions Received
            </h4>
            <button
              @click="addRecognition"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center gap-2"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4"
                ></path>
              </svg>
              Add Recognition
            </button>
          </div>
        </div>

        <div class="p-6">
          <div
            v-if="loading && recognitionData.length === 0"
            class="text-center py-8 text-gray-500"
          >
            <div
              class="inline-flex items-center justify-center w-12 h-12 mb-4 bg-blue-100 rounded-full"
            >
              <div
                class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"
              ></div>
            </div>
            <p>Loading recognition records...</p>
          </div>

          <div
            v-else-if="recognitionData.length === 0"
            class="text-center py-8 text-gray-500"
          >
            <svg
              class="w-12 h-12 mx-auto mb-4 text-gray-300"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014.846 21H9.154a3.374 3.374 0 00-2.039-.764l-.547-.547z"
              ></path>
            </svg>
            <p>
              No recognitions added yet. Click "Add Recognition" to get started.
            </p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="(recognition, index) in recognitionData"
              :key="recognition.recognition_id || recognition.id"
              class="bg-gray-50 rounded-lg p-4 border border-gray-200"
            >
              <div class="flex justify-between items-start mb-3">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center"
                  >
                    <span class="text-yellow-600 font-semibold text-sm">{{
                      index + 1
                    }}</span>
                  </div>
                  <h5 class="text-sm font-medium text-gray-700">
                    Recognition {{ index + 1 }}
                  </h5>
                </div>
                <button
                  @click="removeRecognition(index)"
                  class="text-red-600 hover:text-red-800 transition-colors duration-200"
                  title="Remove recognition"
                >
                  <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    ></path>
                  </svg>
                </button>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1"
                  >Award/Recognition Details</label
                >
                <textarea
                  v-model="recognition.details"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Enter award title, organization/institution that gave the award, date received, and any other relevant details..."
                ></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          @click="saveRecognitions"
          :disabled="loading || !isFormValid"
          class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-2 rounded-md font-medium transition-colors duration-200 flex items-center gap-2"
        >
          <svg
            v-if="loading"
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
            ></path>
          </svg>
          {{ loading ? "Saving..." : "Save Recognition Information" }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from "vue";
import { ElNotification, ElMessageBox } from "element-plus";
import { ApiService } from "@/services/api.js";
import { useAppNotification } from "@/composables/useAppNotification.js";
import { Award } from "lucide-vue-next";

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});

const notify = useAppNotification();

const recognitionData = reactive([]);
const referenceData = reactive([]);
const additionalInfo = reactive({
  specialSkills: "",
  memberships: "",
});

const loading = ref(false);
const error = ref(null);
const employeeId = ref(null);
let nextRecognitionId = 1;

const isFormValid = computed(() => {
  // For recognitions, all fields with details should be valid
  if (recognitionData.length === 0) return true; // Allow saving empty list

  return recognitionData.every(
    (recognition) => recognition.details && recognition.details.trim() !== ""
  );
});

const loadRecognitionsData = async () => {
  loading.value = true;
  error.value = null;

  try {
    // Fetch data from API first
    const response = await ApiService.getApplicantPage();

    if (response.data && response.data.data) {
      const data = response.data.data;

      // Capture employee id from API response (this is the correct source)
      employeeId.value =
        data.employee_info?.[0]?.id ??
        props.info?.employee_info?.[0]?.id ??
        props.info?.employee_id ??
        props.info?.id ??
        null;

      // Clear existing data
      recognitionData.splice(0, recognitionData.length);

      // Load recognitions data
      if (data.recognitions && Array.isArray(data.recognitions)) {
        data.recognitions.forEach((recognition) => {
          recognitionData.push({
            id: nextRecognitionId++,
            recognition_id: recognition.recognation_id || null,
            details: recognition.recognation || "",
          });
        });
      }
    }
  } catch (err) {
    console.error("Error loading recognition data:", err);
    error.value =
      err.response?.data?.message || "Failed to load recognition data";
  } finally {
    loading.value = false;
  }
};

const addRecognition = () => {
  recognitionData.push({
    id: nextRecognitionId++,
    recognition_id: null,
    details: "",
  });
};

const removeRecognition = async (index) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to remove this recognition?",
      "Remove Recognition",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
  } catch {
    return;
  }

  const recognition = recognitionData[index];
  const recognitionId = recognition.recognition_id;

  // Remove from UI immediately for better UX
  recognitionData.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Recognition removed successfully!",
    type: "success",
    duration: 2000,
    position: "top-right",
  });

  // If it's an existing record, delete from backend (type_id 8 for recognitions)
  if (recognitionId) {
    try {
      await ApiService.destroyPDS(8, recognitionId);
    } catch (err) {
      const status = err.response?.status;

      if (status && status >= 400 && status < 500) {
        recognitionData.splice(index, 0, recognition);
        ElNotification({
          title: "Error",
          message: "Failed to delete recognition from server. Please try again.",
          type: "error",
          duration: 5000,
          position: "top-right",
        });
      } else if (status && status >= 500) {
        console.error("Server error during deletion:", err);
      } else {
        console.warn("Unexpected response format during deletion:", err);
      }
    }
  }
};

const addReference = () => {
  referenceData.push({
    name: "",
    address: "",
    contactNumber: "",
  });
};

const removeReference = async (index) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to remove this reference?",
      "Remove Reference",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
  } catch {
    return;
  }
  referenceData.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Reference removed successfully!",
    type: "success",
    duration: 2000,
    position: "top-right",
  });
};

const saveRecognitions = async () => {
  if (!isFormValid.value) {
    notify.error("Validation", "Please fill in all recognition details before saving.", {
      duration: 4000,
    });
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    // Ensure we have the correct employee ID
    const resolvedEmployeeId =
      employeeId.value ??
      props.info?.employee_info?.[0]?.id ??
      props.info?.employee_id ??
      props.info?.id ??
      null;

    if (!resolvedEmployeeId) {
      throw new Error("Employee information not found");
    }

    // Prepare recognitions data for API
    const formData = new FormData();
    formData.append("section", "recognitions");

    // Send recognitions as JSON string in form data (backend will decode)
    const recognitionsPayload = recognitionData.map((recognition) => ({
      recognition_id: recognition.recognition_id || null,
      details: recognition.details || "",
    }));

    formData.append("recognitions", JSON.stringify(recognitionsPayload));

    const response = await ApiService.storePDS(resolvedEmployeeId, formData);

    notify.success("Saved", "Recognition information saved successfully!", {
      duration: 4000,
    });
    await loadRecognitionsData();
  } catch (err) {
    console.error("Error saving recognition data:", err);
    const msg =
      err.response?.data?.message ||
      (err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(" ")
        : null) ||
      err.message ||
      "Failed to save recognition information. Please try again.";
    notify.error("Save failed", msg, { duration: 6000 });
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await loadRecognitionsData();
});
</script>

<style scoped>
/* Custom styles for better visual hierarchy */
.recognition-card {
  transition: all 0.2s ease-in-out;
}

.recognition-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Textarea styling */
textarea {
  resize: vertical;
  min-height: 2.5rem;
}

/* Custom styling for different sections */
.special-skills-section {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}

.membership-section {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.references-section {
  background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
}
</style>
