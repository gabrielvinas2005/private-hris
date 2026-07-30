<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Employee SALN</h1>
        <p class="text-slate-600">Statement of Assets, Liabilities, and Net Worth</p>
      </div>

      <!-- Warning Message -->
      <div 
        v-if="showWarning" 
        class="mb-4 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-md flex items-center justify-between"
      >
        <div class="flex items-center">
          <svg class="h-5 w-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <p class="text-sm text-gray-700">
            <strong>Warning:</strong> Please select a Compliance For option and a Filing Type (Joint Filing, Separate Filing, or Not Applicable) before downloading the SALN.
          </p>
        </div>
        <button 
          @click="showWarning = false"
          class="text-gray-400 hover:text-gray-600 ml-4"
        >
          <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>

      <!-- Download Button -->
      <div class="flex justify-end mb-6">
        <button 
          @click="showPrintPreview"
          class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition-colors duration-200"
        >
          Download SALN
        </button>
      </div>

      <!-- Print Preview -->
      <div v-if="showPrint" class="mb-6">
        <el-card shadow="never">
          <div class="flex items-center justify-between mb-3">
            <div class="text-base font-semibold">SALN Print Preview</div>
            <div class="flex items-center gap-2">
              <el-button size="small" type="primary" :disabled="!previewUrl" @click="downloadFromPreview">
                Download
              </el-button>
              <el-button size="small" @click="closePrint">Close</el-button>
            </div>
          </div>
          <div v-if="previewUrl" class="border rounded overflow-hidden" style="height: 680px; max-width: 100%;">
            <iframe :src="previewUrl" class="w-full h-full" style="max-width: 100%;"></iframe>
          </div>
          <div v-else class="text-center text-gray-500 py-10">Loading preview...</div>
        </el-card>
      </div>

      <!-- Main Content -->
      <div class="bg-white rounded-lg shadow-sm border border-slate-200">
        <div class="p-6">
          <!-- Tab Navigation -->
          <div class="border-b border-slate-200 mb-6">
            <nav class="flex space-x-8">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  activeTab === tab.id
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                ]"
              >
                {{ tab.name }}
              </button>
            </nav>
          </div>

          <!-- Tab Content -->
          <div class="mt-6">
            <!-- Compliance For Tab -->
            <div v-if="activeTab === 'compliance'" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Compliance For Section -->
                <div>
                  <h3 class="text-lg font-semibold text-slate-900 mb-4">Compliance For:</h3>
                  <div class="space-y-4">
                    <!-- Assumption of office -->
                    <div class="flex items-center space-x-3">
                      <input
                        type="checkbox"
                        id="compliance-assumption"
                        :checked="complianceType === 'assumption'"
                        @change="handleComplianceChange('assumption')"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                      />
                      <label for="compliance-assumption" class="text-sm text-slate-700">
                        Assumption of office as of
                      </label>
                      <input
                        type="date"
                        v-model="assumptionDate"
                        :disabled="complianceType !== 'assumption'"
                        class="px-3 py-1 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                      />
                    </div>

                    <!-- Annual filing -->
                    <div class="flex flex-col space-y-1">
                      <div class="flex items-center space-x-3">
                        <input
                          type="checkbox"
                          id="compliance-annual"
                          :checked="complianceType === 'annual'"
                          @change="handleComplianceChange('annual')"
                          class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                        <label for="compliance-annual" class="text-sm text-slate-700">
                          Annual filing as of December 31,
                        </label>
                        <input
                          type="number"
                          v-model="complianceYear"
                          @input="validateYear"
                          :disabled="complianceType !== 'annual'"
                          :min="2000"
                          :max="maxYear"
                          :placeholder="(new Date().getFullYear() - 1).toString()"
                          :class="[
                            'px-3 py-1 text-sm border rounded-md focus:outline-none focus:ring-2 disabled:bg-gray-100 disabled:cursor-not-allowed w-24',
                            yearError ? 'border-red-500 focus:ring-red-500' : 'border-slate-300 focus:ring-blue-500'
                          ]"
                        />
                      </div>
                      <p v-if="yearError" class="text-xs text-red-600 ml-7">{{ yearError }}</p>
                    </div>

                    <!-- Exit -->
                    <div class="flex items-center space-x-3">
                      <input
                        type="checkbox"
                        id="compliance-exit"
                        :checked="complianceType === 'exit'"
                        @change="handleComplianceChange('exit')"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                      />
                      <label for="compliance-exit" class="text-sm text-slate-700">
                        Exit as of
                      </label>
                      <input
                        type="date"
                        v-model="exitDate"
                        :disabled="complianceType !== 'exit'"
                        class="px-3 py-1 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                      />
                    </div>
                  </div>
                </div>

                <!-- Filing Type Section -->
                <div>
                  <h3 class="text-lg font-semibold text-slate-900 mb-4">Filing Type:</h3>
                  <div class="space-y-3">
                    <div class="flex items-center">
                      <input
                        type="radio"
                        id="filing-joint"
                        v-model="filingType"
                        value="joint"
                        @change="handleFilingTypeChange"
                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                      />
                      <label for="filing-joint" class="ml-2 text-sm text-slate-700">
                        Joint Filing
                      </label>
                    </div>
                    <div class="flex items-center">
                      <input
                        type="radio"
                        id="filing-separate"
                        v-model="filingType"
                        value="separate"
                        @change="handleFilingTypeChange"
                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                      />
                      <label for="filing-separate" class="ml-2 text-sm text-slate-700">
                        Separate Filing
                      </label>
                    </div>
                    <div class="flex items-center">
                      <input
                        type="radio"
                        id="filing-na"
                        v-model="filingType"
                        value="na"
                        @change="handleFilingTypeChange"
                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                      />
                      <label for="filing-na" class="ml-2 text-sm text-slate-700">
                        Not Applicable
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Personal Information Tab -->
            <div v-if="activeTab === 'personal'" class="space-y-6">
              <PersonalInformation 
                :employee="employeeData" 
                :address="addressData"
                :children="childrenData"
              />
            </div>

            <!-- Assets Tab -->
            <div v-if="activeTab === 'assets'" class="space-y-6">
              <AssetsSection 
                :realProperties="realProperties"
                :personalProperties="personalProperties"
                :liabilities="liabilities"
                @update="handleUpdate"
              />
            </div>

            <!-- Business Interests Tab -->
            <div v-if="activeTab === 'business'" class="space-y-6">
              <BusinessInterests 
                :businessInterests="businessInterests"
                @update="handleUpdate"
              />
            </div>

            <!-- Relatives Tab -->
            <div v-if="activeTab === 'relatives'" class="space-y-6">
              <RelativesSection 
                :relatives="relatives"
                @update="handleUpdate"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import PersonalInformation from '../../components/SALN/PersonalInformation.vue'
