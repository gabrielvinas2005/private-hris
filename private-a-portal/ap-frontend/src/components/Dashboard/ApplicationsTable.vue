<template>
  <div class="bg-white rounded-xl shadow-sm border">
    <!-- Show Job Listings if in browse mode -->
    <JobListings
      v-if="showJobListings"
      :applicant-id="applicantId"
      @back-to-applications="showJobListings = false"
      @application-submitted="handleApplicationSubmitted"
      @go-to-pds="handleGoToPds"
    />

    <!-- Applications View -->
    <template v-else>
      <!-- Header -->
      <div class="p-6 border-b border-gray-200">
        <div
          class="flex flex-col sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <h3 class="text-lg font-semibold text-gray-900">My Applications</h3>
            <p class="mt-1 text-sm text-gray-500">
              Track your job applications and their status
            </p>
          </div>
          <div class="mt-4 sm:mt-0 flex space-x-3">
            <button
              @click="showJobListings = true"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
            >
              <Plus :size="16" class="mr-2" />
              New Application
            </button>
            <button
              @click="refreshData"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors"
            >
              <RefreshCw
                :size="16"
                class="mr-2"
                :class="{ 'animate-spin': loading }"
              />
              Refresh
            </button>
          </div>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="p-6 border-b border-gray-200 bg-gray-50">
        <div
          class="flex flex-col sm:flex-row sm:items-center space-y-3 sm:space-y-0 sm:space-x-4"
        >
          <!-- Search -->
          <div class="flex-1">
            <div class="relative">
              <Search
                :size="18"
                class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
              />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search applications..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>

          <!-- Status Filter -->
          <select
            v-model="statusFilter"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="under-review">Under Review</option>
            <option value="shortlisted">Shortlisted</option>
            <option value="rejected">Rejected</option>
            <option value="not-qualified">Not Qualified</option>
            <option value="will-not-proceed">Will not proceed</option>
            <option value="for hiring">For Hiring</option>
          </select>

          <!-- Department Filter -->
          <select
            v-model="departmentFilter"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All Departments</option>
            <option v-for="dept in departmentOptions" :key="dept" :value="dept">
              {{ dept }}
            </option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-6">
        <div class="animate-pulse space-y-4">
          <div v-for="i in 3" :key="i" class="flex space-x-4">
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="filteredApplications.length === 0"
        class="p-12 text-center"
      >
        <div
          class="mx-auto h-12 w-12 text-gray-400 flex items-center justify-center"
        >
          <Briefcase :size="48" />
        </div>
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          No applications found
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          {{
            searchQuery || statusFilter || departmentFilter
              ? "Try adjusting your search criteria."
              : "Get started by creating your first job application."
          }}
        </p>
        <button
          v-if="!searchQuery && !statusFilter && !departmentFilter"
          @click="showJobListings = true"
          class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700"
        >
          <Plus :size="16" class="mr-2" />
          Create Application
        </button>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Position
              </th>
              <!-- <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Type
              </th> -->
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Department
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Applied Date
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Status
              </th>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="application in paginatedApplications"
              :key="
                application.type
                  ? `${application.type}-${application.id}`
                  : application.id
              "
              class="hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
              @click="$emit('view-application', application)"
            >
              <!-- Position -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-gray-900">
                    {{ application.position }}
                  </div>
                  <div class="text-sm text-gray-500">
                    {{
                      application.type === "plantilla"
                        ? "Plantilla"
                        : application.type === "non_plantilla"
                          ? "Non‑Plantilla"
                          : "N/A"
                    }}
                  </div>
                </div>
              </td>

              <!-- <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full"
                  :class="
                    application.type === 'plantilla'
                      ? 'bg-blue-100 text-blue-800'
                      : application.type === 'non_plantilla'
                        ? 'bg-yellow-100 text-yellow-800'
                        : 'bg-gray-100 text-gray-800'
                  "
                >
                  {{
                    application.type === "plantilla"
                      ? "Plantilla"
                      : application.type === "non_plantilla"
                        ? "Non‑Plantilla"
                        : "N/A"
                  }}
                </span>
              </td> -->

              <!-- Department -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ application.department }}
                </div>
                <div class="text-sm text-gray-500">
                  {{ application.location }}
                </div>
              </td>

              <!-- Applied Date -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(application.appliedDate) }}
              </td>

              <!-- Status -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusClass(application.status)"
                  class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full"
                >
                  <component
                    :is="getStatusIcon(application.status)"
                    :size="14"
                    class="mr-1"
                  />
                  {{ formatStatus(application.status) }}
                </span>
              </td>

              <!-- Actions -->
              <td
                class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                @click.stop
              >
                <div class="flex items-center space-x-2">
                  <button
                    @click="$emit('view-application', application)"
                    class="inline-flex items-center justify-center min-w-[32px] min-h-[32px] px-2 text-blue-600 hover:text-blue-900 hover:bg-blue-100 rounded-lg transition-colors border border-blue-300 bg-blue-50 shadow-sm"
                    title="View Details"
                  >
                    <Eye :size="16" />
                  </button>
                  <button
                    v-if="application.canEdit"
                    @click="$emit('edit-application', application)"
                    class="inline-flex items-center justify-center w-8 h-8 text-green-600 hover:text-green-900 hover:bg-green-50 rounded-lg transition-colors border border-green-200"
                    title="Edit Application"
                  >
                    <Edit :size="16" />
                  </button>
                  <button
                    v-if="canCancel(application)"
                    @click.stop="confirmWithdraw(application)"
                    class="inline-flex items-center justify-center min-w-[32px] min-h-[32px] px-2 text-red-600 hover:text-red-900 hover:bg-red-100 rounded-lg transition-colors border border-red-300 bg-red-50 shadow-sm"
                    title="Cancel Application"
                  >
                    <Trash2 :size="16" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="filteredApplications.length > itemsPerPage"
        class="px-6 py-3 bg-gray-50 border-t"
      >
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            Showing {{ startIndex + 1 }} to {{ endIndex }} of
            {{ filteredApplications.length }} applications
          </div>
          <div class="flex space-x-2">
            <button
              @click="currentPage--"
              :disabled="currentPage === 1"
              class="px-3 py-1 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="currentPage = page"
              :class="[
                'px-3 py-1 text-sm border rounded',
                currentPage === page
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'hover:bg-gray-100',
              ]"
            >
              {{ page }}
            </button>
            <button
              @click="currentPage++"
              :disabled="currentPage === totalPages"
              class="px-3 py-1 text-sm border rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import {
  Plus,
  RefreshCw,
  Search,
  Briefcase,
  Eye,
  Edit,
  Trash2,
  Clock,
  Star,
  CheckCircle,
  XCircle,
  Ban,
  HelpCircle,
} from "lucide-vue-next";
import { ElMessageBox } from "element-plus";
import JobListings from "./JobListings.vue";

