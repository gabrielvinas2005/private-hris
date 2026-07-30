<template>
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
  >
    <div
      class="bg-white rounded-lg shadow-xl max-w-7xl w-full mx-4 max-h-[95vh] overflow-y-auto"
    >
      <div
        class="flex items-center justify-between p-4 border-b bg-white sticky top-0 z-10"
      >
        <h2 class="text-xl font-semibold text-gray-900">
          Edit Personal Data Sheet (PDS)
        </h2>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>

      <div class="px-4 py-6 bg-gray-50">
        <div v-if="loading" class="text-center py-20">
          <div
            class="w-12 h-12 mx-auto mb-4 border-b-2 border-blue-600 rounded-full animate-spin"
          ></div>
          <p class="text-gray-600">Loading personal information...</p>
        </div>

        <div v-else-if="error" class="text-center py-20">
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

        <div v-else class="grid grid-cols-1 xl:grid-cols-4 gap-6">
          <aside class="col-span-1 space-y-4">
            <div class="p-4 bg-white border rounded-lg shadow">
              <div class="flex flex-col items-center mb-4">
                <div
                  class="flex items-center justify-center w-32 h-40 mb-3 border-2 border-gray-300 border-dashed rounded bg-gray-50 overflow-hidden"
                >
                  <img
                    v-if="photoPreview || personalInfo.photo"
                    :src="photoPreview || getPhotoUrl(personalInfo.photo)"
                    alt="Applicant Photo"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="text-center">
                    <i class="mb-2 text-2xl text-gray-400 fas fa-camera"></i>
                    <p class="text-xs text-gray-500">Photo</p>
                  </div>
                </div>
                <input
                  ref="photoInput"
                  type="file"
                  accept="image/*"
                  class="hidden"
                  @change="handlePhotoChange"
                />
                <button
                  @click="$refs.photoInput.click()"
                  class="px-3 py-1 text-xs text-white bg-blue-500 rounded hover:bg-blue-600"
                >
                  Browse Photo
                </button>
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

            <EditContactInfo :info="personalInfo" @update="handleUpdate" />

            <EditGovernmentIds :info="personalInfo" @update="handleUpdate" />
          </aside>

          <section class="col-span-1 xl:col-span-3">
            <div class="space-y-6">
              <EditPersonalInfo
                :info="personalInfo"
                :dropdown-data="dropdownData"
                @update="handleUpdate"
              />

              <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <EditAddress
                  :info="personalInfo"
                  :address-data="addressData"
                  type="residential"
                  title="Residential Address"
                  prefix="ra"
                  @update="handleUpdate"
                />

                <EditAddress
                  :info="personalInfo"
                  :address-data="addressData"
                  :residential-data="personalInfo"
                  type="permanent"
                  title="Permanent Address"
                  prefix="pa"
                  @update="handleUpdate"
                  @copy-residential="copyResidentialAddress"
                />
              </div>
            </div>
          </section>
        </div>
      </div>

      <div
        class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50 sticky bottom-0"
      >
        <button
          @click="$emit('close')"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Cancel
        </button>
        <button
          @click="saveChanges"
          :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        >
          <div
            v-if="saving"
            class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"
          ></div>
          {{ saving ? "Saving..." : "Save Changes" }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from "vue";
import { ApiService } from "@/services/api.js";
import { useAppNotification } from "@/composables/useAppNotification.js";
import EditPersonalInfo from "./EditPersonalInfo.vue";
import EditContactInfo from "./EditContactInfo.vue";
import EditGovernmentIds from "./EditGovernmentIds.vue";
import EditAddress from "./EditAddress.vue";

import regionsData from "@/assets/refregion.json";
import provincesData from "@/assets/refprovince.json";
import citiesData from "@/assets/refcitymun.json";
import barangaysData from "@/assets/refbrgy.json";

const emit = defineEmits(["close", "save"]);

const personalInfo = ref({});
const loading = ref(true);
const error = ref(null);
const saving = ref(false);
const isInitialLoad = ref(true);
const photoInput = ref(null);
const photoPreview = ref(null);
const selectedPhotoFile = ref(null);

const notify = useAppNotification();

const dropdownData = ref({
  bloodTypes: [],
  civilStatus: [],
  prefixes: [],
  suffixes: [],
  religions: [],
});

const addressData = ref({
  regions: [],
  ra_provinces: [],
  ra_cities: [],
  ra_barangays: [],
  pa_provinces: [],
  pa_cities: [],
  pa_barangays: [],
});

const getApplicantNo = () => {
  if (personalInfo.value?.employee_no || personalInfo.value?.applicant_no) {
    return personalInfo.value.employee_no || personalInfo.value.applicant_no;
  }

  const userData = localStorage.getItem("user_data");
  if (userData) {
    const parsedData = JSON.parse(userData);
    return parsedData.applicant_no || parsedData.employee_no;
  }

  return null;
};

const handleUpdate = (updates) => {
  Object.assign(personalInfo.value, updates);
};

const REQUIRED_FIELDS = [
  { key: "first_name", label: "First Name" },
  // { key: "middlename", label: "Middle Name" },
  { key: "surname", label: "Last Name" },
  { key: "birth_date", label: "Birth Date" },
  { key: "birth_place", label: "Birth Place" },
  { key: "gender", label: "Gender" },
  { key: "civil_status", label: "Civil Status" },
  { key: "citizenship", label: "Citizenship" },
  { key: "ra_region", label: "Residential address — Region" },
  { key: "ra_province", label: "Residential address — Province" },
  { key: "ra_city", label: "Residential address — City/Municipality" },
  { key: "ra_barangay", label: "Residential address — Barangay" },
  { key: "ra_street", label: "Residential address — Street" },
  { key: "ra_village", label: "Residential address — Village/Subdivision" },
  { key: "pa_region", label: "Permanent address — Region" },
  { key: "pa_province", label: "Permanent address — Province" },
  { key: "pa_city", label: "Permanent address — City/Municipality" },
  { key: "pa_barangay", label: "Permanent address — Barangay" },
  { key: "pa_street", label: "Permanent address — Street" },
  { key: "pa_village", label: "Permanent address — Village/Subdivision" },
];

const isMissingRequiredValue = (value) => {
  if (value === null || value === undefined) return true;
  if (typeof value === "string") return value.trim() === "";
  if (typeof value === "number") return value === 0;
  return false;
};

const validateRequiredFields = () => {
  const missing = [];
  for (const field of REQUIRED_FIELDS) {
    const value = personalInfo.value?.[field.key];
    // Treat "0" as missing for select fields that map to numeric ids.
    const normalized =
      typeof value === "string" &&
      value.trim() !== "" &&
      !Number.isNaN(Number(value))
        ? Number(value)
        : value;
    if (isMissingRequiredValue(normalized)) {
      missing.push(field.label);
    }
  }
  return missing;
};

const copyResidentialAddress = async () => {
  personalInfo.value.pa_region = personalInfo.value.ra_region;
  personalInfo.value.pa_province = personalInfo.value.ra_province;
  personalInfo.value.pa_city = personalInfo.value.ra_city;
  personalInfo.value.pa_barangay = personalInfo.value.ra_barangay;
  personalInfo.value.pa_house_no = personalInfo.value.ra_house_no;
  personalInfo.value.pa_street = personalInfo.value.ra_street;
  personalInfo.value.pa_village = personalInfo.value.ra_village;

  if (personalInfo.value.pa_region) {
    await fetchProvinces(personalInfo.value.pa_region, "pa");
  }
  if (personalInfo.value.pa_province) {
    await fetchCities(personalInfo.value.pa_province, "pa");
  }
  if (personalInfo.value.pa_city) {
    await fetchBarangays(personalInfo.value.pa_city, "pa");
  }
};

const fetchProvinces = async (regionCode, type = "ra") => {
  try {
    if (!regionCode) {
      if (type === "ra") {
        addressData.value.ra_provinces = [];
      } else {
        addressData.value.pa_provinces = [];
      }
      return;
    }

    const provinces = provincesData.RECORDS.filter(
      (province) => province.regCode === regionCode,
    );

    if (type === "ra") {
      addressData.value.ra_provinces = provinces.map((province) => ({
        id: province.id,
        name: province.provDesc,
        code: province.provCode,
        value: province.provDesc,
        regCode: province.regCode,
      }));
    } else {
      addressData.value.pa_provinces = provinces.map((province) => ({
        id: province.id,
        name: province.provDesc,
        code: province.provCode,
        value: province.provDesc,
        regCode: province.regCode,
      }));
    }
  } catch (err) {
    if (type === "ra") {
      addressData.value.ra_provinces = [];
    } else {
      addressData.value.pa_provinces = [];
    }
  }
};

const fetchCities = async (provinceCode, type = "ra") => {
  try {
    if (!provinceCode) {
      if (type === "ra") {
        addressData.value.ra_cities = [];
      } else {
        addressData.value.pa_cities = [];
      }
      return;
    }

    const cities = citiesData.RECORDS.filter(
      (city) => city.provCode === provinceCode,
    );
    if (type === "ra") {
      addressData.value.ra_cities = cities.map((city) => ({
        id: city.id,
        name: city.citymunDesc,
        code: city.citymunCode,
        value: city.citymunDesc,
        provCode: city.provCode,
      }));
    } else {
      addressData.value.pa_cities = cities.map((city) => ({
        id: city.id,
        name: city.citymunDesc,
        code: city.citymunCode,
        value: city.citymunDesc,
        provCode: city.provCode,
      }));
    }
  } catch (err) {
    if (type === "ra") {
      addressData.value.ra_cities = [];
    } else {
      addressData.value.pa_cities = [];
    }
  }
};

const fetchBarangays = async (cityCode, type = "ra") => {
  try {
    if (!cityCode) {
      if (type === "ra") {
        addressData.value.ra_barangays = [];
      } else {
        addressData.value.pa_barangays = [];
      }
      return;
    }

    const barangays = barangaysData.RECORDS.filter(
      (barangay) => barangay.citymunCode === cityCode,
    );
    if (type === "ra") {
      addressData.value.ra_barangays = barangays.map((barangay) => ({
        id: barangay.id,
        name: barangay.brgyDesc,
        code: barangay.brgyCode,
        value: barangay.brgyDesc,
        citymunCode: barangay.citymunCode,
      }));
    } else {
      addressData.value.pa_barangays = barangays.map((barangay) => ({
        id: barangay.id,
        name: barangay.brgyDesc,
        code: barangay.brgyCode,
        value: barangay.brgyDesc,
        citymunCode: barangay.citymunCode,
      }));
    }
  } catch (err) {
    if (type === "ra") {
      addressData.value.ra_barangays = [];
    } else {
      addressData.value.pa_barangays = [];
    }
  }
};

const extractDataFromResponse = (response) => {
  if (!response || !response.data) return [];

  let data = response.data;

  if (typeof data === "string") {
    try {
      data = JSON.parse(data);
    } catch (e) {
      return [];
    }
  }

  if (Array.isArray(data)) {
    return data;
  }

  if (data.data && Array.isArray(data.data)) {
    return data.data;
  }

  if (typeof data === "object") {
    const possibleArrayProps = ["items", "list", "data", "results", "records"];
    for (const prop of possibleArrayProps) {
      if (data[prop] && Array.isArray(data[prop])) {
        return data[prop];
      }
    }
  }

  return Array.isArray(data) ? data : [];
};

const getEmployeeNo = () => {
  const userData = localStorage.getItem("user_data");
  if (userData) {
    const parsedData = JSON.parse(userData);
    return parsedData.employee_no;
  }

  return localStorage.getItem("employee_no") || null;
};

const fetchPersonalInfo = async () => {
  try {
    loading.value = true;
    error.value = null;

    const employeeNo = getEmployeeNo();

    if (!employeeNo) {
      throw new Error("Employee number not found. Please login again.");
    }

    const response = await ApiService.getPDSDataApi(employeeNo);

    if (response.data && response.data.data) {
      const payload = response.data.data;
      const raw = Array.isArray(payload) ? payload[0] || {} : payload || {};
      personalInfo.value = mapPdsRecord(raw);

      if (personalInfo.value.photo) {
        photoPreview.value = getPhotoUrl(personalInfo.value.photo);
      } else {
        photoPreview.value = null;
      }

      await initializeAddressDropdowns();
      isInitialLoad.value = false;
    }
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      err.message ||
      "Failed to load personal information.";
  } finally {
    loading.value = false;
  }
};

const initializeAddressDropdowns = async () => {
  if (personalInfo.value.ra_region) {
    await fetchProvinces(personalInfo.value.ra_region, "ra");

    if (personalInfo.value.ra_province) {
      await fetchCities(personalInfo.value.ra_province, "ra");

      if (personalInfo.value.ra_city) {
        await fetchBarangays(personalInfo.value.ra_city, "ra");
      }
    }
  }

  if (personalInfo.value.pa_region) {
    await fetchProvinces(personalInfo.value.pa_region, "pa");

    if (personalInfo.value.pa_province) {
      await fetchCities(personalInfo.value.pa_province, "pa");

      if (personalInfo.value.pa_city) {
        await fetchBarangays(personalInfo.value.pa_city, "pa");
      }
    }
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

    birth_place: record.birth_place ?? "",
    birth_date: record.birthdate ?? record.birth_date ?? "",

    email: record.email ?? "",
    mobile_no: record.mobile_no ?? "",
    tel_no: record.tel_no ?? record.telephone_no ?? "",

    ra_region: record.ra_region ?? "",
    ra_province: record.ra_province ?? "",
    ra_city: record.ra_city ?? "",
    ra_house_no: record.ra_house_no ?? "",
    ra_barangay: record.ra_barangay ?? "",
    ra_street: record.ra_street ?? "",
    ra_village: record.ra_village ?? "",

    pa_region: record.pa_region ?? "",
    pa_province: record.pa_province ?? "",
    pa_city: record.pa_city ?? "",
    pa_house_no: record.pa_house_no ?? "",
    pa_barangay: record.pa_barangay ?? "",
    pa_street: record.pa_street ?? "",
    pa_village: record.pa_village ?? "",

    gender: record.gender_id ?? record.Gender ?? record.gender ?? "",
    civil_status:
      record.civil_status_id ?? record.civilStatus ?? record.civil_status ?? "",
    citizenship:
      record.citizenship_id ?? record.citizenship ?? record.citizen ?? "",
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

const fetchDropdownData = async () => {
  try {
    const [
      civilStatusRes,
      bloodTypesRes,
      prefixesRes,
      suffixesRes,
      religionsRes,
    ] = await Promise.all([
      ApiService.getCivilStatus(),
      ApiService.getBloodTypes(),
      ApiService.getNamePrefixes(),
      ApiService.getNameSuffixes(),
      ApiService.getReligions(),
    ]);

    dropdownData.value.civilStatus = extractDataFromResponse(civilStatusRes);
    dropdownData.value.bloodTypes = extractDataFromResponse(bloodTypesRes);
    dropdownData.value.prefixes = extractDataFromResponse(prefixesRes);
    dropdownData.value.suffixes = extractDataFromResponse(suffixesRes);
    dropdownData.value.religions = extractDataFromResponse(religionsRes);

    addressData.value.regions = regionsData.RECORDS || [];
  } catch (err) {
    dropdownData.value.civilStatus = [
      "Single",
      "Married",
      "Widowed",
      "Divorced",
      "Separated",
    ];
    dropdownData.value.bloodTypes = [
      "A+",
      "A-",
      "B+",
      "B-",
      "AB+",
      "AB-",
      "O+",
      "O-",
    ];
    dropdownData.value.prefixes = [
      "Mr.",
      "Mrs.",
      "Ms.",
      "Dr.",
      "Prof.",
      "Engr.",
      "Atty.",
    ];
    dropdownData.value.suffixes = ["Jr.", "Sr.", "I", "II", "III", "IV", "V"];
    dropdownData.value.religions = [
      "Roman Catholic",
      "Protestant",
      "Islam",
      "Buddhism",
      "Hinduism",
      "Other",
    ];

    addressData.value.regions = regionsData.RECORDS || [];
  }
};

const saveChanges = async () => {
  try {
    saving.value = true;

    const firstName = personalInfo.value.first_name || "";
    // const middleName = personalInfo.value.middlename || "";
    const lastName = personalInfo.value.surname || "";
    const fullname = [firstName, lastName]
      .filter((name) => name.trim())
      .join(" ");
    personalInfo.value.fullname = fullname;

    const missingRequired = validateRequiredFields();
    if (missingRequired.length > 0) {
      notify.warning(
        "Validation",
        `Please fill in all required fields: ${missingRequired.join(", ")}`,
        { duration: 7000 },
      );
      return;
    }

    let applicantId = null;

    try {
      const applicantResponse = await ApiService.getApplicantPage();
      if (applicantResponse.data?.data?.applicant?.[0]?.id) {
        applicantId = applicantResponse.data.data.applicant[0].id;
      }
    } catch (apiError) {}

    if (!applicantId) {
      const userData = localStorage.getItem("user_data");
      if (userData) {
        try {
          const parsedData = JSON.parse(userData);
          applicantId =
            parsedData.id || parsedData.applicant_id || parsedData.applicantId;
        } catch (e) {}
      }
    }

    if (!applicantId && personalInfo.value?.id && personalInfo.value.id !== 0) {
      applicantId = personalInfo.value.id;
    }

    if (!applicantId || applicantId === 0) {
      throw new Error(
        "Applicant information not found. Please refresh the page and try again.",
      );
    }

    const formData = new FormData();
    formData.append("section", "personal");

    if (selectedPhotoFile.value) {
      formData.append("photo", selectedPhotoFile.value);
    }

    formData.append("first_name", personalInfo.value.first_name || "");
    formData.append("middle_name", personalInfo.value.middlename || "");
    formData.append("last_name", personalInfo.value.surname || "");
    formData.append("birth_place", personalInfo.value.birth_place || "");
    formData.append("birth_date", personalInfo.value.birth_date || "");
    formData.append("email", personalInfo.value.email || "");

    formData.append(
      "prefix",
      personalInfo.value.prefix ? parseInt(personalInfo.value.prefix) : 0,
    );
    formData.append(
      "suffix",
      personalInfo.value.suffix ? parseInt(personalInfo.value.suffix) : 0,
    );
    formData.append(
      "civil_status",
      personalInfo.value.civil_status
        ? parseInt(personalInfo.value.civil_status)
        : 0,
    );
    formData.append(
      "nationality",
      personalInfo.value.nationality
        ? parseInt(personalInfo.value.nationality)
        : 1,
    );
    formData.append(
      "religion",
      personalInfo.value.religion ? parseInt(personalInfo.value.religion) : 0,
    );
    formData.append(
      "citizenship",
      personalInfo.value.citizenship
        ? parseInt(personalInfo.value.citizenship)
        : 0,
    );
    formData.append(
      "gender",
      personalInfo.value.gender ? parseInt(personalInfo.value.gender) : 0,
    );
    formData.append(
      "age",
      personalInfo.value.age ? parseInt(personalInfo.value.age) : 0,
    );

    formData.append(
      "height",
      personalInfo.value.height ? parseFloat(personalInfo.value.height) : 0,
    );
    formData.append(
      "weight",
      personalInfo.value.weight ? parseFloat(personalInfo.value.weight) : 0,
    );
    formData.append(
      "blood_type",
      personalInfo.value.blood_type
        ? parseInt(personalInfo.value.blood_type)
        : 0,
    );

    formData.append("mobile_no", personalInfo.value.mobile_no || "");
    formData.append("telephone_no", personalInfo.value.tel_no || "");

    formData.append("tin_no", personalInfo.value.tin_no || "");
    formData.append("gsis_no", personalInfo.value.gsis_no || "");
    formData.append("sss_no", personalInfo.value.sss_no || "");
    formData.append("pagibig_no", personalInfo.value.pagibig_no || "");
    formData.append("philhealth_no", personalInfo.value.philhealth_no || "");

    formData.append("ra_region", personalInfo.value.ra_region || "");
    formData.append("ra_province", personalInfo.value.ra_province || "");
    formData.append("ra_city", personalInfo.value.ra_city || "");
    formData.append("ra_barangay", personalInfo.value.ra_barangay || "");
    formData.append("ra_house_no", personalInfo.value.ra_house_no || "");
    formData.append("ra_street", personalInfo.value.ra_street || "");
    formData.append("ra_village", personalInfo.value.ra_village || "");
    formData.append("pa_region", personalInfo.value.pa_region || "");
    formData.append("pa_province", personalInfo.value.pa_province || "");
    formData.append("pa_city", personalInfo.value.pa_city || "");
    formData.append("pa_barangay", personalInfo.value.pa_barangay || "");
    formData.append("pa_house_no", personalInfo.value.pa_house_no || "");
    formData.append("pa_street", personalInfo.value.pa_street || "");
    formData.append("pa_village", personalInfo.value.pa_village || "");

    formData.append(
      "is_dual_citizen",
      Boolean(personalInfo.value.is_dual_citizen) ? "1" : "0",
    );
    formData.append(
      "by_birth",
      Boolean(personalInfo.value.by_birth) ? "1" : "0",
    );
    formData.append(
      "by_naturalization",
      Boolean(personalInfo.value.by_naturalization) ? "1" : "0",
    );
    if (personalInfo.value.indicate_country)
      formData.append("indicate_country", personalInfo.value.indicate_country);

    const response = await ApiService.storePDS(applicantId, formData);

    const errorMessage = response.data?.message || "";
    const hasError =
      errorMessage.toLowerCase().includes("not found") ||
      errorMessage.toLowerCase().includes("error") ||
      errorMessage.toLowerCase().includes("failed");

    if (response && response.data && response.data.success && !hasError) {
      selectedPhotoFile.value = null;
      photoPreview.value = null;
      if (photoInput.value) {
        photoInput.value.value = "";
      }

      emit("save", personalInfo.value);
      emit("close");
      notify.success(
        "Saved",
        response.data?.message || "Personal information updated successfully!",
      );
    } else {
      const errorMsg = hasError
        ? errorMessage
        : response.data?.message || "Failed to update personal information";
      throw new Error(errorMsg);
    }
  } catch (err) {
    if (err.response && err.response.status === 422) {
      if (err.response.data.errors) {
        const validationErrors = [];
        Object.keys(err.response.data.errors).forEach((field) => {
          err.response.data.errors[field].forEach((message) => {
            validationErrors.push(`${field}: ${message}`);
          });
        });
        const msg =
          validationErrors.length > 0
            ? validationErrors.slice(0, 3).join(" | ") +
              (validationErrors.length > 3
                ? ` (+${validationErrors.length - 3} more)`
                : "")
            : "Please check all required fields.";
        notify.warning("Validation", msg, { duration: 7000 });
      } else {
        notify.warning("Validation", "Please check all required fields.", {
          duration: 7000,
        });
      }
    } else {
      const errorMessage =
        err.response?.data?.message ||
        err.message ||
        "Failed to save changes. Please try again.";
      notify.error("Save failed", errorMessage, { duration: 5000 });
    }
  } finally {
    saving.value = false;
  }
};

const handlePhotoChange = (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  if (!file.type.startsWith("image/")) {
    notify.warning("Invalid file", "Please select a valid image file.");
    return;
  }

  if (file.size > 3 * 1024 * 1024) {
    notify.warning("File too large", "Image size must be less than 3MB.");
    return;
  }

  selectedPhotoFile.value = file;

  const reader = new FileReader();
  reader.onload = (e) => {
    photoPreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const getPhotoUrl = (photo) => {
  if (!photo) return null;

  if (typeof photo === "string" && photo.startsWith("data:image")) {
    return photo;
  }

  if (typeof photo === "string" && !photo.startsWith("data:")) {
    return `data:image/jpeg;base64,${photo}`;
  }

  return photo;
};

watch(
  () => personalInfo.value.ra_region,
  async (newRegion, oldRegion) => {
    if (isInitialLoad.value && oldRegion === undefined) {
      return;
    }

    if (newRegion) {
      const selectedRegion = addressData.value.regions.find(
        (r) =>
          r.name === newRegion ||
          r.value === newRegion ||
          r.regCode === newRegion,
      );

      if (selectedRegion) {
        await fetchProvinces(
          selectedRegion.regCode || selectedRegion.code,
          "ra",
        );
      } else {
        await fetchProvinces(newRegion, "ra");
      }

      if (!isInitialLoad.value) {
        personalInfo.value.ra_province = "";
        personalInfo.value.ra_city = "";
        personalInfo.value.ra_barangay = "";
        addressData.value.ra_cities = [];
        addressData.value.ra_barangays = [];
      }
    } else {
      addressData.value.ra_provinces = [];
      addressData.value.ra_cities = [];
      addressData.value.ra_barangays = [];
      personalInfo.value.ra_province = "";
      personalInfo.value.ra_city = "";
      personalInfo.value.ra_barangay = "";
    }
  },
);

watch(
  () => personalInfo.value.ra_province,
  async (newProvince, oldProvince) => {
    if (isInitialLoad.value && oldProvince === undefined) {
      return;
    }

    if (newProvince) {
      const selectedProvince = addressData.value.ra_provinces.find(
        (p) =>
          p.name === newProvince ||
          p.value === newProvince ||
          p.provCode === newProvince,
      );
      if (selectedProvince) {
        await fetchCities(
          selectedProvince.provCode || selectedProvince.code,
          "ra",
        );
      } else {
        await fetchCities(newProvince, "ra");
      }

      if (!isInitialLoad.value) {
        personalInfo.value.ra_city = "";
        personalInfo.value.ra_barangay = "";
        addressData.value.ra_barangays = [];
      }
    } else {
      addressData.value.ra_cities = [];
      addressData.value.ra_barangays = [];
      personalInfo.value.ra_city = "";
      personalInfo.value.ra_barangay = "";
    }
  },
);

watch(
  () => personalInfo.value.ra_city,
  async (newCity, oldCity) => {
    if (isInitialLoad.value && oldCity === undefined) {
      return;
    }

    if (newCity) {
      const selectedCity = addressData.value.ra_cities.find(
        (c) =>
          c.name === newCity ||
          c.value === newCity ||
          c.citymunCode === newCity,
      );

      if (selectedCity) {
        await fetchBarangays(
          selectedCity.citymunCode || selectedCity.code,
          "ra",
        );
      } else {
        await fetchBarangays(newCity, "ra");
      }

      if (!isInitialLoad.value) {
        personalInfo.value.ra_barangay = "";
      }
    } else {
      addressData.value.ra_barangays = [];
      personalInfo.value.ra_barangay = "";
    }
  },
);

watch(
  () => personalInfo.value.pa_region,
  async (newRegion, oldRegion) => {
    if (isInitialLoad.value && oldRegion === undefined) {
      return;
    }

    if (newRegion) {
      const selectedRegion = addressData.value.regions.find(
        (r) =>
          r.name === newRegion ||
          r.value === newRegion ||
          r.regCode === newRegion,
      );

      if (selectedRegion) {
        await fetchProvinces(
          selectedRegion.regCode || selectedRegion.code,
          "pa",
        );
      } else {
        await fetchProvinces(newRegion, "pa");
      }

      if (!isInitialLoad.value) {
        personalInfo.value.pa_province = "";
        personalInfo.value.pa_city = "";
        personalInfo.value.pa_barangay = "";
        addressData.value.pa_cities = [];
        addressData.value.pa_barangays = [];
      }
    } else {
      addressData.value.pa_provinces = [];
      addressData.value.pa_cities = [];
      addressData.value.pa_barangays = [];
      personalInfo.value.pa_province = "";
      personalInfo.value.pa_city = "";
      personalInfo.value.pa_barangay = "";
    }
  },
);

watch(
  () => personalInfo.value.pa_province,
  async (newProvince, oldProvince) => {
    if (isInitialLoad.value && oldProvince === undefined) {
      return;
    }

    if (newProvince) {
      const selectedProvince = addressData.value.pa_provinces.find(
        (p) =>
          p.name === newProvince ||
          p.value === newProvince ||
          p.provCode === newProvince,
      );

      if (selectedProvince) {
        await fetchCities(
          selectedProvince.provCode || selectedProvince.code,
          "pa",
        );
      } else {
        await fetchCities(newProvince, "pa");
      }

      if (!isInitialLoad.value) {
        personalInfo.value.pa_city = "";
        personalInfo.value.pa_barangay = "";
        addressData.value.pa_barangays = [];
      }
    } else {
      addressData.value.pa_cities = [];
      addressData.value.pa_barangays = [];
      personalInfo.value.pa_city = "";
      personalInfo.value.pa_barangay = "";
    }
  },
);

watch(
  () => personalInfo.value.pa_city,
  async (newCity, oldCity) => {
    if (isInitialLoad.value && oldCity === undefined) {
      return;
    }

    if (newCity) {
      const selectedCity = addressData.value.pa_cities.find(
        (c) =>
          c.name === newCity ||
          c.value === newCity ||
          c.citymunCode === newCity,
      );

      if (selectedCity) {
        await fetchBarangays(
          selectedCity.citymunCode || selectedCity.code,
          "pa",
        );
      } else {
        await fetchBarangays(newCity, "pa");
      }

      if (!isInitialLoad.value) {
        personalInfo.value.pa_barangay = "";
      }
    } else {
      addressData.value.pa_barangays = [];
      personalInfo.value.pa_barangay = "";
    }
  },
);

onMounted(async () => {
  try {
    await Promise.all([fetchDropdownData(), fetchPersonalInfo()]);
  } catch (err) {}
});
</script>
