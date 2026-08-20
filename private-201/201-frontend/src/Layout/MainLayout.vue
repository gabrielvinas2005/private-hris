<template>
  <div class="flex min-h-screen bg-[#F3F5FA] text-slate-900 font-sans antialiased">
    <!-- Sidebar Navigation -->
    <aside :class="[
      collapsed ? 'w-20' : 'w-64 sm:w-72',
      'bg-white text-slate-700 border-slate-200/70 shadow-xl shadow-slate-200/50',
      'h-screen sticky top-0 z-40 flex flex-col transition-all duration-300 ease-in-out border-r overflow-y-auto'
    ]">
      <!-- Sidebar Header -->
      <div class="px-6 py-8 border-b border-slate-200/70">
        <div class="flex flex-col items-center text-center space-y-3">
          <div v-if="showLogo && companyLogoSrc" class="flex items-center justify-center overflow-hidden w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200/70">
            <img :src="companyLogoSrc" :alt="companyName" class="object-contain w-full h-full" @error="onLogoError" />
          </div>
          <div v-else class="flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-lg tracking-tight shadow-lg shadow-[#3B5EFF]/30">
            HR
          </div>
          <div v-if="!collapsed" class="min-w-0 w-full">
            <h1 class="text-base font-bold tracking-tight text-slate-900 truncate">{{ companyName }}</h1>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 truncate mt-0.5">HR Module</p>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="flex-1 px-3.5 py-5 space-y-1 overflow-y-auto sidebar-scroll">
        <!-- Main HR Navigation -->
        <RouterLink
          to="/hr"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path === '/hr'
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Menu /></el-icon>
          <span v-if="!collapsed">HR Module</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('Employee Records')"
          to="/employee-records"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/employee-records')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><User /></el-icon>
          <span v-if="!collapsed">Employee Records</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('Employee Assignments')"
          to="/employee-assignments"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/employee-assignments')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Employee Assignments</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('Off-Boarding')"
          to="/off-boarding"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/off-boarding')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Off-Boarding</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('Step Increment')"
          to="/step-increment"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path === '/step-increment'
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Step Increment</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('Step Increment Approval')"
          to="/step-increment-approval"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/step-increment-approval')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Step Increment Approval</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('Salary Adjustment')"
          to="/salary-adjustment"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/salary-adjustment')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Salary Adjustment</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('IPCR')"
          to="/ipcr"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/ipcr')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">IPCR</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('OPCR')"
          to="/opcr"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/opcr')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Document /></el-icon>
          <span v-if="!collapsed">OPCR</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('DPCR')"
          to="/dpcr"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/dpcr')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">DPCR</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('Update 201 Schedule')"
          to="/update-201-schedule"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/update-201-schedule')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Update 201 Schedule</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('Export Employee Data')"
          to="/export-employee-data"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/export-employee-data')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Export Employee Data</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('Vacant Position Posting')"
          to="/vacant-position-posting"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/vacant-position-posting')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Vacant Position Posting</span>
        </RouterLink>

        <RouterLink
          v-if="canAccessMenu('Length Of Service')"
          to="/length-of-service"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[
            collapsed ? 'justify-center px-0' : 'px-3.5',
            route.path.startsWith('/length-of-service')
              ? 'bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25'
              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
          ]"
        >
          <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
          <span v-if="!collapsed">Length Of Service</span>
        </RouterLink>

        <!-- HR Reports Accordion -->
        <div v-if="showHrReportsNav" class="pt-1">
          <button
            @click="toggleHr"
            class="w-full flex items-center justify-between py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
            :class="[
              collapsed ? 'justify-center px-0' : 'px-3.5',
              isHrActive ? 'text-[#3B5EFF] bg-[#3B5EFF]/[0.07] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            ]"
          >
            <div class="flex items-center space-x-3 min-w-0">
              <el-icon class="text-lg flex-shrink-0"><Setting /></el-icon>
              <span v-if="!collapsed" class="truncate">HR Reports</span>
            </div>
            <svg v-if="!collapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': openHr }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <el-collapse-transition>
            <div v-show="!collapsed && openHr" class="pl-3 pr-1 mt-1 space-y-1 border-l-2 border-slate-200/70 ml-4">
              <RouterLink v-if="canAccessMenu('Personal Data Sheet') || canAccessMenu('HR Reports')" to="/hr-reports/personal-data-sheet" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path.startsWith('/hr-reports/personal-data-sheet') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><OfficeBuilding /></el-icon><span>Personal Data Sheet</span></RouterLink>
              <RouterLink v-if="canAccessMenu('NOSI') || canAccessMenu('HR Reports')" to="/hr-reports/nosi" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path.startsWith('/hr-reports/nosi') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><OfficeBuilding /></el-icon><span>NOSI</span></RouterLink>
              <RouterLink v-if="canAccessMenu('NOSA') || canAccessMenu('HR Reports')" to="/hr-reports/nosa" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path.startsWith('/hr-reports/nosa') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><OfficeBuilding /></el-icon><span>NOSA</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Plantilla Report') || canAccessMenu('HR Reports')" to="/hr-reports/plantilla-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path.startsWith('/hr-reports/plantilla-report') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Medal /></el-icon><span>Plantilla Report</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Request for Publication') || canAccessMenu('HR Reports')" to="/hr-reports/request-for-publicaiton" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path.startsWith('/hr-reports/request-for-publicaiton') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Medal /></el-icon><span>Request for Publication</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Birthday Summary') || canAccessMenu('HR Reports')" to="/hr-reports/birthday-summary" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path.startsWith('/hr-reports/birthday-summary') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Medal /></el-icon><span>Birthday Summary</span></RouterLink>
              <RouterLink v-if="canAccessMenu('List of New Hires and Promotions') || canAccessMenu('HR Reports')" to="/hr-reports/newly-hired-and-promoted" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path.startsWith('/hr-reports/newly-hired-and-promoted') ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Medal /></el-icon><span>List of New Hires and Promotions</span></RouterLink>
            </div>
          </el-collapse-transition>
        </div>

        <!-- Certificates Accordion -->
        <div v-if="showCertificatesNav" class="pt-1">
          <button
            @click="openTk = !openTk"
            class="w-full flex items-center justify-between py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
            :class="[
              collapsed ? 'justify-center px-0' : 'px-3.5',
              route.path.startsWith('/certificates') ? 'text-[#3B5EFF] bg-[#3B5EFF]/[0.07] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            ]"
          >
            <div class="flex items-center space-x-3 min-w-0">
              <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
              <span v-if="!collapsed" class="truncate">Certificates</span>
            </div>
            <svg v-if="!collapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': openTk }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <el-collapse-transition>
            <div v-show="!collapsed && openTk" class="pl-3 pr-1 mt-1 space-y-1 border-l-2 border-slate-200/70 ml-4">
              <RouterLink v-if="canAccessMenu('Employee Certificate') || canAccessMenu('Certificates')" to="/certificates/employee" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/employee' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Employee Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('COS Certificate') || canAccessMenu('Certificates')" to="/certificates/cos-certificate" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/cos-certificate' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>COS Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('COS Contract') || canAccessMenu('Certificates')" to="/certificates/cos-contract" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/cos-contract' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>COS Contract</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Employee Certificate Of Compensation') || canAccessMenu('Certificates')" to="/certificates/compensation" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/compensation' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Certificate Of Compensation</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Medical Certificate') || canAccessMenu('Certificates')" to="/certificates/medical" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/medical' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Medical Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Service Record') || canAccessMenu('Certificates')" to="/certificates/service-record" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/service-record' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Service Record</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Acceptance of Resignation') || canAccessMenu('Certificates')" to="/certificates/acceptance-resignation" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/acceptance-resignation' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Tickets /></el-icon><span>Acceptance of Resignation</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Acceptance of Retirement') || canAccessMenu('Certificates')" to="/certificates/acceptance-of-retirement" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/acceptance-of-retirement' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><User /></el-icon><span>Acceptance of Retirement</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Last Day of Service') || canAccessMenu('Certificates')" to="/certificates/last-day-service" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/last-day-service' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Timer /></el-icon><span>Last Day of Service</span></RouterLink>
              <RouterLink v-if="canAccessMenu('No Pending Certificates') || canAccessMenu('Certificates')" to="/certificates/no-pending" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/no-pending' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Postcard /></el-icon><span>No Pending Certificates</span></RouterLink>
              <RouterLink v-if="canAccessMenu('LBP ATM Request Certificate') || canAccessMenu('Certificates')" to="/certificates/atm-request" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/atm-request' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><User /></el-icon><span>ATM Request Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Appearance') || canAccessMenu('Certificates')" to="/certificates/appearance" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/appearance' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><User /></el-icon><span>Certificate of Appearance</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Clearance Certificate') || canAccessMenu('Certificates')" to="/certificates/clearance-certificate" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/clearance-certificate' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><User /></el-icon><span>Clearance Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Transfer of Leave Credit') || canAccessMenu('Certificates')" to="/certificates/transfer-of-leave-credit" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/transfer-of-leave-credit' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><User /></el-icon><span>Transfer Leave Credit</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Acceptance Letter Intern') || canAccessMenu('Certificates')" to="/certificates/acceptance-letter-intern" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/acceptance-letter-intern' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><User /></el-icon><span>Acceptance Intern</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Completion') || canAccessMenu('Certificates')" to="/certificates/cert-of-completion" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/cert-of-completion' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><User /></el-icon><span>Cert of Completion</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Last Salary') || canAccessMenu('Certificates')" to="/certificates/cert-of-last-salary" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/cert-of-last-salary' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><User /></el-icon><span>Cert of Last Salary</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Salary Deduction') || canAccessMenu('Certificates')" to="/certificates/cert-of-salary-deduction" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/cert-of-salary-deduction' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><User /></el-icon><span>Cert Salary Deduction</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Certificate of Rendered Service') || canAccessMenu('Certificates')" to="/certificates/cert-of-rendered-service" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/certificates/cert-of-rendered-service' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><User /></el-icon><span>Cert Rendered Service</span></RouterLink>
            </div>
          </el-collapse-transition>
        </div>

        <!-- Hiring Accordion -->
        <div v-if="showRecruitmentNav" class="pt-1">
          <button
            @click="openRecruitment = !openRecruitment"
            class="w-full flex items-center justify-between py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
            :class="[
              collapsed ? 'justify-center px-0' : 'px-3.5',
              route.path.startsWith('/recruitment') && !route.path.startsWith('/recruitment-reports') ? 'text-[#3B5EFF] bg-[#3B5EFF]/[0.07] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            ]"
          >
            <div class="flex items-center space-x-3 min-w-0">
              <el-icon class="text-lg flex-shrink-0"><Timer /></el-icon>
              <span v-if="!collapsed" class="truncate">Recruitment</span>
            </div>
            <svg v-if="!collapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': openRecruitment }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <el-collapse-transition>
            <div v-show="!collapsed && openRecruitment" class="pl-3 pr-1 mt-1 space-y-1 border-l-2 border-slate-200/70 ml-4">
              <RouterLink v-if="canAccessMenu('Applicant Qualification') || canAccessMenu('Recruitment')" to="/recruitment/applicant-qualification" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment/applicant-qualification' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Applicant Qualification</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Applicant Shortlisting') || canAccessMenu('Recruitment')" to="/recruitment/applicant-shortlisting" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment/applicant-shortlisting' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Applicant Shortlisting</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Applicant Progress') || canAccessMenu('Recruitment')" to="/recruitment/applicants-records" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment/applicants-records' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Applicant Progress</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Examination Setup') || canAccessMenu('Recruitment')" to="/recruitment/examination" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment/examination' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Examination Setup</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Interview Setup') || canAccessMenu('Recruitment')" to="/recruitment/panel-interview-setup" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment/panel-interview-setup' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Interview Setup</span></RouterLink>
              <RouterLink v-if="canAccessMenu('HRMPSB Deliberation') || canAccessMenu('Recruitment')" to="/recruitment/hrdd-perf-review" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment/hrdd-perf-review' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>HRMPSB Deliberation</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Administrator Selection') || canAccessMenu('Recruitment')" to="/recruitment/administrator-selection" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment/administrator-selection' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Administrator Selection</span></RouterLink>
            </div>
          </el-collapse-transition>
        </div>

        <!-- Recruitment Reports Accordion -->
        <div v-if="showRecruitmentReportsNav" class="pt-1">
          <button
            @click="openPayroll = !openPayroll"
            class="w-full flex items-center justify-between py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
            :class="[
              collapsed ? 'justify-center px-0' : 'px-3.5',
              route.path.startsWith('/recruitment-reports') ? 'text-[#3B5EFF] bg-[#3B5EFF]/[0.07] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'
            ]"
          >
            <div class="flex items-center space-x-3 min-w-0">
              <el-icon class="text-lg flex-shrink-0"><Money /></el-icon>
              <span v-if="!collapsed" class="truncate">Recruitment Reports</span>
            </div>
            <svg v-if="!collapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': openPayroll }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <el-collapse-transition>
            <div v-show="!collapsed && openPayroll" class="pl-3 pr-1 mt-1 space-y-1 border-l-2 border-slate-200/70 ml-4">
              <RouterLink v-if="canAccessMenu('Appointment Certificate') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/appointment-cert" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment-reports/appointment-cert' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Appointment Certificate</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Assumption of Duty') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/assumption-of-duty" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment-reports/assumption-of-duty' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Assumption of Duty</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Oath of Office') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/oath-of-office" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment-reports/oath-of-office' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Oath of Office</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Acceptance Letter') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/acceptance-letter" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment-reports/acceptance-letter' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Acceptance Letter</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Work Experience Sheet') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/work-experience-sheet" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment-reports/work-experience-sheet' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Work Experience Sheet</span></RouterLink>
              <RouterLink v-if="canAccessMenu('Position Description') || canAccessMenu('Recruitment Reports')" to="/recruitment-reports/position-description" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/recruitment-reports/position-description' ? 'bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"><el-icon><Collection /></el-icon><span>Position Description</span></RouterLink>
            </div>
          </el-collapse-transition>
        </div>
      </nav>

      <!-- Sidebar Footer -->
      <div class="p-4 border-t border-slate-200/70 bg-slate-50/80">
        <div class="flex items-center" :class="collapsed ? 'justify-center' : 'justify-between'">
          <button 
            @click="collapsed = !collapsed" 
            class="p-2 transition rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-200/60"
            :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
          >
            <svg v-if="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
          <div v-if="!collapsed" class="text-right">
            <p class="text-xs font-medium text-slate-600">{{ companyName }}</p>
            <p class="text-[11px] text-slate-400">v1.0.0</p>
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
            <h2 class="text-xl font-bold tracking-tight text-slate-900">
              <slot name="header">HR Module</slot>
            </h2>
            <p class="mt-0.5 text-sm text-slate-500">Employee Master Data & HR Lifecycle Management</p>
          </div>

          <!-- Right Side Controls -->
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
import { ref, watchEffect, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { ElIcon, ElCollapseTransition, ElTooltip } from 'element-plus'
import { Menu, User, Timer, Setting, OfficeBuilding, Collection, Medal, Tickets, Postcard, Remove, Star, Document, TrendCharts, Notebook, Money, Fold, Expand } from '@element-plus/icons-vue'
import { authApi, accessRightsApi } from '@/services/api'
import { useCompany } from '@/composable/useCompany.js'

const { primaryCompany, fetchCompanies, getLogoUrl } = useCompany()

const showLogo = ref(true)
const companyName = computed(() => primaryCompany.value?.name || 'HR Module')
const companyLogoSrc = computed(() => getLogoUrl(primaryCompany.value))

function onLogoError() {
  showLogo.value = false
}

watch(primaryCompany, () => {
  showLogo.value = true
})

const route = useRoute()

const openHr = ref(false)
const openTk = ref(false)
const openPayroll = ref(false)
const openRecruitment = ref(false)
const HRreport = ref(false)
const collapsed = ref(false)

// Access-controlled menus (hide sidebar items based on DB "access" table)
const accessLoaded = ref(false)
const allowedMenuNameSet = ref(new Set())
const ACCESS_CACHE_KEY = 'hr_module_sidebar_access_v1'
const SIDEBAR_COLLAPSED_KEY = 'hr_module_sidebar_collapsed_v1'

const normalizeMenuName = (s) => String(s ?? '').toLowerCase().replace(/[^a-z0-9]/g, '')

const readSidebarCache = () => {
  try {
    const raw = localStorage.getItem(ACCESS_CACHE_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed?.menus)) return null
    return parsed
  } catch {
    return null
  }
}

