<template>
  <div class="flex min-h-screen bg-slate-100 text-slate-900">
    <!-- Professional Sidebar Navigation -->
    <aside :class="[isSidebarCollapsed ? 'w-20' : 'w-64', 'h-screen sticky top-0 z-40 bg-white/95 shadow-xl shadow-slate-200/70 border-r border-slate-200/80 flex flex-col transition-all duration-300 ease-in-out backdrop-blur overflow-y-auto']">
      <!-- Sidebar Header -->
      <div class="px-4 py-5 border-b border-slate-200/80">
        <div class="flex items-center" :class="isSidebarCollapsed ? 'justify-center' : 'space-x-3'">
          <div v-if="companyLogo" class="flex items-center justify-center overflow-hidden border shadow-md w-11 h-11 rounded-2xl bg-white border-slate-200">
            <img :src="companyLogo" :alt="companyName" class="object-contain w-full h-full p-1" />
          </div>
          <div v-if="!isSidebarCollapsed">
            <h1 class="text-base font-bold tracking-tight text-slate-900">{{ companyName }}</h1>
            <p class="text-xs font-medium text-slate-500">Employee System</p>
          </div>
        </div>
      </div>
      
      <!-- Navigation Menu -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto sidebar-scroll">
        <!-- Dashboard -->
        <router-link
          to="/dashboard"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/dashboard') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">Dashboard</span>
        </router-link>
        
        <!-- Profile & Records -->
        <router-link
          to="/201-file"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/201-file') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="isMenuAccessLoaded && hasMenuAccess('My Profile & Records')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">My Profile & Records</span>
        </router-link>
        
        <!-- Leave Management -->
        <router-link
          to="/leave-time"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/leave-time') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="isMenuAccessLoaded && hasMenuAccess('Leave & Time Management')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">Leave & Time Management</span>
        </router-link>

        <!-- Training Records -->
        <router-link
            to="/training-records"
            class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
            :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/training-records') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 17s4.5 10.747 10 10.747c5.5 0 10-4.998 10-11.247S17.5 6.253 12 6.253z"></path>
            </svg>
            <span v-if="!isSidebarCollapsed" class="font-medium">Training Records</span>
        </router-link>
        
        
        <!-- Pass Slip 
        <router-link
          to="/pass-slip"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/pass-slip') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="isMenuAccessLoaded && hasMenuAccess('Pass Slip')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">Pass Slip</span>
        </router-link> -->
        
        <!-- Overtime Management -->
        <router-link
          to="/overtime-monitoring"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/overtime-monitoring') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="isMenuAccessLoaded && hasMenuAccess('Overtime Management')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">Overtime Management</span>
        </router-link>
        
        <!-- Payslip -->
        <router-link
          to="/payslip"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/payslip') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="isMenuAccessLoaded && hasMenuAccess('Payslip')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">Payslip</span>
        </router-link>
        
        <!-- DTR -->
        <router-link
          to="/dtr"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/dtr') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="isMenuAccessLoaded && hasMenuAccess('Daily Time Record')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">Daily Time Record</span>
        </router-link>
        
        <!-- WFH Application -->
        <router-link
          to="/wfh-application"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/wfh-application') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="isMenuAccessLoaded && hasMenuAccess('WFH Application')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">WFH Application</span>
        </router-link>
        
        <!-- SALN 
        <router-link
          to="/saln"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/saln') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="isMenuAccessLoaded && hasMenuAccess('SALN')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">SALN</span>
        </router-link>
        -->

        <!-- Applicant Monitoring
        <router-link
          to="/applicants"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/applicants') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="hasHrmAccess && isMenuAccessLoaded && hasMenuAccess('Applicant Monitoring')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">Applicant Monitoring</span>
        </router-link> -->

        <!-- Interview Ratings (Panels) -->
        <router-link
          to="/panel-interview-ratings"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/panel-interview-ratings') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="isMenuAccessLoaded && showPanelInterviewRatingsNav"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">Interview Ratings (Panels)</span>
        </router-link>
        
        <!-- Announcements -->
        <router-link
          to="/announcements"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/announcements') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          v-if="isMenuAccessLoaded && hasMenuAccess('Announcements')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19a7 7 0 01-7-7v-3a4 4 0 014-4h6a4 4 0 014 4v3a7 7 0 01-7 7z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">Announcements</span>
        </router-link>

        <!-- Document Request -->
          <router-link
            to="/document-requests"
            class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
            :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/document-requests') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          >
            <!-- Document / File Outline SVG Icon -->
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span v-if="!isSidebarCollapsed" class="font-medium">Document Requests</span>
        </router-link>

        <!-- Downloadables -->
         <router-link
              to="/downloadables"
              class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
              :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/downloadables') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
          >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <span v-if="!isSidebarCollapsed" class="font-medium">Downloadables</span>
          </router-link>

        <router-link
          v-if="isIpcrAvailable"
          to="/ipcr"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/ipcr') && !isActive('/ipcr/agency-head-approval') && !isActive('/ipcr/hr-recalibration') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">IPCR</span>
        </router-link>

        <router-link
          v-if="isIpcrAgencyHead"
          to="/ipcr/agency-head-approval"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/ipcr/agency-head-approval') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">
            IPCR HoA Approval
            <span v-if="pendingIpcrAgencyHeadCount > 0" class="text-blue-600">({{ pendingIpcrAgencyHeadCount }})</span>
          </span>
        </router-link>

        <router-link
          v-if="isIpcrHR"
          to="/ipcr/hr-recalibration"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/ipcr/hr-recalibration') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">
            IPCR HR Recalibration
            <span v-if="pendingIpcrHRCount > 0" class="text-blue-600">({{ pendingIpcrHRCount }})</span>
          </span>
        </router-link>

        <router-link
          v-if="isOpcrAvailable"
          to="/opcr"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/opcr') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">OPCR</span>
        </router-link>

        <router-link
          v-if="isDpcrAvailable"
          to="/dpcr"
          class="flex items-center py-3 space-x-3 text-sm font-medium transition-all duration-200 text-slate-600 rounded-xl hover:bg-blue-50 hover:text-blue-700 hover:shadow-sm group"
          :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4', isActive('/dpcr') ? 'bg-blue-50 text-blue-700 shadow-sm ring-1 ring-blue-100' : '']"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed" class="font-medium">DPCR</span>
        </router-link>

        <!-- Administrative Access (only if user has at least one admin module) -->
        <div class="pt-2" v-if="hasAnyAdminAccess">
          <div v-if="!isSidebarCollapsed" class="px-4 pt-3 pb-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase">Administrative Access</div>

          <!-- Control Panel -->
          <a
            @click="navigateToControlPanel()"
            class="flex items-center py-3 space-x-3 text-gray-700 transition-colors duration-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:text-blue-700 group"
            :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4']"
            v-if="hasCpmAccess"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 16v-2m4-8h2M4 12H2m13.657-5.657l1.414-1.414M6.343 17.657l-1.414 1.414m12.728 0l-1.414-1.414M6.343 6.343L4.929 4.929"></path>
            </svg>
            <span v-if="!isSidebarCollapsed" class="font-medium">Control Panel</span>
          </a>

          <!-- Payroll Module -->
          <a
            @click="navigateToPayrollModule()"
            class="flex items-center py-3 space-x-3 text-gray-700 transition-colors duration-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:text-blue-700 group"
            :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4']"
            v-if="hasHrpAccess"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            <span v-if="!isSidebarCollapsed" class="font-medium">Payroll Module</span>
          </a>

          <!-- Timekeeping Module -->
          <a
            @click="navigateToTimekeeping()"
            class="flex items-center py-3 space-x-3 text-gray-700 transition-colors duration-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:text-blue-700 group"
            :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4']"
            v-if="hasHrtAccess"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span v-if="!isSidebarCollapsed" class="font-medium">Timekeeping Module</span>
          </a>

          <!-- HR Module -->
          <a
            @click="navigateToHRModule()"
            class="flex items-center py-3 space-x-3 text-gray-700 transition-colors duration-200 rounded-lg cursor-pointer hover:bg-blue-50 hover:text-blue-700 group"
            :class="[isSidebarCollapsed ? 'justify-center px-0' : 'px-4']"
            v-if="hasHrmAccess"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5.334-3.8M9 20H4v-2a4 4 0 015.334-3.8M12 14a4 4 0 100-8 4 4 0 000 8"></path>
            </svg>
            <span v-if="!isSidebarCollapsed" class="font-medium">HR Module</span>
          </a>
        </div>
      </nav>
      
      <!-- Sidebar Footer -->
      <div class="p-4 bg-white border-t border-slate-200/80">
        <div class="flex items-center" :class="isSidebarCollapsed ? 'justify-center' : 'justify-between'">
          <button @click="toggleSidebar" class="p-2 transition text-slate-500 rounded-xl hover:bg-slate-100 hover:text-slate-900" :aria-label="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <svg v-if="!isSidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
          <div v-if="!isSidebarCollapsed" class="text-right">
            <p class="text-xs text-gray-500">{{ companyName }}</p>
            <p class="text-xs text-gray-400">v1.0.0</p>
          </div>
        </div>
      </div>
    </aside>
    
    <!-- Main Content Area -->
    <div class="flex flex-col flex-1 min-w-0">
      <!-- Top Header -->
      <header class="sticky top-0 z-30 px-4 py-3 border-b shadow-sm bg-white/90 border-slate-200/80 backdrop-blur sm:px-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ getPageTitle() }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ getPageDescription() }}</p>
          </div>

          <!-- Right Side: User Controls -->
          <div class="flex flex-wrap items-center gap-3 lg:justify-end">
            <!-- Notification Bell -->
            <div class="relative notification-dropdown">
              <button
                @click="toggleNotifications"
                class="relative p-2.5 text-slate-500 transition-all duration-200 rounded-xl hover:text-blue-600 hover:bg-blue-50"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19a7 7 0 01-7-7v-3a4 4 0 014-4h6a4 4 0 014 4v3a7 7 0 01-7 7z"></path>
                </svg>
                <!-- Notification Badge -->
                <span v-if="unreadCount > 0" class="absolute flex items-center justify-center w-5 h-5 text-xs text-white bg-red-500 rounded-full -top-1 -right-1">
                  {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
              </button>

              <!-- Professional Notifications Dropdown -->
              <div v-if="showNotifications" class="absolute right-0 z-50 mt-3 overflow-hidden bg-white border border-slate-200 rounded-2xl shadow-2xl shadow-slate-300/40 w-[22rem] sm:w-96">
                <div class="p-4 border-b border-gray-200">
                  <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                    <button
                      @click="markAllAsRead"
                      class="text-sm text-blue-600 hover:text-blue-800"
                    >
                      Mark all as read
                    </button>
                  </div>
                  <div class="flex items-center justify-between text-xs text-gray-600">
                    <button
                      @click="archiveAllRead"
                      class="hover:text-gray-800"
                    >
                      Archive all read
                    </button>
                    <button
                      @click="toggleArchived"
                      class="hover:text-gray-800"
                    >
                      {{ showArchived ? 'Hide archived' : 'Show archived' }}
                    </button>
                  </div>
                </div>
                
                <div class="overflow-y-auto max-h-96">
                  <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                    No notifications
                  </div>
                  <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="relative p-4 transition-colors duration-200 border-b border-gray-100 group hover:bg-gray-50"
                  >
                    <div class="flex items-start space-x-3">
                      <div class="flex-shrink-0" @click="handleNotificationClick(notification)">
                        <div :class="getNotificationIconClass(notification.type)" class="flex items-center justify-center w-8 h-8 rounded-full cursor-pointer">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="notification.type === 'retirement_reminder'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                          </svg>
                        </div>
                      </div>
                      <div class="flex-1 min-w-0 cursor-pointer" @click="handleNotificationClick(notification)">
                        <p class="text-sm font-medium text-gray-900">
                          {{ notification.title || notification.Title || 'Untitled' }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                          {{ notification.message || notification.content || notification.Event }}
                        </p>
                        <div class="flex items-center justify-between mt-2">
                          <span class="text-xs text-gray-500">
                            {{ formatNotificationDate(notification.created_at) }}
                          </span>
                          <div class="flex items-center space-x-2">
                            <span v-if="notification.data && notification.data.employee_id" class="px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">
                              Personal
                            </span>
                            <span v-else class="px-2 py-1 text-xs text-green-800 bg-green-100 rounded-full">
                              Global
                            </span>
                            <span v-if="!notification.is_read" class="w-2 h-2 bg-red-500 rounded-full"></span>
                          </div>
                        </div>
                      </div>
                      <button
                        @click.stop="archiveNotification(notification.id)"
                        class="flex-shrink-0 p-1 text-gray-400 transition-colors rounded opacity-0 group-hover:opacity-100 hover:text-gray-600 hover:bg-gray-200"
                        title="Archive notification"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
                
                <div class="p-4 bg-white border-t border-slate-200/80">
                  <router-link
                    to="/announcements"
                    class="block text-sm font-medium text-center text-blue-600 hover:text-blue-800"
                  >
                    View all notifications
                  </router-link>
            </div>
            </div>
          </div>

            <!-- Professional User Menu -->
          <div class="flex flex-wrap items-center gap-3 lg:justify-end">
            <div class="text-right">
                <p class="text-sm font-medium text-gray-900">{{ userData.name }}</p>
                <p class="text-xs text-gray-500">{{ userData.email }}</p>
            </div>
              <div class="w-px h-6 bg-gray-300"></div>
            <button
              @click="refreshUserData"
                class="flex items-center px-3 py-2 space-x-2 text-sm text-gray-600 transition-all duration-200 rounded-md hover:text-gray-900 hover:bg-gray-100"
                title="Refresh Access Rights"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
              <span class="font-medium">Refresh</span>
            </button>
            <button
              @click="logout"
                class="flex items-center px-3 py-2 space-x-2 text-sm text-gray-600 transition-all duration-200 rounded-md hover:text-gray-900 hover:bg-gray-100"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
              <span class="font-medium">Sign Out</span>
            </button>
          </div>
        </div>
      </div>
    </header>

      <!-- Main Content Area -->
      <main class="flex-1 min-w-0 p-4 overflow-y-auto bg-slate-100 sm:p-6">
        <!-- Breadcrumb Navigation 
        <div v-if="breadcrumbs.length > 0" class="mb-6">
          <div class="p-4 bg-white border shadow-sm border-slate-200 rounded-2xl">
        <Breadcrumb :breadcrumbs="breadcrumbs" />
      </div>
    </div>
    -->
        <!-- Page Content -->
        <div class="p-4 bg-white border shadow-sm border-slate-200 rounded-2xl sm:p-6">
      <slot />
        </div>
    </main>
    </div>
  </div>
</template>

<script>
import Breadcrumb from '../components/Breadcrumb.vue'
import { fetchCompanyPublic, getCompanyLogo } from '../services/companyPublic.js'

export default {
  name: 'MainLayout',
  components: { Breadcrumb },
  props: {
    breadcrumbs: {
      type: Array,
      default: () => []
    }
  },
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
      isSidebarCollapsed: false,
      userData: {
        name: '',
        email: ''
      },
      // Access flags (derived in mounted from user_data)
      hasHrmAccess: false,
      hasHrtAccess: false,
      hasHrpAccess: false,
      hasCpmAccess: false,
      // Panel interview (interviewer) access
      hasPanelInterviewAccess: false,
      isDivisionChief: false,
      isIpcrAvailable: false,
      isIpcrAgencyHead: false,
      pendingIpcrAgencyHeadCount: 0,
      isIpcrHR: false,
      pendingIpcrHRCount: 0,
      isOpcrAvailable: false,
      isDpcrAvailable: false,

      // Tab/module access from `access` table (controls sidebar visibility)
      allowedMenuKeys: [],
      allowedMenuNames: [],
      isMenuAccessLoaded: false,
        showNotifications: false,
        notifications: [],
        unreadCount: 0,
        showArchived: false,
        notificationInterval: null // Store interval ID for cleanup
    }
  },
  computed: {
    companyLogo() {
      return getCompanyLogo(this.company)
    },
    companyName() {
      return this.company.name || 'Employee Portal'
    },
    /** Panelists need this link even without a separate tab-access row; HR may grant tab-only access too. */
    showPanelInterviewRatingsNav() {
      return this.hasPanelInterviewAccess || this.hasMenuAccess('Interview Ratings (Panels)')
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
      // Initialize access flags from stored user_data
      this.recalculateAccessFlags()
      this.loadNotifications()
      // Load menu access rights based on `access` table (with caching to avoid flicker)
      try {
        const cachedAccess = localStorage.getItem('user_tab_access')
        if (cachedAccess) {
          const parsed = JSON.parse(cachedAccess)
          const menus = Array.isArray(parsed?.menus) ? parsed.menus : []
          this.allowedMenuKeys = menus.map(m => m.menu_key).filter(Boolean)
          this.allowedMenuNames = menus.map(m => m.menu).filter(Boolean)
          this.isMenuAccessLoaded = true
        }
      } catch (e) {
        // Invalid cache JSON; loadMenuAccess will reset from API
      }
      // Always reconcile with server — cache alone keeps revoked items visible until cleared.
      this.loadMenuAccess()

      // Restore cached panel interview nav access to prevent disappearing on refresh.
      try {
        const cacheKey = this.userData?.id ? `portal_panel_interview_access_${this.userData.id}` : ''
        if (cacheKey) {
          const cachedPanelAccess = JSON.parse(localStorage.getItem(cacheKey) || '{}')
          if (typeof cachedPanelAccess?.hasPanelInterviewAccess === 'boolean') {
            this.hasPanelInterviewAccess = cachedPanelAccess.hasPanelInterviewAccess
          }
        }
      } catch (_) {}

      // Restore cached head/availability flags (IPCR/OPCR/DPCR) to avoid flicker
      try {
        const cacheKey = `portal_head_flags_${this.userData.id}`
        const cachedFlagsRaw = localStorage.getItem(cacheKey)
        if (cachedFlagsRaw) {
          const cachedFlags = JSON.parse(cachedFlagsRaw)
          if (typeof cachedFlags.isIpcrAvailable === 'boolean') {
            this.isIpcrAvailable = cachedFlags.isIpcrAvailable
          }
          if (typeof cachedFlags.isIpcrAgencyHead === 'boolean') {
            this.isIpcrAgencyHead = cachedFlags.isIpcrAgencyHead
          }
          if (typeof cachedFlags.pendingIpcrAgencyHeadCount === 'number') {
            this.pendingIpcrAgencyHeadCount = cachedFlags.pendingIpcrAgencyHeadCount
          }
          if (typeof cachedFlags.isIpcrHR === 'boolean') {
            this.isIpcrHR = cachedFlags.isIpcrHR
          }
          if (typeof cachedFlags.pendingIpcrHRCount === 'number') {
            this.pendingIpcrHRCount = cachedFlags.pendingIpcrHRCount
          }
          if (typeof cachedFlags.isOpcrAvailable === 'boolean') {
            this.isOpcrAvailable = cachedFlags.isOpcrAvailable
          }
          if (typeof cachedFlags.isDpcrAvailable === 'boolean') {
            this.isDpcrAvailable = cachedFlags.isDpcrAvailable
          }
        }
      } catch (e) {
        // Ignore cache issues; async checks below will refresh
      }

        // Check if current user is tagged as an interviewer (has panel interviews assigned)
        this.checkPanelInterviewAccess()

      // If user_data is from an older session and is missing access flags, refresh it once.
      if (
        typeof this.userData.with_hrm_access === 'undefined' &&
        typeof this.userData.with_hrt_access === 'undefined' &&
        typeof this.userData.with_hrp_access === 'undefined' &&
        typeof this.userData.with_cpm_access === 'undefined'
      ) {
        this.refreshUserData()
      }
    }

    // Check if user is a division chief for IPCR access
    this.checkDivisionChiefAccess()
    // Check OPCR access
    this.checkOPCRAccess()
    // Check DPCR access
    this.checkDPCRAccess()

    // Add click outside listener
    document.addEventListener('click', this.handleClickOutside)

    // Auto-refresh notifications every 30 seconds
    this.notificationInterval = setInterval(() => {
      this.loadNotifications()
    }, 30000) // 30 seconds
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside)
    // Clear notification interval
    if (this.notificationInterval) {
      clearInterval(this.notificationInterval)
    }
  },
  methods: {
    toBool(val) {
      if (val === true || val === 1) return true
      if (val === false || val === 0 || val === null || typeof val === 'undefined') return false
      if (typeof val === 'string') {
        const v = val.trim().toLowerCase()
        return v === '1' || v === 'true' || v === 'yes' || v === 'y'
      }
      return !!val
    },
    recalculateAccessFlags() {
      const isAdmin = this.toBool(this.userData?.is_admin)
      this.hasHrmAccess = this.toBool(this.userData?.with_hrm_access) || isAdmin
      this.hasHrtAccess = this.toBool(this.userData?.with_hrt_access) || isAdmin
      this.hasHrpAccess = this.toBool(this.userData?.with_hrp_access) || isAdmin
      this.hasCpmAccess = this.toBool(this.userData?.with_cpm_access) || isAdmin
    },

    hasAnyAdminAccess() {
      return this.hasHrmAccess || this.hasHrpAccess || this.hasHrtAccess || this.hasCpmAccess
    },

    async loadMenuAccess() {
      const previousKeys = [...this.allowedMenuKeys]
      const previousNames = [...this.allowedMenuNames]
      try {
        const ApiService = (await import('../services/api.js')).default
        const res = await ApiService.getUserTabAccess()
        const menus = res?.data?.menus

        if (Array.isArray(menus)) {
          this.allowedMenuKeys = menus
            .map(m => m.menu_key)
            .filter(Boolean)
          this.allowedMenuNames = menus
            .map(m => m.menu)
            .filter(Boolean)
          localStorage.setItem('user_tab_access', JSON.stringify({ menus }))
        } else {
          // Successful response but unexpected shape — do not leave stale cache in UI
          this.allowedMenuKeys = []
          this.allowedMenuNames = []
          localStorage.setItem('user_tab_access', JSON.stringify({ menus: [] }))
        }
      } catch (e) {
        // Transient API failure: keep existing allowances (from cache) instead of wiping the nav.
        if (previousKeys.length === 0 && previousNames.length === 0) {
          this.allowedMenuKeys = []
          this.allowedMenuNames = []
        }
      } finally {
        this.isMenuAccessLoaded = true
      }
    },

    normalizeMenuKey(key) {
      if (key === null || typeof key === 'undefined') return ''
      return String(key).trim().toLowerCase().replace(/\s+/g, ' ')
    },

    hasMenuAccess(menuKey) {
      // Until loaded, hide restricted items.
      if (!this.isMenuAccessLoaded) return false

      const k = this.normalizeMenuKey(menuKey)
      const allowedKeys = this.allowedMenuKeys.map(this.normalizeMenuKey)
      const allowedNames = this.allowedMenuNames.map(this.normalizeMenuKey)
      return allowedKeys.includes(k) || allowedNames.includes(k)
    },
    getPanelAccessCacheKey() {
      return this.userData?.id ? `portal_panel_interview_access_${this.userData.id}` : ''
    },
    async checkPanelInterviewAccess() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const res = await ApiService.getPanelInterviews()
        const interviews = Array.isArray(res?.data?.interviews) ? res.data.interviews : []
        const isPanelMember = !!res?.data?.is_panel_member
        this.hasPanelInterviewAccess = !!(res?.success && (interviews.length > 0 || isPanelMember))

        const cacheKey = this.getPanelAccessCacheKey()
        if (cacheKey) {
          localStorage.setItem(cacheKey, JSON.stringify({ hasPanelInterviewAccess: this.hasPanelInterviewAccess }))
        }
      } catch (e) {
        // Keep cached state on transient API errors to avoid nav flicker/disappearance.
        const cacheKey = this.getPanelAccessCacheKey()
        if (cacheKey) {
          try {
            const cached = JSON.parse(localStorage.getItem(cacheKey) || '{}')
            if (typeof cached?.hasPanelInterviewAccess === 'boolean') {
              this.hasPanelInterviewAccess = cached.hasPanelInterviewAccess
              return
            }
          } catch (_) {}
        }
        this.hasPanelInterviewAccess = false
      }
    },
    toggleSidebar() {
      this.isSidebarCollapsed = !this.isSidebarCollapsed
    },
    isActive(path) {
      return this.$route.path === path
    },
    getPageTitle() {
      const route = this.$route.path
      const titles = {
        '/dashboard': 'Dashboard',
        '/201-file': 'My Profile & Records',
        '/leave-management': 'Leave Management',
        '/overtime-monitoring': 'Overtime Management',
        '/payslip': 'Payslip',
        '/dtr': 'Daily Time Record',
        '/saln': 'SALN',
        '/announcements': 'Announcements',
        '/ipcr': 'IPCR',
        '/ipcr/agency-head-approval': 'IPCR HoA Approval',
        '/ipcr/hr-recalibration': 'IPCR HR Recalibration',
        '/opcr': 'OPCR',
        '/dpcr': 'DPCR',
        '/applicants': ' ',
        '/panel-interview-ratings': 'Interview Ratings (Panels)'
      }
      return titles[route] || this.companyName
    },
    
    getPageDescription() {
      const route = this.$route.path
      const descriptions = {
        '/dashboard': 'Overview of your employment information',
        '/201-file': 'Manage your personal and employment records',
        '/leave-management': 'Submit and track your leave requests',
        '/overtime-monitoring': 'Manage your overtime applications',
        '/payslip': 'View and download your payslips',
        '/dtr': 'Track your daily time records',
        '/saln': 'Submit your Statement of Assets, Liabilities and Net Worth',
        '/announcements': 'View important announcements and updates',
        '/ipcr': 'Individual Performance Commitment and Review',
        '/ipcr/agency-head-approval': 'Approve IPCR records after supervisor calibration',
        '/ipcr/hr-recalibration': 'HR recalibration queue after Head of Agency approval',
        '/opcr': 'Office Performance Commitment and Review',
        '/dpcr': 'Department Performance Commitment and Review',
        '/applicants': 'Monitor and manage job applicants',
        '/panel-interview-ratings': 'Submit interview ratings for assigned applicants'
      }
      return descriptions[route] || `${this.companyName} — Employee self-service portal`
    },
    
    async loadNotifications() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const response = await ApiService.getUserNotifications(this.showArchived)
        if (response && response.success) {
          this.notifications = Array.isArray(response.data) ? response.data : (response.data?.notifications || [])
          this.unreadCount = this.notifications.filter(n => !n.is_read).length
        }
      } catch (error) {
        console.error('Failed to load notifications:', error)
      }
    },
    async checkDivisionChiefAccess() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const response = await ApiService.checkDivisionChiefAccess()
        if (response && response.success) {
          this.isDivisionChief = response.data?.is_division_chief || false
          this.isIpcrAvailable = response.data?.is_available ?? false
          this.isIpcrAgencyHead = response.data?.is_agency_head ?? false
          this.pendingIpcrAgencyHeadCount = response.data?.pending_agency_head_count ?? 0
          this.isIpcrHR = response.data?.is_hr_user ?? false
          this.pendingIpcrHRCount = response.data?.pending_hr_recalibration_count ?? 0

          // Cache flags per user to avoid flicker on navigation
          try {
            const cacheKey = `portal_head_flags_${this.userData.id}`
            const existing = JSON.parse(localStorage.getItem(cacheKey) || '{}')
            localStorage.setItem(
              cacheKey,
              JSON.stringify({
                ...existing,
                isIpcrAvailable: this.isIpcrAvailable,
                isIpcrAgencyHead: this.isIpcrAgencyHead,
                pendingIpcrAgencyHeadCount: this.pendingIpcrAgencyHeadCount,
                isIpcrHR: this.isIpcrHR,
                pendingIpcrHRCount: this.pendingIpcrHRCount,
              })
            )
          } catch (e) {
            // ignore cache errors
          }
        }
      } catch (error) {
        console.error('Failed to check division chief access:', error)
        this.isDivisionChief = false
        this.isIpcrAvailable = false
      }
    },
    async checkOPCRAccess() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const response = await ApiService.checkDivisionChiefAccessOPCR()
        if (response && response.success) {
          // Show OPCR if user has access (either division chief or department-based access)
          this.isOpcrAvailable = !!(response.data?.has_access)

          // Cache flag
          try {
            const cacheKey = `portal_head_flags_${this.userData.id}`
            const existing = JSON.parse(localStorage.getItem(cacheKey) || '{}')
            localStorage.setItem(
              cacheKey,
              JSON.stringify({
                ...existing,
                isOpcrAvailable: this.isOpcrAvailable,
              })
            )
          } catch (e) {
            // ignore cache errors
          }
        }
      } catch (error) {
        console.error('Failed to check OPCR access:', error)
        this.isOpcrAvailable = false
      }
    },
    async checkDPCRAccess() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const response = await ApiService.checkDPCRAccess()
        if (response && response.success) {
          this.isDpcrAvailable = !!(response.data?.has_access)

          // Cache flag
          try {
            const cacheKey = `portal_head_flags_${this.userData.id}`
            const existing = JSON.parse(localStorage.getItem(cacheKey) || '{}')
            localStorage.setItem(
              cacheKey,
              JSON.stringify({
                ...existing,
                isDpcrAvailable: this.isDpcrAvailable,
              })
            )
          } catch (e) {
            // ignore cache errors
          }
        }
      } catch (error) {
        console.error('Failed to check DPCR access:', error)
        this.isDpcrAvailable = false
      }
    },
    handleClickOutside(event) {
      const notificationDropdown = this.$el.querySelector('.notification-dropdown')
      if (notificationDropdown && !notificationDropdown.contains(event.target)) {
        this.showNotifications = false
      }
    },
    getNotificationIconClass(type) {
      switch (type) {
        case 'announcement':
          return 'bg-green-100 text-green-600'
        case 'retirement_reminder':
          return 'bg-purple-100 text-purple-600'
        case 'birthday_month':
          return 'bg-pink-100 text-pink-600'
        case 'service_milestone':
          return 'bg-indigo-100 text-indigo-600'
        case 'cos_contract_expiration':
          return 'bg-yellow-100 text-yellow-600'
        default:
          return 'bg-blue-100 text-blue-600'
      }
    },
    async handleNotificationClick(notification) {
      try {
        // Mark notification as read if it's not already read
        if (!notification.is_read && !notification.id.startsWith('announcement_')) {
          const ApiService = (await import('../services/api.js')).default
          await ApiService.markNotificationAsRead(notification.id)
          
          // Update local state
          notification.is_read = true
          this.unreadCount = this.notifications.filter(n => !n.is_read).length
        }

        // Handle navigation based on notification type
        if (notification.action_url) {
          this.$router.push(notification.action_url)
        } else if (notification.type === 'announcement') {
          this.$router.push('/announcements')
        }

        // Close notification dropdown
        this.showNotifications = false
      } catch (error) {
        console.error('Error handling notification click:', error)
      }
    },
    async markAllAsRead() {
      try {
        const ApiService = (await import('../services/api.js')).default
        await ApiService.markAllNotificationsAsRead()
        
        // Update local state
        this.notifications.forEach(notification => {
          notification.is_read = true
        })
        this.unreadCount = 0
      } catch (error) {
        console.error('Error marking all notifications as read:', error)
      }
    },
    async archiveNotification(notificationId) {
      try {
        const ApiService = (await import('../services/api.js')).default
        await ApiService.archiveNotification(notificationId)
        
        // Remove from local state
        this.notifications = this.notifications.filter(n => n.id !== notificationId)
        this.unreadCount = this.notifications.filter(n => !n.is_read).length
      } catch (error) {
        console.error('Error archiving notification:', error)
      }
    },
    async archiveAllRead() {
      try {
        const ApiService = (await import('../services/api.js')).default
        await ApiService.archiveAllReadNotifications()
        
        // Remove all read notifications from local state
        this.notifications = this.notifications.filter(n => !n.is_read)
        this.unreadCount = this.notifications.filter(n => !n.is_read).length
      } catch (error) {
        console.error('Error archiving read notifications:', error)
      }
    },
    async toggleArchived() {
      this.showArchived = !this.showArchived
      await this.loadNotifications()
    },
    async refreshUserData() {
      try {
        const ApiService = (await import('../services/api.js')).default
        const response = await ApiService.getCurrentUser()
        if (response && response.success && response.data.user) {
          // Update localStorage with fresh user data
          localStorage.setItem('user_data', JSON.stringify(response.data.user))
          
          // Update component data
          this.userData = response.data.user
          
          // Recalculate access flags
          this.recalculateAccessFlags()
          
          return true
        }
        return false
      } catch (error) {
        console.error('Failed to refresh user data:', error)
        return false
      }
    },
    logout() {
      // Clear stored data
      localStorage.removeItem('auth_token')
      localStorage.removeItem('temp_token')
      localStorage.removeItem('user_data')
      localStorage.removeItem('user_tab_access')

      // Redirect to login
      this.$router.push('/login')
    },
    toggleNotifications() {
      this.showNotifications = !this.showNotifications
      // Refresh notifications when opening the dropdown
      if (this.showNotifications) {
        this.loadNotifications()
      }
    },
    formatNotificationDate(dateString) {
      if (!dateString) return 'Just now'
      
      const date = new Date(dateString)
      const now = new Date()
      const diffInSeconds = Math.floor((now - date) / 1000)
      
      if (diffInSeconds < 60) return 'Just now'
      if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
      if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
      if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`
      
      return date.toLocaleDateString()
    },
    
    navigateToControlPanel() {
      console.log('=== Control Panel Navigation Started ===');
      
      // Try to get user data from component first, then from localStorage
      let user = this.userData;
      console.log('this.userData:', user);
      
      if (!user) {
        const storedUser = localStorage.getItem('user_data');
        console.log('localStorage user_data:', storedUser);
        if (storedUser) {
          try {
            user = JSON.parse(storedUser);
            console.log('Parsed user from localStorage:', user);
          } catch (error) {
            console.error('Error parsing user data:', error);
          }
        }
      }
      
      console.log('Final user data for navigation:', user);
      
      // Check if we have email (employee_no can be null for admin users)
      if (user && user.email) {
        // Get the auth token from localStorage
        const authToken = localStorage.getItem('auth_token');
        console.log('Auth token from E-Portal:', authToken ? authToken.substring(0, 20) + '...' : 'No token');
        
        // Use employee_no if available, otherwise use a default or the user ID
        const employeeNo = user.employee_no || user.id || 'admin';
        
        // Pass the auth token along with other parameters
        const controlPanelUrl = import.meta.env.VITE_CONTROL_PANEL_URL || 'http://192.168.0.126:8081'
        const url = `${controlPanelUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`;
        console.log('Opening URL with auth:', url);
        console.log('Parameters being passed:', {
          employeeNo: employeeNo,
          email: user.email,
          authToken: authToken ? authToken.substring(0, 20) + '...' : 'No token',
          redirectFrom: 'e_portal'
        });
        window.open(url, '_blank');
      } else {
        console.log('No user data available, opening without auth');
        console.log('User object:', user);
        console.log('User has email?', user ? !!user.email : 'user is null/undefined');
        const controlPanelUrl = import.meta.env.VITE_CONTROL_PANEL_URL || 'http://192.168.0.126:8081'
        window.open(`${controlPanelUrl}/`, '_blank');
      }
      
      console.log('=== Control Panel Navigation Ended ===');
    },
    
    navigateToPayrollModule() {
      console.log('=== Payroll Module Navigation Started ===');
      
      // Try to get user data from component first, then from localStorage
      let user = this.userData;
      if (!user) {
        const storedUser = localStorage.getItem('user_data');
        if (storedUser) {
          user = JSON.parse(storedUser);
        }
      }
      
      if (user && user.email) {
        // Get the auth token from localStorage
        const authToken = localStorage.getItem('auth_token');
        const employeeNo = user.employee_no || user.id || 'admin';
        const payrollModuleUrl = import.meta.env.VITE_PAYROLL_MODULE_URL || 'http://192.168.0.126:8083'
        const url = `${payrollModuleUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`;
        window.open(url, '_blank');
      } else {
        const payrollModuleUrl = import.meta.env.VITE_PAYROLL_MODULE_URL || 'http://192.168.0.126:8083'
        window.open(`${payrollModuleUrl}/`, '_blank');
      }
      
      console.log('=== Payroll Module Navigation Ended ===');
    },
    navigateToTimekeeping() {
      console.log('=== Timekeeping Navigation Started ===');
      
      // Try to get user data from component first, then from localStorage
      let user = this.userData;
      console.log('this.userData:', user);
      
      if (!user) {
        const storedUser = localStorage.getItem('user_data');
        console.log('localStorage user_data:', storedUser);
        if (storedUser) {
          try {
            user = JSON.parse(storedUser);
            console.log('Parsed user from localStorage:', user);
          } catch (error) {
            console.error('Error parsing user data:', error);
          }
        }
      }
      
      console.log('Final user data for navigation:', user);
      
      if (user && user.email) {
        // Get the auth token from localStorage
        const authToken = localStorage.getItem('auth_token');
        console.log('Auth token from E-Portal:', authToken ? authToken.substring(0, 20) + '...' : 'No token');
        
        const employeeNo = user.employee_no || user.id || 'admin';
        const timekeepingUrl = import.meta.env.VITE_TIMEKEEPING_MODULE_URL || 'http://192.168.0.126:8085'
        const url = `${timekeepingUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`;
        console.log('Opening URL with auth:', url);
        console.log('Parameters being passed:', {
          employeeNo: employeeNo,
          email: user.email,
          authToken: authToken ? authToken.substring(0, 20) + '...' : 'No token',
          redirectFrom: 'e_portal'
        });
        window.open(url, '_blank');
      } else {
        console.log('No user data available, opening without auth');
        console.log('User object:', user);
        console.log('User has email?', user ? !!user.email : 'user is null/undefined');
        const timekeepingUrl = import.meta.env.VITE_TIMEKEEPING_MODULE_URL || 'http://192.168.0.126:8085'
        window.open(`${timekeepingUrl}/`, '_blank');
      }
      
      console.log('=== Timekeeping Navigation Ended ===');
    },
    
    navigateToHRModule() {
      console.log('=== HR Module Navigation Started ===');
      
      // Try to get user data from component first, then from localStorage
      let user = this.userData;
      if (!user) {
        const storedUser = localStorage.getItem('user_data');
        if (storedUser) {
          user = JSON.parse(storedUser);
        }
      }
      
      if (user && user.email) {
        // Get the auth token from localStorage
        const authToken = localStorage.getItem('auth_token');
        const employeeNo = user.employee_no || user.id || 'admin';
        const hrModuleUrl = import.meta.env.VITE_HR_MODULE_URL || 'http://192.168.0.126:8082'
        const url = `${hrModuleUrl}/?employee_no=${employeeNo}&email=${user.email}&auth_token=${encodeURIComponent(authToken || '')}&redirect_from=e_portal`;
        window.open(url, '_blank');
      } else {
        const hrModuleUrl = import.meta.env.VITE_HR_MODULE_URL || 'http://192.168.0.126:8082'
        window.open(`${hrModuleUrl}/`, '_blank');
      }
      
      console.log('=== HR Module Navigation Ended ===');
    }
  }
}
</script>

<style scoped>
main {
  overflow-x: hidden;
  max-width: 100%;
}

aside {
  height: 100vh;
}

.sidebar-scroll {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}

.sidebar-scroll::-webkit-scrollbar {
  width: 6px;
}

.sidebar-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 999px;
}

.router-link-active svg,
a:hover svg,
button:hover svg {
  transform: translateZ(0);
}
</style>