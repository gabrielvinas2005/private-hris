<template>
  <div class="bg-white rounded-xl shadow-sm border">
    <!-- Header -->
    <div class="p-6 border-b border-gray-200">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">
            Available Positions
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            Browse and apply for open positions
          </p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
          <button
            @click="$emit('back-to-applications')"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
          >
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Applications
          </button>
          <button
            @click="refreshPositions"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
          >
            <i
              class="fas fa-sync-alt mr-2"
              :class="{ 'animate-spin': loading }"
            ></i>
            Refresh
          </button>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="p-6 border-b border-gray-200 bg-gray-50">
      <div
        class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-4"
      >
        <!-- Department Filter -->
        <select
          v-model="filterDepartment"
          class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="all">All Departments</option>
          <option v-for="dept in departments" :key="dept" :value="dept">
            {{ dept }}
          </option>
        </select>

        <!-- Employment Type Filter -->
        <select
          v-model="filterType"
          class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="all">All Types</option>
          <option value="plantilla">Plantilla</option>
          <option value="non-plantilla">Non-Plantilla</option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="p-12 text-center">
      <div
        class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"
      ></div>
      <p class="mt-4 text-gray-600">Loading positions...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="p-12 text-center">
      <div
        class="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md mx-auto"
      >
        <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
        <h3 class="text-lg font-semibold text-red-800 mb-2">
          Failed to Load Positions
        </h3>
        <p class="text-red-700 mb-4">{{ error }}</p>
        <button
          @click="fetchPositions"
          class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
        >
          Try Again
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="filteredPositions.length === 0" class="p-12 text-center">
      <div class="mx-auto h-12 w-12 text-gray-400">
        <i class="fas fa-briefcase text-4xl"></i>
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900">No positions found</h3>
      <p class="mt-1 text-sm text-gray-500">
        {{
          filterDepartment !== "all" || filterType !== "all"
            ? "Try adjusting your filters."
            : "There are currently no open positions available."
        }}
      </p>
    </div>

    <!-- Positions List -->
    <div v-else class="divide-y divide-gray-200">
      <article
        v-for="job in filteredPositions"
        :key="job.id"
        class="p-6 hover:bg-gray-50 transition-colors cursor-pointer"
        @click.stop="openJob(job)"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ job.position }}
              </h3>
              <span
                v-if="job.featured"
                class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full"
              >
                ⭐ Featured
              </span>
            </div>
            <div
              class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-3"
            >
              <span class="flex items-center gap-1">
                <i class="fas fa-building text-gray-400"></i>
                {{ job.department || "Department TBD" }}
              </span>
              <span class="flex items-center gap-1">
                <i class="fas fa-map-marker-alt text-gray-400"></i>
                {{ job.location || "Manila, Philippines" }}
              </span>
              <span class="flex items-center gap-1">
                <i class="fas fa-peso-sign text-gray-400"></i>
                {{ job.salary || "Competitive salary" }}
              </span>
            </div>
            <p class="text-gray-800 font-semibold text-sm line-clamp-2 mb-3">
              {{ job.description || "See details for more information." }}
            </p>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500 font-semibold">
                <i class="fas fa-clock mr-1"></i>
                Posted {{ formatDate(job.postedDate || job.created_at) }}
              </span>
              <button
                @click.stop="openJob(job)"
                class="text-blue-600 hover:text-blue-700 font-medium hover:underline transition-all"
              >
                View Details →
              </button>
            </div>
          </div>
          <div class="ml-4">
            <span
              v-if="job.category"
              class="ml-2 text-xs px-2 py-1 rounded"
              :class="
                job.isNonPlantilla
                  ? 'bg-yellow-100 text-yellow-800 font-bold'
                  : 'bg-blue-100 text-blue-800 font-bold'
              "
            >
              {{ job.category }}
            </span>
          </div>
        </div>
      </article>
    </div>

    <!-- Job Detail Modal -->
    <Teleport to="body">
      <div
        v-if="selectedJob"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-[9999] p-4"
        @click.self="closeJob"
      >
        <div
          class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full overflow-hidden max-h-[90vh]"
        >
          <header
            class="p-8 bg-gradient-to-br from-blue-600 to-blue-800 text-white"
          >
            <div class="flex items-start justify-between mb-6">
              <div class="flex-1">
                <div
                  v-if="selectedJob.featured"
                  class="inline-block px-3 py-1 bg-white/20 rounded-full text-sm mb-3"
                >
                  ⭐ Featured Position
                </div>
                <h1 class="text-3xl md:text-4xl mb-3 text-white">
                  {{ selectedJob.position }}
                </h1>
                <p class="text-blue-100">
                  {{ selectedJob.department || "Department TBD" }}
                </p>
              </div>
              <button
                class="text-white/80 hover:text-white transition-colors"
                @click="closeJob"
              >
                <i class="fas fa-times text-2xl"></i>
              </button>
            </div>

            <div
              class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-blue-50"
            >
              <div class="flex items-center gap-3">
                <i class="fas fa-map-marker-alt w-5"></i>
                <div>
                  <p class="text-xs uppercase tracking-wide">Location</p>
                  <p class="text-base">
                    {{ selectedJob.location || "Location TBD" }}
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <i class="fas fa-peso-sign w-5"></i>
                <div>
                  <p class="text-xs uppercase tracking-wide">Salary Range</p>
                  <p class="text-base">
                    {{ selectedJob.salary || "Competitive salary" }}
                  </p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <i class="fas fa-clock w-5"></i>
                <div>
                  <p class="text-xs uppercase tracking-wide">Posted</p>
                  <p class="text-base">
                    {{
                      formatDate(
                        selectedJob.postedDate || selectedJob.created_at,
                      )
                    }}
                  </p>
                </div>
              </div>
            </div>
          </header>

          <section
            class="p-8 space-y-8 overflow-y-auto max-h-[calc(90vh-220px)]"
          >
            <!-- Requirements Section -->
            <div v-if="hasAdditionalRequirements">
              <h2 class="text-2xl text-gray-900 mb-4 font-semibold">
                Requirements
              </h2>
              <ul class="space-y-3">
                <li
                  v-if="
                    selectedJob.eligibility &&
                    String(selectedJob.eligibility).trim() !== ''
                  "
                  class="flex items-start gap-3"
                >
                  <div
                    class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1"
                  >
                    <span class="text-blue-600 text-sm font-bold">✓</span>
                  </div>
                  <div>
                    <span class="text-gray-900 font-semibold"
                      >Eligibility:
                    </span>
                    <span class="text-gray-700 leading-relaxed">{{
                      selectedJob.eligibility
                    }}</span>
                  </div>
                </li>

                <!-- Educational Requirements with Academic Levels -->
                <li
                  v-if="hasEducationalRequirements"
                  class="flex items-start gap-3"
                >
                  <div
                    class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1"
                  >
                    <span class="text-blue-600 text-sm font-bold">✓</span>
                  </div>
                  <div class="flex-1">
                    <span class="text-gray-900 font-semibold"
                      >Educational Requirements:
                    </span>
                    <ul class="mt-1 space-y-1 ml-4">
                      <li
                        v-for="(edu, index) in selectedJob.education_details"
                        :key="index"
                        class="text-gray-700 leading-relaxed"
                      >
                        {{ edu.program
                        }}<span v-if="edu.academic_level" class="text-gray-600">
                          ({{ edu.academic_level }})</span
                        >
                      </li>
                    </ul>
                  </div>
                </li>

                <li
                  v-if="
                    selectedJob.experience &&
                    String(selectedJob.experience).trim() !== ''
                  "
                  class="flex items-start gap-3"
                >
                  <div
                    class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1"
                  >
                    <span class="text-blue-600 text-sm font-bold">✓</span>
                  </div>
                  <div>
                    <span class="text-gray-900 font-semibold"
                      >Experience:
                    </span>
                    <span class="text-gray-700 leading-relaxed">{{
                      selectedJob.experience
                    }}</span>
                  </div>
                </li>
                <li
                  v-if="
                    selectedJob.training &&
                    String(selectedJob.training).trim() !== ''
                  "
                  class="flex items-start gap-3"
                >
                  <div
                    class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1"
                  >
                    <span class="text-blue-600 text-sm font-bold">✓</span>
                  </div>
                  <div>
                    <span class="text-gray-900 font-semibold">Training: </span>
                    <span class="text-gray-700 leading-relaxed">{{
                      selectedJob.training
                    }}</span>
                  </div>
                </li>
              </ul>
            </div>

            <!-- Additional Remarks/Requirements (from plantilla_remarks) -->
            <div
              v-if="
                selectedJob.requirements_list &&
                selectedJob.requirements_list.length > 0
              "
              class="pt-4 border-t"
            >
              <h2 class="text-2xl text-gray-900 mb-4 font-semibold">
                Additional Requirements
              </h2>
              <ul class="space-y-3">
                <li
                  v-for="(req, index) in selectedJob.requirements_list"
                  :key="index"
                  class="flex items-start gap-3"
                >
                  <div
                    class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1"
                  >
                    <span class="text-blue-600 text-sm font-bold">✓</span>
                  </div>
                  <span class="text-gray-700 leading-relaxed text-base">{{
                    req
                  }}</span>
                </li>
              </ul>
            </div>

            <!-- Show message if no requirements at all -->
            <div
              v-if="
                !hasAdditionalRequirements &&
                (!selectedJob.requirements ||
                  selectedJob.requirements.length === 0)
              "
              class="text-center py-8 text-gray-500"
            >
              <p class="text-lg">
                No specific requirements listed for this position.
              </p>
              <p class="text-sm mt-2">
                Please contact the HR department for more information.
              </p>
            </div>

            <!-- Job Description (only show if it's not the placeholder) -->
            <div v-if="selectedJob.description && !isPlaceholderDescription">
              <h2 class="text-2xl text-gray-900 mb-4 font-semibold">
                Job Description
              </h2>
              <p class="text-gray-600 leading-relaxed text-lg">
                {{ selectedJob.description }}
              </p>
            </div>

            <div class="flex flex-col gap-4 pt-6 border-t">
              <div
                v-if="
                  selectedJob &&
                  !selectedJob.isNonPlantilla &&
                  !plantillaMatch.meets_requirements
                "
                class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
              >
                <p class="font-semibold">You do not meet this position's requirements.</p>
                <p class="mt-1">
                  Update your PDS (eligibility, education, experience, or training) before applying.
                </p>
                <ul
                  v-if="plantillaMatch.missing_requirements?.length"
                  class="mt-2 list-disc pl-5 space-y-1"
                >
                  <li
                    v-for="(item, index) in plantillaMatch.missing_requirements"
                    :key="index"
                  >
                    {{ item }}
                  </li>
                </ul>
              </div>

              <div class="flex flex-col md:flex-row gap-4">
              <button
                class="flex-1 px-6 py-4 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all hover:shadow-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                @click="applyForPosition(selectedJob)"
                :disabled="
                  applying ||
                  checkingMatch ||
                  (!selectedJob.isNonPlantilla &&
                    !plantillaMatch.meets_requirements)
                "
              >
                <i class="fas fa-paper-plane mr-2"></i>
                {{
                  applying
                    ? "Applying..."
                    : checkingMatch
                      ? "Checking requirements..."
                      : !selectedJob.isNonPlantilla &&
                          !plantillaMatch.meets_requirements
                        ? "Requirements not met"
                        : "Apply for this Position"
                }}
              </button>
              <button
                class="px-6 py-4 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all font-medium"
                @click="closeJob"
              >
                Back to List
              </button>
              </div>
            </div>
          </section>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, Teleport } from "vue";
