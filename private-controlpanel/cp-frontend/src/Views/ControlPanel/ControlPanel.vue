<template>
  <MainLayout>
    <template #header>
      <div class="header">
        <div class="step">01</div>
        <div class="title">Select Services</div>
      </div>
    </template>

    <div class="search-container">
      <div class="search-bar">
        <el-icon class="search-icon"><Search /></el-icon>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Search modules..." 
          class="search-input"
        />
        <button v-if="searchQuery" @click="clearSearch" class="clear-btn">
          <el-icon><Close /></el-icon>
        </button>
      </div>
    </div>

    <section class="grid">
      <RouterLink 
        v-for="card in filteredCards" 
        :key="card.to"
        :to="card.to" 
        class="link-reset"
      >
        <ServiceCard 
          :title="card.title" 
          :description="card.description"
          :gradient="card.gradient"
          :icon="card.icon"
        />
      </RouterLink>
    </section>

    <div v-if="filteredCards.length === 0" class="no-results">
      <el-icon class="no-results-icon"><Search /></el-icon>
      <p>No modules found matching "{{ searchQuery }}"</p>
      <button @click="clearSearch" class="clear-search-btn">Clear search</button>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ElIcon } from 'element-plus'
import { 
  Search, Close, UserFilled, Timer, Briefcase, Clock, Money,
  OfficeBuilding, Collection, Medal, Tickets, Postcard, 
  Remove, Star, Document, TrendCharts, Notebook, Menu,
  CreditCard,
  Setting, Calendar, Warning
} from '@element-plus/icons-vue'
import MainLayout from '../../Layout/MainLayout.vue'
import ServiceCard from '../../components/ServiceCard.vue'

const searchQuery = ref('')

