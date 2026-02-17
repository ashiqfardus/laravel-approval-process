# Laravel Approval Process Package

**Author**: Ashiq Fardus | **Email**: <ashiqfardus@hotmail.com>  
**Package**: ashiqfardus/laravel-approval-process  
**Laravel**: 10, 11, 12 | **PHP**: 8.1+

Enterprise-grade approval workflow engine with multi-level approvals, SLA management, delegation support, and complete audit logging.

---

## 📦 Installation

```bash
composer require ashiqfardus/laravel-approval-process

php artisan vendor:publish --provider="AshiqFardus\ApprovalProcess\ApprovalProcessServiceProvider" --tag="approval-process-config"
php artisan vendor:publish --provider="AshiqFardus\ApprovalProcess\ApprovalProcessServiceProvider" --tag="approval-process-migrations"
php artisan migrate
```

---

## 🎯 Quick Start

### 1. Make Model Approvable

```php
use AshiqFardus\ApprovalProcess\Traits\Approvable;

class Offer extends Model { use Approvable; }
```

### 2. Create Workflow with Weightage

```php
$workflow = Workflow::create(['name' => 'Offer', 'model_type' => Offer::class]);
$step = ApprovalStep::create([
    'workflow_id' => 1, 
    'name' => 'Manager', 
    'sequence' => 1,
    'minimum_approval_percentage' => 51, // NEW: Only need 51% to proceed
]);

// Add approvers with weightage (voting power)
Approver::create(['approval_step_id' => 1, 'approver_type' => 'user', 'user_id' => 1, 'weightage' => 50]);
Approver::create(['approval_step_id' => 1, 'approver_type' => 'user', 'user_id' => 2, 'weightage' => 30]);
Approver::create(['approval_step_id' => 1, 'approver_type' => 'user', 'user_id' => 3, 'weightage' => 20]);
// Result: If user 1 (50%) + user 2 (30%) approve = 80% ≥ 51% → Step proceeds!
```

### 3. Submit

```php
$offer = Offer::create([...]); 
$approval = $offer->submitForApproval(auth()->id());
```

### 4. Approve/Reject

```php
$engine = app(ApprovalEngine::class);
$engine->approve($approval, auth()->id());
$engine->reject($approval, auth()->id(), 'reason');
```

---

## 📊 Features

### Core Features
✅ Multi-level approvals | ✅ Serial/Parallel/Any-One | ✅ Delegation  
✅ SLA management | ✅ Audit trail | ✅ Rejection & resubmission | ✅ Notifications

### Advanced Features (NEW!)
✅ **Conditional Workflows** - Dynamic routing with 15+ operators  
✅ **Parallel Workflows** - Fork/join patterns with sync types  
✅ **Dynamic Level Management** - Runtime workflow modifications, versioning & rollback

### Document Management (NEW!)
✅ **File Attachments** - Upload, download, virus scanning, access logging  
✅ **Document Templates** - Variable substitution, multiple formats, preview & generation  
✅ **Digital Signatures** - Multiple types, verification, device tracking, secure sharing

### Reporting & Analytics (NEW!)
✅ **Dashboard Analytics** - Real-time stats, approval times, SLA compliance  
✅ **Workflow & User Metrics** - Performance tracking, productivity metrics  
✅ **Bottleneck Detection** - Automatic identification, severity classification, recommendations  
✅ **Custom Reports** - Flexible reports, multiple formats, scheduled execution, audit trails

### UI & Visualization (NEW!)
✅ **Modern Admin Panel** - Beautiful responsive interface with Tailwind CSS  
✅ **Visual Workflow Designer** - Drag-and-drop workflow builder with live preview  
✅ **Interactive Dashboards** - Charts and visualizations with Chart.js  
✅ **Real-time Updates** - Live notifications with Laravel Echo + Pusher  
✅ **Request Management** - Track and manage approval requests with timeline  
✅ **Analytics Dashboards** - Comprehensive performance insights and metrics  
✅ **Mobile Responsive** - Works perfectly on all devices

