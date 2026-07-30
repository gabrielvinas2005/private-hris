<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow-sm sticky top-0 z-40">
      <div
        class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between"
      >
        <button
          class="flex items-center gap-2 text-blue-600 hover:text-blue-700 transition-colors"
          @click="handleNavigate('/')"
        >
          <ArrowLeft class="w-5 h-5" />
          <span>Back to Home</span>
        </button>
        <div class="flex items-center gap-3">
          <button
            class="px-5 py-2 text-gray-700 hover:text-blue-600 transition-colors"
            @click="handleNavigate('/registration')"
          >
            Register
          </button>
          <button
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            @click="handleNavigate('/login')"
          >
            Sign In
          </button>
        </div>
      </div>
    </nav>

    <section
      class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16 px-6"
    >
      <div class="max-w-7xl mx-auto">
        <div
          class="flex flex-col md:flex-row md:items-center md:justify-between gap-6"
        >
          <div>
            <h1 class="text-4xl md:text-5xl mb-4">Available Positions</h1>
            <p class="text-xl text-blue-100">
              {{ displayedCount }} opportunities waiting for you
            </p>
          </div>
          <button
            class="md:hidden px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg flex items-center gap-2 self-start"
            @click="toggleFilters"
          >
            <Filter class="w-5 h-5" />
            Filters
          </button>
        </div>
      </div>
    </section>

    <div class="max-w-7xl mx-auto px-6 py-12">
      <div v-if="loading" class="text-center py-16">
        <div
          class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-t-2 border-blue-600"
        ></div>
        <p class="mt-4 text-gray-600">Loading positions...</p>
      </div>

      <div v-else-if="error" class="text-center py-12">
        <div
          class="bg-red-50 border border-red-200 rounded-lg p-8 max-w-md mx-auto"
        >
          <div
            class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4"
          >
            <span class="text-red-600 text-2xl">⚠️</span>
          </div>
          <h3 class="text-lg font-semibold text-red-900 mb-2">
            Failed to Load Positions
          </h3>
          <p class="text-red-700 mb-4">{{ error }}</p>
          <div
            class="text-sm text-red-600 bg-red-100 rounded p-3 mb-4 text-left"
          >
            <p class="font-semibold mb-1">Troubleshooting:</p>
            <ul class="list-disc list-inside space-y-1">
              <li>Check if the backend API is running</li>
              <li>Verify you have active vacancies in the database</li>
              <li>Check browser console for detailed error messages</li>
            </ul>
          </div>
          <button
            class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors"
            @click="fetchPositions"
          >
            Try Again
          </button>
        </div>
      </div>

      <div v-else class="flex flex-col lg:flex-row gap-8">
        <aside
          :class="[
            showFilters ? 'block' : 'hidden lg:block',
            'w-full lg:w-72 flex-shrink-0',
          ]"
        >
          <div class="bg-white rounded-2xl p-6 shadow-sm sticky top-28">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-xl text-gray-900">Filters</h3>
              <button
                class="text-sm text-blue-600 hover:text-blue-700"
                @click="resetFilters"
              >
                Clear All
              </button>
            </div>

            <div class="space-y-6">
              <div>
                <label class="block text-sm text-gray-700 mb-3"
                  >Department</label
                >
                <div class="space-y-2">
                  <button
                    v-for="dept in departments"
                    :key="dept"
                    class="w-full text-left px-4 py-2 rounded-lg transition-all"
                    :class="
                      filterDepartment === dept
                        ? 'bg-blue-600 text-white shadow-md'
                        : 'bg-gray-50 text-gray-700 hover:bg-gray-100'
                    "
                    @click="filterDepartment = dept"
                  >
                    {{ dept === "all" ? "All Departments" : dept }}
                  </button>
                </div>
              </div>

              <div class="border-t pt-6">
                <label class="block text-sm text-gray-700 mb-3"
                  >Employment Type</label
                >
                <div class="space-y-2">
                  <button
                    v-for="type in employmentTypes"
                    :key="type"
                    class="w-full text-left px-4 py-2 rounded-lg transition-all"
                    :class="
                      filterType === type
                        ? 'bg-blue-600 text-white shadow-md'
                        : 'bg-gray-50 text-gray-700 hover:bg-gray-100'
                    "
                    @click="filterType = type"
                  >
                    {{ type === "all" ? "All Types" : type }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </aside>

        <section class="flex-1 space-y-12">
          <div v-if="featuredPositions.length" class="space-y-6">
            <h2 class="text-2xl text-gray-900">Featured Positions</h2>
            <div class="grid gap-6">
              <article
                v-for="job in featuredPositions"
                :key="job.id"
                class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-8 text-white shadow-xl hover:shadow-2xl transition-all cursor-pointer transform hover:-translate-y-1"
                @click.stop="openJob(job)"
              >
                <div class="flex items-start justify-between mb-4">
                  <div>
                    <div
                      class="inline-block px-3 py-1 bg-white/20 rounded-full text-sm mb-3"
                    >
                      ⭐ Featured
                    </div>
                    <h3 class="text-2xl mb-2">{{ job.position }}</h3>
                    <div
                      class="flex flex-wrap items-center gap-4 text-blue-100 text-sm"
                    >
                      <span class="flex items-center gap-1">
                        <OfficeBuilding class="w-4 h-4" />
                        {{ job.department || "Department TBD" }}
                      </span>
                      <span class="flex items-center gap-1">
                        <Location class="w-4 h-4" />
                        {{ job.location || "Location TBD" }}
                      </span>
                    </div>
                  </div>
                  <span
                    class="px-4 py-2 bg-white/20 rounded-lg text-sm backdrop-blur-sm"
                  >
                    {{ job.type || "Full-time" }}
                  </span>
                  <span v-if="job.category" class="ml-2 text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded">
                    {{ job.category }}
                  </span>
                </div>
                <p class="text-blue-100 mb-4 line-clamp-2">
                  {{ job.description || "Details to follow." }}
                </p>
                <div class="flex items-center justify-between text-sm">
                  <span class="flex items-center gap-2 text-xl">
                    <Money class="w-5 h-5" />
                    {{ job.salary || "Competitive salary" }}
                  </span>
                  <span class="flex items-center gap-2 text-blue-100">
                    <Timer class="w-4 h-4" />
                    Posted {{ job.postedDate || formatDate(job.created_at) }}
                  </span>
                </div>
              </article>
            </div>
          </div>

          <div v-if="regularPositions.length" class="space-y-6">
            <h2 class="text-2xl text-gray-900">All Positions</h2>
            <div class="grid gap-4">
              <article
                v-for="job in regularPositions"
                :key="job.id"
                class="bg-white rounded-xl p-6 shadow-sm hover:shadow-lg transition-all cursor-pointer border border-gray-100"
                @click.stop="openJob(job)"
              >
                <div class="flex items-start justify-between mb-3">
                  <div>
                    <h3 class="text-xl text-gray-900 mb-2">
                      {{ job.position }}
                    </h3>
                    <div
                      class="flex flex-wrap items-center gap-3 text-sm text-gray-600"
                    >
                      <span class="flex items-center gap-1">
                        <OfficeBuilding class="w-4 h-4" />
                        {{ job.department || "Department TBD" }}
                      </span>
                      <span class="flex items-center gap-1">
                        <Location class="w-4 h-4" />
                        {{ job.location || "Location TBD" }}
                      </span>
                      <span class="flex items-center gap-1">
                        <Money class="w-4 h-4" />
                        {{ job.salary || "Competitive salary" }}
                      </span>
                    </div>
                  </div>
                  <span
                    class="px-3 py-1 bg-blue-50 text-blue-600 rounded-lg text-sm"
                  >
                    {{ job.type || "Full-time" }}
                  </span>
                  <span v-if="job.category" class="ml-2 text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded">
                    {{ job.category }}
                  </span>
                  <span v-if="job.category" class="ml-2 text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded">
                    {{ job.category }}
                  </span>
                </div>
                <p class="text-gray-600 line-clamp-2 mb-3">
                  {{ job.description || "See details for more information." }}
                </p>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-500"
                    >Posted
                    {{ job.postedDate || formatDate(job.created_at) }}</span
                  >
                  <button
                    @click.stop="openJob(job)"
                    class="text-blue-600 hover:text-blue-700 hover:underline transition-all font-medium"
                  >
                    View Details →
                  </button>
                </div>
              </article>
            </div>
          </div>

          <div
            v-if="!filteredPositions.length && !loading && !error"
            class="text-center py-16"
          >
            <Briefcase class="w-16 h-16 text-gray-300 mx-auto mb-4" />
            <h3 class="text-xl text-gray-600 mb-2">No positions found</h3>
            <p class="text-gray-500 mb-4">
              {{
                filterDepartment !== "all" || filterType !== "all"
                  ? "Try adjusting your filters"
                  : "There are currently no open positions available."
              }}
            </p>
            <div
              v-if="filterDepartment === 'all' && filterType === 'all'"
              class="text-sm text-gray-400 bg-gray-50 rounded-lg p-4 max-w-md mx-auto"
            >
              <p class="mb-2">To add positions:</p>
              <ol class="list-decimal list-inside space-y-1 text-left">
                <li>Create plantillas in the backend database</li>
                <li>
                  Set
                  <code class="bg-gray-200 px-1 rounded">active = 1</code> and
                  <code class="bg-gray-200 px-1 rounded">approved = 1</code>
                </li>
                <li>
                  Set
                  <code class="bg-gray-200 px-1 rounded">employee_id = 0</code>
                  (unassigned)
                </li>
                <li>
                  Ensure
                  <code class="bg-gray-200 px-1 rounded">publication_to</code>
                  date is in the future
                </li>
              </ol>
            </div>
          </div>
        </section>
      </div>
    </div>

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
          :class="[
            'p-8',
            selectedJob.featured
              ? 'bg-gradient-to-br from-blue-600 to-blue-800 text-white'
              : 'bg-gray-50',
          ]"
        >
          <div class="flex items-start justify-between mb-6">
            <div>
              <div
                v-if="selectedJob.featured"
                class="inline-block px-3 py-1 bg-white/20 rounded-full text-sm mb-3"
              >
                ⭐ Featured Position
              </div>
              <h1
                :class="[
                  'text-3xl md:text-4xl mb-3',
                  selectedJob.featured ? 'text-white' : 'text-gray-900',
                ]"
              >
                {{ selectedJob.position }}
              </h1>
              <p
                :class="
                  selectedJob.featured ? 'text-blue-100' : 'text-gray-600'
                "
              >
                {{ selectedJob.department || "Department TBD" }}
              </p>
            </div>
            <button class="text-white/80 hover:text-white" @click="closeJob">
              <Close class="w-6 h-6" />
            </button>
          </div>

          <div
            :class="[
              'grid grid-cols-1 md:grid-cols-3 gap-4 text-sm',
              selectedJob.featured ? 'text-blue-50' : 'text-gray-600',
            ]"
          >
            <div class="flex items-center gap-3">
              <Location class="w-5 h-5" />
              <div>
                <p class="text-xs uppercase tracking-wide">Location</p>
                <p class="text-base">
                  {{ selectedJob.location || "Location TBD" }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <Money class="w-5 h-5" />
              <div>
                <p class="text-xs uppercase tracking-wide">Salary Range</p>
                <p class="text-base">
                  {{ selectedJob.salary || "Competitive salary" }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <Timer class="w-5 h-5" />
              <div>
                <p class="text-xs uppercase tracking-wide">Posted</p>
                <p class="text-base">
                  {{
                    selectedJob.postedDate || formatDate(selectedJob.created_at)
                  }}
                </p>
              </div>
            </div>
          </div>
        </header>

        <section class="p-8 space-y-8 overflow-y-auto max-h-[calc(90vh-220px)]">
          <!-- Requirements Section -->
          <div v-if="hasAdditionalRequirements">
            <h2 class="text-2xl text-gray-900 mb-4 font-semibold">Requirements</h2>
            <ul class="space-y-3">
              <li
                v-if="selectedJob.eligibility && String(selectedJob.eligibility).trim() !== ''"
                class="flex items-start gap-3"
              >
                <div
                  class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1"
                >
                  <span class="text-blue-600 text-sm font-bold">✓</span>
                </div>
                <div>
                  <span class="text-gray-900 font-semibold">Eligibility: </span>
                  <span class="text-gray-700 leading-relaxed">{{ selectedJob.eligibility }}</span>
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
                  <span class="text-gray-900 font-semibold">Educational Requirements: </span>
                  <ul class="mt-1 space-y-1 ml-4">
                    <li
                      v-for="(edu, index) in selectedJob.education_details"
                      :key="index"
                      class="text-gray-700 leading-relaxed"
                    >
                      {{ edu.program }}<span v-if="edu.academic_level" class="text-gray-600"> ({{ edu.academic_level }})</span>
                    </li>
                  </ul>
                </div>
              </li>
              
              <li
                v-if="selectedJob.experience && String(selectedJob.experience).trim() !== ''"
                class="flex items-start gap-3"
              >
                <div
                  class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1"
                >
                  <span class="text-blue-600 text-sm font-bold">✓</span>
                </div>
                <div>
                  <span class="text-gray-900 font-semibold">Experience: </span>
                  <span class="text-gray-700 leading-relaxed">{{ selectedJob.experience }}</span>
                </div>
              </li>
              <li
                v-if="selectedJob.training && String(selectedJob.training).trim() !== ''"
                class="flex items-start gap-3"
              >
                <div
                  class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1"
                >
                  <span class="text-blue-600 text-sm font-bold">✓</span>
                </div>
                <div>
                  <span class="text-gray-900 font-semibold">Training: </span>
                  <span class="text-gray-700 leading-relaxed">{{ selectedJob.training }}</span>
                </div>
              </li>
            </ul>
          </div>

          <!-- Additional Remarks/Requirements (from plantilla_remarks) -->
          <div v-if="selectedJob.requirements_list && selectedJob.requirements_list.length > 0" class="pt-4 border-t">
            <h2 class="text-2xl text-gray-900 mb-4 font-semibold">Additional Requirements</h2>
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
                <span class="text-gray-700 leading-relaxed text-base">{{ req }}</span>
              </li>
            </ul>
          </div>

          <!-- Job Description (only show if it's not the placeholder) -->
          <div v-if="selectedJob.description && !isPlaceholderDescription">
            <h2 class="text-2xl text-gray-900 mb-4 font-semibold">Job Description</h2>
            <p class="text-gray-600 leading-relaxed text-lg">
              {{ selectedJob.description }}
            </p>
          </div>

          <div class="flex flex-col gap-4 pt-6 border-t">
            <div
              v-if="
                selectedJob &&
                selectedJob.isPlantilla &&
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
              class="flex-1 px-6 py-4 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
              @click="applyForPosition(selectedJob)"
              :disabled="
                checkingMatch ||
                (selectedJob?.isPlantilla && !plantillaMatch.meets_requirements)
              "
            >
              {{
                checkingMatch
                  ? "Checking requirements..."
                  : selectedJob?.isPlantilla && !plantillaMatch.meets_requirements
                    ? "Requirements not met"
                    : "Apply for this Position"
              }}
            </button>
            <button
              class="px-6 py-4 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all"
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
import { computed, onMounted, ref, Teleport } from "vue";
import { useRouter } from "vue-router";
import {
  ArrowLeft,
  Filter,
  Close,
  Briefcase,
  Location,
  Money,
  Timer,
  OfficeBuilding,
} from "@element-plus/icons-vue";
import { ElNotification, ElMessageBox } from "element-plus";
import { ApiService } from "@/services/api.js";
import { checkPDSCompletion } from "@/composables/usePDSCompletion.js";

const router = useRouter();
const positions = ref([]);
const loading = ref(false);
const error = ref(null);
const filterDepartment = ref("all");
const filterType = ref("all");
const showFilters = ref(false);
const selectedJob = ref(null);
const checkingMatch = ref(false);
const plantillaMatch = ref({
  meets_requirements: true,
  missing_requirements: [],
});

const fetchPositions = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await ApiService.getVacancies();

    // Combine plantilla and non‑plantilla lists if both are provided
    let vacancies = [];
    if (response.data?.data) {
      vacancies = response.data.data.vacancies || [];
      const nonVacancies = response.data.data.non_plantilla_vacancies || [];
      vacancies = [...vacancies, ...nonVacancies];
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

      // Format salary display (non‑plantilla may send a salary field)
      const salaryDisplay = isNonPlantilla
        ? position.salary || "Competitive salary"
        : position.grade && position.step
        ? `SG-${position.grade} Step ${position.step}`
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
        // use backend type or indicate non‑plantilla for clarity
        // keep `type` for employment type (full-time/part-time/etc.)
        type: position.type || "Full-time",
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
        // helper flags for downstream logic
        isNonPlantilla,
        isPlantilla: !isNonPlantilla,
        rawType: position.type,
      };
    });
  } catch (err) {
    console.error("Error fetching positions:", err);
    console.error("Error details:", err.response?.data);
    error.value =
      err.response?.data?.message ||
      err.message ||
      "Failed to load positions. Please check your connection and try again.";
    positions.value = [];
  } finally {
    loading.value = false;
  }
};

