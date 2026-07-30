<template>
  <div class="min-h-screen bg-white">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center">
            <div
              class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center mr-3"
            >
              <span class="text-white font-bold text-sm">WTI</span>
            </div>
            <div>
              <h1 class="text-xl font-semibold text-gray-900">WTI HRMP</h1>
              <p class="text-sm text-gray-500">Applicant Portal</p>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <router-link
              to="/login"
              class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
            >
              Login
            </router-link>
            <router-link
              to="/register"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              Get Started
            </router-link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="text-center">
          <h1 class="text-4xl md:text-6xl font-bold mb-6">
            Welcome to WTI HRMP
            <span class="block text-blue-200">Applicant Portal</span>
          </h1>
          <p class="text-xl md:text-2xl text-blue-100 mb-8 max-w-3xl mx-auto">
            Streamline your job application process with our comprehensive Human
            Resource Management Portal. Apply for positions, take examinations,
            and manage your professional profile all in one place.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <router-link
              to="/register"
              class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
            >
              Start Your Application
            </router-link>
            <router-link
              to="/about"
              class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors"
            >
              Learn More
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            Why Choose WTI HRMP Portal?
          </h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Experience a modern, efficient, and user-friendly application
            process designed for both applicants and HR professionals.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Feature 1 -->
          <div class="bg-white p-8 rounded-xl shadow-sm border text-center">
            <div
              class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6"
            >
              <i class="fas fa-file-alt text-2xl text-blue-600"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-4">
              Easy Application Process
            </h3>
            <p class="text-gray-600">
              Complete your Personal Data Sheet (PDS) online with our intuitive
              form system. Save your progress and update information anytime.
            </p>
          </div>

          <!-- Feature 2 -->
          <div class="bg-white p-8 rounded-xl shadow-sm border text-center">
            <div
              class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6"
            >
              <i class="fas fa-clipboard-check text-2xl text-green-600"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-4">
              Online Examinations
            </h3>
            <p class="text-gray-600">
              Take required examinations online with real-time scoring and
              immediate results. Track your performance and review past exams.
            </p>
          </div>

          <!-- Feature 3 -->
          <div class="bg-white p-8 rounded-xl shadow-sm border text-center">
            <div
              class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6"
            >
              <i class="fas fa-chart-line text-2xl text-purple-600"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-4">
              Application Tracking
            </h3>
            <p class="text-gray-600">
              Monitor your application status in real-time. Receive
              notifications about updates, requirements, and next steps in the
              hiring process.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            How It Works
          </h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Get started with your application in just a few simple steps
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <!-- Step 1 -->
          <div class="text-center">
            <div
              class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6"
            >
              <span class="text-white font-bold text-xl">1</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              Create Account
            </h3>
            <p class="text-gray-600">
              Register with your basic information to get started
            </p>
          </div>

          <!-- Step 2 -->
          <div class="text-center">
            <div
              class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6"
            >
              <span class="text-white font-bold text-xl">2</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              Complete PDS
            </h3>
            <p class="text-gray-600">
              Fill out your Personal Data Sheet with comprehensive information
            </p>
          </div>

          <!-- Step 3 -->
          <div class="text-center">
            <div
              class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6"
            >
              <span class="text-white font-bold text-xl">3</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              Apply for Positions
            </h3>
            <p class="text-gray-600">
              Browse available positions and submit your applications
            </p>
          </div>

          <!-- Step 4 -->
          <div class="text-center">
            <div
              class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6"
            >
              <span class="text-white font-bold text-xl">4</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              Take Examinations
            </h3>
            <p class="text-gray-600">
              Complete required examinations and track your results
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Available Positions Section -->
    <section class="py-20 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            Available Positions
          </h2>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Explore current job openings and find the perfect position for your
            career growth
          </p>
        </div>

        <!-- Loading State -->
        <div v-if="loadingPositions" class="text-center py-12">
          <div
            class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
          ></div>
          <p class="mt-4 text-gray-600">Loading available positions...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="positionsError" class="text-center py-12">
          <div
            class="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md mx-auto"
          >
            <i
              class="fas fa-exclamation-triangle text-red-500 text-2xl mb-4"
            ></i>
            <p class="text-red-700">{{ positionsError }}</p>
            <button
              @click="fetchPositions"
              class="mt-4 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors"
            >
              Try Again
            </button>
          </div>
        </div>

        <!-- Positions Grid -->
        <div
          v-else-if="positions.length > 0"
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
          <div
            v-for="position in positions"
            :key="position.id"
            class="position-card bg-white border border-gray-200 rounded-lg p-6"
          >
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                  {{ position.position }}
                  <span
                    v-if="position.isNonPlantilla"
                    class="ml-2 inline-block text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full"
                  >
                    Non‑Plantilla
                  </span>
                </h3>
                <p class="text-sm text-gray-600 mb-2">{{ position.code }}</p>
                <div class="flex items-center space-x-4 text-sm text-gray-500">
                  <span class="flex items-center">
                    <i class="fas fa-layer-group mr-1"></i>
                    SG-{{ position.grade }}
                  </span>
                  <span class="flex items-center">
                    <i class="fas fa-step-forward mr-1"></i>
                    Step {{ position.step }}
                  </span>
                </div>
              </div>
              <div
                class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium"
              >
                {{ position.vacant }}
                {{ position.vacant === 1 ? "vacancy" : "vacancies" }}
              </div>
            </div>

            <p
              v-if="position.description"
              class="text-gray-600 text-sm mb-4 line-clamp-3"
            >
              {{ position.description }}
            </p>

            <div class="flex items-center justify-between">
              <router-link
                to="/positions"
                class="text-blue-600 hover:text-blue-700 font-medium text-sm transition-colors"
              >
                Apply Now →
              </router-link>
              <button
                @click="viewPositionDetails(position)"
                class="text-gray-500 hover:text-gray-700 text-sm transition-colors"
              >
                View Details
              </button>
            </div>
          </div>
        </div>

        <!-- No Positions State -->
        <div v-else class="text-center py-12">
          <div
            class="bg-gray-50 border border-gray-200 rounded-lg p-8 max-w-md mx-auto"
          >
            <i class="fas fa-briefcase text-gray-400 text-3xl mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              No Positions Available
            </h3>
            <p class="text-gray-600 mb-4">
              There are currently no open positions. Please check back later.
            </p>
            <router-link
              to="/positions"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
            >
              View Positions
            </router-link>
          </div>
        </div>

        <!-- View All Positions CTA -->
        <div v-if="positions.length > 0" class="text-center mt-12">
          <router-link
            to="/positions"
            class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
          >
            View All Positions
            <i class="fas fa-arrow-right ml-2"></i>
          </router-link>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section class="py-20 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
              About WTI HRMP
            </h2>
            <p class="text-lg text-gray-600 mb-6">
              The WTI Human Resource Management Portal is designed to streamline
              the recruitment and application process for {{ companyName }}. Our platform provides a comprehensive solution
              for managing job applications, examinations, and candidate
              profiles.
            </p>
            <p class="text-lg text-gray-600 mb-8">
              We are committed to providing a modern, efficient, and
              user-friendly experience for both applicants and HR professionals,
              ensuring transparency and fairness in the hiring process.
            </p>
            <router-link
              to="/about"
              class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
            >
              Learn More About Us
            </router-link>
          </div>
          <div class="bg-white p-8 rounded-xl shadow-sm border">
            <h3 class="text-xl font-semibold text-gray-900 mb-6">
              Key Benefits
            </h3>
            <ul class="space-y-4">
              <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <span class="text-gray-700"
                  >Secure and confidential data handling</span
                >
              </li>
              <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <span class="text-gray-700"
                  >Real-time application status updates</span
                >
              </li>
              <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <span class="text-gray-700"
                  >Comprehensive examination system</span
                >
              </li>
              <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <span class="text-gray-700">Mobile-responsive design</span>
              </li>
              <li class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <span class="text-gray-700">24/7 accessibility</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-blue-600 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">
          Ready to Start Your Career Journey?
        </h2>
        <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
          Join thousands of applicants who have successfully used our portal to
          advance their careers at {{ companyName }}.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <router-link
            to="/register"
            class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-colors"
          >
            Create Account Now
          </router-link>
          <router-link
            to="/login"
            class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition-colors"
          >
            Sign In
          </router-link>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <div class="flex items-center mb-4">
              <div
                class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center mr-3"
              >
                <span class="text-white font-bold text-sm">WTI</span>
              </div>
              <div>
                <h3 class="font-semibold">WTI HRMP</h3>
                <p class="text-sm text-gray-400">Applicant Portal</p>
              </div>
            </div>
            <p class="text-gray-400 text-sm">
              Streamlining the recruitment process for {{ companyName }}.
            </p>
          </div>

          <div>
            <h4 class="font-semibold mb-4">Quick Links</h4>
            <ul class="space-y-2 text-sm text-gray-400">
              <li>
                <router-link to="/about" class="hover:text-white"
                  >About Us</router-link
                >
              </li>
              <li>
                <router-link to="/positions" class="hover:text-white"
                  >Available Positions</router-link
                >
              </li>
              <li>
                <router-link to="/contact" class="hover:text-white"
                  >Contact</router-link
                >
              </li>
              <li>
                <router-link to="/help" class="hover:text-white"
                  >Help Center</router-link
                >
              </li>
            </ul>
          </div>

          <div>
            <h4 class="font-semibold mb-4">Support</h4>
            <ul class="space-y-2 text-sm text-gray-400">
              <li>
                <a href="#" class="hover:text-white">Technical Support</a>
              </li>
              <li><a href="#" class="hover:text-white">FAQ</a></li>
              <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
              <li><a href="#" class="hover:text-white">Terms of Service</a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Contact Info</h4>
            <ul class="space-y-2 text-sm text-gray-400">
              <li class="flex items-center">
                <i class="fas fa-map-marker-alt mr-2"></i>
                <span>{{ companyName }}</span>
              </li>
              <li class="flex items-center">
                <i class="fas fa-phone mr-2"></i>
                <span>+63 2 1234 5678</span>
              </li>
              <li class="flex items-center">
                <i class="fas fa-envelope mr-2"></i>
                <span>info@wti-hrmp.gov.ph</span>
              </li>
            </ul>
          </div>
        </div>
        <div class="border-t border-gray-800 mt-8 pt-8 text-center">
          <p class="text-gray-400 text-sm">
            © 2024 WTI HRMP Applicant Portal. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
