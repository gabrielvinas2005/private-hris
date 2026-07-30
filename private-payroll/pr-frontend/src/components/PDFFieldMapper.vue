<template>
  <el-dialog
    v-model="visible"
    title="PDF Field Mapper"
    width="95%"
    :close-on-click-modal="false"
    destroy-on-close
  >
    <div class="pdf-mapper-container">
      <div class="fields-panel">
        <h3>Available Fields</h3>
        <p class="help-text">Drag fields onto the PDF to map their positions</p>

        <div class="fields-list">
          <div
            v-for="field in filteredAvailableFields"
            :key="field.key"
            class="field-item"
            :class="{
              mapped: isFieldMapped(field.key),
              selected: selectedFieldKey === field.key,
            }"
            draggable="true"
            @dragstart="onDragStart($event, field)"
            @click="selectField(field.key)"
          >
            <div class="field-icon"></div>
            <div class="field-info">
              <div class="field-label">{{ field.label }}</div>
              <div class="field-key">{{ field.key }}</div>
              <div v-if="isFieldMapped(field.key)" class="mapped-indicator">
                ✓ Mapped at ({{ getMappedCoords(field.key) }})
              </div>
            </div>
          </div>
        </div>

        <el-divider />
        
        <div class="coord-input-panel">
          <h4>Manual Coordinate Input</h4>
          <div v-if="selectedFieldKey">
            <p class="help-text">
              Editing: <strong>{{ getFieldLabel(selectedFieldKey) }}</strong>
            </p>
            <div class="coord-input-row">
              <span class="coord-label">X:</span>
              <el-input-number
                v-model="manualX"
                :min="0"
                :max="612"
                :step="1"
                size="small"
                style="width: 100px"
                @change="applyManualCoordinates"
              />
            </div>
            <div class="coord-input-row">
              <span class="coord-label">Y:</span>
              <el-input-number
                v-model="manualY"
                :min="0"
                :max="pageHeight"
                :step="1"
                size="small"
                style="width: 100px"
                @change="applyManualCoordinates"
              />
            </div>
            <div v-if="isBoxedField(selectedFieldKey)" class="coord-input-row">
              <span class="coord-label">Char Spacing:</span>
              <el-input-number
                v-model="manualCharSpacing"
                :min="8"
                :max="50"
                :step="1"
                size="small"
                style="width: 100px"
                @change="applyManualCoordinates"
              />
              <small style="margin-left: 8px; color: #909399"
                >Gap between each character</small
              >
            </div>
            <small class="coord-hint">
              Top-left is (0, 0). Y increases going down.
              <span
                v-if="isBoxedField(selectedFieldKey)"
                style="display: block; margin-top: 4px; color: #409eff"
              >
                Adjust "Char Spacing" to match the width of each box on the
                form.
              </span>
            </small>
          </div>
          <div v-else class="coord-placeholder">
            <p class="help-text">
              Click on a field above to edit its coordinates
            </p>
          </div>
        </div>

        <el-divider />

        <div class="actions-panel">
          <el-button @click="clearAllMappings" type="danger" plain>
            Clear All
          </el-button>
          <el-button @click="saveMappings" type="primary">
            Save Mappings
          </el-button>
        </div>

        <div v-if="showGrid" class="grid-info">
          <el-checkbox v-model="showGrid">Show Grid</el-checkbox>
          <el-input-number
            v-model="gridSize"
            :min="10"
            :max="100"
            :step="10"
            size="small"
            style="width: 100px; margin-left: 10px"
          />
        </div>
      </div>

      <div class="coordinate-grid-panel">
        <h3>Coordinate Reference Grid</h3>
        <p class="help-text">
          Place fields here using coordinates from PDF hover
        </p>

        <div
          class="page-navigation"
          style="
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 10px;
            background: #f5f7fa;
            border-radius: 4px;
          "
        >
          <el-button
            @click="goToPreviousPage"
            :disabled="currentPage <= 1"
            size="small"
            type="primary"
            plain
          >
            ← Previous Page
          </el-button>
          <span
            style="
              font-weight: 600;
              font-size: 14px;
              min-width: 100px;
              text-align: center;
            "
          >
            Page {{ currentPage }} of {{ totalPages }}
          </span>
          <el-button
            @click="goToNextPage"
            :disabled="currentPage >= totalPages"
            size="small"
            type="primary"
            plain
          >
            Next Page →
          </el-button>
        </div>

        <div
          class="coordinate-grid-container"
          @drop="onCoordinateGridDrop"
          @dragover.prevent
          @dragenter.prevent
          @dragleave.prevent
          @mousemove="updateCoordinateGridHover"
          ref="coordinateGridContainer"
        >
          <canvas
            ref="coordinateGridCanvas"
            class="coordinate-grid-canvas"
          ></canvas>

          <div class="coordinate-markers">
            <div
              v-for="(mapping, fieldKey) in currentPageMappings"
              :key="fieldKey"
              class="coordinate-marker"
              :style="getCoordinateMarkerStyle(mapping)"
              :draggable="true"
              @dragstart="onCoordinateMarkerDragStart($event, fieldKey)"
              @dragend="onCoordinateMarkerDragEnd($event, fieldKey)"
              :title="`${getFieldLabel(fieldKey)} - (${Math.round(mapping.x)}, ${Math.round(mapping.y)})`"
            >
              <span class="coordinate-marker-label">{{
                getFieldLabel(fieldKey)
              }}</span>
              <span
                class="coordinate-marker-delete"
                @click.stop="removeMapping(fieldKey)"
                >✕</span
              >
            </div>
          </div>

          <div
            v-if="coordinateGridHover"
            class="coordinate-grid-coords"
            :style="{
              left: coordinateGridHover.x + 'px',
              top: coordinateGridHover.y + 'px',
            }"
          >
            X: {{ coordinateGridHover.pdfX }}, Y: {{ coordinateGridHover.pdfY }}
          </div>
        </div>

        <div class="coordinate-grid-info">
          <small>Grid Size: 612 × 792 points (matches PDF)</small>
        </div>
      </div>

      <div class="pdf-panel">
        <div class="pdf-controls">
          <h3>Live Preview</h3>
          <el-button-group>
            <el-button @click="zoomOut" :disabled="scale <= 0.5" size="small">
              ➖
            </el-button>
            <el-button size="small">{{ Math.round(scale * 100) }}%</el-button>
            <el-button @click="zoomIn" :disabled="scale >= 2" size="small">
              ➕
            </el-button>
          </el-button-group>
          <el-button
            @click="regeneratePreview"
            type="text"
            size="small"
            :loading="generatingPreview"
          >
            Refresh Preview
          </el-button>
        </div>

        <div class="pdf-preview-container" ref="pdfPreviewContainer">
          <div v-if="generatingPreview" class="preview-loading">
            <el-icon class="is-loading" style="font-size: 24px"
              ><Loading
            /></el-icon>
            <p>Generating preview...</p>
          </div>
          <div v-else-if="previewError" class="preview-error">
            <p>{{ previewError }}</p>
            <el-button @click="regeneratePreview" size="small">Retry</el-button>
          </div>
          <iframe
            v-else-if="previewPdfUrl"
            :src="previewPdfUrl"
            class="preview-iframe"
            style="width: 100%; height: 100%; border: none; background: white"
          ></iframe>
          <div v-else class="preview-empty">
            <p>Map fields on the left to see preview here</p>
          </div>
        </div>

        <div class="pdf-info">
          <span>Preview updates automatically as you map fields</span>
          <span style="margin-left: 20px; color: #409eff">
            Use sample data to visualize field placement
          </span>
        </div>
      </div>
    </div>

    <template #footer>
      <el-button @click="close">Cancel</el-button>
      <el-button type="primary" @click="saveMappings">Save & Close</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from "vue";