const toSortedList = (set) => {
  const [first, ...rest] = Array.from(set);
  return [first, ...rest.sort()];
};

const departments = computed(() => {
  const set = new Set(["all"]);
  positions.value.forEach((job) => {
    if (job.department) set.add(job.department);
  });
  return toSortedList(set);
});

const employmentTypes = computed(() => {
  const set = new Set(["all"]);
  positions.value.forEach((job) => {
    if (job.type) set.add(job.type);
  });
  if (set.size === 1)
    ["Full-time", "Part-time", "Contract"].forEach((type) => set.add(type));
  return toSortedList(set);
});

const filteredPositions = computed(() =>
  positions.value.filter((job) => {
    const deptMatch =
      filterDepartment.value === "all" ||
      job.department === filterDepartment.value;
    const typeMatch =
      filterType.value === "all" || job.type === filterType.value;
    return deptMatch && typeMatch;
  })
);

const featuredPositions = computed(() => {
  const marked = filteredPositions.value.filter((job) => job.featured);
  if (marked.length) return marked;
  return filteredPositions.value.slice(
    0,
    Math.min(filteredPositions.value.length, 2)
  );
});

const regularPositions = computed(() => {
  const featuredIds = new Set(featuredPositions.value.map((job) => job.id));
  return filteredPositions.value.filter((job) => !featuredIds.has(job.id));
});

