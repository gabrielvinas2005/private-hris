<template>
  <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <!-- Filters Header Bar -->
    <div class="p-5 bg-slate-50/80 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="p-2 bg-purple-50 text-purple-600 rounded-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h3 class="text-base font-bold text-slate-900">Attendance History & DTR</h3>
          <p class="text-xs text-slate-500 font-medium">Filter by payroll cutoff period and export official DTR PDF</p>
        </div>
      </div>

      <div class="flex items-center gap-3 flex-wrap">
        <!-- Payroll Cutoff Selector -->
        <el-select
          v-model="selectedPayrollPeriod"
          placeholder="Select Payroll Period"
          style="width: 240px"
          @change="fetchPeriodDTR"
        >
          <el-option
            v-for="period in payrollPeriods"
            :key="period.id"
            :label="formatPeriodLabel(period)"
            :value="period.id"
          />
        </el-select>

        <!-- Column Visibility Toggle -->
        <el-popover placement="bottom-end" width="220" trigger="click">
          <template #reference>
            <el-button type="default" plain class="!rounded-xl font-semibold shadow-sm">
              <template #icon>
                <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </template>
              Columns
            </el-button>
          </template>
          <div class="space-y-2 p-1">
            <div class="font-bold text-xs text-slate-700 border-b border-slate-100 pb-1.5 mb-2">Column Visibility</div>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.date" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Date</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.dayType" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Day Type</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.punches" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Punches (AM/PM)</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.workedHours" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Worked Hours</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.late" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Late</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.undertime" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Undertime</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.ot" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Approved OT</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.remarks" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Remarks</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded">
              <input type="checkbox" v-model="visibleColumns.actions" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
              <span>Required Action</span>
            </label>
          </div>
        </el-popover>

        <!-- Action Buttons: Refresh, Preview & Download DTR PDF -->
        <div class="flex items-center gap-2">
          <el-button
            type="default"
            plain
            :loading="loading"
            @click="fetchPeriodDTR"
            class="!rounded-xl font-semibold shadow-sm"
            title="Refresh Attendance History"
          >
            <template #icon>
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
            </template>
            Refresh
          </el-button>

          <el-button
            type="info"
            plain
            :loading="previewingPdf"
            :disabled="!selectedPayrollPeriod"
            @click="previewDTR"
            class="!rounded-xl font-semibold shadow-sm"
          >
            <template #icon>
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </template>
            Preview DTR
          </el-button>

          <el-button
            type="primary"
            :loading="exportingPdf"
            :disabled="!selectedPayrollPeriod"
            @click="downloadDTR"
            class="!rounded-xl font-semibold shadow-sm"
          >
            <template #icon>
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </template>
            Download DTR PDF
          </el-button>
        </div>
      </div>
    </div>

    <!-- Week Navigation Bar (Top of Table) -->
    <div class="px-5 py-3 bg-slate-100/70 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <span class="text-xs font-bold text-slate-700">View Mode:</span>
        <div class="inline-flex p-0.5 bg-slate-200/80 rounded-xl border border-slate-300/60">
          <button
            @click="viewMode = 'weekly'"
            type="button"
            :class="[
              viewMode === 'weekly' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900',
              'px-2.5 py-1 text-xs rounded-lg transition-all cursor-pointer font-semibold'
            ]"
          >
            Weekly View
          </button>
          <button
            @click="viewMode = 'all'"
            type="button"
            :class="[
              viewMode === 'all' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900',
              'px-2.5 py-1 text-xs rounded-lg transition-all cursor-pointer font-semibold'
            ]"
          >
            All Cutoff Records
          </button>
        </div>
      </div>

      <!-- Week Selector Navigation Buttons (< This Week > or < August 16-22 >) -->
      <div v-if="viewMode === 'weekly'" class="flex items-center gap-1.5 bg-white p-1 rounded-xl border border-slate-300/80 shadow-xs">
        <button
          @click="navigateWeek(-1)"
          type="button"
          title="Previous Week"
          class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition-all font-bold cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <button
          @click="resetToCurrentWeek"
          type="button"
          :title="weekOffset !== 0 ? 'Click to reset to This Week' : 'Current Week'"
          :class="[
            weekOffset === 0 ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-800 hover:bg-indigo-50 hover:text-indigo-600 font-bold',
            'px-3.5 py-1 rounded-lg text-xs transition-all flex items-center gap-1.5 cursor-pointer'
          ]"
        >
          <span>{{ currentWeekLabel }}</span>
        </button>

        <button
          @click="navigateWeek(1)"
          type="button"
          title="Next Week"
          class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-700 hover:bg-indigo-50 hover:text-indigo-600 transition-all font-bold cursor-pointer"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

      <div class="text-xs text-slate-500 font-medium">
        Showing <span class="font-bold text-slate-800">{{ displayTableRows.length }}</span> record(s)
      </div>
    </div>

    <!-- Action Required Banner for Missing Punches in Previous Days -->
    <div v-if="missingPunchRowsCount > 0" class="mx-5 my-3 p-4 bg-amber-50/95 border border-amber-300 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm transition-all duration-200">
      <div class="flex items-start gap-3">
        <div class="p-2 bg-amber-100 text-amber-800 rounded-xl mt-0.5 flex-shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <h4 class="text-xs uppercase font-extrabold tracking-wider text-amber-900 flex items-center gap-2">
            <span>Action Required: Missing Punches Detected</span>
            <span class="px-2 py-0.5 bg-amber-200 text-amber-900 rounded-full text-[10px] font-bold">
              {{ missingPunchRowsCount }} Day{{ missingPunchRowsCount > 1 ? 's' : '' }}
            </span>
          </h4>
          <p class="text-xs font-semibold text-amber-800 mt-0.5">
            You have {{ missingPunchRowsCount }} past day(s) with missing punches or unverified log exceptions. Submit a DTR correction request for each date to avoid undertime deductions.
          </p>
        </div>
      </div>
      <el-button type="warning" size="small" class="!rounded-xl font-bold border border-amber-400 !px-4 hover:!bg-amber-600 flex-shrink-0" @click="openFirstMissingCorrection">
        Fix First Missing Punch
      </el-button>
    </div>

    <!-- Skeleton Loader during database fetch -->
    <div v-if="loading" class="p-5 space-y-4 bg-slate-50/40 animate-pulse">
      <div class="h-10 bg-slate-200 rounded-xl w-full"></div>
      <div v-for="i in 7" :key="i" class="h-12 bg-white border border-slate-200 rounded-xl flex items-center justify-between px-4 space-x-3">
        <div class="h-4 bg-slate-200 rounded w-24"></div>
        <div class="h-4 bg-slate-200 rounded w-20"></div>
        <div class="h-4 bg-slate-200 rounded w-40"></div>
        <div class="h-4 bg-slate-200 rounded w-16"></div>
        <div class="h-4 bg-slate-200 rounded w-16"></div>
        <div class="h-4 bg-slate-200 rounded w-28"></div>
      </div>
    </div>

    <!-- Attendance Data Table with Side Padding -->
    <div v-else class="p-2.5 sm:p-3.5 bg-slate-50/40">
      <el-table
        :data="displayTableRows"
        stripe
        border
        fit
        size="small"
        style="width: 100%"
        :row-class-name="getRowClassName"
        :span-method="arraySpanMethod"
        empty-text="No attendance records found for this period"
      >
        <!-- Date -->
        <el-table-column v-if="visibleColumns.date" prop="date" label="Date" min-width="115" resizable>
          <template #default="{ row }">
            <div class="flex items-center gap-1 flex-wrap">
              <span class="font-bold text-slate-900 text-xs">{{ row.date }}</span>
              <span v-if="isTodayRow(row)" class="px-1.5 py-0.2 rounded text-[8px] font-black bg-amber-500 text-white uppercase tracking-wider shadow-sm">
                Today
              </span>
            </div>
            <div class="text-[10px] text-slate-400 font-sans uppercase font-medium leading-tight">
              {{ row.day_name }}
            </div>
          </template>
        </el-table-column>

        <!-- Day Type -->
        <el-table-column v-if="visibleColumns.dayType" min-width="150" resizable>
          <template #header>
            <div class="flex items-center justify-between gap-1 w-full">
              <span>Day Type</span>
              <el-select
                v-model="selectedDayType"
                placeholder="All"
                size="small"
                style="width: 90px"
                clearable
                @change="currentPage = 1"
                @click.stop
              >
                <el-option value="All" label="All" />
                <el-option value="On-Site" label="On-Site" />
                <el-option value="Work From Home" label="WFH" />
                <el-option value="On Leave" label="On Leave" />
                <el-option value="Official Business" label="OB" />
                <el-option value="Rest Day" label="Rest Day" />
                <el-option value="Holiday" label="Holiday" />
                <el-option value="Work Suspended" label="Suspended" />
              </el-select>
            </div>
          </template>
          <template #default="{ row }">
            <div v-if="isRestday(row) && !hasWorkPunches(row)" class="py-0.5 px-2 bg-slate-100/90 text-slate-600 rounded-md border border-slate-200/80 text-[11px] font-bold flex items-center justify-center gap-1.5 tracking-wide">
              <span :class="dayTypeBadgeClass(row)" class="px-2 py-0.5 rounded-full text-[10px] font-bold inline-block">
                {{ getDayTypeLabel(row) }}
              </span>
              <span class="text-slate-400 font-normal">•</span>
              <span class="text-slate-500 uppercase text-[10px] font-semibold">No Shift</span>
            </div>
            <span v-else :class="dayTypeBadgeClass(row)" class="px-2 py-0.5 rounded-full text-[10px] font-bold inline-block">
              {{ getDayTypeLabel(row) }}
            </span>
          </template>
        </el-table-column>

        <!-- Punches (AM / PM) -->
        <el-table-column v-if="visibleColumns.punches" label="Punches (AM / PM)" min-width="180" resizable>
          <template #default="{ row }">
            <!-- ABSENT: On-Site/WFH with zero punches past shift end -->
            <div v-if="isAutoAbsent(row)" class="flex items-center justify-center py-0.5">
              <span class="flex items-center gap-1 px-2.5 py-0.5 bg-rose-50 text-rose-700 font-extrabold text-[10px] rounded border border-rose-300 tracking-widest uppercase">
                <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                Absent
              </span>
            </div>
            <!-- Normal punch times -->
            <div v-else class="text-[11px] leading-tight space-y-0.5">
              <div class="flex items-center gap-1.5">
                <span class="text-slate-400 text-[10px] w-6 font-semibold uppercase">AM</span>
                <span class="text-slate-800 font-bold tabular-nums">{{ formatPunchTime(row.am_in) }} – {{ formatPunchTime(row.am_out) }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="text-slate-400 text-[10px] w-6 font-semibold uppercase">PM</span>
                <span class="text-slate-800 font-bold tabular-nums">{{ formatPunchTime(row.pm_in) }} – {{ formatPunchTime(row.pm_out) }}</span>
              </div>
              <!-- Missing punch indicator -->
              <div v-if="hasMissingPunch(row)" class="pt-0.5">
                <span class="inline-flex items-center gap-1 text-[9px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200">
                  <svg class="w-2.5 h-2.5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  Missing Punch
                </span>
              </div>
            </div>
          </template>
        </el-table-column>

        <!-- Worked Hours -->
        <el-table-column v-if="visibleColumns.workedHours" label="Worked Hours" min-width="110" resizable>
          <template #default="{ row }">
            <span class="text-xs font-bold text-slate-900">
              {{ formatWorkHours(row.hours_worked || row.work_hours) }}
            </span>
          </template>
        </el-table-column>

        <!-- Late (mins) -->
        <el-table-column v-if="visibleColumns.late" label="Late" min-width="75" resizable>
          <template #default="{ row }">
            <template v-if="computeLateMinutes(row) > 0">
              <span class="text-amber-700 font-bold text-[11px] bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                {{ computeLateMinutes(row) }}m
              </span>
            </template>
            <span v-else class="text-slate-300 text-xs">—</span>
          </template>
        </el-table-column>

        <!-- Undertime -->
        <el-table-column v-if="visibleColumns.undertime" label="Undertime" min-width="85" resizable>
          <template #default="{ row }">
            <template v-if="computeUndertimeMinutes(row) > 0">
              <span class="text-orange-700 font-bold text-[11px] bg-orange-50 px-1.5 py-0.5 rounded border border-orange-200">
                {{ computeUndertimeMinutes(row) }}m
              </span>
            </template>
            <span v-else class="text-slate-300 text-xs">—</span>
          </template>
        </el-table-column>

        <!-- Approved OT -->
        <el-table-column v-if="visibleColumns.ot" label="Approved OT" min-width="95" resizable>
          <template #default="{ row }">
            <span v-if="Number(row.ot_hours || row.approved_ot_hours || 0) > 0" class="text-purple-700 font-bold text-[11px] bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200">
              {{ Number(row.ot_hours || row.approved_ot_hours).toFixed(1) }}h
            </span>
            <span v-else class="text-slate-300 text-xs">—</span>
          </template>
        </el-table-column>

        <!-- Remarks -->
        <el-table-column v-if="visibleColumns.remarks" label="Remarks" min-width="135" resizable>
          <template #default="{ row }">
            <span v-if="row.remarks" class="text-[11px] text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded">
              {{ row.remarks }}
            </span>
            <span v-else class="text-slate-400 text-xs">—</span>
          </template>
        </el-table-column>

        <!-- Required Action -->
        <el-table-column v-if="visibleColumns.actions" label="Required Action" min-width="145" align="center" resizable>
          <template #default="{ row }">
            <div v-if="hasMissingPunch(row)">
              <span v-if="getPendingCorrection(row.date)" class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-300 shadow-sm">
                <svg class="w-3 h-3 text-amber-600 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Pending
              </span>
              <el-button
                v-else
                size="small"
                type="warning"
                class="!px-2 !py-0.5 !text-[10px] font-bold !rounded-md shadow-sm border border-amber-300 hover:!bg-amber-600"
                @click="openRowCorrectionDialog(row)"
              >
                <svg class="w-3 h-3 mr-0.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Correction
              </el-button>
            </div>
            <span v-else class="text-slate-300 text-xs">—</span>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Pagination & Footer Summary Control Bar -->
    <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
      <div v-if="viewMode === 'weekly'" class="flex items-center gap-2 text-xs text-slate-600 font-medium">
        <span class="p-1 bg-indigo-50 text-indigo-700 rounded-md font-bold">Weekly View</span>
        <span class="text-slate-500">
          Displaying week period <strong class="text-slate-800">{{ currentWeekLabel }}</strong> ({{ displayTableRows.length }} day records)
        </span>
      </div>

      <div v-else class="flex items-center gap-2 text-xs text-slate-600 font-medium">
        <span>Rows per page:</span>
        <el-select
          v-model="pageSize"
          size="small"
          style="width: 85px"
          @change="currentPage = 1"
        >
          <el-option :value="5" label="5" />
          <el-option :value="10" label="10" />
          <el-option :value="20" label="20" />
          <el-option :value="50" label="50" />
          <el-option :value="filteredTableRows.length || 999" label="All" />
        </el-select>
        <span class="text-slate-400 font-normal ml-1">
          Showing {{ filteredTableRows.length > 0 ? (currentPage - 1) * pageSize + 1 : 0 }}–{{ Math.min(currentPage * pageSize, filteredTableRows.length) }} of {{ filteredTableRows.length }} records
        </span>
      </div>

      <el-pagination
        v-if="viewMode === 'all' && filteredTableRows.length > pageSize"
        v-model:current-page="currentPage"
        :page-size="pageSize"
        :total="filteredTableRows.length"
        layout="prev, pager, next"
        background
        size="small"
      />
    </div>

    <!-- Running Totals Summary Footer Card -->
    <div class="p-5 bg-slate-900 text-white space-y-3">
      <div class="flex items-center justify-between text-xs text-slate-400 font-semibold uppercase tracking-wider">
        <span>Totals Summary</span>
        <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-indigo-300 border border-slate-700 normal-case font-bold">
          {{ viewMode === 'weekly' ? 'Weekly View (' + currentWeekLabel + ')' : 'All Cutoff Records' }}
        </span>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Stat Card 1 -->
        <div class="bg-slate-800/80 rounded-xl p-3.5 border border-slate-700">
          <span class="text-[11px] text-slate-400 font-semibold block uppercase">Present Days</span>
          <span class="text-xl font-black text-emerald-400">{{ runningTotals.daysPresent }}</span>
        </div>
        <!-- Stat Card 2 -->
        <div class="bg-slate-800/80 rounded-xl p-3.5 border border-slate-700">
          <span class="text-[11px] text-slate-400 font-semibold block uppercase">Total Late</span>
          <span class="text-xl font-black text-amber-400">{{ runningTotals.totalLate }} mins</span>
        </div>
        <!-- Stat Card 3 -->
        <div class="bg-slate-800/80 rounded-xl p-3.5 border border-slate-700">
          <span class="text-[11px] text-slate-400 font-semibold block uppercase">Total Undertime</span>
          <span class="text-xl font-black text-orange-400">{{ runningTotals.totalUndertime }} mins</span>
        </div>
        <!-- Stat Card 4 -->
        <div class="bg-slate-800/80 rounded-xl p-3.5 border border-slate-700">
          <span class="text-[11px] text-slate-400 font-semibold block uppercase">Absences</span>
          <span class="text-xl font-black text-rose-400">{{ runningTotals.totalAbsences }}</span>
        </div>
        <!-- Stat Card 5 -->
        <div class="bg-slate-800/80 rounded-xl p-3.5 border border-slate-700">
          <span class="text-[11px] text-slate-400 font-semibold block uppercase">Approved OT</span>
          <span class="text-xl font-black text-purple-400">{{ runningTotals.totalOT }} hrs</span>
        </div>
      </div>
    </div>

    <!-- DTR PDF Preview Dialog -->
    <el-dialog
      v-model="pdfPreviewVisible"
      title="Daily Time Record (DTR) PDF Preview"
      width="900px"
      top="5vh"
      destroy-on-close
      @closed="cleanupPdfPreview"
    >
      <div v-loading="previewingPdf" class="w-full h-[70vh] bg-slate-100 rounded-xl overflow-hidden flex items-center justify-center border border-slate-200">
        <iframe
          v-if="pdfPreviewUrl"
          :src="pdfPreviewUrl"
          class="w-full h-full"
          frameborder="0"
        />
        <div v-else class="text-slate-400 text-sm font-medium">
          Generating PDF preview...
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500 font-medium">
            Official HRIS DTR Document
          </span>
          <div class="flex items-center gap-2">
            <el-button @click="pdfPreviewVisible = false">Close</el-button>
            <el-button type="primary" :loading="exportingPdf" @click="downloadDTR">
              Download PDF
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- Submit Correction Modal for Missing Punch -->
    <el-dialog v-model="rowCorrectionDialogVisible" title="Submit DTR Correction Request" width="540px" destroy-on-close>
      <div v-if="selectedRowForCorrection" class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl mb-4 text-xs space-y-1 shadow-sm">
        <div class="flex items-center justify-between font-bold text-amber-900">
          <span>Target Date: {{ selectedRowForCorrection.date }} ({{ selectedRowForCorrection.day_name }})</span>
          <span class="px-2.5 py-0.5 rounded-full bg-amber-200 text-amber-900 text-[11px] font-bold">
            {{ getDayTypeLabel(selectedRowForCorrection) }}
          </span>
        </div>
        <div class="text-slate-600 text-[11px] pt-1">
          Recorded Punches:
          <span class="font-bold text-slate-800">
            AM: {{ formatPunchTime(selectedRowForCorrection.am_in) }} – {{ formatPunchTime(selectedRowForCorrection.am_out) }} |
            PM: {{ formatPunchTime(selectedRowForCorrection.pm_in) }} – {{ formatPunchTime(selectedRowForCorrection.pm_out) }}
          </span>
        </div>
      </div>

      <el-form label-position="top" class="space-y-1">
        <el-form-item label="Target Payroll Period">
          <el-select v-model="rowCorrectionForm.payroll_period_id" class="w-full" placeholder="Select period">
            <el-option
              v-for="p in payrollPeriods"
              :key="p.id"
              :label="p.period_description || p.name || `${p.attendance_start_date || p.start_date} to ${p.attendance_end_date || p.end_date}`"
              :value="p.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Target Date">
          <el-date-picker v-model="rowCorrectionForm.target_date" type="date" value-format="YYYY-MM-DD" class="w-full" disabled />
        </el-form-item>

        <div class="space-y-3 mb-4 p-4 bg-slate-50 border border-slate-200 rounded-2xl">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-800">Claimed Punch Times to Correct:</span>
            <span class="text-[11px] text-slate-500">Provide one or multiple punch times</span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">AM In</label>
              <el-time-picker v-model="rowCorrectionForm.am_in" format="HH:mm" value-format="HH:mm" placeholder="e.g. 08:00" class="w-full" />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">AM Out</label>
              <el-time-picker v-model="rowCorrectionForm.am_out" format="HH:mm" value-format="HH:mm" placeholder="e.g. 12:00" class="w-full" />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">PM In</label>
              <el-time-picker v-model="rowCorrectionForm.pm_in" format="HH:mm" value-format="HH:mm" placeholder="e.g. 13:00" class="w-full" />
            </div>
            <div>
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">PM Out</label>
              <el-time-picker v-model="rowCorrectionForm.pm_out" format="HH:mm" value-format="HH:mm" placeholder="e.g. 17:00" class="w-full" />
            </div>
          </div>
        </div>

        <el-form-item label="Supporting Reason / Justification (Required)">
          <el-input v-model="rowCorrectionForm.reason" type="textarea" :rows="3" placeholder="Explain the reason for missing log or punch correction..." />
        </el-form-item>

        <el-form-item label="Supporting Document (Optional Upload)">
          <input type="file" @change="handleRowCorrectionFile" class="text-xs text-slate-600" />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="flex items-center justify-end gap-2">
          <el-button @click="rowCorrectionDialogVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="submittingRowCorrection" @click="submitRowCorrection">Submit Application</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { useToast } from 'vue-toastification'

export default {
  name: 'AttendanceHistoryTable',
  props: {
    employeeId: { type: [Number, String], default: null }
  },
  data() {
    return {
      toast: useToast(),
      loading: false,
      exportingPdf: false,
      previewingPdf: false,
      pdfPreviewVisible: false,
      pdfPreviewUrl: '',
      selectedPayrollPeriod: null,
      selectedDayType: 'All',
      visibleColumns: {
        date: true,
        dayType: true,
        punches: true,
        workedHours: true,
        late: true,
        undertime: true,
        ot: true,
        remarks: true,
        actions: true
      },
      pendingApplications: [],
      rowCorrectionDialogVisible: false,
      selectedRowForCorrection: null,
      selectedCorrectionFile: null,
      submittingRowCorrection: false,
      rowCorrectionForm: {
        payroll_period_id: null,
        target_date: '',
        reason: '',
        am_in: '',
        am_out: '',
        pm_in: '',
        pm_out: ''
      },
      payrollPeriods: [],
      tableRows: [],
      currentPage: 1,
      pageSize: 10,
      pageSizeOptions: [5, 10, 20, 50],
      weekOffset: 0,
      viewMode: 'weekly'
    }
  },
  computed: {
    currentWeekLabel() {
      if (this.weekOffset === 0) {
        return 'This Week'
      }
      const { monday, sunday } = this.getWeekRange(this.weekOffset)
      const monMonth = monday.toLocaleString('en-US', { month: 'long' })
      const sunMonth = sunday.toLocaleString('en-US', { month: 'long' })
      const monDate = monday.getDate()
      const sunDate = sunday.getDate()

      if (monMonth === sunMonth) {
        return `${monMonth} ${monDate}-${sunDate}`
      } else {
        return `${monMonth} ${monDate} - ${sunMonth} ${sunDate}`
      }
    },
    missingPunchRowsCount() {
      return this.tableRows.filter(row => this.hasMissingPunch(row) && !this.getPendingCorrection(row.date)).length
    },
    filteredTableRows() {
      if (!this.selectedDayType || this.selectedDayType === 'All') {
        return this.tableRows
      }
      return this.tableRows.filter(row => {
        const label = this.getDayTypeLabel(row)
        return label === this.selectedDayType
      })
    },
    displayTableRows() {
      const rows = this.filteredTableRows
      if (this.viewMode !== 'weekly') {
        if (!this.pageSize || this.pageSize >= rows.length) {
          return rows
        }
        const start = (this.currentPage - 1) * this.pageSize
        const end = start + this.pageSize
        return rows.slice(start, end)
      }

      const { monday, sunday } = this.getWeekRange(this.weekOffset)
      const monMs = monday.getTime()
      const sunMs = sunday.getTime()

      return rows.filter(r => {
        if (!r.date) return false
        const d = new Date(r.date)
        if (isNaN(d.getTime())) return false
        const time = d.getTime()
        return time >= monMs && time <= sunMs
      })
    },
    paginatedTableRows() {
      return this.displayTableRows
    },
    runningTotals() {
      let daysPresent = 0
      let totalLate = 0
      let totalUndertime = 0
      let totalAbsences = 0
      let totalOT = 0

      // Use displayTableRows for Weekly view (filtered by selected week),
      // or filteredTableRows for All Cutoff Records (entire period)
      const targetRows = this.viewMode === 'weekly' ? this.displayTableRows : this.filteredTableRows

      targetRows.forEach(row => {
        const label = this.getDayTypeLabel(row)

        // Rest Days and Holidays never count toward any metric — always skip
        if (label === 'Rest Day' || label === 'Holiday') return

        // Check if any actual attendance data exists
        const hasActualWork = !!(
          row.am_in || row.am_out || row.pm_in || row.pm_out ||
          Number(row.hours_worked || row.work_hours || 0) > 0
        )

        // For On-Site and WFH days: apply auto-absent detection
        // For Work Suspended, On Leave, Official Business: if employee showed up, count as Present
        const isScheduledWorkShift = label === 'On-Site' || label === 'Work From Home' || label === 'WFH'
        const isDbAbsent = !!(row.absent || row.is_absent)

        if (isScheduledWorkShift && !hasActualWork && (isDbAbsent || this.isAutoAbsent(row))) {
          // Absent: On-Site/WFH with no work and no punches, past shift end
          totalAbsences++
        } else if (hasActualWork) {
          // Present: employee has actual attendance data regardless of day type
          daysPresent++
          totalLate += this.computeLateMinutes(row)
          totalUndertime += this.computeUndertimeMinutes(row)
          totalOT += Number(row.ot_hours || row.approved_ot_hours || row.ot || 0)
        }
        // else: On-Site/WFH today before 5pm with no punches, or future dates — skip (not yet determined)
      })

      totalOT = Math.round(totalOT * 100) / 100

      return { daysPresent, totalLate, totalUndertime, totalAbsences, totalOT }
    }
  },
  watch: {
    employeeId: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.loadPayrollPeriods()
        }
      }
    },
    viewMode() {
      this.saveUserPreferences()
    },
    pageSize() {
      this.saveUserPreferences()
    },
    selectedDayType() {
      this.saveUserPreferences()
    },
    visibleColumns: {
      deep: true,
      handler() {
        this.saveUserPreferences()
      }
    }
  },
  async mounted() {
    this.loadUserPreferences()
    await this.loadPayrollPeriods()
    this.boundFetchDTR = () => {
      this.fetchPeriodDTR()
    }
    window.addEventListener('dtr-updated', this.boundFetchDTR)
    window.addEventListener('focus', this.boundFetchDTR)
    this.refreshTimer = setInterval(() => {
      if (this.selectedPayrollPeriod) {
        this.fetchPeriodDTR()
      }
    }, 25000)
  },
  beforeUnmount() {
    if (this.boundFetchDTR) {
      window.removeEventListener('dtr-updated', this.boundFetchDTR)
      window.removeEventListener('focus', this.boundFetchDTR)
    }
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer)
    }
    this.cleanupPdfPreview()
  },
  methods: {
    loadUserPreferences() {
      try {
        const saved = localStorage.getItem('dtr_table_preferences')
        if (saved) {
          const prefs = JSON.parse(saved)
          if (prefs.viewMode && ['weekly', 'all'].includes(prefs.viewMode)) {
            this.viewMode = prefs.viewMode
          }
          if (prefs.pageSize && !isNaN(Number(prefs.pageSize))) {
            this.pageSize = Number(prefs.pageSize)
          }
          if (prefs.selectedDayType) {
            this.selectedDayType = prefs.selectedDayType
          }
          if (prefs.visibleColumns && typeof prefs.visibleColumns === 'object') {
            this.visibleColumns = { ...this.visibleColumns, ...prefs.visibleColumns }
          }
        }
      } catch (e) {
        console.error('Failed to load user table preferences:', e)
      }
    },
    saveUserPreferences() {
      try {
        const prefs = {
          viewMode: this.viewMode,
          pageSize: this.pageSize,
          selectedDayType: this.selectedDayType,
          visibleColumns: { ...this.visibleColumns }
        }
        localStorage.setItem('dtr_table_preferences', JSON.stringify(prefs))
      } catch (e) {
        console.error('Failed to save user table preferences:', e)
      }
    },
    formatWorkHours(val) {
      const num = Number(val)
      if (isNaN(num) || num <= 0) return '0 hrs 0 mins'
      const totalMinutes = Math.round(num * 60)
      const hrs = Math.floor(totalMinutes / 60)
      const mins = totalMinutes % 60
      if (hrs > 0 && mins > 0) return `${hrs} hrs ${mins} mins`
      if (hrs > 0) return `${hrs} hrs 0 mins`
      return `${mins} mins`
    },
    formatPeriodLabel(period) {
      if (!period) return ''
      if (period.period_description) return period.period_description
      if (period.name) return period.name
      const start = period.attendance_start_date || period.start_date
      const end = period.attendance_end_date || period.end_date
      if (start && end) return `${start} to ${end}`
      return `Payroll Period #${period.id}`
    },
    formatPunchTime(val) {
      if (!val || val === '--:--') return '--:--'
      if (val.length <= 8 && (val.includes('AM') || val.includes('PM'))) return val
      if (/^\d{2}:\d{2}(:\d{2})?$/.test(val)) {
        const parts = val.split(':')
        let hrs = parseInt(parts[0], 10)
        const mins = parts[1]
        const ampm = hrs >= 12 ? 'PM' : 'AM'
        hrs = hrs % 12 || 12
        return `${String(hrs).padStart(2, '0')}:${mins} ${ampm}`
      }
      try {
        const d = new Date(val)
        if (!isNaN(d.getTime())) {
          return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
        }
      } catch (e) {}
      return val
    },
    /**
     * Convert a time string to total minutes since midnight.
     * Handles both 24-hour "HH:MM" / "HH:MM:SS" and 12-hour "HH:MM AM/PM" formats.
     */
    timeToMinutes(timeStr) {
      if (!timeStr || timeStr === '--:--') return null
      // 24-hour format: "07:00" or "07:00:00"
      const m24 = timeStr.match(/^(\d{1,2}):(\d{2})/)
      if (m24) {
        return parseInt(m24[1], 10) * 60 + parseInt(m24[2], 10)
      }
      // 12-hour format: "07:00 AM" / "04:00 PM"
      const m12 = timeStr.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)/i)
      if (m12) {
        let h = parseInt(m12[1], 10)
        const min = parseInt(m12[2], 10)
        const period = m12[3].toUpperCase()
        if (period === 'PM' && h !== 12) h += 12
        if (period === 'AM' && h === 12) h = 0
        return h * 60 + min
      }
      return null
    },
    /**
     * Compute lateness in minutes for a row.
     * Uses the fix schedule's am_in + effective allowance (grace OR flexi, whichever is set).
     * Falls back to row.late (DB-processed value) if no schedule data is available.
     */
    computeLateMinutes(row) {
      if (!row) return 0
      // If schedule times aren't available, fall back to the DB-computed value
      if (!row.sched_am_in || !row.am_in) return Number(row.late || 0)
      const scheduled = this.timeToMinutes(row.sched_am_in)
      const actual    = this.timeToMinutes(row.am_in)
      if (scheduled === null || actual === null) return Number(row.late || 0)
      // Effective allowance: employee has either grace period OR flexi hours (not both)
      const graceMins = Number(row.sched_grace || 0)
      const flexiMins = Number(row.sched_flexi || 0) * 60
      const allowance = Math.max(graceMins, flexiMins)
      return Math.max(0, actual - scheduled - allowance)
    },
    /**
     * Compute undertime in minutes for a row.
     * Compares actual pm_out against the scheduled pm_out.
     * Falls back to row.undertime (DB-processed value) if no schedule data is available.
     */
    computeUndertimeMinutes(row) {
      if (!row) return 0
      // If schedule times aren't available, fall back to the DB-computed value
      if (!row.sched_pm_out || !row.pm_out) return Number(row.undertime || 0)
      const scheduled = this.timeToMinutes(row.sched_pm_out)
      const actual    = this.timeToMinutes(row.pm_out)
      if (scheduled === null || actual === null) return Number(row.undertime || 0)
      return Math.max(0, scheduled - actual)
    },
    async loadPayrollPeriods() {
      if (!this.employeeId) {
        this.payrollPeriods = []
        this.tableRows = []
        return
      }
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        const response = await dtrApiService.getDTRApplicationPayrollPeriods(this.employeeId)
        const raw = response?.data?.payroll_periods || response?.payroll_periods || (Array.isArray(response?.data) ? response.data : (Array.isArray(response) ? response : []))
        this.payrollPeriods = Array.isArray(raw) ? raw : []
        if (this.payrollPeriods.length > 0) {
          this.selectedPayrollPeriod = this.payrollPeriods[0].id
          await this.fetchPeriodDTR()
        }
      } catch (err) {
        console.error('Failed to load payroll periods:', err)
      }
    },
    async fetchPeriodDTR() {
      if (!this.selectedPayrollPeriod) return
      this.loading = true
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        const response = await dtrApiService.getDTRDetail(this.employeeId, this.selectedPayrollPeriod)
        const data = response?.data || response
        const records = data?.daily_time_records || data?.dtr_records || (Array.isArray(data) ? data : [])
        this.tableRows = Array.isArray(records) ? records.map(r => ({
          ...r,
          hours_worked: r.work_hours ?? r.hours_worked ?? 0,
          day_name: r.day_name || (r.date ? new Date(r.date).toLocaleDateString('en-US', { weekday: 'short' }) : '')
        })) : []
        this.autoAdjustWeekOffsetToPeriod()
        await this.loadPendingApplications()
      } catch (err) {
        console.error('Fetch DTR error:', err)
        this.toast.error('Failed to load DTR history for selected period.')
      } finally {
        this.loading = false
      }
    },
    async loadPendingApplications() {
      try {
        const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
        const userId = userData.id || userData.user_id || this.employeeId || null
        if (!userId) return
        const { dtrApiService } = await import('../../services/apiService.js')
        const data = await dtrApiService.getDTRApplications(userId)
        const rawApps = data?.data?.applications || data?.applications || (Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []))
        this.pendingApplications = Array.isArray(rawApps) ? rawApps : []
      } catch (e) {
        this.pendingApplications = []
      }
    },
    /**
     * Checks if a row in the past has at least one punch but is missing required punch slots.
     * Excludes full absences (0 punches).
     */
    hasMissingPunch(row) {
      if (!row || !row.date) return false
      // Do not include if marked as absent
      if (this.isAutoAbsent(row)) return false

      const label = this.getDayTypeLabel(row)
      const isWorkShift = label === 'On-Site' || label === 'Work From Home' || label === 'WFH'
      if (!isWorkShift) return false

      const rowDate = new Date(row.date.includes('T') ? row.date : `${row.date}T00:00:00`)
      rowDate.setHours(0, 0, 0, 0)
      const today = new Date()
      today.setHours(0, 0, 0, 0)

      // Must be a past date
      if (rowDate >= today) return false

      // Must have at least 1 punch or logged work hours
      const hasAnyPunch = !!(row.am_in || row.am_out || row.pm_in || row.pm_out || Number(row.hours_worked || row.work_hours || 0) > 0)
      if (!hasAnyPunch) return false

      // Incomplete punches (missing 1, 2, or 3 punch slots)
      const hasAllPunches = !!(row.am_in && row.am_out && row.pm_in && row.pm_out)
      return !hasAllPunches
    },
    getPendingCorrection(dateStr) {
      if (!dateStr || !this.pendingApplications || this.pendingApplications.length === 0) return null
      return this.pendingApplications.find(app => {
        const target = app.target_date || app.request_date
        if (!target) return false
        const isDateMatch = target.includes(dateStr) || dateStr.includes(target)
        const s = (app.status_label || app.status || '').toString().toLowerCase()
        const isPending = s.includes('pending') || s === '0' || s.includes('for approval')
        return isDateMatch && isPending
      })
    },
    getMissingFieldType(row) {
      if (!row) return 'missed_log'
      if (!row.am_in) return 'am_in'
      if (!row.am_out) return 'am_out'
      if (!row.pm_in) return 'pm_in'
      if (!row.pm_out) return 'pm_out'
      return 'missed_log'
    },
    openRowCorrectionDialog(row) {
      this.selectedRowForCorrection = row
      this.rowCorrectionForm.target_date = row.date
      this.rowCorrectionForm.payroll_period_id = this.selectedPayrollPeriod
      this.rowCorrectionForm.am_in = row.am_in || ''
      this.rowCorrectionForm.am_out = row.am_out || ''
      this.rowCorrectionForm.pm_in = row.pm_in || ''
      this.rowCorrectionForm.pm_out = row.pm_out || ''
      this.rowCorrectionForm.reason = ''
      this.selectedCorrectionFile = null
      this.rowCorrectionDialogVisible = true
    },
    openFirstMissingCorrection() {
      const firstRow = this.tableRows.find(row => this.hasMissingPunch(row) && !this.getPendingCorrection(row.date))
      if (firstRow) {
        this.openRowCorrectionDialog(firstRow)
      } else {
        this.toast.info('No unresolved missing punches found.')
      }
    },
    handleRowCorrectionFile(e) {
      if (e.target.files && e.target.files[0]) {
        this.selectedCorrectionFile = e.target.files[0]
      }
    },
    async submitRowCorrection() {
      if (!this.rowCorrectionForm.payroll_period_id || !this.rowCorrectionForm.reason) {
        this.toast.error('Please fill in required fields (Reason / Justification).')
        return
      }

      if (!this.rowCorrectionForm.am_in && !this.rowCorrectionForm.am_out && !this.rowCorrectionForm.pm_in && !this.rowCorrectionForm.pm_out) {
        this.toast.error('Please specify at least one claimed punch time to correct.')
        return
      }

      this.submittingRowCorrection = true
      try {
        const formData = new FormData()
        formData.append('payroll_period_id', this.rowCorrectionForm.payroll_period_id)
        formData.append('reason', this.rowCorrectionForm.reason)
        formData.append('target_date', this.rowCorrectionForm.target_date)

        const summaryParts = []
        if (this.rowCorrectionForm.am_in) {
          formData.append('am_in', this.rowCorrectionForm.am_in)
          summaryParts.push(`AMI:${this.rowCorrectionForm.am_in}`)
        }
        if (this.rowCorrectionForm.am_out) {
          formData.append('am_out', this.rowCorrectionForm.am_out)
          summaryParts.push(`AMO:${this.rowCorrectionForm.am_out}`)
        }
        if (this.rowCorrectionForm.pm_in) {
          formData.append('pm_in', this.rowCorrectionForm.pm_in)
          summaryParts.push(`PMI:${this.rowCorrectionForm.pm_in}`)
        }
        if (this.rowCorrectionForm.pm_out) {
          formData.append('pm_out', this.rowCorrectionForm.pm_out)
          summaryParts.push(`PMO:${this.rowCorrectionForm.pm_out}`)
        }

        formData.append('claimed_time', summaryParts.join(', '))
        formData.append('field_type', summaryParts.length > 1 ? 'multiple' : (this.rowCorrectionForm.am_in ? 'am_in' : this.rowCorrectionForm.am_out ? 'am_out' : this.rowCorrectionForm.pm_in ? 'pm_in' : 'pm_out'))

        if (this.selectedCorrectionFile) {
          formData.append('dtr_attachment', this.selectedCorrectionFile)
          formData.append('attachment', this.selectedCorrectionFile)
        }

        const { dtrApiService } = await import('../../services/apiService.js')
        await dtrApiService.submitDTRApplication(this.employeeId, formData)

        this.toast.success(`DTR Correction request submitted for ${this.rowCorrectionForm.target_date}!`)
        this.rowCorrectionDialogVisible = false
        await this.fetchPeriodDTR()
      } catch (err) {
        const errorMsg = err?.response?.data?.message || err?.data?.message || err?.message || 'Failed to submit correction application.'
        this.toast.error(errorMsg)
      } finally {
        this.submittingRowCorrection = false
      }
    },
    getWeekRange(offset = 0) {
      const now = new Date()
      const currentDay = now.getDay()
      const distanceToMonday = (currentDay + 6) % 7
      const monday = new Date(now)
      monday.setDate(now.getDate() - distanceToMonday + (offset * 7))
      monday.setHours(0, 0, 0, 0)

      const sunday = new Date(monday)
      sunday.setDate(monday.getDate() + 6)
      sunday.setHours(23, 59, 59, 999)

      return { monday, sunday }
    },
    navigateWeek(offset) {
      this.weekOffset += offset
    },
    resetToCurrentWeek() {
      this.weekOffset = 0
    },
    autoAdjustWeekOffsetToPeriod() {
      if (!this.tableRows || this.tableRows.length === 0) return
      const { monday, sunday } = this.getWeekRange(this.weekOffset)
      const monMs = monday.getTime()
      const sunMs = sunday.getTime()
      const hasCurrentMatch = this.tableRows.some(r => {
        if (!r.date) return false
        const d = new Date(r.date)
        return !isNaN(d.getTime()) && d.getTime() >= monMs && d.getTime() <= sunMs
      })

      if (!hasCurrentMatch && this.tableRows[0]?.date) {
        const firstDate = new Date(this.tableRows[0].date)
        if (!isNaN(firstDate.getTime())) {
          const now = new Date()
          const currentDay = now.getDay()
          const distToMon = (currentDay + 6) % 7
          const currentMon = new Date(now)
          currentMon.setDate(now.getDate() - distToMon)
          currentMon.setHours(0, 0, 0, 0)

          const diffMs = firstDate.getTime() - currentMon.getTime()
          const diffWeeks = Math.floor(diffMs / (1000 * 60 * 60 * 24 * 7))
          this.weekOffset = diffWeeks
        }
      }
    },
    isHoliday(row) {
      if (!row) return false
      if (row.day_type_label) return row.day_type_label === 'Holiday'
      return Number(row.is_holiday) === 1 || row.is_holiday === true
    },
    isRestday(row) {
      if (!row) return false
      if (row.day_type_label) return row.day_type_label === 'Rest Day'
      return Number(row.is_restday) === 1 || row.is_restday === true || row.rest_day === true
    },
    isOb(row) {
      if (!row) return false
      if (row.day_type_label) return row.day_type_label === 'Official Business' || row.day_type_label === 'OB'
      return Number(row.is_ob) === 1 || row.is_ob === true
    },
    isWfh(row) {
      if (!row) return false
      if (row.day_type_label) return row.day_type_label === 'Work From Home' || row.day_type_label === 'WFH'
      return Number(row.is_wfh) === 1 || row.is_wfh === true || (row.remarks && row.remarks.toLowerCase().includes('wfh'))
    },
    getDayTypeLabel(row) {
      if (!row) return 'On-Site'
      if (row.day_type_label) return row.day_type_label
      if (row.is_work_suspended || (row.remarks && (row.remarks.toLowerCase().includes('suspended') || row.remarks.toLowerCase().includes('cancellation')))) {
        return 'Work Suspended'
      }
      if (this.isHoliday(row)) return 'Holiday'
      if (row.leave || row.is_leave) return 'On Leave'
      if (this.isWfh(row)) return 'Work From Home'
      if (this.isOb(row)) return 'Official Business'
      if (this.isRestday(row)) return 'Rest Day'
      return 'On-Site'
    },
    dayTypeBadgeClass(row) {
      const label = this.getDayTypeLabel(row)
      if (label === 'Work Suspended' || label === 'Suspended') return 'bg-purple-100 text-purple-900 border border-purple-300 font-bold'
      if (label === 'Holiday') return 'bg-blue-100 text-blue-800 border border-blue-200 font-bold'
      if (label === 'On Leave') return 'bg-sky-100 text-sky-800 border border-sky-200 font-bold'
      if (label === 'Work From Home' || label === 'WFH') return 'bg-indigo-100 text-indigo-800 border border-indigo-200 font-bold'
      if (label === 'Official Business' || label === 'OB') return 'bg-teal-100 text-teal-800 border border-teal-200 font-bold'
      if (label === 'Rest Day') return 'bg-slate-200 text-slate-700 border border-slate-300 font-medium'
      return 'bg-emerald-100 text-emerald-800 border border-emerald-200 font-semibold'
    },
    async downloadDTR() {
      if (!this.selectedPayrollPeriod) return
      this.exportingPdf = true
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        const blob = await dtrApiService.printDTR(this.employeeId, this.selectedPayrollPeriod)
        const url = window.URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `DTR_${this.employeeId}_Period_${this.selectedPayrollPeriod}.pdf`)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        this.toast.success('DTR PDF exported successfully!')
      } catch (err) {
        this.toast.error('Failed to download DTR PDF.')
      } finally {
        this.exportingPdf = false
      }
    },
    async previewDTR() {
      if (!this.selectedPayrollPeriod) return
      this.previewingPdf = true
      try {
        const { dtrApiService } = await import('../../services/apiService.js')
        const blob = await dtrApiService.printDTR(this.employeeId, this.selectedPayrollPeriod)
        if (this.pdfPreviewUrl) {
          window.URL.revokeObjectURL(this.pdfPreviewUrl)
        }
        this.pdfPreviewUrl = window.URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
        this.pdfPreviewVisible = true
      } catch (err) {
        console.error('Preview PDF error:', err)
        this.toast.error('Failed to generate DTR PDF preview.')
      } finally {
        this.previewingPdf = false
      }
    },
    cleanupPdfPreview() {
      if (this.pdfPreviewUrl) {
        window.URL.revokeObjectURL(this.pdfPreviewUrl)
        this.pdfPreviewUrl = ''
      }
    },
    hasWorkPunches(row) {
      if (!row) return false
      return !!(row.am_in || row.pm_in || (row.hours_worked || row.work_hours || 0) > 0)
    },
    /**
     * Returns true when a row should be treated as an automatic absence.
     * Conditions (ALL must be true):
     *   1. Day type is On-Site or Work From Home (employee has a shift)
     *   2. Zero total punches (am_in, am_out, pm_in, pm_out all absent)
     *   3. The date is a past date OR it is today and the current time is >= 17:00 (shift end)
     */
    isAutoAbsent(row) {
      if (!row || !row.date) return false

      // Rule 1: Only applies to scheduled work-shift day types
      const label = this.getDayTypeLabel(row)
      const isWorkShift = label === 'On-Site' || label === 'Work From Home' || label === 'WFH'
      if (!isWorkShift) return false

      // Rule 2: Strictly zero punches and zero worked hours — any punch or logged work hours means NOT absent
      if (this.hasWorkPunches(row)) return false
      const hasAnyPunch = !!(row.am_in || row.am_out || row.pm_in || row.pm_out || (row.hours_worked || row.work_hours || 0) > 0)
      if (hasAnyPunch) return false

      // Rule 3: Date must be in the past, OR today after 5:00 PM
      const rowDate = new Date(row.date)
      rowDate.setHours(0, 0, 0, 0)
      const today = new Date()
      const todayMidnight = new Date(today)
      todayMidnight.setHours(0, 0, 0, 0)

      if (rowDate < todayMidnight) {
        // Past date — always absent
        return true
      } else if (rowDate.getTime() === todayMidnight.getTime()) {
        // Today — only absent after 5:00 PM (shift end)
        return today.getHours() >= 17
      }

      return false
    },
    arraySpanMethod({ row, columnIndex }) {
      if (this.isRestday(row) && !this.hasWorkPunches(row)) {
        const activeColsCount = Object.values(this.visibleColumns).filter(Boolean).length
        const dayTypeIdx = this.visibleColumns.date ? 1 : 0
        if (this.visibleColumns.dayType && columnIndex === dayTypeIdx) {
          return [1, Math.max(1, activeColsCount - dayTypeIdx)]
        } else if (columnIndex > dayTypeIdx) {
          return [0, 0]
        }
      }
      return [1, 1]
    },
    isTodayRow(row) {
      if (!row || !row.date) return false
      const todayStr = new Date().toISOString().slice(0, 10)
      return row.date === todayStr
    },
    getRowClassName({ row }) {
      if (!row || !row.date) return 'dtr-row-custom'

      const todayStr = new Date().toISOString().slice(0, 10)
      const isToday = row.date === todayStr

      const dName = (row.day_name || '').toUpperCase()
      const dayOfWeek = new Date(row.date).getDay()
      const isWeekend = dayOfWeek === 0 || dayOfWeek === 6 || dName.includes('SAT') || dName.includes('SUN') || this.isRestday(row)

      if (isToday) {
        return 'dtr-row-custom dtr-row-today'
      }
      if (this.isAutoAbsent(row)) {
        return 'dtr-row-custom dtr-row-absent'
      }
      if (isWeekend) {
        return 'dtr-row-custom dtr-row-weekend'
      }
      return 'dtr-row-custom'
    }
  }
}
</script>