const writeSidebarCache = (userId, menus) => {
  try {
    localStorage.setItem(
      ACCESS_CACHE_KEY,
      JSON.stringify({
        userId: userId ?? null,
        menus: Array.from(menus || []),
        updatedAt: Date.now()
      })
    )
  } catch {
    // no-op
  }
}

const canAccessMenu = (menuName) => {
  // Wait for access to load before showing any permission-gated UI.
  if (!accessLoaded.value) return false
  return allowedMenuNameSet.value.has(normalizeMenuName(menuName))
}

const canShowAny = (menuLabels) => menuLabels.some((label) => canAccessMenu(label))

const hrReportsMenuLabels = [
  'Personal Data Sheet',
  'NOSI',
  'NOSA',
  'Terminal Leave Endorsement',
  'Plantilla Report',
  'Request for Publication',
  'Birthday Summary'
]

const recruitmentMenuLabels = [
  'Applicant Qualification',
  'Applicant Shortlisting',
  'Applicant Progress',
  'Examination Setup',
  'Interview Setup',
  'HRMPSB Deliberation',
  'Administrator Selection'
]

const certificatesMenuLabels = [
  'Employee Certificate',
  'COS Certificate',
  'COS Contract',
  'Employee Certificate Of Compensation',
  'Medical Certificate',
  'Service Record',
  'Acceptance of Resignation',
  'Acceptance of Retirement',
  'Certificate of Last Day of Service',
  'No Pending Certificates',
  'LBP ATM Request Certificate',
  'Certificate of Appearance',
  'Clearance Certificate',
  'Transfer of Leave Credit',
  'Acceptance Letter Intern',
  'Certificate of Completion',
  'Certificate of Last Salary',
  'Certificate of Salary Deduction',
  'Certificate of Rendered Service'
]

