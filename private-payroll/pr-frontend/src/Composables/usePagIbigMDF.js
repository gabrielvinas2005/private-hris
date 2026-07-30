import { ref, reactive } from "vue";
import { pagIbigMDFApi } from "../services/api.js";
import { ElMessage } from "element-plus";
import { fillPagIbigMDFPDF } from "../utils/pdffiller.js";

export function usePagIbigMDF() {
  const loading = ref(false);
  const error = ref(null);

  const formData = reactive({
    employee_id: "",
    last_name: "",
    first_name: "",
    middle_name: "",
    name_extension: "",
    date_of_birth: "",
    place_of_birth: "",
    sex: "",
    civil_status: "",
    citizenship: "",
    tin_no: "",
    sss_no: "",
    gsis_no: "",
    employee_no: "",
    height: "",
    weight: "",
    mobile_no: "",
    email: "",
    permanent_address_unit_room_floor: "",
    permanent_address_building_name: "",
    permanent_address_lot_block_phase_house: "",
    permanent_address_street_name: "",
    permanent_address_subdivision: "",
    permanent_address_barangay: "",
    permanent_address_municipality_city: "",
    permanent_address_province_state_country: "",
    permanent_address_zip: "",
    permanent_address: "",
    present_address_unit_room_floor: "",
    present_address_building_name: "",
    present_address_lot_block_phase_house: "",
    present_address_street_name: "",
    present_address_subdivision: "",
    present_address_barangay: "",
    present_address_municipality_city: "",
    present_address_province_state_country: "",
    present_address_zip: "",
    present_address: "",
    present_zip: "",
    employer_name: "",
    employer_address: "",
    employer_zip: "",
    monthly_compensation: "",
    date_employed: "",
    father_last_name: "",
    father_first_name: "",
    father_middle_name: "",
    mother_last_name: "",
    mother_first_name: "",
    mother_middle_name: "",
    spouse_last_name: "",
    spouse_first_name: "",
    spouse_middle_name: "",
    spouse_tin: "",
    dependent_1_last_name: "",
    dependent_1_first_name: "",
    dependent_1_middle_name: "",
    dependent_1_birthdate: "",
    informant_signature: "",
    processed_by_name: "",
    processed_by_position: "",
    processed_by_branch_unit: "",
  });

  const employees = ref([]);

  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;
      const res = await pagIbigMDFApi.getPagIbigMDFData();
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

  const loadEmployeeDetails = async (employeeId) => {
    if (!employeeId) {
      Object.keys(formData).forEach((key) => {
        if (key !== "employee_id") formData[key] = "";
      });
      formData.informant_signature = "";
      formData.processed_by_name = "";
      formData.processed_by_position = "";
      formData.processed_by_branch_unit = "";
      return;
    }

    try {
      loading.value = true;
      error.value = null;
      const res = await pagIbigMDFApi.getEmployeeDetails(employeeId);
      const data = res.data.data || {};

      Object.assign(formData, {
        employee_id: employeeId,
        last_name: data.last_name || "",
        first_name: data.first_name || "",
        middle_name: data.middle_name || "",
        name_extension: data.name_extension || "",
        date_of_birth: data.date_of_birth || "",
        place_of_birth: data.place_of_birth || "",
        sex: data.sex || "",
        civil_status: data.civil_status || "",
        citizenship: data.citizenship || "",
        tin_no: data.tin_no || "",
        sss_no: data.sss_no || "",
        gsis_no: data.gsis_no || "",
        employee_no: data.employee_no || "",
        height: data.height || "",
        weight: data.weight || "",
        mobile_no: data.mobile_no || "",
        email: data.email || "",
        permanent_address_unit_room_floor:
          data.permanent_address_unit_room_floor || "",
        permanent_address_building_name:
          data.permanent_address_building_name || "",
        permanent_address_lot_block_phase_house:
          data.permanent_address_lot_block_phase_house || "",
        permanent_address_street_name: data.permanent_address_street_name || "",
        permanent_address_subdivision: data.permanent_address_subdivision || "",
        permanent_address_barangay: data.permanent_address_barangay || "",
        permanent_address_municipality_city:
          data.permanent_address_municipality_city || "",
        permanent_address_province_state_country:
          data.permanent_address_province_state_country || "",
        permanent_address_zip:
          data.permanent_address_zip || data.permanent_zip || "",
        permanent_address: data.permanent_address || "",
        permanent_zip: data.permanent_zip || "",
        present_address_unit_room_floor:
          data.present_address_unit_room_floor || "",
        present_address_building_name: data.present_address_building_name || "",
        present_address_lot_block_phase_house:
          data.present_address_lot_block_phase_house || "",
        present_address_street_name: data.present_address_street_name || "",
        present_address_subdivision: data.present_address_subdivision || "",
        present_address_barangay: data.present_address_barangay || "",
        present_address_municipality_city:
          data.present_address_municipality_city || "",
        present_address_province_state_country:
          data.present_address_province_state_country || "",
        present_address_zip: data.present_address_zip || data.present_zip || "",
        present_address: data.present_address || "",
        present_zip: data.present_zip || "",
        employer_name: data.employer_name || "",
        employer_address: data.employer_address || "",
        employer_zip: data.employer_zip || "",
        monthly_compensation: data.monthly_compensation || "",
        date_employed: data.date_employed || "",
        father_last_name: data.father_last_name || "",
        father_first_name: data.father_first_name || "",
        father_middle_name: data.father_middle_name || "",
        mother_last_name: data.mother_last_name || "",
        mother_first_name: data.mother_first_name || "",
        mother_middle_name: data.mother_middle_name || "",
        spouse_last_name: data.spouse_last_name || "",
        spouse_first_name: data.spouse_first_name || "",
        spouse_middle_name: data.spouse_middle_name || "",
        spouse_tin: data.spouse_tin || "",
        dependent_1_last_name: data.dependent_1_last_name || "",
        dependent_1_first_name: data.dependent_1_first_name || "",
        dependent_1_middle_name: data.dependent_1_middle_name || "",
        dependent_1_birthdate: data.dependent_1_birthdate || "",
      });

      // Auto-fill informant signature with employee's full name
      const fullName = [
        data.first_name || "",
        data.middle_name || "",
        data.last_name || "",
      ]
        .filter(Boolean)
        .join(" ")
        .trim();
      formData.informant_signature = fullName || "";

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
          <title>Generating Pag-IBIG MDF...</title>
          <style>
            body { margin: 0; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: Arial, sans-serif; background: #f5f5f5; }
            .loader { display: flex; flex-direction: column; align-items: center; gap: 12px; }
            .spinner { width: 40px; height: 40px; border-radius: 50%; border: 4px solid #ccc; border-top-color: #0d6efd; animation: spin 0.8s linear infinite; }
            @keyframes spin { to { transform: rotate(360deg); } }
          </style>
        </head>
        <body>
          <div class="loader">
            <div class="spinner"></div>
            <div>Preparing Pag-IBIG MDF, please wait...</div>
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

      const blob = await fillPagIbigMDFPDF(formData);

      if (!blob || blob.size === 0) {
        previewWindow.close();
        throw new Error("Generated PDF is empty");
      }

      const url = window.URL.createObjectURL(blob);
      previewWindow.location.href = url;

      ElMessage.success("Pag-IBIG MDF preview opened");
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

      const blob = await fillPagIbigMDFPDF(formData);

      if (!blob || blob.size === 0) {
        throw new Error("Generated PDF is empty");
      }

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      const empObj = employees.value.find((e) => e.id === formData.employee_id);
      const empName =
        empObj?.name || `emp-${formData.employee_id || "unknown"}`;
      const sanitize = (s) => String(s).replace(/[^A-Za-z0-9_\-()]+/g, "");
      link.download = `PagIbig_MDF_${sanitize(empName)}_${
        new Date().toISOString().split("T")[0]
      }.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      ElMessage.success("Pag-IBIG MDF downloaded successfully");
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
    loadInitialData,
    loadEmployeeDetails,
    previewReport,
    generateReport,
  };
}
