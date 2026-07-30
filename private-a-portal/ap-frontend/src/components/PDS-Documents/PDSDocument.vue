<template>
  <div class="pds-documents">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Documents</h3>

    <div class="space-y-6">
      <!-- Documents Section -->
      <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div
          class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200 rounded-t-lg"
        >
          <div class="flex justify-between items-center">
            <div>
              <h4
                class="text-lg font-semibold text-gray-900 inline-flex items-center mb-1"
              >
                <FileText :size="20" class="mr-2 text-indigo-600" /> Uploaded
                Documents
              </h4>
              <p class="text-sm text-gray-600">
                Upload and manage your documents. Supported formats: PDF, JPG,
                PNG, DOC, DOCX, XLS, XLSX
              </p>
              <p
                v-if="!hasPersonalInfo"
                class="text-sm text-amber-600 mt-1 font-medium"
              >
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Please save your Personal Information first before uploading
                documents.
              </p>
            </div>
            <button
              @click="showUploadModal = true"
              :disabled="!hasPersonalInfo"
              :class="[
                'px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center gap-2',
                hasPersonalInfo
                  ? 'bg-green-600 hover:bg-green-700 text-white'
                  : 'bg-gray-400 text-white cursor-not-allowed',
              ]"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4"
                ></path>
              </svg>
              Upload Document
            </button>
          </div>
        </div>

        <div class="p-6">
          <!-- Warning: Personal Information Not Saved -->
          <div
            v-if="!hasPersonalInfo"
            class="mb-6 p-4 bg-amber-50 border-l-4 border-amber-400 rounded-md"
          >
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <i
                  class="fas fa-exclamation-triangle text-amber-400 text-xl"
                ></i>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-amber-800">
                  Personal Information Required
                </h3>
                <div class="mt-2 text-sm text-amber-700">
                  <p>
                    Please save your Personal Information first before uploading
                    documents. Go to the
                    <strong>Personal Information</strong> section and save your
                    details.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div
            v-if="loading && documentsData.length === 0"
            class="text-center py-8 text-gray-500"
          >
            <div
              class="inline-flex items-center justify-center w-12 h-12 mb-4 bg-blue-100 rounded-full"
            >
              <div
                class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"
              ></div>
            </div>
            <p>Loading documents...</p>
          </div>

          <!-- Empty State -->
          <div
            v-else-if="documentsData.length === 0"
            class="text-center py-8 text-gray-500"
          >
            <svg
              class="w-12 h-12 mx-auto mb-4 text-gray-300"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              ></path>
            </svg>
            <p>
              No documents uploaded yet. Click "Upload Document" to get started.
            </p>
          </div>

          <!-- Documents Table -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Document Type
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Description
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    File Name
                  </th>
                  <th
                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Upload Date
                  </th>
                  <th
                    class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
                  >
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="(doc, index) in documentsData"
                  :key="doc.employee_document_id || index"
                  class="hover:bg-gray-50 transition-colors"
                >
                  <td class="px-4 py-3 whitespace-nowrap">
                    <span class="text-sm font-medium text-gray-900">
                      {{ doc.name || "N/A" }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <p
                      class="text-sm text-gray-700 max-w-xs truncate"
                      :title="doc.description"
                    >
                      {{ doc.description || "No description" }}
                    </p>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <svg
                        class="w-4 h-4 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        ></path>
                      </svg>
                      <span class="text-sm text-gray-600">
                        {{ doc.attachment_name || "N/A" }}
                      </span>
                    </div>
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(doc.created_at) }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-center">
                    <div class="flex items-center justify-center gap-2">
                      <!-- Preview Button -->
                      <button
                        @click="previewDocument(doc)"
                        class="text-blue-600 hover:text-blue-800 transition-colors p-1 rounded"
                        title="Preview"
                      >
                        <svg
                          class="w-5 h-5"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                          ></path>
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                          ></path>
                        </svg>
                      </button>
                      <!-- Download Button -->
                      <button
                        @click="downloadDocument(doc)"
                        class="text-green-600 hover:text-green-800 transition-colors p-1 rounded"
                        title="Download"
                      >
                        <svg
                          class="w-5 h-5"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                          ></path>
                        </svg>
                      </button>
                      <!-- Delete Button -->
                      <button
                        @click="removeDocument(index, doc)"
                        class="text-red-600 hover:text-red-800 transition-colors p-1 rounded"
                        title="Delete"
                      >
                        <svg
                          class="w-5 h-5"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Documents Summary -->
      <div
        v-if="documentsData.length > 0"
        class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 rounded-xl border border-blue-200 shadow-sm p-6"
      >
        <div class="flex items-center gap-2 mb-4">
          <div
            class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
          >
            <svg
              class="w-6 h-6 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              ></path>
            </svg>
          </div>
          <h5 class="text-lg font-semibold text-gray-800">Documents Summary</h5>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
          >
            <div class="flex items-center justify-between mb-2">
              <div
                class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center"
              >
                <svg
                  class="w-5 h-5 text-blue-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  ></path>
                </svg>
              </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1">
              {{ documentsData.length }}
            </div>
            <div class="text-sm text-gray-600 font-medium">Total Documents</div>
          </div>

          <!-- PDF Files Card -->
          <div
            class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
          >
            <div class="flex items-center justify-between mb-2">
              <div
                class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center"
              >
                <svg
                  class="w-5 h-5 text-red-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                  ></path>
                </svg>
              </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1">
              {{ pdfCount }}
            </div>
            <div class="text-sm text-gray-600 font-medium">PDF Files</div>
          </div>

          <!-- Image Files Card -->
          <div
            class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
          >
            <div class="flex items-center justify-between mb-2">
              <div
                class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center"
              >
                <svg
                  class="w-5 h-5 text-green-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                  ></path>
                </svg>
              </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1">
              {{ imageCount }}
            </div>
            <div class="text-sm text-gray-600 font-medium">Image Files</div>
          </div>

          <!-- Office Files Card -->
          <div
            class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow"
          >
            <div class="flex items-center justify-between mb-2">
              <div
                class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center"
              >
                <svg
                  class="w-5 h-5 text-indigo-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  ></path>
                </svg>
              </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 mb-1">
              {{ officeFilesCount }}
            </div>
            <div class="text-sm text-gray-600 font-medium">Office Files</div>
          </div>
        </div>

        <!-- Additional Stats Row -->
        <div
          v-if="mostRecentDocument"
          class="mt-4 pt-4 border-t border-gray-200"
        >
          <div
            class="flex items-center gap-3 text-sm bg-blue-50 rounded-lg p-3 border border-blue-200"
          >
            <div
              class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0"
            >
              <svg
                class="w-4 h-4 text-blue-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
              </svg>
            </div>
            <div>
              <div class="text-blue-800 font-medium">Most Recent Upload</div>
              <div class="text-blue-600 text-xs">
                {{ mostRecentDocument.document_name || "Document" }} -
                {{ formatDate(mostRecentDocument.created_at) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upload Modal -->
      <teleport to="body">
        <div
          v-if="showUploadModal"
          class="fixed inset-0 bg-black/35 backdrop-blur-[2px] flex items-center justify-center z-[9999]"
          @click.self="closeUploadModal"
        >
          <div
            class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
          >
            <div class="p-6">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                  Upload Document
                </h3>
                <button
                  @click="closeUploadModal"
                  class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    ></path>
                  </svg>
                </button>
              </div>

              <form @submit.prevent="uploadDocument" class="space-y-4">
                <!-- File Upload -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Files * (Up to 5 files)
                  </label>
                  <div
                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors"
                  >
                    <div class="space-y-1 text-center">
                      <svg
                        class="mx-auto h-12 w-12 text-gray-400"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 48 48"
                        aria-hidden="true"
                      >
                        <path
                          d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                          stroke-width="2"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                        />
                      </svg>
                      <div class="flex text-sm text-gray-600">
                        <label
                          for="file-upload"
                          class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500"
                        >
                          <span>Upload files</span>
                          <input
                            id="file-upload"
                            ref="fileInput"
                            type="file"
                            multiple
                            class="sr-only"
                            @change="handleFileSelect"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                            :disabled="uploadForm.files.length >= 5"
                          />
                        </label>
                        <p class="pl-1">or drag and drop</p>
                      </div>
                      <p class="text-xs text-gray-500">
                        PDF, JPG, PNG, DOC, DOCX, XLS, XLSX up to 10MB each
                      </p>
                      <p class="text-xs text-gray-500 mt-1">
                        Maximum 5 files at once
                      </p>
                      <p
                        v-if="uploadForm.files.length > 0"
                        class="text-sm text-gray-700 font-medium mt-2"
                      >
                        {{ uploadForm.files.length }} file(s) selected
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Selected Files with Document Types -->
                <div v-if="uploadForm.files.length > 0" class="space-y-4">
                  <div
                    v-for="(fileItem, index) in uploadForm.files"
                    :key="index"
                    class="border border-gray-200 rounded-lg p-4 space-y-3"
                  >
                    <div class="flex items-start justify-between">
                      <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">
                          {{ fileItem.file.name }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                          {{ formatFileSize(fileItem.file.size) }}
                        </p>
                      </div>
                      <button
                        type="button"
                        @click="removeFile(index)"
                        class="text-red-600 hover:text-red-700 text-sm"
                      >
                        Remove
                      </button>
                    </div>

                    <!-- Document Type -->
                    <div>
                      <label
                        class="block text-sm font-medium text-gray-700 mb-1"
                      >
                        Document Type *
                      </label>
                      <select
                        v-model.number="fileItem.document_type_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        required
                      >
                        <option :value="null">Select Document Type</option>
                        <option
                          v-for="docType in documentTypes"
                          :key="docType.id"
                          :value="Number(docType.id)"
                        >
                          {{ docType.name }}
                        </option>
                      </select>
                    </div>

                    <!-- Description -->
                    <div>
                      <label
                        class="block text-sm font-medium text-gray-700 mb-1"
                      >
                        Description
                      </label>
                      <textarea
                        v-model="fileItem.description"
                        rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Enter document description..."
                      ></textarea>
                    </div>
                  </div>
                </div>

                <!-- Error Message -->
                <div
                  v-if="uploadError"
                  class="bg-red-50 border border-red-200 rounded-md p-3"
                >
                  <p class="text-sm text-red-600">{{ uploadError }}</p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                  <button
                    type="button"
                    @click="closeUploadModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="
                      uploading ||
                      uploadForm.files.length === 0 ||
                      !allFilesHaveDocumentType
                    "
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed rounded-md transition-colors flex items-center gap-2"
                  >
                    <svg
                      v-if="uploading"
                      class="w-4 h-4 animate-spin"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                      ></path>
                    </svg>
                    {{
                      uploading
                        ? "Uploading..."
                        : `Upload ${uploadForm.files.length} Document(s)`
                    }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </teleport>

      <!-- Preview Modal -->
      <teleport to="body">
        <div
          v-if="showPreviewModal && currentPreviewDocument"
          class="fixed inset-0 bg-black/35 backdrop-blur-[2px] flex items-center justify-center z-[9999]"
          @click.self="closePreviewModal"
        >
          <div
            class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col"
          >
            <div class="p-4 border-b flex justify-between items-center">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ currentPreviewDocument.name || "Document Preview" }}
              </h3>
              <button
                @click="closePreviewModal"
                class="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg
                  class="w-6 h-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  ></path>
                </svg>
              </button>
            </div>
            <div class="p-4 flex-1 overflow-auto">
              <!-- Show image preview for image files -->
              <div
                v-if="
                  previewUrl &&
                  (currentPreviewDocument?.extension?.toLowerCase() === 'png' ||
                    currentPreviewDocument?.extension?.toLowerCase() ===
                      'jpg' ||
                    currentPreviewDocument?.extension?.toLowerCase() === 'jpeg')
                "
                class="text-center"
              >
                <img
                  :src="previewUrl"
                  :alt="currentPreviewDocument?.name"
                  class="max-w-full max-h-[600px] mx-auto rounded"
                />
              </div>
              <!-- Show iframe preview for PDF -->
              <iframe
                v-else-if="
                  previewUrl &&
                  currentPreviewDocument?.extension?.toLowerCase() === 'pdf'
                "
                :src="previewUrl"
                class="w-full h-full min-h-[500px] border rounded"
                frameborder="0"
              ></iframe>
              <!-- No preview available -->
              <div v-else class="text-center py-8 text-gray-500">
                <p>Preview not available for this file type.</p>
                <button
                  @click="downloadDocument(currentPreviewDocument)"
                  class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                  Download Instead
                </button>
              </div>
            </div>
          </div>
        </div>
      </teleport>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, ref, onMounted } from "vue";
import { ElNotification, ElMessageBox } from "element-plus";
import ApiService from "@/services/api";
import { useAppNotification } from "@/composables/useAppNotification.js";
import { FileText } from "lucide-vue-next";

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(["documents-updated"]);

const notify = useAppNotification();

const documentsData = reactive([]);
const loading = ref(false);
const error = ref(null);
const employeeId = ref(null);
const showUploadModal = ref(false);
const showPreviewModal = ref(false);
const currentPreviewDocument = ref(null);
const previewUrl = ref(null);
const uploading = ref(false);
const uploadError = ref(null);
const fileInput = ref(null);
const hasPersonalInfo = ref(false);

const documentTypes = ref([]);
const uploadForm = reactive({
  files: [], // Array of { file, document_type_id, description }
});

const loadDocumentsData = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await ApiService.getApplicantPage();

    if (response.data && response.data.data) {
      const data = response.data.data;

      employeeId.value =
        data.employee_info?.[0]?.id ??
        props.info?.employee_info?.[0]?.id ??
        props.info?.employee_id ??
        props.info?.id ??
        null;

      // Check if personal information is saved (at minimum first_name and last_name)
      // Handle both array and collection formats
      const applicant = Array.isArray(data.applicant)
        ? data.applicant[0] || null
        : data.applicant || props.info?.applicant?.[0] || null;
      hasPersonalInfo.value = applicant
        ? !!(applicant.first_name && applicant.last_name)
        : false;

      documentsData.length = 0;
      if (data.documents && Array.isArray(data.documents)) {
        data.documents.forEach((document) => {
          documentsData.push({
            employee_document_id: document.employee_document_id || null,
            name: document.name || "",
            description: document.description || "",
            attachment_name: document.attachment_name || "",
            extension: document.extension || "",
            created_at: document.created_at || null,
            updated_at: document.updated_at || null,
          });
        });
      }
    }
  } catch (err) {
    console.error("Error loading documents data:", err);
    error.value =
      err.response?.data?.message || "Failed to load documents data";
    notify.error("Load failed", error.value, { duration: 5000 });
  } finally {
    loading.value = false;
  }
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files || []);

  if (files.length === 0) return;

  // Check if adding these files would exceed the limit of 5
  const totalFiles = uploadForm.files.length + files.length;
  if (totalFiles > 5) {
    uploadError.value = `You can upload a maximum of 5 files at once. You currently have ${uploadForm.files.length} file(s) selected.`;
    fileInput.value.value = "";
    return;
  }

  const allowedExtensions = [
    "pdf",
    "jpg",
    "jpeg",
    "png",
    "doc",
    "docx",
    "xls",
    "xlsx",
  ];
  const maxSize = 10 * 1024 * 1024; // 10MB in bytes

  const validFiles = [];
  const errors = [];

  files.forEach((file) => {
    // Validate file size
    if (file.size > maxSize) {
      errors.push(`${file.name}: File size must be less than 10MB`);
      return;
    }

    // Validate file extension
    const fileExtension = file.name.split(".").pop().toLowerCase();
    if (!allowedExtensions.includes(fileExtension)) {
      errors.push(
        `${file.name}: Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX`,
      );
      return;
    }

    // Add valid file to the list
    validFiles.push({
      file: file,
      document_type_id: null,
      description: "",
    });
  });

  if (errors.length > 0) {
    uploadError.value = errors.join("\n");
  } else {
    uploadError.value = null;
  }

  // Add valid files to the upload form
  uploadForm.files.push(...validFiles);

  // Reset file input
  fileInput.value.value = "";
};

