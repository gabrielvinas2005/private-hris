<template>
  <div class="pds-work-experience">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Work Experience</h3>

    <div class="space-y-6">
      <!-- Work Experience Entries Section -->
      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div
          class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200 rounded-t-lg"
        >
          <div class="flex justify-between items-center">
            <h4
              class="text-lg font-semibold text-gray-900 inline-flex items-center"
            >
              <Briefcase :size="20" class="mr-2 text-indigo-600" /> Work
              Experience Records
            </h4>
            <div class="flex gap-2">
              <div v-if="workExperienceData.length > 0" class="relative">
                <button
                  @click="showReportMenu = !showReportMenu"
                  :disabled="generatingReport"
                  class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 shadow-sm transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                  <span
                    v-if="generatingReport"
                    class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"
                  ></span>
                  <svg
                    v-else
                    class="w-4 h-4 mr-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    ></path>
                  </svg>
                  {{ generatingReport ? "Generating..." : "Generate Report" }}
                  <svg
                    v-if="!generatingReport"
                    class="w-4 h-4 ml-2"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 9l-7 7-7-7"
                    ></path>
                  </svg>
                </button>
                <!-- Dropdown Menu -->
                <div
                  v-if="showReportMenu"
                  class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                  @click.stop
                >
                  <div class="py-1" role="menu">
                    <button
                      @click="generateReport('preview')"
                      class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center"
                      role="menuitem"
                    >
                      <svg
                        class="w-4 h-4 mr-2 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        ></path>
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        ></path>
                      </svg>
                      Preview PDF
                    </button>
                    <button
                      @click="generateReport('pdf')"
                      class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center"
                      role="menuitem"
                    >
                      <svg
                        class="w-4 h-4 mr-2 text-red-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        ></path>
                      </svg>
                      Download PDF
                    </button>
                    <button
                      @click="generateReport('word')"
                      class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center"
                      role="menuitem"
                    >
                      <svg
                        class="w-4 h-4 mr-2 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        ></path>
                      </svg>
                      Download Word (.doc)
                    </button>
                  </div>
                </div>
              </div>
              <button
                @click="addWorkExperience"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors"
              >
                Add Work Experience
              </button>
            </div>
          </div>
        </div>

        <div class="p-6">
          <div
            v-if="workExperienceData.length === 0"
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
                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
              ></path>
            </svg>
            <p>
              No Work Experience added yet. Click "Add Work Experience" to get
              started.
            </p>
          </div>

          <div v-else class="space-y-6">
            <div
              v-for="(experience, index) in workExperienceData"
              :key="experience.key || experience.id || index"
              class="bg-gray-50 rounded-lg p-6 border border-gray-200"
            >
              <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center"
                  >
                    <span class="text-green-600 font-semibold text-sm">{{
                      index + 1
                    }}</span>
                  </div>
                  <h5 class="text-lg font-medium text-gray-800">
                    {{ experience.officeName || "Work Experience Record" }}
                  </h5>
                </div>
                <button
                  @click="removeWorkExperience(index)"
                  class="text-red-600 hover:text-red-800 transition-colors duration-200 p-1 rounded-full hover:bg-red-50"
                  title="Remove work experience record"
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

              <!-- Office/Company Name (Office_name) -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Office/Company Name *</label
                >
                <input
                  v-model="experience.officeName"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., Wizzard Technologies Inc."
                  required
                />
              </div>

              <!-- Office Address (Office_Address) -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Office Address *</label
                >
                <input
                  v-model="experience.officeAddress"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., Manila City, BGC Taguig"
                  required
                />
              </div>

              <!-- Inclusive Dates -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Inclusive Dates</label
                >
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >From *</label
                    >
                    <input
                      v-model="experience.dateFrom"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1"
                      >To</label
                    >
                    <input
                      v-model="experience.dateTo"
                      type="date"
                      :disabled="experience.isPresent"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                    />
                  </div>
                  <div class="flex items-end">
                    <label class="flex items-center space-x-2 text-sm">
                      <input
                        v-model="experience.isPresent"
                        type="checkbox"
                        @change="handlePresentChange(experience)"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                      />
                      <span class="text-gray-700">Present</span>
                    </label>
                  </div>
                </div>
              </div>

              <!-- Position (Position) -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Position Title *</label
                >
                <input
                  v-model="experience.position"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., Junior Web Dev, Software Developer, Manager"
                  required
                />
              </div>

              <!-- Immediate Supervisor (Immediate_supervisor) -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Immediate Supervisor</label
                >
                <input
                  v-model="experience.immediateSupervisor"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., Marasigan, Beral B."
                />
              </div>

              <!-- Summary of Duties (Summary_of_Duties) -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >Summary of Duties</label
                >
                <textarea
                  v-model="experience.summaryOfDuties"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., Created Task Us Website, key responsibilities..."
                ></textarea>
              </div>

              <!-- List of Accomplishments (List_Of_Accomplishment) -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >List of Accomplishments</label
                >
                <textarea
                  v-model="experience.listOfAccomplishment"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., HRIS Accomplishment, Best Developer of the Year"
                ></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Work Experience Summary -->
        <div
          v-if="workExperienceData.length > 0"
          class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 rounded-xl border border-blue-200 shadow-sm p-6"
        >
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
            >
              <svg
                class="w-6 h-6 text-blue-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                ></path>
              </svg>
            </div>
            <h5 class="text-lg font-semibold text-gray-800">
              Work Experience Summary
            </h5>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Experiences Card -->
            <div
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between mb-2">
                <div
                  class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
                >
                  <svg
                    class="w-5 h-5 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ workExperienceData.length }}
              </div>
              <div class="text-sm text-gray-600 font-medium">
                Total Positions
              </div>
            </div>

            <!-- Total Years Card -->
            <div
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between mb-2">
                <div
                  class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
                >
                  <svg
                    class="w-5 h-5 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ formatYears(totalYears) }}
              </div>
              <div class="text-sm text-gray-600 font-medium">Total Years</div>
            </div>

            <!-- Current Positions Card -->
            <div
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between mb-2">
                <div
                  class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"
                >
                  <svg
                    class="w-5 h-5 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ currentPositions }}
              </div>
              <div class="text-sm text-gray-600 font-medium">
                Current Positions
              </div>
            </div>

            <!-- Average Years Card -->
            <div
              class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
            >
              <div class="flex items-center justify-between mb-2">
                <div
                  class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center"
                >
                  <svg
                    class="w-5 h-5 text-purple-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                    ></path>
                  </svg>
                </div>
              </div>
              <div class="text-3xl font-bold text-gray-900 mb-1">
                {{ formatYears(averageYears) }}
              </div>
              <div class="text-sm text-gray-600 font-medium">
                Avg. Years/Position
              </div>
            </div>
          </div>

          <!-- Additional Stats Row -->
          <div
            v-if="mostRecentPosition || longestPosition"
            class="mt-4 pt-4 border-t border-gray-200"
          >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-if="mostRecentPosition"
                class="flex items-center gap-3 text-sm bg-blue-50 rounded-lg p-3 border border-blue-200"
              >
                <div
                  class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0"
                >
                  <svg
                    class="w-4 h-4 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 10V3L4 14h7v7l9-11h-7z"
                    ></path>
                  </svg>
                </div>
                <div>
                  <div class="text-blue-800 font-medium">Most Recent</div>
                  <div class="text-blue-600 text-xs">
                    {{ mostRecentPosition.position }} at
                    {{ mostRecentPosition.officeName }}
                  </div>
                  <div class="text-blue-500 text-xs">
                    {{
                      formatDateRange(
                        mostRecentPosition.dateFrom,
                        mostRecentPosition.dateTo,
                        mostRecentPosition.isPresent
                      )
                    }}
                  </div>
                </div>
              </div>
              <div
                v-if="longestPosition"
                class="flex items-center gap-3 text-sm bg-indigo-50 rounded-lg p-3 border border-indigo-200"
              >
                <div
                  class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0"
                >
                  <svg
                    class="w-4 h-4 text-indigo-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                    ></path>
                  </svg>
                </div>
                <div>
                  <div class="text-indigo-800 font-medium">
                    Longest Position
                  </div>
                  <div class="text-indigo-600 text-xs">
                    {{ longestPosition.position }} at
                    {{ longestPosition.officeName }}
                  </div>
                  <div class="text-indigo-500 text-xs">
                    {{ formatYears(longestPosition.years) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          @click="saveWorkExperience"
          :disabled="!isFormValid || loading"
          class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white px-6 py-2 rounded-md font-medium transition-colors duration-200 flex items-center gap-2"
        >
          <span v-if="loading" class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
          {{ loading ? 'Saving...' : 'Save Work Experience' }}
        </button>
      </div>
    </div>

    <!-- PDF Preview Modal -->
    <div
      v-if="showPdfModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click.self="closePdfModal"
    >
      <div
        class="bg-white rounded-lg shadow-xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col"
      >
        <div class="p-4 border-b flex justify-between items-center">
          <h3 class="text-lg font-semibold text-gray-900">
            Work Experience Sheet Preview
          </h3>
          <div class="flex gap-2">
            <button
              @click="downloadPdfFromModal"
              class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors"
            >
              <svg
                class="w-4 h-4 mr-1.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                ></path>
              </svg>
              Download
            </button>
            <button
              @click="closePdfModal"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg
                class="w-6 h-6"
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
        </div>
        <div class="flex-1 overflow-auto p-4 bg-gray-100">
          <iframe
            v-if="pdfPreviewUrl"
            :src="pdfPreviewUrl"
            class="w-full h-full min-h-[600px] border-0 rounded"
            style="background: white;"
          ></iframe>
          <div
            v-else
            class="flex items-center justify-center h-full min-h-[600px]"
          >
            <div class="text-center">
              <div
                class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent mb-4"
              ></div>
              <p class="text-gray-600">Loading PDF preview...</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref, onMounted, onUnmounted } from "vue";
import { ElNotification, ElMessageBox } from "element-plus";
import { ApiService } from "@/services/api.js";
import { useAppNotification } from "@/composables/useAppNotification.js";
import { Briefcase } from "lucide-vue-next";

const emit = defineEmits(["saved"]);

const notify = useAppNotification();

const workExperienceData = reactive([]);
const loading = ref(false);
const error = ref(null);
const employeeId = ref(null);
const showReportMenu = ref(false);
const generatingReport = ref(false);
const showPdfModal = ref(false);
const pdfPreviewUrl = ref(null);
const currentWorkExperienceId = ref(null);

const addWorkExperience = () => {
  workExperienceData.push({
    key: generateKey("new"),
    id: null,
    officeName: "",
    officeAddress: "",
    position: "",
    dateFrom: "",
    dateTo: "",
    isPresent: false,
    immediateSupervisor: "",
    summaryOfDuties: "",
    listOfAccomplishment: "",
  });
};

const loadWorkExperienceData = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await ApiService.getApplicantPage();

    if (response.data && response.data.data) {
      const data = response.data.data;

      employeeId.value =
        data.employee_info?.[0]?.id ?? data.employee_id ?? null;

      const source = (data.work_experience && Array.isArray(data.work_experience)) ? data.work_experience : [];

      workExperienceData.splice(0, workExperienceData.length);
      source.forEach((exp) => {
        workExperienceData.push({
          key: `existing-${exp.id}`,
          id: exp.id,
          officeName: exp.Office_name || "",
          officeAddress: exp.Office_Address || "",
          position: exp.Position || "",
          dateFrom: formatDate(exp.Work_start_date),
          dateTo: formatDate(exp.Work_end_date),
          isPresent: false,
          immediateSupervisor: exp.Immediate_supervisor || "",
          summaryOfDuties: exp.Summary_of_Duties || "",
          listOfAccomplishment: exp.List_Of_Accomplishment || "",
        });
      });
    }
  } catch (err) {
    console.error("Error loading work experience data:", err);
    error.value =
      err.response?.data?.message || "Failed to load work experience data";
  } finally {
    loading.value = false;
  }
};

