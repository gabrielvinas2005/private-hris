<template>
  <MainLayout>
    <!-- Welcome Section -->
    <div class="mb-6">
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-6 text-white shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold mb-1">Welcome, {{ userData.name }}</h1>
            <p class="text-blue-100">{{ companyPortalSubtitle }}</p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="bg-white/20 rounded-lg p-3">
              <p class="text-xs text-blue-100 mb-1">Current Date</p>
              <p class="text-sm font-semibold">{{ currentDate }}</p>
            </div>
            <div class="bg-white/20 rounded-lg p-3">
              <p class="text-xs text-blue-100 mb-1">Current Time</p>
              <p class="text-sm font-semibold">{{ currentTime }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notifications banner (pending approvals / expirations / announcements count) -->
    <div v-if="hasNotifications" class="mb-6">
      <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3">
        <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
          </svg>
        </div>
        <div class="flex-1 text-sm">
          <p class="font-medium text-amber-900 mb-1">You have updates waiting</p>
          <ul class="text-amber-800 space-y-0.5">
            <li v-if="dashboardData.pending_requests?.total">
              {{ dashboardData.pending_requests.total }} transaction(s) pending approval
            </li>
            <li v-if="contractExpiringSoon">
              Your contract/probation period ends {{ formatDate(contractExpiringSoon) }}
            </li>
            <li v-if="recentlyResolvedCount">
              {{ recentlyResolvedCount }} transaction(s) recently approved or returned — check Quick Actions below
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Administrative Access Section (Only if user has at least one admin module) -->
    <div v-if="hasAnyAdminAccess()" class="mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Administrative Access</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- HR Module Card -->
        <div
          v-if="hasHrmAccess"
          @click="navigateToHRModule()"
          class="group bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md hover:border-purple-300 transition-all duration-200 cursor-pointer"
        >
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors duration-200">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900">201 Files</h3>
              <p class="text-gray-600 text-sm">HR Module - 201 Files</p>
            </div>
          </div>
        </div>

        <!-- Control Panel Card -->
        <div
          v-if="hasCpmAccess"
          @click="navigateToControlPanel()"
          class="group bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md hover:border-red-300 transition-all duration-200 cursor-pointer"
        >
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center group-hover:bg-red-200 transition-colors duration-200">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900">Control Panel</h3>
              <p class="text-gray-600 text-sm">System Administration</p>
            </div>
          </div>
        </div>

        <!-- Payroll Module Card -->
        <div
          v-if="hasHrpAccess"
          @click="navigateToPayrollModule()"
          class="group bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md hover:border-yellow-300 transition-all duration-200 cursor-pointer"
        >
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center group-hover:bg-yellow-200 transition-colors duration-200">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900">Payroll Module</h3>
              <p class="text-gray-600 text-sm">Payroll Management</p>
            </div>
          </div>
        </div>

        <!-- Timekeeping Module Card -->
        <div
          v-if="hasHrtAccess"
          @click="navigateToTimekeeping()"
          class="group bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md hover:border-green-300 transition-all duration-200 cursor-pointer"
        >
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors duration-200">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900">Timekeeping</h3>
              <p class="text-gray-600 text-sm">Time & Attendance</p>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Role-based Widgets: Payslip, Leave Balance, Pending Approvals -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

      <!-- Payslip Summary Widget -->
      <div
        v-if="canViewPayslip"
        class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm cursor-pointer hover:shadow-md transition-shadow duration-200"
        @click="navigateToModule('payslip')"
      >
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-gray-900 text-sm">Payslip Summary</h3>
          <div class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z"></path>
            </svg>
          </div>
        </div>
        <div v-if="payslipSummary">
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(payslipSummary.net_pay) }}</p>
          <p class="text-xs text-gray-500 mt-1">Net pay — {{ payslipSummary.period_label }}</p>
        </div>
        <p v-else class="text-sm text-gray-500">No payslip data available yet</p>
      </div>

      <!-- Leave Balance Widget -->
      <div
        v-if="canViewLeave"
        class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm cursor-pointer hover:shadow-md transition-shadow duration-200"
        @click="navigateToModule('leave-time')"
      >
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-gray-900 text-sm">Leave Balance</h3>
          <div class="w-8 h-8 bg-green-100 rounded flex items-center justify-center">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ dashboardData.leave_balance?.total_balance ?? 0 }}</p>
        <p class="text-xs text-gray-500 mt-1">Total days available</p>
        <ul v-if="dashboardData.leave_balance?.leave_types?.length" class="mt-2 space-y-1">
          <li
            v-for="lt in dashboardData.leave_balance.leave_types.slice(0, 3)"
            :key="lt.name"
            class="flex items-center justify-between text-xs text-gray-600"
          >
            <span>{{ lt.name }}</span>
            <span class="font-medium text-gray-900">{{ lt.balance }}</span>
          </li>
        </ul>
      </div>

      <!-- Pending Approvals Widget -->
      <div
        v-if="canViewLeave || canViewOvertime || canViewDocumentRequest"
        class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm"
      >
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-gray-900 text-sm">Pending Approvals</h3>
          <div class="w-8 h-8 bg-amber-100 rounded flex items-center justify-center">
            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ dashboardData.pending_requests?.total ?? 0 }}</p>
        <p class="text-xs text-gray-500 mt-1">Awaiting action across all modules</p>
        <ul class="mt-2 space-y-1 text-xs text-gray-600">
          <li v-if="dashboardData.pending_requests?.leaves" class="flex items-center justify-between">
            <span>Leave</span>
            <span class="font-medium text-gray-900">{{ dashboardData.pending_requests.leaves }}</span>
          </li>
          <li v-if="dashboardData.pending_requests?.overtime" class="flex items-center justify-between">
            <span>Overtime</span>
            <span class="font-medium text-gray-900">{{ dashboardData.pending_requests.overtime }}</span>
          </li>
          <li v-if="dashboardData.pending_requests?.official_business" class="flex items-center justify-between">
            <span>Travel / OB</span>
            <span class="font-medium text-gray-900">{{ dashboardData.pending_requests.official_business }}</span>
          </li>
          <li v-if="dashboardData.pending_requests?.document_requests" class="flex items-center justify-between">
            <span>Document Requests</span>
            <span class="font-medium text-gray-900">{{ dashboardData.pending_requests.document_requests }}</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Information Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Quick Actions Panel (only if user has at least one allowed shortcut) -->
      <div
        v-if="menuAccessLoaded && hasAnyQuickAction"
        class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm"
      >
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-gray-900">Quick Actions</h3>
          <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
          </div>
        </div>
        <div class="space-y-2">
          <div
            v-if="canViewLeave"
            @click="navigateToModule('leave-time')"
            class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors duration-200 cursor-pointer group"
          >
            <div class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center group-hover:bg-blue-200 transition-colors duration-200">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19a7 7 0 01-7-7v-3a4 4 0 014-4h6a4 4 0 014 4v3a7 7 0 01-7 7z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-gray-900 font-medium text-sm">Submit Leave Request</p>
              <p class="text-gray-600 text-xs">Apply for time off</p>
            </div>
          </div>

          <div
            v-if="canViewOvertime"
            @click="navigateToModule('overtime-scheduling')"
            class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-green-50 transition-colors duration-200 cursor-pointer group"
          >
            <div class="w-8 h-8 bg-green-100 rounded flex items-center justify-center group-hover:bg-green-200 transition-colors duration-200">
              <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-gray-900 font-medium text-sm">Overtime Application</p>
              <p class="text-gray-600 text-xs">Request additional hours</p>
            </div>
          </div>

          <div
            v-if="canViewTravelOrder"
            @click="navigateToModule('travel-order')"
            class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-orange-50 transition-colors duration-200 cursor-pointer group"
          >
            <div class="w-8 h-8 bg-orange-100 rounded flex items-center justify-center group-hover:bg-orange-200 transition-colors duration-200">
              <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-gray-900 font-medium text-sm">Travel Order / Official Business</p>
              <p class="text-gray-600 text-xs">Request travel authorization</p>
            </div>
          </div>

          <div
            v-if="canViewWfh"
            @click="navigateToModule('wfh-application')"
            class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-teal-50 transition-colors duration-200 cursor-pointer group"
          >
            <div class="w-8 h-8 bg-teal-100 rounded flex items-center justify-center group-hover:bg-teal-200 transition-colors duration-200">
              <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v6a1 1 0 001 1h3m6-7l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-gray-900 font-medium text-sm">Work-From-Home Request</p>
              <p class="text-gray-600 text-xs">File a remote-work application</p>
            </div>
          </div>

          <div
            v-if="canViewDocumentRequest"
            @click="navigateToModule('document-requests')"
            class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-pink-50 transition-colors duration-200 cursor-pointer group"
          >
            <div class="w-8 h-8 bg-pink-100 rounded flex items-center justify-center group-hover:bg-pink-200 transition-colors duration-200">
              <svg class="w-4 h-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-gray-900 font-medium text-sm">Request a Document</p>
              <p class="text-gray-600 text-xs">Certificates, clearances, and more</p>
            </div>
          </div>

          <div
            @click="navigateTo201File()"
            v-if="canViewEmployeeRecord"
            class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg hover:bg-purple-50 transition-colors duration-200 cursor-pointer group"
          >
            <div class="w-8 h-8 bg-purple-100 rounded flex items-center justify-center group-hover:bg-purple-200 transition-colors duration-200">
              <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <p class="text-gray-900 font-medium text-sm">Update Profile</p>
              <p class="text-gray-600 text-xs">Edit personal information</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Company Announcements Feed -->
      <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-gray-900">Announcements</h3>
          <div class="w-6 h-6 bg-blue-100 rounded flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
            </svg>
          </div>
        </div>
        <AnnouncementList v-if="announcements.length" :announcements="announcements" />
        <p v-else class="text-sm text-gray-500">No announcements right now</p>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../layout/MainLayout.vue'
