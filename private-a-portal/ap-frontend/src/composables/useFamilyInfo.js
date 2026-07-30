import { ref, reactive, watch } from "vue";
import { ElNotification, ElMessageBox } from "element-plus";
import { ApiService } from "@/services/api.js";
import { useAppNotification } from "@/composables/useAppNotification.js";

/**
 * Composable for managing family information
 * Provides shared state and functions for PDS Family components
 */
export function useFamilyInfo() {
  const notify = useAppNotification();

  // State
  const loading = ref(true); // Start with true to show loading state immediately
  const error = ref(null);
  const saving = ref(false);
  const showModal = ref(false);

  // Dropdown options
  const prefixOptions = ref([]);
  const suffixOptions = ref([]);

  // Family data structure
  const familyData = reactive({
    id: null,
    father: {
      prefix: "",
      suffix: "",
      firstName: "",
      middleName: "",
      lastName: "",
    },
    mother: {
      prefix: "",
      suffix: "",
      firstName: "",
      middleName: "",
      lastName: "",
    },
    spouse: {
      prefix: "",
      suffix: "",
      firstName: "",
      middleName: "",
      lastName: "",
      occupation: "",
      employer: "",
      businessAddress: "",
    },
    children: [],
  });

  /**
   * Get employee number from localStorage
   */
  const getEmployeeNo = () => {
    const userData = localStorage.getItem("user_data");
    if (userData) {
      try {
        const parsedData = JSON.parse(userData);
        return parsedData.employee_no || parsedData.applicant_no;
      } catch (e) {
        console.error("Error parsing user_data:", e);
      }
    }
    return localStorage.getItem("employee_no") || null;
  };

  /**
   * Fetch prefix and suffix options from API
   */
  const fetchPrefixSuffixOptions = async () => {
    try {
      const [prefixResponse, suffixResponse] = await Promise.all([
        ApiService.getNamePrefixes(),
        ApiService.getNameSuffixes(),
      ]);

      // Parse prefix options
      if (
        prefixResponse.data &&
        prefixResponse.data.data &&
        prefixResponse.data.data.prefixes
      ) {
        prefixOptions.value = prefixResponse.data.data.prefixes.map((item) => ({
          id: item.id,
          name: item.name,
          value: item.id,
        }));
      } else if (Array.isArray(prefixResponse.data?.data)) {
        prefixOptions.value = prefixResponse.data.data.map((item) => ({
          id: item.id,
          name: item.name || item.title || item.label,
          value: item.id,
        }));
      }

      // Parse suffix options
      if (
        suffixResponse.data &&
        suffixResponse.data.data &&
        suffixResponse.data.data.suffixes
      ) {
        suffixOptions.value = suffixResponse.data.data.suffixes.map((item) => ({
          id: item.id,
          name: item.name,
          value: item.id,
        }));
      } else if (Array.isArray(suffixResponse.data?.data)) {
        suffixOptions.value = suffixResponse.data.data.map((item) => ({
          id: item.id,
          name: item.name || item.title || item.label,
          value: item.id,
        }));
      }
    } catch (error) {
      console.error("Error fetching prefix/suffix options:", error);
    }
  };

  /**
   * Get prefix name from ID
   */
  const getPrefixName = (id) => {
    if (!id || id === "" || id === 0) return "-";
    const prefix = prefixOptions.value.find((p) => p.id == id);
    return prefix ? prefix.name : id;
  };

  /**
   * Get suffix name from ID
   */
  const getSuffixName = (id) => {
    if (!id || id === 0 || id === "") return "-";
    const suffix = suffixOptions.value.find((s) => s.id == id);
    return suffix ? suffix.name : id;
  };

  /**
   * Get gender name from ID
   */
  const getGenderName = (id) => {
    if (!id || id === 0) return "-";
    const genderMap = { 1: "Male", 2: "Female" };
    return genderMap[id] || id;
  };

  /**
   * Initialize family data from API response
   */
  const initializeFamilyData = (raw) => {
    if (raw.id) {
      familyData.id = raw.id;
    } else {
      familyData.id = 0;
    }

    // Father data
    if (raw.father_first_name || raw.father_last_name) {
      familyData.father = {
        prefix: raw.father_name_prefix_id
          ? parseInt(raw.father_name_prefix_id)
          : "",
        suffix: raw.father_name_suffix_id
          ? parseInt(raw.father_name_suffix_id)
          : "",
        firstName: raw.father_first_name || "",
        middleName: raw.father_middle_name || "",
        lastName: raw.father_last_name || "",
      };
    }

    // Mother data
    if (raw.mother_first_name || raw.mother_last_name) {
      familyData.mother = {
        prefix: raw.mother_name_prefix_id
          ? parseInt(raw.mother_name_prefix_id)
          : "",
        suffix: raw.mother_name_suffix_id
          ? parseInt(raw.mother_name_suffix_id)
          : "",
        firstName: raw.mother_first_name || "",
        middleName: raw.mother_middle_name || "",
        lastName: raw.mother_last_name || "",
      };
    }

    // Spouse data
    if (raw.spouse_first_name || raw.spouse_last_name) {
      familyData.spouse = {
        prefix: raw.spouse_name_prefix_id
          ? parseInt(raw.spouse_name_prefix_id)
          : "",
        suffix: raw.spouse_name_suffix_id
          ? parseInt(raw.spouse_name_suffix_id)
          : "",
        firstName: raw.spouse_first_name || "",
        middleName: raw.spouse_middle_name || "",
        lastName: raw.spouse_last_name || "",
        occupation: raw.spouse_occupation || "",
        employer: raw.spouse_employer || "",
        businessAddress: raw.spouse_business_address || "",
      };
    }

    // Children data
    if (raw.children && Array.isArray(raw.children)) {
      familyData.children = raw.children.map((child) => ({
        children_id: child.children_id || null,
        firstName: child.first_name || "",
        middleName: child.middle_name || "",
        lastName: child.last_name || "",
        gender: child.gender ? parseInt(child.gender) : 0,
        birthdate: child.birthdate || "",
      }));
    } else {
      familyData.children = [];
    }
  };

  /**
   * Fetch family information from API
   */
  const fetchFamilyInfo = async () => {
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
        initializeFamilyData(raw);
      }
    } catch (err) {
      console.error("Error fetching family information:", err);
      error.value =
        err.response?.data?.message ||
        err.message ||
        "Failed to load family information.";
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Add a new child to the children array
   */
  const addChild = () => {
    familyData.children.push({
      children_id: null,
      firstName: "",
      middleName: "",
      lastName: "",
      gender: "",
      birthdate: "",
    });
  };

  /**
   * Remove a child from the children array
   */
  const removeChild = async (index) => {
    try {
      await ElMessageBox.confirm(
        "Are you sure you want to remove this child?",
        "Remove Child",
        {
          confirmButtonText: "Remove",
          cancelButtonText: "Cancel",
          type: "warning",
        }
      );
    } catch {
      return;
    }
    familyData.children.splice(index, 1);
    ElNotification({
      title: "Success",
      message: "Child removed successfully!",
      type: "success",
      duration: 2000,
      position: "top-right",
    });
  };

  /**
   * Prepare FormData for saving family information
   */
  const prepareFamilyFormData = () => {
    const formData = new FormData();
    formData.append("section", "family");

    // Father data
    formData.append("father_name_prefix_id", familyData.father.prefix || "");
    formData.append("father_first_name", familyData.father.firstName || "");
    formData.append("father_middle_name", familyData.father.middleName || "");
    formData.append("father_last_name", familyData.father.lastName || "");
    formData.append("father_name_suffix_id", familyData.father.suffix || "");

    // Mother data
    formData.append("mother_name_prefix_id", familyData.mother.prefix || "");
    formData.append("mother_first_name", familyData.mother.firstName || "");
    formData.append("mother_middle_name", familyData.mother.middleName || "");
    formData.append("mother_last_name", familyData.mother.lastName || "");
    formData.append("mother_name_suffix_id", familyData.mother.suffix || "");

    // Spouse data
    formData.append("spouse_name_prefix_id", familyData.spouse.prefix || "");
    formData.append("spouse_first_name", familyData.spouse.firstName || "");
    formData.append("spouse_middle_name", familyData.spouse.middleName || "");
    formData.append("spouse_last_name", familyData.spouse.lastName || "");
    formData.append("spouse_name_suffix_id", familyData.spouse.suffix || "");
    formData.append("spouse_occupation", familyData.spouse.occupation || "");
    formData.append("spouse_employer", familyData.spouse.employer || "");
    formData.append(
      "spouse_business_address",
      familyData.spouse.businessAddress || ""
    );

    // Children data
    familyData.children.forEach((child, index) => {
      if (child.firstName || child.lastName) {
        formData.append(`child_name[${index}]`, child.firstName || "");
        formData.append(`child_middlename[${index}]`, child.middleName || "");
        formData.append(`child_lastname[${index}]`, child.lastName || "");
        const genderId = child.gender ? parseInt(child.gender) : 0;
        formData.append(`child_gender[${index}]`, genderId.toString());
        formData.append(`child_birthdate[${index}]`, child.birthdate || "");
        if (child.children_id) {
          formData.append(`children_id[${index}]`, child.children_id);
        }
      }
    });

    return formData;
  };

  /**
   * Get employee ID from various sources
   */
  const getEmployeeId = async (employeeIdProp = null, infoId = null) => {
    let employeeId = 0;

    // First try to get from prop
    if (employeeIdProp !== null && employeeIdProp !== undefined) {
      const parsedId = parseInt(employeeIdProp, 10);
      if (!isNaN(parsedId)) {
        employeeId = parsedId;
      }
    }

    // If still no valid ID, try to get from info.id
    if (employeeId === 0 && infoId) {
      const parsedId = parseInt(infoId, 10);
      if (!isNaN(parsedId)) {
        employeeId = parsedId;
      }
    }

    // If still no ID, fetch employee ID from API based on employee_no
    if (employeeId === 0) {
      try {
        const employeeNo = getEmployeeNo();
        if (employeeNo) {
          const pdsResponse = await ApiService.getPDSDataApi(employeeNo);
          if (pdsResponse.data && pdsResponse.data.data) {
            const payload = pdsResponse.data.data;
            const raw = Array.isArray(payload)
              ? payload[0] || {}
              : payload || {};
            if (raw.id) {
              const parsedId = parseInt(raw.id, 10);
              if (!isNaN(parsedId)) {
                employeeId = parsedId;
              }
            }
          }
        }
      } catch (err) {
        console.warn("Could not fetch employee ID, using 0 (new record):", err);
      }
    }

    return parseInt(employeeId, 10) || 0;
  };

  /**
   * Save family information to API
   */
  const saveFamilyInfo = async (employeeIdProp = null) => {
    try {
      saving.value = true;

      const employeeNo = getEmployeeNo();
      if (!employeeNo) {
        throw new Error("Employee number not found. Please login again.");
      }

      // Prepare form data
      const formData = prepareFamilyFormData();

      // Get employee ID
      const employeeId = await getEmployeeId(employeeIdProp, familyData.id);

      // Add employee_no to form data if creating new record
      if (employeeId === 0) {
        formData.append("employee_no", employeeNo || "");
      }

      console.log("💾 Saving family information:", {
        employeeId,
        employeeNo,
        familyData: {
          father: familyData.father,
          mother: familyData.mother,
          spouse: familyData.spouse,
          childrenCount: familyData.children.length,
        },
      });

      // Call API to save family data
      const response = await ApiService.storePDS(employeeId, formData);

      console.log("✅ Save response:", response);

      notify.success("Success", "Family information saved successfully!", {
        duration: 4000,
      });

      return response;
    } catch (error) {
      console.error("Error saving family information:", error);
      const errorMessage =
        error.response?.data?.message ||
        error.message ||
        "Failed to save family information. Please try again.";
      notify.error("Save failed", errorMessage, { duration: 5000 });
      throw error;
    } finally {
      saving.value = false;
    }
  };

  /**
   * Initialize family data from props (for EditFamily component)
   */
  const initializeFromProps = (info) => {
    if (info && Object.keys(info).length > 0) {
      // Map the data from props to familyData structure
      if (info.father) {
        Object.assign(familyData.father, {
          ...info.father,
          prefix: info.father.prefix ? parseInt(info.father.prefix) : "",
          suffix: info.father.suffix ? parseInt(info.father.suffix) : "",
        });
      }
      if (info.mother) {
        Object.assign(familyData.mother, {
          ...info.mother,
          prefix: info.mother.prefix ? parseInt(info.mother.prefix) : "",
          suffix: info.mother.suffix ? parseInt(info.mother.suffix) : "",
        });
      }
      if (info.spouse) {
        Object.assign(familyData.spouse, {
          ...info.spouse,
          prefix: info.spouse.prefix ? parseInt(info.spouse.prefix) : "",
          suffix: info.spouse.suffix ? parseInt(info.spouse.suffix) : "",
        });
      }
      if (info.children && Array.isArray(info.children)) {
        familyData.children = info.children.map((child) => ({
          ...child,
          gender: child.gender ? parseInt(child.gender) : 0,
        }));
      }
      if (info.id) {
        familyData.id = info.id;
      }
    }
  };

  return {
    // State
    loading,
    error,
    saving,
    showModal,
    prefixOptions,
    suffixOptions,
    familyData,

    // Functions
    getEmployeeNo,
    fetchPrefixSuffixOptions,
    getPrefixName,
    getSuffixName,
    getGenderName,
    fetchFamilyInfo,
    saveFamilyInfo,
    addChild,
    removeChild,
    initializeFamilyData,
    initializeFromProps,
    prepareFamilyFormData,
    getEmployeeId,
  };
}