import { ElMessage } from "element-plus";
import { Loading } from "@element-plus/icons-vue";
import { PDFDocument, StandardFonts, rgb } from "pdf-lib";
import { pdfFieldMappingApi } from "../services/api.js";

const props = defineProps({
  modelValue: Boolean,
  templatePath: {
    type: String,
    default: "/templates/philhealth_PMRF.pdf",
  },
  storageKey: {
    type: String,
    default: "philhealth_pmrf_mappings",
  },
  formType: {
    type: String,
    default: null,
  },
  availableFields: {
    type: Array,
    required: true,
  },
  coordinatesConfig: {
    type: Object,
    required: true,
  },
  getCoordinatesFunction: {
    type: Function,
    required: true,
  },
  sampleDataFunction: {
    type: Function,
    required: true,
  },
  fieldToDataKey: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["update:modelValue", "saved"]);

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
});

const pdfContainer = ref(null);
const pdfCanvas = ref(null);
const gridCanvas = ref(null);
const pdfDoc = ref(null);
const scale = ref(1);
const showGrid = ref(true);
const gridSize = ref(20);
const totalPages = ref(1);
const currentPage = ref(1);
const pageHeight = ref(792); // Default to 792, will be updated when PDF loads
const draggedField = ref(null);
const currentMappings = ref({});
const hoverCoords = ref(null);
const draggingMarker = ref(null);
const coordinateGridContainer = ref(null);
const coordinateGridCanvas = ref(null);
const coordinateGridHover = ref(null);
const draggingCoordinateMarker = ref(null);
const selectedFieldKey = ref(null);
const manualX = ref(0);
const manualY = ref(0);
const manualCharSpacing = ref(16);
const previewPdfUrl = ref(null);
const generatingPreview = ref(false);
const previewError = ref(null);
const pdfPreviewContainer = ref(null);

const filteredAvailableFields = computed(() =>
  props.availableFields.filter((field) => field.page === currentPage.value)
);

const currentPageMappings = computed(() => {
  const pageFieldKeys = props.availableFields
    .filter((f) => f.page === currentPage.value)
    .map((f) => f.key);

  const filtered = {};
  for (const [key, value] of Object.entries(currentMappings.value)) {
    if (pageFieldKeys.includes(key)) {
      filtered[key] = value;
    }
  }
  return filtered;
});

onMounted(async () => {
  if (visible.value) {
    await loadPDF();
    loadExistingMappings();
    setTimeout(() => {
      drawCoordinateGrid();
      generatePreview();
    }, 200);
  }

  window.addEventListener("resize", () => {
    if (visible.value) {
      setTimeout(() => {
        drawCoordinateGrid();
      }, 100);
    }
  });
});

onUnmounted(() => {
  if (previewPdfUrl.value) {
    URL.revokeObjectURL(previewPdfUrl.value);
  }
});

watch(visible, async (newVal) => {
  if (newVal) {
    await nextTick();
    await loadPDF();
    loadExistingMappings();
    setTimeout(() => {
      drawCoordinateGrid();
      generatePreview();
    }, 200);
  } else {
    if (previewPdfUrl.value) {
      URL.revokeObjectURL(previewPdfUrl.value);
      previewPdfUrl.value = null;
    }
  }
});

watch(
  currentMappings,
  () => {
    if (visible.value) {
      setTimeout(() => {
        generatePreview();
      }, 300);
    }
  },
  { deep: true }
);

watch(showGrid, () => {
  drawCoordinateGrid();
});

watch(gridSize, () => {
  if (showGrid.value) {
    drawGrid();
  }
  drawCoordinateGrid();
});

watch(currentPage, () => {
  drawCoordinateGrid();
  generatePreview();
});

async function loadPDF() {
  try {
    const templateUrl = props.templatePath;

    const response = await fetch(templateUrl);
    if (!response.ok) {
      throw new Error(
        `PDF not found: ${response.status} ${response.statusText}`
      );
    }

    const templateBytes = await response.arrayBuffer();

    pdfDoc.value = await PDFDocument.load(templateBytes);

    const pages = pdfDoc.value.getPages();
    totalPages.value = pages.length;

    if (pages.length > 0) {
      const { height } = pages[0].getSize();
      pageHeight.value = height;
    }

    if (totalPages.value === 1) {
      const page2Fields = props.availableFields.filter((f) => f.page === 2);
      if (page2Fields.length > 0) {
      }
    }
  } catch (error) {
    ElMessage.error("Failed to load PDF: " + error.message);
  }
}

async function renderPDF() {
  const templateUrl = props.templatePath;

  let canvasWidth = 612;
  let canvasHeight = 792;

  if (pdfDoc.value) {
    const pages = pdfDoc.value.getPages();
    if (pages.length > 0) {
      const firstPage = pages[0];
      const { width, height } = firstPage.getSize();
      canvasWidth = width;
      canvasHeight = height;
    }
  }

  const scaledWidth = canvasWidth * scale.value;
  const scaledHeight = canvasHeight * scale.value;

  const container = pdfContainer.value;
  if (!container) {
    console.error("PDF container not found");
    return;
  }

  const existingPDF = container.querySelector("iframe, embed, object, img");
  if (existingPDF) {
    existingPDF.remove();
  }

  const iframe = document.createElement("iframe");
  iframe.src = templateUrl;
  iframe.style.width = "100%";
  iframe.style.height = scaledHeight + "px";
  iframe.style.border = "none";
  iframe.style.background = "white";
  iframe.style.position = "absolute";
  iframe.style.top = "0";
  iframe.style.left = "0";
  iframe.style.zIndex = "1";
  iframe.style.pointerEvents = "none";
  iframe.style.minWidth = scaledWidth + "px";

  const loadingDiv = document.createElement("div");
  loadingDiv.id = "pdf-loading";
  loadingDiv.style.position = "absolute";
  loadingDiv.style.top = "50%";
  loadingDiv.style.left = "50%";
  loadingDiv.style.transform = "translate(-50%, -50%)";
  loadingDiv.style.zIndex = "10";
  loadingDiv.style.background = "rgba(255, 255, 255, 0.9)";
  loadingDiv.style.padding = "20px";
  loadingDiv.style.borderRadius = "4px";
  loadingDiv.textContent = "Loading PDF...";
  container.appendChild(loadingDiv);

  container.appendChild(iframe);

  iframe.onload = () => {
    const loading = container.querySelector("#pdf-loading");
    if (loading) {
      loading.remove();
    }
  };

  iframe.onerror = () => {
    const loading = container.querySelector("#pdf-loading");
    if (loading) {
      loading.textContent = "Failed to load PDF. Please check the file path.";
      loading.style.color = "red";
    }
  };

  container.style.minWidth = scaledWidth + "px";
  container.style.minHeight = scaledHeight + "px";
  container.style.position = "relative";

  if (pdfCanvas.value) {
    pdfCanvas.value.width = scaledWidth;
    pdfCanvas.value.height = scaledHeight;
    pdfCanvas.value.style.position = "absolute";
    pdfCanvas.value.style.top = "0";
    pdfCanvas.value.style.left = "0";
    pdfCanvas.value.style.pointerEvents = "none";
    pdfCanvas.value.style.zIndex = "2";
  }

}

