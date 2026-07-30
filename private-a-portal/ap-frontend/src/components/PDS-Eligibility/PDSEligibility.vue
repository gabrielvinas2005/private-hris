<template>
  <div class="pds-eligibility">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Eligibility</h3>

    <div class="space-y-6">
      <!-- Eligibility Entries Section -->
      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div
          class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200 rounded-t-lg"
        >
          <div class="flex justify-between items-center">
            <h4
              class="text-lg font-semibold text-gray-900 inline-flex items-center"
            >
              <ClipboardCheck :size="20" class="mr-2 text-indigo-600" /> Civil
              Service Eligibility
            </h4>
            <button
              @click="addEligibility"
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
              Add Eligibility
            </button>
          </div>
        </div>

        <div class="p-6">
          <div
            v-if="eligibilityData.length === 0"
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
                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
              ></path>
            </svg>
            <p>
              No eligibility records added yet. Click "Add Eligibility" to get
              started.
            </p>
          </div>

          <div v-else class="space-y-6">
            <div
              v-for="(eligibility, index) in eligibilityData"
              :key="eligibility.id"
              class="bg-gray-50 rounded-lg p-6 border border-gray-200"
            >
              <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center"
                  >
                    <span class="text-purple-600 font-semibold text-sm">{{
                      index + 1
                    }}</span>
                  </div>
                  <h5 class="text-lg font-medium text-gray-800">
                    {{
                      getEligibilityLabel(eligibility.eligibilityId) ||
                      "Eligibility Record"
                    }}
                  </h5>
                </div>
                <button
                  @click="removeEligibility(index)"
                  class="text-red-600 hover:text-red-800 transition-colors duration-200 p-1 rounded-full hover:bg-red-50"
                  title="Remove eligibility record"
                >
                  <svg
                    class="w-5 h-5"
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

              <!-- Examination Type -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Career Service/RA 1080 (Board/Bar) Under Special
                  Laws/CES/CSEE *</label
                >
                <div class="relative">
                  <select
                    v-model.number="eligibility.eligibilityId"
                    class="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                    required
                  >
                    <option :value="0">Select Examination</option>
                    <option
                      v-for="option in eligibilityOptions"
                      :key="option.id"
                      :value="option.id"
                    >
                      {{ option.name }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Rating and Examination Date -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Rating *</label
                  >
                  <div class="relative">
                    <input
                      v-model.number="eligibility.rating"
                      type="number"
                      step="0.01"
                      min="0"
                      max="100"
                      class="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="85.50"
                      required
                    />
                    <span class="absolute right-3 top-2 text-xs text-gray-400"
                      >%</span
                    >
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Date of Examination/Conferment *</label
                  >
                  <input
                    v-model="eligibility.examinationDate"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                  />
                </div>
              </div>

              <!-- Place of Examination -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Place of Examination/Conferment *</label
                >
                <input
                  v-model="eligibility.placeOfExam"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., Manila, Cebu City, Davao City"
                  required
                />
              </div>

              <!-- License Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >License Number</label
                  >
                  <input
                    v-model="eligibility.licenseNumber"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g., 12345678 (if applicable)"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Date of Release/Validity</label
                  >
                  <input
                    v-model="eligibility.dateReleased"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          @click="saveEligibility"
          :disabled="!isFormValid"
          class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-2 rounded-md font-medium transition-colors duration-200"
        >
          Save Eligibility Information
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref, onMounted } from "vue";
import { ElNotification, ElMessageBox } from "element-plus";
import { ApiService } from "@/services/api.js";
import { useAppNotification } from "@/composables/useAppNotification.js";
import { ClipboardCheck } from "lucide-vue-next";
const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(["saved"]);

const notify = useAppNotification();

const eligibilityData = reactive([]);
const loading = ref(false);
const error = ref(null);
const employeeId = ref(null);
const eligibilityOptions = ref([]);

const addEligibility = () => {
  eligibilityData.push({
    key: generateKey("new"),
    id: null,
    eligibilityId: 0,
    rating: "",
    examinationDate: "",
    placeOfExam: "",
    licenseNumber: "",
    dateReleased: "",
  });
};

const loadEligibilityData = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await ApiService.getApplicantPage();

    if (response.data && response.data.data) {
      const data = response.data.data;

      employeeId.value =
        data.employee_info?.[0]?.id ??
        data.employee_id ??
        props.info?.employee_info?.[0]?.id ??
        props.info?.employee_id ??
        props.info?.id ??
        null;

      // populate eligibility dropdown options if available
      if (Array.isArray(data.eligibilities)) {
        eligibilityOptions.value = data.eligibilities.map((e) => ({
          id: e.id ?? e.eligibility_id ?? 0,
          name: e.eligibility ?? e.name ?? "",
        }));
      }

      if (data.examinations && Array.isArray(data.examinations)) {
        eligibilityData.splice(0, eligibilityData.length);

        data.examinations.forEach((exam) => {
          eligibilityData.push({
            key: exam.examination_id
              ? `existing-${exam.examination_id}`
              : generateKey("existing"),
            id: exam.examination_id || null,
            eligibilityId: exam.eligibility_id ?? 0,
            rating: exam.exam_rating ?? "",
            examinationDate: formatDate(exam.exam_date),
            placeOfExam: exam.place_of_exam ?? "",
            licenseNumber: exam.license_number ?? "",
            dateReleased: formatDate(exam.date_released),
          });
        });
      }
    }
  } catch (err) {
    console.error("Error loading eligibility data:", err);
    error.value =
      err.response?.data?.message || "Failed to load eligibility data";
  } finally {
    loading.value = false;
  }
};

