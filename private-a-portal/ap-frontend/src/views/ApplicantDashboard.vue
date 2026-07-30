<template>
  <div>
    <!-- Loading State -->
    <div
      v-if="isLoading"
      class="min-h-screen flex items-center justify-center bg-gray-50"
    >
      <div class="text-center">
        <div
          class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"
        ></div>
        <p class="mt-4 text-gray-600">Loading dashboard...</p>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-else-if="error"
      class="min-h-screen flex items-center justify-center bg-gray-50"
    >
      <div class="text-center max-w-md mx-auto">
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
          <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
          <h3 class="text-lg font-semibold text-red-800 mb-2">
            Error Loading Dashboard
          </h3>
          <p class="text-red-700 mb-4">{{ error }}</p>
          <div class="space-y-3">
            <button
              @click="loadApplicantData"
              class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
            >
              Try Again
            </button>
            <router-link
              v-if="error.includes('No applicant profile')"
              to="/registration"
              class="block w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              Complete Registration
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Dashboard -->
    <div v-else class="min-h-screen bg-gray-70">
      <DashboardHeader
        :user="applicantData"
        :notifications="notifications"
        :notification-badge="totalUnreadNotifications"
        @logout="handleLogout"
        @profile-click="handleProfileClick"
        @settings-click="handleSettingsClick"
        @notification-click="handleNotificationClick"
      />

      <TabNavigation
        :tabs="tabs"
        :active-tab="activeTab"
        @tab-change="activeTab = $event"
      />

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'">
          <DashboardStats
            :stats="dashboardStats"
            :loading="statsLoading"
            @stat-click="handleStatClick"
          />

          <!-- PDS Completion Status -->
          <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-light">Personal Data Sheet (PDS) Status</h3>
              <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-600"
                  >{{ pdsStatus.completionPercentage }}% Complete</span
                >
                <div class="w-24 bg-gray-200 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all duration-300"
                    :class="
                      pdsStatus.isComplete ? 'bg-green-500' : 'bg-blue-500'
                    "
                    :style="{ width: pdsStatus.completionPercentage + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <div v-if="!pdsStatus.isComplete" class="mb-4">
              <p class="text-sm text-gray-600 mb-2">
                Complete the following sections to apply for positions:
              </p>
              <ul class="text-sm text-gray-600 space-y-1">
                <li
                  v-for="section in pdsStatus.missingSections"
                  :key="section"
                  class="flex items-center"
                >
                  <i class="fas fa-times text-red-500 mr-2 text-xs"></i>
                  {{ section }}
                </li>
              </ul>
            </div>

            <div v-else class="mb-4">
              <div class="flex items-center text-green-600 mb-2">
                <CircleCheckBig :size="18" class="mr-2" />
                <span class="text-sm font-medium"
                  >PDS Complete! You can now apply for positions.</span
                >
              </div>
              <div v-if="eligibilityStatus.hasEligibilityRequired" class="mt-3">
                <div
                  v-if="eligibilityStatus.hasRequiredEligibility"
                  class="flex items-center text-green-600"
                >
                  <CircleCheckBig :size="16" class="mr-2" />
                  <span class="text-sm font-medium"
                    >Eligibility requirements met.</span
                  >
                </div>
                <!-- <div v-else class="flex items-start text-amber-600">
                  <svg
                    class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                  </svg>
                   <div class="flex-1">
                    <span class="text-sm font-medium block mb-1"
                      >Eligibility Required</span
                    >
                     <p
                      v-if="eligibilityStatus.hasEligibility"
                      class="text-xs text-gray-600 mb-1"
                    >
                      Some positions require specific eligibility that you may
                      not have. Please add the required eligibility in your PDS
                      to apply for those positions.
                    </p>
                    <p v-else class="text-xs text-gray-600 mb-1">
                      Some positions require specific eligibility. Please add
                      your eligibility in your PDS to apply for those positions.
                    </p> -->
                    <!-- <p
                      v-if="eligibilityStatus.requiredEligibilities.length > 0"
                      class="text-xs text-gray-500 mt-1"
                    >
                      Required:
                      {{
                        eligibilityStatus.requiredEligibilities
                          .slice(0, 3)
                          .join(", ")
                      }}{{
                        eligibilityStatus.requiredEligibilities.length > 3
                          ? "..."
                          : ""
                      }}
                    </p> -->
                  <!-- </div>  -->
                <!-- </div> -->
              </div>

              <!-- Document Upload Status -->
              <div class="mt-3">
                <div
                  v-if="documentStatus.hasDocuments"
                  class="flex items-center text-green-600"
                >
                  <CircleCheckBig :size="16" class="mr-2" />
                  <span class="text-sm font-medium"
                    >Documents uploaded ({{ documentStatus.documentCount }}
                    {{
                      documentStatus.documentCount === 1
                        ? "document"
                        : "documents"
                    }}).</span
                  >
                </div>
                <div v-else class="flex items-start text-amber-600">
                  <svg
                    class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                  </svg>
                  <div class="flex-1">
                    <span class="text-sm font-medium block mb-1"
                      >Documents Required</span
                    >
                    <p class="text-xs text-gray-600 mb-1">
                      You must upload at least one document before applying to
                      positions. Please upload your required documents in your
                      PDS.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex space-x-3">
              <!-- <router-link
                to="/positions"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium"
                :class="{
                  'opacity-50 cursor-not-allowed': !pdsStatus.isComplete,
                }"
                :disabled="!pdsStatus.isComplete"
                hidden
              >
                <i class="fas fa-briefcase mr-2"></i>
                Browse Positions
              </router-link> -->
              <button
                @click="activeTab = 'personal_information'"
                class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm font-medium"
              >
                <i class="fas fa-edit mr-2"></i>
                Complete PDS
              </button>
            </div>
          </div>

          <ActivityList
            :activities="recentActivities"
            :loading="activitiesLoading"
            :max-items="5"
            :show-view-all="recentActivities.length > 0"
            @activity-click="handleActivityClick"
            @view-all="handleViewAllActivities"
          />
        </div>

        <!-- Applications Tab -->
        <div v-if="activeTab === 'applications'">
          <ApplicationsTable
            :applications="applications"
            :loading="applicationsLoading"
            :applicant-id="applicantData.id ? Number(applicantData.id) : null"
            @view-application="handleViewApplication"
            @edit-application="handleEditApplication"
            @withdraw-application="handleWithdrawApplication"
            @new-application="handleNewApplication"
            @refresh="refreshApplications"
            @go-to-pds="handleGoToPds"
          />

          <!-- Application Details Modal -->
          <ApplicationDetailsModal
            :application="selectedApplication"
            :withdrawing="withdrawingApplication"
            @close="selectedApplication = null"
            @withdraw="handleWithdrawApplication"
          />
        </div>

        <!-- PDS Tabs -->
        <div v-if="activeTab === 'personal_information'">
          <PDSPersonalInformation :info="applicantData" />
        </div>

        <div v-if="activeTab === 'questionnaire'">
          <PDSQuestionnaire :info="applicantData" />
        </div>

        <div v-if="activeTab === 'Family'">
          <PDSFamily :info="applicantData" />
        </div>

        <div v-if="activeTab === 'Education'">
          <PDSEducation :info="applicantData" @saved="loadApplicantData" />
        </div>

        <div v-if="activeTab === 'work_experience'">
          <PDSWorkExperience :info="applicantData" @saved="loadApplicantData" />
        </div>

        <div v-if="activeTab === 'eligibility'">
          <PDSEligibility :info="applicantData" @saved="loadApplicantData" />
        </div>

        <div v-if="activeTab === 'Trainings'">
          <PDSTrainings :info="applicantData" @saved="loadApplicantData" />
        </div>

        <div v-if="activeTab === 'Recognitions'">
          <PDSRecognitions :info="applicantData" />
        </div>

        <div v-if="activeTab === 'Skills'">
          <PDSSkills :info="applicantData" />
        </div>

        <div v-if="activeTab === 'Voluntary_Work'">
          <PDSVoluntaryWork :info="applicantData" />
        </div>

        <div v-if="activeTab === 'Reference'">
          <PDSReferences :info="applicantData" @saved="loadApplicantData" />
        </div>

        <div v-if="activeTab === 'Dependents'">
          <PDSDependents :info="applicantData" />
        </div>

        <div v-if="activeTab === 'Documents'">
          <PDSDocument :info="applicantData" @documents-updated="loadApplicantData" />
        </div>
        <div v-if="activeTab === 'Examinations'">
          <ExaminationsTable
            :examinations="examinations"
            :loading="examinationsLoading"
            @take-exam="handleTakeExam"
            @submit-exam="handleSubmitExam"
            @view-results="handleViewResults"
            @view-details="handleViewExamDetails"
            @refresh="refreshExaminations"
          />
        </div>
        <div v-if="activeTab === 'Interviews'">
          <InterviewsTable
            :interviews="interviews"
            :loading="interviewsLoading"
            @join-interview="handleJoinInterview"
            @view-details="handleViewInterviewDetails"
            @cancel-interview="handleCancelInterview"
            @refresh="refreshInterviews"
          />
        </div>

        <!-- Job Offers Tab -->
        <div v-if="activeTab === 'job_offers'">
          <JobOffers
            :job-offers="jobOffers"
            :loading="applicationsLoading"
            @accept-offer="handleAcceptOffer"
            @reject-offer="handleRejectOffer"
            @refresh="refreshApplications"
          />
        </div>
      </div>
    </div>

    <!-- Modals that overlay on top of dashboard -->
    <InterviewDetailsModal
      :interview="selectedInterview"
      @close="selectedInterview = null"
      @join-meeting="handleJoinInterview"
    />

    <!-- All Activities Modal -->
    <div
      v-if="showActivitiesModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[80vh] flex flex-col"
      >
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="text-lg font-semibold">All Recent Activities</h3>
          <button
            class="text-gray-500 hover:text-gray-700"
            @click="showActivitiesModal = false"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="flex-1 overflow-y-auto">
          <ActivityList
            :activities="recentActivities"
            :loading="activitiesLoading"
            :max-items="recentActivities.length"
            :show-view-all="false"
            @activity-click="handleActivityClick"
          />
        </div>
        <div class="px-6 py-3 border-t flex justify-end">
          <button
            class="px-4 py-2 rounded-lg bg-gray-600 text-white hover:bg-gray-700 text-sm"
            @click="showActivitiesModal = false"
          >
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- Progress Details Modal -->
    <div
      v-if="showProgressModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[80vh] flex flex-col"
      >
        <div class="flex items-center justify-between px-6 py-4 border-b">
          <h3 class="text-lg font-semibold">Application Progress</h3>
          <button
            class="text-gray-500 hover:text-gray-700"
            @click="showProgressModal = false"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
          <div
            v-for="(step, index) in applicantProgress.steps"
            :key="step.key || index"
            class="flex items-start justify-between border rounded-lg px-4 py-3"
          >
            <div>
              <div class="flex items-center space-x-2">
                <span
                  class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-semibold bg-gray-100 text-gray-700"
                >
                  {{ index + 1 }}
                </span>
                <span class="font-medium text-gray-800">{{ step.label }}</span>
              </div>
              <p class="text-xs text-gray-500 mt-1">
                <span class="font-semibold capitalize">{{ step.state }}</span>
                <span v-if="step.date || step.time">
                  ·
                  {{ step.date || "Not scheduled yet" }}
                  <span v-if="step.time"> {{ step.time }}</span>
                </span>
              </p>
            </div>
          </div>
          <p
            v-if="
              !applicantProgress.steps || applicantProgress.steps.length === 0
            "
            class="text-sm text-gray-500"
          >
            No progress data available yet.
          </p>
        </div>
        <div class="px-6 py-3 border-t flex justify-end">
          <button
            class="px-4 py-2 rounded-lg bg-gray-600 text-white hover:bg-gray-700 text-sm"
            @click="showProgressModal = false"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { useRouter } from "vue-router";