function renderPDFWithIframe(url, width, height) {
  const container = pdfContainer.value;
  if (!container) return;

  const existingObject = container.querySelector("object");
  if (existingObject) {
    existingObject.remove();
  }

  const iframe = document.createElement("iframe");
  iframe.src = url;
  iframe.style.width = width + "px";
  iframe.style.height = height + "px";
  iframe.style.border = "none";
  iframe.style.background = "white";
  iframe.style.position = "absolute";
  iframe.style.top = "0";
  iframe.style.left = "0";
  iframe.style.zIndex = "1";
  iframe.style.pointerEvents = "auto";
  container.appendChild(iframe);
}

async function renderPDFWithDirectURL() {
  const templateUrl = props.templatePath;
  const canvasWidth = 612 * scale.value;
  const canvasHeight = 792 * scale.value;

  const container = pdfContainer.value;
  if (!container) return;

  const existing = container.querySelector("iframe, embed, object");
  if (existing) {
    existing.remove();
  }

  renderPDFWithIframe(templateUrl, canvasWidth, canvasHeight);

  if (pdfCanvas.value) {
    pdfCanvas.value.width = canvasWidth;
    pdfCanvas.value.height = canvasHeight;
    pdfCanvas.value.style.position = "absolute";
    pdfCanvas.value.style.top = "0";
    pdfCanvas.value.style.left = "0";
    pdfCanvas.value.style.pointerEvents = "none";
    pdfCanvas.value.style.zIndex = "2";
  }

  if (gridCanvas.value) {
    gridCanvas.value.width = canvasWidth;
    gridCanvas.value.height = canvasHeight;
    gridCanvas.value.style.position = "absolute";
    gridCanvas.value.style.top = "0";
    gridCanvas.value.style.left = "0";
    gridCanvas.value.style.pointerEvents = "none";
    gridCanvas.value.style.zIndex = "3";
    if (showGrid.value) {
      drawGrid();
    }
  }
}

function drawCoordinateGrid() {
  if (!coordinateGridCanvas.value || !coordinateGridContainer.value) return;

  const container = coordinateGridContainer.value;
  const canvas = coordinateGridCanvas.value;

  const rect = container.getBoundingClientRect();
  canvas.width = rect.width;
  canvas.height = rect.height;

  const ctx = canvas.getContext("2d");
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  const width = canvas.width;
  const height = canvas.height;
  const gridStep = gridSize.value;

  ctx.strokeStyle = "rgba(100, 150, 255, 0.4)";
  ctx.lineWidth = 0.5;
  ctx.font = "9px Arial";
  ctx.fillStyle = "rgba(100, 150, 255, 0.8)";
  ctx.textAlign = "left";
  ctx.textBaseline = "top";

  for (let x = 0; x <= width; x += gridStep) {
    ctx.beginPath();
    ctx.moveTo(x, 0);
    ctx.lineTo(x, height);
    ctx.stroke();

    const pdfX = Math.round((x / width) * 612);
    if (pdfX % 80 === 0 && pdfX >= 0 && pdfX <= 612) {
      ctx.fillText(`${pdfX}`, x + 2, 2);
    }
  }

  ctx.textAlign = "left";
  ctx.textBaseline = "middle";

  for (let y = 0; y <= height; y += gridStep) {
    ctx.beginPath();
    ctx.moveTo(0, y);
    ctx.lineTo(width, y);
    ctx.stroke();

    // Label every 4th line
    // Standard coordinate system: Y=0 at top, Y=792 at bottom
    // Grid: y=0 at top, y=height at bottom
    // Formula: displayY = (y / height) * 792
    const displayY = Math.round((y / height) * 792);

    // Only label if it's a round number and within bounds
    if (displayY % 80 === 0 && displayY >= 0 && displayY <= 792) {
      ctx.fillText(`${displayY}`, 2, y);
    }
  }

  ctx.fillStyle = "rgba(255, 50, 50, 0.9)";
  ctx.font = "bold 10px Arial";
  ctx.textBaseline = "top";
  ctx.textAlign = "left";
  ctx.fillText("X:0, Y:0", 2, 2);
  ctx.textAlign = "right";
  ctx.fillText("X:612, Y:0", width - 2, 2);

  ctx.textAlign = "left";
  ctx.textBaseline = "bottom";
  ctx.fillText("X:0, Y:792", 2, height - 2);

  ctx.textAlign = "right";
  ctx.fillText("X:612, Y:792", width - 2, height - 2);
}

function drawGrid() {
  if (!gridCanvas.value || !showGrid.value) return;

  const ctx = gridCanvas.value.getContext("2d");
  const width = gridCanvas.value.width;
  const height = gridCanvas.value.height;

  ctx.clearRect(0, 0, width, height);
  const gridStep = gridSize.value * scale.value;
  ctx.strokeStyle = "rgba(100, 150, 255, 0.5)";
  ctx.lineWidth = 0.5;
  ctx.font = "9px Arial";
  ctx.fillStyle = "rgba(100, 150, 255, 0.9)";
  ctx.textAlign = "left";
  ctx.textBaseline = "top";

  for (let canvasX = 0; canvasX <= width; canvasX += gridStep) {
    // Draw line
    ctx.beginPath();
    ctx.moveTo(canvasX, 0);
    ctx.lineTo(canvasX, height);
    ctx.stroke();

    const pdfX = canvasX / scale.value;
    if (Math.round(pdfX) % 80 === 0 && pdfX >= 0 && pdfX <= 612) {
      ctx.fillText(`${Math.round(pdfX)}`, canvasX + 3, 3);
    }
  }

  ctx.textAlign = "left";
  ctx.textBaseline = "middle";

  for (let canvasY = 0; canvasY <= height; canvasY += gridStep) {
    // Draw line
    ctx.beginPath();
    ctx.moveTo(0, canvasY);
    ctx.lineTo(width, canvasY);
    ctx.stroke();

    const pdfY = 792 - canvasY / scale.value;
    if (Math.round(pdfY) % 80 === 0 && pdfY >= 0 && pdfY <= 792) {
      ctx.fillText(`${Math.round(pdfY)}`, 3, canvasY);
    }
  }

  ctx.fillStyle = "rgba(255, 50, 50, 0.95)";
  ctx.font = "bold 11px Arial";
  ctx.textAlign = "left";
  ctx.textBaseline = "top";
  ctx.fillText("(0, 792)", 3, 3);
  ctx.textBaseline = "bottom";
  ctx.fillText("(612, 0)", width - 55, height - 3);
}

function onDragStart(event, field) {
  draggedField.value = field;
  event.dataTransfer.effectAllowed = "move";
}

