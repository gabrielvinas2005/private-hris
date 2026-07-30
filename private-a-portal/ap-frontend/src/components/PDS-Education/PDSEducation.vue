<template>
  <div
    class="pds-education min-h-screen px-4 py-8 bg-gradient-to-br from-gray-50 to-gray-100"
  >
    <div class="max-w-7xl mx-auto">
      <!-- Page Header -->
      <div
        class="mb-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6"
      >
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-1">
              Educational Background
            </h1>
            <p class="text-sm text-gray-500">
              Add and manage your academic history and achievements
            </p>
          </div>
          <div class="flex items-center gap-3">
            <button
              @click="saveEducationData"
              :disabled="loading || !isFormValid"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors"
            >
              <svg
                class="w-4 h-4 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"
                ></path>
              </svg>
              Save Changes
            </button>
            <button
              @click="addEducation"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors"
            >
              <svg
                class="w-4 h-4 mr-2"
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
              Add Education
            </button>
          </div>
        </div>
      </div>

      <!-- Education Records Section -->
      <div
        class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden"
      >
        <div
          class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200"
        >
          <h2 class="text-lg font-semibold text-gray-900 flex items-center">
            <svg
              class="w-5 h-5 mr-2 text-indigo-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"
              ></path>
            </svg>
            Educational Records
          </h2>
        </div>

        <div class="p-6">
          <!-- Empty State -->
          <div v-if="educationData.length === 0" class="text-center py-16">
            <div
              class="inline-flex items-center justify-center w-20 h-20 mb-6 bg-indigo-100 rounded-full"
            >
              <svg
                class="w-10 h-10 text-indigo-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"
                ></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              No educational records yet
            </h3>
            <p class="text-gray-500 mb-6">
              Click "Add Education" to get started with your academic history
            </p>
            <button
              @click="addEducation"
              class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm transition-colors"
            >
              <svg
                class="w-5 h-5 mr-2"
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
              Add Your First Education
            </button>
          </div>

          <!-- Education List -->
          <div v-else class="space-y-6">
            <div
              v-for="(education, index) in educationData"
              :key="education.key"
              class="bg-gradient-to-br from-gray-50 to-white rounded-xl p-6 border-2 border-gray-200 hover:border-indigo-300 transition-all duration-200 shadow-sm hover:shadow-md"
            >
              <!-- Header -->
              <div class="flex justify-between items-start mb-6">
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md"
                  >
                    <span class="text-white font-bold text-sm">{{
                      index + 1
                    }}</span>
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                      {{
                        getAcademicLevelLabel(education.academicLevelId) ||
                        "New Educational Record"
                      }}
                    </h3>
                    <p
                      v-if="education.schoolName"
                      class="text-sm text-gray-500"
                    >
                      {{ education.schoolName }}
                    </p>
                  </div>
                </div>
                <button
                  @click="removeEducation(index)"
                  class="text-red-600 hover:text-red-800 hover:bg-red-50 p-2 rounded-lg transition-colors"
                  title="Remove education record"
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
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    ></path>
                  </svg>
                </button>
              </div>

              <!-- Form Fields -->
              <div class="space-y-5">
                <!-- Academic Level -->
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Academic Level <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model.number="education.academicLevelId"
                    class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                    required
                  >
                    <option value="0">Select Academic Level</option>
                    <option
                      v-for="level in academicLevels"
                      :key="level.id"
                      :value="level.id"
                    >
                      {{ level.label }}
                    </option>
                  </select>
                </div>

                <!-- School Name and Program -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label
                      class="block text-sm font-semibold text-gray-700 mb-2"
                    >
                      School Name <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="education.schoolName"
                      type="text"
                      class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                      placeholder="Enter School/Institution name"
                      required
                    />
                  </div>
                  <!-- Hide Program/Course when academic level is Elementary (1) or Secondary/High School (2) -->
                  <div
                    v-if="
                      Number(education.academicLevelId) !== 1 &&
                      Number(education.academicLevelId) !== 2
                    "
                  >
                    <label
                      class="block text-sm font-semibold text-gray-700 mb-2"
                    >
                      Program/Course
                    </label>
                    <input
                      v-model="education.program"
                      type="text"
                      class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                      placeholder="Degree, Course, or Program"
                    />
                  </div>
                </div>

                <!-- Period of Attendance -->
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-3">
                    Period of Attendance <span class="text-red-500">*</span>
                  </label>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <label
                        class="block text-xs font-medium text-gray-600 mb-2"
                      >
                        From <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="education.attendanceFrom"
                        type="date"
                        class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        required
                      />
                    </div>
                    <div>
                      <label
                        class="block text-xs font-medium text-gray-600 mb-2"
                      >
                        To
                      </label>
                      <input
                        v-model="education.attendanceTo"
                        type="date"
                        :disabled="education.isPresent"
                        class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors disabled:bg-gray-100 disabled:cursor-not-allowed"
                      />
                    </div>
                  <!-- Hide Currently Enrolled when academic level is Elementary (1) or Secondary/High School (2) -->
                  <div
                    class="flex items-end"
                    v-if="
                      Number(education.academicLevelId) !== 1 &&
                      Number(education.academicLevelId) !== 2
                    "
                  >
                    <label
                      class="flex items-center gap-3 px-4 py-3 bg-indigo-50 rounded-lg cursor-pointer hover:bg-indigo-100 transition-colors w-full"
                    >
                      <input
                        v-model="education.isPresent"
                        type="checkbox"
                        @change="handlePresentChange(education)"
                        class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 focus:ring-2"
                      />
                      <span class="text-sm font-medium text-gray-700"
                        >Currently Enrolled</span
                      >
                    </label>
                  </div>
                  </div>
                </div>

                <!-- Year Graduated and Units Earned -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label
                      class="block text-sm font-semibold text-gray-700 mb-2"
                    >
                      Year Graduated <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model.number="education.yearGraduated"
                      type="number"
                      min="1900"
                      :max="maxGraduationYear"
                      class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                      placeholder="YYYY"
                    />
                  </div>
                  <!-- Hide Units Earned when academic level is Elementary (1) or Secondary/High School (2) -->
                  <div
                    v-if="
                      Number(education.academicLevelId) !== 1 &&
                      Number(education.academicLevelId) !== 2
                    "
                  >
                    <label
                      class="block text-sm font-semibold text-gray-700 mb-2"
                    >
                      Units Earned <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <input
                        v-model.number="education.unitsEarned"
                        type="number"
                        step="0.5"
                        min="0"
                        class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        placeholder="Enter units (if undergraduate)"
                      />
                      <span
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400"
                        >units</span
                      >
                    </div>
                  </div>
                </div>

                <!-- Honors/Awards -->
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Honors/Awards
                  </label>
                  <textarea
                    v-model="education.honors"
                    rows="3"
                    class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none"
                    placeholder="Academic honors, awards, or recognitions received (e.g., Cum Laude, Dean's List, Scholarship Recipient)"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Save Button -->
      <div v-if="educationData.length > 0" class="mt-6 flex justify-end">
        <button
          @click="saveEducationInfo"
          :disabled="loading || !isFormValid"
          class="inline-flex items-center px-8 py-3 text-base font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-400 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transition-all duration-200"
        >
          <svg
            class="w-5 h-5 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 13l4 4L19 7"
            ></path>
          </svg>
          Save Educational Background
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted, computed } from "vue";
import { ElNotification, ElMessageBox } from "element-plus";
import { ApiService } from "@/services/api.js";
import { useAppNotification } from "@/composables/useAppNotification.js";

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(["saved"]);