const displayedCount = computed(() => filteredPositions.value.length);

const hasEducationalRequirements = computed(() => {
  if (!selectedJob.value) return false;
  return selectedJob.value.education_details && 
         Array.isArray(selectedJob.value.education_details) && 
         selectedJob.value.education_details.length > 0;
});

const hasAdditionalRequirements = computed(() => {
  if (!selectedJob.value) return false;
  const hasEligibility = selectedJob.value.eligibility && String(selectedJob.value.eligibility).trim() !== '';
  const hasEducation = hasEducationalRequirements.value;
  const hasExperience = selectedJob.value.experience && String(selectedJob.value.experience).trim() !== '';
  const hasTraining = selectedJob.value.training && String(selectedJob.value.training).trim() !== '';
  return hasEligibility || hasEducation || hasExperience || hasTraining;
});

const isPlaceholderDescription = computed(() => {
  if (!selectedJob.value) return false;
  return selectedJob.value.description?.includes("See details for requirements");
});

const handleNavigate = (path) => {
  router.push(path);
};

const toggleFilters = () => {
  showFilters.value = !showFilters.value;
};

const resetFilters = () => {
  filterDepartment.value = "all";
  filterType.value = "all";
};

const openJob = async (job) => {
  selectedJob.value = { ...job };
  plantillaMatch.value = {
    meets_requirements: true,
    missing_requirements: [],
  };

  if (!job?.isPlantilla) {
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

const applyForPosition = async (position) => {
  try {
    const token = localStorage.getItem("auth_token");
    if (!token) {
      ElNotification({
        title: "Login required",
        message: "Please log in to apply for positions.",
        type: "warning",
        duration: 4000,
        position: "top-right",
      });
      router.push("/login");
      return;
    }

    const applicantResponse = await ApiService.getApplicantPage();
    const data = applicantResponse.data?.data || {};
    const applicant = data.applicant?.[0];
    if (!applicant?.id) {
      ElNotification({
        title: "Complete registration",
        message: "Please complete your registration first.",
        type: "warning",
        duration: 4000,
        position: "top-right",
      });
      router.push("/registration");
      return;
    }

    // Check if documents are uploaded
    const documents = data?.documents || [];
    if (!Array.isArray(documents) || documents.length === 0) {
      // Close the position detail modal first
      closeJob();
      
      // Small delay to ensure modal closes before showing warning
      await new Promise(resolve => setTimeout(resolve, 100));
      
      const proceed = await ElMessageBox.confirm(
        `You must upload at least one document before applying to positions. Please upload your required documents first.\n\nWould you like to go to your dashboard to upload documents?`,
        "Documents Required",
        {
          confirmButtonText: "Go to dashboard",
          cancelButtonText: "Stay here",
          type: "warning",
        }
      ).catch(() => false);
      if (proceed) router.push("/dashboard");
      return;
    }
    
    const pdsComplete = checkPDSCompletion(data);
    if (!pdsComplete.isComplete) {
      // Close the position detail modal first
      closeJob();
      
      // Small delay to ensure modal closes before showing warning
      await new Promise(resolve => setTimeout(resolve, 100));
      
      const proceed = await ElMessageBox.confirm(
        `Your Personal Data Sheet (PDS) is incomplete. Please complete the following sections first:\n\n${pdsComplete.missingSections.join(
          "\n"
        )}\n\nWould you like to go to your dashboard to complete your PDS?`,
        "PDS Incomplete",
        {
          confirmButtonText: "Go to dashboard",
          cancelButtonText: "Stay here",
          type: "warning",
        }
      ).catch(() => false);
      if (proceed) router.push("/dashboard");
      return;
    }

    if (position.isPlantilla && !plantillaMatch.value.meets_requirements) {
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
      applicant.id,
      position.id,
      position.isPlantilla !== false // send false for non‑plantillas
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
  }
};

const formatDate = (dateString) => {
  if (!dateString) return "Recently";
  return new Date(dateString).toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
};

onMounted(fetchPositions);
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