const removeEligibility = async (index) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to remove this eligibility record?",
      "Remove Eligibility",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
  } catch {
    return;
  }

  const eligibility = eligibilityData[index];

  if (eligibility.id) {
    try {
      // type_id 5 -> employee_examinations (eligibility)
      await ApiService.destroyPDS(5, eligibility.id);
    } catch (err) {
      console.error("Error deleting eligibility record:", err);
      ElNotification({
        title: "Error",
        message: "Failed to delete eligibility record from server",
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      return;
    }
  }

  eligibilityData.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Eligibility record removed successfully!",
    type: "success",
    duration: 2000,
    position: "top-right",
  });
};

const isFormValid = computed(() => {
  if (eligibilityData.length === 0) return false;

  return eligibilityData.every(
    (eligibility) =>
      (eligibility.rating === 0 || !!eligibility.rating) &&
      eligibility.examinationDate &&
      eligibility.placeOfExam
  );
});

const saveEligibility = async () => {
  if (!isFormValid.value) {
    notify.error(
      "Validation",
      "Please fill in all required fields (Rating, Date, and Place of Examination)",
      { duration: 4000 },
    );
    return;
  }

  const invalidRatings = eligibilityData.filter(
    (eligibility) =>
      eligibility.rating && (eligibility.rating < 0 || eligibility.rating > 100)
  );

  if (invalidRatings.length > 0) {
    notify.error("Validation", "Rating must be between 0 and 100", {
      duration: 4000,
    });
    return;
  }

  try {
    loading.value = true;

    const resolvedEmployeeId =
      employeeId.value ??
      props.info?.employee_info?.[0]?.id ??
      props.info?.employee_id ??
      props.info?.id ??
      null;

    if (!resolvedEmployeeId) {
      throw new Error("Employee information not found");
    }

    for (const eligibility of eligibilityData) {
      const formData = new FormData();

      formData.append("section", "eligibility");
      const eligibilityId = Number(eligibility.eligibilityId) || 0;
      formData.append("eligibility_id", eligibilityId);
      formData.append("exam_rating", eligibility.rating ?? "");
      formData.append("exam_date", eligibility.examinationDate || "");
      formData.append("place_of_exam", eligibility.placeOfExam || "");
      formData.append("license_number", eligibility.licenseNumber || "");
      formData.append("date_released", eligibility.dateReleased || "");

      if (eligibility.id) {
        formData.append("examination_id", eligibility.id);
      }

      await ApiService.storePDS(resolvedEmployeeId, formData);
    }

    notify.success("Saved", "Eligibility information saved successfully!", {
      duration: 4000,
    });
    await loadEligibilityData();
    emit("saved");
  } catch (err) {
    console.error("Error saving eligibility information:", err);
    const msg =
      err.response?.data?.message ||
      (err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(" ")
        : null) ||
      err.message ||
      "Failed to save eligibility information. Please try again.";
    notify.error("Save failed", msg, { duration: 6000 });
  } finally {
    loading.value = false;
  }
};

const formatDate = (value) => {
  if (!value) return "";
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
  if (/^\d{4}$/.test(value)) return `${value}-01-01`;
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return "";
  return parsed.toISOString().slice(0, 10);
};

const generateKey = (prefix = "key") => {
  if (typeof crypto !== "undefined" && crypto.randomUUID) {
    return `${prefix}-${crypto.randomUUID()}`;
  }
  return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
};

const getEligibilityLabel = (id) => {
  const opt = eligibilityOptions.value.find((o) => Number(o.id) === Number(id));
  return opt ? opt.name : "";
};

// Initialize and load existing records
onMounted(() => {
  loadEligibilityData();
  // if (eligibilityData.length === 0) {
  //   addEligibility();
  // }
});
</script>

<style scoped>
/* Custom styles for better visual hierarchy */
.eligibility-card {
  transition: all 0.2s ease-in-out;
}

.eligibility-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Number input styling */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  appearance: none;
  -webkit-appearance: none;
  margin: 0;
}

input[type="number"] {
  appearance: textfield;
  -moz-appearance: textfield;
}

/* Select dropdown styling */
select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  padding-right: 2.5rem;
}

/* Optgroup styling */
optgroup {
  font-weight: bold;
  color: #374151;
}

optgroup option {
  font-weight: normal;
  padding-left: 1rem;
}

/* Rating input with percentage indicator */
.rating-input {
  position: relative;
}

.rating-input::after {
  content: "%";
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 0.75rem;
}
</style>