const removeWorkExperience = async (index) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to remove this work experience record?",
      "Remove Work Experience",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
  } catch {
    return;
  }

  const experience = workExperienceData[index];

  if (experience.id) {
    try {
      await ApiService.destroyPDS(14, experience.id);
    } catch (err) {
      console.error("Error deleting work experience record:", err);
      ElNotification({
        title: "Error",
        message: "Failed to delete work experience record from server",
        type: "error",
        duration: 5000,
        position: "top-right",
      });
      return;
    }
  }

  workExperienceData.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Work experience record removed successfully!",
    type: "success",
    duration: 2000,
    position: "top-right",
  });
};

const handlePresentChange = (experience) => {
  if (experience.isPresent) {
    experience.dateTo = "";
  }
};

const isFormValid = computed(() => {
  if (workExperienceData.length === 0) return false;

  return workExperienceData.every(
    (experience) =>
      experience.officeName && experience.officeAddress && experience.position && experience.dateFrom
  );
});

const calculateYears = (dateFrom, dateTo, isPresent) => {
  if (!dateFrom) return 0;

  const startDate = new Date(dateFrom);
  const endDate = isPresent
    ? new Date()
    : dateTo
      ? new Date(dateTo)
      : new Date();

  if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) return 0;
  if (endDate < startDate) return 0;

  const diffTime = Math.abs(endDate - startDate);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  const years = diffDays / 365.25;

  return Math.round(years * 100) / 100; // Round to 2 decimal places
};

