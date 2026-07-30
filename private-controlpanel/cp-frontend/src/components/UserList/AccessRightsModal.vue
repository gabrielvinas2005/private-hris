<template>
  <el-dialog
    v-model="visible"
    title="User Access Rights Informations"
    width="90%"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <div v-if="loading" class="loading-container">
      <el-skeleton :rows="10" animated />
    </div>

    <div v-else-if="userInfo" class="access-rights-container">
      <!-- User Profile Section -->
      <div class="user-profile-section">
        <div class="user-info">
          <el-avatar :size="60" class="user-avatar">
            {{ getInitials(userInfo.name) }}
          </el-avatar>
          <div class="user-details">
            <h3 class="user-name">{{ userInfo.name }}</h3>
            <p class="user-email">{{ userInfo.email }}</p>
          </div>
        </div>

        <!-- Account Settings -->
        <div class="account-settings">
          <div class="setting-row">
            <label class="setting-label">Employee No.</label>
            <el-input 
              v-model="formData.employee_no" 
              placeholder="Employee number"
              style="width: 200px;"
              disabled
            />
          </div>

          <div class="setting-row">
            <el-checkbox v-model="formData.with_expiration">
              With Account Expiration
            </el-checkbox>
            <span v-if="formData.with_expiration" class="expiration-text">
              Set Account Expiration Date.
            </span>
          </div>

          <div v-if="formData.with_expiration" class="setting-row">
            <el-date-picker
              v-model="formData.expiration_date"
              type="date"
              placeholder="MM/DD/YYYY"
              format="MM/DD/YYYY"
              value-format="YYYY-MM-DD"
              style="width: 200px;"
            />
          </div>

          <div class="setting-row">
            <el-checkbox v-model="formData.locked">
              Lock Account
            </el-checkbox>
          </div>

          <div class="setting-row">
            <el-checkbox v-model="formData.access_all_branches">
              Access All Branches
            </el-checkbox>
          </div>

          <div class="setting-row">
            <el-checkbox v-model="formData.is_notify">
              Notify via Email on 201 File updates
            </el-checkbox>
          </div>
        </div>
      </div>

      <!-- Module Tabs -->
      <div class="module-tabs">
        <el-tabs v-model="activeTab" type="card">
          <el-tab-pane label="HR Module" name="hrm">
            <div class="module-content">
              <div class="module-access">
                <el-checkbox v-model="formData.with_hrm_access">
                  Give access to HR Module
                </el-checkbox>
              </div>

              <!-- HR sub menu dropdown -->
              <div class="hr-submenu-select" style="margin-bottom: 12px;">
                <el-dropdown trigger="click" @command="onHrDropdownChange">
                  <span class="el-dropdown-link" style="cursor: pointer; user-select:none;">
                    {{ hrSubMenuLabel }}
                    <el-icon><ArrowDown /></el-icon>
                  </span>
                  <template #dropdown>
                    <el-dropdown-menu>
                      <el-dropdown-item command="hrm">HR Module</el-dropdown-item>
                      <el-dropdown-item command="recruitment">Recruitment</el-dropdown-item>
                    </el-dropdown-menu>
                  </template>
                </el-dropdown>
              </div>

              <div class="menu-table">
                <el-table :data="activeHrMenus" border style="width: 100%">
                  <el-table-column label="Select" width="80">
                    <template #default="{ row }">
                      <el-checkbox 
                        v-model="row.status" 
                        @change="updateMenuAccess(row)"
                      />
                    </template>
                  </el-table-column>
                  <el-table-column prop="menu" label="Menu" />
                  <el-table-column prop="description" label="Description" />
                </el-table>
              </div>
            </div>
          </el-tab-pane>

          <el-tab-pane label="Timekeeping" name="hrt">
            <div class="module-content">
              <div class="module-access">
                <el-checkbox v-model="formData.with_hrt_access">
                  Give access to Timekeeping Module
                </el-checkbox>
              </div>
              
              <div class="menu-table">
                <el-table :data="hrtMenus" border style="width: 100%">
                  <el-table-column label="Select" width="80">
                    <template #default="{ row }">
                      <el-checkbox 
                        v-model="row.status" 
                        @change="updateMenuAccess(row)"
                      />
                    </template>
                  </el-table-column>
                  <el-table-column prop="menu" label="Menu" />
                  <el-table-column prop="description" label="Description" />
                </el-table>
              </div>
            </div>
          </el-tab-pane>

          <el-tab-pane label="Payroll" name="hrp">
            <div class="module-content">
              <div class="module-access">
                <el-checkbox v-model="formData.with_hrp_access">
                  Give access to Payroll Module
                </el-checkbox>
              </div>
              
              <div class="menu-table">
                <el-table :data="hrpMenus" border style="width: 100%">
                  <el-table-column label="Select" width="80">
                    <template #default="{ row }">
                      <el-checkbox 
                        v-model="row.status" 
                        @change="updateMenuAccess(row)"
                      />
                    </template>
                  </el-table-column>
                  <el-table-column prop="menu" label="Menu" />
                  <el-table-column prop="description" label="Description" />
                </el-table>
              </div>
            </div>
          </el-tab-pane>

          <el-tab-pane label="Control Panel" name="cpm">
            <div class="module-content">
              <div class="module-access">
                <el-checkbox v-model="formData.with_cpm_access">
                  Give access to Control Panel Module
                </el-checkbox>
              </div>
              
              <div class="menu-table">
                <el-table :data="cpmMenus" border style="width: 100%">
                  <el-table-column label="Select" width="80">
                    <template #default="{ row }">
                      <el-checkbox 
                        v-model="row.status" 
                        @change="updateMenuAccess(row)"
                      />
                    </template>
                  </el-table-column>
                  <el-table-column prop="menu" label="Menu" />
                  <el-table-column prop="description" label="Description" />
                </el-table>
              </div>
            </div>
          </el-tab-pane>

          <!-- <el-tab-pane label="Learning & Development" name="ld">
            <div class="module-content">
              <div class="module-access">
                <el-checkbox v-model="formData.with_ld_access">
                  Give access to Learning & Development Module
                </el-checkbox>
              </div>
              
              <div class="menu-table">
                <el-table :data="ldMenus" border style="width: 100%">
                  <el-table-column label="Select" width="80">
                    <template #default="{ row }">
                      <el-checkbox 
                        v-model="row.status" 
                        @change="updateMenuAccess(row)"
                      />
                    </template>
                  </el-table-column>
                  <el-table-column prop="menu" label="Menu" />
                  <el-table-column prop="description" label="Description" />
                </el-table>
              </div>
            </div> -->
          <!-- </el-tab-pane> -->
          <el-tab-pane label="Employee Portal" name="ep">
            <div class="module-content">
              <div class="module-access">
                <el-checkbox v-model="formData.with_ep_access">
                  Give access to Employee Portal Module
                </el-checkbox>
              </div>
              
              <div class="menu-table">
                <el-table :data="epMenus" border style="width: 100%">
                  <el-table-column label="Select" width="80">
                    <template #default="{ row }">
                      <el-checkbox 
                        v-model="row.status" 
                        @change="updateMenuAccess(row)"
                      />
                    </template>
                  </el-table-column>
                  <el-table-column prop="menu" label="Menu" />
                  <el-table-column prop="description" label="Description" />
                </el-table>
              </div>
            </div>
          </el-tab-pane>
        </el-tabs>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">Cancel</el-button>
        <el-button 
          type="primary" 
          @click="saveAccessRights"
          :loading="saving"
        >
          Save Access Rights
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { ElMessage } from 'element-plus'
import apiService from '../../Services/api.js'
import { ArrowDown } from '@element-plus/icons-vue'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  userId: {
    type: [String, Number],
    default: null
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'saved'])

