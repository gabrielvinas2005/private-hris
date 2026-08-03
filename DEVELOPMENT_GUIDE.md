# Feature Development Guide & Architectural Compliance
## Private HRIS Platform

This guide outlines the standard technical workflow for introducing new modules, functions, and features to the HRIS portal while maintaining strict adherence to the 6 core architectural principles of the platform posture.

---

## 🏛️ The 6 Core Architectural Principles

When building any new feature, it must comply with these 6 non-negotiable architectural tenets:

| # | Architectural Principle | Implementation Requirement |
|---|---|---|
| 1 | **Browser-Based Responsive Front-End** | Must work on Desktop, Tablet, and Mobile devices via standard browsers (Vue 3/Vite responsive components). Zero native app/client software required. |
| 2 | **Single Master Data Source (Core HR)** | All employee references MUST bind to the centralized `employee_id` / `user_id` from the 201 Core HR Master Data. Never duplicate employee tables. |
| 3 | **Unified Role-Based Access (RBAC)** | Access to endpoints, tabs, and action buttons MUST be controlled by the central RBAC middleware and user permission context. |
| 4 | **Shared Configurable Approval Engine** | Transaction approvals (Leave, OT, Requisitions, Expenses, custom requests) MUST route through the shared workflow engine, NOT custom hard-coded logic. |
| 5 | **Central Event & Notification Engine** | Status changes MUST trigger domain events that feed the central notification system (In-App, Email, SMS). |
| 6 | **Deployment Independence** | Features must rely on environment-driven parameters (`.env`) to support seamless deployment on On-Premise servers or Cloud infrastructure. |

---

## 🚀 Step-by-Step Blueprint for Adding a New Feature

Below is the standard 5-step development pattern for introducing a new feature (e.g., *Equipment / Asset Requisition Module*).

```
   +-------------------------------------------------------------------------------+
   | Step 1: Database Migration & Core HR Linkage                                  |
   | Create migration in primary DB (`sqlsrv`) referencing `employee_id` (Core HR) |
   +---------------------------------------+---------------------------------------+
                                           |
                                           v
   +-------------------------------------------------------------------------------+
   | Step 2: Backend API & RBAC Integration                                        |
   | Create Controller & API Routes protected by Sanctum & RBAC Permissions        |
   +---------------------------------------+---------------------------------------+
                                           |
                                           v
   +-------------------------------------------------------------------------------+
   | Step 3: Shared Workflow Engine Integration                                    |
   | Register transaction type in Workflow Engine for multi-tier approvals          |
   +---------------------------------------+---------------------------------------+
                                           |
                                           v
   +-------------------------------------------------------------------------------+
   | Step 4: Event & Notification Dispatch                                         |
   | Dispatch Domain Events (`Submitted`, `Approved`) -> In-App, Email, SMS          |
   +---------------------------------------+---------------------------------------+
                                           |
                                           v
   +-------------------------------------------------------------------------------+
   | Step 5: Responsive Vue 3 Frontend Component                                   |
   | Build responsive UI with RBAC dynamic visibility & mobile/tablet support      |
   +-------------------------------------------------------------------------------+
```

---

### Step 1: Database Schema & Core HR Binding

All new transactional tables must anchor directly to the **Core HR Employee Master Data** (`employees` or `users` table).

```php
// database/migrations/xxxx_xx_xx_create_asset_requisitions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::connection('sqlsrv')->create('asset_requisitions', function (Blueprint $table) {
            $table->id();
            // 1. Single Master Data Source (Core HR Link)
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            
            $table->string('item_name');
            $table->text('reason');
            
            // 2. Shared Approval Workflow Tracking
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->unsignedBigInteger('current_step_id')->nullable();
            
            $table->timestamps();
        });
    }
};
```

---

### Step 2: Backend API & Role-Based Access Control (RBAC)

Protect API endpoints using **Laravel Sanctum** and the centralized permission governance system.

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    // Role-based route authorization
    Route::get('/asset-requisitions', [AssetRequisitionController::class, 'index'])
        ->middleware('permission:asset_requisition.view');
        
    Route::post('/asset-requisitions', [AssetRequisitionController::class, 'store'])
        ->middleware('permission:asset_requisition.create');
});
```

```php
// app/Http/Controllers/AssetRequisitionController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssetRequisition;

class AssetRequisitionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'reason'    => 'required|string',
        ]);

        // Bound to authenticated Core HR employee
        $employeeId = $request->user()->employee_id;

        $requisition = AssetRequisition::create([
            'employee_id' => $employeeId,
            'item_name'   => $validated['item_name'],
            'reason'      => $validated['reason'],
            'status'      => 'PENDING'
        ]);

        // Step 3 & 4: Initiate Workflow & Notifications
        app('WorkflowEngine')->initiateWorkflow('ASSET_REQUISITION', $requisition);

        return response()->json([
            'message' => 'Asset requisition submitted successfully.',
            'data'    => $requisition
        ], 201);
    }
}
```

---

### Step 3: Integrating with the Shared Workflow / Approval Engine

Instead of writing hard-coded approval logic for your new module, register the transaction type with the **Configurable Workflow Engine**.

```php
// Concept: Shared Workflow Engine Registration
class WorkflowEngine
{
    public function initiateWorkflow(string $transactionType, $transaction)
    {
        // 1. Fetch approval chain configured in Control Panel for this transaction type
        // e.g. ASSET_REQUISITION -> [Stage 1: Supervisor, Stage 2: HR Admin]
        $workflowConfig = WorkflowConfig::where('transaction_type', $transactionType)->first();
        
        // 2. Create approval pipeline entries
        foreach ($workflowConfig->steps as $step) {
            TransactionApprovalStep::create([
                'transaction_id'   => $transaction->id,
                'transaction_type' => $transactionType,
                'approver_role_id' => $step->role_id,
                'approver_id'      => $this->resolveApprover($transaction->employee_id, $step),
                'status'           => 'PENDING'
            ]);
        }

        // 3. Trigger Notification Engine for Stage 1 Approver
        event(new TransactionSubmittedEvent($transaction, $transactionType));
    }
}
```

---

### Step 4: Event-Driven Notification Engine

When transactions are submitted, approved, or rejected, dispatch domain events. The central Notification Engine handles delivery across channels (In-App, Email, SMS).

```php
// app/Listeners/SendTransactionNotification.php
namespace App\Listeners;

