<template>
  <div
    v-if="application"
    class="modal-backdrop fixed inset-0 flex items-center justify-center z-50 p-4"
    @click.self="close"
  >
    <div
      class="modal-card bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden max-h-[90vh] flex flex-col"
    >
      <!-- Header -->
      <header class="modal-header flex-shrink-0">
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0 flex-1">
            <h1 class="modal-title">
              {{ application.position }}
            </h1>
            <p class="modal-subtitle">
              {{ application.department || "Department TBD" }}
            </p>
          </div>
          <button
            type="button"
            class="modal-close"
            aria-label="Close"
            @click="close"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-meta">
          <div
            v-if="application.type === 'plantilla' && application.jobCode"
            class="meta-item"
          >
            <i class="fas fa-briefcase meta-icon"></i>
            <div>
              <span class="meta-label">Job Code</span>
              <span class="meta-value">{{ application.jobCode }}</span>
            </div>
          </div>
          <div class="meta-item">
            <i class="fas fa-map-marker-alt meta-icon"></i>
            <div>
              <span class="meta-label">Unit</span>
              <span class="meta-value">{{
                application.location || "N/A"
              }}</span>
            </div>
          </div>
          <div class="meta-item">
            <i class="fas fa-calendar-alt meta-icon"></i>
            <div>
              <span class="meta-label">Applied</span>
              <span class="meta-value">{{
                formatDate(application.appliedDate)
              }}</span>
            </div>
          </div>
          <div v-if="application.salary" class="meta-item">
            <i class="fas fa-money-bill-wave meta-icon"></i>
            <div>
              <span class="meta-label">Salary</span>
              <span class="meta-value"
                >₱{{ formatSalary(application.salary) }}</span
              >
            </div>
          </div>
        </div>
      </header>

      <!-- Body -->
      <section class="modal-body flex-1 overflow-y-auto">
        <!-- Status card -->
        <div class="section">
          <h2 class="section-title">Application Status</h2>
          <span
            :class="getStatusClass(application.status)"
            class="status-badge"
          >
            <i
              :class="getStatusIcon(application.status)"
              class="status-icon"
            ></i>
            {{ formatStatus(application.status) }}
          </span>
        </div>

        <!-- Job description card -->
        <div v-if="application.description" class="section section-card">
          <h2 class="section-title">Job Description</h2>
          <p class="section-text">{{ application.description }}</p>
        </div>

        <!-- Requirements -->
        <div
          v-if="application.requirements?.length"
          class="section section-card"
        >
          <h2 class="section-title">Requirements</h2>
          <ul class="requirements-list">
            <li
              v-for="(req, index) in application.requirements"
              :key="index"
              class="requirement-item"
            >
              <span class="requirement-check"></span>
              <span class="requirement-text">{{ req }}</span>
            </li>
          </ul>
        </div>

        <!-- Actions -->
        <div class="modal-actions">
          <button
            v-if="application.canWithdraw"
            type="button"
            class="btn btn-withdraw"
            :disabled="withdrawing"
            @click="handleWithdraw"
          >
            <i class="fas fa-times-circle btn-icon"></i>
            {{ withdrawing ? "Withdrawing..." : "Withdraw Application" }}
          </button>
          <button type="button" class="btn btn-close" @click="close">
            Close
          </button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  application: {
    type: Object,
    default: null,
  },
  withdrawing: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["close", "withdraw"]);

const close = () => {
  emit("close");
};

const handleWithdraw = () => {
  emit("withdraw", props.application);
};