const removeFile = (index) => {
  uploadForm.files.splice(index, 1);
  uploadError.value = null;
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
};

const allFilesHaveDocumentType = computed(() => {
  return uploadForm.files.every(
    (fileItem) =>
      fileItem.document_type_id !== null && fileItem.document_type_id !== 0,
  );
});

const closeUploadModal = () => {
  showUploadModal.value = false;
  uploadForm.files = [];
  uploadError.value = null;
  if (fileInput.value) {
    fileInput.value.value = "";
  }
};

const uploadDocument = async () => {
  // Check if personal information is saved first
  if (!hasPersonalInfo.value) {
    uploadError.value =
      "Please save your Personal Information first before uploading documents. Go to Personal Information section and save your details.";
    notify.error(
      "Personal information required",
      "Please save your Personal Information first before uploading documents.",
      { duration: 5000 },
    );
    return;
  }

  if (uploadForm.files.length === 0) {
    uploadError.value = "Please select at least one file";
    return;
  }

  // Validate all files have document types
  const filesWithoutType = uploadForm.files.filter(
    (fileItem) => !fileItem.document_type_id || fileItem.document_type_id === 0,
  );
  if (filesWithoutType.length > 0) {
    uploadError.value = "Please select a document type for all files";
    return;
  }

  if (employeeId.value === null || employeeId.value === undefined) {
    try {
      const response = await ApiService.getApplicantPage();
      if (response.data && response.data.data) {
        employeeId.value =
          response.data.data.employee_info?.[0]?.id ??
          props.info?.employee_info?.[0]?.id ??
          props.info?.employee_id ??
          props.info?.id ??
          null;
      }
    } catch (err) {
      console.error("Error fetching employee ID:", err);
      notify.error("Error", "Failed to fetch employee information", {
        duration: 5000,
      });
      return;
    }
  }

  if (employeeId.value === null || employeeId.value === undefined) {
    uploadError.value =
      "Unable to determine employee ID. Please refresh the page and try again.";
    return;
  }

  // Check if document types are loaded
  if (!documentTypes.value || documentTypes.value.length === 0) {
    uploadError.value =
      "Document types are still loading. Please wait a moment and try again.";
    console.error("Document types not loaded yet");
    return;
  }

  uploading.value = true;
  uploadError.value = null;

  try {
    const formData = new FormData();
    formData.append("section", "documents");

    // Process each file
    uploadForm.files.forEach((fileItem) => {
      const selectedId = Number(fileItem.document_type_id);
      const selectedDocType = documentTypes.value.find(
        (dt) => Number(dt.id) === selectedId,
      );

      if (!selectedDocType) {
        throw new Error(
          `Invalid document type for file: ${fileItem.file.name}`,
        );
      }

      formData.append("document_name[]", selectedDocType.name);
      formData.append(
        "document_type_id[]",
        fileItem.document_type_id.toString(),
      );
      formData.append("document_description[]", fileItem.description || "");
      formData.append("document[]", fileItem.file);
    });

    const response = await ApiService.storePDS(employeeId.value, formData);

    if (response.data && response.data.success) {
      const count = uploadForm.files.length;
      notify.success(
        "Uploaded",
        `${count} document${count > 1 ? "s" : ""} uploaded successfully!`,
        { duration: 4000 },
      );

      closeUploadModal();
      await loadDocumentsData();
      emit("documents-updated");
    } else {
      throw new Error(response.data?.message || "Failed to upload documents");
    }
  } catch (err) {
    console.error("Error uploading documents:", err);
    const msg =
      err.response?.data?.message ||
      (err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(" ")
        : null) ||
      err.message ||
      "Failed to upload documents.";
    uploadError.value = msg;
    notify.error("Upload failed", msg, { duration: 6000 });
  } finally {
    uploading.value = false;
  }
};

