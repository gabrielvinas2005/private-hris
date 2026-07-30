<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="visible" class="modal-backdrop" @click.self="close">
        <div class="modal-card">
          <div class="modal-header">
            <h3 class="modal-title">{{ dialogTitle }}</h3>
            <button class="close-btn" @click="close">
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
              </svg>
            </button>
          </div>

          <div class="modal-body">
            <div v-if="employee" class="task-section">
              <div class="section-title">Division head approved tasks</div>
              <template v-if="employee.isTaskApproved">
                <div v-if="taskPeriodLabel" class="task-meta muted">
                  Task period: {{ taskPeriodLabel }}
                </div>
                <ul v-if="employee.approvedTasks?.length" class="task-list">
                  <li v-for="(task, index) in employee.approvedTasks" :key="index">
                    {{ task }}
                  </li>
                </ul>
                <p v-else class="empty-note">No task descriptions recorded.</p>
                <div class="approval-meta">
                  <div v-if="employee.approvedByName">
                    <span class="meta-label">Approved by</span>
                    <strong>{{ employee.approvedByName }}</strong>
                  </div>
                  <div v-if="employee.approvedAt">
                    <span class="meta-label">Approved on</span>
                    <strong>{{ formatDateTime(employee.approvedAt) }}</strong>
                  </div>
                  <div v-if="employee.taskRemarks" class="full-width">
                    <span class="meta-label">Remarks</span>
                    <strong>{{ employee.taskRemarks }}</strong>
                  </div>
                </div>
                <div v-if="employee.taskAttachments?.length" class="attachments-block">
                  <div class="attachments-title">Attached documents</div>
                  <ul class="attachment-list">
                    <li
                      v-for="file in employee.taskAttachments"
                      :key="file.id"
                      class="attachment-item"
                    >
                      <div class="attachment-info">
                        <span class="attachment-name">{{ file.fileName }}</span>
                        <span v-if="file.fileSize" class="attachment-size muted">
                          {{ formatFileSize(file.fileSize) }}
                        </span>
                        <span v-if="file.description" class="attachment-desc muted">
                          {{ file.description }}
                        </span>
                      </div>
                      <button
                        class="btn-attachment"
                        :disabled="openingAttachmentId === file.id"
                        @click="openAttachment(file)"
                      >
                        {{ openingAttachmentId === file.id ? "Loading…" : "Preview" }}
                      </button>
                    </li>
                  </ul>
                </div>
              </template>
              <template v-else-if="employee.taskAttachments?.length">
                <div class="attachments-block">
                  <div class="attachments-title">Attached documents</div>
                  <ul class="attachment-list">
                    <li
                      v-for="file in employee.taskAttachments"
                      :key="file.id"
                      class="attachment-item"
                    >
                      <div class="attachment-info">
                        <span class="attachment-name">{{ file.fileName }}</span>
                        <span v-if="file.fileSize" class="attachment-size muted">
                          {{ formatFileSize(file.fileSize) }}
                        </span>
                      </div>
                      <button
                        class="btn-attachment"
                        :disabled="openingAttachmentId === file.id"
                        @click="openAttachment(file)"
                      >
                        {{ openingAttachmentId === file.id ? "Loading…" : "Preview" }}
                      </button>
                    </li>
                  </ul>
                </div>
                <p class="empty-note pending-note">
                  Tasks are submitted but not yet approved by the division head.
                </p>
              </template>
              <div v-else class="empty-note">
                {{
                  employee.taskApprovalStatus === "pending"
                    ? "Tasks are submitted but not yet approved by the division head."
                    : "No approved tasks found for this payroll period."
                }}
              </div>
            </div>

            <div v-if="employee" class="payroll-breakdown">
              <div class="breakdown-title">Payroll breakdown</div>
              <div class="breakdown-rows">
                <div class="breakdown-row">
                  <div class="breakdown-item full">
                    <span>Gross</span>
                    <strong>₱{{ fc(employee.gross) }}</strong>
                  </div>
                </div>
                <div class="breakdown-row pair">
                  <div class="breakdown-item">
                    <span>Late</span>
                    <strong>₱{{ fc(employee.late) }}</strong>
                  </div>
                  <span class="breakdown-sep" aria-hidden="true">|</span>
                  <div class="breakdown-item">
                    <span>Undertime</span>
                    <strong>₱{{ fc(employee.undertime) }}</strong>
                  </div>
                </div>
                <div class="breakdown-row">
                  <div class="breakdown-item full">
                    <span>Absent / LWOP</span>
                    <strong>₱{{ fc(absentLwopTotal) }}</strong>
                  </div>
                </div>
                <div class="breakdown-row">
                  <div class="breakdown-item full subtotal">
                    <span>Attendance total</span>
                    <strong>₱{{ fc(employee.attendanceTotal) }}</strong>
                  </div>
                </div>
                <div class="breakdown-row">
                  <div class="breakdown-item full highlight">
                    <span>Balance after attendance</span>
                    <strong>₱{{ fc(employee.balanceAfterAttendance) }}</strong>
                  </div>
                </div>
                <div class="breakdown-row">
                  <div class="breakdown-item full add">
                    <span>Premium added (20%)</span>
                    <strong>+₱{{ fc(employee.premium) }}</strong>
                  </div>
                </div>
                <div class="breakdown-row">
                  <div class="breakdown-item full highlight">
                    <span>Balance + premium (NVAT/EWT base)</span>
                    <strong>₱{{ fc(employee.balanceAfterPremium) }}</strong>
                  </div>
                </div>
                <div class="breakdown-row pair">
                  <div class="breakdown-item">
                    <span>NVAT (3%)</span>
                    <strong>₱{{ fc(employee.nvat) }}</strong>
                  </div>
                  <span class="breakdown-sep" aria-hidden="true">|</span>
                  <div class="breakdown-item">
                    <span>EWT (2%)</span>
                    <strong>₱{{ fc(employee.ewt) }}</strong>
                  </div>
                </div>
                <div class="breakdown-row">
                  <div class="breakdown-item full net">
                    <span>Net pay</span>
                    <strong>₱{{ fc(employee.netPay) }}</strong>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="fade">
      <div
        v-if="previewAttachment"
        class="modal-backdrop preview-backdrop"
        @click.self="clearPreview"
      >
        <div class="modal-card preview-modal">
          <div class="modal-header">
            <h3 class="modal-title">{{ previewAttachment.fileName }}</h3>
            <button class="close-btn" @click="clearPreview">
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <path d="M2 2l10 10M12 2L2 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
              </svg>
            </button>
          </div>
          <div class="preview-body">
            <iframe
              v-if="previewAttachment.isPdf"
              :src="previewAttachment.url"
              class="preview-frame"
              title="Attachment preview"
            />
            <img
              v-else-if="previewAttachment.isImage"
              :src="previewAttachment.url"
              :alt="previewAttachment.fileName"
              class="preview-image"
            />
            <p v-else class="empty-note">
              Preview is not available for this file type.
            </p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch, onUnmounted } from "vue";
