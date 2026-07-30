// Usage: in services/api.js -> export const processAttendanceService = useProcessAttendance(api)
// Updated: Added reprocessAll method

import { API_BASE_URL } from '../config/api'

export function useProcessAttendance(api) {
    const base = '/process-attendance'

    const index = async () => {
        const res = await api.get(base)
        return res?.data ?? res
    }

    const getPayrollPeriods = async (intervalId, type = null) => {
        const url = type
            ? `${base}/periods/${intervalId}?type=${encodeURIComponent(type)}`
            : `${base}/periods/${intervalId}`
        const res = await api.get(url)
        return res?.data ?? res
    }

    const checkPayrollPeriodStatus = async (payrollPeriodId) => {
        const res = await api.get(`${base}/period-status/${payrollPeriodId}`)
        return res?.data ?? res
    }

    const getEmployeeAttendanceData = async (payrollPeriodId, params = {}) => {
        const res = await api.get(`${base}/employees/${payrollPeriodId}`, { params })
        return res?.data ?? res
    }

    const getDaysPresentSummary = async (payrollPeriodId) => {
        const res = await api.get(`${base}/days-present/${payrollPeriodId}`)
        return res?.data ?? res
    }

    const getLeaveReversalPreview = async (payrollPeriodId, params = {}) => {
        const res = await api.get(`${base}/leave-reversal-preview/${payrollPeriodId}`, { params })
        return res?.data ?? res
    }


    const process = async (payload) => {
        // payload: { payroll_interval_id, payroll_period_id }
        const res = await api.post(base, payload)
        return res?.data ?? res
    }

    const syncTimeDataPayrollPeriod = async (payload) => {
        const requestPayload = typeof payload === 'object'
            ? payload
            : { payroll_period_id: payload }
        const res = await api.post(`${base}/sync-time-data`, requestPayload)
        return res?.data ?? res
    }

    const view = async (id, payrollPeriodId) => {
        const res = await api.get(`${base}/${id}/${payrollPeriodId}`)
        return res?.data ?? res
    }

    const reprocess = async (id, payrollPeriodId, payload = {}) => {
        const res = await api.post(`${base}/${id}/${payrollPeriodId}/reprocess`, payload)
        return res?.data ?? res
    }

    const reprocessAll = async (payload) => {
        // payload: { payroll_period_id } or just payrollPeriodId (number)
        // Use explicit POST (fetch) so the route always receives POST and never GET (avoids MethodNotAllowedHttpException)
        const requestPayload = typeof payload === 'object' ? payload : { payroll_period_id: payload }
        const url = `${API_BASE_URL}${base}/reprocess-all`
        const token = (() => {
            try {
                const raw = localStorage.getItem('auth_token') || localStorage.getItem('dev_auth_token')
                if (!raw) return null
                const parsed = JSON.parse(raw)
                return parsed?.token || raw
            } catch (_) {
                return null
            }
        })()
        const headers = {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        }
        const response = await fetch(url, {
            method: 'POST',
            headers,
            credentials: 'include',
            body: JSON.stringify(requestPayload),
        })
        const data = await response.json().catch(() => ({}))
        if (!response.ok) {
            const err = new Error(data?.message || response.statusText || 'Request failed')
            err.response = { status: response.status, data }
            err.status = response.status
            throw err
        }
        return data?.data ?? data
    }

    const cancelProcess = async (payload) => {
        // payload: { process_run_id }
        const res = await api.post(`${base}/cancel`, payload)
        return res?.data ?? res
    }

    const getLatestProcessProgress = async (params = {}) => {
        const usp = new URLSearchParams()
        if (params?.payroll_period_id != null) usp.append('payroll_period_id', String(params.payroll_period_id))
        if (params?.process_run_id) usp.append('process_run_id', String(params.process_run_id))
        const qs = usp.toString()
        const url = `${API_BASE_URL}${base}/progress-latest${qs ? `?${qs}` : ''}`
        const token = (() => {
            try {
                const raw = localStorage.getItem('auth_token') || localStorage.getItem('dev_auth_token')
                if (!raw) return null
                const parsed = JSON.parse(raw)
                return parsed?.token || raw
            } catch (_) {
                return null
            }
        })()
        const headers = {
            Accept: 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        }
        const response = await fetch(url, {
            method: 'GET',
            headers,
            credentials: 'omit',
        })
        const data = await response.json().catch(() => ({}))
        if (!response.ok) {
            const err = new Error(data?.message || response.statusText || 'Failed to fetch latest process progress')
            err.response = { status: response.status, data }
            err.status = response.status
            throw err
        }
        return data?.data ?? data
    }

    const report = async (employeeId, payrollPeriodId) => {
        // returns PDF blob
        const path = `${base}/${employeeId}/${payrollPeriodId}/report`
        const response = await fetch(`${API_BASE_URL}${path}`, {
            method: 'GET',
            credentials: 'include'
        })
        if (!response.ok) throw new Error('Failed to download report')
        const blob = await response.blob()
        return blob
    }

    const viewDTR = async (employeeId, payrollPeriodId) => {
        // returns PDF blob for DTR
        const path = `${base}/${employeeId}/${payrollPeriodId}/dtr`
        const response = await fetch(`${API_BASE_URL}${path}`, {
            method: 'GET',
            credentials: 'include'
        })
        if (!response.ok) throw new Error('Failed to load DTR')
        const blob = await response.blob()
        return blob
    }

    const getDTRData = async (employeeId, payrollPeriodId) => {
        // returns DTR data for template-based preview
        const res = await api.get(`${base}/${employeeId}/${payrollPeriodId}/dtr/data`)
        return res?.data ?? res
    }

    const offset = async (payload) => {
        const res = await api.post(`${base}/offset`, payload)
        return res?.data ?? res
    }

    const cancelOffset = async (id, payrollPeriodId, { is_adj = false } = {}) => {
        const res = await api.post(`${base}/${id}/${payrollPeriodId}/cancel-offset`, { is_adj: !!is_adj })
        return res?.data ?? res
    }

    const cancelOffsetAll = async (payrollPeriodId) => {
        const res = await api.post(`${base}/cancel-offset-all`, { payroll_period_id: payrollPeriodId })
        return res?.data ?? res
    }

    const getOffsetEmployeeSummary = async (payrollPeriodId) => {
        const res = await api.get(`${base}/offset-employee-summary/${payrollPeriodId}`)
        return res?.data ?? res
    }

    const offsetDetails = async (id, payrollPeriodId, payload) => {
        const res = await api.post(`${base}/${id}/${payrollPeriodId}/offset-details`, payload)
        return res?.data ?? res
    }

    const cancelOffsetDetails = async (id, payrollPeriodId) => {
        const res = await api.post(`${base}/${id}/${payrollPeriodId}/cancel-offset-details`, {})
        return res?.data ?? res
    }

    const save = async (payload) => {
        // payload: { payroll_period_id, employee_id, daily_rate, days_covered, total_late, late_amount, total_undertime, undertime_amount, total_absent, absent_amount, total_amount }
        const res = await api.post(`${base}/save`, payload)
        return res?.data ?? res
    }

    const editTimes = async (payload) => {
        // payload: { payroll_period_id, employee_id, records: [{ id?, date, am_in?, am_out?, break_in?, break_out?, pm_in?, pm_out? }] }
        const res = await api.post(`${base}/edit-times`, payload)
        return res?.data ?? res
    }

    const getOffsetData = async (payrollPeriodId) => {
        // Get all employees with absences, late, or undertime for offset
        const res = await api.get(`${base}/offset-data/${payrollPeriodId}`)
        return res?.data ?? res
    }

    const getEmployeeOffsetDetails = async (employeeId, payrollPeriodId) => {
        // Get daily offset details for a specific employee
        const res = await api.get(`${base}/offset-details/${employeeId}/${payrollPeriodId}`)
        return res?.data ?? res
    }

    const applyOffset = async (payload) => {
        // payload: { employee_id, payroll_period_id, time_data_ids: [...] }
        const res = await api.post(`${base}/apply-offset`, payload)
        return res?.data ?? res
    }

    const applyOffsetAll = async (payrollPeriodId) => {
        const res = await api.post(`${base}/apply-offset-all`, { payroll_period_id: payrollPeriodId })
        return res?.data ?? res
    }

    const calculateOffsetTotalDays = async (payload) => {
        // payload: { late, undertime, absent }
        const res = await api.post(`${base}/calculate-offset-total`, payload)
        return res?.data ?? res
    }

    return {
        index,
        getPayrollPeriods,
        checkPayrollPeriodStatus,
        getEmployeeAttendanceData,
        getDaysPresentSummary,
        getLeaveReversalPreview,
        process,
        view,
        reprocess,
        reprocessAll,
        cancelProcess,
        getLatestProcessProgress,
        report,
        viewDTR,
        getDTRData,
        offset,
        cancelOffset,
        cancelOffsetAll,
        getOffsetEmployeeSummary,
        offsetDetails,
        cancelOffsetDetails,
        save,
        editTimes,
        getOffsetData,
        getEmployeeOffsetDetails,
        applyOffset,
        applyOffsetAll,
        calculateOffsetTotalDays,
        syncTimeDataPayrollPeriod
    }
}