const previewDocument = async (doc) => {
  if (!doc.employee_document_id) {
    notify.error("Error", "Cannot preview document: Invalid document ID", {
      duration: 5000,
    });
    return;
  }

  currentPreviewDocument.value = doc;

  // Check if file can be previewed (PDF, images)
  const previewableExtensions = ["pdf", "jpg", "jpeg", "png"];
  const extension = doc.extension?.toLowerCase() || "";

  if (previewableExtensions.includes(extension)) {
    try {
      // Fetch file as blob to create a blob URL for preview
      const response = await ApiService.downloadDocument(
        doc.employee_document_id,
      );

      // Create blob URL with correct MIME type. Server may send wrong Content-Type
      // (e.g. text/html in test/proxy), so we force the type from document extension.
      const mimeType =
        extension === "pdf"
          ? "application/pdf"
          : extension === "jpg" || extension === "jpeg"
            ? "image/jpeg"
            : extension === "png"
              ? "image/png"
              : "application/octet-stream";

      // Strip UTF-8 BOM (EF BB BF) if present — test/proxy sometimes prepends it and
      // breaks image (PNG/JPEG) signature so <img> shows broken; PDF can still render.
      let payload = response.data;
      if (response.data instanceof Blob && response.data.size >= 3) {
        try {
          const bomBuf = await response.data.slice(0, 3).arrayBuffer();
          const b = new Uint8Array(bomBuf);
          if (b[0] === 0xef && b[1] === 0xbb && b[2] === 0xbf) {
            payload = response.data.slice(3);
          }
        } catch {
          // If BOM detection fails, fall back to raw blob
        }
      }

      const blob =
        payload instanceof Blob
          ? new Blob([payload], { type: mimeType })
          : new Blob([payload], { type: mimeType });
      previewUrl.value = URL.createObjectURL(blob);

      showPreviewModal.value = true;
    } catch (err) {
      console.error("Error loading preview:", err);
      notify.error("Preview failed", "Failed to load document preview", {
        duration: 5000,
      });
    }
  } else {
    showPreviewModal.value = true;
    previewUrl.value = null;
  }
};

