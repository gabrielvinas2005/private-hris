<template>
  <el-dialog
    v-model="isVisible"
    v-if="offer"
    width="640px"
    class="offer-details-dialog"
    :close-on-click-modal="false"
    destroy-on-close
    @close="handleClose"
  >
    <template #header>
      <div class="dialog-header">
        <div class="dialog-icon bg-blue-600">
          <i class="fas fa-briefcase"></i>
        </div>
        <div class="dialog-header-text">
          <h3 class="dialog-title">{{ offer.position }}</h3>
          <p class="dialog-subtitle">
            {{ offer.department || "Department not specified" }}
          </p>
        </div>
        <span
          :class="[
            'dialog-status-pill',
            statusClass === 'status-hiring' ? 'bg-blue-600' : '',
            statusClass,
          ]"
        >
          {{ statusText }}
        </span>
      </div>
    </template>

    <div class="dialog-body" v-if="offer">
      <!-- Congratulations Message -->
      <div class="congrats-card">
        <h4 class="congrats-title">
          Warm greetings from {{ companyName }}!
        </h4>
        <p class="congrats-text">
          After a careful and thorough evaluation of your qualifications and
          competencies, we are pleased to inform you that you have been
          <strong>accepted</strong> for the position of
          <strong>{{ offer.position }}</strong
          >.
        </p>

        <div class="congrats-summary-grid">
          <div class="summary-item">
            <span class="summary-label">Position</span>
            <span class="summary-value">{{ offer.position }}</span>
          </div>
          <div class="summary-item">
            <span class="summary-label">Monthly Salary</span>
            <span class="summary-value">
              <span v-if="offer.salary">
                ₱{{ formatSalary(offer.salary) }}
              </span>
              <span v-else>N/A</span>
            </span>
          </div>
          <div class="info-item">
            <span class="info-label">Offer Date</span>
            <span class="info-value">
              {{ formatDate(offer.appliedDate) }}
            </span>
          </div>
        </div>

        <div class="submission-note">
          <i class="fas fa-info-circle"></i>
          <p>
            You are required to submit
            <strong>all pre-employment requirements</strong>
            on or before
            <strong>
              {{
                formatDate(
                  offer.responseDeadline ||
                    offer.startDate ||
                    offer.appliedDate,
                )
              }}
            </strong>
            . Failure to submit on time may result in the withdrawal of this
            offer.
          </p>
        </div>
      </div>

      <div class="dialog-salary-card bg-blue-600">
        <div>
          <p class="salary-label">Monthly Salary</p>
          <p class="salary-amount">
            <span v-if="offer.salary"> ₱{{ formatSalary(offer.salary) }} </span>
            <span v-else>N/A</span>
          </p>
        </div>
        <div class="salary-grade-step" v-if="offer.grade || offer.step">
          <span v-if="offer.grade">SG {{ offer.grade }}</span>
          <span v-if="offer.step">Step {{ offer.step }}</span>
        </div>
      </div>

      <!-- Pre-employment Requirements -->
      <div class="dialog-section">
        <ul class="requirements-list">
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-file-alt"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">NBI Clearance</div>
              <div class="requirement-text">Updated, original copy</div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-file-alt"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">Drug Test Result</div>
              <div class="requirement-text">Original copy</div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-file-alt"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">Medical Certificate</div>
              <div class="requirement-text">
                CSC Form No. 211, Revised 2018, signed by a government physician
                with attached laboratory results (all original copies)
              </div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-id-card"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">Vaccination Card</div>
              <div class="requirement-text">Photocopy and/or original</div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-id-badge"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">Birth Certificate</div>
              <div class="requirement-text">Authenticated copy</div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-ring"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">Marriage Certificate</div>
              <div class="requirement-text">
                If applicable, authenticated copy
              </div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-file-invoice-peso"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">TIN ID / BIR Forms</div>
              <div class="requirement-text">
                TIN ID or BIR Form Nos. 1902 / 1905 / 2316
              </div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">
                Transcript of Records &amp; Diploma
              </div>
              <div class="requirement-text">Authenticated copies</div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-certificate"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">
                CSC Eligibility / PRC Board Rating
              </div>
              <div class="requirement-text">
                If applicable, authenticated copy
              </div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-file-alt"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">Personal Data Sheet (PDS)</div>
              <div class="requirement-text">
                CSC Form 212, Revised 2017, printed on legal size paper, 3
                copies
              </div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-briefcase"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">Work Experience Sheet</div>
              <div class="requirement-text">
                CSC Form No. 212 Attachment – Work Experience Sheet (WES)
              </div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-building"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">Certificates of Employment</div>
              <div class="requirement-text">
                Certificates of Employment from previous jobs
              </div>
            </div>
          </li>
          <li class="requirement-card">
            <div class="requirement-icon">
              <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="requirement-content">
              <div class="requirement-title">
                Training / Seminar Certificates
              </div>
              <div class="requirement-text">
                Certificates of trainings and seminars attended
              </div>
            </div>
          </li>
        </ul>
      </div>

      <div
        v-if="offer.requirements && offer.requirements.length"
        class="dialog-section"
      >
        <h4 class="section-title">Minimum Qualifications</h4>
        <ul class="section-list">
          <li v-for="(req, index) in offer.requirements" :key="index">
            <i class="fas fa-check-circle list-icon"></i>
            <span>{{ req }}</span>
          </li>
        </ul>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <button
          type="button"
          class="btn-decline"
          @click="handleReject"
          :disabled="isProcessing || offer?.canReject === false"
        >
          <i class="fas fa-times-circle"></i>
          <span>Decline Offer</span>
        </button>
        <button
          type="button"
          class="btn-accept"
          @click="handleAccept"
          :disabled="isProcessing || offer?.canAccept === false"
        >
          <i class="fas fa-check-circle"></i>
          <span>Accept Offer</span>
        </button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { useJobOffer } from "@/composables/useJobOffer";