const totalYears = computed(() => {
  return workExperienceData.reduce((total, exp) => {
    return total + calculateYears(exp.dateFrom, exp.dateTo, exp.isPresent);
  }, 0);
});

const currentPositions = computed(() => {
  return workExperienceData.filter((exp) => exp.isPresent).length;
});

const averageYears = computed(() => {
  if (workExperienceData.length === 0) return 0;
  return totalYears.value / workExperienceData.length;
});

const mostRecentPosition = computed(() => {
  if (workExperienceData.length === 0) return null;

  const positionsWithDates = workExperienceData
    .filter((exp) => exp.dateFrom)
    .map((exp) => ({
      ...exp,
      dateObj: new Date(exp.dateFrom),
    }))
    .sort((a, b) => b.dateObj - a.dateObj);

  return positionsWithDates.length > 0 ? positionsWithDates[0] : null;
});

const longestPosition = computed(() => {
  if (workExperienceData.length === 0) return null;

  const positionsWithYears = workExperienceData
    .filter((exp) => exp.dateFrom)
    .map((exp) => ({
      ...exp,
      years: calculateYears(exp.dateFrom, exp.dateTo, exp.isPresent),
    }))
    .sort((a, b) => b.years - a.years);

  return positionsWithYears.length > 0 && positionsWithYears[0].years > 0
    ? positionsWithYears[0]
    : null;
});