import { ApiService } from "@/services/api.js";
import { ElNotification, ElMessageBox } from "element-plus";
import { CircleCheckBig } from "lucide-vue-next";

// Import dashboard components
import DashboardHeader from "@/components/Dashboard/DashboardHeader.vue";
import DashboardStats from "@/components/Dashboard/DashboardStats.vue";
import TabNavigation from "@/components/Dashboard/TabNavigation.vue";
import ActivityList from "@/components/Dashboard/ActivityList.vue";
import ApplicationsTable from "@/components/Dashboard/ApplicationsTable.vue";
import ApplicationDetailsModal from "@/components/Dashboard/ApplicationDetailsModal.vue";
import ExaminationsTable from "@/components/Examination/ExaminationsTable.vue";
import InterviewsTable from "@/components/Interview/InterviewsTable.vue";
import InterviewDetailsModal from "@/components/Interview/InterviewDetailsModal.vue";
import JobOffers from "@/components/JobOffer/JobOffers.vue";

// Import PDS components
import PDSPersonalInformation from "@/components/PDS-Personal_Information/PDSPersonal_information.vue";
import PDSEducation from "@/components/PDS-Education/PDSEducation.vue";
import PDSDocument from "@/components/PDS-Documents/PDSDocument.vue";
import PDSFamily from "@/components/PDS-Family/PDSFamily.vue";
import PDSEligibility from "@/components/PDS-Eligibility/PDSEligibility.vue";
import PDSRecognitions from "@/components/PDS-Recognitions/PDSRecognitions.vue";
import PDSReferences from "@/components/PDS-References/PDSReferences.vue";
import PDSSkills from "@/components/PDS-Skills/PDSSkills.vue";
import PDSTrainings from "@/components/PDS-Trainings/PDSTrainings.vue";
import PDSVoluntaryWork from "@/components/PDS-Voluntary_Work/PDSVoluntary_work.vue";
import PDSWorkExperience from "@/components/PDS-Work_Experience/PDSWork_Experience.vue";
import PDSDependents from "@/components/PDS-Dependents/PDSDependents.vue";
import PDSQuestionnaire from "@/components/PDS-Questionnaire/PDSQuestionnaire.vue";

const router = useRouter();

// Reactive data
const activeTab = ref("overview");
const statsLoading = ref(false);
const activitiesLoading = ref(false);
const applicationsLoading = ref(false);
const examinationsLoading = ref(false);
const interviewsLoading = ref(false);
const selectedApplication = ref(null);
const withdrawingApplication = ref(false);
const selectedInterview = ref(null);
const examinations = ref([]);
const interviews = ref([]);
const applications = ref([]);
const notifications = ref([]);
const recentActivities = ref([]);
const showActivitiesModal = ref(false);

const applicantData = ref({
  name: "",
  email: "",
  applicantNo: "",
  id: null,
});

const isLoading = ref(false);
const error = ref(null);
const pdsStatus = ref({
  isComplete: false,
  completionPercentage: 0,
  missingSections: [],
});

const eligibilityStatus = ref({
  hasEligibility: false,
  hasEligibilityRequired: false,
  requiredEligibilities: [],
});

const documentStatus = ref({
  hasDocuments: false,
  documentCount: 0,
});

const applicantProgress = ref({
  steps: [],
});

const showProgressModal = ref(false);

const unreadNotificationCounts = ref({
  application: 0,
  exam: 0,
  interview: 0,
});

const totalUnreadNotifications = computed(
  () =>
    unreadNotificationCounts.value.application +
    unreadNotificationCounts.value.exam +
    unreadNotificationCounts.value.interview,
);

