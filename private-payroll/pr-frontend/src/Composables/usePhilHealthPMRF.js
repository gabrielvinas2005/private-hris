import { ref, reactive } from "vue";
import { philHealthPMRFApi } from "../services/api.js";
import { ElMessage } from "element-plus";
import { fillPhilHealthPDF } from "../utils/pdffiller.js";

export function usePhilHealthPMRF() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    employee_id: "",
    purpose: "registration",
    last_name: "",
    first_name: "",
    middle_name: "",
    name_extension: "",
    date_of_birth: "",
    place_of_birth: "",
    sex: "",
    civil_status: "",
    citizenship: "FILIPINO",
    philhealth_no: "",
    tin_no: "",
    mobile_number: "",
    email: "",
    permanent_address: "",
    permanent_zip: "",
    mailing_address: "",
    mailing_zip: "",
    member_type: "",
    signatory_name: "",
  });

  const employees = ref([]);

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;
      const res = await philHealthPMRFApi.getPhilHealthPMRFData();
      const data = res.data.data || {};
      employees.value = data.employees || [];
      return data;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load data";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Load employee details and auto-populate form
  const loadEmployeeDetails = async (employeeId) => {
    if (!employeeId) {
      // Reset form if no employee selected
      formData.last_name = "";
      formData.first_name = "";
      formData.middle_name = "";
      formData.name_extension = "";
      formData.date_of_birth = "";
      formData.place_of_birth = "";
      formData.sex = "";
      formData.civil_status = "";
      formData.citizenship = "FILIPINO";
      formData.philhealth_no = "";
      formData.tin_no = "";
      formData.mobile_number = "";
      formData.email = "";
      formData.permanent_address = "";
      formData.permanent_zip = "";
      formData.mailing_address = "";
      formData.mailing_zip = "";
      formData.mother_first_name = "";
      formData.mother_middle_name = "";
      formData.mother_last_name = "";
      formData.spouse_first_name = "";
      formData.spouse_middle_name = "";
      formData.spouse_last_name = "";
      formData.child_name = "";
      formData.child_birthdate = "";
      formData.child_middlename = "";
      formData.child_lastname = "";
      formData.signatory_name = "";
      return;
    }

    try {
      loading.value = true;
      error.value = null;
      const res = await philHealthPMRFApi.getEmployeeDetails(employeeId);
      const data = res.data.data || {};

      // Auto-populate form fields with employee data
      formData.last_name = data.last_name || "";
      formData.first_name = data.first_name || "";
      formData.middle_name = data.middle_name || "";
      formData.name_extension = data.name_extension || "";
      formData.date_of_birth = data.date_of_birth || "";
      formData.place_of_birth = data.place_of_birth || "";
      formData.sex = data.sex || "";
      formData.civil_status = data.civil_status || "";
      formData.citizenship = data.citizenship || "FILIPINO";
      formData.philhealth_no = data.philhealth_no || "";
      formData.tin_no = data.tin_no || "";
      formData.mobile_number = data.mobile_number || "";
      formData.email = data.email || "";
      formData.permanent_address = data.permanent_address || "";
      formData.permanent_zip = data.permanent_zip || "";
      formData.mailing_address =
        data.mailing_address || data.permanent_address || "";
      formData.mailing_zip = data.mailing_zip || data.permanent_zip || "";
      formData.mother_first_name = data.mother_first_name || "";
      formData.mother_middle_name = data.mother_middle_name || "";
      formData.mother_last_name = data.mother_last_name || "";
      formData.spouse_first_name = data.spouse_first_name || "";
      formData.spouse_middle_name = data.spouse_middle_name || "";
      formData.spouse_last_name = data.spouse_last_name || "";
      formData.child_name = data.child_name || "";
      formData.child_birthdate = data.child_birthdate || "";
      formData.child_middlename = data.child_middlename || "";
      formData.child_lastname = data.child_lastname || "";
      
      // Auto-fill signatory name with employee's full name
      const fullName = [
        data.first_name || "",
        data.middle_name || "",
        data.last_name || "",
      ]
        .filter(Boolean)
        .join(" ")
        .trim();
      formData.signatory_name = fullName || "";
      
      ElMessage.success("Employee details loaded successfully");
      return data;
    } catch (err) {
      error.value =
        err.response?.data?.message || "Failed to load employee details";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Preview report (opens in new tab)
  const previewReport = async () => {
    // Open a blank tab synchronously to avoid popup blocking
    const previewWindow = window.open("", "_blank");
    if (!previewWindow) {
      ElMessage.error("Popup blocked. Please allow popups for this site.");
      return;
    }

    // Show a simple loading screen while the PDF is being generated
    previewWindow.document.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="utf-8" />
          <title>Generating PhilHealth PMRF...</title>
          <style>
            * {
              box-sizing: border-box;
            }
            body {
              margin: 0;
              height: 100vh;
              display: flex;
              align-items: center;
              justify-content: center;
              font-family: Arial, sans-serif;
              background: #f5f5f5;
            }
            .loader {
              display: flex;
              flex-direction: column;
              align-items: center;
              gap: 12px;
            }
            .spinner {
              width: 40px;
              height: 40px;
              border-radius: 50%;
              border: 4px solid #ccc;
              border-top-color: #0d6efd;
              animation: spin 0.8s linear infinite;
            }
            @keyframes spin {
              to { transform: rotate(360deg); }
            }
            .text {
              color: #444;
              font-size: 14px;
            }
          </style>
        </head>
        <body>
          <div class="loader">
            <div class="spinner"></div>
            <div class="text">Preparing PhilHealth PMRF, please wait...</div>
          </div>
        </body>
      </html>
    `);
    previewWindow.document.close();

    try {
      loading.value = true;
      error.value = null;

      if (!formData.employee_id) {
        previewWindow.close();
        throw new Error("Employee is required");
      }

      // Fill PDF using pdf-lib (client-side)
      const blob = await fillPhilHealthPDF(formData);

      if (!blob || blob.size === 0) {
        previewWindow.close();
        throw new Error("Generated PDF is empty");
      }

      const url = window.URL.createObjectURL(blob);
      previewWindow.location.href = url;

      ElMessage.success("PhilHealth PMRF preview opened");
    } catch (err) {
      
      previewWindow.close();

      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to preview report";
      if (err.response?.status === 404) {
        ElMessage.error(
          serverMessage || "No data found for the selected employee"
        );
      } else if (err.response?.status === 422) {
        ElMessage.error("Please complete the required fields");
      } else {
        ElMessage.error(error.value);
      }
      throw err;
    } finally {
      loading.value = false;
    }
  };

  // Generate report (downloads file)
  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (!formData.employee_id) {
        throw new Error("Employee is required");
      }

      // Fill PDF using pdf-lib (client-side)
      const blob = await fillPhilHealthPDF(formData);

      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty");
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      // Build friendly filename
      const empObj = employees.value.find((e) => e.id === formData.employee_id);
      const empName =
        empObj?.name || `emp-${formData.employee_id || "unknown"}`;
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      link.download = `PhilHealth_PMRF_${sanitize(empName)}_${
        new Date().toISOString().split("T")[0]
      }.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      ElMessage.success("PhilHealth PMRF downloaded successfully");
    } catch (err) {
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to generate report";
      if (err.response?.status === 404) {
        ElMessage.error(
          serverMessage || "No data found for the selected employee"
        );
      } else if (err.response?.status === 422) {
        ElMessage.error("Please complete the required fields");
      } else {
        ElMessage.error(error.value);
      }
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    formData,
    employees,
    loadInitialData,
    loadEmployeeDetails,
    previewReport,
    generateReport,
  };
}