import { ElMessage } from "element-plus";
import { cosPayrollApi } from "../../services/api.js";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  employee: { type: Object, default: null },
});

const emit = defineEmits(["update:modelValue"]);

const openingAttachmentId = ref(null);
const previewAttachment = ref(null);

const clearPreview = () => {
  if (previewAttachment.value?.url) {
    window.URL.revokeObjectURL(previewAttachment.value.url);
  }
  previewAttachment.value = null;
};

const visible = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});

watch(visible, (isOpen) => {
  if (!isOpen) clearPreview();
});

onUnmounted(() => {
  clearPreview();
});

const dialogTitle = computed(() =>
  props.employee
    ? `Work details — ${props.employee.employeeName}`
    : "COS work details",
);

const absentLwopTotal = computed(() => {
  const absent = Number(props.employee?.absent || 0);
  const lwop = Number(props.employee?.lwop || 0);
  return absent + lwop;
});

const taskPeriodLabel = computed(() => {
  const from = formatDate(props.employee?.taskPeriodFrom);
  const to = formatDate(props.employee?.taskPeriodTo);
  if (from === "—" && to === "—") return "";
  if (from !== "—" && to !== "—") return `${from} to ${to}`;
  return from !== "—" ? from : to;
});

const formatDate = (dateString) => {
  if (!dateString) return "—";
  const [year, month, day] = String(dateString).split("T")[0].split("-");
  return `${month}-${day}-${year}`;
};

