<template>
  <el-dialog
    :model-value="visible"
    @update:model-value="$emit('update:visible', $event)"
    title="Web Time Clock Terminal"
    width="540px"
    class="clock-modal-dialog"
    destroy-on-close
  >
    <div class="space-y-5">
      <!-- Biometric Override Alert -->
      <div v-if="isBiometricLocked" class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
          <h4 class="font-bold text-amber-900">Biometric Log Detected</h4>
          <p class="mt-0.5">Your time entry for today was already recorded via biometric hardware device at {{ biometricTime || 'earlier today' }}. Web clocking is disabled per company policy.</p>
        </div>
      </div>

      <div v-else>
        <!-- Mode indicator -->
        <div class="text-center p-4 bg-slate-900 rounded-2xl text-white">
          <span class="text-xs uppercase tracking-widest text-slate-400 font-semibold block mb-1">Target Action</span>
          <h3 class="text-2xl font-black" :class="isClockedIn ? 'text-rose-400' : 'text-emerald-400'">
            {{ isClockedIn ? 'CLOCK OUT' : 'CLOCK IN' }}
          </h3>
          <p class="text-xs text-slate-400 mt-1">{{ currentTimeFormatted }}</p>
        </div>

        <!-- Geofencing Status Indicator (shown only when enforce_geofence is enabled from Control Panel) -->
        <div v-if="enforceGeofence" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div :class="geoStatusClass" class="p-2 rounded-lg">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
            <div>
              <span class="text-xs text-slate-500 font-medium block">WFH GPS Location Logging</span>
              <span class="text-xs font-bold" :class="geoTextClass">{{ geoStatusText }}</span>
            </div>
          </div>
          <el-button size="small" type="info" link @click="fetchLocation">
            Refresh GPS
          </el-button>
        </div>
        <div v-else class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500 flex items-center gap-2">
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          </svg>
          GPS location tracking is disabled for your account.
        </div>

        <!-- Camera / Selfie Capture (shown only when require_selfie is enabled from Control Panel) -->
        <div v-if="requireSelfie" class="border border-slate-200 rounded-xl p-3.5 bg-white">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Anti-Buddy Punch Selfie Verification
            </span>
            <el-switch v-model="cameraEnabled" size="small" />
          </div>

          <div v-if="cameraEnabled" class="relative rounded-lg overflow-hidden bg-slate-900 aspect-video flex items-center justify-center">
            <video ref="videoRef" autoplay playsinline class="w-full h-full object-cover" v-show="!capturedImage"></video>
            <img v-if="capturedImage" :src="capturedImage" class="w-full h-full object-cover" alt="Selfie preview" />
            
            <div class="absolute bottom-2 flex gap-2">
              <button 
                v-if="!capturedImage" 
                @click="takeSelfie"
                type="button"
                class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs px-3 py-1.5 rounded-lg font-semibold shadow"
              >
                Snap Photo
              </button>
              <button 
                v-else 
                @click="retakeSelfie"
                type="button"
                class="bg-slate-700 hover:bg-slate-600 text-white text-xs px-3 py-1.5 rounded-lg font-semibold shadow"
              >
                Retake
              </button>
            </div>
          </div>
        </div>

        <!-- Manual Fallback Reason Input (only relevant when GPS is denied or unavailable) -->
        <div v-if="enforceGeofence && geoDenied" class="mt-3">
          <label class="block text-xs font-semibold text-amber-800 mb-1">
            Location Note / Reason (Required when GPS is unavailable)
          </label>
          <el-input
            v-model="manualReason"
            type="textarea"
            :rows="2"
            placeholder="Provide WFH location details..."
          />
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-3">
        <el-button @click="$emit('update:visible', false)">Cancel</el-button>
        <el-button
          v-if="!isBiometricLocked"
          :type="isClockedIn ? 'danger' : 'success'"
          :loading="submitting"
          @click="submitPunch"
        >
          Confirm {{ isClockedIn ? 'Clock Out' : 'Clock In' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script>
import { useToast } from 'vue-toastification'

export default {
  name: 'ClockPanelModal',
  props: {
    visible: { type: Boolean, default: false },
    isClockedIn: { type: Boolean, default: false },
    isBiometricLocked: { type: Boolean, default: false },
    biometricTime: { type: String, default: null },
    // Control Panel configurable feature flags
    requireSelfie:    { type: Boolean, default: true },
    enforceGeofence:  { type: Boolean, default: true }
  },
  emits: ['update:visible', 'punch-success'],
  data() {
    return {
      toast: useToast(),
      submitting: false,
      currentTimeFormatted: '',
      locationWithin: true,
      geoDenied: false,
      geoStatusText: 'Checking location...',
      latitude: null,
      longitude: null,
      cameraEnabled: false,
      capturedImage: null,
      manualReason: '',
      mediaStream: null
    }
  },
  computed: {
    geoStatusClass() {
      if (this.geoDenied) return 'bg-amber-100 text-amber-700'
      return this.latitude ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'
    },
    geoTextClass() {
      if (this.geoDenied) return 'text-amber-700'
      return this.latitude ? 'text-emerald-700' : 'text-slate-700'
    }
  },
  watch: {
    visible(val) {
      if (val) {
        this.updateTime()
        this.fetchLocation()
      } else {
        this.stopCamera()
      }
    },
    cameraEnabled(val) {
      if (val) this.startCamera()
      else this.stopCamera()
    }
  },
  methods: {
    updateTime() {
      this.currentTimeFormatted = new Date().toLocaleTimeString()
    },
    fetchLocation() {
      if (!navigator.geolocation) {
        this.geoDenied = true
        this.geoStatusText = 'GPS Not Supported — Reason Required'
        return
      }
      this.geoStatusText = 'Acquiring GPS location...'
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          this.latitude = pos.coords.latitude
          this.longitude = pos.coords.longitude
          this.geoDenied = false
          this.locationWithin = true
          this.geoStatusText = `GPS Captured (Lat: ${pos.coords.latitude.toFixed(4)}, Long: ${pos.coords.longitude.toFixed(4)})`
        },
        (err) => {
          this.geoDenied = true
          this.locationWithin = false
          this.geoStatusText = 'Location Access Denied — Reason Required'
        },
        { timeout: 8000 }
      )
    },
    async startCamera() {
      try {
        this.mediaStream = await navigator.mediaDevices.getUserMedia({ video: true })
        if (this.$refs.videoRef) {
          this.$refs.videoRef.srcObject = this.mediaStream
        }
      } catch (err) {
        this.toast.warning('Camera access denied or unavailable.')
        this.cameraEnabled = false
      }
    },
    stopCamera() {
      if (this.mediaStream) {
        this.mediaStream.getTracks().forEach(track => track.stop())
        this.mediaStream = null
      }
      this.capturedImage = null
    },
    takeSelfie() {
      const video = this.$refs.videoRef
      if (!video) return
      const canvas = document.createElement('canvas')
      canvas.width = video.videoWidth || 320
      canvas.height = video.videoHeight || 240
      const ctx = canvas.getContext('2d')
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
      this.capturedImage = canvas.toDataURL('image/png')
    },
    retakeSelfie() {
      this.capturedImage = null
    },
    async submitPunch() {
      if (this.enforceGeofence && this.geoDenied && !this.manualReason.trim()) {
        this.toast.error('Please provide a location note or reason when GPS is unavailable.')
        return
      }

      this.submitting = true
      try {
        const payload = {
          user_id: JSON.parse(localStorage.getItem('user_data') || '{}').id,
          action: this.isClockedIn ? 'out' : 'in',
          latitude: this.latitude,
          longitude: this.longitude,
          geofence_status: this.locationWithin ? 'within' : 'outside',
          reason: this.manualReason,
          selfie: this.capturedImage
        }

        const { dtrApiService } = await import('../../services/apiService.js')
        const response = await dtrApiService.webClockPunch(payload)

        const newClockStatus = !this.isClockedIn
        localStorage.setItem('is_clocked_in', newClockStatus ? 'true' : 'false')

        window.dispatchEvent(new CustomEvent('dtr-updated'))

        this.toast.success(`Successfully clocked ${this.isClockedIn ? 'out' : 'in'}!`)
        this.$emit('punch-success', response.data || response)
        this.$emit('update:visible', false)
      } catch (err) {
        this.toast.error(err.response?.data?.message || 'Failed to submit clock punch.')
      } finally {
        this.submitting = false
      }
    }
  }
}
</script>

<style scoped>
.clock-modal-dialog :deep(.el-dialog__body) {
  padding-top: 10px;
}
</style>