<style scoped>
:deep(.el-table--small .el-table__cell) {
  padding: 4px 0 !important;
}
:deep(.el-table th.el-table__cell) {
  background-color: #f8fafc !important;
  color: #475569 !important;
  font-size: 0.7rem !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.025em !important;
  padding: 6px 0 !important;
}
:deep(.el-table--small .cell) {
  padding-left: 8px !important;
  padding-right: 8px !important;
}

:deep(.el-table .dtr-row-today) {
  background-color: #fefce8 !important;
}
:deep(.el-table .dtr-row-today td.el-table__cell) {
  background-color: #fefce8 !important;
}
:deep(.el-table .dtr-row-today .el-table__cell:first-child) {
  border-left: 4px solid #f59e0b !important;
}

:deep(.el-table .dtr-row-absent) {
  background-color: #fff5f5 !important;
}
:deep(.el-table .dtr-row-absent td.el-table__cell) {
  background-color: #fff1f2 !important;
}
:deep(.el-table .dtr-row-absent .el-table__cell:first-child) {
  border-left: 4px solid #f43f5e !important;
}

:deep(.el-table .dtr-row-weekend) {
  background-color: #f8fafc !important;
}
:deep(.el-table .dtr-row-weekend td.el-table__cell) {
  background-color: #f1f5f9 !important;
  color: #64748b !important;
}
</style>