function onDrop(event) {
  event.preventDefault();
  if (!draggedField.value || !pdfContainer.value) return;

  const rect = pdfContainer.value.getBoundingClientRect();
  const scrollLeft = pdfContainer.value.scrollLeft || 0;
  const scrollTop = pdfContainer.value.scrollTop || 0;

  const x = event.clientX - rect.left + scrollLeft;
  const y = event.clientY - rect.top + scrollTop;

  const displayX = x / scale.value;
  const displayY = y / scale.value;

  const margin = 50;
  if (
    displayX < -margin ||
    displayX > 612 + margin ||
    displayY < -margin ||
    displayY > pageHeight.value + margin
  ) {
    ElMessage.warning(
      `Position (${Math.round(displayX)}, ${Math.round(displayY)}) is too far outside PDF bounds. Please drop closer to the PDF.`
    );
    draggedField.value = null;
    return;
  }

  const clampedX = Math.max(0, Math.min(612, displayX));
  const clampedY = Math.max(0, Math.min(pageHeight.value, displayY));

  currentMappings.value[draggedField.value.key] = {
    x: clampedX,
    y: clampedY,
    scale: scale.value,
  };

  ElMessage.success(
    `Mapped "${draggedField.value.label}" at (${Math.round(clampedX)}, ${Math.round(clampedY)})`
  );

  draggedField.value = null;
}

function onCanvasClick(event) {
  if (event.shiftKey && hoverCoords.value) {
  }
}

function zoomIn() {
  if (scale.value < 2) {
    scale.value += 0.25;
  }
}

function zoomOut() {
  if (scale.value > 0.5) {
    scale.value -= 0.25;
  }
}

function isFieldMapped(fieldKey) {
  return !!currentMappings.value[fieldKey];
}

function getMappedCoords(fieldKey) {
  const mapping = currentMappings.value[fieldKey];
  if (!mapping) return "";
  return `${Math.round(mapping.x)}, ${Math.round(mapping.y)}`;
}

function selectField(fieldKey) {
  const fieldInfo = props.availableFields.find((f) => f.key === fieldKey);
  if (fieldInfo && fieldInfo.page && fieldInfo.page !== currentPage.value) {
    currentPage.value = fieldInfo.page;
  }
  selectedFieldKey.value = fieldKey;
  const mapping = currentMappings.value[fieldKey];
  if (mapping) {
    manualX.value = Math.round(mapping.x);
    manualY.value = Math.round(mapping.y);
    manualCharSpacing.value = mapping.charSpacing || 16;
  } else {
    // Default to 0,0 if not yet mapped
    manualX.value = 0;
    manualY.value = 0;
    // Check config for default charSpacing (for SSS/GSIS, TIN, etc.)
    const configCoords =
      props.getCoordinatesFunction(fieldKey, 1) ||
      props.getCoordinatesFunction(fieldKey, 2);
    manualCharSpacing.value = configCoords?.charSpacing || 16;
  }
}

function isBoxedField(fieldKey) {
  if (!fieldKey) return false;
  const mapping = currentMappings.value[fieldKey];
  if (mapping && mapping.charSpacing !== undefined) {
    return true;
  }

  const coords =
    props.getCoordinatesFunction(fieldKey, 1) ||
    props.getCoordinatesFunction(fieldKey, 2);
  return coords && coords.charSpacing !== undefined;
}

function getFieldLabel(fieldKey) {
  const field = props.availableFields.find((f) => f.key === fieldKey);
  return field ? field.label : fieldKey;
}

function getMarkerStyle(mapping) {
  // Convert display coordinates to canvas coordinates for PDF viewer
  // Standard system: Y=0 at top, Y=792 at bottom
  // Canvas Y: 0 at top
  const canvasX = mapping.x * scale.value;
  const canvasY = mapping.y * scale.value;
  return {
    left: canvasX + "px",
    top: canvasY + "px",
  };
}

function getCoordinateMarkerStyle(mapping) {
  if (!coordinateGridContainer.value) return { left: "0px", top: "0px" };

  const container = coordinateGridContainer.value;
  const rect = container.getBoundingClientRect();
  const width = rect.width;
  const height = rect.height;
  const gridX = (mapping.x / 612) * width;
  const gridY = (mapping.y / pageHeight.value) * height;

  return {
    left: gridX + "px",
    top: gridY + "px",
  };
}

function applyManualCoordinates() {
  if (!selectedFieldKey.value) return;

  const x = Math.max(0, Math.min(612, Number(manualX.value) || 0));
  const y = Math.max(0, Math.min(pageHeight.value, Number(manualY.value) || 0));

  const fieldInfo = props.availableFields.find(
    (f) => f.key === selectedFieldKey.value
  );
  const page = fieldInfo?.page || currentPage.value;

  // Preserve existing mapping properties
  const existingMapping = currentMappings.value[selectedFieldKey.value] || {};

  const mapping = {
    ...existingMapping,
    x,
    y,
    scale: scale.value,
    page,
  };

  if (manualCharSpacing.value > 0) {
    mapping.charSpacing = manualCharSpacing.value;
  } else if (existingMapping.charSpacing !== undefined) {
    // Preserve existing charSpacing if manual value is 0 or not set
    mapping.charSpacing = existingMapping.charSpacing;
  }

  currentMappings.value[selectedFieldKey.value] = mapping;
  drawCoordinateGrid();
  generatePreview();

  const coordsMsg = mapping.charSpacing
    ? `(${x}, ${y}) with charSpacing: ${mapping.charSpacing}`
    : `(${x}, ${y})`;

  ElMessage.success(
    `Updated "${getFieldLabel(selectedFieldKey.value)}" to ${coordsMsg} on page ${page}`
  );
}

function removeMapping(fieldKey) {
  delete currentMappings.value[fieldKey];
  ElMessage.info(`Removed mapping for "${getFieldLabel(fieldKey)}"`);
}

function onMarkerDragStart(event, fieldKey) {
  draggingMarker.value = fieldKey;
  event.dataTransfer.effectAllowed = "move";
  event.target.classList.add("dragging");
}

function onMarkerDragEnd(event, fieldKey) {
  event.target.classList.remove("dragging");

  if (!draggingMarker.value || !pdfContainer.value) {
    draggingMarker.value = null;
    return;
  }

  const rect = pdfContainer.value.getBoundingClientRect();
  const scrollLeft = pdfContainer.value.scrollLeft || 0;
  const scrollTop = pdfContainer.value.scrollTop || 0;

  const x = event.clientX - rect.left + scrollLeft;
  const y = event.clientY - rect.top + scrollTop;

  const displayX = x / scale.value;
  const displayY = y / scale.value;

  const margin = 50;
  if (
    displayX < -margin ||
    displayX > 612 + margin ||
    displayY < -margin ||
    displayY > pageHeight.value + margin
  ) {
    ElMessage.warning(
      `Position (${Math.round(displayX)}, ${Math.round(displayY)}) is too far outside PDF bounds. Marker position unchanged.`
    );
    draggingMarker.value = null;
    return;
  }

  const clampedX = Math.max(0, Math.min(612, displayX));
  const clampedY = Math.max(0, Math.min(pageHeight.value, displayY));

  currentMappings.value[fieldKey] = {
    x: clampedX,
    y: clampedY,
    scale: scale.value,
  };

  ElMessage.success(
    `Repositioned "${getFieldLabel(fieldKey)}" to (${Math.round(clampedX)}, ${Math.round(clampedY)})`
  );

  draggingMarker.value = null;
}

function onCoordinateGridDrop(event) {
  event.preventDefault();
  if (!draggedField.value || !coordinateGridContainer.value) return;

  const rect = coordinateGridContainer.value.getBoundingClientRect();
  const x = event.clientX - rect.left;
  const y = event.clientY - rect.top;

  const width = rect.width;
  const height = rect.height;
  const displayX = (x / width) * 612;
  const displayY = (y / height) * pageHeight.value;

  // Clamp to bounds
  const clampedX = Math.max(0, Math.min(612, displayX));
  const clampedY = Math.max(0, Math.min(pageHeight.value, displayY));

  currentMappings.value[draggedField.value.key] = {
    x: clampedX,
    y: clampedY,
    scale: scale.value,
    page: currentPage.value,
  };

  ElMessage.success(
    `Mapped "${draggedField.value.label}" at (${Math.round(clampedX)}, ${Math.round(clampedY)}) on page ${currentPage.value}`
  );

  draggedField.value = null;
}

