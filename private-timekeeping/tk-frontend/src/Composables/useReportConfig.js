/**
 * Report Configuration Composable
 * Provides standardized configurations for different report types
 */

import { getReportOrganizationName, getReportSystemLabel } from './useCompany.js'

export function useReportConfig() {
  const systemLabel = getReportSystemLabel()
  const orgName = getReportOrganizationName()

  // Base configuration
  const baseConfig = {
    paperSize: 'A4',
    departmentName: 'Department Of Trade and Industry',
    organizationName: orgName,
    printedBy: 'System User',
    showPageNumbers: true,
    zebraStripes: true,
    showRowNumbers: false
  }

  // Report type configurations
  const reportConfigs = {
    // Shift Schedule Reports
    shift_schedule_list: {
      ...baseConfig,
      orientation: 'portrait',
      additionalInfo: `Shift Schedule List Report - ${systemLabel}`,
      title: 'Shift Schedule Report',
      subtitle: 'List of all shift schedules'
    },
    
    shift_schedule_viewer: {
      ...baseConfig,
      orientation: 'landscape',
      additionalInfo: `Shift Schedule Viewer Report - ${systemLabel}`,
      title: 'Shift Schedule Viewer Report',
      subtitle: 'Detailed shift schedule information'
    },
    
    shift_schedule_employee_details: {
      ...baseConfig,
      orientation: 'portrait',
      additionalInfo: `Assigned Employees Report - ${systemLabel}`,
      title: 'Assigned Employees Report',
      subtitle: 'Employees assigned to shift schedule'
    },

    // Attendance Reports
    attendance_report: {
      ...baseConfig,
      orientation: 'landscape',
      additionalInfo: `Attendance Report - ${systemLabel}`,
      title: 'Attendance Report',
      subtitle: 'Employee attendance records'
    },

    // Timekeeping Reports
    timekeeping_summary: {
      ...baseConfig,
      orientation: 'portrait',
      additionalInfo: `Timekeeping Summary Report - ${systemLabel}`,
      title: 'Timekeeping Summary Report',
      subtitle: 'Summary of timekeeping data'
    },

    // Employee Reports
    employee_list: {
      ...baseConfig,
      orientation: 'portrait',
      additionalInfo: `Employee List Report - ${systemLabel}`,
      title: 'Employee List Report',
      subtitle: 'List of all employees'
    },

    // Generic Report
    generic: {
      ...baseConfig,
      orientation: 'portrait',
      additionalInfo: `Report - ${systemLabel}`,
      title: 'Report',
      subtitle: ''
    }
  }

  /**
   * Get configuration for a specific report type
   */
  const getReportConfig = (reportType, customConfig = {}) => {
    const config = reportConfigs[reportType] || reportConfigs.generic
    return {
      ...config,
      organizationName: getReportOrganizationName(),
      ...customConfig
    }
  }

  /**
   * Create custom report configuration
   */
  const createCustomConfig = (config = {}) => {
    return { ...baseConfig, ...config }
  }

  /**
   * Get all available report types
   */
  const getAvailableReportTypes = () => {
    return Object.keys(reportConfigs)
  }

  /**
   * Validate report configuration
   */
  const validateConfig = (config) => {
    const required = ['paperSize', 'orientation', 'title']
    const missing = required.filter(key => !config[key])
    
    if (missing.length > 0) {
      throw new Error(`Missing required configuration: ${missing.join(', ')}`)
    }
    
    return true
  }

  return {
    baseConfig,
    reportConfigs,
    getReportConfig,
    createCustomConfig,
    getAvailableReportTypes,
    validateConfig
  }
}

export default useReportConfig