import AnnouncementList from '../components/Announcement/AnnouncementList.vue'
import { fetchCompanyPublic } from '../services/companyPublic.js'

export default {
  name: 'Dashboard',
  components: { MainLayout, AnnouncementList },
  data() {
    return {
      company: {
        name: '',
        address: '',
        email: '',
        telephone_no: '',
        mobile_no: '',
        logo_data_url: null
      },
      userData: {
        id: null,
        name: '',
        email: ''
      },
      menuAccessLoaded: false,
      canViewEmployeeRecord: false,
      canViewLeave: false,
      canViewOvertime: false,
      canViewPayslip: false,
      canViewDtr: false,
      canViewSaln: false,
      canViewTravelOrder: false,
      canViewWfh: false,
      canViewDocumentRequest: false,
      currentDate: '',
      currentTime: '',
      dashboardData: {
        leave_balance: { total_balance: 0, leave_types: [] },
        pending_requests: { total: 0, leaves: 0, overtime: 0, official_business: 0, document_requests: 0 },
        work_hours: { hours_today: 0, status: 'Hours today' },
        overtime_hours: 0,
        recent_activity: [],
        // TODO: backend getDashboardData() needs to return these two for the
        // payslip widget and the "recently resolved" notification line to
        // have real data instead of falling back to defaults/hidden state.
        payslip_summary: null,
        contract_expiry: null,
        recently_resolved_count: 0
      },
      announcements: [],
      loading: false,
      hasHrmAccess: false,
      hasHrpAccess: false,
      hasHrtAccess: false,
      hasCpmAccess: false
    }
  },
  computed: {
    companyPortalSubtitle() {
      return this.company.name
        ? `${this.company.name} — Employee Portal`
        : 'Employee Portal'
    },
    hasAnyQuickAction() {
      return (
        this.canViewLeave ||
        this.canViewOvertime ||
        this.canViewTravelOrder ||
        this.canViewWfh ||
        this.canViewDocumentRequest ||
        this.canViewEmployeeRecord
      )
    },
    payslipSummary() {
      return this.dashboardData?.payslip_summary || null
    },
    contractExpiringSoon() {
      // Only surface this if the expiry is within the next 30 days.
      const expiry = this.dashboardData?.contract_expiry
      if (!expiry) return null
      const days = (new Date(expiry) - new Date()) / (1000 * 60 * 60 * 24)
      return days >= 0 && days <= 30 ? expiry : null
    },
    recentlyResolvedCount() {
      return this.dashboardData?.recently_resolved_count || 0
    },
    hasNotifications() {
      return Boolean(
        (this.dashboardData.pending_requests?.total || 0) > 0 ||
        this.contractExpiringSoon ||
        this.recentlyResolvedCount > 0
      )
    }
  },
  mounted() {
    fetchCompanyPublic().then((data) => {
      this.company = data
    })

    // Get user data from localStorage
    const storedUserData = localStorage.getItem('user_data')
    if (storedUserData) {
      this.userData = JSON.parse(storedUserData)
      const isAdmin = this.toBool(this.userData?.is_admin)
      this.hasHrmAccess = this.toBool(this.userData?.with_hrm_access) || isAdmin
      this.hasHrpAccess = this.toBool(this.userData?.with_hrp_access) || isAdmin
      this.hasHrtAccess = this.toBool(this.userData?.with_hrt_access) || isAdmin
      this.hasCpmAccess = this.toBool(this.userData?.with_cpm_access) || isAdmin
      this.loadMenuAccess()
      // Load dashboard data after getting user data
      this.loadDashboardData()
      this.loadAnnouncements()
    }

    // Set current date and time
    this.updateDateTime()
    setInterval(this.updateDateTime, 1000)

    // Poll for updated pending-approval / notification state every 60s.
    // This is a simple polling fallback — for true real-time push, wire this
    // dashboard up to your websocket/Pusher broadcast channel instead and
    // call loadDashboardData() (or a lighter notifications-only endpoint)
    // whenever a broadcast event fires.
    this._notificationPoll = setInterval(() => {
      if (this.userData?.id) this.loadDashboardData()
    }, 60000)
  },
  beforeUnmount() {
    if (this._notificationPoll) clearInterval(this._notificationPoll)
  },
  methods: {
    hasAnyAdminAccess() {
      return this.hasHrmAccess || this.hasHrpAccess || this.hasHrtAccess || this.hasCpmAccess
    },

    toBool(val) {
      if (typeof val === 'string') {
        const v = val.trim().toLowerCase()
        return v === '1' || v === 'true' || v === 'yes' || v === 'y'
      }
      return !!val
    },

    formatCurrency(value) {
      const num = Number(value || 0)
      return num.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
    },

    async loadMenuAccess() {
      this.menuAccessLoaded = false
      this.canViewEmployeeRecord = false
      this.canViewLeave = false
      this.canViewOvertime = false
      this.canViewPayslip = false
      this.canViewDtr = false
      this.canViewSaln = false
      this.canViewTravelOrder = false
      this.canViewWfh = false
      this.canViewDocumentRequest = false
      try {
        const ApiService = (await import('../services/api.js')).default
        await ApiService.initSanctum()
        const res = await ApiService.getUserTabAccess()
        const menus = res?.data?.menus
        if (Array.isArray(menus)) {
          // Same normalization as MainLayout.vue `hasMenuAccess` (tab / submodule access)
          const normalize = (s) =>
            String(s ?? '')
              .trim()
              .toLowerCase()
              .replace(/\s+/g, ' ')
          const allowed = new Set()
          for (const m of menus) {
            if (m?.menu_key) allowed.add(normalize(m.menu_key))
            if (m?.menu) allowed.add(normalize(m.menu))
          }
          const has = (label) => allowed.has(normalize(label))

          this.canViewEmployeeRecord =
            has('My Profile & Records') ||
            has('employee_record') ||
            has('Employee Records')
          this.canViewLeave =
            has('Leave & Time Management') ||
            has('leave_management') ||
            has('leave-time')
          this.canViewOvertime =
            has('Overtime Management') ||
            has('overtime_management') ||
            has('overtime-monitoring')
          this.canViewPayslip = has('Payslip') || has('payslip')
          this.canViewDtr =
            has('Daily Time Record') || has('dtr') || has('daily time record')
          this.canViewSaln = has('SALN') || has('saln')
          this.canViewTravelOrder =
            has('Travel Order') || has('travel_order') || has('official business') || has('ob')
          this.canViewWfh =
            has('WFH Application') || has('wfh') || has('wfh_application') || has('work from home')
          this.canViewDocumentRequest =
            has('Document Requests') || has('document_request') || has('document-requests')
        }
      } catch (e) {
        // On error, keep disabled (hide restricted cards).
        this.canViewEmployeeRecord = false
        this.canViewLeave = false
        this.canViewOvertime = false
        this.canViewPayslip = false
        this.canViewDtr = false
        this.canViewSaln = false
        this.canViewTravelOrder = false
        this.canViewWfh = false
        this.canViewDocumentRequest = false
      } finally {
        this.menuAccessLoaded = true
      }
    },

    navigateToModule(module) {
      this.$router.push(`/${module}`)
    },

    // Get user ID for API calls
    getUserId() {
      if (!this.userData || !this.userData.id) {
        console.error('User data not available or missing ID')
        return null
      }
      return this.userData.id
    },

    // Navigate to 201 file with user ID
    navigateTo201File() {
      const userId = this.getUserId()
      if (userId) {
        this.$router.push(`/201-file/${userId}`)
      } else {
        console.error('User ID not available')
      }
    },

    // Load dashboard data from API
    async loadDashboardData() {
      const userId = this.getUserId()
      if (!userId) {
        console.error('User ID not available - user may not be properly logged in')
        return
      }

      this.loading = true
      try {
        const ApiService = (await import('../services/api.js')).default
        await ApiService.initSanctum()

        const response = await ApiService.getDashboardData(userId)

        if (response && response.success) {
          // Merge rather than replace, so any fields the backend doesn't yet
          // return (payslip_summary, contract_expiry, recently_resolved_count)
          // keep their safe defaults instead of becoming undefined.
          this.dashboardData = { ...this.dashboardData, ...response.data }
        } else {
          console.error('Failed to load dashboard data:', response?.message || 'Unknown error')
          this.$toast?.error('Failed to load dashboard data. Please try refreshing the page.')
        }
      } catch (error) {
        console.error('Dashboard data loading failed:', error)
        if (error.response?.status === 419) {
          this.$toast?.error('Session expired. Please login again.')
        }
      } finally {
        this.loading = false
      }
    },

    async load201FileData() {
      const userId = this.getUserId()
      if (userId) {
        try {
          const ApiService = (await import('../services/api.js')).default
          const response = await ApiService.getEmployee201File(userId)
          return response
        } catch (error) {
          console.error('Failed to load 201 file data:', error)
        }
      }
    },

    updateDateTime() {
      const now = new Date()
      this.currentDate = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
      this.currentTime = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    formatDate(value) {
      if (!value) return ''
      try {
        const d = new Date(value)
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
      } catch {
        return String(value)
      }
    },

    submitSupportTicket() {
      this.$message.success('Support ticket submitted successfully!')
    },

    async loadAnnouncements() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const response = await ApiService.getAnnouncements()

        if (response && response.success) {
          this.announcements = response.data || []
        } else {
          console.error('Failed to load announcements:', response?.message || 'Unknown error')
        }
      } catch (error) {
        console.error('Error loading announcements:', error)
      }
    },

    // Administrative Access Navigation Methods (mirror MainLayout.vue)
    navigateToControlPanel() {
      let user = this.userData
      if (!user) {
        const storedUser = localStorage.getItem('user_data')
        if (storedUser) {
          try {
            user = JSON.parse(storedUser)
          } catch (error) {
            console.error('Error parsing user data:', error)
          }
        }
      }

      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const controlPanelUrl = import.meta.env.VITE_CONTROL_PANEL_URL || 'http://192.168.0.126:8081'
        const url = `${controlPanelUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`
        window.open(url, '_blank')
      } else {
        const controlPanelUrl = import.meta.env.VITE_CONTROL_PANEL_URL || 'http://192.168.0.126:8081'
        window.open(`${controlPanelUrl}/`, '_blank')
      }
    },

    navigateToPayrollModule() {
      let user = this.userData
      if (!user) {
        const storedUser = localStorage.getItem('user_data')
        if (storedUser) {
          user = JSON.parse(storedUser)
        }
      }

      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const payrollModuleUrl = import.meta.env.VITE_PAYROLL_MODULE_URL || 'http://192.168.0.126:8083'
        const url = `${payrollModuleUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`
        window.open(url, '_blank')
      } else {
        const payrollModuleUrl = import.meta.env.VITE_PAYROLL_MODULE_URL || 'http://192.168.0.126:8083'
        window.open(`${payrollModuleUrl}/`, '_blank')
      }
    },

    navigateToTimekeeping() {
      let user = this.userData
      if (!user) {
        const storedUser = localStorage.getItem('user_data')
        if (storedUser) {
          try {
            user = JSON.parse(storedUser)
          } catch (error) {
            console.error('Error parsing user data:', error)
          }
        }
      }

      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const timekeepingUrl = import.meta.env.VITE_TIMEKEEPING_MODULE_URL || 'http://192.168.0.126:8085'
        const url = `${timekeepingUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`
        window.open(url, '_blank')
      } else {
        const timekeepingUrl = import.meta.env.VITE_TIMEKEEPING_MODULE_URL || 'http://192.168.0.126:8085'
        window.open(`${timekeepingUrl}/`, '_blank')
      }
    },

    navigateToHRModule() {
      let user = this.userData
      if (!user) {
        const storedUser = localStorage.getItem('user_data')
        if (storedUser) {
          user = JSON.parse(storedUser)
        }
      }

      if (user && user.email) {
        const authToken = localStorage.getItem('auth_token')
        const employeeNo = user.employee_no || user.id || 'admin'
        const hrModuleUrl = import.meta.env.VITE_HR_MODULE_URL || 'http://192.168.0.126:8082'
        const url = `${hrModuleUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`
        window.open(url, '_blank')
      } else {
        const hrModuleUrl = import.meta.env.VITE_HR_MODULE_URL || 'http://192.168.0.126:8082'
        window.open(`${hrModuleUrl}/`, '_blank')
      }
    }
  }
}
</script>