const allCards = [
  {
    to: '/users',
    title: 'User List',
    description: 'Manage and view all system users, their roles, and access permissions',
    gradient: 'linear-gradient(135deg,#ef4444,#f97316)',
    icon: 'UserFilled',
    submodules: ['User List', 'User Management', 'User Roles', 'Access Control', 'User Permissions']
  },
  {
    to: '/activity',
    title: 'User Activities',
    description: 'Track and monitor user actions, login history, and system activities',
    gradient: 'linear-gradient(135deg,#3b82f6,#22d3ee)',
    icon: 'Timer',
    submodules: ['User Activities', 'Audit Log', 'Activity Monitoring', 'Login History', 'System Activities', 'User Actions']
  },
  {
    to: '/hr-setup',
    title: 'HR Setup',
    description: 'Configure company structure, positions, and employee management settings',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)',
    icon: 'Briefcase',
    submodules: [
      'Company', 'Branch', 'Office', 'Division', 'Section', 'Eligibility', 
      'Employment Type', 'Specialization', 'Position', 'Plantilla', 'Non-Plantilla',
      'Promotion Types', 'Off Boarding Types', 'IPCR Ratings', 'Document No.',
      'Document Type', 'Semester Rating', 'Competencies', 'EETE Rating', 'Exam Category'
    ]
  },
  {
    to: '/timekeeping-setup',
    title: 'Time Keeping Setup',
    description: 'Set up work schedules, overtime rules, holidays, and leave management',
    gradient: 'linear-gradient(135deg,#f59e0b,#f97316)',
    icon: 'Clock',
    submodules: [
      'Overtime Types', 'Holiday Types', 'Holidays', 'Leave Types', 
      'Official Business Types', 'Logging Options', 'Biometric Setup', 'Approvers Setup'
    ]
  },
  {
    to: '/payroll-setup',
    title: 'Payroll Setup',
    description: 'Configure salary structures, benefits, deductions, and payroll processing',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Money',
    submodules: [
      'Salary Schedule Setup', 'Tax Table Setup', 'HDMF Table Setup', 'Philhealth Table Setup',
      'GSIS Table Setup', 'Salary Step Setup', 'Salary Grade Setup', 'Income Setup',
      'Deduction Setup', 'Deduction Priority Setup', 'Payroll Interval Setup', 'Payroll Cut-off Setup',
      'Loyalty Award Setup', 'Uniform and Clothing Allowance Setup', 'RATA Positions Setup',
      'RATA Table Setup', 'Hazard Pay Setup', 'Overtime Tax Table Setup', 'Mid Year Bonus Table Setup',
      'Year End Bonus Table Setup', 'Cash Gift Table Setup', 'Monetization Setup'
    ]
  },

  // Individual Sub-module Cards for direct navigation
  // HR Setup Sub-modules
  {
    to: '/hr-setup/company',
    title: 'Company Setup',
    description: 'Configure company information and organizational details',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)',
    icon: 'OfficeBuilding'
  },
  {
    to: '/hr-setup/branch',
    title: 'Branch Setup',
    description: 'Manage branch locations and organizational structure',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/office',
    title: 'Office Setup',
    description: 'Configure office locations and departmental structure',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/division',
    title: 'Division Setup',
    description: 'Set up organizational divisions and reporting structure',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/section',
    title: 'Section Setup',
    description: 'Configure sections within divisions and teams',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/eligibility',
    title: 'Eligibility Setup',
    description: 'Define employee eligibility criteria and requirements',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/employment-type',
    title: 'Employment Type Setup',
    description: 'Configure different types of employment arrangements',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/specialization',
    title: 'Specialization Setup',
    description: 'Define job specializations and skill categories',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/position',
    title: 'Position Setup',
    description: 'Configure job positions and role definitions',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/plantila',
    title: 'Plantilla Setup',
    description: 'Manage regular employee positions and job descriptions',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/non-plantila',
    title: 'Non-Plantilla Setup',
    description: 'Configure contract and temporary positions',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/promotion-types',
    title: 'Promotion Types Setup',
    description: 'Define different types of employee promotions',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/off-boarding-types',
    title: 'Off Boarding Types Setup',
    description: 'Configure employee separation and exit processes',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/ipcr-ratings',
    title: 'IPCR Ratings Setup',
    description: 'Set up Individual Performance Commitment Review ratings',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/document-no',
    title: 'Document No. Setup',
    description: 'Configure document numbering systems and formats',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/document-type',
    title: 'Document Type Setup',
    description: 'Define different types of HR documents',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/semester-rating',
    title: 'Semester Rating Setup',
    description: 'Configure semester-based performance rating systems',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/competencies',
    title: 'Competencies Setup',
    description: 'Define job competencies and skill requirements',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/eete-rating',
    title: 'EETE Rating Setup',
    description: 'Set up Education, Experience, Training, and Experience ratings',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },
  {
    to: '/hr-setup/exam-category',
    title: 'Exam Category Setup',
    description: 'Configure different categories of examinations',
    gradient: 'linear-gradient(135deg,#10b981,#06b6d4)'
  },

  // Time Keeping Setup Sub-modules
  {
    to: '/timekeeping-setup/overtime-types',
    title: 'Overtime Types Setup',
    description: 'Configure different types of overtime work arrangements',
    gradient: 'linear-gradient(135deg,#f59e0b,#f97316)'
  },
  {
    to: '/timekeeping-setup/holiday-types',
    title: 'Holiday Types Setup',
    description: 'Define different types of holidays and special days',
    gradient: 'linear-gradient(135deg,#f59e0b,#f97316)'
  },
  {
    to: '/timekeeping-setup/holidays',
    title: 'Holidays Setup',
    description: 'Configure specific holidays and their schedules',
    gradient: 'linear-gradient(135deg,#f59e0b,#f97316)'
  },
  {
    to: '/timekeeping-setup/leave-types',
    title: 'Leave Types Setup',
    description: 'Set up different types of employee leave',
    gradient: 'linear-gradient(135deg,#f59e0b,#f97316)'
  },
  {
    to: '/timekeeping-setup/official-business-types',
    title: 'Official Business Types Setup',
    description: 'Configure types of official business and travel',
    gradient: 'linear-gradient(135deg,#f59e0b,#f97316)'
  },
  {
    to: '/timekeeping-setup/time-keeping',
    title: 'Logging Options',
    description: 'Configure time in and attendance logging options',
    gradient: 'linear-gradient(135deg,#f59e0b,#f97316)'
  },
  {
    to: '/timekeeping-setup/biometric',
    title: 'Biometric Setup',
    description: 'Set up biometric attendance and access control',
    gradient: 'linear-gradient(135deg,#f59e0b,#f97316)'
  },
  {
    to: '/timekeeping-setup/approvers',
    title: 'Approvers Setup',
    description: 'Configure approval workflows and hierarchies',
    gradient: 'linear-gradient(135deg,#f59e0b,#f97316)'
  },

  // Payroll Setup Sub-modules
  {
    to: '/payroll-setup/salary-schedule-setup',
    title: 'Salary Schedule Setup',
    description: 'Configure salary schedules and payment periods',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Document'
  },
  {
    to: '/payroll-setup/tax-table-setup',
    title: 'Tax Table Setup',
    description: 'Set up tax brackets and withholding tables',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Document'
  },
  {
    to: '/payroll-setup/HDMF Table Setup',
    title: 'HDMF Table Setup',
    description: 'Configure Home Development Mutual Fund contributions',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'CreditCard'
  },
  {
    to: '/payroll-setup/Philhealth Table Setup',
    title: 'Philhealth Table Setup',
    description: 'Set up Philippine Health Insurance Corporation tables',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Document'
  },
  {
    to: '/payroll-setup/GSIS Table Setup',
    title: 'GSIS Table Setup',
    description: 'Configure Government Service Insurance System tables',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Document'
  },
  {
    to: '/payroll-setup/Salary Step Setup',
    title: 'Salary Step Setup',
    description: 'Define salary steps and progression levels',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Document'
  },
  {
    to: '/payroll-setup/salary-grade-setup',
    title: 'Salary Grade Setup',
    description: 'Configure salary grades and classification levels',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Document'
  },
  {
    to: '/payroll-setup/income-setup',
    title: 'Income Setup',
    description: 'Set up different types of employee income and earnings',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Money'
  },
  {
    to: '/payroll-setup/deduction-setup',
    title: 'Deduction Setup',
    description: 'Configure various types of payroll deductions',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Remove'
  },
  {
    to: '/payroll-setup/deduction-priority-setup',
    title: 'Deduction Priority Setup',
    description: 'Set up the order and priority of payroll deductions',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Setting'
  },
  {
    to: '/payroll-setup/payroll-interval-setup',
    title: 'Payroll Interval Setup',
    description: 'Configure payroll processing intervals and schedules',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Calendar'
  },
  {
    to: '/payroll-setup/payroll-cutoff-setup',
    title: 'Payroll Cut-off Setup',
    description: 'Set up payroll cut-off dates and processing periods',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Calendar'
  },
  {
    to: '/payroll-setup/loyalty-award-setup',
    title: 'Loyalty Award Setup',
    description: 'Configure loyalty awards and recognition programs',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Medal'
  },
  {
    to: '/payroll-setup/uniform-and-clothing-allowance-setup',
    title: 'Uniform and Clothing Allowance Setup',
    description: 'Set up uniform and clothing allowance benefits',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Briefcase'
  },
  {
    to: '/payroll-setup/rata-positions-setup',
    title: 'RATA Positions Setup',
    description: 'Configure Representation and Transportation Allowance positions',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Briefcase'
  },
  {
    to: '/payroll-setup/rata-table-setup',
    title: 'RATA Table Setup',
    description: 'Set up RATA tables and allowance calculations',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Briefcase'
  },
  {
    to: '/payroll-setup/hazard-pay-setup',
    title: 'Hazard Pay Setup',
    description: 'Configure hazard pay for high-risk positions',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Warning'
  },
  {
    to: '/payroll-setup/overtime-tax-table-setup',
    title: 'Overtime Tax Table Setup',
    description: 'Set up tax tables for overtime payments',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Clock'
  },
  {
    to: '/payroll-setup/mid-year-bonus-table-setup',
    title: 'Mid Year Bonus Table Setup',
    description: 'Configure mid-year bonus calculations and tables',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Medal'
  },
  {
    to: '/payroll-setup/year-end-bonus-table-setup',
    title: 'Year End Bonus Table Setup',
    description: 'Set up year-end bonus calculations and tables',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Medal'
  },
  {
    to: '/payroll-setup/cash-gift-table-setup',
    title: 'Cash Gift Table Setup',
    description: 'Configure cash gift tables and calculations',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Tickets'
  },
  {
    to: '/payroll-setup/monetization-setup',
    title: 'Monetization Setup',
    description: 'Set up monetization of leave credits and benefits',
    gradient: 'linear-gradient(135deg,#7c3aed,#4f46e5)',
    icon: 'Money'
  }
]