// Reactive data
const visible = ref(props.modelValue)
const loading = ref(false)
const saving = ref(false)
const activeTab = ref('hrm')

// User and form data
const userInfo = ref(null)
const formData = reactive({
  employee_no: '',
  with_expiration: false,
  expiration_date: null,
  locked: false,
  access_all_branches: false,
  is_notify: false,
  with_hrm_access: false,
  with_hrt_access: false,
  with_hrp_access: false,
  with_cpm_access: false,
  with_ld_access: false,
  with_mig_access: false,
  with_ep_access: false
})

// Menu data
const hrmMenus = ref([])
const recruitmentMenus = ref([])
const hrtMenus = ref([])
const hrpMenus = ref([])
const cpmMenus = ref([])
const ldMenus = ref([])
const migMenus = ref([])
const epMenus = ref([])

// HR sub menu state
const hrSubMenu = ref('hrm') // 'hrm' | 'recruitment'
const hrSubMenuLabel = computed(() => hrSubMenu.value === 'recruitment' ? 'Recruitment' : 'HR Module')
const activeHrMenus = computed(() => hrSubMenu.value === 'recruitment' ? recruitmentMenus.value : hrmMenus.value)

function onHrDropdownChange(command) {
  hrSubMenu.value = command
}

// Watch for prop changes
watch(() => props.modelValue, (newVal) => {
  visible.value = newVal
  if (newVal && props.userId) {
    loadAccessRights()
  }
})

watch(visible, (newVal) => {
  emit('update:modelValue', newVal)
})