import { ApiService } from "@/services/api.js";
import { ElNotification, ElMessageBox } from "element-plus";
import { checkPDSCompletion } from "@/composables/usePDSCompletion.js";

// Props
const props = defineProps({
  applicantId: {
    type: Number,
    default: null,
  },
});

// Emits
const emit = defineEmits([
  "back-to-applications",
  "application-submitted",
  "go-to-pds",
]);

// Reactive data
const positions = ref([]);
const loading = ref(false);
const error = ref(null);
const filterDepartment = ref("all");
const filterType = ref("all");
const selectedJob = ref(null);
const applying = ref(false);
const checkingMatch = ref(false);
const plantillaMatch = ref({
  meets_requirements: true,
  missing_requirements: [],
});

// Computed properties
const departments = computed(() => {
  const set = new Set();
  positions.value.forEach((job) => {
    if (job.department) set.add(job.department);
  });
  return Array.from(set).sort();
});

const filteredPositions = computed(() => {
  return positions.value.filter((job) => {
    const deptMatch =
      filterDepartment.value === "all" ||
      job.department === filterDepartment.value;
    const typeMatch =
      filterType.value === "all" ||
      (filterType.value === "plantilla" && !job.isNonPlantilla) ||
      (filterType.value === "non-plantilla" && job.isNonPlantilla);
    return deptMatch && typeMatch;
  });
});

