<template>
    <MainLayout>
        <div class="container">
            <h3>RATA Positions Setup</h3>

            <el-card shadow="never" class="block-card">
                <div class="controls-row">
                    <el-input v-model="search" placeholder="Search by code or position" clearable />
                    <el-button type="primary" @click="openForm">Edit Amounts</el-button>
                </div>
            </el-card>

            <el-card shadow="never" class="block-card">
                <div class="export-row">
                    <div class="export-buttons">
                        <el-button @click="handlePrint"><el-icon><Printer /></el-icon> Print</el-button>
                        <el-button @click="handleExportCsv"><el-icon><Download /></el-icon> Excel</el-button>
                        <el-button @click="handleExportPdf"><el-icon><Document /></el-icon> PDF</el-button>
                    </div>
                    <el-dropdown trigger="click">
                        <el-button>
                            <el-icon><Setting /></el-icon>
                            Column Visibility
                            <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                        </el-button>
                        <template #dropdown>
                            <el-dropdown-menu class="column-visibility">
                                <el-dropdown-item v-for="key in visibilityKeys" :key="key" disabled>
                                    <el-checkbox @click.stop v-model="columnVisibility[key]">{{ getColumnLabel(key) }}</el-checkbox>
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>
            </el-card>

            <el-card shadow="never">
                <el-table :data="filtered" v-loading="loading" border stripe height="500">
                    <el-table-column v-if="columnVisibility.serial" label="#" width="60" type="index" />
                    <el-table-column v-if="columnVisibility.code" prop="code" label="Code" min-width="140" />
                    <el-table-column v-if="columnVisibility.position" prop="position" label="Position" min-width="240" />
                    <el-table-column v-if="columnVisibility.ra_amount" prop="ra_amount" label="RA Amount" min-width="140" />
                    <el-table-column v-if="columnVisibility.ta_amount" prop="ta_amount" label="TA Amount" min-width="140" />
                </el-table>
            </el-card>

            <el-dialog v-model="showForm" title="Edit RATA Amounts" width="800px">
                <div class="dialog-body">
                    <el-table :data="formData.rows" border height="400">
                        <el-table-column label="#" type="index" width="60" />
                        <el-table-column prop="code" label="Code" min-width="140" />
                        <el-table-column prop="position" label="Position" min-width="240" />
                        <el-table-column label="RA Amount" min-width="140">
                            <template #default="{ row }">
                                <el-input v-model.number="row.ra_amount" type="number" />
                            </template>
                        </el-table-column>
                        <el-table-column label="TA Amount" min-width="140">
                            <template #default="{ row }">
                                <el-input v-model.number="row.ta_amount" type="number" />
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
                <template #footer>
                    <span class="dialog-actions">
                        <el-button @click="showForm=false">Cancel</el-button>
                        <el-button type="primary" @click="handleSave">Save Changes</el-button>
                    </span>
                </template>
            </el-dialog>
        </div>
    </MainLayout>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useRataPositions } from '../../../composables/useRataPositions.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
    loading, search, records, columnVisibility, visibilityKeys, filtered,
    showForm, formData, openForm, save, fetchRecords
} = useRataPositions()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchRecords)

function getColumnLabel(key){
    const map = { serial:'#', code:'Code', position:'Position', ra_amount:'RA Amount', ta_amount:'TA Amount', actions:'Actions' }
    return map[key] || key
}

const exportColumns = [
    { key: 'serial', label: '#' },
    { key: 'code', label: 'Code' },
    { key: 'position', label: 'Position' },
    { key: 'ra_amount', label: 'RA Amount' },
    { key: 'ta_amount', label: 'TA Amount' }
]

function buildExportPayload(){
    const data = filtered.value.map((row, index) => ({
        ...row,
        serial: index + 1
    }))
    return {
        title: 'RATA Positions Setup',
        data,
        columns: exportColumns,
        columnVisibility: columnVisibility.value
    }
}

function handlePrint(){ 
    exportPrint(buildExportPayload())
}
function handleExportCsv(){
    exportExcel(buildExportPayload())
}
function handleExportPdf(){ 
    exportPDF(buildExportPayload())
}

async function handleSave(){
    const res = await save()
    if(res && res.success!==false){
        ElMessage.success('RATA positions updated successfully')
    } else if(res && res.message){
        ElMessage.error(res.message)
    }
}
</script>

<style scoped>
.container { display: flex; flex-direction: column; gap: 12px; }
.block-card { padding: 8px; }
.controls-row { display: flex; gap: 12px; align-items: center; }
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }
.dialog-actions { display: flex; gap: 8px; justify-content: flex-end; }
</style>