const notify = useAppNotification();

const academicLevels = [
  { id: 1, label: "Elementary" },
  { id: 2, label: "Secondary (High School)" },
  { id: 3, label: "Senior High School" },
  { id: 4, label: "Vocational/Technical" },
  { id: 5, label: "College/University" },
  { id: 6, label: "Graduate Studies (Masters)" },
  { id: 7, label: "Doctoral Studies (PhD)" },
  { id: 8, label: "Post-Graduate" },
];

const educationData = reactive([]);
const loading = ref(false);
const error = ref(null);
const employeeId = ref(null);

const loadEducationData = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await ApiService.getApplicantPage();

    if (response.data && response.data.data) {
      const data = response.data.data;

      // Capture employee id from payload or fallback to prop
      employeeId.value =
        data.employee_info?.[0]?.id ??
        props.info?.employee_info?.[0]?.id ??
        props.info?.employee_id ??
        props.info?.id ??
        null;

      if (data.educations && Array.isArray(data.educations)) {
        educationData.splice(0, educationData.length);

        data.educations.forEach((edu) => {
          educationData.push({
            key: edu.education_id
              ? `existing-${edu.education_id}`
              : generateKey("existing"),
            id: edu.education_id || null,
            academicLevelId: edu.academic_level_id || 0,
            schoolName: edu.school_name || "",
            program: edu.program || "",
            attendanceFrom: formatYearToDate(edu.from),
            attendanceTo: formatYearToDate(edu.to),
            isPresent: false,
            yearGraduated: edu.graduated_year || "",
            unitsEarned: edu.units_earned || "",
            honors: edu.honors || "",
          });
        });
      }
    }
  } catch (err) {
    console.error("Error loading education data:", err);
    const isTimeout =
      err.code === "ECONNABORTED" || err.message?.toLowerCase().includes("timeout");
    error.value = isTimeout
      ? "Request timed out. The server may be slow—please try again."
      : err.response?.data?.message || "Failed to load education data";
  } finally {
    loading.value = false;
  }
};