const formatYears = (years) => {
  if (!years && years !== 0) return "0";
  if (years < 1) {
    const months = Math.round(years * 12);
    return months === 1 ? "1 month" : `${months} months`;
  }
  const wholeYears = Math.floor(years);
  const months = Math.round((years - wholeYears) * 12);
  if (months === 0) {
    return wholeYears === 1 ? "1 year" : `${wholeYears} years`;
  }
  return `${wholeYears}.${Math.floor(months / 10)}${months % 10} years`;
};

const formatDateRange = (dateFrom, dateTo, isPresent) => {
  if (!dateFrom) return "";

  const formatDateForDisplay = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
    });
  };

  const from = formatDateForDisplay(dateFrom);
  const to = isPresent ? "Present" : formatDateForDisplay(dateTo);

  return `${from} - ${to}`;
};

const saveWorkExperience = async () => {
  // Prevent double submission
  if (loading.value) {
    return;
  }

  if (!isFormValid.value) {
    notify.error(
      "Validation",
      "Please fill in all required fields (Office/Company Name, Office Address, Position Title, and From date)",
      { duration: 4000 },
    );
    return;
  }

  try {
    loading.value = true;

    const resolvedEmployeeId = employeeId.value;

    if (!resolvedEmployeeId) {
      throw new Error("Employee information not found");
    }

    for (const exp of workExperienceData) {
      const formData = new FormData();

      formData.append("section", "work");
      formData.append("office_name", exp.officeName || "");
      formData.append("office_address", exp.officeAddress || "");
      formData.append("position", exp.position || "");
      formData.append("work_start_date", exp.dateFrom || "");
      formData.append("work_end_date", exp.isPresent ? "" : (exp.dateTo || ""));
      formData.append("is_present", exp.isPresent ? "1" : "0");
      formData.append("immediate_supervisor", exp.immediateSupervisor || "");
      formData.append("list_of_accomplishment", exp.listOfAccomplishment || "");
      formData.append("summary_of_duties", exp.summaryOfDuties || "");

      if (exp.id) {
        formData.append("work_experience_id", exp.id);
      }

      const response = await ApiService.storePDS(resolvedEmployeeId, formData);

      if (response.data && response.data.data && response.data.data.id) {
        exp.id = response.data.data.id;
      }
    }

    notify.success("Saved", "Work experience saved successfully!", {
      duration: 4000,
    });
    await loadWorkExperienceData();
    emit("saved");
  } catch (err) {
    console.error("Error saving work experience data:", err);
    const msg =
      err.response?.data?.message ||
      (err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(" ")
        : null) ||
      err.message ||
      "Failed to save work experience data. Please try again.";
    notify.error("Save failed", msg, { duration: 6000 });
  } finally {
    loading.value = false;
  }
};

