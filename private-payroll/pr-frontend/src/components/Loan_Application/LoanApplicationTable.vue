<template>
  <div class="table-shell">
    <!-- Loading skeleton -->
    <div v-if="loading" class="skeleton-wrap">
      <div v-for="i in 6" :key="i" class="skeleton-row" />
    </div>

    <template v-else>
      <div class="table-wrap" v-if="rows.length > 0">
        <table>
          <colgroup>
            <col style="width: 10%" />

            <col style="width: 9%" />

            <col style="width: 6%" />

            <col style="width: 6%" />

            <col style="width: 6%" />

            <col style="width: 6%" />

            <col style="width: 6%" />

            <col style="width: 6%" />

            <!-- <col style="width: 6%" /> -->

            <col style="width: 5%" />
          </colgroup>
          <thead>
            <tr>
              <th>Employee</th>
              <th>Loan type</th>
              <th>Voucher no.</th>
              <th>Amount</th>
              <th>Amortization</th>
              <th>Payment</th>
              <th>Balance</th>
              <!-- <th>Repayment</th> -->
              <th>End date</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in paginatedRows" :key="row.id">
              <!-- Employee -->
              <td>
                <div class="emp-cell">
                  <div
                    class="avatar"
                    :style="{
                      background: avatarBg(row.name),
                      color: avatarColor(row.name),
                    }"
                  >
                    <img
                      v-if="row.photo"
                      :src="row.photo"
                      class="avatar-img"
                      :alt="row.name"
                    />
                    <span v-else>{{ getInitials(row.name) }}</span>
                  </div>
                  <span class="emp-name">{{ row.name }}</span>
                </div>
              </td>

              <!-- Loan type badge -->
              <td>
                <span class="loan-badge" :class="loanBadgeClass(row.loan)">
                  {{ row.loan || "—" }}
                </span>
              </td>

              <!-- Voucher — truncate UUIDs -->
              <td>
                <span class="mono" :title="row.voucher_number">
                  {{ truncateVoucher(row.voucher_number) }}
                </span>
              </td>

              <!-- Amount -->
              <td class="amount">₱{{ fmt(row.loan_amount) }}</td>

              <!-- Amortization -->
              <td class="amount">₱{{ fmt(row.loan_amortization) }}</td>

              <!-- Payment -->
              <td class="amount">₱{{ fmt(row.payment) }}</td>

              <!-- Balance with color -->
              <td>
                <span :class="balanceClass(row.balance)">
                  {{ Number(row.balance) < 0 ? "-" : "" }}₱{{
                    fmt(Math.abs(row.balance))
                  }}
                </span>
              </td>

              <!-- Repayment progress -->
              <!-- <td>
                <div class="progress-wrap">
                  <div class="progress-bar">
                    <div
                      class="progress-fill"
                      :class="progressClass(row)"
                      :style="{ width: progressPct(row) + '%' }"
                    />
                  </div>
                  <span
                    class="progress-pct"
                    :class="{ 'pct-danger': progressPct(row) > 100 }"
                    >{{ progressPct(row) }}%</span
                  >
                </div>
              </td> -->

              <!-- End date -->
              <td>
                <span class="date-text">{{ fmtDate(row.end_date) }}</span>
              </td>

              <!-- Actions -->
              <td>
                <div class="actions-cell">
                  <button class="act-edit" @click="$emit('view', row)">
                    View
                  </button>
                  <button
                    class="act-recon"
                    :title="'Reconstruct ' + row.name"
                    @click="$emit('reconstruct', row)"
                  >
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                      <path
                        d="M2.5 7A4.5 4.5 0 1 0 4 3.5"
                        stroke="currentColor"
                        stroke-width="1.3"
                        stroke-linecap="round"
                      />
                      <path
                        d="M1.5 2v2h2"
                        stroke="currentColor"
                        stroke-width="1.3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination">
          <span class="page-info">
            Showing {{ (currentPage - 1) * pageSize + 1 }}–{{
              Math.min(currentPage * pageSize, rows.length)
            }}
            of {{ rows.length }} results
          </span>
          <div class="page-btns">
            <button
              class="page-btn"
              :disabled="currentPage === 1"
              @click="currentPage--"
            >
              &#8249;
            </button>
            <button
              v-for="p in totalPages"
              :key="p"
              class="page-btn"
              :class="{ active: p === currentPage }"
              @click="currentPage = p"
            >
              {{ p }}
            </button>
            <button
              class="page-btn"
              :disabled="currentPage === totalPages"
              @click="currentPage++"
            >
              &#8250;
            </button>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
          <rect
            x="5"
            y="7"
            width="26"
            height="22"
            rx="3"
            stroke="#d1d5db"
            stroke-width="1.5"
          />
          <path
            d="M11 13h14M11 18h9"
            stroke="#d1d5db"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
        <p>No loan applications found</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});
defineEmits(["view", "reconstruct"]);

const currentPage = ref(1);
const pageSize = ref(10);

const totalPages = computed(() =>
  Math.max(1, Math.ceil(props.rows.length / pageSize.value)),
);
const paginatedRows = computed(() => {
  const s = (currentPage.value - 1) * pageSize.value;
  return props.rows.slice(s, s + pageSize.value);
});
watch(
  () => props.rows,
  () => {
    currentPage.value = 1;
  },
);

// --- Formatting ---
const fmt = (val) => {
  const n = Number(val || 0);
  return n.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};
