<template>
  <div class="min-h-screen bg-gradient-to-b from-blue-50 to-white">
    <!-- Navigation -->
    <nav
      class="fixed top-0 left-0 right-0 bg-white/95 backdrop-blur-sm shadow-sm z-50"
    >
      <div class="max-w-7xl mx-auto px-6 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <img
              v-if="logo"
              :src="logo"
              alt="Company Logo"
              class="h-12 w-auto object-contain"
            />
            <h1 class="text-xl font-semibold text-gray-900">
              {{ companyName }}
            </h1>
          </div>
          <div class="flex items-center gap-2">
            <button
              class="px-5 py-2 text-gray-700 hover:text-blue-600 transition-colors hidden md:block"
              @click="handleNavigate('/positions')"
            >
              Browse Jobs
            </button>
            <button
              class="px-5 py-2 text-gray-700 hover:text-blue-600 transition-colors"
              @click="handleNavigate('/login')"
            >
              Sign In
            </button>
            <button
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all hover:shadow-lg"
              @click="handleNavigate('/registration')"
            >
              Get Started
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-6 relative overflow-hidden">
      <div class="absolute inset-0 z-0">
        <img
          :src="heroBg"
          alt="Company Building"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-white/85 backdrop-blur-sm"></div>
      </div>

      <div class="max-w-7xl mx-auto relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div class="space-y-8">
            <div
              class="inline-block px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm"
            >
              Your Career Journey Starts Here
            </div>
            <h1 class="text-4xl md:text-6xl leading-tight text-gray-900">
              Find Your Dream Job with <span class="text-blue-600">{{ shortName }}</span>
            </h1>
            <p class="text-xl text-gray-600 leading-relaxed">
              Join thousands of successful applicants who found their perfect
              career match through our innovative HRMS platform. Simple, fast,
              and efficient job application process.
            </p>
            <div class="flex flex-wrap gap-4">
              <button
                class="px-8 py-4 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all hover:shadow-xl flex items-center gap-2"
                @click="handleNavigate('/registration')"
              >
                <UserFilled class="w-5 h-5" />
                Create Account
              </button>
              <button
                class="px-8 py-4 bg-white text-blue-600 border-2 border-blue-600 rounded-xl hover:bg-blue-50 transition-all flex items-center gap-2"
                @click="handleNavigate('/positions')"
              >
                <Search class="w-5 h-5" />
                Explore Positions
              </button>
            </div>
            <div class="flex items-center gap-8 pt-4 flex-wrap text-center">
              <div v-for="stat in heroStats" :key="stat.label">
                <div class="text-3xl text-blue-600">{{ stat.value }}</div>
                <div class="text-sm text-gray-600">{{ stat.label }}</div>
              </div>
            </div>
          </div>

          <div class="relative">
            <Transition name="fade" mode="out-in">
              <div
                v-if="latestPosition"
                :key="`card-${currentPositionIndex}`"
                class="relative"
              >
                <div class="absolute inset-0 bg-blue-400 rounded-3xl"></div>

                <div
                  class="relative bg-white rounded-3xl p-8 shadow-2xl border border-blue-200 group"
                  @mouseenter="pauseAutoRotate"
                  @mouseleave="resumeAutoRotate"
                >
                  <!-- Navigation Arrows (shown on hover) -->
                  <button
                    v-if="positions.length > 1"
                    @click="navigatePosition('prev')"
                    class="absolute left-2 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-blue-600 text-white rounded-full p-2 hover:bg-blue-700 shadow-lg z-20"
                    title="Previous Position"
                  >
                    <ArrowLeft class="w-5 h-5" />
                  </button>
                  <button
                    v-if="positions.length > 1"
                    @click="navigatePosition('next')"
                    class="absolute right-2 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-blue-600 text-white rounded-full p-2 hover:bg-blue-700 shadow-lg z-20"
                    title="Next Position"
                  >
                    <ArrowRight class="w-5 h-5" />
                  </button>

                  <div class="space-y-6">
                    <div
                      class="flex items-center gap-4 p-4 bg-blue-50 rounded-xl"
                    >
                      <div
                        class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0"
                      >
                        <Briefcase class="w-8 h-8 text-white" />
                      </div>
                      <div class="flex-1">
                        <div class="text-sm text-gray-600">Latest Position</div>
                        <div class="text-lg text-gray-900">
                          {{
                            latestPosition.position ||
                            latestPosition.name ||
                            "Position Available"
                          }}
                        </div>
                        <div class="text-sm text-blue-600">
                          {{ formatSalary(latestPosition) }}
                        </div>
                      </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                      <div
                        class="p-4 bg-gradient-to-br from-blue-50 to-white rounded-xl border border-blue-100 text-center"
                      >
                        <div class="text-2xl text-blue-600">
                          {{ stats.newThisWeek }}
                        </div>
                        <div class="text-sm text-gray-600">New This Week</div>
                      </div>
                      <div
                        class="p-4 bg-gradient-to-br from-green-50 to-white rounded-xl border border-green-100 text-center"
                      >
                        <div class="text-2xl text-green-600">
                          {{ currentApplicantsCount }}
                        </div>
                        <div class="text-sm text-gray-600">
                          Applicants Applied
                        </div>
                      </div>
                    </div>

                    <div class="space-y-3">
                      <div
                        v-for="dept in currentPositionDepartments"
                        :key="dept.name"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                      >
                        <span class="text-sm text-gray-700">{{
                          dept.name
                        }}</span>
                        <span
                          class="text-xs text-blue-600 bg-blue-100 px-3 py-1 rounded-full"
                        >
                          {{ dept.count }}
                          {{ dept.count === 1 ? "position" : "positions" }}
                        </span>
                      </div>
                      <div
                        v-if="currentPositionDepartments.length === 0"
                        class="text-sm text-gray-500 text-center py-4"
                      >
                        No department information available
                      </div>
                    </div>

                    <!-- Position indicator (only show if multiple positions) -->
                    <div
                      v-if="positions.length > 1"
                      class="flex justify-center gap-1 pt-2"
                    >
                      <div
                        v-for="(pos, index) in positions.slice(
                          0,
                          Math.min(positions.length, 5),
                        )"
                        :key="index"
                        :class="[
                          'w-2 h-2 rounded-full transition-all duration-300',
                          index === currentPositionIndex
                            ? 'bg-blue-600 w-6'
                            : 'bg-gray-300',
                        ]"
                      ></div>
                      <div
                        v-if="positions.length > 5"
                        class="text-xs text-gray-400 self-center ml-1"
                      >
                        +{{ positions.length - 5 }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else :key="'no-position'" class="relative">
                <Transition name="layer" mode="out-in">
                  <div
                    :key="`bg-${currentPositionIndex}`"
                    class="absolute inset-0 bg-gradient-to-br from-blue-200 to-blue-400 rounded-3xl transform rotate-3"
                  ></div>
                </Transition>
                <div class="relative bg-white rounded-3xl p-8 shadow-2xl">
                  <div class="space-y-6">
                    <div
                      class="flex items-center gap-4 p-4 bg-blue-50 rounded-xl"
                    >
                      <div
                        class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0"
                      >
                        <Briefcase class="w-8 h-8 text-white" />
                      </div>
                      <div class="flex-1">
                        <div class="text-sm text-gray-600">Latest Position</div>
                        <div class="text-lg text-gray-900">
                          No positions available
                        </div>
                        <div class="text-sm text-gray-500">Check back soon</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
          <div
            class="inline-block px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm mb-4"
          >
            Simple Process
          </div>
          <h2 class="text-4xl text-gray-900 mb-4">How It Works</h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Get hired in four simple steps
          </p>
        </div>

        <div class="grid md:grid-cols-4 gap-8">
          <div
            v-for="(step, index) in howItWorksSteps"
            :key="step.title"
            class="text-center group"
          >
            <div class="relative mb-6">
              <div
                class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform"
              >
                <component :is="step.icon" class="w-10 h-10 text-white" />
              </div>
              <div
                class="absolute -top-2 -right-2 w-8 h-8 bg-blue-400 text-white rounded-full flex items-center justify-center text-sm"
              >
                {{ index + 1 }}
              </div>
            </div>
            <h3 class="text-xl mb-2 text-gray-900">{{ step.title }}</h3>
            <p class="text-gray-600">{{ step.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Grid -->
    <section class="py-20 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
          <h2 class="text-4xl text-gray-900 mb-4">Why Choose {{ shortName }} Portal?</h2>
          <p class="text-xl text-gray-600">
            Everything you need for a successful job search
          </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <div
            v-for="feature in featureCards"
            :key="feature.title"
            class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition-shadow border border-gray-100"
          >
            <div
              :class="[
                'w-14 h-14 rounded-xl flex items-center justify-center mb-4',
                feature.iconBg,
              ]"
            >
              <component
                :is="feature.icon"
                :class="['w-7 h-7', feature.iconColor]"
              />
            </div>
            <h3 class="text-xl mb-3 text-gray-900">{{ feature.title }}</h3>
            <p class="text-gray-600 leading-relaxed">
              {{ feature.description }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-6">
      <div class="max-w-5xl mx-auto">
        <div
          class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-3xl p-12 text-center text-white shadow-2xl relative overflow-hidden"
        >
          <div
            class="absolute top-0 right-0 w-64 h-64 bg-blue-500 rounded-full opacity-20 blur-3xl"
          ></div>
          <div
            class="absolute bottom-0 left-0 w-64 h-64 bg-blue-400 rounded-full opacity-20 blur-3xl"
          ></div>
          <div class="relative z-10">
            <h2 class="text-4xl mb-4">Ready to Start Your Journey?</h2>
            <p class="text-xl mb-8 text-blue-100">
              Join {{ shortName }} today and unlock endless career opportunities
            </p>
            <div class="flex flex-wrap gap-4 justify-center">
              <button
                class="px-8 py-4 bg-white text-blue-600 rounded-xl hover:bg-gray-100 transition-all hover:shadow-xl flex items-center gap-2"
                @click="handleNavigate('/registration')"
              >
                <UserFilled class="w-5 h-5" />
                Create Free Account
              </button>
              <button
                class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-xl hover:bg-white hover:text-blue-600 transition-all flex items-center gap-2"
                @click="handleNavigate('/login')"
              >
                <Key class="w-5 h-5" />
                Sign In Now
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <!-- <footer class="bg-gray-900 text-white py-12 px-6">
      <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-4 gap-8 mb-8">
          <div>
            <div class="flex items-center gap-2 mb-4">
              <div
                class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center"
              >
                <Briefcase class="w-5 h-5 text-white" />
              </div>
              <span class="text-lg font-semibold"
                >{{ companyName }}</span
              >
            </div>
            <p class="text-gray-400">
              Empowering careers through innovative HR technology solutions.
            </p>
          </div>

          <div>
            <h4 class="mb-4">Quick Links</h4>
            <div class="space-y-2">
              <button
                v-for="link in quickLinks"
                :key="link.label"
                class="block text-gray-400 hover:text-white transition-colors"
                @click="handleNavigate(link.path)"
              >
                {{ link.label }}
              </button>
            </div>
          </div>

          <div>
            <h4 class="mb-4">Resources</h4>
            <div class="space-y-2 text-gray-400">
              <div>Help Center</div>
              <div>FAQs</div>
              <div>Career Tips</div>
            </div>
          </div>

          <div>
            <h4 class="mb-4">Contact</h4>
            <div class="space-y-2 text-gray-400">
              <div>support@privatehris.com</div>
              <div>+63 (02) 1234-5678</div>
              <div>Manila, Philippines</div>
            </div>
          </div>
        </div>

        <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
          <p>{{ companyName }}.</p>
          <p>All rights reserved.</p>
          <p>&copy; {{ currentYear }}</p>
        </div>
      </div>
    </footer> -->
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import {
  Briefcase,
  UserFilled,
  Key,
  Search,
  Bell,
  Finished,
  TrendCharts,
  Medal,
  Avatar,
  ArrowLeft,
  ArrowRight,
} from "@element-plus/icons-vue";
import { ApiService } from "@/services/api.js";
import { useCompanyBranding } from "@/composables/useCompanyBranding.js";
import heroBg from "@/assets/img/landingpagebg.jpg";

const router = useRouter();
const { companyName, shortName, companyLogo, loadCompany } = useCompanyBranding();
const logo = companyLogo;

// Reactive data
const positions = ref([]);
const loading = ref(false);
const latestPosition = ref(null);
const currentPositionIndex = ref(0);
const isPaused = ref(false);
const stats = ref({
  activeJobs: 0,
  newThisWeek: 0,
  departments: [],
  totalApplicants: 0,
  successRate: 0,
});

let positionAutoRotateInterval = null;

// Navigate through positions in the card
const navigatePosition = (direction) => {
  if (positions.value.length === 0) return;

  if (direction === "next") {
    currentPositionIndex.value =
      (currentPositionIndex.value + 1) % positions.value.length;
  } else {
    currentPositionIndex.value =
      (currentPositionIndex.value - 1 + positions.value.length) %
      positions.value.length;
  }

  latestPosition.value = positions.value[currentPositionIndex.value];
};

// Auto-rotate positions every 8 seconds
const startPositionAutoRotate = () => {
  if (positionAutoRotateInterval) {
    clearInterval(positionAutoRotateInterval);
  }

  positionAutoRotateInterval = setInterval(() => {
    if (!isPaused.value && positions.value.length > 1) {
      navigatePosition("next");
    }
  }, 8000); // 8 seconds
};

// Pause auto-rotation when user hovers over the card
const pauseAutoRotate = () => {
  isPaused.value = true;
};

// Resume auto-rotation when user leaves the card
const resumeAutoRotate = () => {
  isPaused.value = false;
};

// Computed properties
const heroStats = computed(() => [
  { value: `${stats.value.activeJobs}+`, label: "Active Plantilla" },
  {
    value:
      stats.value.totalApplicants > 0
        ? stats.value.totalApplicants.toLocaleString()
        : "0",
    label: "Total Applicants",
  },
  { value: `${stats.value.successRate}%`, label: "Success Rate" },
]);

const departments = computed(() => {
  return stats.value.departments.slice(0, 3);
});

// Applicants count for the currently displayed position
const currentApplicantsCount = computed(() => {
  if (!latestPosition.value) return 0;

  // Try several common backend field names
  return (
    latestPosition.value.applicants_count ||
    latestPosition.value.applicants ||
    latestPosition.value.applications_count ||
    0
  );
});

// Get departments for the current position only
const currentPositionDepartments = computed(() => {
  if (!latestPosition.value || !latestPosition.value.department) {
    return [];
  }

  const deptName = latestPosition.value.department;
  // Count how many positions are in this department
  const count = positions.value.filter(
    (pos) => pos.department === deptName,
  ).length;

  return [
    {
      name: deptName,
      count: count,
    },
  ];
});

// Fetch positions and calculate statistics
const fetchLandingPageData = async () => {
  try {
    loading.value = true;
    const response = await ApiService.getVacancies();

    let vacancies = [];
    let applicantCounts = [];

    if (response.data?.data?.vacancies) {
      vacancies = response.data.data.vacancies;
      applicantCounts = response.data.data.applicant_count || [];
      // Extract total applicants and success rate from response
      stats.value.totalApplicants = response.data.data.total_applicants || 0;
      stats.value.successRate = response.data.data.success_rate || 0;
    } else if (response.data?.data && Array.isArray(response.data.data)) {
      vacancies = response.data.data;
    } else if (Array.isArray(response.data)) {
      vacancies = response.data;
    }

    // Map applicant counts by position_applied_id and attach to vacancies
    if (applicantCounts.length > 0) {
      const countMap = new Map(
        applicantCounts.map((item) => [item.position_applied_id, item.total]),
      );

      vacancies = vacancies.map((v) => ({
        ...v,
        applicants_count: countMap.get(v.id) || 0,
      }));
    }

    positions.value = vacancies;

    // Calculate statistics
    stats.value.activeJobs = vacancies.length;

    // Get latest position (most recent publication_from or first in array)
    if (vacancies.length > 0) {
      const sorted = [...vacancies].sort((a, b) => {
        const dateA = new Date(a.publication_from || a.created_at || 0);
        const dateB = new Date(b.publication_from || b.created_at || 0);
        return dateB - dateA;
      });
      positions.value = sorted;
      currentPositionIndex.value = 0;
      latestPosition.value = sorted[0];

      // Start auto-rotation if there are multiple positions
      if (sorted.length > 1) {
        startPositionAutoRotate();
      }
    } else {
      latestPosition.value = null;
      currentPositionIndex.value = 0;
      // Stop auto-rotation if no positions
      if (positionAutoRotateInterval) {
        clearInterval(positionAutoRotateInterval);
        positionAutoRotateInterval = null;
      }
    }

    // Calculate new positions this week
    const oneWeekAgo = new Date();
    oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
    stats.value.newThisWeek = vacancies.filter((pos) => {
      const pubDate = new Date(pos.publication_from || pos.created_at);
      return pubDate >= oneWeekAgo;
    }).length;

    // Group by department
    const deptMap = new Map();
    vacancies.forEach((pos) => {
      const dept = pos.department || "General";
      deptMap.set(dept, (deptMap.get(dept) || 0) + 1);
    });

    stats.value.departments = Array.from(deptMap.entries())
      .map(([name, count]) => ({ name, count }))
      .sort((a, b) => b.count - a.count)
      .slice(0, 3);
  } catch (error) {
    console.error("Error fetching landing page data:", error);
    // Keep default values on error
  } finally {
    loading.value = false;
  }
};

// Format salary display
const formatSalary = (position) => {
  if (position.grade && position.step) {
    // You might want to fetch actual salary ranges from the API
    return `SG-${position.grade} Step ${position.step}`;
  }
  return "Competitive salary";
};

// Load data on mount
onMounted(() => {
  loadCompany();
  fetchLandingPageData();
});

// Cleanup on unmount
onUnmounted(() => {
  if (positionAutoRotateInterval) {
    clearInterval(positionAutoRotateInterval);
  }
});

const howItWorksSteps = [
  {
    title: "Create Account",
    description: "Sign up and build your professional profile in minutes.",
    icon: UserFilled,
  },
  {
    title: "Browse Jobs",
    description: "Explore hundreds of opportunities across departments.",
    icon: Search,
  },
  {
    title: "Apply Instantly",
    description: "Submit applications with one click using your profile.",
    icon: Briefcase,
  },
  {
    title: "Get Hired",
    description: "Track your progress and start your new career.",
    icon: Medal,
  },
];

const featureCards = [
  {
    title: "Real-Time Notifications",
    description:
      "Get instant alerts for new job postings, application updates, and interview schedules right to your dashboard.",
    icon: Bell,
    iconBg: "bg-blue-100",
    iconColor: "text-blue-600",
  },
  {
    title: "Secure & Private",
    description:
      "Your personal information is protected with enterprise-grade security and strict privacy policies.",
    icon: Finished,
    iconBg: "bg-green-100",
    iconColor: "text-green-600",
  },
  {
    title: "Career Growth",
    description:
      "Access professional development resources and track your progress within the organization.",
    icon: TrendCharts,
    iconBg: "bg-purple-100",
    iconColor: "text-purple-600",
  },
  {
    title: "Smart Search",
    description:
      "Advanced filtering and matching algorithms help you find positions that perfectly fit your skills and interests.",
    icon: Search,
    iconBg: "bg-orange-100",
    iconColor: "text-orange-600",
  },
  {
    title: "HR Support",
    description:
      "Dedicated HR specialists are available to answer questions and guide you through the hiring process.",
    icon: Avatar,
    iconBg: "bg-pink-100",
    iconColor: "text-pink-600",
  },
  {
    title: "Quality Positions",
    description:
      "All job postings are verified and come with competitive compensation and benefits packages.",
    icon: Medal,
    iconBg: "bg-cyan-100",
    iconColor: "text-cyan-600",
  },
];

const quickLinks = [
  { label: "Home", path: "/" },
  { label: "Job Offers", path: "/positions" },
  { label: "Register", path: "/registration" },
];

const currentYear = computed(() => new Date().getFullYear());

const handleNavigate = (path) => {
  router.push(path);
};
</script>

<style>
@import "../assets/styles/View-Home-Style.css";

/* Slide transition for position card - SMOOTHER VERSION */
.fade-enter-from {
  opacity: 0;
  transform: translateX(-100px);
}

.fade-enter-to {
  opacity: 1;
  transform: translateX(0);
}

.fade-leave-from {
  opacity: 1;
  transform: translateX(0);
}

.fade-leave-to {
  opacity: 0;
  transform: translateX(100px);
}

.fade-enter-active,
.fade-leave-active {
  transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform, opacity;
}

/* Layer/cover animation for decorative background - SMOOTHER VERSION */
.layer-enter-from {
  opacity: 0;
  transform: rotate(-3deg) scale(0.9);
  z-index: 1;
}

.layer-enter-to {
  opacity: 1;
  transform: rotate(3deg) scale(1);
  z-index: 1;
}

.layer-leave-from {
  opacity: 1;
  transform: rotate(3deg) scale(1);
  z-index: 1;
}

.layer-leave-to {
  opacity: 0;
  transform: rotate(-3deg) scale(0.9);
  z-index: 1;
}

.layer-enter-active,
.layer-leave-active {
  transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform, opacity;
}
</style>
