<template>
  <div class="bg-white rounded-lg shadow-sm border">
    <div class="bg-white text-gray-800 px-6 py-3 rounded-t-lg border-b">
      <h2 class="text-sm font-semibold flex items-center">
        <i class="fas fa-user mr-1"></i>
        PERSONAL INFORMATION
      </h2>
    </div>
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-3">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                SURNAME
              </label>
              <input
                v-if="editable"
                :value="info.surname"
                @input="
                  $emit('update:info', {
                    ...info,
                    surname: $event.target.value,
                  })
                "
                type="text"
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
                placeholder="Enter surname"
              />
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ info.surname || "" }}
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                FIRST NAME
              </label>
              <input
                v-if="editable"
                :value="info.firstname"
                @input="
                  $emit('update:info', {
                    ...info,
                    firstname: $event.target.value,
                  })
                "
                type="text"
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
                placeholder="Enter first name"
              />
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ info.firstname || "" }}
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                MIDDLE NAME
              </label>
              <input
                v-if="editable"
                :value="info.middlename"
                @input="
                  $emit('update:info', {
                    ...info,
                    middlename: $event.target.value,
                  })
                "
                type="text"
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
                placeholder="Enter middle name"
              />
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ info.middlename || "" }}
              </div>
            </div>
          </div>
        </div>

        <div class="md:col-span-3">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                Suffix
              </label>
              <select
                v-if="editable"
                :value="info.suffix"
                @change="
                  $emit('update:info', { ...info, suffix: $event.target.value })
                "
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
              >
                <option value="">Select Suffix</option>
                <option
                  v-for="suffix in suffixOptions"
                  :key="suffix"
                  :value="suffix"
                >
                  {{ suffix }}
                </option>
              </select>
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ getSuffixName(info.suffix) || "" }}
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                Prefix
              </label>
              <select
                v-if="editable"
                :value="info.prefix"
                @change="
                  $emit('update:info', { ...info, prefix: $event.target.value })
                "
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
              >
                <option value="">Select Prefix</option>
                <option
                  v-for="prefix in prefixOptions"
                  :key="prefix"
                  :value="prefix"
                >
                  {{ prefix }}
                </option>
              </select>
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ getPrefixName(info.prefix) || "" }}
              </div>
            </div>

            <div class="md:col-span-2">
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                DATE OF BIRTH (mm/dd/yyyy)
              </label>
              <input
                v-if="editable"
                :value="info.birth_date"
                @input="
                  $emit('update:info', {
                    ...info,
                    birth_date: $event.target.value,
                  })
                "
                type="date"
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
              />
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ formatDate(info.birth_date) || "" }}
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                PLACE OF BIRTH
              </label>
              <input
                v-if="editable"
                :value="info.birth_place"
                @input="
                  $emit('update:info', {
                    ...info,
                    birth_place: $event.target.value,
                  })
                "
                type="text"
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
                placeholder="Enter birth place"
              />
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ info.birth_place || "" }}
              </div>
            </div>
          </div>
        </div>

        <div class="md:col-span-3">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                SEX
              </label>
              <select
                v-if="editable"
                :value="info.sex"
                @change="
                  $emit('update:info', { ...info, sex: $event.target.value })
                "
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
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
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ getGenderName(info.sex) || "" }}
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                CIVIL STATUS
              </label>
              <select
                v-if="editable"
                :value="info.civil_status"
                @change="
                  $emit('update:info', {
                    ...info,
                    civil_status: $event.target.value,
                  })
                "
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
              >
                <option value="">Select Status</option>
                <option
                  v-for="status in civilStatusOptions"
                  :key="status"
                  :value="status"
                >
                  {{ status }}
                </option>
              </select>
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ getCivilStatusName(info.civil_status) || "" }}
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                CITIZENSHIP
              </label>
              <select
                v-if="editable"
                :value="info.citizenship"
                @change="
                  $emit('update:info', {
                    ...info,
                    citizenship: $event.target.value,
                  })
                "
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
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
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ getCitizenshipName(info.citizenship) || "" }}
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                INDICATE COUNTRY
              </label>
              <div class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ info.indicate_country || "" }}
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                HEIGHT (m)
              </label>
              <input
                v-if="editable"
                :value="info.height"
                @input="
                  $emit('update:info', { ...info, height: $event.target.value })
                "
                type="number"
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
                placeholder="Height"
              />
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ info.height || "" }}
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                WEIGHT (kg)
              </label>
              <input
                v-if="editable"
                :value="info.weight"
                @input="
                  $emit('update:info', { ...info, weight: $event.target.value })
                "
                type="number"
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
                placeholder="Weight"
              />
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ info.weight || "" }}
              </div>
            </div>
          </div>
        </div>

        <div class="md:col-span-3">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                BLOOD TYPE
              </label>
              <select
                v-if="editable"
                :value="info.blood_type"
                @change="
                  $emit('update:info', {
                    ...info,
                    blood_type: $event.target.value,
                  })
                "
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
              >
                <option value="">Select Blood Type</option>
                <option
                  v-for="bloodType in bloodTypes"
                  :key="bloodType"
                  :value="bloodType"
                >
                  {{ bloodType }}
                </option>
              </select>
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ getBloodTypeName(info.blood_type) || "" }}
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-medium text-gray-700 uppercase mb-1"
              >
                RELIGION
              </label>
              <select
                v-if="editable"
                :value="info.religion"
                @change="
                  $emit('update:info', {
                    ...info,
                    religion: $event.target.value,
                  })
                "
                class="w-full px-2 py-1 border-b border-gray-300 focus:outline-none focus:border-blue-500 bg-transparent"
              >
                <option value="">Select Religion</option>
                <option
                  v-for="religion in religionOptions"
                  :key="religion"
                  :value="religion"
                >
                  {{ religion }}
                </option>
              </select>
              <div v-else class="border-b border-gray-300 pb-1 min-h-[24px]">
                {{ getReligionName(info.religion) || "" }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { ApiService } from "@/services/api.js";

const props = defineProps({
  info: { type: Object, required: true },
  editable: { type: Boolean, default: false },
});

const emit = defineEmits(["update:info"]);

const bloodTypes = ref([]);
const civilStatusOptions = ref([]);
const prefixOptions = ref([]);
const suffixOptions = ref([]);
const religionOptions = ref([]);
const genderOptions = ref([]);
const citizenshipOptions = ref([]);

onMounted(async () => {
  try {
    const [
      civilStatusRes,
      bloodTypesRes,
      prefixesRes,
      suffixesRes,
      religionsRes,
      gendersRes,
      citizenshipsRes,
    ] = await Promise.all([
      ApiService.getCivilStatus(),
      ApiService.getBloodTypes(),
      ApiService.getNamePrefixes(),
      ApiService.getNameSuffixes(),
      ApiService.getReligions(),
      ApiService.getGenders(),
      ApiService.getCitizenships(),
    ]);

    civilStatusOptions.value = extractNestedData(
      civilStatusRes,
      "civil_status"
    );
    bloodTypes.value = extractNestedData(bloodTypesRes, "blood_types");
    prefixOptions.value = extractNestedData(prefixesRes, "prefixes");
    suffixOptions.value = extractNestedData(suffixesRes, "suffixes");
    religionOptions.value = extractNestedData(religionsRes, "religions");
    genderOptions.value = extractNestedData(gendersRes, "genders");
    citizenshipOptions.value = extractNestedData(citizenshipsRes, "citizenships");
  } catch (err) {
    console.error("Error loading dropdown data:", err);
  }
});

const extractNestedData = (response, key) => {
  if (!response || !response.data) return [];

  let data = response.data;

  if (typeof data === "string") {
    try {
      data = JSON.parse(data);
    } catch (e) {
      return [];
    }
  }

  if (data.data && data.data[key] && Array.isArray(data.data[key])) {
    return data.data[key];
  }

  if (data[key] && Array.isArray(data[key])) {
    return data[key];
  }

  if (Array.isArray(data)) {
    return data;
  }

  if (data.data && Array.isArray(data.data)) {
    return data.data;
  }

  return [];
};

const getGenderName = (genderId) => {
  if (!genderId) return "";
  // Try to find in genderOptions first (only if it's loaded)
  if (genderOptions.value && genderOptions.value.length > 0) {
    const gender = genderOptions.value.find(
      (g) => g.id === genderId || g.id === Number(genderId) || String(g.id) === String(genderId)
    );
    if (gender) return gender.name;
  }
  // Fallback to old logic
  if (genderId === 1 || genderId === "1") return "Male";
  if (genderId === 2 || genderId === "2") return "Female";
  if (
    typeof genderId === "string" &&
    (genderId.toLowerCase() === "male" || genderId.toLowerCase() === "female")
  ) {
    return genderId;
  }
  return genderId;
};

const getCitizenshipName = (citizenshipId) => {
  if (!citizenshipId) return "";
  if (typeof citizenshipId === "string" && isNaN(citizenshipId)) {
    return citizenshipId;
  }
  // Try to find in citizenshipOptions first (only if it's loaded)
  if (citizenshipOptions.value && citizenshipOptions.value.length > 0) {
    const citizenship = citizenshipOptions.value.find(
      (c) =>
        c.id == citizenshipId ||
        c.citizenship_id == citizenshipId ||
        c.id === Number(citizenshipId)
    );
    if (citizenship) return citizenship.name;
  }
  return citizenshipId;
};

const getCivilStatusName = (statusId) => {
  if (!statusId) return "";
  if (typeof statusId === "string" && isNaN(statusId)) {
    return statusId;
  }
  const status = civilStatusOptions.value.find(
    (s) =>
      s.id == statusId ||
      s.civil_status_id == statusId ||
      s.id === Number(statusId)
  );
  return status
    ? status.name || status.civil_status || status.status
    : statusId;
};

const getReligionName = (religionId) => {
  if (!religionId) return "";

  if (typeof religionId === "string" && isNaN(religionId)) {
    return religionId;
  }

  const religion = religionOptions.value.find(
    (r) =>
      r.id == religionId ||
      r.religion_id == religionId ||
      r.id === Number(religionId)
  );

  return religion ? religion.name || religion.religion : religionId;
};

const getBloodTypeName = (bloodTypeId) => {
  if (!bloodTypeId) return "";
  if (typeof bloodTypeId === "string" && isNaN(bloodTypeId)) {
    return bloodTypeId;
  }
  const bloodType = bloodTypes.value.find(
    (bt) =>
      bt.id == bloodTypeId ||
      bt.blood_type_id == bloodTypeId ||
      bt.id === Number(bloodTypeId)
  );
  return bloodType ? bloodType.name || bloodType.blood_type : bloodTypeId;
};

const getPrefixName = (prefixId) => {
  if (!prefixId) return "";
  if (typeof prefixId === "string" && isNaN(prefixId)) {
    return prefixId;
  }
  const prefix = prefixOptions.value.find(
    (p) =>
      p.id == prefixId ||
      p.name_prefix_id == prefixId ||
      p.id === Number(prefixId)
  );
  return prefix ? prefix.name || prefix.prefix : prefixId;
};

const getSuffixName = (suffixId) => {
  if (!suffixId) return "";
  if (typeof suffixId === "string" && isNaN(suffixId)) {
    return suffixId;
  }
  const suffix = suffixOptions.value.find(
    (s) =>
      s.id == suffixId ||
      s.name_suffix_id == suffixId ||
      s.id === Number(suffixId)
  );
  return suffix ? suffix.name || suffix.suffix : suffixId;
};

const formatDate = (date) => {
  if (!date) return "";
  return new Date(date).toLocaleDateString("en-US");
};
</script>