function onCoordinateMarkerDragStart(event, fieldKey) {
  draggingCoordinateMarker.value = fieldKey;
  event.dataTransfer.effectAllowed = "move";
  event.target.style.opacity = "0.7";
}

function onCoordinateMarkerDragEnd(event, fieldKey) {
  event.target.style.opacity = "1";

  if (!draggingCoordinateMarker.value || !coordinateGridContainer.value) {
    draggingCoordinateMarker.value = null;
    return;
  }

  const rect = coordinateGridContainer.value.getBoundingClientRect();
  const x = event.clientX - rect.left;
  const y = event.clientY - rect.top;

  const width = rect.width;
  const height = rect.height;
  const displayX = (x / width) * 612;
  const displayY = (y / height) * pageHeight.value;

  // Clamp to bounds
  const clampedX = Math.max(0, Math.min(612, displayX));
  const clampedY = Math.max(0, Math.min(pageHeight.value, displayY));

  const existingMapping = currentMappings.value[fieldKey] || {};
  const fieldInfo = props.availableFields.find((f) => f.key === fieldKey);
  const page = fieldInfo?.page || existingMapping.page || currentPage.value;

  currentMappings.value[fieldKey] = {
    ...existingMapping,
    x: clampedX,
    y: clampedY,
    scale: scale.value,
    page,
  };

  ElMessage.success(
    `Repositioned "${getFieldLabel(fieldKey)}" to (${Math.round(clampedX)}, ${Math.round(clampedY)})`
  );

  draggingCoordinateMarker.value = null;
}

function goToPreviousPage() {
  if (currentPage.value > 1) {
    currentPage.value--;
    drawCoordinateGrid();
    generatePreview();
    if (selectedFieldKey.value) {
      const mapping = currentMappings.value[selectedFieldKey.value];
      if (mapping) {
        manualX.value = Math.round(mapping.x);
        manualY.value = Math.round(mapping.y);
        manualCharSpacing.value = mapping.charSpacing || 16;
      }
    }
  }
}

function goToNextPage() {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
    drawCoordinateGrid();
    generatePreview();
    if (selectedFieldKey.value) {
      const mapping = currentMappings.value[selectedFieldKey.value];
      if (mapping) {
        manualX.value = Math.round(mapping.x);
        manualY.value = Math.round(mapping.y);
        manualCharSpacing.value = mapping.charSpacing || 16;
      }
    }
  }
}

function clearAllMappings() {
  currentMappings.value = {};
  localStorage.removeItem(props.storageKey);
  ElMessage.success("All mappings cleared from memory and localStorage");
  generatePreview();
}

async function loadExistingMappings() {
  const formType =
    props.formType || getFormTypeFromStorageKey(props.storageKey);

  try {
    const response = await pdfFieldMappingApi.getMappings(formType);
    if (response.data?.success && response.data?.data?.mappings) {
      const savedMappings = response.data.data.mappings;
      if (Object.keys(savedMappings).length > 0) {
        const mappings = {};
        for (const [key, coords] of Object.entries(savedMappings)) {
          if (coords.x !== undefined && coords.y !== undefined) {
            mappings[key] = {
              x: coords.x,
              y: coords.y,
              scale: 1,
              page:
                coords.page ||
                props.availableFields.find((f) => f.key === key)?.page ||
                1,
            };
            if (coords.charSpacing !== undefined) {
              mappings[key].charSpacing = coords.charSpacing;
            }
          }
        }
        if (Object.keys(mappings).length > 0) {
          currentMappings.value = mappings;
          return;
        }
      }
    }
  } catch (e) {
    console.warn(
      "[PDFFieldMapper] Failed to load from JSON file, trying localStorage:",
      e
    );
  }

  try {
    const saved = localStorage.getItem(props.storageKey);
    if (saved) {
      const savedMappings = JSON.parse(saved);
      const mappings = {};
      for (const [key, coords] of Object.entries(savedMappings)) {
        if (coords.x !== undefined && coords.y !== undefined) {
          mappings[key] = {
            x: coords.x,
            y: coords.y,
            scale: 1,
            page:
              coords.page ||
              props.availableFields.find((f) => f.key === key)?.page ||
              1,
          };
          if (coords.charSpacing !== undefined) {
            mappings[key].charSpacing = coords.charSpacing;
          }
        }
      }
      if (Object.keys(mappings).length > 0) {
        currentMappings.value = mappings;
        return;
      }
    }
  } catch (e) {}

  currentMappings.value = {};
}

function getFormTypeFromStorageKey(storageKey) {
  const keyMap = {
    pagibig_mdf_mappings: "pagibig_mdf",
    philhealth_pmrf_mappings: "philhealth_pmrf",
    bir_form_2305_mappings: "bir_form_2305",
  };
  return keyMap[storageKey] || storageKey.replace("_mappings", "");
}