use App\Events\TransactionSubmittedEvent;
use App\Services\NotificationService;

class SendTransactionNotification
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(TransactionSubmittedEvent $event)
    {
        $approver = $event->getPendingApprover();

        // Single service handles all channels dynamically based on user preferences
        $this->notificationService->send([
            'recipient' => $approver,
            'title'     => 'New Approval Request',
            'message'   => "A new {$event->transactionType} request requires your review.",
            'channels'  => ['in_app', 'email', 'sms'], // Channels enabled for system
            'action_url'=> "/approvals/{$event->transactionType}/{$event->transaction->id}"
        ]);
    }
}
```

---

### Step 5: Responsive Vue 3 Front-End Component

Build responsive UI views that scale across Desktop, Tablet, and Mobile viewports without requiring additional client software.

```vue
<!-- src/views/AssetRequisition/Index.vue -->
<template>
  <div class="p-4 sm:p-6 max-w-7xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Asset Requisitions</h1>
        <p class="text-xs sm:text-sm text-gray-500">Request equipment and items linked to your Core HR Profile.</p>
      </div>
      
      <!-- Dynamic RBAC Button Visibility -->
      <button 
        v-if="hasPermission('asset_requisition.create')"
        @click="showCreateModal = true"
        class="w-full sm:w-auto px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm transition-colors shadow-sm"
      >
        + New Requisition
      </button>
    </div>

    <!-- Responsive Layout: Table on Desktop, Cards on Mobile -->
    <div class="hidden md:block bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
      <table class="w-full text-left text-sm text-gray-600">
        <thead class="bg-gray-50 border-b text-xs uppercase font-semibold text-gray-500">
          <tr>
            <th class="px-6 py-3">Item Name</th>
            <th class="px-6 py-3">Reason</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Date Submitted</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in requisitions" :key="item.id" class="border-b hover:bg-gray-50/50">
            <td class="px-6 py-4 font-medium text-gray-900">{{ item.item_name }}</td>
            <td class="px-6 py-4">{{ item.reason }}</td>
            <td class="px-6 py-4">
              <span :class="statusBadgeClass(item.status)">{{ item.status }}</span>
            </td>
            <td class="px-6 py-4 text-xs text-gray-400">{{ formatDate(item.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile Card View for Tablets & Smartphones -->
    <div class="md:hidden space-y-4">
      <div 
        v-for="item in requisitions" 
        :key="item.id" 
        class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm"
      >
        <div class="flex justify-between items-center mb-2">
          <h3 class="font-bold text-gray-900">{{ item.item_name }}</h3>
          <span :class="statusBadgeClass(item.status)">{{ item.status }}</span>
        </div>
        <p class="text-sm text-gray-600 mb-3">{{ item.reason }}</p>
        <div class="text-xs text-gray-400 border-t pt-2 mt-2">
          Submitted on: {{ formatDate(item.created_at) }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import ApiService from '@/services/apiService';

const authStore = useAuthStore();
const requisitions = ref([]);
const showCreateModal = ref(false);

// RBAC Permission Checking
const hasPermission = (permissionKey) => {
  return authStore.permissions.includes(permissionKey);
};

const fetchRequisitions = async () => {
  try {
    const res = await ApiService.get('/asset-requisitions');
    requisitions.value = res.data;
  } catch (err) {
    console.error('Failed to load requisitions:', err);
  }
};

const statusBadgeClass = (status) => {
  switch (status) {
    case 'APPROVED': return 'px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800';
    case 'REJECTED': return 'px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800';
    default: return 'px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800';
  }
};

const formatDate = (dateStr) => new Date(dateStr).toLocaleDateString();

onMounted(fetchRequisitions);
</script>
```

---

## 📋 Architectural Checklist for New Features

Before deploying or submitting a pull request for a new feature, verify it passes all compliance checks:

- [ ] **Zero Native Software Dependency**: Accessible via standard web browsers on Desktop, Tablet, and Mobile.
- [ ] **Single Master Employee Record**: All data belongs to `employee_id` from Core HR; no standalone or duplicate user tables.
- [ ] **RBAC Protection**: API routes protected by Sanctum and permission middleware; UI elements toggle via user role context.
- [ ] **Shared Workflow Integration**: Approvals route through Control Panel's configurable approval engine rather than custom inline logic.
- [ ] **Notification Triggers**: Submissions and approvals raise domain events that trigger In-App, Email, and SMS alerts.
- [ ] **Cloud & On-Premise Ready**: All configuration parameters (DB, Mail, Storage, SMS) are externalized in `.env`.