const formatDateTime = (dateString) => {
  if (!dateString) return "—";
  const normalized = String(dateString).replace(" ", "T");
  const date = new Date(normalized);
  if (Number.isNaN(date.getTime())) return formatDate(dateString);
  return date.toLocaleString("en-PH", {
    year: "numeric",
    month: "short",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const fc = (v) =>
  Number(v || 0).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

const formatFileSize = (bytes) => {
  const size = Number(bytes) || 0;
  if (size < 1024) return `${size} B`;
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
  return `${(size / (1024 * 1024)).toFixed(1)} MB`;
};

const isPdfType = (contentType, fileName) => {
  const type = String(contentType || "").toLowerCase();
  const name = String(fileName || "").toLowerCase();
  return type.includes("pdf") || name.endsWith(".pdf");
};

const isImageType = (contentType, fileName) => {
  const type = String(contentType || "").toLowerCase();
  const name = String(fileName || "").toLowerCase();
  return (
    type.startsWith("image/") ||
    [".png", ".jpg", ".jpeg", ".gif", ".webp", ".bmp"].some((ext) =>
      name.endsWith(ext),
    )
  );
};

const openAttachment = async (attachment) => {
  const attachmentId = attachment?.id;
  if (!attachmentId) return;

  openingAttachmentId.value = attachmentId;
  try {
    const response = await cosPayrollApi.downloadAttachment(attachmentId);
    const contentType = String(
      response.headers?.["content-type"] || attachment.fileType || "",
    ).toLowerCase();

    if (contentType.includes("application/json")) {
      const text = await response.data.text();
      let message = "Failed to load attachment preview.";
      try {
        const json = JSON.parse(text);
        message = json.message || json.error || message;
      } catch (parseError) {
        message = text || message;
      }
      ElMessage.error(message);
      return;
    }

    clearPreview();

    const resolvedType =
      attachment.fileType || response.headers?.["content-type"] || "application/octet-stream";
    const blob = new Blob([response.data], { type: resolvedType });
    const url = window.URL.createObjectURL(blob);

    previewAttachment.value = {
      id: attachmentId,
      fileName: attachment.fileName || "Attachment",
      url,
      contentType: resolvedType,
      isPdf: isPdfType(resolvedType, attachment.fileName),
      isImage: isImageType(resolvedType, attachment.fileName),
    };
  } catch (error) {
    console.error("Error opening COS attachment:", error);
    const message =
      error.response?.data?.message ||
      error.response?.data?.error ||
      "Failed to load attachment preview.";
    ElMessage.error(message);
  } finally {
    openingAttachmentId.value = null;
  }
};

const close = () => {
  clearPreview();
  visible.value = false;
};
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
}
.modal-card {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
  width: 100%;
  max-width: 560px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.preview-backdrop {
  z-index: 1100;
}
.preview-modal {
  max-width: 960px;
  width: min(960px, 96vw);
  height: 88vh;
  max-height: 88vh;
}
.preview-body {
  flex: 1;
  min-height: 0;
  overflow: hidden;
  background: #fff;
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid #e5e7eb;
}
.modal-title {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: #111827;
}
.close-btn {
  background: transparent;
  border: none;
  padding: 5px;
  border-radius: 6px;
  color: #9ca3af;
  cursor: pointer;
}
.modal-body {
  padding: 20px 24px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.task-section {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 14px 16px;
}
.section-title {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 10px;
}
.task-meta {
  font-size: 12px;
  margin-bottom: 8px;
}
.task-list {
  margin: 0 0 12px;
  padding-left: 18px;
  color: #111827;
  font-size: 13px;
}
.task-list li + li {
  margin-top: 6px;
}
.approval-meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px 16px;
  font-size: 13px;
}
.meta-label {
  display: block;
  font-size: 11px;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin-bottom: 2px;
}
.approval-meta .full-width {
  grid-column: 1 / -1;
}
.attachments-block {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid #f3f4f6;
}
.attachments-title {
  font-size: 11px;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin-bottom: 8px;
}
.attachment-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.attachment-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f9fafb;
}
.attachment-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
.attachment-name {
  font-size: 13px;
  color: #111827;
  word-break: break-word;
}
.attachment-size,
.attachment-desc {
  font-size: 11px;
}
.btn-attachment {
  flex-shrink: 0;
  background: #fff;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 5px 12px;
  font-size: 12px;
  color: #374151;
  cursor: pointer;
  white-space: nowrap;
}
.btn-attachment:hover:not(:disabled) {
  background: #f3f4f6;
}
.btn-attachment:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.preview-frame {
  width: 100%;
  height: 100%;
  border: none;
  display: block;
}
.preview-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  display: block;
}
.pending-note {
  margin-top: 12px;
}
.empty-note {
  margin: 0;
  font-size: 13px;
  color: #9ca3af;
}
.muted {
  color: #6b7280;
}
.payroll-breakdown {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 14px 16px;
}
.breakdown-title {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 10px;
}
.breakdown-rows {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.breakdown-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
}
.breakdown-row.pair {
  display: flex;
  align-items: center;
  gap: 12px;
}
.breakdown-row.pair .breakdown-item {
  flex: 1;
  min-width: 0;
}
.breakdown-sep {
  flex-shrink: 0;
  color: #d1d5db;
  font-size: 13px;
  line-height: 1;
  user-select: none;
}
.breakdown-item {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
  font-size: 13px;
  color: #6b7280;
}
.breakdown-item span {
  min-width: 0;
}
.breakdown-item.full {
  width: 100%;
}
.breakdown-item.subtotal {
  padding-top: 2px;
  border-top: 1px dashed #e5e7eb;
}
.breakdown-item.subtotal strong {
  color: #374151;
}
.breakdown-item strong {
  font-family: monospace;
  color: #111827;
}
.breakdown-item.highlight strong {
  color: #185fa5;
}
.breakdown-item.add strong {
  color: #059669;
}
.breakdown-item.net strong {
  color: #059669;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.18s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