async function generatePreview() {
  if (Object.keys(currentMappings.value).length === 0) {
    previewPdfUrl.value = null;
    return;
  }

  console.log(
    "[PDFFieldMapper] Generating preview with mappings:",
    Object.keys(currentMappings.value)
  );

  generatingPreview.value = true;
  previewError.value = null;

  try {
    const response = await fetch(props.templatePath);
    if (!response.ok) {
      throw new Error(`Failed to load PDF template: ${response.statusText}`);
    }
    const templateBytes = await response.arrayBuffer();
    const pdfDoc = await PDFDocument.load(templateBytes);
    const pages = pdfDoc.getPages();
    const firstPage = pages[0];
    const secondPage = pages.length > 1 ? pages[1] : null;
    const { width, height } = firstPage.getSize();

    const helveticaFont = await pdfDoc.embedFont(StandardFonts.Helvetica);
    const helveticaBoldFont = await pdfDoc.embedFont(
      StandardFonts.HelveticaBold
    );

    const sampleData = props.sampleDataFunction();
    console.log("[PDFFieldMapper] Sample data keys:", Object.keys(sampleData));

    const fieldToDataKey = props.fieldToDataKey;

    let fieldsDrawn = 0;
    let fieldsSkipped = 0;

    for (const [fieldKey, mapping] of Object.entries(currentMappings.value)) {
      try {
        const pageNumber =
          mapping.page ||
          props.availableFields.find((f) => f.key === fieldKey)?.page ||
          1;

        const configCoords =
          props.getCoordinatesFunction(fieldKey, pageNumber) ||
          props.getCoordinatesFunction(fieldKey, 1) ||
          props.getCoordinatesFunction(fieldKey, 2);

        const coords = {
          ...configCoords,
          ...mapping,
        };

        if (!coords || coords.x === undefined || coords.y === undefined)
          continue;

        const targetPage =
          pageNumber === 2 && secondPage ? secondPage : firstPage;
        const pageHeight =
          pageNumber === 2 && secondPage ? secondPage.getSize().height : height;

        if (pageNumber === 2 && !secondPage) {
        }

        const pdfX = coords.x;
        const pdfY = pageHeight - coords.y; // Y is inverted

        if (
          fieldKey.includes("date_of_birth") &&
          (fieldKey.includes("month") ||
            fieldKey.includes("day") ||
            fieldKey.includes("year"))
        ) {
          const dateParts = sampleData.date_of_birth?.split("-") || [];
          let text = "";
          if (fieldKey.includes("month")) text = dateParts[1] || "";
          else if (fieldKey.includes("day")) text = dateParts[2] || "";
          else if (fieldKey.includes("year")) text = dateParts[0] || "";

          if (text) {
            const fontSize = coords.fontSize || 10;
            const charSpacing = coords.charSpacing || 16;

            let currentX = pdfX;
            for (let i = 0; i < text.length; i++) {
              const char = text[i];
              const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
              const charCenterX = currentX + (charSpacing - charWidth) / 2;

              targetPage.drawText(char, {
                x: charCenterX,
                y: pdfY,
                size: fontSize,
                font: helveticaFont,
              });
              currentX += charSpacing;
            }
          }
        }
        else if (
          (fieldKey === "date_of_birth" ||
            fieldKey === "effective_date" ||
            fieldKey === "certification_date" ||
            fieldKey.includes("birthdate")) &&
          coords.charSpacing
        ) {
          const dataKey = fieldToDataKey[fieldKey];
          if (dataKey && sampleData[dataKey]) {
            const dateStr = String(sampleData[dataKey]);
            let formattedDate = "";

            if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
              const parts = dateStr.split("-");
              formattedDate = `${parts[1]}/${parts[2]}/${parts[0]}`;
            } else {
              const date = new Date(dateStr);
              if (!isNaN(date.getTime())) {
                const month = String(date.getMonth() + 1).padStart(2, "0");
                const day = String(date.getDate()).padStart(2, "0");
                const year = String(date.getFullYear());
                formattedDate = `${month}/${day}/${year}`;
              } else {
                formattedDate = dateStr;
              }
            }

            const fontSize = coords.fontSize || 10;
            const charSpacing = coords.charSpacing;

            let currentX = pdfX;
            for (let i = 0; i < formattedDate.length; i++) {
              const char = formattedDate[i];
              if (char === "/") {
                currentX += charSpacing;
                continue;
              }
              const charWidth = helveticaFont.widthOfTextAtSize(char, fontSize);
              const charCenterX = currentX + (charSpacing - charWidth) / 2;

              targetPage.drawText(char, {
                x: charCenterX,
                y: pdfY,
                size: fontSize,
                font: helveticaFont,
              });
              currentX += charSpacing;
            }
          }
        }
        else if (fieldKey.includes("tin_") && coords.charSpacing) {
          const dataKey = fieldToDataKey[fieldKey];
          if (dataKey && sampleData[dataKey]) {
            let value = String(sampleData[dataKey]).replace(/[-\s]/g, "");
            if (fieldKey.includes("_1")) {
              value = value.substring(0, 3);
            } else if (fieldKey.includes("_2")) {
              value = value.substring(3, 6);
            } else if (fieldKey.includes("_3")) {
              value = value.substring(6, 9);
            }

            if (value) {
              const fontSize = coords.fontSize || 10;
              const charSpacing = coords.charSpacing;

              // Draw each character separately with spacing
              let currentX = pdfX;
              for (let i = 0; i < value.length; i++) {
                const char = value[i];
                const charWidth = helveticaFont.widthOfTextAtSize(
                  char,
                  fontSize
                );
                const charCenterX = currentX + (charSpacing - charWidth) / 2;

                targetPage.drawText(char, {
                  x: charCenterX,
                  y: pdfY,
                  size: fontSize,
                  font: helveticaFont,
                });
                currentX += charSpacing;
              }
            }
          }
        }
        else if (
          (fieldKey === "sss_no" || fieldKey === "gsis_no") &&
          coords.charSpacing
        ) {
          const dataKey = fieldToDataKey[fieldKey];
          if (dataKey && sampleData[dataKey]) {
            const value = String(sampleData[dataKey]).replace(/[-\s]/g, "");

            if (value) {
              const fontSize = coords.fontSize || 10;
              const charSpacing = coords.charSpacing;
              let currentX = pdfX;
              for (let i = 0; i < value.length; i++) {
                const char = value[i];
                const charWidth = helveticaFont.widthOfTextAtSize(
                  char,
                  fontSize
                );
                const charCenterX = currentX + (charSpacing - charWidth) / 2;

                targetPage.drawText(char, {
                  x: charCenterX,
                  y: pdfY,
                  size: fontSize,
                  font: helveticaFont,
                });
                currentX += charSpacing;
              }
            }
          }
        }
        else if (fieldKey === "sss_gsis_no" && coords.charSpacing) {
          const sssValue = sampleData.sss_no ? String(sampleData.sss_no).trim() : "";
          const gsisValue = sampleData.gsis_no ? String(sampleData.gsis_no).trim() : "";
          const value = (sssValue || gsisValue).replace(/[-\s]/g, "");

          if (value) {
            const fontSize = coords.fontSize || 10;
            const charSpacing = coords.charSpacing;
            let currentX = pdfX;
            for (let i = 0; i < value.length; i++) {
              const char = value[i];
              const charWidth = helveticaFont.widthOfTextAtSize(
                char,
                fontSize
              );
              const charCenterX = currentX + (charSpacing - charWidth) / 2;

              targetPage.drawText(char, {
                x: charCenterX,
                y: pdfY,
                size: fontSize,
                font: helveticaFont,
              });
              currentX += charSpacing;
            }
          }
        }
        else if (fieldKey === "sss_gsis_no" && !coords.charSpacing) {
          const sssValue = sampleData.sss_no ? String(sampleData.sss_no).trim() : "";
          const gsisValue = sampleData.gsis_no ? String(sampleData.gsis_no).trim() : "";
          const value = (sssValue || gsisValue).replace(/[-\s]/g, "");

          if (value) {
            targetPage.drawText(value, {
              x: pdfX,
              y: pdfY,
              size: coords.fontSize || 10,
              font: helveticaFont,
              maxWidth: coords.maxWidth || 200,
              color: rgb(0, 0, 0),
            });
          }
        }
        else if (fieldKey === "employee_no" && coords.charSpacing) {
          const dataKey = fieldToDataKey[fieldKey];
          if (dataKey && sampleData[dataKey]) {
            const value = String(sampleData[dataKey]).toUpperCase();

            if (value) {
              const fontSize = coords.fontSize || 10;
              const charSpacing = coords.charSpacing;

              // Draw each character separately with spacing
              let currentX = pdfX;
              for (let i = 0; i < value.length; i++) {
                const char = value[i];
                const charWidth = helveticaFont.widthOfTextAtSize(
                  char,
                  fontSize
                );
                const charCenterX = currentX + (charSpacing - charWidth) / 2;

                targetPage.drawText(char, {
                  x: charCenterX,
                  y: pdfY,
                  size: fontSize,
                  font: helveticaFont,
                });
                currentX += charSpacing;
              }
            }
          }
        }
        else if (fieldKey === "child_birthdate") {
          const dataKey = fieldToDataKey[fieldKey];
          if (dataKey && sampleData[dataKey]) {
            const textValue = String(sampleData[dataKey]).toUpperCase();
            const fontSize = coords.fontSize || 8;
            targetPage.drawText(textValue, {
              x: pdfX,
              y: pdfY,
              size: fontSize,
              font: helveticaFont,
              maxWidth: coords.maxWidth || 200,
            });
          }
        }
        else if (
          fieldKey.startsWith("purpose_") ||
          fieldKey.startsWith("sex_") ||
          fieldKey.startsWith("civil_status_") ||
          fieldKey.startsWith("marital_status_") ||
          fieldKey.startsWith("citizenship_") ||
          fieldKey.startsWith("filer_")
        ) {
          let shouldDraw = false;
          if (
            fieldKey === "purpose_registration" &&
            sampleData.purpose === "registration"
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "purpose_updating" &&
            sampleData.purpose === "updating"
          ) {
            shouldDraw = true;
          } else if (fieldKey === "sex_male" && sampleData.sex === "male") {
            shouldDraw = true;
          } else if (fieldKey === "sex_female" && sampleData.sex === "female") {
            shouldDraw = true;
          } else if (
            fieldKey === "civil_status_single" &&
            sampleData.civil_status === "single"
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "civil_status_married" &&
            sampleData.civil_status === "married"
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "marital_status_single" &&
            sampleData.civil_status === "single"
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "marital_status_married" &&
            sampleData.civil_status === "married"
          ) {
            shouldDraw = true;
          } else if (
            (fieldKey === "marital_status_widow" ||
              fieldKey === "marital_status_widower") &&
            (sampleData.civil_status === "widow" ||
              sampleData.civil_status === "widower")
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "marital_status_annulled" &&
            sampleData.civil_status === "annulled"
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "marital_status_legally_separated" &&
            sampleData.civil_status === "legally_separated"
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "citizenship_filipino" &&
            sampleData.citizenship === "filipino"
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "citizenship_dual" &&
            sampleData.citizenship === "dual"
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "citizenship_foreign" &&
            sampleData.citizenship === "foreign"
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "filer_employee" &&
            sampleData.filer_type === "employee"
          ) {
            shouldDraw = true;
          } else if (
            fieldKey === "filer_self_employed" &&
            sampleData.filer_type === "self_employed"
          ) {
            shouldDraw = true;
          }

          if (shouldDraw) {
            targetPage.drawText("X", {
              x: pdfX,
              y: pdfY,
              size: coords.fontSize || 10,
              font: helveticaFont,
            });
          }
        }
        else {
          const dataKey = fieldToDataKey[fieldKey];
          if (dataKey && sampleData[dataKey]) {
            const textValue =
              fieldKey === "email"
                ? String(sampleData[dataKey]).toLowerCase()
                : String(sampleData[dataKey]).toUpperCase();

            targetPage.drawText(textValue, {
              x: pdfX,
              y: pdfY,
              size: coords.fontSize || 10,
              font: helveticaFont,
              maxWidth: coords.maxWidth || 200,
              color: rgb(0, 0, 0),
            });
            fieldsDrawn++;
          } else {
            const fieldLabel = getFieldLabel(fieldKey);
            console.log(
              `[PDFFieldMapper] No data for field "${fieldKey}" (dataKey: ${dataKey}), showing placeholder`
            );

            targetPage.drawText(`[${fieldLabel}]`, {
              x: pdfX,
              y: pdfY,
              size: coords.fontSize || 10,
              font: helveticaFont,
              color: rgb(0.5, 0.5, 0.5),
            });
            fieldsSkipped++;
          }
        }
      } catch (e) {
        console.error(`[PDFFieldMapper] Error drawing field "${fieldKey}":`, e);
      }
    }

    console.log(
      `[PDFFieldMapper] Preview generation complete: ${fieldsDrawn} fields drawn, ${fieldsSkipped} fields skipped`
    );

    const pdfBytes = await pdfDoc.save();
    const blob = new Blob([pdfBytes], { type: "application/pdf" });

    if (previewPdfUrl.value) {
      URL.revokeObjectURL(previewPdfUrl.value);
    }

    previewPdfUrl.value = URL.createObjectURL(blob);

    const signatureFields = Object.keys(currentMappings.value).filter((k) =>
      k.startsWith("signature_")
    );
    const page1Fields = Object.keys(currentMappings.value).filter((k) => {
      const field = props.availableFields.find((f) => f.key === k);
      return field && field.page === 1;
    });
    const page2Fields = Object.keys(currentMappings.value).filter((k) => {
      const field = props.availableFields.find((f) => f.key === k);
      return field && field.page === 2;
    });
  } catch (error) {
    previewError.value = error.message || "Failed to generate preview";
    ElMessage.error("Failed to generate preview: " + error.message);
  } finally {
    generatingPreview.value = false;
  }
}