const hasEducationalRequirements = computed(() => {
  if (!selectedJob.value) return false;
  return (
    selectedJob.value.education_details &&
    Array.isArray(selectedJob.value.education_details) &&
    selectedJob.value.education_details.length > 0
  );
});

const hasAdditionalRequirements = computed(() => {
  if (!selectedJob.value) return false;
  const hasEligibility =
    selectedJob.value.eligibility &&
    String(selectedJob.value.eligibility).trim() !== "";
  const hasEducation = hasEducationalRequirements.value;
  const hasExperience =
    selectedJob.value.experience &&
    String(selectedJob.value.experience).trim() !== "";
  const hasTraining =
    selectedJob.value.training &&
    String(selectedJob.value.training).trim() !== "";
  return hasEligibility || hasEducation || hasExperience || hasTraining;
});

const isPlaceholderDescription = computed(() => {
  if (!selectedJob.value) return false;
  return selectedJob.value.description?.includes(
    "See details for requirements",
  );
});

// Methods
const fetchPositions = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await ApiService.getVacancies();

    // Combine plantilla and non‑plantilla arrays
    let vacancies = [];
    if (response.data?.data) {
      vacancies = response.data.data.vacancies || [];
      const nonVac = response.data.data.non_plantilla_vacancies || [];
      vacancies = [...vacancies, ...nonVac];
    } else if (response.data?.data && Array.isArray(response.data.data)) {
      vacancies = response.data.data;
    } else if (Array.isArray(response.data)) {
      vacancies = response.data;
    }

    // Map backend fields to frontend format
    positions.value = vacancies.map((position) => {
      const isNonPlantilla = position.type === "non_plantilla";

      // Combine requirements fields if they exist
      const requirements = [];
      if (position.eligibility)
        requirements.push(`Eligibility: ${position.eligibility}`);
      if (position.education)
        requirements.push(`Education: ${position.education}`);
      if (position.experience)
        requirements.push(`Experience: ${position.experience}`);
      if (position.training)
        requirements.push(`Training: ${position.training}`);

      // Format salary display
      const salaryDisplay = isNonPlantilla
        ? position.salary || "Competitive salary"
        : position.grade && position.step
          ? `${position.grade} ${position.step}`
          : "Competitive salary";

      // Format posted date
      const postedDate = position.publication_from
        ? new Date(position.publication_from).toISOString().split("T")[0]
        : new Date().toISOString().split("T")[0];

      return {
        id: position.id,
        code: position.code,
        position: position.position,
        department: position.department || "General",
        location: "Manila, Philippines",
        // Employment type pill (avoid duplicating plantilla/non-plantilla discriminator)
        type: position.employment_type || "Full-time",
        category: isNonPlantilla ? "Non‑Plantilla" : "Plantilla",
        salary: salaryDisplay,
        description:
          position.description ||
          `Position: ${position.position}. See details for requirements.`,
        requirements: requirements.length > 0 ? requirements : [],
        postedDate: postedDate,
        featured: false,
        vacant: position.vacant ?? 1,
        grade: position.grade,
        step: position.step,
        created_at: position.publication_from || postedDate,
        eligibility: position.eligibility,
        education: position.education,
        education_details: position.education_details || [],
        experience: position.experience,
        training: position.training,
        isNonPlantilla,
        isPlantilla: !isNonPlantilla,
        rawType: position.type,
      };
    });
  } catch (err) {
    console.error("Error fetching positions:", err);
    error.value =
      err.response?.data?.message ||
      err.message ||
      "Failed to load positions. Please check your connection and try again.";
    positions.value = [];
  } finally {
    loading.value = false;
  }
};