<style scoped>
/* Hide scrollbars and devtools */
html, body, #app {
  overflow: hidden;
}

#__vconsole, #devtools, .devtools, [data-vconsole] {
  display: none !important;
}

::-webkit-scrollbar {
  display: none;
}

* {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.service-card {
  cursor: pointer;
  transition: all 0.3s ease;
}

.service-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.service-icon {
  margin-bottom: 1rem;
}

.service-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.service-description {
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.4;
}

.space-y-3 > * + * {
  margin-top: 0.75rem;
}

.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.justify-between {
  justify-content: space-between;
}

.space-x-3 > * + * {
  margin-left: 0.75rem;
}

.flex-1 {
  flex: 1 1 0%;
}

.text-sm {
  font-size: 0.875rem;
  line-height: 1.25rem;
}

.text-xs {
  font-size: 0.75rem;
  line-height: 1rem;
}

.text-lg {
  font-size: 1.125rem;
  line-height: 1.75rem;
}

.font-medium {
  font-weight: 500;
}

.font-semibold {
  font-weight: 600;
}

.text-gray-900 {
  color: #111827;
}

.text-gray-700 {
  color: #374151;
}

.text-gray-600 {
  color: #4b5563;
}

.text-gray-500 {
  color: #6b7280;
}

.p-3 {
  padding: 0.75rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mb-8 {
  margin-bottom: 2rem;
}

.mt-4 {
  margin-top: 1rem;
}

.rounded-lg {
  border-radius: 0.5rem;
}

.hover\:bg-gray-50:hover {
  background-color: #f9fafb;
}

.transition-colors {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

.duration-200 {
  transition-duration: 200ms;
}

.cursor-pointer {
  cursor: pointer;
}

.w-full {
  width: 100%;
}
</style>