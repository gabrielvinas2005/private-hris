import { ref, reactive, watch } from 'vue';
import { ElMessage } from 'element-plus';
import api from '../services/api';
import { divisionsFromApi } from '../utils/payrollReportDivisions.js';

export function useMidYearIndividualVoucher() {
  const loading = ref(false);
  const error = ref(null);

  // Form data
  const formData = reactive({
    division_id: '',
    employee_id: '',
    years: '',
  });

  // Data arrays
  const divisions = ref([]);
  const years = ref([]);
  const employees = ref([]);
  const allEmployees = ref([]); // Store all employees for filtering
  const employeeOptions = ref([]); // For signatory selection

  // Signatories
  const signatories = reactive({
    certifying_officer_name: '',
    certifying_officer_position: '',
    accountant_name: '',
    accountant_position: '',
    approving_officer_name: '',
    approving_officer_position: '',
  });

  /**
   * Load initial data (divisions, years, and all employees)
   */
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      // Use the same endpoint as ATM Mid-Year Bonus since it has the same data structure
      const response = await api.get('/atm-letter-midyear');

      if (response.data.success) {
        divisions.value = divisionsFromApi(response.data.data) || [];
        years.value = response.data.data.years || [];
        allEmployees.value = response.data.data.employee_options || [];
        employeeOptions.value = response.data.data.employee_options || [];

        // Initially show all employees
        employees.value = allEmployees.value;
      } else {
        throw new Error(response.data.message || 'Failed to load data');
      }
    } catch (err) {
      error.value =
        err.response?.data?.message || err.message || 'Failed to load data';
      ElMessage.error(error.value);
      throw err;
    } finally {
      loading.value = false;
    }
  };

  /**
   * Filter employees based on selected division
   */
  const filterEmployeesByDivision = () => {
    if (!formData.division_id) {
      employees.value = [];
    } else {
      employees.value = allEmployees.value.filter(
        (emp) => String(emp.division_id) === String(formData.division_id),
      );
    }

    // Reset employee selection when division changes
    if (formData.employee_id) {
      const stillExists = employees.value.some(
        (emp) => emp.id === formData.employee_id
      );
      if (!stillExists) {
        formData.employee_id = '';
      }
    }
  };

  // Watch for division changes to filter employees
  watch(
    () => formData.division_id,
    () => {
      filterEmployeesByDivision();
    }
  );

  /**
   * Generate Individual DV Report
   */
  const generateReport = async () => {
    if (!formData.years) {
      ElMessage.warning('Please select Year');
      return;
    }

    if (!formData.employee_id) {
      ElMessage.warning('Please select an Employee');
      return;
    }

    try {
      loading.value = true;
      error.value = null;

      const response = await api.post(
        '/midyear-individual-dv/pdf',
        {
          employee_id: formData.employee_id,
          years: formData.years,
          certifying_officer_name: signatories.certifying_officer_name,
          certifying_officer_position: signatories.certifying_officer_position,
          accountant_name: signatories.accountant_name,
          accountant_position: signatories.accountant_position,
          approving_officer_name: signatories.approving_officer_name,
          approving_officer_position: signatories.approving_officer_position,
        },
        {
          responseType: 'blob',
        }
      );

      // Create download link for PDF
      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `individual_dv_midyear_${formData.employee_id}_${
        formData.years
      }_${new Date().toISOString().split('T')[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);

      ElMessage.success('Individual DV report generated successfully');
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        err.message ||
        'Failed to generate report';
      ElMessage.error(error.value);
    } finally {
      loading.value = false;
    }
  };

  /**
   * Preview report (opens PDF in new tab)
   */
  const previewReport = async () => {
    if (!formData.years) {
      ElMessage.warning('Please select Year');
      return;
    }

    if (!formData.employee_id) {
      ElMessage.warning('Please select an Employee');
      return;
    }

    try {
      loading.value = true;
      error.value = null;

      const response = await api.post(
        '/midyear-individual-dv/pdf',
        {
          employee_id: formData.employee_id,
          years: formData.years,
          certifying_officer_name: signatories.certifying_officer_name,
          certifying_officer_position: signatories.certifying_officer_position,
          accountant_name: signatories.accountant_name,
          accountant_position: signatories.accountant_position,
          approving_officer_name: signatories.approving_officer_name,
          approving_officer_position: signatories.approving_officer_position,
        },
        {
          responseType: 'blob',
        }
      );

      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);

      window.open(url, '_blank');

      setTimeout(() => {
        window.URL.revokeObjectURL(url);
      }, 1000);

      ElMessage.success('Individual DV preview opened');
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        err.message ||
        'Failed to preview report';
      ElMessage.error(error.value);
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    formData,
    divisions,
    years,
    employees,
    employeeOptions,
    signatories,
    loadInitialData,
    generateReport,
    previewReport,
  };
}