import AssetsSection from '../../components/SALN/AssetsSection.vue'
import BusinessInterests from '../../components/SALN/BusinessInterests.vue'
import RelativesSection from '../../components/SALN/RelativesSection.vue'
import ApiService from '../../services/api.js'
import { useToast } from 'vue-toastification'

export default {
  name: 'SALNView',
  components: {
    MainLayout,
    PersonalInformation,
    AssetsSection,
    BusinessInterests,
    RelativesSection
  },
  setup() {
    const toast = useToast()
    return { toast }
  },
  data() {
    return {
      activeTab: 'compliance',
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'My Profile & Records', path: '/profile-records' },
        { name: 'SALN', path: '/saln' }
      ],
      employeeData: {},
      addressData: {},
      childrenData: [],
      realProperties: [],
      personalProperties: [],
      liabilities: [],
      businessInterests: [],
      relatives: [],
      complianceType: null,
      assumptionDate: '',
      exitDate: '',
      complianceYear: new Date().getFullYear() - 1,
      filingType: null,
      showWarning: false,
      yearError: '',
      showPrint: false,
      previewUrl: null,
      previewLoading: false,
      previewFileName: null
    }
  },
  computed: {
    tabs() {
      return [
        { id: 'compliance', name: 'Compliance For' },
        { id: 'personal', name: 'Personal Information' },
        { id: 'assets', name: 'Assets' },
        { id: 'business', name: 'Business Interests' },
        { id: 'relatives', name: 'Relatives in Government' }
      ]
    },
    maxYear() {
      return new Date().getFullYear() - 1
    }
  },
  async mounted() {
    await this.loadSALNData()
  },
  beforeUnmount() {
    if (this.previewUrl) {
      URL.revokeObjectURL(this.previewUrl)
      this.previewUrl = null
    }
  },
  methods: {
    getCurrentUserId() {
      const raw = localStorage.getItem('user_data')
      try { return raw ? JSON.parse(raw).id : null } catch { return null }
    },
    async loadSALNData() {
      try {
        const userId = this.getCurrentUserId()
        if (!userId) throw new Error('No authenticated user')
        // Resolve employee id first using 201 file API
        const empRes = await ApiService.getEmployee201File(userId)
        const employeeId = empRes?.success && empRes?.data?.info && empRes.data.info.length > 0
          ? empRes.data.info[0].id
          : null
        const targetId = employeeId || userId
        const res = await ApiService.getSALN(targetId)
        if (!res.success) throw new Error(res.message || 'Failed to load SALN')
        const d = res.data
        // Map data from controller payload
        const employeeInfo = (d.info && d.info[0]) || {}
        this.employeeData = employeeInfo
        this.addressData = d.address || {}
        this.childrenData = d.children || []
        this.realProperties = d.realProperties || []
        this.personalProperties = d.personalProperties || []
        this.liabilities = d.liabilities || []
        this.businessInterests = d.business || []
        this.relatives = d.relatives || []
      } catch (error) {
        console.error('Error loading SALN data:', error)
        this.toast.error('Failed to load SALN data')
      }
    },
    async handleUpdate(type, data) {
      try {

        
        // Handle asset-related events
        if (type === 'add-real-property') {
          // Add new real property to the list
          this.realProperties.push({
            id: null,
            description: '',
            kind: '',
            location: '',
            assessed_value: '',
            current_fair_market_value: '',
            acquisition_cost: '',
            mode_of_acquisition: '',
            year_acquired: ''
          })
          // Don't show success message or reload data for add operations
          return
        } else if (type === 'edit-real-property') {
          // Handle editing - could open a modal or form

          return
        } else if (type === 'delete-real-property') {
          // Delete the real property from backend
          try {
            if (!data.id) {
              // If no ID, just remove from local list (new item)
              this.realProperties = this.realProperties.filter(p => p.id !== data.id)
              return
            }
            
            const response = await ApiService.deleteSALNRealProperty(data.id)
            if (response.success) {
              this.realProperties = this.realProperties.filter(p => p.id !== data.id)
              this.toast.success('Property deleted successfully')
            } else {
              throw new Error(response.message || 'Failed to delete property')
            }
          } catch (error) {
            console.error('Error deleting real property:', error)
            this.toast.error('Failed to delete property: ' + error.message)
          }
          return
        } else if (type === 'save-real-property') {
          // Save the real property to backend
          try {
            const userId = this.getCurrentUserId()
            if (!userId) throw new Error('No authenticated user')
            
            // Prepare the data in the format expected by the backend
            const properties = {
              [data.index]: {
                id: data.property.id || null,
                description: data.property.description,
                kind: data.property.kind,
                exact_location: data.property.location,
                assessed_value: data.property.assessed_value,
                current_fair_market_value: data.property.current_fair_market_value,
                acquisition_year: data.property.year_acquired,
                acquisition_mode: data.property.mode_of_acquisition,
                acquisition_cost: data.property.acquisition_cost
              }
            }
            
            const response = await ApiService.saveSALNRealProperties(properties)
            if (response.success) {
              this.toast.success('Real property saved successfully')
              await this.loadSALNData()
            } else {
              throw new Error(response.message || 'Failed to save real property')
            }
          } catch (error) {
            console.error('Error saving real property:', error)
            this.toast.error('Failed to save real property: ' + error.message)
          }
          return
        } else if (type === 'cancel-new-real-property') {
          // Remove the new property from the list
          this.realProperties.splice(data.index, 1)
          return
        } else if (type === 'add-personal-property') {
          // Add new personal property to the list
          this.personalProperties.push({
            id: null,
            description: '',
            year_acquired: '',
            acquisition_cost: ''
          })
          return
        } else if (type === 'edit-personal-property') {
          // Handle editing

          return
        } else if (type === 'delete-personal-property') {
          // Delete the personal property from backend
          try {
            if (!data.id) {
              // If no ID, just remove from local list (new item)
              this.personalProperties = this.personalProperties.filter(p => p.id !== data.id)
              return
            }
            
            const response = await ApiService.deleteSALNPersonalProperty(data.id)
            if (response.success) {
              this.personalProperties = this.personalProperties.filter(p => p.id !== data.id)
              this.toast.success('Personal property deleted successfully')
            } else {
              throw new Error(response.message || 'Failed to delete personal property')
            }
          } catch (error) {
            console.error('Error deleting personal property:', error)
            this.toast.error('Failed to delete personal property: ' + error.message)
          }
          return
        } else if (type === 'save-personal-property') {
          // Save the personal property to backend
          try {
            const userId = this.getCurrentUserId()
            if (!userId) throw new Error('No authenticated user')
            
            // Prepare the data in the format expected by the backend
            const personal_properties = [{
              id: data.property.id || null,
              description: data.property.description,
              year_acquired: data.property.year_acquired,
              acquisition_cost: data.property.acquisition_cost
            }]
            
            const response = await ApiService.saveSALNPersonalProperties(personal_properties)
            if (response.success) {
              this.toast.success('Personal property saved successfully')
              await this.loadSALNData()
            } else {
              throw new Error(response.message || 'Failed to save personal property')
            }
          } catch (error) {
            console.error('Error saving personal property:', error)
            this.toast.error('Failed to save personal property: ' + error.message)
          }
          return
        } else if (type === 'cancel-new-personal-property') {
          // Remove the new personal property from the list
          this.personalProperties.splice(data.index, 1)
          return
        } else if (type === 'add-liability') {
          // Add new liability to the list
          this.liabilities.push({
            id: null,
            nature: '',
            creditor_name: '',
            outstanding_balance: ''
          })
          return
        } else if (type === 'edit-liability') {
          // Handle editing

          return
        } else if (type === 'delete-liability') {
          // Delete the liability from backend
          try {
            if (!data.id) {
              // If no ID, just remove from local list (new item)
              this.liabilities = this.liabilities.filter(l => l.id !== data.id)
              return
            }
            
            const response = await ApiService.deleteSALNLiability(data.id)
            if (response.success) {
              this.liabilities = this.liabilities.filter(l => l.id !== data.id)
              this.toast.success('Liability deleted successfully')
            } else {
              throw new Error(response.message || 'Failed to delete liability')
            }
          } catch (error) {
            console.error('Error deleting liability:', error)
            this.toast.error('Failed to delete liability: ' + error.message)
          }
          return
        } else if (type === 'save-liability') {
          // Save the liability to backend
          try {
            const userId = this.getCurrentUserId()
            if (!userId) throw new Error('No authenticated user')
            
            // Prepare the data in the format expected by the backend
            const liabilities = [{
              id: data.liability.id || null,
              nature: data.liability.nature,
              creditor_name: data.liability.creditor_name,
              outstanding_balance: data.liability.outstanding_balance
            }]
            
            const response = await ApiService.saveSALNLiabilities(liabilities)
            if (response.success) {
              this.toast.success('Liability saved successfully')
              await this.loadSALNData()
            } else {
              throw new Error(response.message || 'Failed to save liability')
            }
          } catch (error) {
            console.error('Error saving liability:', error)
            this.toast.error('Failed to save liability: ' + error.message)
          }
          return
        } else if (type === 'cancel-new-liability') {
          // Remove the new liability from the list
          this.liabilities.splice(data.index, 1)
          return
         } else if (type === 'add-business-interest') {
           // Add new business interest to the list
           this.businessInterests.push({
             id: null,
             entity_name: '',
             business_address: '',
             nature_of_business: '',
             date_acquired: ''
           })
           return
         } else if (type === 'edit-business-interest') {
           // Handle editing
           return
         } else if (type === 'delete-business-interest') {
           // Delete the business interest from backend
           try {
             if (!data.id) {
               // If no ID, just remove from local list (new item)
               this.businessInterests = this.businessInterests.filter(b => b.id !== data.id)
               return
             }
             
             const response = await ApiService.deleteSALNBusinessInterest(data.id)
             if (response.success) {
               this.businessInterests = this.businessInterests.filter(b => b.id !== data.id)
               this.toast.success('Business interest deleted successfully')
             } else {
               throw new Error(response.message || 'Failed to delete business interest')
             }
           } catch (error) {
             console.error('Error deleting business interest:', error)
             this.toast.error('Failed to delete business interest: ' + error.message)
           }
           return
         } else if (type === 'save-business-interest') {
           // Save the business interest to backend
           try {
             const userId = this.getCurrentUserId()
             if (!userId) throw new Error('No authenticated user')
             
             // Prepare the data in the format expected by the backend
             const business_interests = [{
               id: data.business.id || null,
               entity_name: data.business.entity_name,
               business_address: data.business.business_address,
               nature_of_business: data.business.nature_of_business,
               date_acquired: data.business.date_acquired
             }]
             
             const response = await ApiService.saveSALNBusinessInterests(business_interests)
             if (response.success) {
               this.toast.success('Business interest saved successfully')
               await this.loadSALNData()
             } else {
               throw new Error(response.message || 'Failed to save business interest')
             }
           } catch (error) {
             console.error('Error saving business interest:', error)
             this.toast.error('Failed to save business interest: ' + error.message)
           }
           return
         } else if (type === 'cancel-new-business-interest') {
           // Remove the new business interest from the list
           this.businessInterests.splice(data.index, 1)
           return
         } else if (type === 'add-relative') {
           // Add new relative to the list
           this.relatives.push({
             id: null,
             relatives_name: '',
             relationship: '',
             position: '',
             office_address: ''
           })
           return
         } else if (type === 'edit-relative') {
           // Handle editing
           return
         } else if (type === 'delete-relative') {
           // Delete the relative from backend
           try {
             if (!data.id) {
               // If no ID, just remove from local list (new item)
               this.relatives = this.relatives.filter(r => r.id !== data.id)
               return
             }
             
             const response = await ApiService.deleteSALNRelative(data.id)
             if (response.success) {
               this.relatives = this.relatives.filter(r => r.id !== data.id)
               this.toast.success('Relative deleted successfully')
             } else {
               throw new Error(response.message || 'Failed to delete relative')
             }
           } catch (error) {
             console.error('Error deleting relative:', error)
             this.toast.error('Failed to delete relative: ' + error.message)
           }
           return
         } else if (type === 'save-relative') {
           // Save the relative to backend
           try {
             const userId = this.getCurrentUserId()
             if (!userId) throw new Error('No authenticated user')
             
             // Prepare the data in the format expected by the backend
             const relatives = [{
               id: data.relative.id || null,
               relatives_name: data.relative.relatives_name,
               relationship: data.relative.relationship,
               position: data.relative.position,
               office_address: data.relative.office_address
             }]
             
             const response = await ApiService.saveSALNRelatives(relatives)
             if (response.success) {
               this.toast.success('Relative saved successfully')
               await this.loadSALNData()
             } else {
               throw new Error(response.message || 'Failed to save relative')
             }
           } catch (error) {
             console.error('Error saving relative:', error)
             this.toast.error('Failed to save relative: ' + error.message)
           }
           return
         } else if (type === 'cancel-new-relative') {
           // Remove the new relative from the list
           this.relatives.splice(data.index, 1)
           return
         }
         
         // Handle legacy event types for backward compatibility
        else if (type === 'realProperties') {
          await ApiService.saveSALNRealProperties(data)
          this.toast.success('SALN data updated successfully')
          await this.loadSALNData()
        } else if (type === 'personalProperties') {
          await ApiService.saveSALNPersonalProperties(data)
          this.toast.success('SALN data updated successfully')
          await this.loadSALNData()
        } else if (type === 'liabilities') {
          await ApiService.saveSALNLiabilities(data)
          this.toast.success('SALN data updated successfully')
          await this.loadSALNData()
        } else if (type === 'businessInterests') {
          await ApiService.saveSALNBusinessInterests(data)
          this.toast.success('SALN data updated successfully')
          await this.loadSALNData()
        } else if (type === 'relatives') {
          await ApiService.saveSALNRelatives(data)
          this.toast.success('SALN data updated successfully')
          await this.loadSALNData()
        }
      } catch (error) {
        console.error('Error updating SALN data:', error)
        this.toast.error('Failed to update SALN data')
      }
    },
    handleComplianceChange(type) {
      // Only allow one compliance type to be selected at a time
      if (this.complianceType === type) {
        // If clicking the same checkbox, uncheck it
        this.complianceType = null
        this.assumptionDate = ''
        this.exitDate = ''
        this.complianceYear = new Date().getFullYear() - 1
        this.yearError = ''
      } else {
        // Set the selected type and clear others
        this.complianceType = type
        if (type === 'annual') {
          // Clear date fields when selecting annual
          this.assumptionDate = ''
          this.exitDate = ''
          // Clear year error when switching to annual
          this.yearError = ''
        } else if (type === 'assumption') {
          // Clear exit date and year when selecting assumption
          this.exitDate = ''
          this.complianceYear = new Date().getFullYear() - 1
          this.yearError = ''
        } else if (type === 'exit') {
          // Clear assumption date and year when selecting exit
          this.assumptionDate = ''
          this.complianceYear = new Date().getFullYear() - 1
          this.yearError = ''
        }
      }
      // Hide warning when user makes a selection
      if (this.complianceType && this.filingType) {
        this.showWarning = false
      }
    },
    handleFilingTypeChange() {
      // Hide warning when user makes a selection
      if (this.complianceType && this.filingType) {
        this.showWarning = false
      }
    },
    validateYear() {
      const currentYear = new Date().getFullYear()
      if (this.complianceYear && this.complianceYear >= currentYear) {
        this.yearError = `Year must be before ${currentYear}. Annual filing is for the previous year.`
      } else {
        this.yearError = ''
      }
    },
    getSALNDownloadParamsOrNull() {
      // Validate that both compliance type and filing type are selected
      if (!this.complianceType || !this.filingType) {
        this.showWarning = true
        return null
      }

      // Validate that date/year is provided based on compliance type
      if (this.complianceType === 'annual') {
        if (!this.complianceYear) {
          this.showWarning = true
          this.toast.error('Please enter a year for annual filing')
          return null
        }
        // Validate year is not current year or future
        const currentYear = new Date().getFullYear()
        if (this.complianceYear >= currentYear) {
          this.yearError = `Year must be before ${currentYear}. Annual filing is for the previous year.`
          this.toast.error(`Year must be before ${currentYear}. Annual filing is for the previous year.`)
          return null
        }
        // Clear any previous year error if validation passes
        this.yearError = ''
      } else if (this.complianceType === 'assumption') {
        if (!this.assumptionDate) {
          this.showWarning = true
          this.toast.error('Please enter a date for assumption of office')
          return null
        }
      } else if (this.complianceType === 'exit') {
        if (!this.exitDate) {
          this.showWarning = true
          this.toast.error('Please enter a date for exit')
          return null
        }
      }

      // Prepare compliance date based on type
      let complianceDateValue = ''
      if (this.complianceType === 'annual') {
        complianceDateValue = this.complianceYear.toString()
      } else if (this.complianceType === 'assumption') {
        complianceDateValue = this.assumptionDate
      } else if (this.complianceType === 'exit') {
        complianceDateValue = this.exitDate
      }

      return {
        complianceType: this.complianceType,
        complianceDate: complianceDateValue,
        filing: this.filingType
      }
    },
    async showPrintPreview() {
      const params = this.getSALNDownloadParamsOrNull()
      if (!params) return

      try {
        const userId = this.getCurrentUserId()
        if (!userId) throw new Error('No authenticated user')
        const employeeId = this.employeeData?.id || null
        const targetId = employeeId || userId

        this.showPrint = true
        this.previewLoading = true
        this.previewFileName = `saln_${targetId}.pdf`
        if (this.previewUrl) {
          URL.revokeObjectURL(this.previewUrl)
          this.previewUrl = null
        }

        await ApiService.initSanctum()

        // Build preview URL (same endpoint as download)
        let url = `${ApiService.baseURL}/saln-download/${targetId}`
        const queryParams = new URLSearchParams()
        if (params.complianceType) queryParams.append('complianceType', params.complianceType)
        if (params.complianceDate) queryParams.append('complianceDate', params.complianceDate)
        if (params.filing) queryParams.append('filing', params.filing)
        if (queryParams.toString()) url += '?' + queryParams.toString()

        const token = localStorage.getItem('auth_token')
        const res = await fetch(url, {
          method: 'GET',
          headers: {
            'Accept': 'application/pdf',
            'X-Requested-With': 'XMLHttpRequest',
            ...(token ? { 'Authorization': `Bearer ${token}` } : {})
          },
          credentials: 'include'
        })
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`)
        const blob = await res.blob()
        this.previewUrl = URL.createObjectURL(blob)

        this.showWarning = false
      } catch (error) {
        console.error('SALN print preview failed:', error)
        this.toast.error('Failed to load SALN preview')
        this.showPrint = false
        if (this.previewUrl) {
          URL.revokeObjectURL(this.previewUrl)
          this.previewUrl = null
        }
      } finally {
        this.previewLoading = false
      }
    },
    closePrint() {
      this.showPrint = false
      if (this.previewUrl) {
        URL.revokeObjectURL(this.previewUrl)
        this.previewUrl = null
      }
      this.previewFileName = null
    },
    async downloadFromPreview() {
      if (!this.previewUrl) {
        this.toast.error('Preview not ready yet')
        return
      }
      const a = document.createElement('a')
      a.href = this.previewUrl
      a.download = this.previewFileName || `saln_${new Date().toISOString().split('T')[0]}.pdf`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
    },
    async downloadSALN() {
      const params = this.getSALNDownloadParamsOrNull()
      if (!params) return

      try {
        const userId = this.getCurrentUserId()
        if (!userId) throw new Error('No authenticated user')
        const employeeId = this.employeeData?.id || null
        
        await ApiService.downloadSALN(employeeId || userId, params)
        this.showWarning = false
      } catch (error) {
        console.error('SALN download failed:', error)
        this.toast.error('Failed to download SALN PDF')
      }
    }
  }
}
</script> 