const closePreviewModal = () => {
  showPreviewModal.value = false;

  // Clean up blob URL to free memory
  if (previewUrl.value && previewUrl.value.startsWith("blob:")) {
    URL.revokeObjectURL(previewUrl.value);
  }

  currentPreviewDocument.value = null;
  previewUrl.value = null;
};

const downloadDocument = async (doc) => {
  if (!doc.employee_document_id) {
    notify.error("Error", "Cannot download document: Invalid document ID", {
      duration: 5000,
    });
    return;
  }

  try {
    const response = await ApiService.downloadDocument(
      doc.employee_document_id,
    );

    // Create blob URL and trigger download
    let blob;
    if (response.data instanceof Blob) {
      blob = response.data;
    } else {
      blob = new Blob([response.data], {
        type: response.headers["content-type"] || "application/octet-stream",
      });
    }

    const url = URL.createObjectURL(blob);
    const link = window.document.createElement("a");
    link.href = url;
    link.download = doc.attachment_name || "document";
    window.document.body.appendChild(link);
    link.click();
    window.document.body.removeChild(link);
    URL.revokeObjectURL(url);

    notify.success("Download", "Download started", { duration: 3000 });
  } catch (err) {
    console.error("Error downloading document:", err);
    notify.error("Download failed", "Failed to download document", {
      duration: 5000,
    });
  }
};