import { useCompanyBranding } from "@/composables/useCompanyBranding.js";

const { companyName, loadCompany } = useCompanyBranding();

onMounted(() => {
  loadCompany();
});

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  offer: {
    type: Object,
    default: null,
  },
  processingOfferId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits(["update:modelValue", "accept", "reject"]);

const {
  getStatusClass,
  getStatusText,
  formatDate,
  formatSalary,
  handleAcceptOffer,
  handleRejectOffer,
} = useJobOffer();

const isVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
});

const statusClass = computed(() =>
  props.offer ? getStatusClass(props.offer) : "",
);
const statusText = computed(() =>
  props.offer ? getStatusText(props.offer) : "",
);

const isProcessing = computed(
  () => props.processingOfferId === props.offer?.id,
);

const handleClose = () => {
  emit("update:modelValue", false);
};

const handleAccept = async () => {
  if (props.offer) {
    await handleAcceptOffer(props.offer, (offerId) => {
      emit("accept", offerId);
    });
  }
};

const handleReject = async () => {
  if (props.offer) {
    await handleRejectOffer(props.offer, (offerId) => {
      emit("reject", offerId);
    });
  }
};
</script>

<style scoped>
/* Offer Details Dialog */
.offer-details-dialog :deep(.el-dialog) {
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
}