const recruitmentReportsMenuLabels = [
  'Appointment Certificate',
  'Assumption of Duty',
  'Oath of Office',
  'Acceptance Letter',
  'Work Experience Sheet',
  'Position Description'
]

const cachedSidebar = readSidebarCache()
if (cachedSidebar?.menus?.length) {
  allowedMenuNameSet.value = new Set(cachedSidebar.menus)
  accessLoaded.value = true
}
try {
  const collapsedCached = localStorage.getItem(SIDEBAR_COLLAPSED_KEY)
  if (collapsedCached !== null) {
    collapsed.value = collapsedCached === '1'
  }
} catch {
  // no-op
}

watchEffect(() => {
  try {
    localStorage.setItem(SIDEBAR_COLLAPSED_KEY, collapsed.value ? '1' : '0')
  } catch {
    // no-op
  }
})

onMounted(async () => {
  fetchCompanies()

  try {
    const userResp = await authApi.getCurrentUser()
    const user = userResp?.data?.data?.user ?? userResp?.data?.user ?? userResp?.data
    const userId = user?.id

    if (!userId) return

    const withHrmAccess = Number(user?.with_hrm_access) === 1 || user?.with_hrm_access === true
    if (!withHrmAccess) {
      allowedMenuNameSet.value = new Set()
      accessLoaded.value = true
      writeSidebarCache(userId, [])
      return
    }

    const rightsResp = await accessRightsApi.get(userId)
    const rights = rightsResp?.data?.data ?? rightsResp?.data ?? {}

    const isEnabledStatus = (s) => {
      if (s === 1 || s === '1') return true
      if (s === true || s === 'true' || s === 'TRUE') return true
      return Number(s) === 1
    }

    const enabledMenus = [
      ...(rights?.hrm_menu || []),
      ...(rights?.hrt_menu || []),
      ...(rights?.hrp_menu || []),
      ...(rights?.cpm_menu || []),
      ...(rights?.ld_menu || []),
    ].filter((m) => isEnabledStatus(m?.status))

    allowedMenuNameSet.value = new Set(enabledMenus.map((m) => normalizeMenuName(m?.menu)))
    accessLoaded.value = true
    writeSidebarCache(userId, allowedMenuNameSet.value)
  } catch (e) {
    console.error('Failed to load access rights:', e?.response?.data || e?.message || e)
    if (!cachedSidebar?.menus?.length) accessLoaded.value = true
  }
})

