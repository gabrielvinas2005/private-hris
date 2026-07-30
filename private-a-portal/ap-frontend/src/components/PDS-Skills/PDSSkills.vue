<template>
  <div class="pds-skills">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
      Special Skills and Hobbies
    </h3>

    <div class="space-y-6">
      <!-- Skills Section -->
      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div
          class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200 rounded-t-lg"
        >
          <div class="flex justify-between items-center">
            <div>
              <h4
                class="text-lg font-semibold text-gray-900 mb-1 inline-flex items-center"
              >
                <Settings :size="20" class="mr-2 text-indigo-600" /> Special
                Skills and Hobbies
              </h4>
              <p class="text-sm text-gray-600">
                Include any special skills, talents, hobbies, or interests that
                may be relevant to your work or personal development.
              </p>
            </div>
            <button
              @click="addSkillEntry"
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
              Add Skill
            </button>
          </div>
        </div>

        <div class="p-6">
          <div
            v-if="loading && skillsData.length === 0"
            class="text-center py-8 text-gray-500"
          >
            <div
              class="inline-flex items-center justify-center w-12 h-12 mb-4 bg-blue-100 rounded-full"
            >
              <div
                class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"
              ></div>
            </div>
            <p>Loading skills...</p>
          </div>

          <div
            v-else-if="skillsData.length === 0"
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
                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014.846 21H9.154a3.374 3.374 0 00-2.039-.764l-.547-.547z"
              ></path>
            </svg>
            <p>No skills added yet. Click "Add Skill" to get started.</p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="(skill, index) in skillsData"
              :key="skill.skill_id || skill.id"
              class="bg-gray-50 rounded-lg p-4 border border-gray-200"
            >
              <div class="flex justify-between items-start mb-3">
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center"
                  >
                    <span class="text-purple-600 font-semibold text-sm">{{
                      index + 1
                    }}</span>
                  </div>
                  <h5 class="text-sm font-medium text-gray-700">
                    Skill {{ index + 1 }}
                  </h5>
                </div>
                <button
                  @click="removeSkill(index)"
                  class="text-red-600 hover:text-red-800 transition-colors duration-200"
                  title="Remove skill"
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
                      d="M6 18L18 6M6 6l12 12"
                    ></path>
                  </svg>
                </button>
              </div>

              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1"
                  >Skill/Description *</label
                >
                <input
                  v-model="skill.skill"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="e.g., Computer Programming (Java, Python, JavaScript)"
                />
              </div>
            </div>
          </div>

          <!-- Helpful tips -->
          <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
            <h5 class="text-sm font-medium text-blue-800 mb-1">
              Tips for listing your skills:
            </h5>
            <ul class="text-sm text-blue-700 space-y-1">
              <li>
                • Be specific about your proficiency level (Basic, Intermediate,
                Advanced)
              </li>
              <li>• Include both technical and soft skills</li>
              <li>• Mention any certifications or formal training</li>
              <li>• Add hobbies that demonstrate valuable qualities</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Quick Add Suggestions - Only show when there are skill entries -->
      <div
        v-if="skillsData.length > 0"
        class="bg-white rounded-lg shadow-sm border p-6"
      >
        <h4 class="text-md font-medium text-gray-800 mb-4">
          Quick Add Common Skills
        </h4>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
          <button
            v-for="skill in commonSkills"
            :key="skill"
            @click="addSkill(skill)"
            class="text-left px-3 py-2 text-sm bg-gray-100 hover:bg-blue-100 hover:text-blue-700 rounded-md transition-colors duration-200"
          >
            {{ skill }}
          </button>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          @click="saveSkills"
          :disabled="loading || skillsData.length === 0"
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
          {{ loading ? "Saving..." : "Save Skills" }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { ElNotification, ElMessageBox } from "element-plus";
import { ApiService } from "@/services/api.js";
import { useAppNotification } from "@/composables/useAppNotification.js";
import { Settings } from "lucide-vue-next";

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});

const notify = useAppNotification();

const skillsData = ref([]);
const loading = ref(false);
const error = ref(null);
const employeeId = ref(null);
let nextSkillId = 1;

const commonSkills = [
  "Microsoft Office",
  "Computer Programming",
  "Data Analysis",
  "Graphic Design",
  "Public Speaking",
  "Project Management",
  "Foreign Languages",
  "Photography",
  "Video Editing",
  "Social Media Management",
  "Web Development",
  "Digital Marketing",
  "Teaching/Training",
  "Customer Service",
  "Leadership",
  "Team Collaboration",
  "Problem Solving",
  "Research Skills",
  "Financial Analysis",
  "Event Planning",
  "Creative Writing",
  "Musical Instruments",
  "Sports/Athletics",
  "Cooking/Culinary",
];

