<template>
  <div class="min-h-screen px-2 py-6 bg-gray-50">
    <div v-if="loading" class="w-[92%] mx-auto text-center py-20">
      <div
        class="w-12 h-12 mx-auto mb-4 border-b-2 border-blue-600 rounded-full animate-spin"
      ></div>
      <p class="text-gray-600">Loading personal information...</p>
    </div>

    <div v-else-if="error" class="w-[92%] mx-auto text-center py-20">
      <div class="mb-4 text-red-500">
        <i class="text-4xl fas fa-exclamation-triangle"></i>
      </div>
      <p class="mb-4 text-red-600">{{ error }}</p>
      <button
        @click="fetchPersonalInfo"
        class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600"
      >
        Try Again
      </button>
    </div>

    <div v-else class="w-[92%] mx-auto">
      <div
        class="mb-4 bg-white border rounded-lg shadow p-4 flex items-center justify-between"
      >
        <div>
          <h1 class="text-xl font-semibold text-gray-900">
            Personal Data Sheet (PDS)
          </h1>
        </div>
        <div class="flex items-center gap-2">
          <button
            class="px-3 py-1.5 text-xs text-gray-700 bg-white border rounded hover:bg-gray-50"
            @click="fetchPersonalInfo"
          >
            Refresh
          </button>
          <button
            class="px-3 py-1.5 text-xs text-white bg-blue-600 rounded hover:bg-blue-700"
            @click="showModal = true"
          >
            Edit
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <aside class="col-span-1 space-y-4">
          <div class="p-4 bg-white border rounded-lg shadow">
            <div class="flex flex-col items-center mb-4">
              <div
                class="flex items-center justify-center w-32 h-40 mb-3 border-2 border-gray-300 border-dashed rounded bg-gray-50 overflow-hidden"
              >
                <img
                  v-if="displayPhoto"
                  :src="displayPhoto"
                  alt="Applicant Photo"
                  class="w-full h-full object-cover"
                  @error="handlePhotoError"
                />
                <div v-else class="text-center">
                  <i class="mb-2 text-2xl text-gray-400 fas fa-camera"></i>
                  <p class="text-xs text-gray-500">Photo</p>
                </div>
              </div>
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Applicant ID</label
              >
              <div
                class="w-full px-3 py-2 text-gray-900 border border-gray-300 rounded-md bg-gray-50"
              >
                {{ personalInfo.employee_no || "" }}
              </div>
            </div>
          </div>

          <PDSContactInfo :info="personalInfo" />
          <PDSGovernmentIDs :info="personalInfo" />
        </aside>

        <section class="col-span-1 xl:col-span-3">
          <div class="space-y-6">
            <PDSBasicInfo :info="basicInfo" />
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
              <PDSResidentialAddress :info="personalInfo" />
              <PDSPermanentAddress :info="personalInfo" />
            </div>
          </div>
        </section>
      </div>
    </div>

    <EditPDSModal
      v-if="showModal"
      @close="handleModalClose"
      @save="handleSave"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { ApiService } from "@/services/api.js";
import { useAppNotification } from "@/composables/useAppNotification.js";
import PDSBasicInfo from "./PDSBasicInfo.vue";
import PDSContactInfo from "./PDSContactInfo.vue";
import PDSGovernmentIDs from "./PDSGovernmentIDs.vue";
import PDSResidentialAddress from "./PDSResidentialAddress.vue";
import PDSPermanentAddress from "./PDSPermanentAddress.vue";
import EditPDSModal from "./Modals/EditPDSModal.vue";

// Address reference data (for mapping codes to descriptions)
import regionsData from "@/assets/refregion.json";
import provincesData from "@/assets/refprovince.json";
import citiesData from "@/assets/refcitymun.json";
import barangaysData from "@/assets/refbrgy.json";

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});

const notify = useAppNotification();

const personalInfo = ref({});
const loading = ref(true);
const error = ref(null);
const showModal = ref(false);
const buttonClicked = ref(false);