const filteredCards = computed(() => {
  if (!searchQuery.value.trim()) {
    // Show only the 4 core modules by default
    return allCards.filter(card => 
      card.to === '/users' || 
      card.to === '/activity' || 
      card.to === '/hr-setup' || 
      card.to === '/timekeeping-setup' || 
      card.to === '/payroll-setup'
    )
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  return allCards.filter(card => {
    // Search in title
    if (card.title.toLowerCase().includes(query)) {
      return true
    }
    
    // Search in description
    if (card.description.toLowerCase().includes(query)) {
      return true
    }
    
    // Search in submodules (for main module cards)
    if (card.submodules && card.submodules.some(submodule => 
      submodule.toLowerCase().includes(query)
    )) {
      return true
    }
    
    return false
  })
})

const clearSearch = () => {
  searchQuery.value = ''
}
</script>

<style scoped>
.header { display: grid; grid-auto-flow: column; align-items: center; gap: 12px; }
.step { background: #fb923c; color: #fff; height: 32px; width: 32px; border-radius: 9999px; display: grid; place-items: center; font-weight: 700; }
.title { font-size: 18px; font-weight: 600; }

.search-container {
  margin: 24px 0;
  display: flex;
  justify-content: center;
}

.search-bar {
  position: relative;
  width: 100%;
  max-width: 400px;
  display: flex;
  align-items: center;
  background: #ffffff;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.search-bar:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 2px 12px rgba(59, 130, 246, 0.15);
}

.search-icon {
  color: #9ca3af;
  font-size: 18px;
  margin-right: 12px;
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 14px;
  color: #374151;
  background: transparent;
}

.search-input::placeholder {
  color: #9ca3af;
}

.clear-btn {
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s ease, background-color 0.2s ease;
  margin-left: 8px;
}

.clear-btn:hover {
  color: #6b7280;
  background-color: #f3f4f6;
}

.grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); 
  gap: 20px; 
  margin-top: 20px; 
}

.link-reset { 
  text-decoration: none; 
}

.no-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
  color: #6b7280;
}

.no-results-icon {
  font-size: 48px;
  color: #d1d5db;
  margin-bottom: 16px;
}

.no-results p {
  font-size: 16px;
  margin-bottom: 20px;
  color: #6b7280;
}

.clear-search-btn {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s ease;
}

.clear-search-btn:hover {
  background: #2563eb;
}
</style>