const saveEducationData = async () => {
  if (!isFormValid.value) {
    notify.error(
      "Validation",
      "Please fill in all required fields (Academic Level, School Name, and Attendance From date)",
      { duration: 4000 },
    );
    return;
  }

  try {
    loading.value = true;

    // Ensure we have the correct employee ID (not applicant header id)
    const resolvedEmployeeId =
      employeeId.value ??
      props.info?.employee_info?.[0]?.id ??
      props.info?.employee_id ??
      props.info?.id ??
      null;

    if (!resolvedEmployeeId) {
      throw new Error("Employee information not found");
    }

    for (const edu of educationData) {
      const formData = new FormData();

      formData.append("section", "education");
      formData.append("academic_level_id", edu.academicLevelId || 0);
      formData.append(
        "level",
        getAcademicLevelLabel(edu.academicLevelId) || ""
      ); // fallback for older backend fields
      formData.append("school_name", edu.schoolName || "");
      formData.append("course", edu.program || "");
      formData.append("start_date", formatDateToYear(edu.attendanceFrom));
      formData.append("end_date", formatDateToYear(edu.attendanceTo));
      formData.append("honors", edu.honors || "");
      formData.append("units_earned", edu.unitsEarned || "");
      formData.append("year_graduated", formatDateToYear(edu.yearGraduated));
      formData.append("is_currently_enrolled", edu.isPresent ? "1" : "0");

      if (edu.id) {
        formData.append("education_id", edu.id);
      }

      await ApiService.storePDS(resolvedEmployeeId, formData);
    }

    notify.success("Saved", "Education data saved successfully!", {
      duration: 4000,
    });
    await loadEducationData();
    emit("saved");
  } catch (err) {
    console.error("Error saving education data:", err);
    const isTimeout =
      err.code === "ECONNABORTED" || err.message?.toLowerCase().includes("timeout");
    const msg = isTimeout
      ? "Request timed out. The server may be slow—please try again."
      : err.response?.data?.message ||
        (err.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(" ")
          : null) ||
        err.message ||
        "Failed to save education data. Please try again.";
    notify.error("Save failed", msg, { duration: 6000 });
  } finally {
    loading.value = false;
  }
};

const addEducation = () => {
  educationData.push({
    key: generateKey("new"),
    id: null,
    academicLevelId: 0,
    schoolName: "",
    program: "",
    attendanceFrom: "",
    attendanceTo: "",
    isPresent: false,
    yearGraduated: "",
    unitsEarned: "",
    honors: "",
  });
};

const removeEducation = async (index) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to remove this educational record?",
      "Remove Education",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
  } catch {
    return;
  }

  const education = educationData[index];

  if (education.id) {
    try {
      await ApiService.destroyPDS(2, education.id);
    } catch (err) {
      console.error("Error deleting education record:", err);
      ElNotification({
        title: "Error",
        message: "Failed to delete education record from server",
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      return;
    }
  }

  educationData.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Education record removed successfully!",
    type: "success",
    duration: 2000,
    position: "top-right",
  });
};

onMounted(() => {
  loadEducationData();
});

const handlePresentChange = (education) => {
  if (education.isPresent) {
    education.attendanceTo = "";
  }
};

const isFormValid = computed(() => {
  if (educationData.length === 0) return false;

  return educationData.every(
    (education) =>
      education.academicLevelId &&
      education.schoolName &&
      education.attendanceFrom
  );
});

const saveEducationInfo = () => {
  saveEducationData();
};

const maxGraduationYear = new Date().getFullYear() + 10;

const getAcademicLevelLabel = (id) => {
  const level = academicLevels.find((lvl) => lvl.id === Number(id));
  return level ? level.label : "";
};

const generateKey = (prefix = "key") => {
  if (typeof crypto !== "undefined" && crypto.randomUUID) {
    return `${prefix}-${crypto.randomUUID()}`;
  }
  return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
};

const formatYearToDate = (year) => {
  if (!year) return "";
  return `${year}-01-01`;
};

const formatDateToYear = (value) => {
  if (!value) return "";
  // Handle date strings and numeric year inputs
  if (typeof value === "number") return value;
  if (/^\d{4}$/.test(value)) return value;
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return "";
  return String(parsed.getFullYear());
};
</script>

<style scoped>
input[type="checkbox"]:checked {
  background-color: #4f46e5;
  border-color: #4f46e5;
}

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
</style>