const tabs = ref([
  {
    id: "overview",
    name: "Overview",
    icon: "fas fa-home",
  },
  {
    id: "applications",
    name: "Applications",
    icon: "fas fa-file-alt",
    badge: 0,
  },
  {
    id: "job_offers",
    name: "Job Offers",
    icon: "fas fa-briefcase",
  },
  {
    id: "personal_information",
    name: "Personal Information",
    icon: "fas fa-user",
  },
  {
    id: "questionnaire",
    name: "PDS Questionnaire",
    icon: "fas fa-clipboard-question",
  },
  {
    id: "Family",
    name: "Family Information",
    icon: "fas fa-users",
  },
  {
    id: "Education",
    name: "Educational Background",
    icon: "fas fa-graduation-cap",
  },
  {
    id: "work_experience",
    name: "Work Experience",
    icon: "fas fa-briefcase",
  },
  {
    id: "eligibility",
    name: "Eligibility",
    icon: "fas fa-file-alt",
  },
  {
    id: "Trainings",
    name: "Trainings ",
    icon: "fas fa-graduation-cap",
  },
  {
    id: "Voluntary_Work",
    name: "Voluntary Work",
    icon: "fas fa-hands-helping",
  },
  {
    id: "Recognitions",
    name: "Recognitions",
    icon: "fas fa-award",
  },
  {
    id: "Skills",
    name: "Skills",
    icon: "fas fa-cogs",
  },
  {
    id: "Reference",
    name: "Reference",
    icon: "fas fa-user-friends",
  },
  {
    id: "Dependents",
    name: "Dependents",
    icon: "fas fa-users",
  },
  {
    id: "Documents",
    name: "Documents",
    icon: "fas fa-file-alt",
  },
  {
    id: "Examinations",
    name: "Examinations",
    icon: "fas fa-clipboard-check",
    badge: 0,
  },
  {
    id: "Interviews",
    name: "Interviews",
    icon: "fas fa-video",
    badge: 0,
  },
]);

const dashboardStats = ref([
  {
    id: 1,
    label: "Applications",
    value: "0",
    icon: "fas fa-file-alt",
    cardBg: "bg-blue-50",
    bgColor: "bg-blue-500",
    textColor: "text-white",
    valueColor: "text-blue-600",
    clickable: true,
  },
  {
    id: 2,
    label: "Exams Completed",
    value: "0",
    icon: "fas fa-check-circle",
    cardBg: "bg-emerald-50",
    bgColor: "bg-green-500",
    textColor: "text-white",
    valueColor: "text-emerald-600",
    clickable: true,
  },
  {
    id: 3,
    label: "Pending Reviews",
    value: "0",
    icon: "fas fa-clock",
    cardBg: "bg-amber-50",
    bgColor: "bg-amber-500",
    textColor: "text-white",
    valueColor: "text-amber-600",
    clickable: true,
  },
  {
    id: 4,
    label: "Current Progress",
    value: "Not started",
    icon: "fas fa-chart-bar",
    cardBg: "bg-purple-50",
    bgColor: "bg-purple-500",
    textColor: "text-white",
    valueColor: "text-purple-600",
    clickable: true,
  },
]);

const handleStatClick = (stat) => {
  if (!stat) return;

  // Applications
  if (stat.id === 1 || stat.id === 3) {
    activeTab.value = "applications";
    return;
  }

  // Examinations
  if (stat.id === 2) {
    activeTab.value = "Examinations";
    return;
  }

  // Current Progress
  if (stat.id === 4) {
    showProgressModal.value = true;
  }
};

const handleLogout = () => {
  localStorage.removeItem("auth_token");
  localStorage.removeItem("user_data");
  router.push("/login");
};

const handleProfileClick = () => {
  // Navigate to profile page or open modal
};

const handleSettingsClick = () => {
  // Navigate to settings page or open modal
};

const handleNotificationClick = (item) => {
  if (!item) return;

  const type = (item.type || "").toLowerCase();

  if (type === "application") {
    activeTab.value = "applications";
    unreadNotificationCounts.value.application = 0;
    ApiService.markNotificationsRead("application").catch(() => {});
    notifications.value = notifications.value.filter(
      (n) => (n.type || "").toLowerCase() !== "application",
    );
    updateTabBadges();
    return;
  }

  if (type === "exam") {
    activeTab.value = "Examinations";
    unreadNotificationCounts.value.exam = 0;
    ApiService.markNotificationsRead("exam").catch(() => {});
    notifications.value = notifications.value.filter(
      (n) => (n.type || "").toLowerCase() !== "exam",
    );
    updateTabBadges();
    return;
  }

  if (type === "interview") {
    activeTab.value = "Interviews";
    unreadNotificationCounts.value.interview = 0;
    ApiService.markNotificationsRead("interview").catch(() => {});
    notifications.value = notifications.value.filter(
      (n) => (n.type || "").toLowerCase() !== "interview",
    );
    updateTabBadges();
  }
};

const handleActivityClick = (activity) => {
  if (!activity) return;

  const type = activity.metadata?.type || "";
  const normalizedType = typeof type === "string" ? type.toLowerCase() : "";

  if (normalizedType === "application") {
    activeTab.value = "applications";
    return;
  }

  if (normalizedType === "examination") {
    activeTab.value = "Examinations";
    return;
  }

  if (normalizedType === "interview") {
    activeTab.value = "Interviews";
  }
};

const handleViewAllActivities = () => {
  // Open modal showing all recent activities
  showActivitiesModal.value = true;
};

const handleViewApplication = (application) => {
  selectedApplication.value = application;
};

const handleEditApplication = (application) => {
  // For now, just show the details modal
  selectedApplication.value = application;
};

const refreshExaminations = async () => {
  try {
    examinationsLoading.value = true;
    await loadApplicantData();
  } catch (error) {
    // Silent error handling
  } finally {
    examinationsLoading.value = false;
  }
};

const isThirdPartyExam = (exam) => {
  const haystack = `${exam?.exam_set || ""} ${exam?.exam_instruction || ""}`.toLowerCase();
  return /psych|psychometric|third[\s-]*party|external/.test(haystack);
};

const openThirdPartyExam = (exam, actionLabel = "Submit") => {
  const externalLink = extractUrlFromText(exam?.exam_instruction || "");
  if (externalLink) {
    window.open(externalLink, "_blank", "noopener,noreferrer");
    ElNotification({
      title: "External examination",
      message: `This assessment is administered by a third-party provider. We opened the link so you can ${actionLabel.toLowerCase()} there.`,
      type: "info",
      duration: 5500,
      position: "top-right",
    });
    return;
  }

  ElMessageBox.alert(
    "This assessment is administered by a third-party provider and is not available in the in-app examination page. Please check the exam details/instruction message from HR for the external exam link.",
    "External examination",
    {
      confirmButtonText: "OK",
      type: "info",
    },
  ).catch(() => {
    // Silently handle close/cancel
  });
};

const handleTakeExam = (exam) => {
  // Psych/third-party assessments are not hosted in the internal exam-page endpoint.
  if (isThirdPartyExam(exam)) {
    openThirdPartyExam(exam, "Take Exam");
    return;
  }

  const alreadyDone =
    exam.is_complete === 1 ||
    exam.is_complete === "1" ||
    exam.is_complete === true ||
    exam.status === "Completed" ||
    !!exam.date_completed;

  if (alreadyDone) {
    ElNotification({
      title: "Examination completed",
      message:
        "This examination was already submitted. Retakes are not permitted. Opening your results.",
      type: "info",
      duration: 6000,
      position: "top-right",
    });
    if (exam.applicant_examination_id) {
      router.push(`/exam-result/${exam.applicant_examination_id}`);
    }
    return;
  }

  // Navigate to exam page using exam.id (examination_schedule_header.id)
  // Route is /exam/:id, not /examination/:id
  router.push(`/exam/${exam.id}`);
};

