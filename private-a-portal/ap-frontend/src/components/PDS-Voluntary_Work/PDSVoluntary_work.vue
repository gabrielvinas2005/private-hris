<template>
  <div class="pds-voluntary-work">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
      Voluntary Work or Involvement in Civic / Non-Government / People /
      Voluntary Organization/s
    </h3>

    <div class="space-y-6">
      <!-- Voluntary Work Entries Section -->
      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div
          class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200 rounded-t-lg"
        >
          <div class="flex justify-between items-center">
            <h4
              class="text-lg font-semibold text-gray-900 inline-flex items-center"
            >
              <Heart :size="20" class="mr-2 text-indigo-600" /> Voluntary Work
              Records
            </h4>
            <button
              @click="addVoluntaryWork"
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
              Add Voluntary Work
            </button>
          </div>
        </div>

        <div class="p-6">
          <div
            v-if="loading && voluntaryWorkData.length === 0"
            class="text-center py-8 text-gray-500"
          >
            <div
              class="inline-flex items-center justify-center w-12 h-12 mb-4 bg-blue-100 rounded-full"
            >
              <div
                class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"
              ></div>
            </div>
            <p>Loading voluntary work records...</p>
          </div>

          <div
            v-else-if="voluntaryWorkData.length === 0"
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
                d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
              ></path>
            </svg>
            <p>
              No voluntary work records added yet. Click "Add Voluntary Work" to
              get started.
            </p>
          </div>

          <div v-else class="space-y-6">
            <div
              v-for="(voluntaryWork, index) in voluntaryWorkData"
              :key="voluntaryWork.organization_id || voluntaryWork.id"
              class="bg-gray-50 rounded-lg p-6 border border-gray-200"
            >
              <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center"
                  >
                    <span class="text-emerald-600 font-semibold text-sm">{{
                      index + 1
                    }}</span>
                  </div>
                  <h5 class="text-lg font-medium text-gray-800">
                    {{ voluntaryWork.organization || "Voluntary Work Record" }}
                  </h5>
                </div>
                <button
                  @click="removeVoluntaryWork(index)"
                  class="text-red-600 hover:text-red-800 transition-colors duration-200 p-1 rounded-full hover:bg-red-50"
                  title="Remove voluntary work record"
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

              <!-- Organization Name -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Name of Organization *</label
                >
                <input
                  v-model="voluntaryWork.organization"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., Philippine Red Cross - Manila Chapter"
                  required
                />
              </div>

              <!-- Organization Address -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Address of Organization</label
                >
                <textarea
                  v-model="voluntaryWork.organizationAddress"
                  rows="2"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., 123 Main Street, Manila, Philippines"
                ></textarea>
              </div>

              <!-- Period of Service -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Inclusive Dates of Service</label
                >
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >From *</label
                    >
                    <input
                      v-model="voluntaryWork.dateFrom"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      @change="calculateServiceHours(voluntaryWork)"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >To</label
                    >
                    <input
                      v-model="voluntaryWork.dateTo"
                      type="date"
                      :disabled="voluntaryWork.isOngoing"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                      @change="calculateServiceHours(voluntaryWork)"
                      :min="voluntaryWork.dateFrom"
                    />
                  </div>
                  <div class="flex items-end">
                    <label class="flex items-center space-x-2 text-sm">
                      <input
                        v-model="voluntaryWork.isOngoing"
                        type="checkbox"
                        @change="handleOngoingChange(voluntaryWork)"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                      />
                      <span class="text-gray-700">Ongoing</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Hours and Position -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Number of Hours *</label
                  >
                  <div class="relative">
                    <input
                      v-model.number="voluntaryWork.hours"
                      type="number"
                      step="0.5"
                      min="0.5"
                      class="w-full px-3 py-2 pr-12 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="40"
                      required
                    />
                    <span class="absolute right-3 top-2 text-xs text-gray-400"
                      >hours</span
                    >
                  </div>
                  <p class="text-xs text-gray-500 mt-1">
                    Total volunteer hours contributed
                  </p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2"
                    >Position/Nature of Work *</label
                  >
                  <input
                    v-model="voluntaryWork.position"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g., Volunteer Teacher, Board Member, Event Coordinator"
                    required
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
          @click="saveVoluntaryWork"
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
          {{ loading ? "Saving..." : "Save Voluntary Work Information" }}
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
import { Heart } from "lucide-vue-next";

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});