async function regeneratePreview() {
  await generatePreview();
}

async function saveMappings() {
  try {
    const formType =
      props.formType || getFormTypeFromStorageKey(props.storageKey);

    const mappingsToSave = {};
    for (const [key, mapping] of Object.entries(currentMappings.value)) {
      const fieldInfo = props.availableFields.find((f) => f.key === key);
      const page = mapping.page || fieldInfo?.page || 1;

      const savedMapping = {
        x: Math.round(mapping.x),
        y: Math.round(mapping.y),
        fontSize: mapping.fontSize || 10,
        maxWidth: mapping.maxWidth || 200,
        page,
      };

      if (mapping.charSpacing !== undefined) {
        savedMapping.charSpacing = mapping.charSpacing;
      }

      mappingsToSave[key] = savedMapping;

      const pageKey = `page${page}`;
      if (!props.coordinatesConfig[pageKey]) {
        props.coordinatesConfig[pageKey] = {};
      }
      props.coordinatesConfig[pageKey][key] = savedMapping;
    }

    try {
      await pdfFieldMappingApi.saveMappings(formType, mappingsToSave);
    } catch (apiError) {
      console.error("[PDFFieldMapper] Failed to save to JSON file:", apiError);
      ElMessage.warning(
        "Failed to save to JSON file, but saved to localStorage as backup."
      );
    }

    localStorage.setItem(props.storageKey, JSON.stringify(mappingsToSave));

    ElMessage.success({
      message: `Saved ${Object.keys(mappingsToSave).length} field mappings to JSON file and localStorage!`,
      duration: 5000,
    });

    emit("saved", mappingsToSave);
    close();
  } catch (error) {
    ElMessage.error("Failed to save mappings: " + error.message);
  }
}

function close() {
  visible.value = false;
}

function testPDFLink() {
  const templateUrl = props.templatePath;
  window.open(templateUrl, "_blank");
  ElMessage.info("Opening PDF in new tab. If it loads, the path is correct.");
}

async function reloadPDF() {
  ElMessage.info("Reloading PDF...");
  await loadPDF();
}

function updateHoverCoords(event) {
  if (!pdfContainer.value) return;

  const rect = pdfContainer.value.getBoundingClientRect();
  const scrollLeft = pdfContainer.value.scrollLeft || 0;
  const scrollTop = pdfContainer.value.scrollTop || 0;

  const x = event.clientX - rect.left + scrollLeft;
  const y = event.clientY - rect.top + scrollTop;

  const displayX = Math.round(x / scale.value);
  const displayY = Math.round(y / scale.value);

  if (
    displayX >= 0 &&
    displayX <= 612 &&
    displayY >= 0 &&
    displayY <= pageHeight.value
  ) {
    hoverCoords.value = {
      x: event.clientX,
      y: event.clientY,
      pdfX: displayX,
      pdfY: displayY,
    };
  } else {
    hoverCoords.value = null;
  }
}

