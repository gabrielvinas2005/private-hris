<template>
  <div class="overtime-setup">
    <div class="header">
      <div class="title">Overtime Types</div>
      <div class="actions">
        <el-button type="primary" @click="handleAdd">Add Row</el-button>
        <el-button type="success" :loading="saving" @click="handleSave">Save Changes</el-button>
      </div>
    </div>

    <el-card shadow="hover">
      <el-table :data="rows" v-loading="loading" border style="width:100%">
        <el-table-column label="#" width="60" align="center">
          <template #default="{ $index }">{{ $index + 1 }}</template>
        </el-table-column>

        <el-table-column label="Name" min-width="200">
          <template #default="{ row }">
            <el-input v-model="row.name" placeholder="Name" />
          </template>
        </el-table-column>

        <el-table-column label="Rate" width="120" align="center">
          <template #default="{ row }">
            <el-input v-model="row.rate" placeholder="Rate" />
          </template>
        </el-table-column>

        <el-table-column label="ND From" width="140" align="center">
          <template #default="{ row }">
            <el-input v-model="row.nd_from" placeholder="HH:MM" />
          </template>
        </el-table-column>

        <el-table-column label="ND To" width="140" align="center">
          <template #default="{ row }">
            <el-input v-model="row.nd_to" placeholder="HH:MM" />
          </template>
        </el-table-column>

        <el-table-column label="ND Rating" width="140" align="center">
          <template #default="{ row }">
            <el-input v-model="row.nd_rating" placeholder="ND Rating" />
          </template>
        </el-table-column>

        <el-table-column label="Min OT" width="120" align="center">
          <template #default="{ row }">
            <el-input v-model="row.min_ot" placeholder="Min OT" />
          </template>
        </el-table-column>

        <el-table-column label="Max OT" width="120" align="center">
          <template #default="{ row }">
            <el-input v-model="row.max_ot" placeholder="Max OT" />
          </template>
        </el-table-column>

        <el-table-column label="Active" width="100" align="center">
          <template #default="{ row }">
            <el-switch v-model="row.active" />
          </template>
        </el-table-column>

        <el-table-column label="Actions" width="120" fixed="right" align="center">
          <template #default="{ row }">
            <el-popconfirm title="Delete this row?" @confirm="handleDelete(row)" confirm-button-text="Delete" cancel-button-text="Cancel">
              <template #reference>
                <el-button type="danger" size="small">Delete</el-button>
              </template>
            </el-popconfirm>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useOvertimeTypes } from '../../../composables/useOvertimeTypes.js'

const { rows, loading, saving, fetchOvertimeTypes, addBlankRow, saveAll, deleteOne } = useOvertimeTypes()

onMounted(fetchOvertimeTypes)

function handleAdd() {
  addBlankRow()
}

async function handleSave() {
  await saveAll()
}

async function handleDelete(row) {
  if (!row.id) {
    const idx = rows.value.indexOf(row)
    if (idx >= 0) rows.value.splice(idx, 1)
    return
  }
  await deleteOne(row.id)
}
</script>

<style scoped>
.overtime-setup .header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.overtime-setup .title { font-weight: 600; font-size: 18px; }
</style>


