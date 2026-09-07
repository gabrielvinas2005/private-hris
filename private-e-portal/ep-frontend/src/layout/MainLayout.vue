<template>
  <div class="flex min-h-screen bg-[#F3F5FA] text-slate-900 font-sans antialiased">
    <!-- Sidebar Navigation — navy/blue to match the Employee Portal sign-in screen -->
    <aside :class="[
      isSidebarCollapsed ? 'w-20' : 'w-64 sm:w-72',
      isDarkMode 
        ? 'dark bg-[#0B1120] text-slate-300 border-white/[0.06] shadow-2xl' 
        : 'bg-white text-slate-700 border-slate-200/70 shadow-xl shadow-slate-200/50',
      'h-screen sticky top-0 z-40 flex flex-col transition-all duration-300 ease-in-out border-r overflow-y-auto'
    ]">
      <!-- Sidebar Header -->
      <div :class="[isDarkMode ? 'border-white/[0.06]' : 'border-slate-200/70', 'px-6 py-8 border-b']">
        <div class="flex flex-col items-center text-center space-y-3">
          <div v-if="companyLogo" :class="[isDarkMode ? 'bg-white/10' : 'bg-slate-100 border border-slate-200/70', 'flex items-center justify-center overflow-hidden w-12 h-12 rounded-3xl']">
            <img :src="companyLogo" :alt="companyName" class="object-contain w-full h-full" />
          </div>
          <div v-else class="flex items-center justify-center w-12 h-12 rounded-3xl bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-lg tracking-tight shadow-lg shadow-[#3B5EFF]/30">
            HR
          </div>
          <div v-if="!isSidebarCollapsed" class="min-w-0 w-full">
            <h1 :class="[isDarkMode ? 'text-white' : 'text-slate-900', 'text-base font-bold tracking-tight truncate']">{{ companyName }}</h1>
            <p :class="[isDarkMode ? 'text-slate-500' : 'text-slate-400', 'text-[11px] font-semibold uppercase tracking-wider truncate mt-0.5']">Employee System</p>
          </div>
        </div>
      </div>
      
      <!-- Navigation Menu -->
      <nav class="flex-1 px-3.5 py-5 space-y-1 overflow-y-auto sidebar-scroll">
        <!-- Dashboard -->
        <router-link
          to="/dashboard"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/dashboard') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">Dashboard</span>
        </router-link>
        
        <!-- Profile & Records -->
        <router-link
          to="/201-file"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/201-file') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
          v-if="isMenuAccessLoaded && hasMenuAccess('My Profile & Records')"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">My Profile & Records</span>
        </router-link>
 
        <!-- Time and Attendance -->
        <router-link
          to="/time-attendance"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/time-attendance') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
          v-if="isMenuAccessLoaded && hasMenuAccess('Time and Attendance')"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">Time and Attendance</span>
        </router-link>

        <!-- Employee Requests (Approvers Setup / Configured Approvers Only) -->
        <router-link
          to="/employee-requests"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/employee-requests') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
          v-if="isApproverUser"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">Employee Requests</span>
        </router-link>
         
        <!-- Payslip -->
        <router-link
          to="/payslip"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/payslip') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
          v-if="isMenuAccessLoaded && hasMenuAccess('Payslip')"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">Payslip</span>
        </router-link>
        
        <!-- DTR -->
        <router-link
          to="/dtr"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/dtr') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
          v-if="isMenuAccessLoaded && hasMenuAccess('Daily Time Record')"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">Daily Time Record</span>
        </router-link>
        
        <!-- WFH Application -->
        <router-link
          to="/wfh-application"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/wfh-application') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">WFH Application</span>
        </router-link>

        <!-- Interview Ratings (Panels) -->
        <router-link
          to="/panel-interview-ratings"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/panel-interview-ratings') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
          v-if="isMenuAccessLoaded && showPanelInterviewRatingsNav"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">Interview Ratings (Panels)</span>
        </router-link>
        
        <!-- Training Records -->
        <router-link
          to="/training-records"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
            isActive('/training-records')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <span v-if="!isSidebarCollapsed">Training Records</span>
        </router-link>
        
        <!-- Announcements -->
        <router-link
          to="/announcements"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/announcements') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
          v-if="isMenuAccessLoaded && hasMenuAccess('Announcements')"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19a7 7 0 01-7-7v-3a4 4 0 014-4h6a4 4 0 014 4v3a7 7 0 01-7 7z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">Announcements</span>
        </router-link>

        <!-- Holiday Calendar -->
        <router-link
          to="/holidays/calendar"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
            isActive('/holidays/calendar')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">Holiday Calendar</span>
        </router-link>

        <!-- Document Request -->
        <router-link
          to="/document-requests"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/document-requests') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
          v-if="isMenuAccessLoaded && hasMenuAccess('Document Requests')"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">Document Requests</span>
        </router-link>

        <!-- Downloadables -->
        <router-link
          to="/downloadables"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/downloadables') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">Downloadables</span>
        </router-link>

        <!-- IPCR -->
        <router-link
          v-if="isIpcrAvailable"
          to="/ipcr"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/ipcr') && !isActive('/ipcr/agency-head-approval') && !isActive('/ipcr/hr-recalibration') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">IPCR</span>
        </router-link>

        <!-- IPCR Agency Head Approval -->
        <router-link
          v-if="isIpcrAgencyHead"
          to="/ipcr/agency-head-approval"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/ipcr/agency-head-approval') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">
            IPCR HoA Approval
            <span v-if="pendingIpcrAgencyHeadCount > 0" class="text-[#7C96FF]">({{ pendingIpcrAgencyHeadCount }})</span>
          </span>
        </router-link>

        <!-- IPCR HR Recalibration -->
        <router-link
          v-if="isIpcrHR"
          to="/ipcr/hr-recalibration"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/ipcr/hr-recalibration') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">
            IPCR HR Recalibration
            <span v-if="pendingIpcrHRCount > 0" class="text-[#7C96FF]">({{ pendingIpcrHRCount }})</span>
          </span>
        </router-link>

        <!-- OPCR -->
        <router-link
          v-if="isOpcrAvailable"
          to="/opcr"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/opcr') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">OPCR</span>
        </router-link>

        <!-- DPCR -->
        <router-link
          v-if="isDpcrAvailable"
          to="/dpcr"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/dpcr') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
          <span v-if="!isSidebarCollapsed">DPCR</span>
        </router-link>

        <!-- Administrative Access -->
        <div :class="[isDarkMode ? 'border-white/[0.06]' : 'border-slate-200/70', 'pt-4 border-t mt-4']" v-if="hasAnyAdminAccess()">
          <div v-if="!isSidebarCollapsed" :class="[isDarkMode ? 'text-slate-500' : 'text-slate-400', 'px-3.5 pt-1 pb-2 text-[10.5px] font-bold tracking-widest uppercase']">Administrative Access</div>

          <!-- Control Panel -->
          <a
            @click="navigateToControlPanel()"
            class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl cursor-pointer group"
            :class="[
              isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
              isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            ]"
            v-if="hasCpmAccess"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 16v-2m4-8h2M4 12H2m13.657-5.657l1.414-1.414M6.343 17.657l-1.414 1.414m12.728 0l-1.414-1.414M6.343 6.343L4.929 4.929"></path>
            </svg>
            <span v-if="!isSidebarCollapsed">Control Panel</span>
          </a>

          <!-- Payroll Module -->
          <a
            @click="navigateToPayrollModule()"
            class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl cursor-pointer group"
            :class="[
              isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
              isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            ]"
            v-if="hasHrpAccess"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            <span v-if="!isSidebarCollapsed">Payroll Module</span>
          </a>

          <!-- Timekeeping Module -->
          <a
            @click="navigateToTimekeeping()"
            class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl cursor-pointer group"
            :class="[
              isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
              isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            ]"
            v-if="hasHrtAccess"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <span v-if="!isSidebarCollapsed">Timekeeping Module</span>
          </a>

          <!-- HR Module -->
          <a
            @click="navigateToHRModule()"
            class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl cursor-pointer group"
            :class="[
              isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
              isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            ]"
            v-if="hasHrmAccess"
          >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5.334-3.8M9 20H4v-2a4 4 0 015.334-3.8M12 14a4 4 0 100-8 4 4 0 000 8"></path>
            </svg>
            <span v-if="!isSidebarCollapsed">HR Module</span>
          </a>
        </div>
      </nav>
      
      <!-- Sidebar Footer -->
      <div :class="[isDarkMode ? 'border-white/[0.06] bg-[#0D1425]/80' : 'border-slate-200/70 bg-slate-50/80', 'p-4 border-t']">
        <div class="flex items-center" :class="isSidebarCollapsed ? 'justify-center' : 'justify-between'">
          <button 
            @click="toggleSidebar" 
            :class="[isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/10' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/60', 'p-2 transition rounded-xl']"
            :aria-label="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          >
            <svg v-if="!isSidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
          <div v-if="!isSidebarCollapsed" class="text-right">
            <p :class="[isDarkMode ? 'text-slate-400' : 'text-slate-600', 'text-xs font-medium']">{{ companyName }}</p>
            <p :class="[isDarkMode ? 'text-slate-600' : 'text-slate-400', 'text-[11px]']">v1.0.0</p>
          </div>
        </div>
      </div>
    </aside>
    
    <!-- Main Content Area -->
    <div class="flex flex-col flex-1 min-w-0">
      <!-- Top Header -->
      <header class="sticky top-0 z-30 px-4 py-3 border-b border-slate-200/70 shadow-sm bg-white/90 backdrop-blur sm:px-6">
        <div class="mt-2 mb-2 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ getPageTitle() }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ getPageDescription() }}</p>
          </div>

          <!-- Right Side: User Controls -->
          <div class="flex flex-wrap items-center gap-3 lg:justify-end">
            <!-- Theme Switcher Button -->
            <button
              @click="toggleTheme"
              class="p-2 text-slate-500 transition-all duration-200 rounded-xl hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-white/10"
              :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
              :aria-label="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            >
              <svg v-if="!isDarkMode" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
              <svg v-else class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </button>

            <!-- Notification Bell -->
            <div class="relative notification-dropdown">
              <button
                @click="toggleNotifications"
                class="relative p-2.5 text-slate-500 transition-all duration-200 rounded-xl hover:text-[#3B5EFF] hover:bg-[#3B5EFF]/[0.07]"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19a7 7 0 01-7-7v-3a4 4 0 014-4h6a4 4 0 014 4v3a7 7 0 01-7 7z"></path>
                </svg>
                <!-- Notification Badge -->
                <span v-if="unreadCount > 0" class="absolute flex items-center justify-center w-5 h-5 text-[10px] font-semibold text-white bg-red-500 rounded-full -top-1 -right-1 ring-2 ring-white">
                  {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
              </button>

              <!-- Notifications Dropdown -->
              <div v-if="showNotifications" class="absolute right-0 z-50 mt-3 overflow-hidden bg-white border border-slate-200 rounded-2xl shadow-2xl shadow-slate-300/40 w-[22rem] sm:w-96">
                <div class="p-4 border-b border-slate-100">
                  <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-bold tracking-tight text-slate-900">Notifications</h3>
                    <button
                      @click="markAllAsRead"
                      class="text-sm font-medium text-[#3B5EFF] hover:text-[#2946D9]"
                    >
                      Mark all as read
                    </button>
                  </div>
                  <div class="flex items-center justify-between text-xs text-slate-500">
                    <button
                      @click="archiveAllRead"
                      class="hover:text-slate-800"
                    >
                      Archive all read
                    </button>
                    <button
                      @click="toggleArchived"
                      class="hover:text-slate-800"
                    >
                      {{ showArchived ? 'Hide archived' : 'Show archived' }}
                    </button>
                  </div>
                </div>
                
                <div class="overflow-y-auto max-h-96">
                  <div v-if="notifications.length === 0" class="p-6 text-center text-sm text-slate-400">
                    No notifications
                  </div>
                  <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="relative p-4 transition-colors duration-200 border-b border-slate-100 group hover:bg-slate-50"
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
                        <p class="text-sm font-semibold text-slate-900">
                          {{ notification.title || notification.Title || 'Untitled' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-600">
                          {{ notification.message || notification.content || notification.Event }}
                        </p>
                        <div class="flex items-center justify-between mt-2">
                          <span class="text-xs text-slate-400">
                            {{ formatNotificationDate(notification.created_at) }}
                          </span>
                          <div class="flex items-center space-x-2">
                            <span v-if="notification.data && notification.data.employee_id" class="px-2 py-0.5 text-[11px] font-medium text-[#2946D9] bg-[#3B5EFF]/10 rounded-full">
                              Personal
                            </span>
                            <span v-else class="px-2 py-0.5 text-[11px] font-medium text-emerald-700 bg-emerald-100 rounded-full">
                              Global
                            </span>
                            <span v-if="!notification.is_read" class="w-2 h-2 bg-red-500 rounded-full"></span>
                          </div>
                        </div>
                      </div>
                      <button
                        @click.stop="archiveNotification(notification.id)"
                        class="flex-shrink-0 p-1 text-slate-400 transition-colors rounded opacity-0 group-hover:opacity-100 hover:text-slate-600 hover:bg-slate-200"
                        title="Archive notification"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
                
                <div class="p-4 bg-white border-t border-slate-200/70">
                  <router-link
                    to="/announcements"
                    class="block text-sm font-semibold text-center text-[#3B5EFF] hover:text-[#2946D9]"
                  >
                    View all notifications
                  </router-link>
                </div>
              </div>
            </div>

            <!-- User Menu -->
            <div class="flex flex-wrap items-center gap-3 lg:justify-end">
              <div class="flex items-center space-x-3">
                <div class="text-right">
                  <p class="text-sm font-semibold text-slate-900">{{ userData.name }}</p>
                  <p class="text-xs text-slate-500">{{ userData.email }}</p>
                </div>
                <el-skeleton v-if="!photoReady" animated class="w-9 h-9 flex items-center justify-center flex-shrink-0">
                  <template #template>
                    <el-skeleton-item variant="circle" style="width: 36px; height: 36px;" />
                  </template>
                </el-skeleton>
                <img v-else-if="userAvatarPhoto && !hasAvatarError" :src="userAvatarPhoto" @error="hasAvatarError = true" alt="Profile" class="w-9 h-9 rounded-full object-cover shadow-sm border border-slate-200 flex-shrink-0" />
                <div v-else class="flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-xs shadow-sm shadow-[#3B5EFF]/30 flex-shrink-0">
                  {{ userInitials }}
                </div>
              </div>
              <div class="w-px h-6 bg-slate-200"></div>
              <button
                @click="refreshUserData"
                class="flex items-center px-3 py-2 space-x-2 text-sm font-medium text-slate-600 transition-all duration-200 rounded-lg hover:text-slate-900 hover:bg-slate-100"
                title="Refresh Access Rights"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="hidden sm:inline">Refresh</span>
              </button>
              <button
                @click="logout"
                class="flex items-center px-3 py-2 space-x-2 text-sm font-medium text-slate-600 transition-all duration-200 rounded-lg hover:text-red-600 hover:bg-red-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="hidden sm:inline">Sign Out</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="flex-1 min-w-0 p-4 overflow-y-auto bg-[#F3F5FA] sm:p-6">
        <!-- Page Content -->
        <div class="p-4 bg-white border border-slate-200/70 shadow-sm rounded-2xl sm:p-6">
          <slot />
        </div>
      </main>

      <!-- Feature #2: Floating Shift-End Smart Reminder Banner -->
      <div 
        v-if="checkIsClockedIn() && !isReminderDismissed" 
        class="fixed bottom-6 right-6 z-50 max-w-md bg-slate-900 text-white rounded-2xl p-4 shadow-2xl border border-slate-700/60 backdrop-blur-md flex items-center justify-between gap-4 transition-all duration-300"
      >
        <div class="flex items-center gap-3">
          <div class="relative flex h-3 w-3 flex-shrink-0">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
          </div>
          <div>
            <span class="text-[10px] font-bold tracking-wider uppercase text-emerald-400 block">Status: Clocked In</span>
            <p class="text-xs text-slate-200 font-medium">Shift Reminder: Don't forget to Clock Out when wrapping up today!</p>
          </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          <button 
            @click="quickClockOut" 
            :disabled="isQuickClockingOut"
            class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs px-3 py-1.5 rounded-xl transition shadow cursor-pointer"
          >
            {{ isQuickClockingOut ? 'Clocking Out...' : 'Clock Out Now' }}
          </button>
          <button 
            @click="dismissReminder" 
            class="text-slate-400 hover:text-white text-xs p-1.5 rounded-lg hover:bg-slate-800 transition cursor-pointer"
            title="Dismiss reminder for this session"
          >
            ✕
          </button>
        </div>
      </div>

      <!-- Feature #1: Logout Clock-Out Guard Modal -->
      <div v-if="showLogoutGuardModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 text-slate-800 space-y-4">
          <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900">Clock Out Before Signing Out?</h3>
            <p class="text-xs text-slate-600 mt-1">
              You are currently <strong class="text-emerald-700 font-bold">Clocked In</strong>. Signing out without clocking out will result in a <strong>Missed Log</strong> for today's shift.
            </p>
          </div>
          <div class="space-y-2 pt-2">
            <button 
              @click="clockOutAndLogout" 
              :disabled="isLoggingOutWithPunch"
              class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-3 rounded-xl shadow-md transition flex items-center justify-center gap-2"
            >
              <span v-if="!isLoggingOutWithPunch">✓ Clock Out & Sign Out</span>
              <span v-else>Clocking Out & Signing Out...</span>
            </button>

            <button 
              @click="performActualLogout" 
              class="w-full bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-700 font-semibold text-xs py-2.5 rounded-xl transition"
            >
              Sign Out Without Clocking Out
            </button>

            <button 
              @click="showLogoutGuardModal = false" 
              class="w-full text-slate-400 hover:text-slate-600 text-xs py-1.5"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
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
      isDarkMode: localStorage.getItem('portal_theme') === 'dark',
      userData: {
        name: '',
        email: ''
      },
      hasAvatarError: false,
      photoReady: false,
      // Access flags (derived in mounted from user_data)
      hasHrmAccess: false,
      hasHrtAccess: false,
      hasHrpAccess: false,
      hasCpmAccess: false,
      // Panel interview (interviewer) access
      hasPanelInterviewAccess: false,
      isApproverUser: localStorage.getItem('is_approver_user') === 'true',
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
        notificationInterval: null, // Store interval ID for cleanup
        // Session timers & Clock-out guard state
        inactivityTimer: null,
        INACTIVITY_TIMEOUT_MS: 5 * 60 * 60 * 1000, // 5 hours of inactivity
        isReminderDismissed: sessionStorage.getItem('shift_reminder_dismissed') === 'true',
        isQuickClockingOut: false,
        showLogoutGuardModal: false,
        isLoggingOutWithPunch: false,
    }
  },
  computed: {
    companyLogo() {
      return getCompanyLogo(this.company)
    },
    companyName() {
      return this.company.name || 'Employee Portal'
    },
    userAvatarPhoto() {
      const p = this.userData?.photo || this.userData?.avatar
      if (!p) return null
      if (typeof p === 'string') {
        if (p.startsWith('data:image/') || p.startsWith('http://') || p.startsWith('https://') || p.startsWith('blob:')) {
          return p
        }
        return `data:image/jpeg;base64,${p}`
      }
      return null
    },
    userInitials() {
      const name = this.userData?.name || this.userData?.email || 'User'
      const parts = name.split(' ')
      if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
      return name.substring(0, 2).toUpperCase()
    },
    /** Panelists need this link even without a separate tab-access row; HR may grant tab-only access too. */
    showPanelInterviewRatingsNav() {
      return this.hasPanelInterviewAccess || this.hasMenuAccess('Interview Ratings (Panels)')
    }
  },
  mounted() {
    if (this.isDarkMode) {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }

    fetchCompanyPublic().then((data) => {
      this.company = data
    })

    // Get user data from localStorage
    const storedUserData = localStorage.getItem('user_data')
    if (storedUserData) {
      this.userData = JSON.parse(storedUserData)
      // Do NOT pre-load photo from localStorage cache — always wait for the API
      // so only the authoritative photo from WTIHRIS_PRIVATE.dbo.employees.photo is shown.
      // photoReady stays false until fetchUserPhoto() resolves.
      // Initialize access flags from stored user_data
      this.recalculateAccessFlags()
      this.loadNotifications()
      this.fetchUserPhoto()
      window.addEventListener('profile-photo-updated', this.handlePhotoUpdated)
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

      // If user_data is from an older session or missing photo/access flags, refresh it.
      if (
        !this.userData.photo ||
        typeof this.userData.with_hrm_access === 'undefined' &&
        typeof this.userData.with_hrt_access === 'undefined' &&
        typeof this.userData.with_hrp_access === 'undefined' &&
        typeof this.userData.with_cpm_access === 'undefined'
      ) {
        this.refreshUserData()
      }
    }

    // Check if user is an approver for Employee Requests
    this.checkApproverStatus()

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

    // Ensure session_start_time exists
    if (!localStorage.getItem('session_start_time')) {
      localStorage.setItem('session_start_time', Date.now().toString())
    }

    // Start 5-hour inactivity auto-logout timer (logs out if user does not move/interact for 5 hours)
    this._boundResetInactivity = this.resetInactivityTimer.bind(this)
    ;['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'].forEach(evt => {
      document.addEventListener(evt, this._boundResetInactivity, { passive: true })
    })
    this.resetInactivityTimer()

    // Login Clock-In status reminder & real-time update listener
    this.checkLoginClockReminder()
    this._boundDtrUpdated = () => this.checkLoginClockReminder(true)
    window.addEventListener('dtr-updated', this._boundDtrUpdated)
  },
  beforeUnmount() {
    if (this._boundDtrUpdated) {
      window.removeEventListener('dtr-updated', this._boundDtrUpdated)
    }
    document.removeEventListener('click', this.handleClickOutside)
    window.removeEventListener('profile-photo-updated', this.handlePhotoUpdated)
    // Clear notification interval
    if (this.notificationInterval) {
      clearInterval(this.notificationInterval)
    }
    // Clear inactivity timer and remove listeners
    if (this.inactivityTimer) {
      clearTimeout(this.inactivityTimer)
    }
    if (this._boundResetInactivity) {
      ;['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'].forEach(evt => {
        document.removeEventListener(evt, this._boundResetInactivity)
      })
    }
  },
  methods: {
    async checkApproverStatus() {
      try {
        const userId = this.userData?.id || JSON.parse(localStorage.getItem('user_data') || '{}').id
        if (!userId) {
          this.isApproverUser = false
          localStorage.setItem('is_approver_user', 'false')
          return
        }
        const { default: ApiService } = await import('../services/api.js')

        let isApprover = false
        try {
          const res = await ApiService.getApproverPipelineAccess(userId)
          if (res && res.data && typeof res.data.is_approver !== 'undefined') {
            isApprover = Boolean(res.data.is_approver)
          }
        } catch (_) {
          const leaveDashboard = await ApiService.getLeaveDashboard(userId).catch(() => null)
          const dtrAccess = await ApiService.getDtrApproverAccess(userId).catch(() => null)
          const isLeaveApprover = Boolean(leaveDashboard?.data?.supervisor_id || (leaveDashboard?.data?.leave_for_approvals?.length > 0))
          const isDtrApprover = Boolean(dtrAccess?.data?.is_approver || dtrAccess?.data?.supervisor_id)
          isApprover = isLeaveApprover || isDtrApprover
        }

        this.isApproverUser = isApprover
        localStorage.setItem('is_approver_user', isApprover ? 'true' : 'false')
      } catch (e) {
        console.error('Error checking approver status:', e)
        this.isApproverUser = false
        localStorage.setItem('is_approver_user', 'false')
      }
    },
    async checkLoginClockReminder(isUpdateOnly = false) {
      const userId = this.userData?.id || JSON.parse(localStorage.getItem('user_data') || '{}').id
      if (!userId) return

      try {
        const { dtrApiService } = await import('../services/apiService.js')
        const res = await dtrApiService.getTodayStatus(userId)
        const statusData = res?.data || res

        if (statusData) {
          const isClockedIn = Boolean(
            statusData.is_clocked_in ||
            statusData.status === 'Clocked In' ||
            (statusData.am_in && !statusData.am_out) ||
            (statusData.pm_in && !statusData.pm_out)
          )
          localStorage.setItem('is_clocked_in', isClockedIn ? 'true' : 'false')

          if (isUpdateOnly) return

          const isJustLoggedIn = sessionStorage.getItem('ep_just_logged_in') === 'true'
          const isRemindedThisSession = sessionStorage.getItem('login_clock_reminded') === 'true'

          if (isJustLoggedIn || !isRemindedThisSession) {
            sessionStorage.removeItem('ep_just_logged_in')
            sessionStorage.setItem('login_clock_reminded', 'true')

            const { useToast } = await import('vue-toastification')
            const toast = useToast()

            if (statusData.is_work_suspended || statusData.status === 'Work Suspended') {
              const reason = statusData.work_suspension_reason ? `: ${statusData.work_suspension_reason}` : ''
              toast.info(`Work Suspension Notice: Work is suspended today${reason}. Stay safe!`, { timeout: 10000 })
            } else if (statusData.is_missed_log || statusData.status === 'Missed Log') {
              toast.warning('Attendance Alert: You have an unclosed punch / missed log from your previous shift.', { timeout: 8000 })
            } else if (statusData.is_late_for_clockin || (statusData.schedule_warning && !isClockedIn)) {
              toast.error(statusData.schedule_warning || `Schedule Warning: You have NOT clocked in according to your schedule today! Scheduled start was ${statusData.scheduled_start_time || '08:00 AM'}.`, { timeout: 10000 })
            } else if (statusData.schedule_warning && isClockedIn) {
              toast.warning(statusData.schedule_warning, { timeout: 8000 })
            } else if (isClockedIn) {
              const timeLog = statusData.am_in || statusData.pm_in || ''
              toast.success(`Clock-In Status: You are currently CLOCKED IN today${timeLog ? ' (Logged: ' + timeLog + ')' : ''}. Have a productive shift!`, { timeout: 7000 })
            } else {
              toast.info(`Attendance Reminder: You have NOT clocked in yet today (${statusData.today_date || 'Today'}). Please remember to log your attendance!`, { timeout: 8000 })
            }
          }
        }
      } catch (err) {
        console.error('Failed to fetch attendance status for reminder:', err)
      }
    },

    // ── Session Auto-Logout & Inactivity Control ─────────────────────────────
    checkIsClockedIn() {
      return (
        localStorage.getItem('is_clocked_in') === 'true' ||
        localStorage.getItem('user_is_clocked_in') === 'true' ||
        Boolean(this.userData?.is_clocked_in)
      )
    },
    resetInactivityTimer() {
      if (this.inactivityTimer) clearTimeout(this.inactivityTimer)

      this.inactivityTimer = setTimeout(() => {
        this.handleInactivityLogout('inactivity')
      }, this.INACTIVITY_TIMEOUT_MS)
    },
    async handleInactivityLogout(reason = 'inactivity') {
      // Only auto-logout if the user is actually logged in
      if (!localStorage.getItem('auth_token')) return
      try {
        const ApiService = (await import('../services/api.js')).default
        await ApiService.logout()
      } catch (e) {
        // ignore errors; still clear state
      } finally {
        localStorage.setItem('ep_logout_event', Date.now().toString())
        localStorage.removeItem('ep_logout_event')
        localStorage.removeItem('auth_token')
        localStorage.removeItem('temp_token')
        localStorage.removeItem('user_data')
        localStorage.removeItem('user_tab_access')
        localStorage.removeItem('session_start_time')
        this.$router.push(`/login?reason=${reason}`)
      }
    },
    // ───────────────────────────────────────────────────────────────────────
    toBool(val) {
      if (val === true || val === 1) return true
      if (val === false || val === 0 || val === null || typeof val === 'undefined') return false
      if (typeof val === 'string') {
        const v = val.trim().toLowerCase()
        return v === '1' || v === 'true' || v === 'yes' || v === 'y'
      }
      return !!val
    },
    toggleTheme() {
      this.isDarkMode = !this.isDarkMode
      localStorage.setItem('portal_theme', this.isDarkMode ? 'dark' : 'light')
      if (this.isDarkMode) {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    },

    recalculateAccessFlags() {
      const isAdmin = this.toBool(this.userData?.is_admin)
      this.hasHrmAccess = this.toBool(this.userData?.with_hrm_access) || isAdmin
      this.hasHrtAccess = this.toBool(this.userData?.with_hrt_access) || isAdmin
      this.hasHrpAccess = this.toBool(this.userData?.with_hrp_access) || isAdmin
      this.hasCpmAccess = this.toBool(this.userData?.with_cpm_access) || isAdmin
    },

    getUserInitials(name) {
      if (!name) return 'EP'
      const parts = String(name).trim().split(/\s+/)
      if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
      }
      return name.substring(0, 2).toUpperCase()
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
      
      const isDirectlyAllowed = allowedKeys.includes(k) || allowedNames.includes(k)
      if (isDirectlyAllowed) return true

      if (k === 'leave management') {
        const fallbacks = ['leave & time management', 'leave and time management', 'leaves']
        return fallbacks.some(f => allowedKeys.includes(f) || allowedNames.includes(f))
      }
      if (k === 'time and attendance' || k === 'time & attendance') {
        const fallbacks = ['leave & time management', 'leave and time management', 'daily time record', 'timekeeping']
        return fallbacks.some(f => allowedKeys.includes(f) || allowedNames.includes(f))
      }

      return false
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
        '/overtime-monitoring': 'Overtime Management',
        '/payslip': 'Payslip',
        '/dtr': 'Daily Time Record',
        '/announcements': 'Announcements',
        '/holidays/calendar': 'Holiday Calendar',
        '/ipcr': 'IPCR',
        '/ipcr/agency-head-approval': 'IPCR HoA Approval',
        '/ipcr/hr-recalibration': 'IPCR HR Recalibration',
        '/applicants': ' ',
        '/panel-interview-ratings': 'Interview Ratings (Panels)',
        '/leave-time': 'Leave & Time Management',
        '/training-records': 'Training Records',
        '/wfh-application': 'Work From Home Applications',
        '/document-requests': 'Document Requests',
        '/downloadables': 'Downloadables'
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
        '/announcements': 'View important announcements and updates',
        '/holidays/calendar': 'View upcoming and declared holidays for the year',
        '/ipcr': 'Individual Performance Commitment and Review',
        '/ipcr/agency-head-approval': 'Approve IPCR records after supervisor calibration',
        '/ipcr/hr-recalibration': 'HR recalibration queue after Head of Agency approval',
        '/opcr': 'Office Performance Commitment and Review',
        '/dpcr': 'Department Performance Commitment and Review',
        '/applicants': 'Monitor and manage job applicants',
        '/panel-interview-ratings': 'Submit interview ratings for assigned applicants',
        '/leave-time': ' Manage leave & time management',
        '/training-records': 'Manage training records',
        '/wfh-application': 'Manage work from home applications',
        '/document-requests': 'Manage document requests',
        '/downloadables': 'Download forms and templates'
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
          // Reset avatar error flag
          this.hasAvatarError = false

          // Update localStorage with fresh user data
          localStorage.setItem('user_data', JSON.stringify(response.data.user))
          
          // Update component data
          this.userData = response.data.user
          
          // Recalculate access flags
          this.recalculateAccessFlags()
          this.fetchUserPhoto()
          
          return true
        }
        return false
      } catch (error) {
        console.error('Failed to refresh user data:', error)
        return false
      }
    },
    handlePhotoUpdated(event) {
      if (event?.detail?.photo) {
        this.userData = {
          ...this.userData,
          photo: event.detail.photo
        }
        localStorage.setItem('user_data', JSON.stringify(this.userData))
        this.hasAvatarError = false
      } else {
        this.fetchUserPhoto()
      }
    },
    async fetchUserPhoto() {
      if (this.userData?.id) {
        try {
          const ApiService = (await import('../services/api.js')).default
          const response = await ApiService.getEmployee201File(this.userData.id)
          if (response?.success && response.data?.info && response.data.info.length > 0) {
            const empPhoto = response.data.info[0].photo
            if (empPhoto) {
              // Clear any stale photo first so no wrong image leaks through
              this.userData = { ...this.userData, photo: null }
              await this.$nextTick()
              this.userData = { ...this.userData, photo: empPhoto }
              localStorage.setItem('user_data', JSON.stringify(this.userData))
              this.hasAvatarError = false
            }
          }
        } catch (e) {
          console.warn('Could not fetch user profile photo:', e)
        } finally {
          // Always unlock avatar display after the API call completes
          this.photoReady = true
        }
      } else {
        this.photoReady = true
      }
    },
    dismissReminder() {
      this.isReminderDismissed = true
      sessionStorage.setItem('shift_reminder_dismissed', 'true')
    },
    // Feature #2: Quick clock out from floating shift reminder banner
    async quickClockOut() {
      this.isQuickClockingOut = true
      try {
        const { dtrApiService } = await import('../services/apiService.js')
        const payload = {
          user_id: this.userData?.id,
          action: 'out',
          reason: 'Quick clock out from reminder banner'
        }
        await dtrApiService.webClockPunch(payload)
        localStorage.setItem('is_clocked_in', 'false')
        this.isReminderDismissed = true
        sessionStorage.setItem('shift_reminder_dismissed', 'true')
        if (this.$toast) this.$toast.success('Successfully Clocked Out!')
      } catch (e) {
        if (this.$toast) this.$toast.error('Failed to Clock Out. Redirecting to clock terminal...')
        this.$router.push('/time-attendance')
      } finally {
        this.isQuickClockingOut = false
      }
    },
    // Feature #1: Guarded Logout Modal Logic
    logout() {
      if (this.checkIsClockedIn()) {
        this.showLogoutGuardModal = true
      } else {
        this.performActualLogout()
      }
    },
    async clockOutAndLogout() {
      this.isLoggingOutWithPunch = true
      try {
        const { dtrApiService } = await import('../services/apiService.js')
        const payload = {
          user_id: this.userData?.id,
          action: 'out',
          reason: 'Clock out prior to logout guard'
        }
        await dtrApiService.webClockPunch(payload)
        localStorage.setItem('is_clocked_in', 'false')
      } catch (e) {
        console.warn('Clock out failed during logout guard:', e)
      } finally {
        this.isLoggingOutWithPunch = false
        this.showLogoutGuardModal = false
        await this.performActualLogout()
      }
    },
    async performActualLogout() {
      try {
        // Revoke the token server-side so the session is immediately invalidated.
        const ApiService = (await import('../services/api.js')).default
        await ApiService.logout()
      } catch (e) {
        // Ignore network errors — still clear local state and redirect.
      } finally {
        // Broadcast logout to all other open tabs of this portal.
        localStorage.setItem('ep_logout_event', Date.now().toString())
        localStorage.removeItem('ep_logout_event')

        // Clear local session data.
        localStorage.removeItem('auth_token')
        localStorage.removeItem('temp_token')
        localStorage.removeItem('user_data')
        localStorage.removeItem('user_tab_access')
        localStorage.removeItem('is_approver_user')
        localStorage.removeItem('session_start_time')

        // Redirect to login
        this.$router.push('/login')
      }
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
  scrollbar-color: #b3b3b3 transparent;
}

.sidebar-scroll::-webkit-scrollbar {
  width: 6px;
}

.sidebar-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
  background-color: #132131;
  border-radius: 999px;
}

.router-link-active svg,
a:hover svg,
button:hover svg {
  transform: translateZ(0);
}
</style>