// Track mouse position on coordinate grid (left side)
function updateCoordinateGridHover(event) {
  if (!coordinateGridContainer.value) return;

  const rect = coordinateGridContainer.value.getBoundingClientRect();
  const x = event.clientX - rect.left;
  const y = event.clientY - rect.top;

  const gridWidth = rect.width;
  const gridHeight = rect.height;
  const displayX = Math.round((x / gridWidth) * 612);
  const displayY = Math.round((y / gridHeight) * pageHeight.value);

  if (
    displayX >= 0 &&
    displayX <= 612 &&
    displayY >= 0 &&
    displayY <= pageHeight.value
  ) {
    coordinateGridHover.value = {
      x: event.clientX,
      y: event.clientY,
      pdfX: displayX,
      pdfY: displayY,
    };
  } else {
    coordinateGridHover.value = null;
  }
}
</script>

<style scoped>
.pdf-mapper-container {
  display: flex;
  gap: 20px;
  height: 80vh;
}

.coordinate-grid-panel {
  width: 350px;
  border-right: 1px solid #e4e7ed;
  padding-right: 20px;
  display: flex;
  flex-direction: column;
}

.coordinate-grid-panel h3 {
  margin-top: 0;
  margin-bottom: 5px;
  font-size: 14px;
}

.coordinate-grid-panel .help-text {
  font-size: 11px;
  color: #909399;
  margin-bottom: 10px;
}

.coordinate-grid-container {
  flex: 1;
  position: relative;
  border: 2px solid #e4e7ed;
  border-radius: 4px;
  background: #fafafa;
  min-height: 400px;
  overflow: hidden;
}

.coordinate-grid-canvas {
  display: block;
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
  pointer-events: none;
}

.coordinate-markers {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 10;
}

.coordinate-marker {
  position: absolute;
  background: rgba(64, 158, 255, 0.9);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 10px;
  cursor: move;
  pointer-events: all;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  border: 1px solid white;
  transform: translate(-50%, -50%);
  z-index: 11;
}

.coordinate-marker:hover {
  background: rgba(102, 177, 255, 0.95);
  transform: translate(-50%, -50%) scale(1.1);
}

.coordinate-marker-label {
  display: block;
  font-weight: 600;
  white-space: nowrap;
}

.coordinate-marker-delete {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 16px;
  height: 16px;
  background: #f56c6c;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  cursor: pointer;
  line-height: 1;
}

.coordinate-grid-coords {
  position: absolute;
  background: rgba(0, 0, 0, 0.8);
  color: #00ff00;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: bold;
  pointer-events: none;
  z-index: 20;
  transform: translate(-50%, -100%);
  margin-top: -5px;
  font-family: "Courier New", monospace;
}

.coordinate-grid-info {
  margin-top: 10px;
  font-size: 11px;
  color: #909399;
  text-align: center;
}

.fields-panel {
  width: 300px;
  border-right: 1px solid #e4e7ed;
  padding-right: 20px;
  overflow-y: auto;
}

.fields-panel h3 {
  margin-top: 0;
  margin-bottom: 10px;
}

.help-text {
  font-size: 12px;
  color: #909399;
  margin-bottom: 15px;
}

.fields-list {
  max-height: 60vh;
  overflow-y: auto;
}

.field-item {
  display: flex;
  align-items: center;
  padding: 10px;
  margin-bottom: 8px;
  border: 2px solid #e4e7ed;
  border-radius: 4px;
  cursor: move;
  background: white;
  transition: all 0.2s;
}

.field-item.selected {
  border-color: #409eff;
  background: #ecf5ff;
}

.field-item:hover {
  border-color: #409eff;
  background: #ecf5ff;
}

.field-item.mapped {
  border-color: #67c23a;
  background: #f0f9ff;
}

.field-icon {
  font-size: 20px;
  margin-right: 10px;
}

.field-info {
  flex: 1;
}

.field-label {
  font-weight: 500;
  font-size: 14px;
}

.field-key {
  font-size: 11px;
  color: #909399;
  margin-top: 2px;
}

.mapped-indicator {
  font-size: 11px;
  color: #67c23a;
  margin-top: 4px;
}

.actions-panel {
  margin-top: 20px;
  display: flex;
  gap: 10px;
}

.coord-input-panel {
  margin-top: 20px;
  padding: 15px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background: #fafafa;
}

.coord-input-panel h4 {
  margin: 0 0 10px 0;
  font-size: 14px;
  font-weight: 600;
  color: #303133;
}

.coord-placeholder {
  padding: 10px 0;
  text-align: center;
}

.coord-input-row {
  display: flex;
  align-items: center;
  margin-bottom: 6px;
  gap: 6px;
}

.coord-label {
  width: 18px;
  font-size: 12px;
  color: #606266;
}

.coord-hint {
  display: block;
  margin-top: 4px;
  font-size: 11px;
  color: #909399;
}

.pdf-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.pdf-controls {
  margin-bottom: 10px;
  display: flex;
  align-items: center;
}

.pdf-viewer-container {
  flex: 1;
  position: relative;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  overflow: auto;
  background: #f5f5f5;
  min-height: 600px;
}

.pdf-viewer-container iframe {
  display: block;
}

.pdf-preview-container {
  flex: 1;
  position: relative;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  overflow: auto;
  background: #f5f5f5;
  min-height: 600px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.preview-iframe {
  width: 100%;
  height: 100%;
  border: none;
  background: white;
}

.preview-loading,
.preview-error,
.preview-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 40px;
  color: #606266;
}

.preview-error {
  color: #f56c6c;
}

.pdf-canvas {
  display: block;
  background: white;
}

.grid-overlay {
  position: absolute;
  top: 0;
  left: 0;
  pointer-events: none;
  z-index: 4;
  opacity: 0.7;
  width: 100%;
  height: 100%;
}

.markers-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 100;
  overflow: visible;
}

.field-marker {
  position: absolute;
  transform: translate(-50%, -100%);
  background: rgba(64, 158, 255, 0.9);
  color: white;
  padding: 6px 10px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
  cursor: move;
  pointer-events: all;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  z-index: 100;
  border: 2px solid white;
  max-width: 200px;
  transition: all 0.2s;
  user-select: none;
}

.field-marker:hover {
  background: rgba(102, 177, 255, 0.95);
  transform: translate(-50%, -100%) scale(1.05);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

.field-marker.dragging {
  opacity: 0.7;
  transform: translate(-50%, -100%) scale(1.1);
}

.marker-label {
  white-space: nowrap;
  display: block;
  font-weight: 600;
}

.marker-coords {
  display: block;
  font-size: 9px;
  opacity: 0.8;
  margin-top: 2px;
}

.marker-delete {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 18px;
  height: 18px;
  background: #f56c6c;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  cursor: pointer;
  line-height: 1;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 0.2s;
}

.marker-delete:hover {
  background: #f78989;
  transform: scale(1.1);
}

.coords-display {
  position: fixed;
  background: rgba(0, 0, 0, 0.9);
  color: #00ff00;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: bold;
  pointer-events: none;
  z-index: 1000;
  transform: translate(-50%, -100%);
  margin-top: -10px;
  border: 2px solid #00ff00;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
  min-width: 120px;
  text-align: center;
}

.coords-label {
  font-size: 10px;
  color: #00ccff;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.coords-value {
  font-family: "Courier New", monospace;
  line-height: 1.4;
}

.pdf-info {
  margin-top: 10px;
  font-size: 12px;
  color: #909399;
}
</style>
