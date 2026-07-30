import { ref } from "vue";
import { ElMessageBox } from "element-plus";

export function useJobOffer() {
  const processingOfferId = ref(null);
  const detailsDialogVisible = ref(false);
  const selectedOffer = ref(null);

  // Status utilities
  const getStatusClass = (offer) => {
    const status = offer.status?.toLowerCase() || "";

    if (status.includes("accepted")) {
      return "status-accepted";
    } else if (status.includes("rejected") || status.includes("declined")) {
      return "status-declined";
    } else if (status.includes("for hiring") || status.includes("hiring")) {
      return "status-hiring";
    } else if (status.includes("pending")) {
      return "status-pending";
    }

    return "status-hiring";
  };

  const getStatusText = (offer) => {
    const status = offer.status || "For hiring";
    return status.charAt(0).toUpperCase() + status.slice(1);
  };

  // Formatting utilities
  const formatDate = (date) => {
    if (!date) return "TBD";

    const dateObj = date instanceof Date ? date : new Date(date);

    if (isNaN(dateObj.getTime())) return "TBD";

    return dateObj.toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  };

  const formatSalary = (amount) => {
    if (!amount) return "0.00";
    return new Intl.NumberFormat("en-PH", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount);
  };

  // Offer actions
  const viewOfferDetails = (offer) => {
    selectedOffer.value = offer;
    detailsDialogVisible.value = true;
  };

  const handleAcceptOffer = async (offer, onAccept) => {
    try {
      const result = await ElMessageBox.confirm(
        `Are you sure you want to accept the job offer for ${offer.position}? This action cannot be undone.`,
        "Accept Job Offer",
        {
          confirmButtonText: "Yes, Accept",
          cancelButtonText: "Cancel",
          type: "success",
          confirmButtonClass: "el-button--success",
        },
      );

      if (result === "confirm") {
        processingOfferId.value = offer.id;
        if (onAccept) {
          await onAccept(offer.id);
        }
        detailsDialogVisible.value = false;
      }
    } catch (error) {
      // User cancelled
    } finally {
      processingOfferId.value = null;
    }
  };

  const handleRejectOffer = async (offer, onReject) => {
    try {
      const result = await ElMessageBox.confirm(
        `Are you sure you want to decline the job offer for ${offer.position}? This action cannot be undone.`,
        "Decline Job Offer",
        {
          confirmButtonText: "Yes, Decline",
          cancelButtonText: "Cancel",
          type: "warning",
          confirmButtonClass: "el-button--danger",
        },
      );

      if (result === "confirm") {
        processingOfferId.value = offer.id;
        if (onReject) {
          await onReject(offer.id);
        }
        detailsDialogVisible.value = false;
      }
    } catch (error) {
      // User cancelled
    } finally {
      processingOfferId.value = null;
    }
  };

  const closeDetailsDialog = () => {
    detailsDialogVisible.value = false;
    selectedOffer.value = null;
  };

  return {
    // State
    processingOfferId,
    detailsDialogVisible,
    selectedOffer,
    // Utilities
    getStatusClass,
    getStatusText,
    formatDate,
    formatSalary,
    // Actions
    viewOfferDetails,
    handleAcceptOffer,
    handleRejectOffer,
    closeDetailsDialog,
  };
}
