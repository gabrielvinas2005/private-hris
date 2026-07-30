<template>
  <div class="job-offers-container">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <p class="loading-text">Loading your job offers...</p>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!jobOffers || jobOffers.length === 0" class="empty-state">
      <div class="empty-state-content">
        <div class="empty-icon-wrapper">
          <i class="fas fa-briefcase empty-icon"></i>
        </div>
        <h3 class="empty-title">No Job Offers Yet</h3>
        <p class="empty-message">
          You don't have any job offers at the moment. Keep applying to
          positions and check back later!
        </p>
      </div>
    </div>

    <!-- Job Offers List -->
    <div v-else class="offers-grid">
      <JobOfferCard
        v-for="offer in jobOffers"
        :key="offer.id"
        :offer="offer"
        @view-details="handleViewDetails"
      />
    </div>

    <!-- Job Offer Details Modal -->
    <JobOfferDetailsModal
      v-model="detailsDialogVisible"
      :offer="selectedOffer"
      :processing-offer-id="processingOfferId"
      @accept="handleAcceptOffer"
      @reject="handleRejectOffer"
    />
  </div>
</template>

<script setup>
import { ref } from "vue";
import JobOfferCard from "./JobOfferCard.vue";
import JobOfferDetailsModal from "./JobOfferDetailsModal.vue";
import { useJobOffer } from "@/composables/useJobOffer";

// Props
const props = defineProps({
  jobOffers: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

// Emits
const emit = defineEmits(["accept-offer", "reject-offer", "refresh"]);

// Composable
const {
  processingOfferId,
  detailsDialogVisible,
  selectedOffer,
  viewOfferDetails,
} = useJobOffer();

// Methods
const handleViewDetails = (offer) => {
  viewOfferDetails(offer);
};

const handleAcceptOffer = (offerId) => {
  emit("accept-offer", offerId);
};

const handleRejectOffer = (offerId) => {
  emit("reject-offer", offerId);
};
</script>

<style scoped>
/* Container */
.job-offers-container {
  padding: 1.5rem 1.25rem;
  max-width: 1100px;
  margin: 0 auto;
  background: #f5f7fa;
}

/* Loading State */
.loading-spinner {
  text-align: center;
}

.spinner {
  width: 50px;
  height: 50px;
  margin: 0 auto 1rem;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.loading-text {
  color: #6b7280;
  font-size: 1rem;
  font-weight: 500;
}

/* Empty State */
.empty-state {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  padding: 5rem 2rem;
  text-align: center;
}

.empty-state-content {
  max-width: 450px;
  margin: 0 auto;
}

.empty-icon-wrapper {
  width: 120px;
  height: 120px;
  margin: 0 auto 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-icon {
  font-size: 3.5rem;
  color: white;
}

.empty-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 1rem;
}

.empty-message {
  color: #6b7280;
  font-size: 1.0625rem;
  line-height: 1.6;
}

/* Offers Grid */
.offers-grid {
  display: grid;
  gap: 2rem;
}

/* Animations */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .job-offers-container {
    padding: 1rem;
  }
}
</style>
