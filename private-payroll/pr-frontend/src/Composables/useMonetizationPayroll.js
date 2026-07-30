import { ref } from 'vue';
import { monetizationPayrollApi } from '../services/api.js';

export function useMonetizationPayroll() {
  const loading = ref(false);
  const payrollList = ref([]);
  const branches = ref([]);
  const employees = ref([]);
  const months = ref([
    { id: 1, name: 'January' },
    { id: 2, name: 'February' },
    { id: 3, name: 'March' },
    { id: 4, name: 'April' },
    { id: 5, name: 'May' },
    { id: 6, name: 'June' },
    { id: 7, name: 'July' },
    { id: 8, name: 'August' },
    { id: 9, name: 'September' },
    { id: 10, name: 'October' },
    { id: 11, name: 'November' },
    { id: 12, name: 'December' },
  ]);

  const formData = ref({
    id: 0,
    branch_id: null,
    month_id: null,
    year_id: new Date().getFullYear(),
    posted: false,
    data: [],
    monetization_employees: [],
  });

  const loadList = async () => {
    loading.value = true;
    try {
      const { data } =
        await monetizationPayrollApi.getMonetizationPayrollList();
      payrollList.value = data?.data || [];
    } finally {
      loading.value = false;
    }
  };

  const loadForm = async (id = 0) => {
    loading.value = true;
    try {
      const { data } =
        await monetizationPayrollApi.getMonetizationPayrollFormData(id);
      const payload = data?.data || {};
      branches.value = payload.branches || [];
      // Ensure we only keep real detail rows (dtl_id present). The backend
      // returns one header row with null detail fields when there are no details.
      const rawRows = payload.data || [];
      const detailRows = rawRows.filter((r) => r?.dtl_id);

      const toBoolean = (value) =>
        value === true || value === 1 || value === '1';

      formData.value = {
        id: rawRows?.[0]?.id || 0,
        branch_id: rawRows?.[0]?.branch_id || null,
        month_id: rawRows?.[0]?.month_id || null,
        year_id: rawRows?.[0]?.year_id || new Date().getFullYear(),
        posted: toBoolean(rawRows?.[0]?.posted),
        data: detailRows,
        monetization_employees: payload.monetization_employees || [],
      };
      employees.value = payload.monetization_employees || [];
    } finally {
      loading.value = false;
    }
  };

  const saveHeader = async (id, payload) => {
    loading.value = true;
    try {
      const { data } = await monetizationPayrollApi.saveMonetizationPayroll(
        id,
        payload
      );
      return data?.data || data;
    } finally {
      loading.value = false;
    }
  };

  const saveEmployees = async (id, payload) => {
    loading.value = true;
    try {
      const { data } =
        await monetizationPayrollApi.addEmployeesToMonetizationPayroll(
          id,
          payload
        );
      return data?.data || data;
    } finally {
      loading.value = false;
    }
  };

  const removeEmployee = async (detailId) => {
    loading.value = true;
    try {
      await monetizationPayrollApi.removeEmployeeFromMonetizationPayroll(
        detailId
      );
    } finally {
      loading.value = false;
    }
  };

  const processPosting = async (id, typeId) => {
    loading.value = true;
    try {
      const { data } = await monetizationPayrollApi.processMonetizationPayroll(
        id,
        typeId
      );
      return data?.data || data;
    } finally {
      loading.value = false;
    }
  };

  const generateReport = async (requestData) => {
    const response =
      await monetizationPayrollApi.generateMonetizationPayrollReport(
        requestData
      );
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'monetization_payroll.pdf';
    link.click();
    window.URL.revokeObjectURL(url);
  };

  const resetFormData = () => {
    formData.value = {
      id: 0,
      branch_id: null,
      month_id: null,
      year_id: new Date().getFullYear(),
      posted: false,
      data: [],
      monetization_employees: [],
    };
  };

  const transformList = (list) =>
    (list || []).map((i) => ({
      id: i.id,
      branch: i.branch,
      branch_id: i.branch_id,
      month: i.month,
      month_id: i.month_id,
      year: i.year_id,
      posted: i.posted === true || i.posted === 1 || i.posted === '1',
    }));

  return {
    // state
    loading,
    payrollList,
    branches,
    employees,
    months,
    formData,

    // actions
    loadList,
    loadForm,
    saveHeader,
    saveEmployees,
    removeEmployee,
    processPosting,
    generateReport,
    resetFormData,

    // helpers
    transformList,
  };
}