// Props
const props = defineProps({
  applications: {
    type: Array,
    required: true,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  applicantId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits([
  "view-application",
  "edit-application",
  "withdraw-application",
  "new-application",
  "refresh",
  "go-to-pds",
]);

const showJobListings = ref(false);

const handleApplicationSubmitted = () => {
  showJobListings.value = false;
  emit("refresh");
};

const handleGoToPds = () => {
  showJobListings.value = false;
  emit("go-to-pds");
};

const searchQuery = ref("");
const statusFilter = ref("");
const departmentFilter = ref("");
const currentPage = ref(1);
const itemsPerPage = ref(10);
const activeDropdown = ref(null);

// Dynamic list of department options based on current applications
const departmentOptions = computed(() => {
  const set = new Set();
  (props.applications || []).forEach((app) => {
    if (app.department && typeof app.department === "string") {
      set.add(app.department);
    }
  });
  return Array.from(set).sort((a, b) => a.localeCompare(b));
});

const filteredApplications = computed(() => {
  let filtered = props.applications;

  if (searchQuery.value) {
    filtered = filtered.filter(
      (app) =>
        app.position.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        app.department
          .toLowerCase()
          .includes(searchQuery.value.toLowerCase()) ||
        app.jobCode.toLowerCase().includes(searchQuery.value.toLowerCase()),
    );
  }

  if (statusFilter.value) {
    filtered = filtered.filter((app) => app.status === statusFilter.value);
  }

  if (departmentFilter.value) {
    filtered = filtered.filter(
      (app) => app.department === departmentFilter.value,
    );
  }

  return filtered;
});

const totalPages = computed(() =>
  Math.ceil(filteredApplications.value.length / itemsPerPage.value),
);

const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value);

const endIndex = computed(() =>
  Math.min(
    startIndex.value + itemsPerPage.value,
    filteredApplications.value.length,
  ),
);

const paginatedApplications = computed(() =>
  filteredApplications.value.slice(
    startIndex.value,
    startIndex.value + itemsPerPage.value,
  ),
);

const visiblePages = computed(() => {
  const pages = [];
  const maxVisible = 5;
  const halfVisible = Math.floor(maxVisible / 2);

  let startPage = Math.max(1, currentPage.value - halfVisible);
  let endPage = Math.min(totalPages.value, startPage + maxVisible - 1);

  if (endPage - startPage + 1 < maxVisible) {
    startPage = Math.max(1, endPage - maxVisible + 1);
  }

  for (let i = startPage; i <= endPage; i++) {
    pages.push(i);
  }

  return pages;
});

const canCancel = (application) => {
  const status = (application.status || "").toString().toLowerCase();
  // Hide cancel only for clearly final states
  if (
    status === "withdrawn" ||
    status === "for hiring" ||
    status === "accepted"
  ) {
    return false;
  }
  return true;
};

const formatDate = (date) => {
  if (!date) return "";
  return new Date(date).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const formatStatus = (status) => {
  const statusMap = {
    pending: "Pending",
    "under-review": "Under Review",
    shortlisted: "Shortlisted",
    rejected: "Rejected",
    "not-qualified": "Not Qualified",
    "will-not-proceed": "Will not proceed",
    accepted: "Accepted",
    withdrawn: "Withdrawn",
    active: "Active",
    "for hiring": "For Hiring",
  };
  return statusMap[status] || status;
};

const getStatusClass = (status) => {
  const statusClasses = {
    pending: "bg-yellow-100 text-yellow-800",
    "under-review": "bg-blue-100 text-blue-800",
    shortlisted: "bg-green-100 text-green-800",
    rejected: "bg-red-100 text-red-800",
    "not-qualified": "bg-red-100 text-red-800",
    "will-not-proceed": "bg-red-100 text-red-800",
    accepted: "bg-emerald-100 text-emerald-800",
    withdrawn: "bg-gray-100 text-gray-800",
    active: "bg-blue-100 text-blue-800",
    "for hiring": "bg-emerald-100 text-emerald-800",
  };
  return statusClasses[status] || "bg-gray-100 text-gray-800";
};

const getStatusIcon = (status) => {
  const statusIcons = {
    pending: Clock,
    "under-review": Search,
    shortlisted: Star,
    rejected: XCircle,
    "not-qualified": XCircle,
    "will-not-proceed": XCircle,
    accepted: CheckCircle,
    withdrawn: Ban,
    active: CheckCircle,
    "for hiring": CheckCircle,
  };
  return statusIcons[status] || HelpCircle;
};

const confirmWithdraw = async (application) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to withdraw your application for ${application.position}?`,
      "Withdraw Application",
      {
        confirmButtonText: "Withdraw",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );
    emit("withdraw-application", application);
  } catch {}
};

const refreshData = () => {
  emit("refresh");
};

const closeDropdowns = () => {
  activeDropdown.value = null;
};

onMounted(() => {
  document.addEventListener("click", closeDropdowns);
});

onUnmounted(() => {
  document.removeEventListener("click", closeDropdowns);
});

// Reset pagination when filters change
const resetPagination = () => {
  currentPage.value = 1;
};

// Watch for filter changes
watch([searchQuery, statusFilter, departmentFilter], resetPagination);
</script>

<style scoped>
/* Custom scrollbar */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