const refreshPositions = () => {
  fetchPositions();
};

const openJob = async (job) => {
  selectedJob.value = { ...job };
  plantillaMatch.value = {
    meets_requirements: true,
    missing_requirements: [],
  };

  if (job?.isNonPlantilla) {
    return;
  }

  checkingMatch.value = true;
  try {
    const response = await ApiService.checkPlantillaRequirements(job.id);
    const data = response?.data?.data;
    if (data) {
      plantillaMatch.value = {
        meets_requirements: !!data.meets_requirements,
        missing_requirements: data.missing_requirements || [],
      };
    }
  } catch (err) {
    console.error("Error checking plantilla requirements:", err);
  } finally {
    checkingMatch.value = false;
  }
};

const closeJob = () => {
  selectedJob.value = null;
  plantillaMatch.value = {
    meets_requirements: true,
    missing_requirements: [],
  };
};

const formatDate = (dateString) => {
  if (!dateString) return "Recently";
  return new Date(dateString).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
};

const applyForPosition = async (position) => {
  if (!props.applicantId) {
    ElNotification({
      title: "Profile incomplete",
      message: "Please complete your registration first.",
      type: "warning",
      duration: 4000,
      position: "top-right",
    });
    return;
  }

  try {
    applying.value = true;

    const applicantRes = await ApiService.getApplicantPage();
    const data = applicantRes?.data?.data || {};

    // Check if documents are uploaded
    const documents = data?.documents || [];
    if (!Array.isArray(documents) || documents.length === 0) {
      // Close the position detail modal first
      closeJob();

      // Small delay to ensure modal closes before showing warning
      await new Promise((resolve) => setTimeout(resolve, 100));

      const proceed = await ElMessageBox.confirm(
        `You must upload at least one document before applying to positions. Please upload your required documents first.\n\nWould you like to go to your PDS to upload documents?`,
        "Documents Required",
        {
          confirmButtonText: "Go to PDS",
          cancelButtonText: "Stay here",
          type: "warning",
        },
      ).catch(() => false);
      if (proceed) emit("go-to-pds");
      return;
    }

    const pdsComplete = checkPDSCompletion(data);

    if (!pdsComplete.isComplete) {
      // Close the position detail modal first
      closeJob();

      // Small delay to ensure modal closes before showing warning
      await new Promise((resolve) => setTimeout(resolve, 100));

      const proceed = await ElMessageBox.confirm(
        `Your Personal Data Sheet (PDS) is incomplete. Please complete the following sections first:\n\n${pdsComplete.missingSections.join("\n")}\n\nWould you like to go to your PDS to complete these sections?`,
        "PDS Incomplete",
        {
          confirmButtonText: "Go to PDS",
          cancelButtonText: "Stay here",
          type: "warning",
        },
      ).catch(() => false);
      if (proceed) emit("go-to-pds");
      return;
    }

    if (!position.isNonPlantilla && !plantillaMatch.value.meets_requirements) {
      ElNotification({
        title: "Requirements not met",
        message:
          plantillaMatch.value.missing_requirements?.join("; ") ||
          "Your PDS does not meet the minimum qualifications for this plantilla position.",
        type: "warning",
        duration: 6000,
        position: "top-right",
      });
      return;
    }

    const response = await ApiService.applyForPosition(
      props.applicantId,
      position.id,
      position.isPlantilla !== false,
    );

    if (response.data.success) {
      ElNotification({
        title: "Application submitted",
        message: "Your application was submitted successfully.",
        type: "success",
        duration: 4000,
        position: "top-right",
      });
      closeJob();
      emit("application-submitted");
      await fetchPositions();
    }
  } catch (err) {
    console.error("Error applying for position:", err);
    if (err.response?.status === 409) {
      ElNotification({
        title: "Already applied",
        message:
          err.response?.data?.message ||
          "You have already applied for this position.",
        type: "warning",
        duration: 4000,
        position: "top-right",
      });
    } else if (err.response?.status === 422) {
      const missingReqs = err.response?.data?.errors?.missing_requirements;
      ElNotification({
        title: missingReqs?.length ? "Requirements not met" : "PDS incomplete",
        message:
          err.response?.data?.message ||
          (missingReqs?.length
            ? missingReqs.join("; ")
            : "Please complete your Personal Data Sheet (PDS) before applying."),
        type: "warning",
        duration: 6000,
        position: "top-right",
      });
    } else {
      ElNotification({
        title: "Application failed",
        message: "Failed to submit application. Please try again.",
        type: "error",
        duration: 5000,
        position: "top-right",
      });
    }
  } finally {
    applying.value = false;
  }
};

// Load positions on mount
onMounted(() => {
  fetchPositions();
});
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  overflow: hidden;
}
</style>
