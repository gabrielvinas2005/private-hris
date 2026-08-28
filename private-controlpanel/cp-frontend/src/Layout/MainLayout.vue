<template>
  <div :class="[isDarkMode ? 'dark bg-[#0B1120] text-slate-100' : 'bg-[#F3F5FA] text-slate-900', 'flex min-h-screen font-sans antialiased']">
    <!-- Sidebar Navigation — matching E-Portal styling -->
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
          <div v-if="showLogo && companyLogoSrc" :class="[isDarkMode ? 'bg-white/10' : 'bg-slate-100 border border-slate-200/70', 'flex items-center justify-center overflow-hidden w-12 h-12 rounded-2xl']">
            <img :src="companyLogoSrc" :alt="companyName" class="object-contain w-full h-full" @error="onLogoError" />
          </div>
          <div v-else class="flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-lg tracking-tight shadow-lg shadow-[#3B5EFF]/30">
            HR
          </div>
          <div v-if="!isSidebarCollapsed" class="min-w-0 w-full">
            <h1 :class="[isDarkMode ? 'text-white' : 'text-slate-900', 'text-base font-bold tracking-tight truncate']">{{ companyName }}</h1>
            <p :class="[isDarkMode ? 'text-slate-500' : 'text-slate-400', 'text-[11px] font-semibold uppercase tracking-wider truncate mt-0.5']">Control Panel</p>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="flex-1 px-3.5 py-5 space-y-1 overflow-y-auto sidebar-scroll">
        <!-- Dashboard / Control Panel Home -->
        <RouterLink
          to="/"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Menu /></el-icon>
          <span v-if="!isSidebarCollapsed">Control Panel</span>
        </RouterLink>

        <!-- User List -->
        <RouterLink
          to="/users"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/users') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><User /></el-icon>
          <span v-if="!isSidebarCollapsed">User List</span>
        </RouterLink>

        <!-- User Activities -->
        <RouterLink
          to="/activity"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5', 
            isActive('/activity') 
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25' 
              : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!isSidebarCollapsed">User Activities</span>
        </RouterLink>

        <!-- HR Setup Collapsible Accordion -->
        <div class="pt-1">
          <button
            @click="toggleHr"
            class="w-full flex items-center justify-between py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
            :class="[
              isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
              isHrActive 
                ? (isDarkMode ? 'text-[#7C96FF] bg-[#3B5EFF]/10 font-semibold' : 'text-[#3B5EFF] bg-[#3B5EFF]/[0.07] font-semibold')
                : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
            ]"
          >
            <div class="flex items-center space-x-3 min-w-0">
              <el-icon class="text-lg flex-shrink-0"><Setting /></el-icon>
              <span v-if="!isSidebarCollapsed" class="truncate">HR Setup</span>
            </div>
            <svg v-if="!isSidebarCollapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': openHr }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <el-collapse-transition>
            <div v-show="!isSidebarCollapsed && openHr" class="pl-3 pr-1 mt-1 space-y-1 border-l-2 border-slate-200/70 dark:border-slate-800 ml-4">
              <RouterLink to="/hr-setup/company" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/company') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><OfficeBuilding /></el-icon><span>Company</span></RouterLink>
              <RouterLink to="/hr-setup/branch" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/branch') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><OfficeBuilding /></el-icon><span>Branch</span></RouterLink>
              <RouterLink to="/hr-setup/office" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/office') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><OfficeBuilding /></el-icon><span>Department</span></RouterLink>
              <RouterLink to="/hr-setup/division" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/division') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Division</span></RouterLink>
              <RouterLink to="/hr-setup/section" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/section') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Section</span></RouterLink>
              <RouterLink to="/hr-setup/employment-type" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/employment-type') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Tickets /></el-icon><span>Employment Type</span></RouterLink>
              <RouterLink to="/hr-setup/specialization" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/specialization') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Specialization</span></RouterLink>
              <RouterLink to="/hr-setup/position" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/position') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Postcard /></el-icon><span>Position</span></RouterLink>
              <RouterLink to="/hr-setup/promotion-types" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/promotion-types') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Promotion Types</span></RouterLink>
              <RouterLink to="/hr-setup/off-boarding-types" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/off-boarding-types') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Remove /></el-icon><span>Off Boarding Types</span></RouterLink>
              <RouterLink to="/hr-setup/document-no" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/document-no') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Document /></el-icon><span>Document No.</span></RouterLink>
              <RouterLink to="/hr-setup/document-type" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/document-type') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Document /></el-icon><span>Document Type</span></RouterLink>
              <RouterLink to="/hr-setup/competencies" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/competencies') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Competencies</span></RouterLink>
              <RouterLink to="/hr-setup/downloadable-docs" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/downloadable-docs') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Document /></el-icon><span>Downloadable Docs</span></RouterLink>
              <RouterLink to="/hr-setup/interview-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/interview-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Document /></el-icon><span>Interview Setup</span></RouterLink>
              <RouterLink to="/hr-setup/applicant-documents" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/hr-setup/applicant-documents') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Document /></el-icon><span>Applicant Docs</span></RouterLink>
            </div>
          </el-collapse-transition>
        </div>

        <!-- Timekeeping Setup Collapsible Accordion -->
        <div class="pt-1">
          <button
            @click="openTk = !openTk"
            class="w-full flex items-center justify-between py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
            :class="[
              isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
              isTkActive 
                ? (isDarkMode ? 'text-[#7C96FF] bg-[#3B5EFF]/10 font-semibold' : 'text-[#3B5EFF] bg-[#3B5EFF]/[0.07] font-semibold')
                : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
            ]"
          >
            <div class="flex items-center space-x-3 min-w-0">
              <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
              <span v-if="!isSidebarCollapsed" class="truncate">Timekeeping Setup</span>
            </div>
            <svg v-if="!isSidebarCollapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': openTk }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <el-collapse-transition>
            <div v-show="!isSidebarCollapsed && openTk" class="pl-3 pr-1 mt-1 space-y-1 border-l-2 border-slate-200/70 dark:border-slate-800 ml-4">
              <RouterLink to="/timekeeping-setup/overtime-types" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/timekeeping-setup/overtime-types') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Overtime Types</span></RouterLink>
              <RouterLink to="/timekeeping-setup/holiday-types" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/timekeeping-setup/holiday-types') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Holiday Types</span></RouterLink>
              <RouterLink to="/timekeeping-setup/holidays" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/timekeeping-setup/holidays') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Holidays</span></RouterLink>
              <RouterLink to="/timekeeping-setup/leave-types" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/timekeeping-setup/leave-types') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Leave Types</span></RouterLink>
              <RouterLink to="/timekeeping-setup/official-business-types" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/timekeeping-setup/official-business-types') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Tickets /></el-icon><span>Official Business Types</span></RouterLink>
              <RouterLink to="/timekeeping-setup/time-keeping" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/timekeeping-setup/time-keeping') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Timer /></el-icon><span>Logging Options</span></RouterLink>
              <RouterLink to="/timekeeping-setup/approvers" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/timekeeping-setup/approvers') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><User /></el-icon><span>Approvers Setup</span></RouterLink>
            </div>
          </el-collapse-transition>
        </div>

        <!-- Payroll Setup Collapsible Accordion -->
        <div class="pt-1">
          <button
            @click="togglePayroll"
            class="w-full flex items-center justify-between py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
            :class="[
              isSidebarCollapsed ? 'justify-center px-0' : 'px-3.5',
              isPayrollActive 
                ? (isDarkMode ? 'text-[#7C96FF] bg-[#3B5EFF]/10 font-semibold' : 'text-[#3B5EFF] bg-[#3B5EFF]/[0.07] font-semibold')
                : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')
            ]"
          >
            <div class="flex items-center space-x-3 min-w-0">
              <el-icon class="text-lg flex-shrink-0"><Money /></el-icon>
              <span v-if="!isSidebarCollapsed" class="truncate">Payroll Setup</span>
            </div>
            <svg v-if="!isSidebarCollapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': openPayroll }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <el-collapse-transition>
            <div v-show="!isSidebarCollapsed && openPayroll" class="pl-3 pr-1 mt-1 space-y-1 border-l-2 border-slate-200/70 dark:border-slate-800 ml-4">
              <RouterLink to="/payroll-setup/salary-schedule-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/salary-schedule-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Salary Schedule Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/tax-table-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/tax-table-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Tax Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/HDMF Table Setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/HDMF Table Setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>HDMF Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/Philhealth Table Setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/Philhealth Table Setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Collection /></el-icon><span>Philhealth Table Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/Salary Step Setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/Salary Step Setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Timer /></el-icon><span>Salary Step Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/salary-grade-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/salary-grade-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><Postcard /></el-icon><span>Salary Grade Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/income-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/income-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><User /></el-icon><span>Income Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/deduction-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/deduction-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><User /></el-icon><span>Deduction Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/deduction-priority-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/deduction-priority-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><User /></el-icon><span>Deduction Priority Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/payroll-interval-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/payroll-interval-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><User /></el-icon><span>Payroll Interval Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/payroll-cutoff-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/payroll-cutoff-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><User /></el-icon><span>Payroll Cut-off Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/uniform-and-clothing-allowance-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/uniform-and-clothing-allowance-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><User /></el-icon><span>Uniform Allowance Setup</span></RouterLink>
              <RouterLink to="/payroll-setup/overtime-tax-table-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[isActive('/payroll-setup/overtime-tax-table-setup') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : (isDarkMode ? 'text-slate-400 hover:text-white hover:bg-white/[0.06]' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100')]"><el-icon><User /></el-icon><span>Overtime Tax Setup</span></RouterLink>
            </div>
          </el-collapse-transition>
        </div>
      </nav>

      <!-- Sidebar Footer -->
      <div :class="[isDarkMode ? 'border-white/[0.06] bg-[#0D1425]/80' : 'border-slate-200/70 bg-slate-50/80', 'p-4 border-t']">
        <div class="flex items-center" :class="isSidebarCollapsed ? 'justify-center' : 'justify-between'">
          <button 
            @click="isSidebarCollapsed = !isSidebarCollapsed" 
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
            <p :class="[isDarkMode ? 'text-slate-400' : 'text-slate-600', 'text-xs font-medium']">Private HRIS</p>
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
            <h2 class="text-xl font-bold tracking-tight text-slate-900">{{ pageTitle }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">{{ pageDescription }}</p>
          </div>

          <!-- Right Side: Navigation & User Controls -->
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707M6.343 17.657l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </button>

            <!-- Portal Quick Link -->
            <a
              href="http://localhost:5171"
              class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl transition-all space-x-2 bg-slate-100 hover:bg-slate-200 text-slate-700"
            >
              <svg class="w-4 h-4 text-[#3B5EFF]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Employee Portal</span>
            </a>

            <!-- User Info Badge -->
            <div class="flex items-center px-3.5 py-1.5 border rounded-2xl space-x-2.5 bg-slate-50/80 border-slate-200/70">
              <img v-if="userPhoto" :src="userPhoto" alt="Profile" class="w-8 h-8 rounded-full object-cover shadow-sm border border-slate-200" />
              <div v-else class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-xs shadow-sm shadow-[#3B5EFF]/30">
                {{ userInitials }}
              </div>
              <div class="text-left hidden sm:block">
                <p class="text-xs font-semibold leading-tight truncate max-w-[120px] text-slate-800">{{ userName }}</p>
                <p class="text-[10px] font-medium leading-tight truncate max-w-[120px] text-slate-500">Administrator</p>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Main Slot Content -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 overflow-y-auto bg-[#F3F5FA]">
        <div class="p-4 bg-white border border-slate-200/70 shadow-sm rounded-2xl sm:p-6">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import { ElIcon, ElCollapseTransition } from 'element-plus'
import { 
  Menu, User, Timer, Setting, OfficeBuilding, Collection, 
  Tickets, Postcard, Remove, Document, Money 
} from '@element-plus/icons-vue'
import { useCompany } from '../composables/useCompany.js'

const { primaryCompany, fetchCompanies, getLogoUrl } = useCompany()

const route = useRoute()
const showLogo = ref(true)
const companyName = computed(() => primaryCompany.value?.name?.trim() || 'Control Panel')
const companyLogoSrc = computed(() => getLogoUrl(primaryCompany.value))
const companyLogo = computed(() => (showLogo.value ? companyLogoSrc.value : null))

function onLogoError() {
  showLogo.value = false
}

watch(primaryCompany, () => {
  showLogo.value = true
})

onMounted(() => {
  fetchCompanies()
})

// Sidebar & Accordion State
const isSidebarCollapsed = ref(false)
const openHr = ref(false)
const openTk = ref(false)
const openPayroll = ref(false)

const isHrActive = computed(() => route.path.startsWith('/hr-setup'))
const isTkActive = computed(() => route.path.startsWith('/timekeeping-setup'))
const isPayrollActive = computed(() => route.path.startsWith('/payroll-setup'))

function toggleHr() { openHr.value = !openHr.value }
function togglePayroll() { openPayroll.value = !openPayroll.value }

watchEffect(() => { if (isHrActive.value) openHr.value = true })
watchEffect(() => { if (isTkActive.value) openTk.value = true })
watchEffect(() => { if (isPayrollActive.value) openPayroll.value = true })

const isActive = (path) => {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

// Dark Mode Theme Switcher
const isDarkMode = ref(localStorage.getItem('theme') === 'dark')
const toggleTheme = () => {
  isDarkMode.value = !isDarkMode.value
  localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light')
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

const userData = ref(JSON.parse(localStorage.getItem('user_data') || '{}'))

onMounted(async () => {
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  }
  try {
    const ApiService = (await import('../Services/api.js')).default
    const res = await ApiService.getCurrentUser()
    if (res?.success && res?.data?.user) {
      userData.value = res.data.user
      localStorage.setItem('user_data', JSON.stringify(res.data.user))
    }
  } catch (_) {}

  // ── Session Auto-Logout & Inactivity Control ─────────────────────────────
  if (!localStorage.getItem('session_start_time')) {
    localStorage.setItem('session_start_time', Date.now().toString())
  }

  const INACTIVITY_TIMEOUT_MS = 5 * 60 * 60 * 1000 // 5 hours of inactivity

  let inactivityTimer = null

  async function forceLogout() {
    if (!localStorage.getItem('auth_token')) return
    try {
      const ApiService = (await import('../Services/api.js')).default
      await ApiService.logout()
    } catch (_) {}
    localStorage.setItem('cp_logout_event', Date.now().toString())
    localStorage.removeItem('cp_logout_event')
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user_data')
    localStorage.removeItem('session_start_time')
    window.location.reload()
  }

  function resetTimer() {
    if (inactivityTimer) clearTimeout(inactivityTimer)
    inactivityTimer = setTimeout(forceLogout, INACTIVITY_TIMEOUT_MS)
  }

  const activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click']
  activityEvents.forEach(evt => document.addEventListener(evt, resetTimer, { passive: true }))
  
  resetTimer()

  onBeforeUnmount(() => {
    if (inactivityTimer) clearTimeout(inactivityTimer)
    activityEvents.forEach(evt => document.removeEventListener(evt, resetTimer))
  })
  // ────────────────────────────────────────────────────────────────────────

  // ── Cross-tab logout listener ────────────────────────────────────────────
  function onStorageChange(event) {
    if (
      (event.key === 'cp_logout_event' || event.key === 'ep_logout_event') &&
      event.newValue
    ) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      window.location.reload()
    }
    if (event.key === 'auth_token' && !event.newValue) {
      window.location.reload()
    }
  }
  window.addEventListener('storage', onStorageChange)
  onBeforeUnmount(() => window.removeEventListener('storage', onStorageChange))
  // ────────────────────────────────────────────────────────────────────────
})

const userName = computed(() => userData.value.name || userData.value.email || 'Admin User')
const userPhoto = computed(() => userData.value.photo || null)
const userInitials = computed(() => {
  const name = userName.value
  if (!name) return 'AU'
  const parts = name.split(' ')
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return name.substring(0, 2).toUpperCase()
})

// Dynamic Header Page Title & Description
const pageTitles = {
  '/': { title: 'Control Panel', desc: 'System configuration and administrative management overview' },
  '/users': { title: 'User List', desc: 'Manage system users, access credentials, and role assignments' },
  '/activity': { title: 'User Activities', desc: 'Audit system activity logs and user transaction histories' },
  '/hr-setup/company': { title: 'Company Setup', desc: 'Configure company profile and master parameters' },
  '/hr-setup/branch': { title: 'Branch Setup', desc: 'Manage organizational branches and locations' },
  '/hr-setup/office': { title: 'Department Setup', desc: 'Configure departments and organizational offices' },
  '/hr-setup/division': { title: 'Division Setup', desc: 'Manage divisions across corporate units' },
  '/hr-setup/section': { title: 'Section Setup', desc: 'Configure operational sections within departments' },
  '/hr-setup/employment-type': { title: 'Employment Type', desc: 'Define employee job types and classification schemes' },
  '/hr-setup/specialization': { title: 'Specialization', desc: 'Manage technical and job specialization fields' },
  '/hr-setup/position': { title: 'Position Setup', desc: 'Manage employee positions and job titles' },
  '/hr-setup/promotion-types': { title: 'Promotion Types', desc: 'Configure career movement and promotion categories' },
  '/hr-setup/off-boarding-types': { title: 'Off-Boarding Types', desc: 'Manage separation and exit processing types' },
  '/hr-setup/document-no': { title: 'Document No. Setup', desc: 'Configure system document numbering formats' },
  '/hr-setup/document-type': { title: 'Document Type', desc: 'Manage document types and file categories' },
  '/hr-setup/competencies': { title: 'Competencies Setup', desc: 'Manage competency standards and evaluation frameworks' },
  '/hr-setup/downloadable-docs': { title: 'Downloadable Docs', desc: 'Manage downloadable forms and templates' },
  '/hr-setup/interview-setup': { title: 'Interview Setup', desc: 'Configure applicant interview templates and criteria' },
  '/hr-setup/applicant-documents': { title: 'Applicant Docs', desc: 'Set required applicant documents and attachments' },
  '/timekeeping-setup/overtime-types': { title: 'Overtime Types', desc: 'Configure overtime classifications and multipliers' },
  '/timekeeping-setup/holiday-types': { title: 'Holiday Types', desc: 'Manage holiday categories and pay rates' },
  '/timekeeping-setup/holidays': { title: 'Holidays Setup', desc: 'Configure company and statutory holiday calendars' },
  '/timekeeping-setup/leave-types': { title: 'Leave Types', desc: 'Manage employee leave benefits and allocations' },
  '/timekeeping-setup/official-business-types': { title: 'Official Business Types', desc: 'Configure official business request types' },
  '/timekeeping-setup/time-keeping': { title: 'Timekeeping Policy', desc: 'Set up timekeeping rules and attendance parameters' },
  '/timekeeping-setup/approvers': { title: 'Approvers Setup', desc: 'Configure approval workflows and escalation paths' },
  '/payroll-setup/salary-schedule-setup': { title: 'Salary Schedule Setup', desc: 'Configure salary schedules and pay scales' },
  '/payroll-setup/tax-table-setup': { title: 'Tax Table Setup', desc: 'Manage statutory withholding tax rates' },
  '/payroll-setup/HDMF Table Setup': { title: 'HDMF Table Setup', desc: 'Configure Pag-IBIG contribution tiers' },
  '/payroll-setup/Philhealth Table Setup': { title: 'PhilHealth Table Setup', desc: 'Manage PhilHealth contribution rates' },
  '/payroll-setup/Salary Step Setup': { title: 'Salary Step Increments', desc: 'Configure step increments per position grade' },
  '/payroll-setup/salary-grade-setup': { title: 'Salary Grade Setup', desc: 'Manage position salary grade levels' },
  '/payroll-setup/income-setup': { title: 'Income Setup', desc: 'Configure taxable and non-taxable income types' },
  '/payroll-setup/deduction-setup': { title: 'Deduction Setup', desc: 'Manage payroll deduction items and rules' },
  '/payroll-setup/deduction-priority-setup': { title: 'Deduction Priority', desc: 'Set deduction order of priority during payroll computation' },
  '/payroll-setup/payroll-interval-setup': { title: 'Payroll Interval Setup', desc: 'Manage payment frequency intervals' },
  '/payroll-setup/payroll-cutoff-setup': { title: 'Payroll Cut-off Setup', desc: 'Configure cutoff dates for payroll runs' },
  '/payroll-setup/uniform-and-clothing-allowance-setup': { title: 'Clothing Allowance Setup', desc: 'Manage uniform and clothing allowance rules' },
  '/payroll-setup/overtime-tax-table-setup': { title: 'Overtime Tax Setup', desc: 'Configure tax rules for overtime pay' }
}

const pageTitle = computed(() => {
  const path = route.path
  if (pageTitles[path]) return pageTitles[path].title
  return 'Control Panel'
})

const pageDescription = computed(() => {
  const path = route.path
  if (pageTitles[path]) return pageTitles[path].desc
  return 'Administrative portal and system settings'
})
</script>

<style scoped>
.sidebar-scroll::-webkit-scrollbar {
  width: 4px;
}

.sidebar-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.2);
  border-radius: 4px;
}

.sidebar-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.4);
}
</style>