const fmtDate = (d) => {
  if (!d) return "—";
  const dt = new Date(d);
  if (isNaN(dt)) return d;
  return dt.toLocaleDateString("en-PH", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};
const truncateVoucher = (v) => {
  if (!v) return "—";
  const dashes = (v.match(/-/g) || []).length;
  if (dashes >= 3 && v.length > 16) return v.slice(0, 8) + "…";
  return v;
};
const getInitials = (name) => {
  if (!name) return "??";
  return name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
};

// --- Avatar colors (deterministic per name) ---
const AVATAR_PAIRS = [
  { bg: "#E6F1FB", fg: "#0C447C" },
  { bg: "#E1F5EE", fg: "#085041" },
  { bg: "#EEEDFE", fg: "#3C3489" },
  { bg: "#FAECE7", fg: "#712B13" },
  { bg: "#FAEEDA", fg: "#633806" },
];
const nameIdx = (name) => {
  if (!name) return 0;
  return name.charCodeAt(0) % AVATAR_PAIRS.length;
};
const avatarBg = (name) => AVATAR_PAIRS[nameIdx(name)].bg;
const avatarColor = (name) => AVATAR_PAIRS[nameIdx(name)].fg;

// --- Loan badge ---
const loanBadgeClass = (loan) => {
  if (!loan) return "lb-none";
  const l = loan.toLowerCase();
  if (l.includes("bank")) return "lb-bank";
  if (l.includes("gsis")) return "lb-gsis";
  if (l.includes("hdmf") || l.includes("pagibig")) return "lb-hdmf";
  return "lb-other";
};

// --- Balance ---
const balanceClass = (val) => {
  const n = Number(val || 0);
  if (n < 0) return "bal-neg";
  if (n === 0) return "bal-zero";
  return "bal-ok";
};

// --- Progress (paid %) ---
const progressPct = (row) => {
  const amount = Number(row.loan_amount || 0);
  const balance = Number(row.balance || 0);
  if (!amount) return 0;
  const paid = amount - balance;
  return Math.round((paid / amount) * 100);
};
const progressClass = (row) => {
  const pct = progressPct(row);
  if (pct > 100) return "pf-danger";
  if (pct > 75) return "pf-warn";
  return "";
};
</script>

<style scoped>
.table-shell {
  width: 100%;
}

/* skeleton */
.skeleton-wrap {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.skeleton-row {
  height: 44px;
  border-radius: 8px;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}
@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* table */
.table-wrap {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
  table-layout: fixed;
}
thead {
  background: #f9fafb;
}
thead th {
  padding: 10px 14px;
  text-align: left;
  font-weight: 500;
  font-size: 12px;
  color: #6b7280;
  border-bottom: 1px solid #e5e7eb;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.1s;
}
tbody tr:last-child {
  border-bottom: none;
}
tbody tr:hover {
  background: #f9fafb;
}
td {
  padding: 11px 14px;
  color: #111827;
  vertical-align: middle;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* emp */
.emp-cell {
  display: flex;
  align-items: center;
  gap: 9px;
}
.avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 500;
  flex-shrink: 0;
  overflow: hidden;
}
.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.emp-name {
  font-weight: 500;
  font-size: 13px;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* badges */
.loan-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
  align-items: center;
  justify-content: center;
  display: flex;
}
.lb-gsis {
  background: #e1f5ee;
  color: #085041;
}
.lb-bank {
  background: #e6f1fb;
  color: #0c447c;
}
.lb-hdmf {
  background: #eeedfe;
  color: #3c3489;
}
.lb-other {
  background: #f3f4f6;
  color: #374151;
}
.lb-none {
  background: #f3f4f6;
  color: #9ca3af;
}

.mono {
  font-family: monospace;
  font-size: 12px;
  color: #6b7280;
}
.amount {
  font-weight: 500;
}
.bal-ok {
  color: #111827;
}
.bal-neg {
  color: #a32d2d;
  font-weight: 500;
}
.bal-zero {
  color: #9ca3af;
}

/* progress */
.progress-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}
.progress-bar {
  height: 4px;
  border-radius: 2px;
  background: #e5e7eb;
  flex: 1;
  overflow: hidden;
  min-width: 50px;
}
.progress-fill {
  height: 100%;
  border-radius: 2px;
  background: #1d9e75;
  transition: width 0.3s;
}
.progress-fill.pf-warn {
  background: #ba7517;
}
.progress-fill.pf-danger {
  background: #e24b4a;
}
.progress-pct {
  font-size: 11px;
  color: #6b7280;
  min-width: 32px;
  text-align: right;
}
.pct-danger {
  color: #a32d2d;
}

.date-text {
  font-size: 12px;
  color: #6b7280;
}

/* actions */
.actions-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}
.act-edit {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 12px;
  color: #374151;
  cursor: pointer;
}
.act-edit:hover {
  background: #f3f4f6;
}
.act-recon {
  background: transparent;
  border: none;
  padding: 5px 6px;
  border-radius: 6px;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.act-recon:hover {
  background: #f3f4f6;
  color: #374151;
}

/* pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-top: 1px solid #f3f4f6;
}
.page-info {
  font-size: 12px;
  color: #9ca3af;
}
.page-btns {
  display: flex;
  gap: 4px;
}
.page-btn {
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  background: transparent;
  font-size: 12px;
  color: #6b7280;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.page-btn.active {
  background: #409eff;
  color: #fff;
  border-color: #409eff;
}
.page-btn:hover:not(.active):not(:disabled) {
  background: #f3f4f6;
}
.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* empty */
.empty-state {
  text-align: center;
  padding: 48px 0;
  color: #9ca3af;
  font-size: 14px;
}
.empty-state svg {
  margin: 0 auto 12px;
  display: block;
}
</style>
