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

### 2. Create Workflow

```php
$workflow = Workflow::create(['name' => 'Offer', 'model_type' => Offer::class]);
$step = ApprovalStep::create(['workflow_id' => 1, 'name' => 'Manager', 'sequence' => 1]);
Approver::create(['approval_step_id' => 1, 'approver_type' => 'role', 'approver_id' => 'manager']);
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

✅ Multi-level approvals | ✅ Serial/Parallel/Any-One | ✅ Conditional steps | ✅ Delegation  
✅ SLA management | ✅ Audit trail | ✅ Rejection & resubmission | ✅ Notifications

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

`approval_workflows` | `approval_steps` | `approval_requests` | `approval_actions` | `approval_approvers` | `approval_delegations`

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

## 🔧 Configuration

`config/approval-process.php`:

```php
[
    'database' => ['prefix' => 'approval_'],
    'features' => ['enable_audit_log' => true],
    'notifications' => ['channels' => ['mail', 'database']],
    'sla' => ['enable_working_days' => true],
]
```

---

## 🆘 Troubleshooting

- **Status Not Changing**: Use ApprovalEngine service
- **Migrations Failed**: Run `php artisan migrate:refresh`  
- **No Notifications**: Configure `.env` mail settings

---

## 📄 License

MIT License

---

**Author**: Ashiq Fardus  
**Email**: <ashiqfardus@hotmail.com>

---

**See OFFER_MODULE_SCENARIO.md, OFFER_VISIBILITY_DIAGRAMS.md, and QUERY_BASED_APPROVAL_EXAMPLES.md for detailed examples.**
