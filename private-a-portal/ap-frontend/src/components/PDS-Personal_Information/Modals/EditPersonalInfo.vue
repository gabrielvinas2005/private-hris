<template>
  <div class="p-6 bg-white border rounded-lg shadow">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="md:col-span-2 lg:col-span-1">
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >First Name <span class="text-red-500">*</span></label
        >
        <input
          v-model="localInfo.first_name"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter First Name"
          required
        />
      </div>
      <div class="md:col-span-2 lg:col-span-1">
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Middle Name
        </label>
        <input
          v-model="localInfo.middlename"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter Middle Name"
        />
      </div>
      <div class="md:col-span-2 lg:col-span-1">
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Last Name <span class="text-red-500">*</span></label
        >
        <input
          v-model="localInfo.surname"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter Last Name"
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Prefix</label
        >
        <select
          v-model="localInfo.prefix"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select Prefix</option>
          <option
            v-for="prefix in prefixOptions"
            :key="prefix.id"
            :value="prefix.id"
          >
            {{ prefix.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Suffix</label
        >
        <select
          v-model="localInfo.suffix"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select Suffix</option>
          <option
            v-for="suffix in suffixOptions"
            :key="suffix.id"
            :value="suffix.id"
          >
            {{ suffix.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Birth Date <span class="text-red-500">*</span></label
        >
        <input
          v-model="localInfo.birth_date"
          type="date"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          required
        />
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Birth Place <span class="text-red-500">*</span></label
        >
        <input
          v-model="localInfo.birth_place"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter birth place"
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Gender <span class="text-red-500">*</span></label
        >
        <select
          v-model="localInfo.gender"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select Gender</option>
          <option
            v-for="gender in genderOptions"
            :key="gender.id"
            :value="gender.id"
          >
            {{ gender.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Civil Status <span class="text-red-500">*</span></label
        >
        <select
          v-model="localInfo.civil_status"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select Civil Status</option>
          <option
            v-for="status in civilStatusOptions"
            :key="status.id"
            :value="status.id"
          >
            {{ status.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Citizenship <span class="text-red-500">*</span></label
        >
        <select
          v-model="localInfo.citizenship"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select Citizenship</option>
          <option
            v-for="citizenship in citizenshipOptions"
            :key="citizenship.id"
            :value="citizenship.id"
          >
            {{ citizenship.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Blood Type</label
        >
        <select
          v-model="localInfo.blood_type"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select Blood Type</option>
          <option
            v-for="bloodType in bloodTypesOptions"
            :key="bloodType.id"
            :value="bloodType.id"
          >
            {{ bloodType.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Religion</label
        >
        <select
          v-model="localInfo.religion"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select Religion</option>
          <option
            v-for="religion in religionOptions"
            :key="religion.id"
            :value="religion.id"
          >
            {{ religion.name }}
          </option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Height (cm)</label
        >
        <input
          v-model="localInfo.height"
          type="number"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter height"
          required
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Weight (kg)</label
        >
        <input
          v-model="localInfo.weight"
          type="number"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter weight"
          required
        />
      </div>

      <div class="md:col-span-3">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="flex items-center">
            <input
              v-model="localInfo.is_dual_citizen"
              type="checkbox"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label class="ml-2 text-sm font-medium text-gray-700"
              >Is Dual Citizen</label
            >
          </div>

          <div class="flex items-center">
            <input
              v-model="localInfo.by_birth"
              type="checkbox"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label class="ml-2 text-sm font-medium text-gray-700"
              >By Birth</label
            >
          </div>

          <div class="flex items-center">
            <input
              v-model="localInfo.by_naturalization"
              type="checkbox"
              class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <label class="ml-2 text-sm font-medium text-gray-700"
              >By Naturalization</label
            >
          </div>
        </div>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2"
          >Indicate Country</label
        >
        <input
          v-model="localInfo.indicate_country"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Enter country"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from "vue";
import { ApiService } from "@/services/api.js";

const props = defineProps({
  info: { type: Object, required: true },
});

const emit = defineEmits(["update"]);

const localInfo = ref({ ...props.info });

const prefixOptions = ref([]);
const suffixOptions = ref([]);
const civilStatusOptions = ref([]);
const bloodTypesOptions = ref([]);
const religionOptions = ref([]);
const genderOptions = ref([]);
const citizenshipOptions = ref([]);

const parseDropdownData = (data) => {
  try {
    if (Array.isArray(data)) {
      const result = data.map((item) => ({
        id: item.id || item.code,
        name: item.name || item.title || item.label,
        value: item.code || item.id || item.name,
      }));

      return result;
    }

    if (data && data.data && Array.isArray(data.data)) {
      const result = data.data.map((item) => ({
        id: item.id || item.code,
        name: item.name || item.title || item.label,
        value: item.code || item.id || item.name,
      }));
      return result;
    }

    return [];
  } catch (error) {
    return [];
  }
};

const fetchDropdownData = async () => {
  try {
    const [
      prefixResponse,
      suffixResponse,
      civilStatusResponse,
      bloodTypesResponse,
      religionsResponse,
      gendersResponse,
      citizenshipsResponse,
    ] = await Promise.all([
      ApiService.getNamePrefixes(),
      ApiService.getNameSuffixes(),
      ApiService.getCivilStatus(),
      ApiService.getBloodTypes(),
      ApiService.getReligions(),
      ApiService.getGenders(),
      ApiService.getCitizenships(),
    ]);

    prefixOptions.value = prefixResponse.data.data.prefixes.map((item) => ({
      id: item.id,
      name: item.name,
      value: item.name,
    }));

    suffixOptions.value = suffixResponse.data.data.suffixes.map((item) => ({
      id: item.id,
      name: item.name,
      value: item.name,
    }));

    civilStatusOptions.value = civilStatusResponse.data.data.civil_status.map(
      (item) => ({
        id: item.id,
        name: item.name,
        value: item.name,
      }),
    );

    bloodTypesOptions.value = bloodTypesResponse.data.data.blood_types.map(
      (item) => ({
        id: item.id,
        name: item.name,
        value: item.name,
      }),
    );

    religionOptions.value = religionsResponse.data.data.religions.map(
      (item) => ({
        id: item.id,
        name: item.name,
        value: item.name,
      }),
    );

    genderOptions.value = gendersResponse.data.data.genders.map((item) => ({
      id: item.id,
      name: item.name,
      value: item.id,
    }));

    citizenshipOptions.value = citizenshipsResponse.data.data.citizenships.map(
      (item) => ({
        id: item.id,
        name: item.name,
        value: item.id,
      }),
    );
  } catch (error) {}
};

watch(
  localInfo,
  (newValue) => {
    emit("update", newValue);
  },
  { deep: true },
);

watch(
  () => props.info,
  (newValue) => {
    localInfo.value = { ...newValue };
  },
  { deep: true },
);

onMounted(async () => {
  await fetchDropdownData();
});
</script>

<style scoped></style>
