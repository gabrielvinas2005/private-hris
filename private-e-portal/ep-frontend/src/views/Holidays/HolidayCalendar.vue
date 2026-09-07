<template>
  <div id="holiday-calendar-page">

    <!-- Page Header Controls -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <p class="text-sm text-slate-500 mt-0.5">
          Showing <span class="font-semibold text-slate-700">{{ totalCount }}</span>
          active holiday{{ totalCount !== 1 ? 's' : '' }} for
          <span class="font-semibold text-slate-700">{{ selectedYear }}</span>
        </p>
      </div>

      <!-- Year Picker -->
      <div class="flex items-center gap-2">
        <button
          id="holiday-prev-year-btn"
          @click="changeYear(-1)"
          class="p-2 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition"
          :disabled="loading"
          title="Previous year"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <select
          id="holiday-year-select"
          v-model="selectedYear"
          @change="loadCalendar(selectedYear)"
          class="px-3 py-2 text-sm font-semibold text-slate-700 border border-slate-200 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-[#3B5EFF]/30"
          :disabled="loading"
        >
          <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
        </select>

        <button
          id="holiday-next-year-btn"
          @click="changeYear(1)"
          class="p-2 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-800 transition"
          :disabled="loading"
          title="Next year"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 gap-3 text-slate-400">
      <svg class="w-8 h-8 animate-spin text-[#3B5EFF]" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
      <p class="text-sm">Loading holidays&hellip;</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex flex-col items-center justify-center py-20 gap-3">
      <div class="w-12 h-12 rounded-2xl bg-red-100 flex items-center justify-center">
        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
        </svg>
      </div>
      <p class="text-sm text-red-600 font-medium">{{ error }}</p>
      <button
        @click="loadCalendar(selectedYear)"
        class="px-4 py-2 text-sm font-semibold text-[#3B5EFF] border border-[#3B5EFF]/30 rounded-lg hover:bg-[#3B5EFF]/5 transition"
      >
        Retry
      </button>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="totalCount === 0"
      class="flex flex-col items-center justify-center py-20 gap-3 text-slate-400"
    >
      <div class="w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center mb-2">
        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
      <p class="text-sm font-medium text-slate-500">No holidays declared for {{ selectedYear }}</p>
      <p class="text-xs text-slate-400 text-center max-w-xs">
        Holidays are set up in the Control Panel under Timekeeping Setup › Holidays Setup.
      </p>
    </div>

    <!-- Holiday Calendar Grid — grouped by month -->
    <div v-else class="space-y-6">
      <div
        v-for="(group, monthKey) in groupedByMonth"
        :key="monthKey"
        class="rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm"
      >
        <!-- Month Header -->
        <div class="flex items-center gap-3 px-5 py-3.5 bg-gradient-to-r from-[#3B5EFF]/5 to-transparent border-b border-slate-200/70">
          <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-[#3B5EFF]/10 text-[#3B5EFF] font-bold text-sm flex-shrink-0">
            {{ monthKey }}
          </div>
          <h3 class="text-sm font-bold text-slate-700 tracking-tight">
            {{ group.monthName }}
            <span class="ml-2 text-xs font-medium text-slate-400">
              {{ group.holidays.length }} holiday{{ group.holidays.length !== 1 ? 's' : '' }}
            </span>
          </h3>
        </div>

        <!-- Holiday Rows -->
        <ul class="divide-y divide-slate-100">
          <li
            v-for="holiday in group.holidays"
            :key="holiday.id"
            class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3.5 hover:bg-slate-50 transition-colors duration-150"
          >
            <!-- Date Pill -->
            <div class="flex-shrink-0 w-28">
              <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                {{ formatDate(holiday.date) }}
              </span>
            </div>

            <!-- Name -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-800 truncate">{{ holiday.name }}</p>
            </div>

            <!-- Badges -->
            <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
              <!-- Holiday Type Badge -->
              <span
                v-if="holiday.holiday_type_name"
                class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold rounded-full"
                :class="holidayTypeBadgeClass(holiday.holiday_type_name)"
              >
                {{ holiday.holiday_type_name }}
              </span>

              <!-- Pay Rate Badge -->
              <span
                v-if="holiday.rate != null"
                class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold rounded-full bg-emerald-100 text-emerald-700"
                :title="`Holiday pay at ${formatRate(holiday.rate)} of daily rate`"
              >
                {{ formatRate(holiday.rate) }} pay
              </span>

              <!-- Absent With Pay Badge -->
              <span
                v-if="holiday.absent_with_pay"
                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-full bg-blue-100 text-blue-700"
                title="Employees absent on this day are still entitled to pay"
              >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Absent with Pay
              </span>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- Legend -->
    <div v-if="!loading && !error && totalCount > 0" class="mt-6 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-xs text-slate-500">
      <div class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
        Pay rate shown is % of daily rate
      </div>
      <div class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full bg-blue-400"></span>
        "Absent with Pay" — employees are paid even if absent
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted, computed } from 'vue'
import { useHolidayCalendar } from '../../composables/useHolidayCalendar.js'

export default {
  name: 'HolidayCalendar',

  setup() {
    const {
      loading,
      error,
      selectedYear,
      groupedByMonth,
      totalCount,
      loadCalendar,
      formatDate,
      formatRate
    } = useHolidayCalendar()

    const currentYear = new Date().getFullYear()

    // Year options: 3 years back to 2 years ahead
    const yearOptions = computed(() => {
      const options = []
      for (let y = currentYear - 3; y <= currentYear + 2; y++) {
        options.push(y)
      }
      return options
    })

    const changeYear = (delta) => {
      loadCalendar(selectedYear.value + delta)
    }

    const holidayTypeBadgeClass = (typeName) => {
      if (!typeName) return 'bg-slate-100 text-slate-600'
      const lower = typeName.toLowerCase()
      if (lower.includes('regular') || lower.includes('legal')) {
        return 'bg-rose-100 text-rose-700'
      }
      if (lower.includes('special')) {
        return 'bg-amber-100 text-amber-700'
      }
      return 'bg-slate-100 text-slate-600'
    }

    onMounted(() => {
      loadCalendar()
    })

    return {
      loading,
      error,
      selectedYear,
      groupedByMonth,
      totalCount,
      yearOptions,
      loadCalendar,
      changeYear,
      formatDate,
      formatRate,
      holidayTypeBadgeClass
    }
  }
}
</script>
