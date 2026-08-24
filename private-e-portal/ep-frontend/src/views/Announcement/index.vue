<template>
	<MainLayout>
		<div class="mb-6 flex justify-between items-center">
			<div>
				<h2 class="text-2xl font-bold text-slate-900">Announcements</h2>
				<p class="text-slate-600">Organization-wide news and advisories</p>
			</div>
			<el-button 
				v-if="hasHRAccess" 
				type="primary" 
				@click="openCreateModal"
				:icon="Plus"
			>
				Create Announcement
			</el-button>
		</div>
		<AnnouncementList :items="announcements" />

		<!-- Create Announcement Modal -->
		<el-dialog
			v-model="createModalVisible"
			title="Create Announcement"
			width="600px"
			:close-on-click-modal="false"
		>
			<el-form
				ref="announcementFormRef"
				:model="announcementForm"
				:rules="announcementRules"
				label-width="120px"
			>
				<el-form-item label="Title" prop="title">
					<el-input
						v-model="announcementForm.title"
						placeholder="Enter announcement title"
						maxlength="255"
						show-word-limit
					/>
				</el-form-item>
				<el-form-item label="Content" prop="content">
					<el-input
						v-model="announcementForm.content"
						type="textarea"
						:rows="6"
						placeholder="Enter announcement content"
					/>
				</el-form-item>
				<el-form-item label="Type">
					<el-radio-group v-model="announcementForm.isGlobal">
						<el-radio :label="true">Global (All Employees)</el-radio>
						<el-radio :label="false">Personal</el-radio>
					</el-radio-group>
				</el-form-item>
				<el-form-item 
					v-if="!announcementForm.isGlobal" 
					label="Employee" 
					prop="employee_id"
				>
					<el-select
						v-model="announcementForm.employee_id"
						placeholder="Select employee"
						filterable
						style="width: 100%"
					>
						<el-option
							v-for="emp in employees"
							:key="emp.id"
							:label="emp.name"
							:value="emp.id"
						/>
					</el-select>
				</el-form-item>
			</el-form>
			<template #footer>
				<span class="dialog-footer">
					<el-button @click="closeCreateModal">Cancel</el-button>
					<el-button type="primary" @click="submitAnnouncement" :loading="submitting">
						Create
					</el-button>
				</span>
			</template>
		</el-dialog>
	</MainLayout>
</template>

<script>
import MainLayout from '../../layout/MainLayout.vue'
import AnnouncementList from '../../components/Announcement/AnnouncementList.vue'
import { Plus } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

export default {
	name: 'AnnouncementsView',
	components: { MainLayout, AnnouncementList },
	data() {
		return {
			announcements: [],
			hasHRAccess: false,
			createModalVisible: false,
			submitting: false,
			employees: [],
			announcementForm: {
				title: '',
				content: '',
				isGlobal: true,
				employee_id: null
			},
			announcementRules: {
				title: [
					{ required: true, message: 'Please enter announcement title', trigger: 'blur' }
				],
				content: [
					{ required: true, message: 'Please enter announcement content', trigger: 'blur' }
				],
				employee_id: [
					{ 
						validator: (rule, value, callback) => {
							if (!this.announcementForm.isGlobal && !value) {
								callback(new Error('Please select an employee'))
							} else {
								callback()
							}
						}, 
						trigger: 'change' 
					}
				]
			},
			Plus
		}
	},
	async mounted() {
		await this.loadAnnouncements()
		this.checkHRAccess()
		if (this.hasHRAccess) {
			await this.loadEmployees()
		}
	},
	methods: {
		checkHRAccess() {
			const storedUserData = localStorage.getItem('user_data')
			if (storedUserData) {
				const userData = JSON.parse(storedUserData)
				this.hasHRAccess = !!(
					userData.with_hrm_access || 
					userData.is_admin || 
					userData.user_type_id === 1 || 
					userData.role === 'admin' || 
					userData.role === 'hr' ||
					userData.user_role === 'admin' ||
					userData.user_role === 'hr'
				)
			} else {
				// Default to true if in dev mode or session active
				this.hasHRAccess = true
			}
		},
		async loadAnnouncements() {
			try {
				const ApiService = (await import('../../services/api.js')).default
				const res = await ApiService.getAnnouncements()
				if (res && res.success) this.announcements = Array.isArray(res.data) ? res.data : []
			} catch (e) {
				console.error('Failed to load announcements:', e)
			}
		},
		async loadEmployees() {
			try {
				const ApiService = (await import('../../services/api.js')).default
				const res = await ApiService.getAnnouncementEmployees()
				if (res && res.success && Array.isArray(res.data)) {
					this.employees = res.data
				}
			} catch (e) {
				console.error('Failed to load employees:', e)
			}
		},
		openCreateModal() {
			this.createModalVisible = true
			this.announcementForm = {
				title: '',
				content: '',
				isGlobal: true,
				employee_id: null
			}
			this.$nextTick(() => {
				if (this.$refs.announcementFormRef) {
					this.$refs.announcementFormRef.clearValidate()
				}
			})
		},
		closeCreateModal() {
			this.createModalVisible = false
			this.announcementForm = {
				title: '',
				content: '',
				isGlobal: true,
				employee_id: null
			}
		},
		async submitAnnouncement() {
			if (!this.$refs.announcementFormRef) return

			await this.$refs.announcementFormRef.validate(async (valid) => {
				if (!valid) return

				this.submitting = true
				try {
					const ApiService = (await import('../../services/api.js')).default
					const payload = {
						title: this.announcementForm.title,
						content: this.announcementForm.content,
						employee_id: this.announcementForm.isGlobal ? null : this.announcementForm.employee_id
					}

					const res = await ApiService.createAnnouncement(payload)
					if (res && res.success) {
						ElMessage.success('Announcement created successfully')
						this.closeCreateModal()
						await this.loadAnnouncements()
					} else {
						ElMessage.error(res?.message || 'Failed to create announcement')
					}
				} catch (e) {
					console.error('Failed to create announcement:', e)
					ElMessage.error(e?.message || 'Failed to create announcement')
				} finally {
					this.submitting = false
				}
			})
		}
	}
}
</script>

<style scoped>
</style>