const removeDocument = async (index, doc) => {
  try {
    await ElMessageBox.confirm(
      "Are you sure you want to delete this document?",
      "Delete Document",
      {
        confirmButtonText: "Delete",
        cancelButtonText: "Cancel",
        type: "warning",
      },
    );
  } catch {
    return;
  }

  const documentId =
    doc?.employee_document_id || documentsData[index]?.employee_document_id;

  if (!documentId) {
    ElNotification({
      title: "Error",
      message: "Cannot delete document: Invalid document ID",
      type: "error",
      duration: 5000,
      position: "top-right",
    });
    return;
  }

  // Store document for potential restoration
  const docToRestore = { ...documentsData[index] };

  // Remove from UI immediately
  documentsData.splice(index, 1);
  ElNotification({
    title: "Success",
    message: "Document removed successfully!",
    type: "success",
    duration: 3000,
    position: "top-right",
  });

  try {
    await ApiService.destroyPDS(13, documentId); // type_id 13 for documents
    emit("documents-updated");
  } catch (err) {
    documentsData.splice(index, 0, docToRestore);
    const errorMessage =
      err.response?.data?.message || "Failed to delete document";
    ElNotification({
      title: "Error",
      message: errorMessage,
      type: "error",
      duration: 5000,
      position: "top-right",
    });
    console.error("Error deleting document:", err);
  }
};

