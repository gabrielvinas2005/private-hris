<template>
    <MainLayout>
        <div class="container">
            <h3>RATA Table Setup</h3>

            <el-card shadow="never" class="block-card">
                <div class="controls-row">
                    <el-input v-model="search" placeholder="Search (day from/to or %)"></el-input>
                    <el-button type="primary" @click="openForm">Add / Edit</el-button>
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
                            <el-dropdown-menu>
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
                    <el-table-column v-if="columnVisibility.serial" label="#" type="index" width="60" />
                    <el-table-column v-if="columnVisibility.day_from" prop="day_from" label="Work Day From" min-width="160" />
                    <el-table-column v-if="columnVisibility.day_to" prop="day_to" label="Work Day To" min-width="160" />
                    <el-table-column v-if="columnVisibility.percentage" prop="percentage" label="Percentage" min-width="140" />
                    <el-table-column v-if="columnVisibility.actions" label="Actions" width="120">
                        <template #default="{ row }">
                            <div class="actions">
                                <el-popconfirm title="Delete this row?" @confirm="() => handleDelete(row)">
                                    <template #reference>
                                        <el-button type="danger" size="small">Delete</el-button>
                                    </template>
                                </el-popconfirm>
                            </div>
                        </template>
                    </el-table-column>
                </el-table>
            </el-card>

            <el-dialog v-model="showForm" title="Add / Edit RATA Table" width="820px">
                <div>
                    <el-button type="primary" size="small" @click="addRow">Add Row</el-button>
                </div>
                <el-table :data="form.items" border height="420" style="margin-top: 8px;">
                    <el-table-column label="#" type="index" width="60" />
                    <el-table-column label="Work Day From" min-width="180">
                        <template #default="{ row }">
                            <el-input v-model.number="row.day_from" type="number" />
                        </template>
                    </el-table-column>
                    <el-table-column label="Work Day To" min-width="180">
                        <template #default="{ row }">
                            <el-input v-model.number="row.day_to" type="number" />
                        </template>
                    </el-table-column>
                    <el-table-column label="Percentage" min-width="160">
                        <template #default="{ row }">
                            <el-input v-model.number="row.percentage" type="number" />
                        </template>
                    </el-table-column>
                    <el-table-column label="" width="110">
                        <template #default="{ $index }">
                            <el-button size="small" @click="removeRow($index)">Remove</el-button>
                        </template>
                    </el-table-column>
                </el-table>
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
import { onMounted } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useRataTable } from '../../../composables/useRataTable.js'
import { useExport } from '../../../composables/useExport.js'
import { Printer, Download, Document, Setting, ArrowDown } from '@element-plus/icons-vue'

const {
    loading, search, rows, filtered,
    columnVisibility, visibilityKeys,
    showForm, form, openForm, addRow, removeRow, save, deleteRow,
    fetchRows
} = useRataTable()

const { exportPrint, exportExcel, exportPDF } = useExport()

onMounted(fetchRows)

function getColumnLabel(key){
    const map = { serial:'#', day_from:'Work Day From', day_to:'Work Day To', percentage:'Percentage', actions:'Actions' }
    return map[key] || key
}

const exportColumns = [
    { key: 'serial', label: '#' },
    { key: 'day_from', label: 'Work Day From' },
    { key: 'day_to', label: 'Work Day To' },
    { key: 'percentage', label: 'Percentage' }
]

function buildExportPayload(){
    const data = filtered.value.map((row, index) => ({
        ...row,
        serial: index + 1
    }))
    return {
        title: 'RATA Table Setup',
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
        ElMessage.success('RATA table updated successfully')
    } else if(res && res.message){
        ElMessage.error(res.message)
    }
}

async function handleDelete(row){
    const res = await deleteRow(row)
    if(res && res.success!==false){
        ElMessage.success('Row deleted')
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
.actions { display: flex; gap: 6px; }
</style>