.offer-details-dialog :deep(.el-dialog__header) {
  padding: 1.2rem 1.75rem 0.8rem;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.offer-details-dialog :deep(.el-dialog__body) {
  padding: 1.5rem 1.75rem 1.75rem;
  background: #f9fafb;
  max-height: 65vh;
  overflow-y: auto;
}

.offer-details-dialog :deep(.el-dialog__footer) {
  padding: 0.9rem 1.75rem 1.1rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.dialog-header {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.dialog-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 4px 10px rgba(102, 126, 234, 0.4);
  flex-shrink: 0;
}

.dialog-icon i {
  font-size: 1.25rem;
}

.dialog-header-text {
  flex: 1;
  min-width: 0;
}

.dialog-title {
  margin: 0 0 0.15rem 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: #111827;
}

.dialog-subtitle {
  margin: 0;
  font-size: 0.875rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dialog-status-pill {
  padding: 0.35rem 0.9rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
  color: #fff;
}

.dialog-status-pill.status-hiring {
  background: linear-gradient(135deg, #2294fe 0%, #2294fe 100%);
}

.dialog-status-pill.status-accepted {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.dialog-status-pill.status-declined {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.dialog-status-pill.status-pending {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.dialog-body {
  margin-top: 0.75rem;
}

.congrats-card {
  background: #ffffff;
  border-radius: 14px;
  padding: 1rem 1.25rem;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
  margin-bottom: 1.1rem;
  border: 1px solid #e5e7eb;
}

.congrats-title {
  margin: 0 0 0.35rem 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #111827;
}

.congrats-text {
  margin: 0;
  font-size: 0.9rem;
  color: #4b5563;
  line-height: 1.5;
}

.congrats-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem 1rem;
  margin-top: 0.9rem;
}

.summary-item {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.summary-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #9ca3af;
}

.summary-value {
  font-size: 0.9rem;
  font-weight: 600;
  color: #111827;
}

.submission-note {
  margin-top: 0.9rem;
  padding: 0.65rem 0.75rem;
  border-radius: 10px;
  background: #eff6ff;
  border: 1px dashed #bfdbfe;
  display: flex;
  gap: 0.6rem;
  align-items: flex-start;
}

.submission-note i {
  color: #2563eb;
  margin-top: 0.15rem;
}

.submission-note p {
  margin: 0;
  font-size: 0.85rem;
  color: #1f2937;
  line-height: 1.5;
}

.dialog-salary-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-radius: 14px;
  color: #fff;
  box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
  margin-bottom: 1.25rem;
}

.salary-label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  opacity: 0.9;
  margin-bottom: 0.15rem;
}

.salary-amount {
  font-size: 1.4rem;
  font-weight: 700;
}

.salary-grade-step {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.1rem;
  font-size: 0.8rem;
  font-weight: 600;
}

.dialog-section {
  margin-bottom: 1.25rem;
}

.section-title {
  margin: 0 0 0.35rem 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: #111827;
}

.section-text {
  margin: 0;
  font-size: 0.9rem;
  color: #4b5563;
  line-height: 1.6;
}

.section-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.section-list li {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #4b5563;
}

.list-icon {
  color: #10b981;
  margin-top: 0.15rem;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.dialog-footer button {
  padding: 0.8rem 1.6rem;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
  transition: all 0.2s ease;
}

.dialog-footer button i {
  font-size: 1rem;
}

.dialog-footer .btn-accept:hover:not(:disabled),
.dialog-footer .btn-decline:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
}

.dialog-footer button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-accept {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.btn-decline {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

/* Requirements list layout */
.requirements-list {
  margin-top: 0.6rem;
  padding-top: 0.4rem;
  border-top: 1px dashed #e5e7eb;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem 1.25rem;
}

.requirement-card {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  padding: 0.6rem 0.75rem;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
  border: 1px solid #e5e7eb;
}

.requirement-icon {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  background: #ecfdf5;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.requirement-icon i {
  color: #047857;
  font-size: 0.9rem;
}

.requirement-content {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.requirement-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #111827;
}

.requirement-text {
  font-size: 0.8rem;
  color: #4b5563;
  line-height: 1.45;
}

@media (max-width: 768px) {
  .congrats-summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .dialog-salary-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.6rem;
  }
}

@media (max-width: 640px) {
  .congrats-summary-grid {
    grid-template-columns: 1fr;
  }

  .requirements-list {
    grid-template-columns: 1fr;
  }
}
</style>