### Weightage-Based Approvals (NEW! 🎉)
✅ **Dynamic Approval Thresholds** - Set any percentage from 0-100% (51%, 75%, 100%, etc.)  
✅ **Weighted Voting** - Each approver has customizable voting power/weightage  
✅ **Real-time Progress Tracking** - Visual progress bars show current approval percentage  
✅ **Flexible Strategies** - Equal, hierarchical, or majority-one distributions  
✅ **Smart Calculations** - Automatic calculation of remaining approvals needed  
✅ **Validation** - Built-in validation for weightage distributions

---

## 🔌 API Endpoints

**Workflows**: `GET/POST/PUT/DELETE /api/approval-process/workflows`  
**Requests**: `GET/POST /api/approval-process/requests` + actions: `/approve`, `/reject`, `/submit`, `/send-back`, `/hold`, `/cancel`  
**Delegations**: `GET/POST /api/approval-process/delegations` + `/activate`, `/deactivate`

---

## 👥 Approver Types

User | Role | Manager | Department Head | Position | Custom

---

## 🗄️ Tables

**Core**: `approval_workflows` | `approval_steps` | `approval_requests` | `approval_actions` | `approval_approvers` | `approval_delegations`

**Advanced**: `workflow_conditions` | `parallel_step_groups` | `workflow_versions` | `dynamic_step_modifications` | `workflow_modification_rules`

---

## 📖 Examples

```php
// Check visibility
$offer->canBeViewedBy(auth()->id());

// Get status
$approval = $offer->getLastApproval();

// History
foreach ($approval->actions as $action) {
    echo $action->user->name . ' ' . $action->action;
}

// Delegate
Delegation::create([
    'user_id' => 1, 'delegated_to_user_id' => 2,
    'starts_at' => now(), 'ends_at' => now()->addDays(14),
]);
```

---

## 🔮 Query-Based Approvals (New!)

Support for Database Views, Raw SQL, and Legacy Systems.

**1. Create Workflow**

```php
Workflow::create(['name' => 'Sales Report', 'model_type' => 'query:view']);
```

**2. Submit View or SQL**

```php
$service = app(QueryApprovalService::class);

// Database View
$service->submitViewApproval('sales_view', ['month' => 'Oct'], auth()->id());

// Raw SQL
$service->submitSqlApproval("SELECT * FROM legacy_table WHERE id = ?", [1], auth()->id());
```

**Edit & resubmit / change history**: The same API works for query-based requests. Use `POST .../requests/{id}/edit-and-resubmit` with `data_snapshot` (full payload or `{ "data": resultArray }` for query-based). Use `GET .../requests/{id}/change-history` for change history. Approve/reject/send-back use the same endpoints.

---

## 🎯 3-Level Offer Approval Example

**Flow**: Manager → Finance → Director

**Visibility**:

- **Draft**: Creator only
- **Pending**: Creator + Current Approver + Previous Approvers  
- **Rejected**: Creator + Who Rejected
- **Approved**: Everyone

**Creator Messages**:

- Draft: "Edit and submit"
- Manager: "Waiting for Manager approval"
- Rejected: "Rejected. Fix and resubmit"
- Finance: "Approved by Manager. Waiting for Finance"
- Director: "Waiting for Director approval"
- Approved: "✅ Visible to all users"

---

## 🎨 Web Interface

Access the modern admin panel at:

```
http://your-app.com/approval-process/dashboard
```

### Key Pages

- **Dashboard** - Overview with metrics, charts, and pending approvals
- **Workflows** - Manage and create workflows
- **Designer** - Visual drag-and-drop workflow builder
- **My Approvals** - Your pending approval tasks
- **My Requests** - Track your submitted requests
- **Analytics** - Performance insights and bottlenecks
- **Reports** - Custom and audit reports

### Real-time Updates