const getApplicantNo = () => {
  if (props.info && props.info.applicant_no) {
    return props.info.applicant_no;
  }

  const userData = localStorage.getItem("user_data");
  if (userData) {
    const parsedData = JSON.parse(userData);
    return parsedData.applicant_no || parsedData.employee_no;
  }

  return props.info?.id || null;
};
const fetchPersonalInfo = async () => {
  try {
    loading.value = true;
    error.value = null;

    try {
      const employeeNo = getApplicantNo();
      if (employeeNo) {
        const response = await ApiService.getPDSDataApi(employeeNo);
        const payload = response.data?.data;
        const raw = Array.isArray(payload) ? payload[0] : payload;
        if (raw) {
          personalInfo.value = mapPdsRecord(raw);
          loading.value = false;
          return;
        }
      }
    } catch (pdsError) {
    }

    if (props.info && props.info.id) {
      personalInfo.value = mapPdsRecord(props.info);
      loading.value = false;
      return;
    }

    try {
      const response = await ApiService.getApplicantPage();

      if (response.data && response.data.data) {
        const data = response.data.data;
        const applicant = data.applicant?.[0] || {};

        if (applicant.id) {
          personalInfo.value = mapPdsRecord(applicant);
          loading.value = false;
          return;
        }
      }
    } catch (apiError) {
    }

    error.value =
      "No personal information found. Please complete your registration first.";
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      err.message ||
      "Failed to load personal information.";
  } finally {
    loading.value = false;
  }
};

const getFirstName = (fullname) => {
  if (!fullname) return "";
  const parts = fullname.split(" ");
  return parts[0] || "";
};

const getMiddleName = (fullname) => {
  if (!fullname) return "";
  const parts = fullname.split(" ");
  return parts[1] || "";
};

const getLastName = (fullname) => {
  if (!fullname) return "";
  const parts = fullname.split(" ");
  return parts.length ? parts[parts.length - 1] : "";
};

// Helper functions to map location codes to descriptions.
// These are tolerant: if the value is already a description (not a known code),
// they simply return the original value.
const normalizeRegion = (value) => {
  if (!value) return "";
  const str = String(value).trim();
  const region =
    regionsData.RECORDS.find((r) => r.regCode === str) ||
    regionsData.RECORDS.find((r) => r.regDesc === str);
  return region ? region.regDesc : str;
};

const normalizeProvince = (value) => {
  if (!value) return "";
  const str = String(value).trim();
  const province =
    provincesData.RECORDS.find((p) => p.provCode === str) ||
    provincesData.RECORDS.find((p) => p.provDesc === str);
  return province ? province.provDesc : str;
};

const normalizeCity = (value) => {
  if (!value) return "";
  const str = String(value).trim();
  const city =
    citiesData.RECORDS.find((c) => c.citymunCode === str) ||
    citiesData.RECORDS.find((c) => c.citymunDesc === str);
  return city ? city.citymunDesc : str;
};

const normalizeBarangay = (value) => {
  if (!value) return "";
  const str = String(value).trim();
  const barangay =
    barangaysData.RECORDS.find((b) => b.brgyCode === str) ||
    barangaysData.RECORDS.find((b) => b.brgyDesc === str);
  return barangay ? barangay.brgyDesc : str;
};