const formatDate = (dateString) => {
  if (!dateString) return "N/A";
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  } catch {
    return dateString;
  }
};

const pdfCount = computed(() => {
  return documentsData.filter((doc) => doc.extension?.toLowerCase() === "pdf")
    .length;
});

const imageCount = computed(() => {
  const imageExtensions = ["jpg", "jpeg", "png"];
  return documentsData.filter((doc) =>
    imageExtensions.includes(doc.extension?.toLowerCase()),
  ).length;
});

const officeFilesCount = computed(() => {
  const officeExtensions = ["doc", "docx", "xls", "xlsx"];
  return documentsData.filter((doc) =>
    officeExtensions.includes(doc.extension?.toLowerCase()),
  ).length;
});

const mostRecentDocument = computed(() => {
  if (documentsData.length === 0) return null;

  const docsWithDates = documentsData
    .filter((doc) => doc.created_at)
    .map((doc) => ({
      ...doc,
      dateObj: new Date(doc.created_at),
    }))
    .sort((a, b) => b.dateObj - a.dateObj);

  return docsWithDates.length > 0 ? docsWithDates[0] : null;
});

const loadDocumentTypes = async () => {
  try {
    const response = await ApiService.getDocumentTypes();
    if (
      response.data &&
      response.data.data &&
      response.data.data.acceptance_letter_documents
    ) {
      documentTypes.value = response.data.data.acceptance_letter_documents.map(
        (dt) => ({
          id: Number(dt.id),
          name: dt.name,
        }),
      );
    } else {
    }
  } catch (err) {
    notify.error(
      "Load failed",
      "Failed to load document types. Please refresh the page.",
      { duration: 5000 },
    );
  }
};

onMounted(() => {
  loadDocumentsData();
  loadDocumentTypes();
});
</script>

<style scoped>
/* Custom scrollbar for modal */
.overflow-y-auto::-webkit-scrollbar {
  width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