const notify = useAppNotification();

const voluntaryWorkData = reactive([]);
const loading = ref(false);
const error = ref(null);
const employeeId = ref(null);
let nextVoluntaryWorkId = 1;

const addVoluntaryWork = () => {
  voluntaryWorkData.push({
    id: nextVoluntaryWorkId++,
    organization_id: null,
    organization: "",
    organizationAddress: "",
    dateFrom: "",
    dateTo: "",
    isOngoing: false,
    hours: "",
    position: "",
    organizationType: "",
    description: "",
  });
};

const removeVoluntaryWork = async (index) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to remove this voluntary work record?",
      "Remove Voluntary Work",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
  } catch {
    return;
  }

  const voluntaryWork = voluntaryWorkData[index];
  const organizationId = voluntaryWork.organization_id;

  // Remove from UI immediately for better UX
  voluntaryWorkData.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Voluntary work record removed successfully!",
    type: "success",
    duration: 2000,
    position: "top-right",
  });

  // If it's an existing record, delete from backend (type_id 7 for organizations)
  if (organizationId) {
    try {
      await ApiService.destroyPDS(7, organizationId);
    } catch (err) {
      const status = err.response?.status;

      if (status && status >= 400 && status < 500) {
        voluntaryWorkData.splice(index, 0, voluntaryWork);
        ElNotification({
          title: "Error",
          message:
            "Failed to delete voluntary work record from server. Please try again.",
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

const handleOngoingChange = (voluntaryWork) => {
  if (voluntaryWork.isOngoing) {
    voluntaryWork.dateTo = "";
  }
};

const calculateServiceHours = (voluntaryWork) => {
  if (voluntaryWork.dateFrom && voluntaryWork.dateTo) {
    const fromDate = new Date(voluntaryWork.dateFrom);
    const toDate = new Date(voluntaryWork.dateTo);

    if (toDate >= fromDate) {
      const diffTime = Math.abs(toDate - fromDate);
      const diffMonths = Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 30));

      // Estimate 4 hours per month for volunteer work (conservative estimate)
      const estimatedHours = diffMonths * 4;

      // Only auto-fill if hours is empty
      if (!voluntaryWork.hours && estimatedHours > 0) {
        voluntaryWork.hours = estimatedHours;
      }
    }
  }
};

const isFormValid = computed(() => {
  if (voluntaryWorkData.length === 0) return false;

  return voluntaryWorkData.every(
    (voluntaryWork) =>
      voluntaryWork.organization &&
      voluntaryWork.dateFrom &&
      voluntaryWork.hours &&
      voluntaryWork.position &&
      (voluntaryWork.isOngoing || voluntaryWork.dateTo)
  );
});

const loadVoluntaryWorkData = async () => {
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

      if (data.organizations && Array.isArray(data.organizations)) {
        voluntaryWorkData.splice(0, voluntaryWorkData.length);

        data.organizations.forEach((org) => {
          // Check if org_to is null to determine if ongoing
          const isOngoing = !org.org_to || org.org_to === null;

          voluntaryWorkData.push({
            id: nextVoluntaryWorkId++,
            organization_id: org.organization_id || null,
            organization: org.organization || "",
            organizationAddress: org.organization_address || "",
            dateFrom: org.org_from || "",
            dateTo: org.org_to || "",
            isOngoing: isOngoing,
            hours: org.org_hours ? parseFloat(org.org_hours) : "", // Convert to number when loading
            position: org.org_position || "",
            organizationType: org.organization_type || "", // May not exist in DB
            description: org.description || "", // May not exist in DB
          });
        });
      }
    }
  } catch (err) {
    console.error("Error loading voluntary work data:", err);
    error.value =
      err.response?.data?.message || "Failed to load voluntary work data";
  } finally {
    loading.value = false;
  }
};

const totalHours = computed(() => {
  const total = voluntaryWorkData.reduce((sum, voluntaryWork) => {
    // Convert hours to number, default to 0 if invalid
    const hours = parseFloat(voluntaryWork.hours) || 0;
    return sum + hours;
  }, 0);

  // Round to 2 decimal places
  return Math.round(total * 100) / 100;
});

const ongoingCount = computed(() => {
  return voluntaryWorkData.filter((voluntaryWork) => voluntaryWork.isOngoing)
    .length;
});

const yearsOfService = computed(() => {
  if (voluntaryWorkData.length === 0) return 0;

  const dates = voluntaryWorkData
    .map((voluntaryWork) => {
      const fromYear = voluntaryWork.dateFrom
        ? new Date(voluntaryWork.dateFrom).getFullYear()
        : null;
      const toYear = voluntaryWork.dateTo
        ? new Date(voluntaryWork.dateTo).getFullYear()
        : new Date().getFullYear();
      return { from: fromYear, to: toYear };
    })
    .filter((date) => date.from);

  if (dates.length === 0) return 0;

  const earliestYear = Math.min(...dates.map((d) => d.from));
  const latestYear = Math.max(...dates.map((d) => d.to));

  return latestYear - earliestYear + 1;
});

const saveVoluntaryWork = async () => {
  if (!isFormValid.value) {
    notify.error(
      "Validation",
      "Please fill in all required fields (Organization, From Date, Hours, Position, and To Date or mark as Ongoing)",
      { duration: 4000 },
    );
    return;
  }

  // Validate date ranges
  const invalidDates = voluntaryWorkData.filter(
    (voluntaryWork) =>
      voluntaryWork.dateFrom &&
      voluntaryWork.dateTo &&
      new Date(voluntaryWork.dateTo) < new Date(voluntaryWork.dateFrom)
  );

  if (invalidDates.length > 0) {
    notify.error(
      "Validation",
      "To Date must be equal to or after From Date for all voluntary work records",
      {
        duration: 4000,
      }
    );
    return;
  }

  // Validate hours
  const invalidHours = voluntaryWorkData.filter(
    (voluntaryWork) => voluntaryWork.hours && voluntaryWork.hours < 0.5
  );

  if (invalidHours.length > 0) {
    notify.error("Validation", "Volunteer hours must be at least 0.5 hours", {
      duration: 4000,
    });
    return;
  }

  try {
    loading.value = true;

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

    // Prepare organizations data for API - send each organization individually
    const formData = new FormData();
    formData.append("section", "voluntary");

    // Send organizations as JSON string in form data (backend will decode)
    const organizationsPayload = voluntaryWorkData.map((voluntaryWork) => ({
      organization_id: voluntaryWork.organization_id || null,
      organization: voluntaryWork.organization || "",
      organizationAddress: voluntaryWork.organizationAddress || "",
      dateFrom: voluntaryWork.dateFrom || "",
      dateTo: voluntaryWork.isOngoing ? "" : voluntaryWork.dateTo || "", // Empty if ongoing
      hours: voluntaryWork.hours || 0,
      position: voluntaryWork.position || "",
      isOngoing: voluntaryWork.isOngoing || false,
      organizationType: voluntaryWork.organizationType || "", // May not be saved to DB
      description: voluntaryWork.description || "", // May not be saved to DB
    }));

    formData.append("organizations", JSON.stringify(organizationsPayload));

    await ApiService.storePDS(resolvedEmployeeId, formData);

    notify.success("Saved", "Voluntary work information saved successfully!", {
      duration: 4000,
    });
    await loadVoluntaryWorkData();
  } catch (err) {
    console.error("Error saving voluntary work data:", err);
    const msg =
      err.response?.data?.message ||
      (err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(" ")
        : null) ||
      err.message ||
      "Failed to save voluntary work information. Please try again.";
    notify.error("Save failed", msg, { duration: 6000 });
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await loadVoluntaryWorkData();
  // Only add empty record if no data was loaded and not still loading
  // if (voluntaryWorkData.length === 0 && !loading.value) {
  //   addVoluntaryWork();
  // }
});
</script>

<style scoped>
/* Custom styles for better visual hierarchy */
.voluntary-work-card {
  transition: all 0.2s ease-in-out;
}

.voluntary-work-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Custom checkbox styling */
input[type="checkbox"]:checked {
  background-color: #3b82f6;
  border-color: #3b82f6;
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

/* Textarea styling */
textarea {
  resize: vertical;
  min-height: 2.5rem;
}
</style>