const mapPdsRecord = (record) => {
  if (!record || typeof record !== "object") return {};

  const dualCitizenRaw =
    record.is_dual_citizen ?? record.is_dual_citizent ?? record.is_dual ?? 0;
  const byBirthRaw = record.by_birth ?? record.dual_by_birth ?? 0;
  const byNaturalizationRaw =
    record.by_naturalization ?? record.dual_by_naturalization ?? 0;

  return {
    id: record.id ?? record.applicant_id ?? "",
    employee_no: record.employee_no ?? record.applicant_no ?? "",
    fullname:
      record.fullname ??
      record.name ??
      `${record.first_name || ""} ${record.middle_name || ""} ${record.last_name || ""}`.trim(),
    first_name: record.first_name ?? getFirstName(record.fullname ?? ""),
    middlename: record.middle_name ?? getMiddleName(record.fullname ?? ""),
    surname: record.last_name ?? getLastName(record.fullname ?? ""),
    prefix: record.prefix_id ?? record.prefix ?? record.name_prefix_id ?? "",
    suffix: record.suffix_id ?? record.suffix ?? record.name_suffix_id ?? "",
    age: record.age ?? "",
    religion: record.religion ?? "",
    birth_place:
      record.birth_place ??
      record.address ??
      record.ra_street ??
      record.pa_street ??
      "",
    birth_date: record.birthdate ?? record.birth_date ?? "",
    email: record.email ?? "",
    mobile_no: record.mobile_no ?? record.mobile_no ?? "",
    tel_no: record.tel_no ?? record.telephone_no ?? "",
    address: record.address ?? record.ra_street ?? record.pa_street ?? "",
    // Store / expose address fields as descriptions (not codes)
    ra_region: normalizeRegion(record.ra_region ?? ""),
    ra_province: normalizeProvince(record.ra_province ?? ""),
    ra_city: normalizeCity(record.ra_city ?? ""),
    ra_house_no: record.ra_house_no ?? "",
    ra_barangay: normalizeBarangay(record.ra_barangay ?? ""),
    ra_street: record.ra_street ?? "",
    ra_village: record.ra_village ?? "",
    pa_region: normalizeRegion(record.pa_region ?? ""),
    pa_province: normalizeProvince(record.pa_province ?? ""),
    pa_city: normalizeCity(record.pa_city ?? ""),
    pa_house_no: record.pa_house_no ?? "",
    pa_barangay: normalizeBarangay(record.pa_barangay ?? ""),
    pa_street: record.pa_street ?? "",
    pa_village: record.pa_village ?? "",
    gender: record.gender_id ?? record.Gender ?? record.gender ?? "",
    civil_status:
      record.civil_status_id ?? record.civilStatus ?? record.civil_status ?? "",
    citizenship: record.citizenship_id ?? record.citizenship ?? record.citizen ?? "",
    citizen: record.citizen ?? "",
    religion: record.religion_id ?? record.religion ?? "",
    blood_type:
      record.blood_type_id ?? record.BloodType ?? record.blood_type ?? "",
    is_dual_citizen: Boolean(Number(dualCitizenRaw)),
    by_birth: Boolean(Number(byBirthRaw)),
    by_naturalization: Boolean(Number(byNaturalizationRaw)),
    indicate_country:
      record.indicate_country ?? record.dual_country ?? record.country ?? "",
    height: record.height ?? "",
    weight: record.weight ?? "",
    tin_no: record.tin_no ?? "",
    gsis_no: record.gsis_no ?? "",
    sss_no: record.sss_no ?? "",
    pagibig_no: record.pagibig_no ?? "",
    philhealth_no: record.philhealth_no ?? "",
    photo: record.photo ?? "",
  };
};

const formatBirthDate = (date) => {
  if (!date) return "";
  return new Date(date).toLocaleDateString() || date;
};

onMounted(() => {
  fetchPersonalInfo();
});

const basicInfo = computed(() => ({
  surname:
    personalInfo.value.surname || getLastName(personalInfo.value.fullname),
  firstname:
    personalInfo.value.first_name || getFirstName(personalInfo.value.fullname),
  middlename:
    personalInfo.value.middlename || getMiddleName(personalInfo.value.fullname),
  suffix: personalInfo.value.suffix,
  prefix: personalInfo.value.prefix,
  birth_date: personalInfo.value.birth_date,
  birth_place: personalInfo.value.birth_place,
  sex: personalInfo.value.gender,
  civil_status: personalInfo.value.civil_status,
  citizenship: personalInfo.value.citizenship,
  is_dual_citizen: personalInfo.value.is_dual_citizen,
  indicate_country: personalInfo.value.indicate_country,
  height: personalInfo.value.height,
  weight: personalInfo.value.weight,
  blood_type: personalInfo.value.blood_type,
  gsis_no: personalInfo.value.gsis_no,
  pagibig_no: personalInfo.value.pagibig_no,
  philhealth_no: personalInfo.value.philhealth_no,
  sss_no: personalInfo.value.sss_no,
  tin_no: personalInfo.value.tin_no,
  employee_no: personalInfo.value.employee_no,
  religion: personalInfo.value.religion,
}));

const handleSave = async (updatedData = null) => {
  try {
    await fetchPersonalInfo();
    showModal.value = false;
  } catch (error) {
    notify.error(
      "Refresh failed",
      "Data saved but failed to refresh. Please reload the page.",
      { duration: 5000 },
    );
  }
};

const handleModalClose = async () => {
  showModal.value = false;
  await fetchPersonalInfo();
};

const displayPhoto = computed(() => {
  const photo = personalInfo.value?.photo;

  if (!photo || (typeof photo === "string" && photo.trim() === "")) {
    return null;
  }

  if (typeof photo === "string" && photo.startsWith("data:image")) {
    return photo;
  }

  if (typeof photo === "string" && photo.trim() !== "") {
    const cleanPhoto = photo.trim().replace(/\s/g, "");
    const photoUrl = `data:image/jpeg;base64,${cleanPhoto}`;
    return photoUrl;
  }

  return null;
});

const handlePhotoError = (event) => {
  event.target.style.display = "none";
};


</script>

<style scoped>
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

.border-gray-300 {
  border-color: #d1d5db;
}

.bg-gray-50 {
  background-color: #f9fafb;
}

.text-gray-900 {
  color: #111827;
}
</style>