const formatDate = (value) => {
  if (!value) return "";
  // accept yyyy-mm-dd or yyyy; leave as-is if already yyyy-mm-dd
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

const generateReport = async (type) => {
  if (workExperienceData.length === 0) {
    notify.error("No data", "No work experience data available to generate report.", {
      duration: 4000,
    });
    return;
  }

  // Get the first work experience ID (or the most recent one)
  const workExperienceId = workExperienceData.find((exp) => exp.id)?.id;

  if (!workExperienceId) {
    notify.error(
      "Save required",
      "Please save your work experience data before generating a report.",
      { duration: 4000 },
    );
    return;
  }

  try {
    generatingReport.value = true;
    showReportMenu.value = false;

    let response;
    let filename;
    let contentType;

    switch (type) {
      case "preview":
        response = await ApiService.generateWorkExperiencePreview(
          workExperienceId
        );
        contentType = "application/pdf";
        // Create blob URL for modal preview
        const previewBlob = new Blob([response.data], { type: contentType });
        pdfPreviewUrl.value = window.URL.createObjectURL(previewBlob);
        currentWorkExperienceId.value = workExperienceId;
        showPdfModal.value = true;
        break;

      case "pdf":
        response = await ApiService.downloadWorkExperiencePDF(workExperienceId);
        contentType = "application/pdf";
        filename = `Work_Experience_Sheet_${workExperienceId}.pdf`;
        break;

      case "word":
        response = await ApiService.downloadWorkExperienceWord(workExperienceId);
        contentType = "application/msword";
        filename = `Work_Experience_Sheet_${workExperienceId}.doc`;
        break;

      default:
        throw new Error("Invalid report type");
    }

    // For downloads (not preview)
    if (type !== "preview") {
      const blob = new Blob([response.data], { type: contentType });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      notify.success("Downloaded", "Report downloaded successfully!", {
        duration: 4000,
      });
    }
  } catch (err) {
    console.error("Error generating report:", err);
    const msg =
      err.response?.data?.message ||
      err.message ||
      "Failed to generate report. Please try again.";
    notify.error("Download failed", msg, { duration: 6000 });
  } finally {
    generatingReport.value = false;
  }
};

const closePdfModal = () => {
  if (pdfPreviewUrl.value) {
    window.URL.revokeObjectURL(pdfPreviewUrl.value);
    pdfPreviewUrl.value = null;
  }
  showPdfModal.value = false;
  currentWorkExperienceId.value = null;
};

const downloadPdfFromModal = async () => {
  if (!currentWorkExperienceId.value) return;

  try {
    generatingReport.value = true;
    const response = await ApiService.downloadWorkExperiencePDF(
      currentWorkExperienceId.value
    );
    const blob = new Blob([response.data], { type: "application/pdf" });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `Work_Experience_Sheet_${currentWorkExperienceId.value}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    notify.success("Downloaded", "PDF downloaded successfully!", {
      duration: 4000,
    });
  } catch (err) {
    console.error("Error downloading PDF:", err);
    const msg =
      err.response?.data?.message ||
      err.message ||
      "Failed to download PDF. Please try again.";
    notify.error("Download failed", msg, { duration: 6000 });
  } finally {
    generatingReport.value = false;
  }
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (!event.target.closest(".relative")) {
    showReportMenu.value = false;
  }
};

onMounted(() => {
  loadWorkExperienceData();
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
  // Cleanup PDF preview URL if modal is still open
  if (pdfPreviewUrl.value) {
    window.URL.revokeObjectURL(pdfPreviewUrl.value);
  }
});
</script>

<style scoped>
/* Custom styles for better visual hierarchy */
.work-experience-card {
  transition: all 0.2s ease-in-out;
}

.work-experience-card:hover {
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

/* Currency input styling */
.currency-input {
  padding-left: 2rem;
}
</style>