const loadSkillsData = async () => {
  loading.value = true;
  error.value = null;

  try {
    // Fetch data from API first
    const response = await ApiService.getApplicantPage();

    if (response.data && response.data.data) {
      const data = response.data.data;

      // Capture employee id from API response (this is the correct source)
      employeeId.value =
        data.employee_info?.[0]?.id ??
        props.info?.employee_info?.[0]?.id ??
        props.info?.employee_id ??
        props.info?.id ??
        null;

      // Load skills data
      skillsData.value = [];
      if (data.skills && Array.isArray(data.skills)) {
        data.skills.forEach((skill) => {
          skillsData.value.push({
            id: nextSkillId++,
            skill_id: skill.skill_id || null,
            skill: skill.skill || "",
          });
        });
      }
    }
  } catch (err) {
    console.error("Error loading skills data:", err);
    error.value = err.response?.data?.message || "Failed to load skills data";
  } finally {
    loading.value = false;
  }
};

const addSkillEntry = () => {
  skillsData.value.push({
    id: nextSkillId++,
    skill_id: null,
    skill: "",
  });
};

const removeSkill = async (index) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to remove this skill?",
      "Remove Skill",
      {
        confirmButtonText: "Remove",
        cancelButtonText: "Cancel",
        type: "warning",
      }
    );
  } catch {
    return;
  }

  const skill = skillsData.value[index];
  const skillId = skill.skill_id;

  // Remove from UI immediately for better UX
  skillsData.value.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Skill removed successfully!",
    type: "success",
    duration: 2000,
    position: "top-right",
  });

  // If it's an existing record, delete from backend (type_id 9 for skills)
  if (skillId) {
    try {
      await ApiService.destroyPDS(9, skillId);
    } catch (err) {
      const status = err.response?.status;

      if (status && status >= 400 && status < 500) {
        skillsData.value.splice(index, 0, skill);
        ElNotification({
          title: "Error",
          message: "Failed to delete skill from server. Please try again.",
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

const addSkill = (skill) => {
  // Check if skill is already in the list
  const exists = skillsData.value.some(
    (s) => s.skill.toLowerCase() === skill.toLowerCase()
  );

  if (!exists) {
    skillsData.value.push({
      id: nextSkillId++,
      skill_id: null,
      skill: skill,
    });
  }
};

const saveSkills = async () => {
  // Validate that all skills have text
  const emptySkills = skillsData.value.filter(
    (skill) => !skill.skill || !skill.skill.trim()
  );

  if (emptySkills.length > 0) {
    notify.error("Validation", "Please fill in all skill descriptions before saving.", {
      duration: 4000,
    });
    return;
  }

  loading.value = true;
  error.value = null;

  try {
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

    // Prepare skills data for API
    const formData = new FormData();
    formData.append("section", "skills");

    // Send skills as JSON string (array format)
    const skillsPayload = skillsData.value.map((skill) => ({
      skill_id: skill.skill_id || null,
      skill: skill.skill || "",
    }));

    formData.append("skills", JSON.stringify(skillsPayload));

    console.log("Saving skills:", {
      employeeId: resolvedEmployeeId,
      skillsCount: skillsPayload.length,
      skills: skillsPayload,
    });

    await ApiService.storePDS(resolvedEmployeeId, formData);

    notify.success("Saved", "Skills information saved successfully!", {
      duration: 4000,
    });
    await loadSkillsData();
  } catch (err) {
    console.error("Error saving skills data:", err);
    const msg =
      err.response?.data?.message ||
      (err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(" ")
        : null) ||
      err.message ||
      "Failed to save skills information. Please try again.";
    notify.error("Save failed", msg, { duration: 6000 });
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  await loadSkillsData();
});
</script>

<style scoped>
/* Custom textarea styling */
textarea {
  min-height: 200px;
}

textarea::placeholder {
  color: #9ca3af;
  font-size: 0.875rem;
  line-height: 1.5;
}

/* Skill button hover effects */
.skill-button {
  transition: all 0.2s ease-in-out;
}

.skill-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Character counter styling */
.character-counter {
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(4px);
  border-radius: 4px;
  padding: 2px 6px;
}
</style>