const formatDate = (date) => {
  if (!date) return "N/A";
  return new Date(date).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const formatSalary = (amount) => {
  if (amount == null || amount === "") return "N/A";
  const num = Number(amount);
  if (Number.isNaN(num)) return "N/A";
  return num.toLocaleString("en-PH", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
};

const normalizeStatus = (status) => {
  if (status == null) return "";
  const s = String(status).toLowerCase().trim();
  if (s.includes("for hiring")) return "for-hiring";
  if (s.includes("under review")) return "under-review";
  return s.replace(/\s+/g, "-");
};

const formatStatus = (status) => {
  const normalized = normalizeStatus(status);
  const statusMap = {
    pending: "Pending",
    "under-review": "Under Review",
    shortlisted: "Shortlisted",
    rejected: "Rejected",
    "not-qualified": "Not Qualified",
    "will-not-proceed": "Will not proceed",
    accepted: "Accepted",
    active: "Active",
    withdrawn: "Withdrawn",
    "for-hiring": "For Hiring",
  };
  return (
    statusMap[normalized] || (status && String(status).trim()) || "Pending"
  );
};

const getStatusClass = (status) => {
  const normalized = normalizeStatus(status);
  const statusClasses = {
    pending: "status-pending",
    "under-review": "status-under-review",
    shortlisted: "status-shortlisted",
    rejected: "status-rejected",
    "not-qualified": "status-rejected",
    "will-not-proceed": "status-rejected",
    accepted: "status-accepted",
    active: "status-active",
    withdrawn: "status-withdrawn",
    "for-hiring": "status-for-hiring",
  };
  return statusClasses[normalized] || "status-default";
};

const getStatusIcon = (status) => {
  const normalized = normalizeStatus(status);
  const statusIcons = {
    pending: "fas fa-clock",
    "under-review": "fas fa-search",
    shortlisted: "fas fa-star",
    rejected: "fas fa-times-circle",
    "not-qualified": "fas fa-times-circle",
    "will-not-proceed": "fas fa-times-circle",
    accepted: "fas fa-check-circle",
    active: "fas fa-check-circle",
    withdrawn: "fas fa-ban",
    "for-hiring": "fas fa-handshake",
  };
  return statusIcons[normalized] || "fas fa-question-circle";
};
</script>

<style scoped>
.modal-backdrop {
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
}

.modal-card {
  animation: modalIn 0.2s ease-out;
}

@keyframes modalIn {
  from {
    opacity: 0;
    transform: scale(0.98) translateY(-8px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

/* Header */
.modal-header {
  background: linear-gradient(135deg, #1aa0e6 0%, #1993dc 50%, #1885d1 100%);
  color: #f8fafc;
  padding: 1.75rem 1.5rem;
}

.modal-title {
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 1.25;
  margin: 0 0 0.25rem 0;
  color: #fff;
}

.modal-subtitle {
  font-size: 0.875rem;
  color: #ffffff;
  margin: 0;
}

.modal-close {
  flex-shrink: 0;
  width: 2.25rem;
  height: 2.25rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.08);
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  transition:
    color 0.15s,
    background 0.15s;
}

.modal-close:hover {
  color: #fff;
  background: rgba(255, 255, 255, 0.15);
}

.modal-meta {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 1rem;
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.meta-item {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.meta-icon {
  width: 17px;
  color: #404952;
  font-size: 0.75rem;
  margin-top: 0.125rem;
}

.meta-item div {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.meta-label {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #fff;
}

.meta-value {
  font-size: 0.875rem;
  font-weight: 500;
  color: #e2e8f0;
}

/* Body */
.modal-body {
  padding: 1.5rem;
}

/* APPLICATION STATUS */
.section {
  margin-bottom: 1.5rem;
}

.section:last-of-type {
  margin-bottom: 0;
}

.section-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  margin: 0 0 0.75rem 0;
}

.section-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1rem 1.25rem;
}

.section-text {
  margin: 0;
  font-size: 0.9375rem;
  line-height: 1.6;
  color: #475569;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 10px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 9999px;
}

.status-icon {
  font-size: 12px;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}
.status-under-review {
  background: #dbeafe;
  color: #1d4ed8;
}
.status-shortlisted {
  background: #ecfdf3;
  color: #166534;
}
.status-rejected {
  background: #fee2e2;
  color: #b91c1c;
}
.status-accepted {
  background: #dcfce7;
  color: #166534;
}
.status-active {
  background: #dbeafe;
  color: #1d4ed8;
}
.status-withdrawn {
  background: #e5e7eb;
  color: #374151;
}
.status-for-hiring {
  background: #dcfce7;
  color: #166534;
}
.status-default {
  background: #e5e7eb;
  color: #111827;
}

.requirements-list {
  margin: 0;
  padding: 0;
  list-style: none;
}

.requirement-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.5rem 0;
  font-size: 0.9375rem;
  line-height: 1.5;
  color: #475569;
}

.requirement-item + .requirement-item {
  border-top: 1px solid #e2e8f0;
}

.requirement-check {
  flex-shrink: 0;
  width: 1.25rem;
  height: 1.25rem;
  margin-top: 0.125rem;
  background: #e0e7ff;
  color: #4f46e5;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 0.625rem;
  font-weight: 700;
}

.requirement-check::before {
  content: "✓";
}

.requirement-text {
  flex: 1;
}

.modal-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.btn {
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  transition:
    background 0.15s,
    box-shadow 0.15s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-withdraw {
  background: #dc2626;
  color: #fff;
}

.btn-withdraw:hover:not(:disabled) {
  background: #b91c1c;
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.35);
}

.btn-close {
  background: #f1f5f9;
  color: #475569;
}

.btn-close:hover {
  background: #e2e8f0;
}

.btn-icon {
  margin-right: 0.5rem;
  font-size: 0.875rem;
}
</style>
