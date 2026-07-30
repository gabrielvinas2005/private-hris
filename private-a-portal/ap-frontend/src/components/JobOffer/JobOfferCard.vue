<template>
  <div class="offer-card">
    <!-- Status Badge - Top Right -->
    <div class="status-badge-wrapper">
      <span :class="['status-badge', statusClass === 'status-hiring' ? 'bg-blue-600' : '', statusClass]">
        {{ statusText }}
      </span>
    </div>

    <!-- Header Section -->
    <div class="offer-header">
      <div class="icon-wrapper bg-blue-600">
        <i class="fas fa-briefcase"></i>
      </div>
      <div class="offer-title-section">
        <h3 class="offer-title">{{ offer.position }}</h3>
        <div class="offer-meta">
          <span class="meta-item">
            <i class="fas fa-building"></i>
            <span>{{
              offer.department ||
              "Planning, Information and Communication Technology Division"
            }}</span>
          </span>
          <span class="meta-item">
            <i class="fas fa-map-marker-alt"></i>
            <span>{{ offer.location || "12321" }}</span>
          </span>
        </div>
      </div>
    </div>

    <!-- Details Grid -->
    <div class="details-grid">
      <div class="detail-item">
        <span class="detail-label">JOB CODE</span>
        <span class="detail-value">{{
          offer.jobCode || offer.code || "JOB-001"
        }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">SALARY</span>
        <span class="detail-value">
          <span v-if="offer.salary">
            ₱{{ formatSalary(offer.salary) }}
          </span>
          <span v-else>N/A</span>
        </span>
      </div>
      <div class="detail-item">
        <span class="detail-label">START DATE</span>
        <span class="detail-value">{{ formatDate(offer.startDate) }}</span>
      </div>
      <div class="detail-item">
        <span class="detail-label">OFFER DATE</span>
        <span class="detail-value">{{
          formatDate(offer.appliedDate)
        }}</span>
      </div>
    </div>

    <!-- Description Box -->
    <div class="offer-description">
      <p class="description-text">
        {{
          offer.description ||
          "Position: Administrative Assistant II. See details for requirements."
        }}
      </p>
    </div>

    <!-- Requirements -->
    <div
      v-if="offer.requirements && offer.requirements.length > 0"
      class="requirements-section"
    >
      <p class="requirements-title">
        <i class="fas fa-check-circle"></i>
        Requirements:
      </p>
      <ul class="requirements-list">
        <li v-for="(req, index) in offer.requirements" :key="index">
          {{ req }}
        </li>
      </ul>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
      <button @click="$emit('view-details', offer)" class="btn-details bg-blue-600 text-white">
        <i class="fas fa-info-circle"></i>
        <span>View Details</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useJobOffer } from "@/composables/useJobOffer";

const props = defineProps({
  offer: {
    type: Object,
    required: true,
  },
});

defineEmits(["view-details"]);

const { getStatusClass, getStatusText, formatDate, formatSalary } =
  useJobOffer();

const statusClass = computed(() => getStatusClass(props.offer));
const statusText = computed(() => getStatusText(props.offer));
</script>

<style scoped>
/* Offer Card */
.offer-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  padding: 0;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  border: 1px solid #e5e7eb;
}

.offer-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

/* Status Badge */
.status-badge-wrapper {
  position: absolute;
  top: 1.5rem;
  right: 1.5rem;
  z-index: 10;
}

.status-badge {
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  text-transform: capitalize;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.status-hiring {
  color: white;
}

.status-accepted {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.status-declined {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.status-pending {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.status-default {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
  color: white;
}

/* Header */
.offer-header {
  display: flex;
  align-items: flex-start;
  gap: 1.25rem;
  padding: 2rem 2rem 1.5rem 2rem;
  background: linear-gradient(to bottom, #f9fafb 0%, white 100%);
  border-bottom: 1px solid #e5e7eb;
}

.icon-wrapper {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.icon-wrapper i {
  font-size: 1.75rem;
  color: white;
}

.offer-title-section {
  flex: 1;
  padding-right: 8rem;
}

.offer-title {
  font-size: 1.625rem;
  font-weight: 700;
  color: #111827;
  margin: 0 0 0.875rem 0;
  line-height: 1.3;
}

.offer-meta {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  font-size: 0.9375rem;
  color: #4b5563;
}

.meta-item i {
  color: #9ca3af;
  font-size: 0.9375rem;
  width: 16px;
  text-align: center;
}

/* Details Grid */
.details-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0;
  border-bottom: 1px solid #e5e7eb;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 1.5rem 2rem;
  border-right: 1px solid #e5e7eb;
}

.detail-item:last-child {
  border-right: none;
}

.detail-label {
  font-size: 0.6875rem;
  color: #9ca3af;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-value {
  font-size: 1rem;
  color: #111827;
  font-weight: 600;
}

/* Description */
.offer-description {
  padding: 1.5rem 2rem;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.description-text {
  margin: 0;
  font-size: 0.9375rem;
  color: #4b5563;
  line-height: 1.6;
}

/* Requirements */
.requirements-section {
  padding: 1.5rem 2rem;
  background: white;
  border-bottom: 1px solid #e5e7eb;
}

.requirements-title {
  font-size: 0.9375rem;
  font-weight: 700;
  color: #111827;
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.requirements-title i {
  color: #10b981;
  font-size: 1rem;
}

.requirements-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.requirements-list li {
  font-size: 0.9375rem;
  color: #4b5563;
  padding-left: 1.75rem;
  position: relative;
  margin-bottom: 0.625rem;
  line-height: 1.6;
}

.requirements-list li:last-child {
  margin-bottom: 0;
}

.requirements-list li::before {
  content: "✓";
  position: absolute;
  left: 0.375rem;
  color: #10b981;
  font-weight: bold;
  font-size: 1.125rem;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 0.875rem;
  padding: 2rem;
  background: white;
}

.action-buttons button {
  padding: 0.875rem 1.75rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.9375rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.action-buttons button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-buttons button i {
  font-size: 1.125rem;
}

.btn-details {
  border: none;
}

.btn-details:hover {
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
  transform: translateY(-1px);
}

.btn-details:active {
  transform: translateY(0);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .details-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .detail-item:nth-child(2n) {
    border-right: none;
  }

  .detail-item:nth-child(1),
  .detail-item:nth-child(2) {
    border-bottom: 1px solid #e5e7eb;
  }
}

@media (max-width: 768px) {
  .offer-card {
    border-radius: 12px;
  }

  .offer-header {
    flex-direction: column;
    padding: 1.5rem;
  }

  .icon-wrapper {
    width: 50px;
    height: 50px;
  }

  .icon-wrapper i {
    font-size: 1.5rem;
  }

  .offer-title-section {
    padding-right: 0;
  }

  .offer-title {
    font-size: 1.375rem;
  }

  .status-badge-wrapper {
    position: static;
    margin-bottom: 1rem;
  }

  .status-badge {
    display: inline-block;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .detail-item {
    border-right: none;
    border-bottom: 1px solid #e5e7eb;
    padding: 1.25rem 1.5rem;
  }

  .detail-item:last-child {
    border-bottom: none;
  }

  .offer-description {
    padding: 1.25rem 1.5rem;
  }

  .requirements-section {
    padding: 1.25rem 1.5rem;
  }

  .action-buttons {
    grid-template-columns: 1fr;
    padding: 1.5rem;
    gap: 0.75rem;
  }

  .btn-details {
    justify-self: stretch;
  }
}

@media (max-width: 480px) {
  .offer-title {
    font-size: 1.25rem;
  }

  .action-buttons button {
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem;
  }
}
</style>
