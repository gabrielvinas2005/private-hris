import { ref, reactive } from 'vue';
import { ElMessage } from 'element-plus';
import api from '../services/api';
import {
  divisionsFromApi,
  divisionParams,
  uniquePayPeriods,
} from '../utils/payrollReportDivisions.js';
import { formatApiError } from '../utils/apiErrorMessage.js';

export function useLandbankTextReport() {
  const loading = ref(false);
  const error = ref(null);

  // Form data
  const formData = reactive({
    division_id: '',
    payroll_period_id: '',
  });

  // Data arrays
  const divisions = ref([]);
  const payrollPeriods = ref([]);
  const previewData = ref([]);
  const previewSummary = ref(null);
  const previewLoading = ref(false);

  /**
   * Load initial data
   */
  const loadInitialData = async () => {
    try {
      loading.value = true;
      error.value = null;

      const response = await api.get('/landbank-text-report');

      if (response.data.success) {
        divisions.value = divisionsFromApi(response.data.data);
        payrollPeriods.value = uniquePayPeriods(response.data.data.payrolls);
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

  const buildRequestParams = (params) => ({
    payroll_period_id: params?.payroll_period_id ?? formData.payroll_period_id,
    ...divisionParams({
      division_id: params?.division_id ?? formData.division_id,
      department_id: params?.division_id ?? formData.division_id,
    }),
  });

  /**
   * Preview report data (returns structured data for table display)
   */
  const previewReport = async (params) => {
    try {
      previewLoading.value = true;
      error.value = null;

      const response = await api.post(
        '/landbank-text-report/preview',
        buildRequestParams(params),
      );

      if (response.data.success) {
        previewData.value = response.data.data.data;
        previewSummary.value = response.data.data.summary;
        return response.data.data;
      } else {
        throw new Error(response.data.message || 'Failed to preview report');
      }
    } catch (err) {
      error.value = formatApiError(err, 'Failed to preview report');
      ElMessage.error(error.value);
      previewData.value = [];
      previewSummary.value = null;
      throw err;
    } finally {
      previewLoading.value = false;
    }
  };

  /**
   * Generate and download report
   */
  const generateReport = async (params) => {
    try {
      loading.value = true;
      error.value = null;

      const response = await api.post(
        '/landbank-text-report/export',
        buildRequestParams(params),
      );

      if (response.data.success) {
        const { file_content, filename, content_type } = response.data.data;

        // Create download link
        const link = document.createElement('a');
        link.href = `data:${content_type};base64,${file_content}`;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        ElMessage.success('Landbank text report downloaded successfully');
        return response.data.data;
      } else {
        throw new Error(response.data.message || 'Failed to generate report');
      }
    } catch (err) {
      error.value = formatApiError(err, 'Failed to generate report');
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
    divisions,
    payrollPeriods,
    previewData,
    previewSummary,
    previewLoading,
    loadInitialData,
    previewReport,
    generateReport,
  };
}