// Methods
async function loadAccessRights() {
  if (!props.userId) return
  
  loading.value = true
  try {
    const response = await apiService.getAccessRights(props.userId)
    
    if (response.success) {
      const data = response.data
      
      // Set user info
      if (data.info && data.info.length > 0) {
        const user = data.info[0]
        userInfo.value = user
        
        // Helper to coerce various truthy values to boolean
        const toBool = (v) => !!(v === true || v === 1 || v === '1' || v === 'true')

        // Populate form data (ensure booleans are coerced)
        formData.employee_no = user.employee_no || ''
        formData.with_expiration = toBool(user.with_expiration)
        // Normalize expiration_date to match date-picker value-format (YYYY-MM-DD)
        formData.expiration_date = user.expiration_date
          ? String(user.expiration_date).substring(0, 10)
          : null
        formData.locked = toBool(user.locked)
        formData.access_all_branches = toBool(user.access_all_branches)
        formData.is_notify = toBool(user.is_notify)
        formData.with_hrm_access = toBool(user.with_hrm_access)
        formData.with_hrt_access = toBool(user.with_hrt_access)
        formData.with_hrp_access = toBool(user.with_hrp_access)
        formData.with_cpm_access = toBool(user.with_cpm_access)
        formData.with_ld_access = toBool(user.with_ld_access)
        formData.with_mig_access = toBool(user.with_mig_access)
        formData.with_ep_access = toBool(user.with_ep_access)
      }
      
      // Set menu data and coerce status to boolean so checkboxes reflect current access
      const toBooleanMenus = (menus) => (menus || []).map(m => ({
        ...m,
        status: !!(m.status === true || m.status === 1 || m.status === '1')
      }))

      hrmMenus.value = toBooleanMenus(data.hrm_menu)
      recruitmentMenus.value = toBooleanMenus(data.recruitment_menu)
      hrtMenus.value = toBooleanMenus(data.hrt_menu)
      hrpMenus.value = toBooleanMenus(data.hrp_menu)
      cpmMenus.value = toBooleanMenus(data.cpm_menu)
      ldMenus.value = toBooleanMenus(data.ld_menu)
      migMenus.value = toBooleanMenus(data.mig_menu)
      epMenus.value = toBooleanMenus(data.ep_menu)
    }
  } catch (error) {
    console.error('Error loading access rights:', error)
    ElMessage.error('Failed to load access rights data')
  } finally {
    loading.value = false
  }
}

function updateMenuAccess(menu) {
  // This will be handled by the checkbox v-model
  console.log('Menu access updated:', menu)
}

async function saveAccessRights() {
  saving.value = true
  try {
    // Prepare form data
    const submitData = { ...formData }
    
    // Prepare menu data - send each menu with its status
    const allMenus = [
      ...hrmMenus.value,
      ...recruitmentMenus.value,
      ...hrtMenus.value,
      ...hrpMenus.value,
      ...cpmMenus.value,
      ...ldMenus.value,
      ...migMenus.value,
      ...epMenus.value
    ]
    
    // Create array of menu access objects
    const menuAccessData = allMenus.map(menu => ({
      menu_id: menu.id,
      status: menu.status ? 1 : 0
    }))
    
    submitData.menu_access = menuAccessData
    
    const response = await apiService.updateAccessRights(props.userId, submitData)
    
    if (response.success) {
      ElMessage.success('Access rights updated successfully!')
      emit('saved')
      handleClose()
    } else {
      ElMessage.error(response.message || 'Failed to update access rights')
    }
  } catch (error) {
    console.error('Error saving access rights:', error)
    ElMessage.error('Failed to save access rights')
  } finally {
    saving.value = false
  }
}

function handleClose() {
  visible.value = false
  // Reset form data
  Object.keys(formData).forEach(key => {
    if (typeof formData[key] === 'boolean') {
      formData[key] = false
    } else {
      formData[key] = null
    }
  })
}

function getInitials(name) {
  if (!name) return 'U'
  return name.split('.').map(part => part.charAt(0)).join('').toUpperCase()
}
</script>

<style scoped>
.access-rights-container {
  max-height: 70vh;
  overflow-y: auto;
}

.user-profile-section {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}

.user-avatar {
  background: #6c757d;
  color: white;
  font-weight: bold;
}

.user-details h3 {
  margin: 0 0 5px 0;
  color: #333;
}

.user-details p {
  margin: 0;
  color: #666;
  font-size: 14px;
}

.account-settings {
  display: grid;
  gap: 15px;
}

.setting-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.setting-label {
  font-weight: 500;
  min-width: 120px;
}

.expiration-text {
  color: #dc3545;
  font-size: 14px;
}

.module-tabs {
  margin-top: 20px;
}

.module-content {
  padding: 20px 0;
}

.module-access {
  margin-bottom: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 6px;
}

.menu-table {
  max-height: 400px;
  overflow-y: auto;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.loading-container {
  padding: 20px;
}
</style>