const isHrActive = computed(() => route.path.startsWith('/hr-reports'))
const isHRreportActive = computed(() => route.path.startsWith('/hr-reports'))
const isRecruitmentActive = computed(() => route.path.startsWith('/recruitment-reports'))
const isRecruitmentMenuActive = computed(() => route.path.startsWith('/recruitment'))

function toggleHr() { openHr.value = !openHr.value }

watchEffect(() => { openHr.value = isHrActive.value })
watchEffect(() => { openTk.value = route.path.startsWith('/certificates') })
watchEffect(() => { openPayroll.value = isRecruitmentActive.value })
watchEffect(() => { openRecruitment.value = isRecruitmentMenuActive.value })
watchEffect(() => { HRreport.value = isHRreportActive.value })

const showHrReportsNav = computed(
  () => canAccessMenu('HR Reports') || canShowAny(hrReportsMenuLabels)
)
const showCertificatesNav = computed(
  () => canAccessMenu('Certificates') || canShowAny(certificatesMenuLabels)
)
const showRecruitmentNav = computed(
  () => canAccessMenu('Recruitment') || canShowAny(recruitmentMenuLabels)
)
const showRecruitmentReportsNav = computed(
  () => canAccessMenu('Recruitment Reports') || canShowAny(recruitmentReportsMenuLabels)
)

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
onMounted(() => {
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  }
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