import { ApiService } from "../services/api.js";
import { ElNotification, ElMessageBox } from "element-plus";
import { fetchCompanyPublic, getCompanyPublic } from "../services/companyPublic";

export default {
  name: "LandingPage",
  data() {
    return {
      companyName: getCompanyPublic().name?.trim() || "Company",
      positions: [],
      loadingPositions: false,
      positionsError: null,
    };
  },
  async mounted() {
    const company = await fetchCompanyPublic();
    this.companyName = company?.name?.trim() || "Company";
    await this.fetchPositions();
  },
  methods: {
    async fetchPositions() {
      this.loadingPositions = true;
      this.positionsError = null;

      try {
        const response = await ApiService.getVacancies();
        if (response.data && response.data.data) {
          const vac = response.data.data.vacancies || [];
          const nonVac = response.data.data.non_plantilla_vacancies || [];
          this.positions = [...vac, ...nonVac].map((position) => ({
            ...position,
            isNonPlantilla: position.type === "non_plantilla",
          }));
        } else {
          this.positions = [];
        }
      } catch (error) {
        console.error("Error fetching positions:", error);
        this.positionsError =
          "Failed to load available positions. Please try again later.";
        this.positions = [];
        ElNotification({
          title: "Failed to load positions",
          message:
            error.response?.data?.message ||
            "Failed to load available positions. Please try again later.",
          type: "error",
          duration: 5000,
          position: "top-right",
        });
      } finally {
        this.loadingPositions = false;
      }
    },

    viewPositionDetails(position) {
      const details = `
        Position: ${position.position}
        Code: ${position.code}
        ${position.isNonPlantilla ? "NON-PLANTILLA" : "Salary Grade"}: ${
  position.isNonPlantilla ? position.salary || "N/A" : position.grade
}
        ${position.isNonPlantilla ? "" : `Step: ${position.step}`}
        Vacancies: ${position.vacant}
        ${position.description ? `\nDescription: ${position.description}` : ""}
      `;
      ElMessageBox.alert(details, "Position Details", {
        confirmButtonText: "Close",
        type: "info",
      });
    },
  },
};
</script>

<style scoped>
/* Custom styles for position cards */
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  line-clamp: 3;
  overflow: hidden;
}

/* Hover effects for position cards */
.position-card {
  transition: all 0.3s ease;
}

.position-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Loading animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