Configure broadcasting in `.env`:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=your-cluster
```

---

## 🔧 Configuration

`config/approval-process.php`:

```php
[
    'database' => ['prefix' => 'approval_'],
    'features' => ['enable_audit_log' => true],
    'notifications' => ['channels' => ['mail', 'database']],
    'sla' => ['enable_working_days' => true],
    'paths' => ['web_prefix' => 'approval-process'],
]
```

---

## 📚 Complete Documentation

### 🚀 Quick Start Guides

- **[Configuration Guide](docs/CONFIGURATION_GUIDE.md)** - Complete setup for all use cases
  - API-Only Mode (Vue/React/Next.js)
  - Full-Stack Mode (Blade UI)
  - Hybrid Mode (Inertia.js/Livewire)
  
- **[SPA Integration Guide](docs/SPA_INTEGRATION_GUIDE.md)** - Integrate with modern frameworks
  - Vue.js, React, Next.js, Angular
  - Authentication setup (Sanctum, OAuth2)
  - Real-time updates
  - File uploads
  - TypeScript types

- **[API Clients](docs/API_CLIENTS.md)** - Ready-to-use API clients
  - Vue 3 Composition API
  - React Hooks
  - Next.js App Router
  - Angular Services
  - Vanilla JavaScript

### 📖 API Documentation

- **[OpenAPI Specification](docs/openapi.yaml)** - Complete API reference (110+ endpoints)
- **[Documentation Index](docs/DOCUMENTATION_INDEX.md)** - Complete documentation guide

### 🎯 Feature Guides

- [Conditional Workflows](docs/CONDITIONAL_WORKFLOWS.md) - Dynamic routing
- [Parallel Workflows](docs/PARALLEL_WORKFLOWS.md) - Concurrent paths
- [Dynamic Level Management](docs/DYNAMIC_LEVEL_MANAGEMENT.md) - Runtime modifications
- [Document Management](docs/DOCUMENT_MANAGEMENT.md) - Attachments, templates, signatures
- [Reporting & Analytics](docs/REPORTING_AND_ANALYTICS.md) - Dashboards and metrics

### 📊 Phase Summaries

- [Phase 1: Core System](docs/PHASE_1_SUMMARY.md) - Multi-level approvals
- [Phase 2: Advanced Workflows](docs/PHASE_2_SUMMARY.md) - Conditional, parallel, dynamic
- [Phase 3: Document Management](docs/PHASE_3_SUMMARY.md) - Files and templates
- [Phase 4: Reporting & Analytics](docs/PHASE_4_SUMMARY.md) - Metrics and reports
- [Phase 5: UI & Visualization](docs/PHASE_5_SUMMARY.md) - Modern admin panel

---

## 🆘 Troubleshooting

- **Status Not Changing**: Use ApprovalEngine service
- **Migrations Failed**: Run `php artisan migrate:refresh`  
- **No Notifications**: Configure `.env` mail settings
- **API 401 Errors**: Check [Configuration Guide](docs/CONFIGURATION_GUIDE.md#authentication-setup)
- **CORS Issues**: See [SPA Integration Guide](docs/SPA_INTEGRATION_GUIDE.md#configuration)

---

## 📄 License

MIT License

---

## 📊 Package Statistics

- **208 Tests** - All passing with 552 assertions
- **35 Database Tables** - Comprehensive schema
- **30 Models** - Full ORM support
- **16 Services** - Business logic layer
- **20 Controllers** - API + Web interfaces
- **110+ API Routes** - RESTful API
- **20+ Web Routes** - Modern UI
- **10+ Views** - Blade templates
- **3 Broadcast Events** - Real-time updates
- **20+ Documentation Files** - Extensive guides

---

## 👥 Support & Community

- **Documentation:** [docs/DOCUMENTATION_INDEX.md](docs/DOCUMENTATION_INDEX.md)
- **GitHub:** [github.com/ashiqfardus/laravel-approval-process](https://github.com/ashiqfardus/laravel-approval-process)
- **Issues:** Report bugs and request features
- **Email:** ashiqfardus@hotmail.com

---

**Author**: Ashiq Fardus  
**Version**: 1.0.0  
**Status**: Production Ready ✅
