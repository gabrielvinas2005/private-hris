import { ref, reactive } from "vue";
import { birForm2305Api } from "../services/api.js";
import { ElMessage } from "element-plus";
import { fillBIRForm2305PDF } from "../utils/pdffiller.js";

export function useBIRForm2305() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    employee_id: "",
    exemption_status: "",
    exemption_code: "",
    dependent_count: 0,
    gross_compensation: "",
    date_hired: "",
    date_resignation: "",
    employer_details: [
      { line_no: "14A", business_nature: "", tax_rate: "", m: "", s: "" },
      { line_no: "14B", business_nature: "", tax_rate: "", m: "", s: "" },
    ],
    tin_no: "",
    rdo_code: "",
    last_name: "",
    first_name: "",
    middle_name: "",
    date_of_birth: "",
    sex: "",
    civil_status: "",
    residence_address: "",
    residence_zip: "",
    business_address: "",
    business_zip: "",
    employer_tin: "",
    employer_rdo: "",
    employer_name: "",
    employer_address: "",
    employer_zip: "",
    spouse_tin: "",
    spouse_last_name: "",
    spouse_first_name: "",
    spouse_middle_name: "",
    dependent_1_last_name: "",
    dependent_1_first_name: "",
    dependent_1_middle_name: "",
    dependent_1_birthdate: "",
    effective_date: "",
    certification_date: "",
    signatory_name: "",
    signatory_title: "",
  });

  const employees = ref([]);
  const company = ref(null);

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;
      const res = await birForm2305Api.getBIRForm2305Data();
      const data = res.data.data || {};
      employees.value = data.employees || [];
      company.value = data.company || null;
      return data;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load data";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const loadEmployeeDetails = async (employeeId) => {
    if (!employeeId) {
      formData.exemption_status = "";
      formData.exemption_code = "";
      formData.dependent_count = 0;
      formData.gross_compensation = "";
      formData.date_hired = "";
      formData.date_resignation = "";
      formData.tin_no = "";
      formData.rdo_code = "";
      formData.last_name = "";
      formData.first_name = "";
      formData.middle_name = "";
      formData.date_of_birth = "";
      formData.sex = "";
      formData.civil_status = "";
      formData.residence_address = "";
      formData.residence_zip = "";
      formData.business_address = "";
      formData.business_zip = "";
      formData.employer_tin = "";
      formData.employer_rdo = "";
      formData.employer_name = "";
      formData.employer_address = "";
      formData.employer_zip = "";
      formData.spouse_tin = "";
      formData.spouse_last_name = "";
      formData.spouse_first_name = "";
      formData.spouse_middle_name = "";
      formData.dependent_1_last_name = "";
      formData.dependent_1_first_name = "";
      formData.dependent_1_middle_name = "";
      formData.dependent_1_birthdate = "";
      formData.effective_date = "";
      formData.certification_date = "";
      formData.signatory_name = "";
      formData.signatory_title = "";
      return;
    }

    try {
      loading.value = true;
      error.value = null;
      const res = await birForm2305Api.getEmployeeDetails(employeeId);
      const data = res.data.data || {};

      formData.exemption_status = data.exemption_status || "";
      formData.exemption_code = data.exemption_code || "";
      formData.dependent_count = data.dependent_count || 0;
      formData.date_hired = data.date_hired || "";
      formData.date_resignation = data.date_resignation || "";
      formData.gross_compensation = data.gross_compensation || "";

      formData.tin_no = data.employee_tin || "";
      formData.rdo_code = data.employee_rdo || "";
      formData.last_name = data.last_name || "";
      formData.first_name = data.first_name || "";
      formData.middle_name = data.middle_name || "";
      formData.date_of_birth = data.date_of_birth || "";
      formData.sex = data.sex || "";
      formData.civil_status = data.civil_status || "";
      formData.residence_address = data.employee_address || "";
      formData.residence_zip = data.employee_zip || "";
      formData.business_address = data.business_address || "";
      formData.business_zip = data.business_zip || "";
      formData.employer_tin = data.company_tin || "";
      formData.employer_rdo = data.company_rdo || "";
      formData.employer_name = data.company_name || "";
      formData.employer_address = data.company_address || "";
      formData.employer_zip = data.company_zip || "";
      formData.spouse_tin = data.spouse_tin || "";
      formData.spouse_last_name = data.spouse_last_name || "";
      formData.spouse_first_name = data.spouse_first_name || "";
      formData.spouse_middle_name = data.spouse_middle_name || "";
      formData.dependent_1_last_name = data.dependent_1_last_name || "";
      formData.dependent_1_first_name = data.dependent_1_first_name || "";
      formData.dependent_1_middle_name = data.dependent_1_middle_name || "";
      formData.dependent_1_birthdate = data.dependent_1_birthdate || "";
      formData.effective_date = data.effective_date || new Date().toISOString().split("T")[0];
      formData.certification_date = data.certification_date || new Date().toISOString().split("T")[0];

      ElMessage.success("Employee details loaded successfully");
      return data;
    } catch (err) {
      error.value = err.response?.data?.message || "Failed to load employee details";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const previewReport = async () => {
    const previewWindow = window.open("", "_blank");
    if (!previewWindow) {
      ElMessage.error("Popup blocked. Please allow popups for this site.");
      return;
    }

    previewWindow.document.write(`
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="utf-8" />
          <title>Generating BIR Form 2305...</title>
          <style>
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
          </style>
        </head>
        <body>
          <div class="loader">
            <div class="spinner"></div>
            <div>Preparing BIR Form 2305, please wait...</div>
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

      const blob = await fillBIRForm2305PDF(formData);

      if (!blob || blob.size === 0) {
        previewWindow.close();
        throw new Error("Generated PDF is empty");
      }

      const url = window.URL.createObjectURL(blob);
      previewWindow.location.href = url;

      ElMessage.success("BIR Form 2305 preview opened");
    } catch (err) {
      previewWindow.close();
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to preview report";
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const generateReport = async () => {
    try {
      loading.value = true;
      error.value = null;

      if (!formData.employee_id) {
        throw new Error("Employee is required");
      }

      const blob = await fillBIRForm2305PDF(formData);

      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty");
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      const empObj = employees.value.find((e) => e.id === formData.employee_id);
      const empName = empObj?.name || `emp-${formData.employee_id || "unknown"}`;
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      link.download = `BIR_Form_2305_${sanitize(empName)}_${new Date()
        .toISOString()
        .split("T")[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      ElMessage.success("BIR Form 2305 downloaded successfully");
    } catch (err) {
      const serverMessage = err.response?.data?.message || err.message;
      error.value = serverMessage || "Failed to generate report";
      ElMessage.error(error.value);
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
    company,
    loadInitialData,
    loadEmployeeDetails,
    previewReport,
    generateReport,
  };
}