const handleSubmitExam = async (exam) => {
  // Submit action for psych/third-party exams.
  if (isThirdPartyExam(exam)) {
    try {
      await ApiService.submitExternalExam(exam.applicant_examination_id);
      await refreshExaminations();
      ElNotification({
        title: "Submitted",
        message: "Psych examination marked as submitted.",
        type: "success",
        duration: 4500,
        position: "top-right",
      });
    } catch (err) {
      ElNotification({
        title: "Submit failed",
        message:
          err?.response?.data?.message ||
          "Failed to mark this psych examination as submitted.",
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      return;
    }

    openThirdPartyExam(exam, "Submit");
    return;
  }

  handleTakeExam(exam);
};

const handleViewResults = (exam) => {
  // Navigate to exam results page using applicant_examination_id
  router.push(`/exam-result/${exam.applicant_examination_id}`);
};

const handleViewExamDetails = async (exam) => {
  // Show exam details modal (including instruction) or navigate to details page
  const extractInstruction = (payload) => {
    const data = payload?.data ?? payload;

    // Most reliable: backend returns { exam: [ { exam_instruction: ... } ] }
    if (Array.isArray(data?.exam) && data.exam.length > 0) {
      return data.exam[0]?.exam_instruction ?? null;
    }

    // Fallbacks for unexpected nesting
    return (
      data?.exam_instruction ??
      data?.instruction ??
      data?.exam_intro ??
      data?.exam_setup_header?.exam_instruction ??
      null
    );
  };

  const normalizeInstruction = (value) => {
    if (value == null) return null;
    if (typeof value === "string" && value.trim().length === 0) return null;
    return value;
  };

  let instruction = normalizeInstruction(
    exam.exam_instruction || exam.instruction || exam.exam_intro || null,
  );

  // If the dashboard payload doesn't include the instruction, fetch it.
  if (!instruction || instruction === "N/A") {
    // 1) Try exam intro endpoint first
    try {
      const response = await ApiService.getExamIntro(exam.id);
      instruction = normalizeInstruction(
        extractInstruction(response?.data?.data ?? response?.data),
      );
    } catch (err) {
      // Ignore and try other endpoint(s) below.
    }

    // 2) If still missing, try the exam page endpoint and extract the instruction from it.
    if (!instruction || instruction === "N/A") {
      try {
        const response = await ApiService.getExamPage(exam.id);
        instruction = normalizeInstruction(
          extractInstruction(response?.data?.data ?? response?.data),
        );
      } catch (e) {
        // Keep whatever we have (likely null).
      }
    }
  }

  const details = `Exam Set: ${exam.exam_set}
Duration: ${exam.exam_duration} minutes
Passing Criteria: ${exam.passing_criteria}
Status: ${exam.status}

Instruction:
${instruction ?? "N/A"}`;

  ElMessageBox.alert(details, "Exam Details", {
    confirmButtonText: "Close",
    type: "info",
  }).catch(() => {
    // Silently handle cancellation/close
  });
};

const refreshInterviews = async () => {
  try {
    interviewsLoading.value = true;
    await loadApplicantData();
  } catch (error) {
    // Silent error handling
  } finally {
    interviewsLoading.value = false;
  }
};

// Helper function to extract URL from text
const extractUrlFromText = (text) => {
  if (!text) return null;

  // Regex to match URLs (http, https, zoom, teams, meet, etc.)
  const urlRegex =
    /(https?:\/\/[^\s]+|(?:zoom\.us|teams\.microsoft\.com|meet\.google\.com|webex\.com)[^\s]*)/gi;
  const matches = text.match(urlRegex);

  if (matches && matches.length > 0) {
    // Return the first URL found, ensuring it has http:// or https://
    let url = matches[0];
    if (!url.startsWith("http://") && !url.startsWith("https://")) {
      url = "https://" + url;
    }
    return url;
  }

  return null;
};

const handleJoinInterview = (interview) => {
  // Extract meeting link from description
  const meetingLink = extractUrlFromText(interview.description);

  if (meetingLink) {
    // Open meeting link in a new tab
    window.open(meetingLink, "_blank", "noopener,noreferrer");
  } else {
    // Fallback if no meeting link found in description
    const details = `Panel Group: ${interview.panel_group}
Level: ${interview.level}
Location: ${interview.interview_location}${interview.description ? `\n\nDescription:\n${interview.description}` : ""}

No meeting link found in the description. Please check the interview details or contact the recruiter.`;
    ElMessageBox.alert(details, "Join Interview", {
      confirmButtonText: "Close",
      type: "warning",
    }).catch(() => {
      // Silently handle cancellation/close
    });
  }
};

const handleViewInterviewDetails = (interview) => {
  selectedInterview.value = interview;
};

const handleCancelInterview = async (interview) => {
  if (!interview) return;

  const confirmCancel = await ElMessageBox.confirm(
    `Are you sure you want to cancel this interview?\n\nPanel Group: ${interview.panel_group}\nLevel: ${interview.level}\nDate: ${new Date(
      interview.start_date,
    ).toLocaleDateString()}`,
    "Cancel Interview",
    {
      confirmButtonText: "Yes, cancel",
      cancelButtonText: "Keep interview",
      type: "warning",
    },
  ).catch(() => false);
  if (!confirmCancel) return;

  try {
    // TODO: Implement cancel interview API call
    ElNotification({
      title: "Coming soon",
      message: "Cancel interview functionality will be implemented soon.",
      type: "info",
      duration: 4000,
      position: "top-right",
    });
    // After successful cancellation, refresh the interviews list
    // await refreshInterviews();
  } catch (error) {
    ElNotification({
      title: "Cancel failed",
      message: "Failed to cancel interview. Please try again.",
      type: "error",
      duration: 5000,
      position: "top-right",
    });
  }
};

const formatTime = (timeStr) => {
  if (!timeStr) return "N/A";
  const parts = timeStr.split(":");
  const hours = parseInt(parts[0]);
  const minutes = parts[1];
  const ampm = hours >= 12 ? "PM" : "AM";
  const displayHours = hours % 12 || 12;
  return `${displayHours}:${minutes} ${ampm}`;
};

const handleWithdrawApplication = async (application) => {
  if (!application) return;

  const confirmWithdraw = await ElMessageBox.confirm(
    `Are you sure you want to withdraw your application for ${application.position}? This action cannot be undone.`,
    "Withdraw Application",
    {
      confirmButtonText: "Withdraw",
      cancelButtonText: "Keep application",
      type: "warning",
    },
  ).catch(() => false);
  if (!confirmWithdraw) return;

  try {
    withdrawingApplication.value = true;

    // Call API to withdraw application
    const response = await ApiService.withdrawApplication(application.id);

    if (response.data.success) {
      ElNotification({
        title: "Application withdrawn",
        message: "Your application was withdrawn successfully.",
        type: "success",
        duration: 4000,
        position: "top-right",
      });
      selectedApplication.value = null;
      // Refresh applications list
      await refreshApplications();
    }
  } catch (error) {
    const errorMessage =
      error.response?.data?.message ||
      "Failed to withdraw application. Please try again.";
    ElNotification({
      title: "Withdraw failed",
      message: errorMessage,
      type: "error",
      duration: 5000,
      position: "top-right",
    });
  } finally {
    withdrawingApplication.value = false;
  }
};

// This function is defined later in the file

const refreshApplications = async () => {
  applicationsLoading.value = true;
  try {
    await loadApplicantData();
  } catch (error) {
    // Silent error handling
  } finally {
    applicationsLoading.value = false;
  }
};

const handleGoToPds = () => {
  activeTab.value = "personal_information";
};

// Computed property for job offers
const jobOffers = computed(() => {
  return applications.value
    .filter((app) => {
      const status = app.status?.toLowerCase() || "";
      return status.includes("for hiring") || status.includes("accepted");
    })
    .map((app) => {
      const status = app.status?.toLowerCase() || "";
      const isForHiring = status.includes("for hiring");
      // jo_* values may come back as 0/1, "0"/"1", or booleans
      const toBool = (val) => val === 1 || val === "1" || val === true;
      const hasResponded =
        toBool(app.jo_is_accepted) ||
        toBool(app.jo_is_rejected) ||
        toBool(app.jo_is_expired);

      return {
        ...app,
        // Only allow actions while the offer is still "For Hiring"
        // and the applicant has not yet accepted/declined/expired it.
        canAccept: isForHiring && !hasResponded,
        canReject: isForHiring && !hasResponded,
        salary: app.salary || null,
        grade: app.grade || null,
        step: app.step || null,
        salaryGrade: app.salaryGrade || app.salary_grade || app.grade || "N/A",
        startDate: app.startDate || app.start_date || null,
      };
    });
});

// Job Offer Action Handlers
const handleAcceptOffer = async (offerId) => {
  try {
    const response = await ApiService.acceptJobOffer(offerId);

    if (response.data.success) {
      ElNotification({
        title: "Offer Accepted",
        message:
          "You have successfully accepted the job offer. Congratulations!",
        type: "success",
        duration: 5000,
        position: "top-right",
      });

      // Refresh applications to update status
      await refreshApplications();
    }
  } catch (error) {
    const errorMessage =
      error.response?.data?.message ||
      "Failed to accept job offer. Please try again.";
    ElNotification({
      title: "Accept Failed",
      message: errorMessage,
      type: "error",
      duration: 5000,
      position: "top-right",
    });
  }
};

const handleRejectOffer = async (offerId) => {
  try {
    const response = await ApiService.rejectJobOffer(offerId);

    if (response.data.success) {
      ElNotification({
        title: "Offer Declined",
        message: "You have declined the job offer.",
        type: "info",
        duration: 5000,
        position: "top-right",
      });

      // Refresh applications to update status
      await refreshApplications();
    }
  } catch (error) {
    const errorMessage =
      error.response?.data?.message ||
      "Failed to decline job offer. Please try again.";
    ElNotification({
      title: "Decline Failed",
      message: errorMessage,
      type: "error",
      duration: 5000,
      position: "top-right",
    });
  }
};

// API Methods
const loadApplicantData = async () => {
  try {
    isLoading.value = true;
    const response = await ApiService.getApplicantPage();

    if (response.data && response.data.data) {
      const data = response.data.data;

      const applicant = data.applicant?.[0] || {};

      if (!applicant || !applicant.id) {
        applicantData.value = {
          name: "Please Complete Registration",
          email: "",
          applicantNo: "",
          id: null,
          first_name: "",
          middle_name: "",
          last_name: "",
        };
      } else {
        const fullName = `${applicant.first_name || ""} ${
          applicant.middle_name || ""
        } ${applicant.last_name || ""}`.trim();

        applicantData.value = {
          name: fullName || "Applicant",
          email: applicant.email || "",
          applicantNo: applicant.applicant_no || "",
          id: applicant.id ? Number(applicant.id) : null,
          first_name: applicant.first_name || "",
          middle_name: applicant.middle_name || "",
          last_name: applicant.last_name || "",
          ...applicant,
        };
      }

      try {
        const progressResponse = await ApiService.getApplicantProgress();
        if (progressResponse.data && progressResponse.data.data) {
          applicantProgress.value = {
            steps: progressResponse.data.data.steps || [],
          };
        } else {
          applicantProgress.value = { steps: [] };
        }
      } catch (progressError) {
        // If progress fails, fall back to empty steps without breaking dashboard load
        applicantProgress.value = { steps: [] };
      }

      updateDashboardStats(data, applicantProgress.value.steps || []);

      // Update PDS completion status
      updatePDSStatus(data);

      // Build notifications (unread-only, based on last-seen timestamps)
      const applicantHeader = data.applicant?.[0] || {};
      notifications.value = buildNotifications(data, applicantHeader);
      if (data.unread_notifications) {
        unreadNotificationCounts.value = {
          application: data.unread_notifications.application ?? 0,
          exam: data.unread_notifications.exam ?? 0,
          interview: data.unread_notifications.interview ?? 0,
        };
      } else {
        syncUnreadFromNotifications();
      }
      updateTabBadges();

      if (data.plantillas_selected || data.non_plantillas) {
        const plantillaApps = (data.plantillas_selected || []).map((app) => {
          const requirements = buildPlantillaRequirements(app);

          const offerDate = new Date(app.created_at || Date.now());
          const responseDeadline = new Date(offerDate);
          responseDeadline.setDate(responseDeadline.getDate() + 7);

          return {
            // Use a composite key namespace to avoid collisions with non-plantilla IDs
            type: "plantilla",
            id: app.id,
            position: app.position || "Unknown Position",
            jobCode: app.code || "N/A",
            department: app.department || "N/A",
            location: app.unit || "N/A",
            appliedDate: offerDate,
            responseDeadline,
            status: getStatusText(app.application_status),
            description:
              app.description ||
              `Position: ${app.position}. See details for requirements.`,
            requirements: requirements,
            education_details: app.education_details || [],
            requirements_list: app.requirements_list || [],
            eligibility: app.eligibility || null,
            education: app.education || null,
            experience: app.experience || null,
            training: app.training || null,
            // Job offer response flags from applicant_jo
            jo_is_accepted: app.jo_is_accepted ?? null,
            jo_is_rejected: app.jo_is_rejected ?? null,
            jo_is_expired: app.jo_is_expired ?? null,
            canEdit:
              app.application_status === "pending" ||
              app.application_status === "Pending",
            canWithdraw:
              app.application_status === "pending" ||
              app.application_status === "Pending" ||
              app.application_status === "Active" ||
              app.application_status === "active" ||
              !app.application_status || // Allow withdraw if status is null/undefined
              app.application_status?.toLowerCase() === "active",
            salary: app.salary || null,
            grade: app.grade || null,
            step: app.step || null,
          };
        });

        const nonPlantillaApps = (data.non_plantillas || []).map((app) => {
          const requirements = [];
          if (app.eligibility)
            requirements.push(`Eligibility: ${app.eligibility}`);
          if (app.education) requirements.push(`Education: ${app.education}`);
          if (app.experience)
            requirements.push(`Experience: ${app.experience}`);
          if (app.training) requirements.push(`Training: ${app.training}`);

          const offerDate = new Date(app.created_at || Date.now());
          const responseDeadline = new Date(offerDate);
          responseDeadline.setDate(responseDeadline.getDate() + 7);

          return {
            // Use a composite key namespace to avoid collisions with plantilla IDs
            type: "non_plantilla",
            id: app.id,
            position: app.position || "Unknown Position",
            jobCode: app.code || "N/A",
            department: app.department || "N/A",
            location: app.unit || "N/A",
            appliedDate: offerDate,
            responseDeadline,
            status: getStatusText(app.application_status),
            description:
              app.description ||
              `Position: ${app.position}. See details for requirements.`,
            requirements: requirements,
            // Job offer response flags from applicant_jo
            jo_is_accepted: app.jo_is_accepted ?? null,
            jo_is_rejected: app.jo_is_rejected ?? null,
            jo_is_expired: app.jo_is_expired ?? null,
            canEdit:
              app.application_status === "pending" ||
              app.application_status === "Pending",
            canWithdraw:
              app.application_status === "pending" ||
              app.application_status === "Pending" ||
              app.application_status === "Active" ||
              app.application_status === "active" ||
              !app.application_status || // Allow withdraw if status is null/undefined
              app.application_status?.toLowerCase() === "active",
          };
        });

        applications.value = [...plantillaApps, ...nonPlantillaApps];
      }

      // Update examinations with real data
      if (data.examination_schedules && data.examination_schedules.length > 0) {
        examinations.value = data.examination_schedules.map((exam) => {
          const isComplete =
            exam.is_complete === 1 ||
            exam.is_complete === "1" ||
            exam.is_complete === true ||
            !!exam.date_completed;

          return {
            id: exam.id,
            applicant_examination_id: exam.applicant_examination_id,
            exam_set: exam.exam_set || "N/A",
            // Used in the "Exam Details" modal to show what the candidate should do.
            exam_instruction: exam.exam_instruction || exam.instruction || "N/A",
            exam_date_from: exam.exam_date_from,
            exam_date_to: exam.exam_date_to,
            exam_time_from: exam.exam_time_from,
            exam_time_to: exam.exam_time_to,
            exam_duration: exam.exam_duration || "N/A",
            passing_criteria: exam.passing_criteria || "N/A",
            is_complete: exam.is_complete || 0,
            date_completed: exam.date_completed,
            exam_rating: exam.exam_rating || null,
            // Backend should already return Completed, but keep a safe fallback
            status: isComplete ? "Completed" : exam.status || "Pending",
          };
        });
      } else {
        examinations.value = [];
      }

      // Update interviews with real data
      if (data.interview_schedules && data.interview_schedules.length > 0) {
        interviews.value = data.interview_schedules.map((interview) => ({
          id: interview.id,
          panel_group: interview.panel_group || "N/A",
          interview_location: interview.interview_location || "N/A",
          description: interview.description || "",
          panel_group_level: interview.panel_group_level,
          level: interview.level || "N/A",
          start_date: interview.start_date,
          end_date: interview.end_date,
          start_time: interview.start_time,
          end_time: interview.end_time,
          status: interview.status || "Pending",
        }));
      } else {
        interviews.value = [];
      }

      // Update recent activities from real data
      const allActivities = [];

      // 1. Application submissions
      const formatApplicationActivityStatus = (applicationStatus) => {
        const raw = (applicationStatus ?? "").toString().trim();
        if (!raw) return "Pending";

        const s = raw.toLowerCase();
        if (s === "pending" || s === "active") return "Pending";
        if (s === "under review" || s === "under-review") return "Under Review";
        if (s === "shortlisted" || s === "proceed to next step") return "Shortlisted";
        if (s === "not qualified") return "Not Qualified";
        if (s === "will not proceed") return "Will not proceed";
        if (s === "rejected") return "Rejected";
        if (s === "for hiring" || s === "for-hiring") return "For Hiring";
        if (s === "accepted") return "Accepted";
        if (s === "hired") return "Hired";
        if (s === "withdrawn") return "Withdrawn";
        if (s === "expired") return "Expired";
        if (s === "cancelled" || s === "canceled") return "Cancelled";

        // Fallback to whatever the backend returned.
        return raw;
      };

      if (data.plantillas_selected && data.plantillas_selected.length > 0) {
        data.plantillas_selected.forEach((app) => {
          allActivities.push({
            id: `app-${app.id}`,
            title: "Application Submitted",
            description: `Your application for ${app.position} position has been submitted successfully.`,
            date: new Date(app.created_at),
            icon: "fas fa-file-alt", // FileText icon for application
            iconBg: "bg-blue-500",
            iconColor: "text-white",
            status: formatApplicationActivityStatus(app.application_status),
            metadata: {
              type: "Application",
              location: app.department || "N/A",
            },
          });
        });
      }

      if (data.non_plantillas && data.non_plantillas.length > 0) {
        data.non_plantillas.forEach((app) => {
          allActivities.push({
            id: `app-np-${app.id}`,
            title: "Application Submitted",
            description: `Your application for ${app.position} position has been submitted successfully.`,
            date: new Date(app.created_at),
            icon: "fas fa-file-alt", // FileText icon for application
            iconBg: "bg-blue-500",
            iconColor: "text-white",
            status: formatApplicationActivityStatus(app.application_status),
            metadata: {
              type: "Application",
              location: app.department || "N/A",
            },
          });
        });
      }

      // 2. Exam schedules
      if (data.examination_schedules && data.examination_schedules.length > 0) {
        data.examination_schedules.forEach((exam) => {
          const examDate = exam.exam_date_from
            ? new Date(exam.exam_date_from)
            : new Date();

          // const status = exam.status?.toLowerCase() || "Pending";

          const rawStatus = (exam.status || "").toLowerCase();
          const isCompletedFlag =
            exam.is_complete === 1 ||
            exam.is_complete === true ||
            rawStatus === "completed";

          const status = isCompletedFlag ? "Completed" : rawStatus || "Pending";

          allActivities.push({
            id: `exam-${exam.id || exam.applicant_examination_id}`,
            title: "Exam Scheduled",
            description: `${
              exam.exam_set || "Technical assessment"
            } has been scheduled${
              exam.exam_date_from
                ? ` for ${examDate.toLocaleDateString("en-US", {
                    month: "long",
                    day: "numeric",
                    year: "numeric",
                  })}`
                : ""
            }.`,
            date: examDate,
            icon: "fas fa-calendar-check", // Calendar icon for exam scheduled
            iconBg:
              status === "Completed"
                ? "bg-green-500"
                : status === "active"
                  ? "bg-blue-500"
                  : "bg-yellow-500",
            iconColor: "text-white",
            status:
              status === "Completed"
                ? "Completed"
                : status === "active"
                  ? "In Progress"
                  : "Pending",
            metadata: {
              type: "Examination",
            },
          });
        });
      }

      // 3. Interview schedules
      if (data.interview_schedules && data.interview_schedules.length > 0) {
        data.interview_schedules.forEach((interview) => {
          const interviewDate = interview.start_date
            ? new Date(interview.start_date)
            : new Date();
          const status = interview.status?.toLowerCase() || "Pending";

          allActivities.push({
            id: `interview-${interview.id}`,
            title: "Interview Scheduled",
            description: `${interview.level || "Interview"} has been scheduled${
              interview.start_date
                ? ` for ${interviewDate.toLocaleDateString("en-US", {
                    month: "long",
                    day: "numeric",
                    year: "numeric",
                  })}`
                : ""
            }.`,
            date: interviewDate,
            icon: "fas fa-user-tie", // Video/Camera icon for interview
            iconBg:
              status === "Completed"
                ? "bg-green-500"
                : status === "active"
                  ? "bg-blue-500"
                  : "bg-purple-500",
            iconColor: "text-white",
            status:
              status === "Completed"
                ? "Completed"
                : status === "active"
                  ? "In Progress"
                  : "Pending",
            metadata: {
              type: "Interview",
            },
          });
        });
      }

      // Sort activities by date (most recent first)
      allActivities.sort((a, b) => new Date(b.date) - new Date(a.date));

      // Take the most recent activities
      recentActivities.value = allActivities.slice(0, 10);
    }
  } catch (err) {
    if (
      err.response?.status === 404 ||
      err.response?.data?.message?.includes("applicant")
    ) {
      error.value =
        "No applicant profile found. Please complete your registration first.";
    } else {
      error.value =
        err.response?.data?.message || "Failed to load dashboard data";
    }
  } finally {
    isLoading.value = false;
  }
};

const updatePDSStatus = (data) => {
  const applicant = data.applicant?.[0] || {};
  const missingSections = [];
  let completedSections = 0;
  const totalSections = 7; // Personal Info, Contact, Education, Work Experience, Documents, Eligibility, Character References

  // Check Personal Information
  if (applicant.first_name && applicant.last_name && applicant.email) {
    completedSections++;
  } else {
    missingSections.push("Personal Information");
  }

  // Check Contact Information
  if (applicant.mobile_no) {
    completedSections++;
  } else {
    missingSections.push("Contact Information");
  }

  // Check Education (if has education records)
  if (data.educations && data.educations.length > 0) {
    completedSections++;
  } else {
    missingSections.push("Educational Background");
  }

  // Check Work Experience (employments = employee_employment_records, work_experience = Work_Experience table)
  const hasWork =
    (data.employments && data.employments.length > 0) ||
    (data.work_experience && data.work_experience.length > 0);
  if (hasWork) {
    completedSections++;
  } else {
    missingSections.push("Work Experience");
  }

  // Check Documents (basic check)
  if (applicant.photo || (data.documents && data.documents.length > 0)) {
    completedSections++;
  } else {
    missingSections.push("Required Documents");
  }

  // Check Eligibility (if has examination/eligibility records)
  const hasEligibility = data.examinations && data.examinations.length > 0;
  if (hasEligibility) {
    completedSections++;
  } else {
    missingSections.push("Eligibility");
  }

  // Check Character References
  const hasReferences = data.references && data.references.length > 0;
  if (hasReferences) {
    completedSections++;
  } else {
    missingSections.push("Character References");
  }

  const completionPercentage = Math.round(
    (completedSections / totalSections) * 100,
  );

  pdsStatus.value = {
    isComplete: completedSections === totalSections,
    completionPercentage,
    missingSections,
  };

  // Check eligibility status
  updateEligibilityStatus(data);

  // Check document status
  updateDocumentStatus(data);
};

const updateEligibilityStatus = (data) => {
  // Get applicant's eligibilities from examinations
  const examinations = data.examinations || [];
  const applicantEligibilityNames = [];

  // Get eligibility names from examinations
  if (examinations.length > 0) {
    examinations.forEach((exam) => {
      // Check if examination already has eligibility name
      if (exam.eligibility_name) {
        applicantEligibilityNames.push(exam.eligibility_name);
      } else if (exam.eligibility_id && data.eligibilities) {
        // Match eligibility_id with eligibilities list
        const eligibility = data.eligibilities.find(
          (e) => e.id === exam.eligibility_id,
        );
        if (eligibility && eligibility.name) {
          applicantEligibilityNames.push(eligibility.name);
        }
      }
    });
  }

  // Check vacant positions for eligibility requirements
  const vacantPositions = [
    ...(data.vacant_data || []),
    ...(data.vacant_non_plantillas || []),
  ];

  const requiredEligibilities = [];
  vacantPositions.forEach((position) => {
    if (position.eligibility && position.eligibility.trim() !== "") {
      const eligibility = position.eligibility.trim();
      if (!requiredEligibilities.includes(eligibility)) {
        requiredEligibilities.push(eligibility);
      }
    }
  });

  // Check if applicant has any of the required eligibilities
  let hasRequiredEligibility = false;
  if (
    applicantEligibilityNames.length > 0 &&
    requiredEligibilities.length > 0
  ) {
    requiredEligibilities.forEach((required) => {
      // Try exact match
      if (applicantEligibilityNames.includes(required)) {
        hasRequiredEligibility = true;
      } else {
        // Try case-insensitive partial match
        const requiredLower = required.toLowerCase();
        applicantEligibilityNames.forEach((applicantElig) => {
          if (
            applicantElig.toLowerCase().includes(requiredLower) ||
            requiredLower.includes(applicantElig.toLowerCase())
          ) {
            hasRequiredEligibility = true;
          }
        });
      }
    });
  }

  eligibilityStatus.value = {
    hasEligibility: applicantEligibilityNames.length > 0,
    // Only show 'Eligibility Required' when there are requirements
    // AND the applicant does not meet any of them.
    hasEligibilityRequired:
      requiredEligibilities.length > 0 && !hasRequiredEligibility,
    requiredEligibilities: requiredEligibilities,
    hasRequiredEligibility: hasRequiredEligibility,
  };
};

const updateDocumentStatus = (data) => {
  const documents = data.documents || [];
  const documentCount = documents.length;

  documentStatus.value = {
    hasDocuments: documentCount > 0,
    documentCount: documentCount,
  };
};

const updateDashboardStats = (data, steps = []) => {
  const plantillaApps = data.plantillas_selected || [];
  const nonPlantillaApps = data.non_plantillas || [];
  const applicationsCount = plantillaApps.length + nonPlantillaApps.length;

  // Count completed exams from examination_schedules
  const examinationSchedules = data.examination_schedules || [];
  const completedExams = examinationSchedules.filter(
    (exam) =>
      exam.is_complete === 1 || exam.status?.toLowerCase() === "completed",
  ).length;

  // Count pending reviews - check for various status values that indicate under review
  const pendingReviews = [...plantillaApps, ...nonPlantillaApps].filter(
    (app) => {
      const status = app.application_status?.toLowerCase();
      return (
        status === "under-review" ||
        status === "under review" ||
        status === "pending" ||
        status === "active"
      );
    },
  ).length;

  const totalSteps = Array.isArray(steps) ? steps.length : 0;
  const completedSteps = totalSteps
    ? steps.filter((step) => step.state === "done").length
    : 0;

  let currentStepLabel = "Not started";
  if (totalSteps > 0) {
    if (completedSteps === totalSteps) {
      currentStepLabel = "All steps completed";
    } else {
      const nextStep =
        steps.find((step) => step.state !== "done") || steps[totalSteps - 1];
      currentStepLabel = nextStep?.label || "In progress";
    }
  }

  const progressDisplay = currentStepLabel;

  dashboardStats.value = [
    {
      id: 1,
      label: "Applications",
      value: applicationsCount.toString(),
      icon: "fas fa-file-alt",
      cardBg: "bg-blue-50",
      bgColor: "bg-blue-500",
      textColor: "text-white",
      valueColor: "text-blue-600",
      clickable: true,
    },
    {
      id: 2,
      label: "Exams Completed",
      value: completedExams.toString(),
      icon: "fas fa-check-circle",
      cardBg: "bg-emerald-50",
      bgColor: "bg-green-500",
      textColor: "text-white",
      valueColor: "text-emerald-600",
      clickable: true,
    },
    {
      id: 3,
      label: "Pending Reviews",
      value: pendingReviews.toString(),
      icon: "fas fa-clock",
      cardBg: "bg-amber-50",
      bgColor: "bg-amber-500",
      textColor: "text-white",
      valueColor: "text-amber-600",
      clickable: true,
    },
    {
      id: 4,
      label: "Current Progress",
      value: progressDisplay,
      icon: "fas fa-chart-bar",
      cardBg: "bg-purple-50",
      bgColor: "bg-purple-500",
      textColor: "text-white",
      valueColor: "text-purple-600",
      clickable: true,
    },
  ];
};

const syncUnreadFromNotifications = () => {
  const appCount = notifications.value.filter(
    (n) => (n.type || "").toLowerCase() === "application",
  ).length;
  const examCount = notifications.value.filter(
    (n) => (n.type || "").toLowerCase() === "exam",
  ).length;
  const interviewCount = notifications.value.filter(
    (n) => (n.type || "").toLowerCase() === "interview",
  ).length;

  unreadNotificationCounts.value = {
    application: appCount,
    exam: examCount,
    interview: interviewCount,
  };

  updateTabBadges();
};

const updateTabBadges = () => {
  tabs.value = tabs.value.map((tab) => {
    if (tab.id === "applications") {
      return { ...tab, badge: unreadNotificationCounts.value.application };
    }
    if (tab.id === "Examinations") {
      return { ...tab, badge: unreadNotificationCounts.value.exam };
    }
    if (tab.id === "Interviews") {
      return { ...tab, badge: unreadNotificationCounts.value.interview };
    }
    return tab;
  });
};

const buildNotifications = (data, applicantHeader = {}) => {
  const items = [];
  const lastSeenApp = applicantHeader.last_seen_application_notifications_at
    ? new Date(applicantHeader.last_seen_application_notifications_at)
    : null;
  const lastSeenExam = applicantHeader.last_seen_exam_notifications_at
    ? new Date(applicantHeader.last_seen_exam_notifications_at)
    : null;
  const lastSeenInterview = applicantHeader.last_seen_interview_notifications_at
    ? new Date(applicantHeader.last_seen_interview_notifications_at)
    : null;

  const isAfter = (value, lastSeen) => {
    if (!lastSeen) return true;
    if (!value) return false;
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return false;
    return d > lastSeen;
  };

  // Applications
  [...(data.plantillas_selected || []), ...(data.non_plantillas || [])].forEach(
    (app) => {
      if (!isAfter(app.created_at, lastSeenApp)) return;
      items.push({
        title: app.position || "Application update",
        subtitle: `${app.application_status || "Pending"} · ${app.code || "APP"}`,
        type: "application",
        createdAt: app.created_at || Date.now(),
      });
    },
  );

  // Exams
  (data.examination_schedules || []).forEach((exam) => {
    if (!isAfter(exam.exam_date_from, lastSeenExam)) return;
    items.push({
      title: exam.exam_set || "Exam scheduled",
      subtitle:
        `${exam.exam_date_from || ""} ${exam.exam_time_from || ""}`.trim(),
      type: "exam",
      createdAt: exam.exam_date_from || Date.now(),
    });
  });

  // Interviews
  (data.interview_schedules || []).forEach((iv) => {
    if (!isAfter(iv.start_date, lastSeenInterview)) return;
    items.push({
      title: iv.level || "Interview scheduled",
      subtitle: `${iv.start_date || ""} ${iv.start_time || ""}`.trim(),
      type: "interview",
      createdAt: iv.start_date || Date.now(),
    });
  });

  items.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
  return items;
};

const calculateAverageScore = (examinationSchedules) => {
  if (!examinationSchedules || examinationSchedules.length === 0) return 0;

  // Filter only completed exams with valid ratings
  const scores = examinationSchedules
    .filter(
      (exam) =>
        (exam.is_complete === 1 ||
          exam.status?.toLowerCase() === "completed") &&
        exam.exam_rating !== null &&
        exam.exam_rating !== undefined &&
        exam.exam_rating !== 0,
    )
    .map((exam) => parseFloat(exam.exam_rating));

  if (scores.length === 0) return 0;

  const average = scores.reduce((sum, score) => sum + score, 0) / scores.length;
  return Math.round(average);
};

const buildPlantillaRequirements = (app) => {
  const requirements = [];

  if (app.eligibility && String(app.eligibility).trim() !== "") {
    requirements.push(`Eligibility: ${app.eligibility}`);
  }

  if (Array.isArray(app.education_details) && app.education_details.length > 0) {
    app.education_details.forEach((edu) => {
      if (edu?.program) {
        const level = edu.academic_level ? ` (${edu.academic_level})` : "";
        requirements.push(`Education: ${edu.program}${level}`);
      }
    });
  } else if (app.education && String(app.education).trim() !== "") {
    requirements.push(`Education: ${app.education}`);
  }

  if (app.experience && String(app.experience).trim() !== "") {
    requirements.push(`Experience: ${app.experience}`);
  }

  if (app.training && String(app.training).trim() !== "") {
    requirements.push(`Training: ${app.training}`);
  }

  (app.requirements_list || []).forEach((req) => {
    if (req && String(req).trim() !== "") {
      requirements.push(String(req));
    }
  });

  return requirements;
};

const getStatusText = (status) => {
  if (!status && status !== 0) return "pending";

  // Handle string statuses coming from application_status.name
  if (typeof status === "string") {
    const s = status.toLowerCase().trim();

    if (s === "pending") return "pending";
    if (s === "active") return "pending";
    if (s === "under review" || s === "under-review") return "under-review";
    if (s === "shortlisted") return "shortlisted";
    if (s === "not qualified") return "not-qualified";
    if (s === "will not proceed") return "will-not-proceed";
    if (s === "proceed to next step") return "shortlisted";
    if (s === "for hiring") return "for hiring";

    return s; // fallback
  }

  // Handle numeric status IDs when used
  const statusMap = {
    1: "pending", // Active
    2: "not-qualified", // Not Qualified
    3: "will-not-proceed", // Will not proceed
    4: "shortlisted", // Proceed to next step
    5: "for hiring", // For Hiring
  };
  return statusMap[status] || "pending";
};

const getActivityIcon = (type) => {
  const iconMap = {
    application: "fas fa-file-alt",
    examination: "fas fa-calendar-check",
    document: "fas fa-edit",
    interview: "fas fa-user-tie",
  };
  return iconMap[type] || "fas fa-info-circle";
};

const getActivityBg = (type) => {
  const bgMap = {
    application: "bg-blue-500",
    examination: "bg-green-500",
    document: "bg-purple-500",
    interview: "bg-orange-500",
  };
  return bgMap[type] || "bg-gray-500";
};

// Enhanced methods
const handleNewApplication = () => {
  // This is now handled by ApplicationsTable component
  // No need to redirect - job listings will be shown within the tab
};

const handleApplyForPosition = async (positionId, isPlantilla = true) => {
  try {
    if (!applicantData.value.id) {
      ElNotification({
        title: "Profile incomplete",
        message: "Please complete your profile first.",
        type: "warning",
        duration: 4000,
        position: "top-right",
      });
      return;
    }

    if (!pdsStatus.value.isComplete) {
      ElNotification({
        title: "PDS incomplete",
        message: `Please complete your Personal Data Sheet first. Missing: ${pdsStatus.value.missingSections?.join(", ") || "required sections"}`,
        type: "warning",
        duration: 5000,
        position: "top-right",
      });
      activeTab.value = "personal_information";
      return;
    }

    const response = await ApiService.applyForPosition(
      applicantData.value.id,
      positionId,
      isPlantilla,
    );

    if (response.data.success) {
      ElNotification({
        title: "Application submitted",
        message: "Your application was submitted successfully.",
        type: "success",
        duration: 4000,
        position: "top-right",
      });
      await loadApplicantData();
    }
  } catch (error) {
    if (error.response?.status === 422) {
      const missingReqs = error.response?.data?.errors?.missing_requirements;
      ElNotification({
        title: missingReqs?.length ? "Requirements not met" : "PDS incomplete",
        message:
          error.response?.data?.message ||
          (missingReqs?.length
            ? missingReqs.join("; ")
            : "Please complete your Personal Data Sheet (PDS) before applying."),
        type: "warning",
        duration: 6000,
        position: "top-right",
      });
      activeTab.value = "personal_information";
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

// Load data on mount
onMounted(async () => {
  await loadApplicantData();
});

watch(
  activeTab,
  (newVal) => {
    if (newVal === "applications") {
      unreadNotificationCounts.value.application = 0;
      ApiService.markNotificationsRead("application").catch(() => {});
      notifications.value = notifications.value.filter(
        (n) => (n.type || "").toLowerCase() !== "application",
      );
    } else if (newVal === "Examinations") {
      unreadNotificationCounts.value.exam = 0;
      ApiService.markNotificationsRead("exam").catch(() => {});
      notifications.value = notifications.value.filter(
        (n) => (n.type || "").toLowerCase() !== "exam",
      );
    } else if (newVal === "Interviews") {
      unreadNotificationCounts.value.interview = 0;
      ApiService.markNotificationsRead("interview").catch(() => {});
      notifications.value = notifications.value.filter(
        (n) => (n.type || "").toLowerCase() !== "interview",
      );
    } else {
      return;
    }
    updateTabBadges();
  },
  { immediate: false },
);
</script>

<style scoped>
/* Add any dashboard-specific styles here */
</style>
